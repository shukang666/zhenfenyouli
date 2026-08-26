<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use api\wxapp\model\BalanceOrderModel;
use api\wxapp\model\CommonModel;
use api\wxapp\model\AccountLogModel;
use api\wxapp\model\OrderPayModel;
use api\wxapp\model\ShopOrderDetailModel;
use api\wxapp\model\ShopOrderModel;
use api\wxapp\model\UserCouponModel;
use api\wxapp\model\VipModel;
use api\wxapp\model\VipOrderModel;
use api\wxapp\model\VipPayModel;
use app\admin\model\company\CompanyModel;
use app\admin\model\forum\ForumModel;
use app\admin\model\goods\GoodsModel;
use app\admin\model\goods\GoodsSellModel;
use app\admin\model\info\IntegralModel;
use app\admin\model\info\MobileModel;
use app\admin\model\info\PromotionLogModel;
use app\admin\model\info\PromotionModel;
use app\admin\model\info\RechargeLogModel;
use app\admin\model\info\ResetModel;
use app\admin\model\info\RewardModel;
use app\admin\model\info\TopModel;
use app\admin\model\member\MemberModel;
use app\admin\model\order\OrderQrcodeModel;
use app\admin\model\order\OrderTakeModel;
use think\Facade\Db;
use think\facade\Log;

class PayController extends AuthController
{

