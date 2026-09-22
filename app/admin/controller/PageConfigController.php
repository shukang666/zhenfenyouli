<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

/**
 * 页面图片配置
 * 管理小程序各页面头部背景图等可配置图片资源
 */
class PageConfigController extends AdminBaseController
{
    /**
     * 页面图片配置页面
     */
    public function index()
    {
        $option_name = 'set_config';

        if ($this->request->isPost()) {
            $qxl_hero_bg = $this->request->param('qxl_hero_bg', '');
            $xsdld_hero_bg = $this->request->param('xsdld_hero_bg', '');
            $dtsh_banner = $this->request->param('dtsh_banner', '');

            // 读取现有配置并更新
            $config = cmf_get_option($option_name);
            if (!is_array($config)) {
                $config = [];
            }
            $config['qxl_hero_bg'] = $qxl_hero_bg;
            $config['xsdld_hero_bg'] = $xsdld_hero_bg;
            $config['dtsh_banner'] = $dtsh_banner;

            $res = cmf_set_option($option_name, $config, true);
            if ($res !== false) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        } else {
            $config = cmf_get_option($option_name);
            $qxl_hero_bg = $config['qxl_hero_bg'] ?? '';
            $xsdld_hero_bg = $config['xsdld_hero_bg'] ?? '';
            $dtsh_banner = $config['dtsh_banner'] ?? '';
            $this->assign('qxl_hero_bg', $qxl_hero_bg);
            $this->assign('xsdld_hero_bg', $xsdld_hero_bg);
            $this->assign('dtsh_banner', $dtsh_banner);
            return $this->fetch();
        }
    }
}
