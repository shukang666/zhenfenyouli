<?php
// +----------------------------------------------------------------------
// | 移动端 OA：独立入口，复用后台账密，登录态与驾驶舱 / Pad 分开
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\BaseController;
use think\facade\Db;

/**
 * 手机 OA 办公入口
 * session：OA_ID / OA_NAME，禁止写 ADMIN_ID、FIELD_WORKER_*
 * 登录页独立，不走 /admin/public/login
 */
class OaController extends BaseController
{
    protected $admin_id;

    protected function initialize()
    {
        parent::initialize();
        $this->admin_id = session('OA_ID');
    }

    protected function fetch($template = '', $vars = [], $config = [])
    {
        $template = $this->parseTemplate($template);
        return $this->view->fetch($template, $vars, $config);
    }

    protected function parseTemplate($template)
    {
        $request = $this->request;

        if (strpos($template, '@')) {
            list($app, $template) = explode('@', $template);
        }

        $cmfAdminThemePath    = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = cmf_get_current_admin_theme();
        $themePath            = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}/";

        $app = isset($app) ? $app : $this->app->http->getName();

        $depr = config('view.view_depr');
        if (0 !== strpos($template, '/')) {
            $template   = str_replace(['/', ':'], $depr, $template);
            $controller = cmf_parse_name($request->controller());
            if ($controller) {
                if ('' == $template) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . cmf_parse_name($request->action(false));
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }

        return $themePath . ($app ? $app . DIRECTORY_SEPARATOR : '') . ltrim($template, '/') . '.' . ltrim(config('view.view_suffix'), '.');
    }

    protected function _initializeView()
    {
        $cmfAdminThemePath    = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = cmf_get_current_admin_theme();
        $themePath            = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}";
        $root                 = cmf_get_root();

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

