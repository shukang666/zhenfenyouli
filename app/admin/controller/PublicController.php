<?php
// 覆盖 vendor/thinkcmf/cmf-app/src/admin/controller/PublicController.php
// 原因：工作台与驾驶舱共用 /admin/public/login，但登录态分开（FIELD_WORKER_* / ADMIN_ID）。
// 框架默认「已有 ADMIN_ID 就跳后台首页」，且 URL 的 #field 到不了服务端，
// 会把「工作台未登录」误判成「驾驶舱已登录」，把人送进管理系统。
namespace app\admin\controller;

use app\admin\model\RoleUserModel;
use app\admin\model\UserModel;
use cmf\controller\AdminBaseController;

class PublicController extends AdminBaseController
{
    public function initialize()
    {
    }

    /**
     * 后台登陆界面
     */
    public function login()
    {
        $loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
        if (empty($loginAllowed)) {
            return redirect(cmf_get_root() . "/");
        }

        $entry    = $this->request->param('entry', '');
        $admin_id = session('ADMIN_ID');
        // 进外勤标签时不要用驾驶舱 session 把人送进后台首页
        if (!empty($admin_id) && $entry !== 'field') {
            return redirect(url("admin/Index/index"));
        }

        session("__SP_ADMIN_LOGIN_PAGE_SHOWED_SUCCESS__", true);
        $result = hook_one('admin_login');
        if (!empty($result)) {
            return $result;
        }
        return $this->fetch(":login");
    }

    /**
     * 登录验证
     */
    public function doLogin()
    {
        if (!$this->request->isPost()) {
            $this->error('非法登录!');
        }
        if (hook_one('admin_custom_login_open')) {
            $this->error('您已经通过插件自定义后台登录！');
        }

        $loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
        if (empty($loginAllowed)) {
            $this->error('非法登录!', cmf_get_root() . '/');
        }

        $name = $this->request->param("username");
        if (empty($name)) {
            $this->error(lang('USERNAME_OR_EMAIL_EMPTY'));
        }
        $pass = $this->request->param("password");
        if (empty($pass)) {
            $this->error(lang('PASSWORD_REQUIRED'));
        }
        if (strpos($name, "@") > 0) {
            $where[] = ['user_email', '=', $name];
        } else {
            $where[] = ['user_login|mobile', '=', $name];
        }
        $where[] = ['user_type', 'in', [1, 3]];

        $result = UserModel::where($where)->find();

        if (!empty($result)) {
            if (cmf_compare_password($pass, $result['user_pass'])) {
                $groups = RoleUserModel::alias("a")
                    ->join('role b', 'a.role_id =b.id')
                    ->where(["user_id" => $result["id"], "status" => 1])
                    ->value("role_id");
                if ($result["id"] != 1 && (empty($groups) || empty($result['user_status']))) {
                    $this->error(lang('USE_DISABLED'));
                }
                session('ADMIN_ID', $result["id"]);

                session('name', $result["user_login"]);
                $data                    = [];
                $data['last_login_ip']   = get_client_ip(0, true);
                $data['last_login_time'] = time();
                $token                   = cmf_generate_user_token($result["id"], 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
                UserModel::where('id', $result['id'])->update($data);
                cookie("admin_username", $name, 3600 * 24 * 30);
                session("__LOGIN_BY_CMF_ADMIN_PW__", null);
                $this->success(lang('LOGIN_SUCCESS'), url("admin/Index/index"));
            } else {
                $this->error(lang('PASSWORD_NOT_RIGHT'));
            }
        } else {
            $this->error(lang('USERNAME_NOT_EXIST'));
        }
    }

    /**
     * 后台管理员退出
     */
    public function logout()
    {
        session('ADMIN_ID', null);
        return redirect(url('/', [], false, true));
    }
}
