<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

/**
 * 首页走马灯通知配置
 * 管理小程序首页头图与功能入口之间的滚动通知文字
 */
class HomeNoticeController extends AdminBaseController
{
    /**
     * 走马灯通知配置页面
     */
    public function index()
    {
        $option_name = 'home_notice';

        if ($this->request->isPost()) {
            $notice = $this->request->param('notice', '');
            $notice = trim($notice);
            // cmf_set_option 要求 data 为数组，存为 ['text' => $notice]
            $res = cmf_set_option($option_name, ['text' => $notice], true);
            if ($res !== false) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        } else {
            $option = cmf_get_option($option_name);
            $notice = '';
            if (is_array($option) && !empty($option['text'])) {
                $notice = $option['text'];
            }
            $this->assign('notice', $notice);
            return $this->fetch();
        }
    }
}
