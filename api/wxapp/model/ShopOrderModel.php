<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace api\wxapp\model;

use api\wxapp\model\CommonModel;
use app\admin\model\goods\GoodsModel;
use app\admin\model\goods\GoodsPositionModel;
use app\admin\model\goods\GoodsSkuModel;
use app\admin\model\order\OrderInvoiceModel;
use app\admin\model\shop\ShopModel;
use think\Model;

class ShopOrderModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'shop_order';


    public function getInfo($map, $field = 'a.*', $page_size = 10)
    {
        $query = $this->alias('a')->field($field . ',b.nickname,b.true_name,b.mobile,b.avatar')
            ->leftjoin('member b', 'a.user_id=b.id')
            ->leftjoin('shop_order_detail c', 'a.order_num = c.order_num')
            ->json(['param'], true)
            ->where($map)->group('a.id')->order('a.id desc');

        if ($page_size == -1) {
            $list = $query->select();
        } else {
            $list = $query->paginate($page_size);
        }
        $ShopModel = new ShopModel();
        $GoodsPositionModel = new GoodsPositionModel();
        $OrderDetailModel = new ShopOrderDetailModel();
        $shopInvoiceModel = new OrderInvoiceModel();


        $pay_type_name = [0 => '未支付', 1 => '微信支付', 2 => '余额支付', 3 => '积分支付'];
        foreach ($list as &$item) {

            if ($item['avatar']) $item['avatar'] = cmf_get_asset_url($item['avatar']);
            $item['status_name'] = self::getStatusName($item['status']);
            if ($item['status'] == 2 && $item['delivery_type'] == 2) {
                $item['status_name'] = '待核销';
            } elseif ($item['status'] == 2 && empty($item['delivery_type'])) {
                // 待发货状态根据付款时长动态显示
                // 超过36h → 出库中；超过24h → 备货中；否则 → 待发货
                // 积分兑换场景 pay_time ≈ create_time，两者兼容兜底
                $pay_time = intval($item['pay_time'] ?? ($item['create_time'] ?? 0));
                if ($pay_time > 0) {
                    $elapsed = time() - $pay_time;
                    if ($elapsed > 36 * 3600) {
                        $item['status_name'] = '出库中';
                    } elseif ($elapsed > 24 * 3600) {
                        $item['status_name'] = '备货中';
                    }
                }
            }
            $item['pay_type_name'] = $pay_type_name[$item['pay_type']] ?? '';
            if ($item['status'] == 1) $item['left_pay_time'] = $item['need_pay_time'] - time();
            if (!empty($item['delivery_type'])) $item['delivery_type_name'] = self::getDeliveryTypeName($item['delivery_type']);
            if (!empty($item['province'])) $item['user_address'] = $item['province'] . $item['city'] . $item['county'] . $item['address'];
            if (!empty($item['cancel_image'])) $item['cancel_image'] = CommonModel::getArrImageFullUrl($item['cancel_image']);

            $detail_list = $OrderDetailModel->where(['order_num' => $item['order_num']])->select();
            $item['goods_list'] = getFullData($detail_list);

            if ($item['delivery_type'] == 2) {
                if ($item['shop_id'] > 0) {
                    //商家评分
                    $shop_info = $ShopModel
                        ->where(['user_id' => $item['user_id']])
                        ->field('id,shop_name,mobile,name,avatar,address')->find();
                    $item['shop_info'] = $shop_info;
                } else {
                    $shop_info = $GoodsPositionModel
                        ->where(['id' => $item['position_id']])
                        ->find();
                    $shop_info['shop_name'] = '平台';
                    $shop_info['avatar'] = cmf_get_asset_url(cmf_get_option('set_config')['app_logo']);
                    $item['shop_info'] = $shop_info;
                }
            }
            //是否开发票

            $shopInvoice = $shopInvoiceModel->where(['order_num' => $item['pay_order_num']])->field('invoice')->find();
            if ($shopInvoice) {
                $item['invoice_status'] = $shopInvoice['invoice'] ? 2 : 1;
            } else {
                $item['invoice_status'] = 0;
            }

//            $item['order_num'] = $item['pay_order_num'];
        }

        return $list;
    }


    /**
     * @param
     *
     * stock_dec   库存减,
     * stock_inc   库存加,
     * sell_count_dec   销售额减
     * sell_count_inc   销售额加,
     *
     * @param string $order_num 子订单号 shop_order.order_num（不是 pay_order_num）
     */

    public static function setSellCountOrStock($type, $order_num)
    {
        $OrderDetailModel = new ShopOrderDetailModel();
        $order_detail = $OrderDetailModel->field('goods_id,sku_id,count')->where(['order_num' => $order_num])->select();

        $type_arr = explode('_', $type);
        if (count($type_arr) == 2) {
            $filed = $type_arr[0];
            $direction = $type_arr[1];
        } else {
            $filed = $type_arr[0] . '_' . $type_arr[1];
            $direction = $type_arr[2];
        }
        foreach ($order_detail as $item) {
            self::doSetStockAndSellCount($item['goods_id'], $item['sku_id'], $item['count'], $filed, $direction);
        }
    }

    //direction inc增加 dec减少
    public static function doSetStockAndSellCount($goods_id, $sku_id, $count, $field, $direction)
    {
        $GoodsModel = new GoodsModel();
        $SkuModel = new GoodsSkuModel();

        $GoodsModel->where(['id' => $goods_id])->$direction($field, $count)->update();
        if (!empty($sku_id)) {
            $SkuModel->where(['id' => $sku_id])->$direction($field, $count)->update();
        }
    }

    public static function findDataByOrderNum($order_num, $user_id = 0)
    {
        $map = [];
        $map[] = ['order_num', '=', $order_num];
        if (!empty($user_id)) $map[] = ['user_id', '=', $user_id];

        $ShopOrderModel = new ShopOrderModel();
        return $ShopOrderModel->where($map)->find();
    }

    public static function afterPayed($order_num)
    {
        ShopOrderModel::setSellCountOrStock('sell_count_inc', $order_num);
    }

    public static function afterFinished($order_num)
    {
        $order_info = self::findDataByOrderNum($order_num);
    }


    public static function getStatusName($status = -1)
    {

        $status_name_arr = self::getStatusList();

        $status_name = [];
        foreach ($status_name_arr as $item) {
            $status_name[$item['status']] = $item['status_name'];
        }
        if ($status == -1) {
            return $status_name;
        } else {
            return $status_name[$status] ?? '未知';
        }
    }

    public static function getStatusList(): array
    {
        return [
            ['status' => 1, 'status_name' => '待付款', 'color' => 'layui-bg-red'],
            ['status' => 2, 'status_name' => '待发货', 'color' => 'layui-bg-orange'],
            ['status' => 3, 'status_name' => '待自提', 'color' => 'layui-bg-orange'],
            ['status' => 4, 'status_name' => '待收货', 'color' => 'layui-bg-blue'],
            ['status' => 6, 'status_name' => '待评价', 'color' => 'layui-btn-primary layui-border-green'],
            ['status' => 8, 'status_name' => '已完成', 'color' => ''],
            ['status' => 10, 'status_name' => '已取消', 'color' => 'layui-btn-primary layui-border'],
            ['status' => 12, 'status_name' => '退款申请', 'color' => 'layui-btn-primary layui-border-red'],
            ['status' => 14, 'status_name' => '待发货(退款拒绝)', 'color' => 'layui-bg-orange'],
            ['status' => 16, 'status_name' => '已退款', 'color' => 'layui-btn-primary layui-border'],
            ['status' => 18, 'status_name' => '退货申请', 'color' => 'layui-btn-primary layui-border'],
            ['status' => 20, 'status_name' => '待退货', 'color' => 'layui-btn-primary layui-border'],
            ['status' => 22, 'status_name' => '退款中', 'color' => 'layui-btn-primary layui-border'],
            ['status' => 24, 'status_name' => '已退款', 'color' => 'layui-btn-primary layui-border'],
        ];
    }

    public static function getStatusMenu(): array
    {
        $status_menu = [//3 => '待自提',
            0 => '全部', 1 => '待付款', 2 => '待发货', 4 => '待收货', 6 => '待评价', 8 => '已完成', 10 => '已取消/关闭', 12 => '申请退款', 20 => '售后'
        ];

        //每个订单状态的数量
        $new_status_menu = [];
        foreach ($status_menu as $status => $status_name) {
            $new_status_menu[$status]['status'] = $status;
            $new_status_menu[$status]['status_name'] = $status_name;
            $new_status_menu[$status]['status_count'] = self::getOrderStatusCount($status);
        }

        return $new_status_menu;

    }

    public static function getDeliveryTypeName($type = -1)
    {
        $type_name = [1 => '快递发货', 2 => '自提'];
        if ($type == -1) {
            return $type_name;
        } else {
            return $type_name[$type] ?? '未知';
        }
    }

    /** 支付方式文案（与 getInfo 内 pay_type_name 一致） */
    public static function getPayName($payType = -1)
    {
        $payTypeName = [0 => '未支付', 1 => '微信支付', 2 => '余额支付', 3 => '积分支付'];
        if ($payType == -1) {
            return $payTypeName;
        }
        return $payTypeName[$payType] ?? '未知';
    }


    public static function getOrderStatusCount($status, $map = []): int
    {
        $ShopOrderModel = new ShopOrderModel();
//        $map = [];
        $map[] = ['delete_time', '=', 0];
        if ($status == 10) {
            $map[] = ['status', 'in', [10, 16]];
        } elseif ($status == 2) {
            $map[] = ['status', 'in', [2, 14]];
        } elseif ($status == 20) {
            $map[] = ['status', 'in', [24, 18, 20, 22]];
        } else {
            $map[] = ['status', '=', $status];
        }
        return $ShopOrderModel->where($map)->count();
    }
}
