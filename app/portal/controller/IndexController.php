<?php
// 覆盖 vendor 默认 portal 首页：本项目无 simpleboot3 前台 CMS，访问根域名应进后台入口。
namespace app\portal\controller;

use cmf\controller\HomeBaseController;

class IndexController extends HomeBaseController
{
    public function index()
    {
        $adminSettings = cmf_get_option('admin_settings');
        $root          = cmf_get_root();

        if (!empty($adminSettings['admin_password'])) {
            return redirect($root . '/' . $adminSettings['admin_password']);
        }

        return redirect((string) url('admin/Index/index'));
    }
}
