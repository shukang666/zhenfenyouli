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
     * 初始化视图配置（关键！）
     * 本控制器继承 BaseController 而非 AdminBaseController（避免后台登录拦截），
     * 但 BaseController 不设置 view_base，模板会错误解析到站点根目录 view/admin/ 下。
     * 这里手动复刻 AdminBaseController 的视图配置，让模板解析到后台主题目录。
     */
    protected function _initializeView()
    {
        $cmfAdminThemePath    = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = cmf_get_current_admin_theme();

        $themePath = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}";

        $root = cmf_get_root();

        // 使 cdn 设置生效（与 AdminBaseController 行为保持一致）
        $cdnSettings = cmf_get_option('cdn_settings');
        if (empty($cdnSettings['cdn_static_root'])) {
            $viewReplaceStr = [
                '__ROOT__'     => $root,
                '__TMPL__'     => "{$root}/{$themePath}",
                '__STATIC__'   => "{$root}/static",
                '__WEB_ROOT__' => $root
            ];
        } else {
            $cdnStaticRoot  = rtrim($cdnSettings['cdn_static_root'], '/');
            $viewReplaceStr = [
                '__ROOT__'     => $root,
                '__TMPL__'     => "{$cdnStaticRoot}/{$themePath}",
                '__STATIC__'   => "{$cdnStaticRoot}/static",
                '__WEB_ROOT__' => $cdnStaticRoot
            ];
        }

        $viewBase = WEB_ROOT . $themePath . '/';

        $this->view->engine()->config([
            'view_base'          => $viewBase,
            'tpl_replace_string' => $viewReplaceStr
        ]);
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

        $this->assign('workerName', session('FIELD_WORKER_NAME'));
        return $this->fetch('field_worker/index');
    }

    /**
     * 搜索小区/点位（复用 search_community 逻辑，返回 name + parent_name）
     */
    public function searchCommunity()
    {
        $keyword = trim($this->request->param('keyword', ''));
        $page    = intval($this->request->param('page', 1));
        $pageSize = intval($this->request->param('page_size', 20));

        // 去除特殊符号（与小程序 search_community 一致）
        $pureKeyword = preg_replace('/[^\p{Han}a-zA-Z0-9]/u', '', $keyword);

        $ignoreSymbols = [' ','　','·','•','‧','・','∙','･','.','．','。',',','，','-','–','—','－','_','~','～','(',')','（',')','[',']','【',']','“','”','‘','’'];
        $nameExpr = 'c.name';
        foreach ($ignoreSymbols as $symbol) {
            $nameExpr = "REPLACE({$nameExpr}, '{$symbol}', '')";
        }

        $query = Db::name('member_community')->alias('c')
            ->leftJoin('member_community_parent p', 'p.id = c.pid')
            ->field('c.id, c.name, c.pid, p.name as parent_name');

        if ($pureKeyword !== '') {
            $query->whereRaw("{$nameExpr} LIKE :keyword", ['keyword' => '%' . $pureKeyword . '%']);
        }

        $query->order('c.list_order asc');

        $total = $query->count();
        $list = $query->limit(($page - 1) * $pageSize, $pageSize)->select();

        $this->success('ok', [
            'data'      => $list,
            'total'     => $total,
            'last_page' => ceil($total / $pageSize),
            'page'      => $page,
        ]);
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
     * 前端传入：community_id(小区ID), community_name(社区名=parent_name), unit_name(小区名)
     * 与督导监测表单保持一致：community_name 存社区名，unit_name 存小区/点位名
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
        $unitName      = trim($this->request->param('unit_name', ''));
        $doorPhoto     = trim($this->request->param('door_photo', ''));
        $innerPhoto    = trim($this->request->param('inner_photo', ''));
        $publicityPhoto= trim($this->request->param('publicity_photo', ''));

        if ($communityId <= 0) {
            $this->error('请选择所属社区');
        }
        if (empty($doorPhoto) || empty($innerPhoto) || empty($publicityPhoto)) {
            $this->error('请上传全部三张照片');
        }

        $data = [
            'user_id'         => $this->admin_id,
            'community_id'    => $communityId,
            'community_name'  => $communityName,
            'unit_name'       => $unitName,
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
