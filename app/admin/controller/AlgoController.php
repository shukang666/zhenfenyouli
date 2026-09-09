<?php

namespace app\admin\controller;

use app\algo\AlgoRegistry;
use cmf\controller\AdminBaseController;

/**
 * PC 后台 - 算法中心
 *
 * 只读展示项目代码中注册的算法（感知哈希打卡查重等）。
 * 算法清单由 app/algo/AlgoRegistry.php 代码注册，后台不可新增/删除，页面自动适配。
 */
class AlgoController extends AdminBaseController
{
    /** 列表：一行一条算法 */
    public function index()
    {
        $list = [];
        foreach (AlgoRegistry::all() as $meta) {
            $meta['status_label'] = $this->statusLabel($meta['status']);
            $list[] = $meta;
        }
        $this->assign('list', $list);
        return $this->fetch('index');
    }

    /** 详情 */
    public function detail()
    {
        $key  = (string)$this->request->param('id', '');
        $meta = AlgoRegistry::byKey($key);
        if ($meta === null) {
            $this->error('算法不存在');
        }
        $meta['status_label'] = $this->statusLabel($meta['status']);
        $meta['paras'] = array_values(array_filter(explode("\n", str_replace("\r", '', $meta['principle']))));
        $this->assign('meta', $meta);
        return $this->fetch('detail');
    }

    private function statusLabel(string $status): string
    {
        return [
            'online'  => '使用中',
            'develop' => '开发中',
            'offline' => '已下线',
        ][$status] ?? $status;
    }
}