        $this->view->engine()->config([
            'view_base'          => WEB_ROOT . $themePath . '/',
            'tpl_replace_string' => $viewReplaceStr
        ]);
    }

    protected function isLoggedIn()
    {
        return !empty($this->admin_id);
    }

    protected function oaLoginUrl()
    {
        return (string) url('admin/Oa/login');
    }

    protected function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            if ($this->request->isPost() || $this->request->isAjax()) {
                $this->error('请先登录', $this->oaLoginUrl());
            }
            return $this->redirect($this->oaLoginUrl());
        }
    }

    protected function assignOa()
    {
        $this->assign('oaName', session('OA_NAME') ?: '');
    }

    public function login()
    {
        if ($this->isLoggedIn()) {
            return redirect(url('admin/Oa/index'));
        }
        return $this->fetch('oa/login');
    }

    public function doLogin()
    {
        if (!$this->request->isPost()) {
            $this->error('非法登录!');
        }

        $name = $this->request->param('username', '', 'trim');
        $pass = $this->request->param('password', '', 'trim');

        if ($name === '') {
            $this->error('请输入账号');
        }
        if ($pass === '') {
            $this->error('请输入密码');
        }

        if (strpos($name, '@') > 0) {
            $where[] = ['user_email', '=', $name];
        } else {
            $where[] = ['user_login|mobile', '=', $name];
        }
        $where[] = ['user_type', 'in', [1, 3]];

        $user = Db::name('user')->where($where)->find();
        if (empty($user) || !cmf_compare_password($pass, $user['user_pass'])) {
            $this->error('账号或密码错误');
        }
        if ($user['id'] != 1 && empty($user['user_status'])) {
            $this->error('该账号已被禁用');
        }

        session('OA_ID', $user['id']);
        session('OA_NAME', $user['user_login']);

        $this->success('登录成功', url('admin/Oa/index'));
    }

    public function logout()
    {
        session('OA_ID', null);
        session('OA_NAME', null);
        return redirect($this->oaLoginUrl());
    }

    public function index()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        $hour = (int) date('H');
        if ($hour < 6) {
            $greet = '凌晨好';
        } elseif ($hour < 9) {
            $greet = '早上好';
        } elseif ($hour < 12) {
            $greet = '上午好';
        } elseif ($hour < 14) {
            $greet = '中午好';
        } elseif ($hour < 18) {
            $greet = '下午好';
        } else {
            $greet = '晚上好';
        }

        $week   = ['日', '一', '二', '三', '四', '五', '六'];
        $today0 = strtotime(date('Y-m-d'));

        $shotPending     = (int) Db::name('active_shot_report')->where('status', 0)->count();
        $taskLogPending  = (int) Db::name('volunteer_task_log')->where('status', 1)->count();
        $checkPending    = (int) Db::name('check')->where('status', 1)->count();
        $shopPending     = (int) Db::name('member_shop')->where('delete_time', 0)->where('status', 1)->count();
        $todoTotal       = $shotPending + $taskLogPending + $checkPending + $shopPending;

        $clockToday = (int) Db::name('forum')->where('delete_time', 0)->where('create_time', '>=', $today0)->count();
        $userToday  = (int) Db::name('member')->where('create_time', '>=', $today0)->count();

        $this->assignOa();
        $this->assign('greet', $greet);
        $this->assign('dateText', date('n月j日') . ' 星期' . $week[(int) date('w')]);
        $this->assign('todoTotal', $todoTotal);
        $this->assign('clockToday', $clockToday);
        $this->assign('userToday', $userToday);
        $this->assign('todos', [
            ['name' => '随手拍待审', 'count' => $shotPending, 'hint' => '问题举报', 'zero' => $shotPending ? 0 : 1],
            ['name' => '整改待审', 'count' => $taskLogPending, 'hint' => '志愿提交', 'zero' => $taskLogPending ? 0 : 1],
            ['name' => '督导申请', 'count' => $checkPending, 'hint' => '入驻审核', 'zero' => $checkPending ? 0 : 1],
            ['name' => '商家入驻', 'count' => $shopPending, 'hint' => '资料待核', 'zero' => $shopPending ? 0 : 1],
        ]);
        return $this->fetch('oa/index');
    }

    public function profile()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }

        $user = Db::name('user')
            ->where('id', $this->admin_id)
            ->field('id,user_login,user_nickname,user_email,mobile,last_login_time')
            ->find();
        if (empty($user)) {
            $user = [];
        }

        $lastLoginText = '—';
        if (!empty($user['last_login_time'])) {
            $lastLoginText = date('Y-m-d H:i', intval($user['last_login_time']));
        }

        $this->assignOa();
        $this->assign('user', $user);
        $this->assign('lastLoginText', $lastLoginText);
        return $this->fetch('oa/profile');
    }

    public function changePassword()
    {
        $check = $this->requireLogin();
        if ($check instanceof \think\response\Redirect) {
            return $check;
        }
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }

        $oldPassword = $this->request->param('old_password', '', 'trim');
        $password    = $this->request->param('password', '', 'trim');
        $rePassword  = $this->request->param('re_password', '', 'trim');

        if ($oldPassword === '') {
            $this->error('请输入原密码');
        }
        if ($password === '') {
            $this->error('请输入新密码');
        }
        if (strlen($password) < 6) {
            $this->error('新密码至少 6 位');
        }
        if ($password !== $rePassword) {
            $this->error('两次输入的新密码不一致');
        }

        $user = Db::name('user')->where('id', $this->admin_id)->find();
        if (empty($user)) {
            $this->error('账号不存在');
        }
        if (!cmf_compare_password($oldPassword, $user['user_pass'])) {
            $this->error('原密码不正确');
        }
        if (cmf_compare_password($password, $user['user_pass'])) {
            $this->error('新密码不能与原密码相同');
        }

        Db::name('user')->where('id', $this->admin_id)->update([
            'user_pass' => cmf_password($password),
        ]);

        $this->success('密码已修改');
    }
}
