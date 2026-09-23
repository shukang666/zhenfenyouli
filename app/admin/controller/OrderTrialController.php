<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use api\user\model\UserModel;
use api\wxapp\controller\RefundController;
use api\wxapp\model\AccountLogModel;
use api\wxapp\model\GoodsModel;
use api\wxapp\model\GoodsSkuModel;
use api\wxapp\model\OrderCommentModel;
use api\wxapp\model\OrderStatusModel;
use api\wxapp\model\ShopOrderDetailModel;
use api\wxapp\model\ShopOrderModel;
use app\admin\model\member\MemberModel;
use app\admin\model\order\AfterSaleModel;
use app\user\model\ThirdPartyUserModel;
use cmf\controller\AdminBaseController;
use think\facade\Db;
use think\facade\Log;
use think\Model;

/**
 * 订单试运行工作台
 * @package app\admin\controller
 */
class OrderTrialController extends AdminBaseController
{
    /**
     * 试运行首页
     */
    public function index()
    {
        $params = $this->request->param();
        $Model = new ShopOrderModel();
        $map = [];
        $map[] = ['a.delete_time', '=', 0];

        if (!empty($params['order_num'])) $map[] = ['a.order_num|a.pay_order_num', 'like', "%{$params['order_num']}%"];
        if (!empty($params['username']))  $map[] = ['a.username|nickname', 'like', "%{$params['username']}%"];
        if (!empty($params['phone']))     $map[] = ['a.phone|mobile', 'like', "%{$params['phone']}%"];
        if (isset($params['status']) && $params['status'] !== '') {
            if ($params['status'] == 10) {
                $map[] = ['a.status', 'in', [10, 16]];
            } elseif ($params['status'] == 2) {
                $map[] = ['a.status', 'in', [2, 14]];
            } elseif ($params['status'] == 20) {
                $map[] = ['a.status', 'in', [24, 18, 20, 22]];
            } else {
                $map[] = ['a.status', '=', $params['status']];
            }
        }
        if (!empty($params['start_time'])) $map[] = ['a.create_time', '>=', strtotime($params['start_time'])];
        if (!empty($params['end_time']))   $map[] = ['a.create_time', '<=', strtotime($params['end_time']) + 86399];

        $list = $Model->getInfo($map, 'a.*', 50);
        $order_ids = array_column($list->items(), 'id');

        $purchase_map = [];
        if (!empty($order_ids)) {
            $rows = Db::name('shop_order')
                ->where('id', 'in', $order_ids)
                ->where('purchase_link', '<>', '')
                ->column('purchase_link', 'id');
            $purchase_map = $rows;
        }

        foreach ($list->items() as &$item) {
            $item['purchase_link'] = $purchase_map[$item['id']] ?? '';
        }
        unset($item);

        $status_list = ShopOrderModel::getStatusList();
        $status_menu = ShopOrderModel::getStatusMenu();
        $delivery_type_list = ShopOrderModel::getDeliveryTypeName(-1);
        $pay_list = [1 => '微信支付', 3 => '积分支付'];

        $this->assign('list', $list->items());
        $this->assign('page', $list->render());
        $this->assign('total', $list->total());
        $this->assign('countPending', $list->total());
        $this->assign('status_menu', $status_menu);
        $this->assign('status_list', $status_list);
        $this->assign('delivery_type_list', $delivery_type_list);
        $this->assign('pay_list', $pay_list);
        $this->assign('search', $params);

        return $this->fetch('order/trial_index');
    }

