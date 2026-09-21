<?php
// 覆盖 vendor/thinkcmf/cmf-app/src/admin/controller/PublicController.php
// 原因：外勤 / OA 与驾驶舱登录态分开（FIELD_WORKER_* / OA_* / ADMIN_ID）。
// 框架默认「已有 ADMIN_ID 就跳后台首页」，独立入口未登录时不能被驾驶舱 session 劫持。
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
        // 独立入口（外勤 / OA）即使浏览器里还有驾驶舱 ADMIN_ID，也不得跳后台首页
        $isolated = ['field', 'oa'];
        if (!empty($admin_id) && !in_array((string) $entry, $isolated, true)) {
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
