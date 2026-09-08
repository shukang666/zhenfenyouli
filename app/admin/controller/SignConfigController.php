<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

/**
 * 签到积分配置
 * 管理小程序首页每日签到赠送积分数
 */
class SignConfigController extends AdminBaseController
{
    /**
     * 签到积分配置页面
     */
    public function index()
    {
        $option_name = 'set_config';

        if ($this->request->isPost()) {
            $sign_point = intval($this->request->param('sign_point', 5));
            if ($sign_point < 0) {
                $this->error('积分不能为负数');
            }
            if ($sign_point > 1000) {
                $this->error('单次签到积分上限为1000');
            }

            // 读取现有配置并更新 sign_point
            $config = cmf_get_option($option_name);
            if (!is_array($config)) {
                $config = [];
            }
            $config['sign_point'] = $sign_point;

            $res = cmf_set_option($option_name, $config, true);
            if ($res !== false) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        } else {
            $config = cmf_get_option($option_name);
            $sign_point = intval($config['sign_point'] ?? 5);
            $this->assign('sign_point', $sign_point);
            return $this->fetch();
        }
    }
}
