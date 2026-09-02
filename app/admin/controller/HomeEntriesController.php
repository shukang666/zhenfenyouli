<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

/**
 * 首页入口配置管理
 * 管理小程序首页四个核心功能入口的图片和跳转
 */
class HomeEntriesController extends AdminBaseController
{
    /**
     * 首页入口配置页面
     */
    public function index()
    {
        $option_name = 'home_entries';

        if ($this->request->isPost()) {
            $data = $this->request->param('entries/a', []);
            if (count($data) !== 4) {
                $this->error('必须配置全部4个入口');
            }
            // 清理数据，只保留需要的字段
            $clean = [];
            foreach ($data as $item) {
                $clean[] = [
                    'key'  => trim($item['key'] ?? ''),
                    'name' => trim($item['name'] ?? ''),
                    'desc' => trim($item['desc'] ?? ''),
                    'image'=> trim($item['image'] ?? ''),
                    'url'  => trim($item['url'] ?? ''),
                    'type' => trim($item['type'] ?? 'navigateTo'),
                ];
            }
            $res = cmf_set_option($option_name, $clean);
            if ($res !== false) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        } else {
            $entries = cmf_get_option($option_name);
            if (empty($entries) || !is_array($entries) || count($entries) !== 4) {
                // 默认 fallback（与前端一致）
                $entries = [
                    ['key' => 'active',    'name' => '社区活动', 'desc' => '参加低碳环保活动', 'image' => '', 'url' => '/pages/low_carbon/low_carbon', 'type' => 'switchTab'],
                    ['key' => 'health',    'name' => '健康测试', 'desc' => '关注身体健康',     'image' => '', 'url' => '/pages/health/health',         'type' => 'switchTab'],
                    ['key' => 'mall',      'name' => '积分商城', 'desc' => '环保好礼兑换',     'image' => '', 'url' => '/pages/mall/mall',             'type' => 'switchTab'],
                    ['key' => 'community', 'name' => '社区互动', 'desc' => '分享绿色生活',     'image' => '', 'url' => '/pages/sjhd/sjhd',             'type' => 'navigateTo'],
                ];
            }
            $this->assign('entries', $entries);
            return $this->fetch();
        }
    }
}