    /**
     * 试运行 - 保存单元格
     */
    public function saveCell()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '非法请求']);
        }

        $id      = intval($this->request->param('id', 0));
        $field   = $this->request->param('field', '');
        $value   = $this->request->param('value', '');

        if (!$id || !in_array($field, ['purchase_link', 'admin_remark', 'express_name', 'express_sn'])) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        $res = Db::name('shop_order')
            ->where('id', $id)
            ->update([$field => $value]);

        return json([
            'code' => $res !== false ? 1 : 0,
            'msg'  => $res !== false ? '已保存' : '保存失败',
        ]);
    }

    /**
     * 试运行 - 划卡式发货 - 保存并下一条
     */
    public function saveShip()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '非法请求']);
        }

        $id           = intval($this->request->param('id', 0));
        $express_name = $this->request->param('express_name', '');
        $express_sn   = $this->request->param('express_sn', '');

        if (!$id) {
            return json(['code' => 0, 'msg' => '无效订单 ID']);
        }
        if (!$express_sn) {
            return json(['code' => 0, 'msg' => '物流单号不能为空']);
        }

        $order = Db::name('shop_order')->where('id', $id)->find();
        if (!$order) {
            return json(['code' => 0, 'msg' => '订单不存在']);
        }
        // 仅待发货状态 (2,14) 允许发货
        if ($order['status'] != 2 && $order['status'] != 14) {
            return json(['code' => 0, 'msg' => '订单状态不允许发货']);
        }

        $update = [
            'status'        => 4,
            'express_name'  => $express_name,
            'express_sn'    => $express_sn,
            'send_time'     => time(),
        ];
        Db::name('shop_order')->where('id', $id)->update($update);

        return json([
            'code' => 1,
            'msg'  => '已发货，状态变为待收货',
        ]);
    }

    /**
     * 试运行 - 下一条未发货订单
     */
    public function nextOrder()
    {
        $current_id = intval($this->request->param('id_after', 0));

        $map = [
            ['delete_time', '=', 0],
            ['status', 'in', [2, 14]],
        ];
        if ($current_id) {
            $map[] = ['id', '>', $current_id];
        }

        $order = Db::name('shop_order')
            ->where($map)
            ->order('id', 'asc')
            ->find();

        if (!$order) {
            return json(['code' => 0, 'msg' => '没有更多待发货订单', 'finished' => 1]);
        }

        return json([
            'code'        => 1,
            'finished'    => 0,
            'order'       => $order,
            'status_name' => ShopOrderModel::getStatusName($order['status']),
            'create_time' => $order['create_time'] ? date('Y-m-d H:i', $order['create_time']) : '',
        ]);
    }

    /**
     * 试运行 - 导出
     */
    public function export()
    {
        $params = $this->request->param();
        $map    = [];
        $map[]  = ['a.delete_time', '=', 0];
        if (!empty($params['order_num'])) $map[] = ['a.order_num|a.pay_order_num', 'like', "%{$params['order_num']}%"];
        if (!empty($params['username']))  $map[] = ['a.username|nickname', 'like', "%{$params['username']}%"];
        if (!empty($params['phone']))     $map[] = ['a.phone|mobile', 'like', "%{$params['phone']}%"];
        if (isset($params['status']) && $params['status'] !== '') {
            if ($params['status'] == 10) {
                $map[] = ['a.status', 'in', [10, 16]];
            } elseif ($params['status'] == 2) {
                $map[] = ['a.status', 'in', [2, 14]];
            } elseif ($params['status'] == 20) {
                $map[] = ['a.status', 'in', [24, 18, 20, 22]];
            } else {
                $map[] = ['a.status', '=', $params['status']];
            }
        }
        if (!empty($params['start_time'])) $map[] = ['a.create_time', '>=', strtotime($params['start_time'])];
        if (!empty($params['end_time']))   $map[] = ['a.create_time', '<=', strtotime($params['end_time']) + 86399];

        $this->performExport($map);
    }

    /**
     * 执行导出
     */
    private function performExport(array $map)
    {
        $Model = new ShopOrderModel();
        $list  = $Model->getInfo($map, 'a.*', -1);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $headers = [
            'A' => '订单号',
            'B' => '商品',
            'C' => '收货人',
            'D' => '电话',
            'E' => '地址',
            'F' => '支付状态',
            'G' => '订单状态',
            'H' => '采购链接',
            'I' => '备注',
            'J' => '下单时间',
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . '1', $text);
        }

        $row = 2;
        foreach ($list as $item) {
            $itemArr = is_array($item) ? $item : $item->toArray();
            $sheet->setCellValue('A' . $row, $itemArr['order_num']);
            $sheet->setCellValue('B' . $row, $this->formatTrialExportGoodsText($itemArr));
            $sheet->setCellValue('C' . $row, $itemArr['username'] ?? '');
            $sheet->setCellValue('D' . $row, $itemArr['phone'] ?? ($itemArr['mobile'] ?? ''));
            $sheet->setCellValue('E' . $row, $itemArr['user_address'] ?? ($itemArr['address'] ?? ''));
            $sheet->setCellValue('F' . $row, ShopOrderModel::getPayName($itemArr['pay_type']));
            $sheet->setCellValue('G' . $row, $itemArr['status_name'] ?? ShopOrderModel::getStatusName($itemArr['status']));
            $sheet->setCellValue('H' . $row, $itemArr['purchase_link'] ?? '');
            $sheet->setCellValue('I' . $row, $itemArr['admin_remark'] ?? ($itemArr['remark'] ?? ''));
            $sheet->setCellValue('J' . $row, date('Y-m-d H:i', $itemArr['create_time']));
            $row++;
        }

        $filename = 'trial_orders_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    /** 导出「商品」列：与列表一致，来自订单明细 goods_list */
    private function formatTrialExportGoodsText(array $item): string
    {
        $lines = [];
        foreach ($item['goods_list'] ?? [] as $g) {
            $g = is_array($g) ? $g : (method_exists($g, 'toArray') ? $g->toArray() : []);
            $name = trim((string)($g['goods_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $line = $name;
            if (!empty($g['sku_name'])) {
                $line .= ' / ' . $g['sku_name'];
            }
            if (isset($g['count']) && (int)$g['count'] > 0) {
                $line .= ' ×' . (int)$g['count'];
            }
            $lines[] = $line;
        }
        return implode("\n", $lines);
    }
}
