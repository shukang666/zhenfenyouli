<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

/**
 * 旧「志愿者整改清单」已并入整改任务管理。
 * 保留控制器是为了旧菜单/收藏夹 302，不要再往 active_shot_rectify 写数据。
 */
class ShotRectifyController extends AdminBaseController
{
    public function index()
    {
        return redirect(url('admin/VolunteerTask/index'));
    }

    public function rectified()
    {
        $this->error('请到「整改任务管理」处理');
    }
}
