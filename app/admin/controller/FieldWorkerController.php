<?php
// +----------------------------------------------------------------------
// | 外勤工作台 - 录入端（复用后台账密体系）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\BaseController;
use think\Db;

/**
 * 外勤工作台入口
 * 外勤人员用后台账号登录 → 进入简化版点位巡查
 * 登录路由：/admin/field_worker/login
 */
class FieldWorkerController extends BaseController
{
    protected $admin_id;

    protected function initialize()
    {
        parent::initialize();
        $this->admin_id = session('FIELD_WORKER_ID');
    }

    /**
     * 是否已登录
     */
    protected function isLoggedIn()
    {
        return !empty($this->admin_id);
    }

    /**
     * 要求登录
     */
    protected function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            if ($this->request->isPost() || $this->request->isAjax()) {
                $this->error('请先登录', url('admin/FieldWorker/login'));
            } else {
                return $this->redirect(url('admin/FieldWorker/login'));
            }
        }
    }

    /**
     * 外勤工作台登录页
     */
    public function login()
    {
        if ($this->isLoggedIn()) {
            return redirect(url('admin/FieldWorker/index'));
        }
        return $this->fetch('field_worker/login');
    }

    /**
     * 登录验证（复用后台账密）
     */
    public function doLogin()
    {
        if (!$this->request->isPost()) {
            $this->error('非法登录!');
        }

        $name = $this->request->param('username', '', 'trim');
        $pass = $this->request->param('password', '', 'trim');

        if (empty($name)) {
            $this->error('请输入用户名');
        }
        if (empty($pass)) {
            $this->error('请输入密码');
        }

        if (strpos($name, "@") > 0) {
            $where[] = ['user_email', '=', $name];
        } else {
            $where[] = ['user_login|mobile', '=', $name];
        }
        $where[] = ['user_type', 'in', [1, 3]];

        $user = Db::name('user')->where($where)->find();

        if (empty($user)) {
            $this->error('账号或密码错误');
        }

        if (!cmf_compare_password($pass, $user['user_pass'])) {
            $this->error('账号或密码错误');
        }

        if ($user['id'] != 1 && $user['user_status'] == 0) {
            $this->error('该账号已被禁用');
        }

        // 登录成功
        session('FIELD_WORKER_ID', $user['id']);
        session('FIELD_WORKER_NAME', $user['user_login']);

        $this->success('登录成功', url('admin/FieldWorker/index'));
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        session('FIELD_WORKER_ID', null);
        session('FIELD_WORKER_NAME', null);
        return redirect(url('admin/FieldWorker/login'));
    }

    /**
     * 工作台首页 - 点位巡查
     */
    public function index()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        // 加载社区列表
        $communityList = Db::name('community')
            ->field('id, name')
            ->order('list_order asc')
            ->select();

        $this->assign('communityList', $communityList);
        $this->assign('workerName', session('FIELD_WORKER_NAME'));
        return $this->fetch('field_worker/index');
    }

    /**
     * 获取子级点位列表（根据社区ID或父级ID）
     */
    public function getPoints()
    {
        $parentId = intval($this->request->param('parent_id', 0));
        if ($parentId <= 0) {
            $this->error('参数错误');
        }
        $list = Db::name('community')
            ->field('id, name')
            ->where('pid', $parentId)
            ->order('list_order asc')
            ->select();
        $this->success($list);
    }

    /**
     * 上传图片
     */
    public function upload()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }

        $file = $this->request->file('file');
        if (!$file) {
            $this->error('请选择文件');
        }

        $uploader = new \cmf\lib\Upload();
        $uploader->setFileType('image');
        $result = $uploader->upload();

        if ($result === false) {
            $this->error($uploader->getError());
        }

        unset($result['name'], $result['id'], $result['preview_url']);
        $this->success('上传成功!', $result);
    }

    /**
     * 提交点位巡查
     */
    public function submit()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }

        $communityId   = intval($this->request->param('community_id', 0));
        $communityName = trim($this->request->param('community_name', ''));
        $pointName     = trim($this->request->param('point_name', ''));
        $doorPhoto     = trim($this->request->param('door_photo', ''));
        $innerPhoto    = trim($this->request->param('inner_photo', ''));
        $publicityPhoto= trim($this->request->param('publicity_photo', ''));

        if ($communityId <= 0) {
            $this->error('请选择所属社区');
        }
        if (empty($pointName)) {
            $this->error('请填写点位名称');
        }
        if (empty($doorPhoto) || empty($innerPhoto) || empty($publicityPhoto)) {
            $this->error('请上传全部三张照片');
        }

        $data = [
            'user_id'         => $this->admin_id,
            'community_id'    => $communityId,
            'community_name'  => $communityName,
            'unit_name'       => $pointName,
            'unit_type'       => 1,
            'check_date'      => date('Y-m-d'),
            'address'         => '',
            'contact'         => '',
            'contact_phone'   => '',
            'has_waste'       => 1,
            'waste_correct'   => 1,
            'waste_flow'      => '',
            'door_photo'      => $doorPhoto,
            'inner_photo'     => $innerPhoto,
            'publicity_photo' => $publicityPhoto,
            'inner_qualified' => 1,
            'need_rectify'    => 0,
            'inner_rectify_photo' => '',
            'point_photo'     => '',
            'point_qualified' => 1,
            'point_rectify_photo' => '',
            'manage_photo'    => '',
            'manage_qualified'=> 1,
            'manage_rectify_photo' => '',
            'transport_photo' => '',
            'catering_photo'  => '',
            'accuracy_photo'  => '',
            'overall_evaluation' => '',
            'create_time'     => time(),
        ];

        Db::name('supervision_monitor')->insert($data);

        $this->success('提交成功');
    }

    /**
     * 列表页 — JSON接口供前端Tab加载
     */
    public function records()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        $page   = intval($this->request->param('page', 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $total = Db::name('supervision_monitor')
            ->where('user_id', $this->admin_id)
            ->count();

        $list = Db::name('supervision_monitor')
            ->where('user_id', $this->admin_id)
            ->field('id, community_name, unit_name, check_date, door_photo, inner_photo, publicity_photo')
            ->order('id desc')
            ->limit($offset, $limit)
            ->select();

        $this->success('ok', [
            'list'      => $list,
            'total'     => $total,
            'page'      => $page,
            'totalPage' => ceil($total / $limit),
        ]);
    }
}
