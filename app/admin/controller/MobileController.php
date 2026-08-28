<?php
// +----------------------------------------------------------------------
// | 移动端H5管理（外勤人员操作，账号与PC后台一致，共用登录态）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\member\CheckModel;
use app\admin\model\member\MemberModel;
use app\admin\model\UserModel;
use cmf\controller\AdminBaseController;

class MobileController extends AdminBaseController
{
    //免登录动作
    private $noNeedLogin = ['login', 'dologin'];

    public function initialize()
    {
        //与PC后台共用登录态（session ADMIN_ID），未登录跳转移动端登录页
        $action = strtolower($this->request->action());
        if (in_array($action, $this->noNeedLogin)) {
            return;
        }

        $admin_id = session('ADMIN_ID');
        if (empty($admin_id)) {
            $this->redirect(url('mobile/login'));
        }

        $user = UserModel::where('id', $admin_id)->find();
        if (empty($user) || ($admin_id != 1 && empty($user['user_status']))) {
            session('ADMIN_ID', null);
            $this->redirect(url('mobile/login'));
        }

        $this->admin_id = $admin_id;
        $this->assign('admin', $user);
    }

    /**
     * 移动端登录页
     */
    public function login()
    {
        if (!empty(session('ADMIN_ID'))) {
            $this->redirect(url('mobile/check_index'));
        }
        session("__LOGIN_BY_CMF_ADMIN_PW__", true);
        return $this->fetch('mobile/login');
    }

    /**
     * 登录提交（校验逻辑与PC后台一致）
     */
    public function doLogin()
    {
        if (!$this->request->isPost()) {
            $this->error('非法登录!');
        }
        if (empty(session("__LOGIN_BY_CMF_ADMIN_PW__"))) {
            $this->error('非法登录!', url('mobile/login'));
        }

        $name = $this->request->param('username', '', 'trim');
        $pass = $this->request->param('password', '', 'trim');
        if (empty($name)) $this->error('请输入账号');
        if (empty($pass)) $this->error('请输入密码');

        $where[] = ['user_login|mobile', '=', $name];
        $where[] = ['user_type', 'in', [1, 3]];
        $result = UserModel::where($where)->find();

        if (empty($result)) {
            $this->error('账号不存在');
        }
        if (!cmf_compare_password($pass, $result['user_pass'])) {
            $this->error('密码错误');
        }
        if ($result['id'] != 1 && empty($result['user_status'])) {
            $this->error('账号已被禁用');
        }

        session('ADMIN_ID', $result['id']);
        session('name', $result['user_login']);
        $data                    = [];
        $data['last_login_ip']   = get_client_ip(0, true);
        $data['last_login_time'] = time();
        $token                   = cmf_generate_user_token($result['id'], 'web');
        if (!empty($token)) {
            session('token', $token);
        }
        UserModel::where('id', $result['id'])->update($data);
        session("__LOGIN_BY_CMF_ADMIN_PW__", null);
        $this->success('登录成功', url('mobile/check_index'));
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        session('ADMIN_ID', null);
        $this->redirect(url('mobile/login'));
    }

    /**
     * 督导员申请列表
     */
    public function check_index(CheckModel $checkModel)
    {
        $params = $this->request->param();
        $where  = [];
        $status = isset($params['status']) ? intval($params['status']) : 1; //默认查待审核
        if ($status > 0) {
            $where[] = ['status', '=', $status];
        }
        if (!empty($params['keyword'])) {
            $where[] = ['name|mobile|community', 'like', '%' . trim($params['keyword']) . '%'];
        }

        $list = $checkModel->where($where)->order('id desc')->paginate(10);
        $list->appends(['status' => $status, 'keyword' => $params['keyword'] ?? '']);
        $status_list = $checkModel->get_status();

        foreach ($list as &$item) {
            $item['status_name'] = $status_list[$item['status']] ?? '';
            $item['active_name'] = $checkModel->get_type()[$item['active_type']] ?? '';
            $item['sign_image']  = empty($item['sign_image']) ? '' : cmf_get_asset_url($item['sign_image']);
            $item['pdf_url']     = empty($item['pdf_file']) ? '' : cmf_get_image_preview_url($item['pdf_file']);
            $item['create_name'] = date('Y-m-d H:i', $item['create_time']);
        }
        unset($item);

        $this->assign('status', $status);
        $this->assign('keyword', $params['keyword'] ?? '');
        $this->assign('list', $list);
        //分页状态预计算（该版本 Paginator 无 onFirstPage/hasMorePages，用 currentPage/lastPage 算术判断）
        $curPage  = $list->currentPage();
        $lastPage = $list->lastPage();
        $this->assign('show_page', $lastPage > 1);
        $this->assign('is_first', $curPage <= 1);
        $this->assign('is_last', $curPage >= $lastPage);
        $this->assign('cur_page', $curPage);
        $this->assign('last_page', $lastPage);
        $this->assign('is_empty', count($list) == 0);
        return $this->fetch('mobile/check_index');
    }

    /**
     * 督导员审核（逻辑与PC后台 CheckController::fix_status 一致）
     */
    public function check_fix_status(CheckModel $checkModel)
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id     = $this->request->param('id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        $msg    = $this->request->param('text', '', 'trim');
        if (empty($id) || empty($status)) $this->error('参数错误');

        $item = $checkModel->where('id', $id)->find();
        if (empty($item)) $this->error('申请不存在');
        if ($item['status'] != 1) $this->error('该申请已审核');

        if ($status == 1) {
            $checkModel->where('id', $id)->save(['status' => 2]);
            //把关联的用户设为督导员
            MemberModel::where('id', $item['user_id'])->update(['is_supervisor' => 1]);
            $this->success('审核通过！');
        } else {
            if (empty($msg)) $this->error('请填写驳回原因');
            $checkModel->where('id', $id)->save(['msg' => $msg, 'status' => 3]);
            $this->success('已驳回！');
        }
    }
}