    /**
     * @OA\Post(
     *     tags={"小程序-基础"},
     *     summary="订单支付",
     *     path="/wxapp/pay/pay_order",
     *
     *    @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单编号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="pay_type",
     *         in="query",
     *         description="1微信 2余额",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function pay_order()
    {
        $this->checkUser();
        if ($this->request->isPost()) {

            $order_num = $this->request->param('order_num');
            $pay_type = $this->request->param('pay_type');
            $return_model = CommonModel::getItemModel($order_num);
            $order_info = $return_model['order_info'];
            $order_all_info = $return_model['order_all_info'];

            if ($order_all_info['status'] != 1) $this->error('当前订单不支持付款');
            $price = $order_all_info['price'];
            if ($price <= 0) $this->error('价格错误');
            if ($pay_type == 1) {
                $total_fee = intval(strval($price * 100));
                $PayClass = new WxPayController();
                $res = $PayClass->pay($total_fee, $this->openid, $order_num);
                $this->success('调用成功', $res);
            } elseif ($pay_type == 2) {
                if ($this->user_info['balance'] < $price) {
                    $this->error('余额不足!');
                }
                $type = $order_info['type'];
                if ($type == 1) {//

                } elseif ($type == 2) {//

                }
                AccountLogModel::add_account_log($this->user_id, 1, $type, -$price);
                $order_info->save(['status' => 2, 'pay_time' => time(), 'pay_type' => 2]);
                $this->success('支付成功');
            }
        } else {
            $this->error('非法操作');
        }
    }

    //支付完后的回调
    public function notify()
    {
        $checkData = $this->checkData();
        if (!$checkData) return false;
        list($Model, $type, $order_info, $order_num) = $checkData;
        OrderPayModel::where(['order_num' => $order_num])->update(['status' => 2, 'pay_time' => time(), 'pay_type' => 1,'delivery_type'=>$order_info['delivery_type']]??3);
        $status = 2;
        if ($type == 1) {//单个订单支付
            if ($order_info['delivery_type'] == 2) {
                OrderTakeModel::add_data($order_info['user_id'],$order_num);
//                $status = 3;//自提
//                OrderQrcodeModel::set_qrcode($order_info['id'], 1, $order_info['user_id'], $order_info['shop_id']);
            }
            $Model->where(['order_num' => $order_num])->update(['status' => $status, 'pay_time' => time(), 'pay_type' => 1, 'pay_log_type' => 2]);
            OrderQrcodeModel::set_qrcode($order_info['id'], 1, $order_info['user_id'], $order_info['shop_id']);
        } elseif ($type == 2) {//购物车支付
//            $Model->where(['pay_order_num' => $order_num])->update(['status' => 2, 'pay_time' => time(), 'pay_type' => 1]);
            $list = $Model->where(['pay_order_num' => $order_num])->field('id,user_id,shop_id,coupon_id,point')->select();
            foreach ($list as $item) {
                if (!empty($item['point'])) {
                    AccountLogModel::add_account_log($this->user_id, 2, 203, -$item['point']);

                }
                if ($order_info['delivery_type'] == 2) {
                    OrderTakeModel::add_data($order_info['user_id'],$order_num);

//                    $status = 3;//自提
//                    OrderQrcodeModel::set_qrcode($item['id'], 1, $item['user_id'], $item['shop_id']);
                }
                $Model->where(['id' => $item['id']])->update(['status' => $status, 'pay_time' => time(), 'pay_type' => 1]);
                $UserCouponModel = new UserCouponModel();
                $UserCouponModel->where(['id' => $item['coupon_id']])->save(['used' => 1, 'used_time' => time()]);

            }

            $order_num_arr = $Model->where(['pay_order_num' => $order_num])->column('order_num');
            foreach ($order_num_arr as $this_order_num) {
                //减库存
                ShopOrderModel::setSellCountOrStock('stock_dec', $this_order_num);
                //增加销量
                ShopOrderDetailModel::where(['order_num' => $this_order_num])->select()->each(function ($ShopOrder)  {
                    GoodsModel::where(['id' => $ShopOrder['goods_id']])->inc('sell_count', $ShopOrder['count'])->update();
                    GoodsModel::where(['id' => $ShopOrder['goods_id']])->inc('sell_amount', $ShopOrder['total_price'])->update();
                    GoodsSellModel::insert(['goods_id' => $ShopOrder['goods_id'],
                        'order_num' => $ShopOrder['order_num'],
                        'count' => $ShopOrder['count'],
                        'total_price' => $ShopOrder['total_price']]);
                });
            }

        }


        return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }


    //开通会员
    public static function doVipPayed($order_num)
    {
//        log::write($order_num, '订单编号');
        $VipPayModel = new VipPayModel();
        $VipOrderModel = new VipOrderModel();
        //先查是否有这个分类的vip  有 就在这个vip增加时间  没有就创建vip
        //判断会员id是否相同 不相同删除原先的 开通现在的 where('cate_id',$VipPay['cate_id'])->
        $VipPay = $VipPayModel->where(['order_num' => $order_num])->find();
        if (!empty($VipPay)) {
            $VipOrder = $VipOrderModel->where('user_id', $VipPay['user_id'])->where('status', 2)->column('cate_id', 'id');
            if (in_array($VipPay['cate_id'], $VipOrder)) {
                $cateVipOrder = $VipOrderModel->where('cate_id', $VipPay['cate_id'])->where('status', 2)->find();
                //相同就叠加会员时间
                $newTime = $cateVipOrder['end_time'] + $VipPay['month'] * 2678400;//按月增加会员时间
                $cateVipOrder->save(['end_time' => $newTime, 'month' => $VipPay['month']]);
            } else {
                //不同就创建新的会员
                $VipOrderModel->save([
                    'user_id' => $VipPay['user_id'],
                    'end_time' => $VipPay['end_time'],
                    'cate_id' => $VipPay['cate_id'],
                    'month' => $VipPay['month'],
                    'status' => 2,
                    'create_time' => time(),
                ]);
            }
            $VipPay->where('order_num', $order_num)->update(['status' => 4]);

        }
    }


    public function checkSign($data): bool
    {
        $post_sign = $data['sign'];
        unset($data['sign']);
        $PayClass = new WxPayController();
        $check_sign = $PayClass->MakeSign($data, config('wxapp.key'));
        if ($post_sign == $check_sign) {
            return true;
        } else {
            return false;
        }
    }


    public function checkData()
    {
        $xml = file_get_contents('php://input');
        $PayClass = new WxPayController();
        $data = $PayClass->xml2array($xml);

        if (!$this->checkSign($data)) {
            Log::write($data, '数据核验不通过');
            return false;
        }
        if ($data["return_code"] == "FAIL") {
            Log::write('return_code.FAll' . $data['return_msg']);
            return false;
        }
        if ($data['result_code'] == "FAIL") {
            Log::write('result_code.FAll' . $data['return_msg']);
            return false;
        }

        //成功  判断金额
        $order_num = $data['out_trade_no'];
        $pay_price = $data['total_fee'];
        $return_model = CommonModel::getItemModel($order_num);
        $order_info = $return_model['order_info'];

        if (empty($order_info)) {
            Log::write('订单不存在', $order_num);
            return false;
        }
        if ($order_info['status'] != 1) {
            Log::write('订单状态错误', $order_num);
            return false;
        }
        $price = intval(strval($order_info['price'] * 100));
        if ($price != $pay_price) {
            Log::write('金额错误', $order_num);
            return false;
        }
        return [$return_model['model'], $return_model['type'], $order_info, $order_num];
    }

    public function getFormatTime($time_str, $status)
    {
        if (!empty($time_str)) {
            $time_arr = json_decode($time_str, true);
            $time_arr[$status . '_time'] = time();
        } else {
            $time_arr[$status . '_time'] = time();
        }
        return json_encode($time_arr);
    }

}
