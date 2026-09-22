<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use api\wxapp\model\CommonModel;
use api\wxapp\model\AccountLogModel;
use api\wxapp\model\AddressModel;
use api\wxapp\model\MemberModel;
use api\wxapp\model\OrderCommentModel;
use api\wxapp\model\OrderStatusModel;
use api\wxapp\model\ShopOrderDetailModel;
use api\wxapp\model\ShopOrderModel;
use api\wxapp\model\UserCouponModel;
use app\admin\model\goods\CartModel;
use app\admin\model\goods\GoodsModel;
use app\admin\model\goods\GoodsPositionModel;
use app\admin\model\goods\GoodsSellModel;
use app\admin\model\goods\GoodsSkuModel;
use app\admin\model\order\OrderInvoiceModel;
use app\admin\model\shop\ShopModel;
use cmf\controller\RestBaseController;
use think\facade\Db;
use think\facade\Log;
use think\Model;

class OrderController extends AuthController
{

    /**
     * 商品自提点列表
     * @OA\Post(
     *tags={"商城订单"},
     *path="/wxapp/order/goods_position",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="lng",description="经度",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="lat",description="纬度",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function goods_position(GoodsPositionModel $positionModel)
    {
        $lng = $this->request->param('lng', 1, 'trim');
        $lat = $this->request->param('lat', 1, 'trim');
        $distance = CommonModel::getLonLatDistanceField($lng, $lat);
        $list = $positionModel->field('*,' . $distance)->paginate($this->page_size);
        if ($list->isEmpty()) $this->error('暂无数据');
        $list = CommonModel::getMany($list, ['thumb']);
        $this->success('请求成功', $list);
    }


    /**
     * 下单
     * @OA\Post(
     *tags={"商家-订单"},
     *path="/wxapp/order/add_order",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="action",description="传info获取下单前的数据",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="cart_id",description="购物车下单就传这一个  数组",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="goods_id",description="商品id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="count",description="数量",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="coupon_id",description="单个下单使用优惠券",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="cart_coupon_arr",description="购物车使用优惠卷[{'shop_id':商家id,'coupon_id':优惠卷id}]",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="address_id",description="地址",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="pay_type",description="支付类型",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="1:积分",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="delivery_type",description="1快递 2自取",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="position_id",description="自提点ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_order()
    {
        $this->checkUser();
        if ($this->request->isPost()) {
            $param = $this->request->param();
//            Log::write($param,'支付测试');
            $cart_id_arr = $this->request->param('cart_id');//, [5]
            $goods_id = $this->request->param('goods_id');

            $action = $this->request->param('action');
            $pay_type = $this->request->param('pay_type', 1);
            $type = $this->request->param('type', 0);
            $count = $this->request->param('count', 1);
            $delivery_type = $this->request->param('delivery_type', 0);
            $position_id = $this->request->param('position_id', 0);
            $coupon_id = $this->request->param('coupon_id', 0);
            $cart_coupon_arr = $this->request->param('cart_coupon_arr', []);

            if ($position_id) {
                $delivery_type = 2;
            }
//            $this->error('请选择配送方式'.$delivery_type);
//            $cart_id_arr = $this->request->param('cart_id', [22, 23, 24]);//
//            $cart_coupon_arr = $this->request->param('cart_coupon_arr', [['shop_id' => 1, 'coupon_id' => 111]]);
//            $coupon_ids = $this->request->param('coupon_ids', [90]);

            //取goods信息
            $GoodsModel = new GoodsModel();
            $skuModel = new GoodsSkuModel();
//            $skuListModel = new SkuListModel();
            $CartModel = new CartModel();
            $ShopOrderModel = new ShopOrderModel();
            //地址信息
            $pay_order_num = CommonModel::createOrderNumber();
            $AddressModel = new AddressModel();
            $address_id = $this->request->param('address_id');
            $address_info = $AddressModel->where(['id' => $address_id])->find();
//            if (empty($address_info) && $action != 'info') $this->error('地址错误');
            $is_cart = 0;
            //组合数据
            $old_goods_list = [];
            //判断是否是购物车
            if (!empty($cart_id_arr)) {
                foreach ($cart_id_arr as $key => $cart_id) {
                    $cart_info = $CartModel->where(['id' => $cart_id])->find();
                    $old_goods_list[$key] = ['goods_id' => $cart_info['goods_id'], 'sku_id' => $cart_info['sku_id'], 'count' => $cart_info['count']];
                }
                $is_cart = 1;
            } else {
                $old_goods_list[] = ['goods_id' => $goods_id, 'count' => $count];
            }
            //获取商品信息  把同商家的商品放在一起
            $new_goods_list = [];
            foreach ($old_goods_list as $old_goods_item) {
                $goods_info = $GoodsModel->where(['id' => $old_goods_item['goods_id']])->find();
                if (empty($goods_info)) {
                    $this->error('商品不存在');
                }
                if (!GoodsModel::userMetSignRequire((int)$this->user_id, $goods_info)) {
                    $this->error(GoodsModel::signRequireErrorMessage($goods_info));
                }
                $new_goods_list[$goods_info['user_id']][] = $old_goods_item;
            }

            $total_need_pay = 0;
            $total_order_goods_price = 0;
            $total_all_price = 0;
            $total_express_price = 0;
            $total_order_point = 0;
            $return_goods_info = [];
            $coupon_total_price = 0;
            $total_point_ratio = 0;
//            $sale = cmf_get_new_option('sale');
//            $money_to_point = $sale['money_to_point'] ?? 100;
//            $need_pay_minute = $sale['order_need_pay_time'] ?? 30;

            // 在遍历商品列表时计算
            $total_point_deduction = 0;  // 总抵扣金额
            $total_points_used = 0;      // 总使用积分
            $data_order = [];
            $point_ratio = $this->get_config['point_ratio'];//积分抵扣比例
            $ShopOrderDetailModel = new ShopOrderDetailModel();
            //通过商家构成的商品进行循环遍历
            foreach ($new_goods_list as $shop_id => $goods_list) {
                $ShopOrderModel = new ShopOrderModel();
                $order_num = CommonModel::createOrderNumber();
                $order_goods_price = 0;
                $order_express_price = 0;
                $data_goods = [];
                $this_goods_info = [];
                $order_type = 1;
                $goods_price_total = 0;

                foreach ($goods_list as $k => $goods_item) {
                    $goods_info = $GoodsModel->where(['id' => $goods_item['goods_id']])->find();
                    $total_points_used += $goods_item['count'] * $goods_info['point'];
                    // 计算该商品可抵扣金额
                    $item_point_deduction
                        = $goods_info['price']
                        * ($goods_info['discount'] / 100);
                    // 累加到总抵扣金额
                    $total_point_deduction += $item_point_deduction;
                    $order_type = $goods_info['goods_type'];
                    $data_goods[$k] = [
                        'order_num' => $order_num, 'goods_id' => $goods_item['goods_id'],
                        'goods_name' => $goods_info['goods_name'],
//                        'start_time'=>$goods_info['start_time'],'end_time'=>$goods_info['end_time'],
                        'thumb' => cmf_get_asset_url($goods_info['thumb']), 'user_id' => $this->user_id,
                        'count' => $goods_item['count'], 'pay_order_num' => $pay_order_num
                    ];

                    if (!empty($goods_item['sku_id'])) {
                        $sku_id = $goods_item['sku_id'];
                        $count = $goods_item['count'];
                        $sku = $skuModel->where('id', $sku_id)->find();
                        $data_goods[$k]['sku_id'] = $goods_item['sku_id'];
                        $data_goods[$k]['sku_name'] = $sku['name'];
                        $data_goods[$k]['sku_value'] = $sku['name'];
                        $data_goods[$k]['sku_name_value'] = $sku['name'];
                        $one_price = $sku['price'];
                        $goods_stock = $sku['stock'];
                    } else {
                        $data_goods[$k]['sku_id'] = 0;
                        $data_goods[$k]['sku_name'] = '';
                        $one_price = $goods_info['price'];
                        $goods_stock = $goods_info['stock'];
                    }

                    if ($goods_item['count'] > $goods_stock) $this->error('库存不足');
                    $data_goods[$k]['one_price'] = $one_price;
                    $data_goods[$k]['one_price'] = $goods_item['count'];
                    $data_goods[$k]['total_price'] = round($one_price * $goods_item['count'], 2);
                    $order_goods_price += $data_goods[$k]['total_price'];
                    $this_goods_info[] = [
                        'goods_id' => $goods_item['goods_id'],
                        'sku_id' => $goods_item['sku_id'] ?? 0,
                        'count' => $goods_item['count'],
                        'thumb' => cmf_get_asset_url($goods_info['thumb']),
                        'goods_name' => $goods_info['goods_name'],
                        'sku_name' => $data_goods[$k]['sku_name'],
                        'price' => $one_price
                    ];
                }
                $shop_info = ShopModel::where('user_id', $shop_id)->find();
                if (empty($shop_info) && $shop_id == 0) {
                    $shop_info = [
                        'shop_name' => '平台自营'
                    ];
                }
                $return_goods_info[] = ['shop_id' => $shop_id,
                    'shop_name' => $shop_info['shop_name'],
                    'shop_info' => ShopModel::where('user_id', $shop_id)->find(),
                    'express_price' => $order_express_price,
                    'goods_list' => $this_goods_info,
                    'goods_price_total' => $order_goods_price,
                ];


                //shop_order表
                $this_remark = '';
                $order_goods_price = round($order_goods_price, 2);

                $data_order = [
                    'user_id' => $this->user_id, 'order_num' => $order_num, 'goods_price' => $order_goods_price,
                    'status' => 1, 'create_time' => time(), 'need_pay_time' => time() + 86400, 'remark' => $this_remark,
                    'pay_type' => $pay_type, 'username' => $address_info['username'] ?? '', 'phone' => $address_info['phone'] ?? '',
                    'province' => $address_info['province'] ?? '', 'city' => $address_info['city'] ?? '',
                    'county' => $address_info['county'] ?? '', 'address' => $address_info['address'] ?? '',
                    'express_price' => $order_express_price, 'pay_order_num' => $pay_order_num,
                    'point' => 0, 'point_pay' => 0, 'point_amount' => 0, 'shop_id' => $shop_id, 'delivery_type' => $delivery_type, 'order_type' => $order_type,
                    'goods_type' => 1, 'position_id' => $position_id
                ];
                $data_order['total_price'] = $order_goods_price + $order_express_price;

                $price = round($data_order['total_price'], 2);


                $coupon_price = 0;
                //优惠券
                $UserCouponModel = new UserCouponModel();
                if ($is_cart) {
                    foreach ($cart_coupon_arr as $k => $v) {
                        if ($v['shop_id'] == $shop_id) {
                            $coupon_id = $v['coupon_id'];
                            if (!empty($coupon_id)) {
                                $coupon_info = $UserCouponModel->where(['id' => $coupon_id, 'used' => 0])->find();
                                if (empty($coupon_info) || $coupon_info['full_amount'] > $order_goods_price) $this->error('优惠券错误');
                                $coupon_price = $coupon_info['amount'];
                            }
                            $data_order['coupon_id'] = $coupon_id;
                            $data_order['coupon_price'] = $coupon_price;
                            $coupon_total_price += $coupon_price;
                            $price = round($data_order['total_price'] - $coupon_price, 2);
                        }
                    }
                } else {

                    if (!empty($coupon_id)) {
                        $coupon_info = $UserCouponModel->where(['id' => $coupon_id, 'user_id' => $this->user_id, 'used' => 0])->find();
                        if (empty($coupon_info) || $coupon_info['amount'] > $order_goods_price) $this->error('优惠券错误');
                        $coupon_price = $coupon_info['amount'];
                    }
                    $data_order['coupon_price'] = $coupon_price;
                    $data_order['coupon_id'] = $coupon_id;
                    $coupon_total_price += $coupon_price;
                    $price = round($data_order['total_price'] - $coupon_price, 2);
                }


                //积分抵扣
                $user_point = $this->user_info['point'];
                // 然后进行积分抵扣计算
                if ($type == 1 && !empty($user_point)) {
                    // 计算需要的积分 = 总抵扣金额 * 积分兑换比例
                    $needed_points = $total_point_deduction * $point_ratio;
                    if ($user_point >= $needed_points) {
                        // 足够抵扣
                        $data_order['point'] = $needed_points;
                        $data_order['point_amount'] = $total_point_deduction;
                    } else {
                        // 不足抵扣
                        $data_order['point'] = $user_point;
                        $data_order['point_amount'] = $user_point / $point_ratio;
                    }
                    // 更新实际支付金额
                    $total_need_pay = round($total_need_pay - $data_order['point_amount'], 2);
                }
                $data_order['point'] = round($data_order['point'], 2);
                $data_order['point_amount'] = round($data_order['point_amount'], 2);

                $data_order['point_pay'] = round($total_points_used, 2);


                $data_order['price'] = $price;
                if ($action != 'info') {
//                    Db::startTrans();
                    $ShopOrderModel->insert($data_order);
                    $ShopOrderDetailModel->insertAll($data_goods);
                    CommonModel::addOrderPay($order_num, 1, $price, $this->user_id);
                }
                $total_need_pay += $data_order['price'];
                $total_order_goods_price += $order_goods_price;
                $total_all_price += $data_order['total_price'];
                $total_express_price += $order_express_price;

            }



            if ($action == 'info') {
                $return['goods_info'] = $return_goods_info;
                $return['goods_price'] = $total_order_goods_price;
                $return['express_price'] = $total_express_price;
                $return['total_price'] = $total_all_price;
                $return['price'] = $total_need_pay;
                $return['point'] = $data_order['point'] ?? 0;
                $return['pay_point'] = $data_order['point_pay'] ?? 0;
                $return['coupon_price'] = $coupon_total_price;
//                Log::write($return,'参数123');

                $this->success('获取成功', $return);
            } else {
                CommonModel::addOrderPay($pay_order_num, 2, $total_need_pay, $this->user_id);
            }

            if ($total_points_used) {
                if ($total_points_used > $this->user_info['point']) $this->error('积分不足');
                $data_order['point'] = $total_points_used;
            }

            $status = 1;
            $msg = '请支付';
            if (empty($total_need_pay)) {
                $status = 2;
//                if ($data_order['delivery_type'] == 2) {
//                    $status = 3;
//                }

                $msg = '支付成功';
//                AccountLogModel::add_account_log($this->user_id, 2, 203, -$data_order['point']);
                $ShopOrderModel->where(['pay_order_num' => $pay_order_num])->update(['status' => 2, 'pay_time' => time(), 'pay_type' => 3]);
                $order_num_arr = $ShopOrderModel->where(['pay_order_num' => $pay_order_num])->column('order_num');
                foreach ($order_num_arr as $this_order_num) {
                    //减库存
                    ShopOrderModel::setSellCountOrStock('stock_dec', $this_order_num);
                    //增加销量
                    $ShopOrderDetailModel->where(['order_num' => $this_order_num])->select()->each(function ($ShopOrder) use ($GoodsModel) {
                        $GoodsModel->where(['id' => $ShopOrder['goods_id']])->inc('sell_count', $ShopOrder['count'])->update();
                        $GoodsModel->where(['id' => $ShopOrder['goods_id']])->inc('sell_amount', $ShopOrder['total_price'])->update();
                        GoodsSellModel::insert(['goods_id' => $ShopOrder['goods_id'],
                            'order_num' => $ShopOrder['order_num'],
                            'count' => $ShopOrder['count'],
                            'total_price' => $ShopOrder['total_price']]);
                    });

                }
                if ($is_cart) {
                    foreach ($cart_coupon_arr as $k => $v) {
                        if ($v['shop_id'] == $shop_id) {
                            $coupon_id = $v['coupon_id'];
                            if (!empty($coupon_id)) {
                                $UserCouponModel = new UserCouponModel();
                                $UserCouponModel->where(['id' => $coupon_id])->save(['used' => 1, 'used_time' => time()]);
                            }
                        }
                    }
                } else {
                    $UserCouponModel = new UserCouponModel();
                    $UserCouponModel->where(['id' => $coupon_id])->save(['used' => 1, 'used_time' => time()]);
                }
            }

            if ($total_points_used) AccountLogModel::add_account_log($this->user_id, 2, 203, -$data_order['point']);
            $this->success($msg, ['order_num' => $pay_order_num, 'status' => $status]);
        } else {
            $this->error('非法操作');
        }
    }

    /**
     * @OA\Get(
     *     tags={"商城订单"},
     *     summary="我的订单",
     *     path="/wxapp/order/my_order",
     *
     *      @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),  *
     *      @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="不传取全部，订单状态 1待付款 2待发货 4待收货  6待评价 8已完成 10退款/取消",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function my_order($status = 0)
    {

        $this->checkUser();
        $ShopOrderModel = new ShopOrderModel();
        $map = [];
        if (!empty($status)) {
            if ($status == 2) {
                $map[] = ['a.status', 'in', [2, 14]];
            } elseif ($status == 10) {
                $map[] = ['a.status', 'in', [10, 12, 16]];
            } else {
                $map[] = ['a.status', '=', $status];
            }
        }
        $map[] = ['a.status', '>', 1];
        $map[] = ['a.delete_time', '=', 0];
        $map[] = ['a.user_id', '=', $this->user_id];
        $list = $ShopOrderModel->getInfo($map, 'a.id,a.order_num,a.price,a.total_price,
        a.status,a.create_time,a.need_pay_time,a.pay_type,a.point,a.pay_order_num', $this->page_size);
        $this->success('获取成功', $list);
    }

    /**
     * @OA\Get(
     *     tags={"商城订单"},
     *     summary="订单详情",
     *     path="/wxapp/order/order_detail",
     *
     *          @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单编号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function order_detail($order_num = '')
    {
        $this->checkUser();
        $ShopOrderModel = new ShopOrderModel();
        $map = [];
        $map[] = ['a.order_num', '=', $order_num];
//        $map[] = ['a.user_id', '=', $this->user_id];
        $list = $ShopOrderModel->getInfo($map);
        $order_info = $list[0] ?? [];
        if (empty($order_info)) $this->error('订单不存在');
        if ($order_info['delivery_type'] == 2) {
            $GoodsPositionModel = new GoodsPositionModel();
            $order_info['position_info'] = $GoodsPositionModel
                ->where(['id' => $order_info['position_id']])
                ->withAttr('thumb', function ($value) {
                    return cmf_get_asset_url($value);
                })
                ->find();
        }
        $this->success('获取成功', $order_info);
    }

    /**
     * @OA\Post(
     *     tags={"商城订单"},
     *     summary="取消订单",
     *     path="/wxapp/order/cancel_order",
     *
     *
     *
     *       @OA\Parameter(
     *          name="openid",
     *          in="query",
     *          description="openid",
     *          required=false,
     *          @OA\Schema(
     *              type="string",
     *          )
     *      ),
     *            @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单编号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *       @OA\Parameter(
     *         name="cancel_reason",
     *         in="query",
     *         description="取消原因",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *       @OA\Parameter(
     *         name="cancel_desc",
     *         in="query",
     *         description="取消描述",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="cancel_image",
     *         in="query",
     *         description="取消凭证",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function cancel_order($order_num = '', $cancel_reason = '', $cancel_desc = '', $cancel_image = '')
    {
        $cancel_image = CommonModel::getStringImageUrl($cancel_image);

        $order_info = ShopOrderModel::findDataByOrderNum($order_num, $this->user_id);
        if (empty($order_info)) $this->error('订单不存在');
        $status = $order_info['status'];

        $data_i = [
            'cancel_reason' => $cancel_reason, 'cancel_desc' => $cancel_desc, 'cancel_image' => $cancel_image, 'cancel_time' => time()
        ];

        if ($status == 1) {
            //待付款取消
            $data_i['status'] = 10;
            $res = $order_info->save($data_i);

            if ($res) {
                $UserCoupon = new UserCouponModel();
                if (!empty($order_info['coupon_id'])) {
                    $UserCoupon->where(['id' => $order_info['coupon_id']])->save(['used' => 0]);
                }
                //加库存
                ShopOrderModel::setSellCountOrStock('stock_inc', $order_num);

                //退回已抵扣积分
                //if($order_info['point'] > 0) AccountLogModel::add_account_log($order_info['user_id'], 2, 204, $order_info['point'], ['order_num' => $order_num]);

                $this->success('取消成功');
            } else {
                $this->error('取消失败');
            }
        } else {
            if ($status != 2 && $status != 4 && $status != 14) $this->error('当前状态不允许取消');
            if ($status == 2) {
                $data_i['status'] = 12;
            } else {
                $data_i['status'] = 18;
            }
            $res = $order_info->save($data_i);
            if ($res) {
                $this->success('申请成功，请耐心等待审核');
            } else {
                $this->error('申请失败');
            }
        }
    }

    /**
     * @OA\Post(
     *     tags={"商城订单"},
     *     summary="确认收货",
     *     path="/wxapp/order/confirm_order",
     *
     *        @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单编号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function confirm_order($order_num = '')
    {
        $this->checkUser();
        $order_info = ShopOrderModel::findDataByOrderNum($order_num, $this->user_id);
        if (empty($order_info)) $this->error('订单不存在');
        if ($order_info['status'] == 4) {
            $order_info->save(['status' => 6, 'receive_time' => time()]);
            if (!empty($order_info['shop_id'])) {
                AccountLogModel::add_account_log($order_info['shop_id'], 3, 300, $order_info['price'], ['order_num' => $order_num]);
            }
            $this->success('收货成功');
        } else {
            $this->error('当前订单不能确认收货');
        }
    }

    /**
     * @OA\Post(
     *     tags={"商城订单"},
     *     summary="删除订单",
     *     path="/wxapp/order/delete_order",
     *
     *        @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function delete_order($order_num = '')
    {
        $this->checkUser();
        $order_info = ShopOrderModel::findDataByOrderNum($order_num, $this->user_id);
        if (empty($order_info)) $this->error('错误');
        if ($order_info['status'] == 1 || $order_info['status'] == 10) {
            $order_info->save(['delete_time' => time()]);
            $this->success('删除成功');
        } else {
            $this->error('当前无法删除订单');
        }
    }

    /**
     * @OA\Post(
     *     tags={"商城订单"},
     *     summary="评价",
     *     path="/wxapp/order/add_comment",
     *
     *            @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单编号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *        @OA\Parameter(
     *         name="point",
     *         in="query",
     *         description="商品分数",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *        @OA\Parameter(
     *         name="content",
     *         in="query",
     *         description="内容",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Parameter(
     *         name="photos",
     *         in="query",
     *         description="图片",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_comment($order_num = '', $point = 5, $content = '', $photos = '')
    {

        $order_info = ShopOrderModel::findDataByOrderNum($order_num, $this->user_id);
        if ($order_info['status'] != 6) $this->error('当前不能评价');
        $OrderDetailModel = new ShopOrderDetailModel();
        $detail = $OrderDetailModel->field('goods_id')->where(['order_num' => $order_num])->select();
        foreach ($detail as $v) {
            $CommentModel = new OrderCommentModel();
            $data_i = [];
            $data_i['shop_id'] = $v['user_id'];
            $data_i['user_id'] = $this->user_id;
            $data_i['point'] = $point;
            $data_i['status'] = 1;
            $data_i['content'] = $content;
            $data_i['create_time'] = time();
            $data_i['order_num'] = $order_num;
            $data_i['goods_id'] = $v['goods_id'];
            $data_i['photos'] = CommonModel::getStringImageUrl($photos);
            $CommentModel->insert($data_i);
        }

        $order_info->save(['status' => 8, 'finish_time' => time()]);

        $this->success('评价成功');

    }


    /**
     * @OA\Post(
     *     tags={"商城订单"},
     *     summary="获取能使用的优惠券 购物车/直接购买/价格",
     *     path="/wxapp/order/get_can_use_coupon",
     *
     *        @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ), @OA\Parameter(
     *         name="cart_id",
     *         in="query",
     *         description="购物车id 数组",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Parameter(
     *         name="price",
     *         in="query",
     *         description="价格",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Parameter(
     *         name="goods_id",
     *         in="query",
     *         description="商品id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Parameter(
     *         name="sku_id",
     *         in="query",
     *         description="规格id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="count",
     *         in="query",
     *         description="数量",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="shop_id",
     *         in="query",
     *         description="商家ID",
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

    public function get_can_use_coupon($price = '', $goods_id = '', $sku_id = '', $shop_id = 0, $count = 1)
    {
        $this->checkUser();
        $param = $this->request->param();
        Log::write($param, '可使用的优惠卷');
        $cart_id_arr = $this->request->param('cart_id');
//        $cart_id_arr = $this->request->param('cart_id',[24]);
        $SkuModel = new GoodsSkuModel();
        $CartModel = new CartModel();
        $GoodsModel = new GoodsModel();
        if (empty($price)) {
            $total_price = 0;
            if (!empty($cart_id_arr)) {
                $where = [];
                $where[] = ['id', 'in', $cart_id_arr];
                $cart_list = $CartModel->where($where)->order('id desc')->select();
//                $shop_id = $cart_list[0]['user_id'];
//                var_dump($shop_id);exit;
                foreach ($cart_list as &$value) {
                    $goods_info = $GoodsModel->where(['id' => $value['goods_id']])->find();
                    $value['price'] = $goods_info['price'];
                    if (!empty($value['sku_id'])) {
                        $sku_info = $SkuModel->where(['id' => $value['sku_id']])->find();
                        $value['price'] = $sku_info['price'];
                    }
                    $total_price += $value['price'] * $value['count'];
                }
                $total_price = round($total_price, 2);
            } else {
                $goods_info = $GoodsModel->where(['id' => $goods_id])->find();
                $shop_id = $goods_info['user_id'];

                if (empty($goods_info)) {
                    $this->error('商品错误');
                }
                if (!empty($sku_id)) {
                    //取sku信息
                    $sku_info = $SkuModel->where(['id' => $sku_id])->find();
                    if (empty($sku_info)) {
                        $this->error('商品错误');
                    }
                    if ($count > $sku_info['stock'] || $sku_info['stock'] == 0) {
                        $this->error('库存不足');
                    }
                    $total_price = $sku_info['price'] * $count;
                } else {
//                    if ($count > $goods_info['stock'] || $goods_info['stock'] == 0) {
//                        $this->error('库存不足');
//                    }
                    $total_price = $goods_info['price'] * $count;
                }
            }
        } else {
            $total_price = $price;
        }


        $UserCouponModel = new UserCouponModel();
        $map = [];
        $map[] = ['user_id', '=', $this->user_id];
        $map[] = ['shop_id', 'in', [$shop_id, 0]];
        $map[] = ['start_time', '<=', time()];
        $map[] = ['end_time', '>=', time()];
        $map[] = ['used', '=', 0];
//        var_dump($total_price);exit;
        $map[] = ['full_amount', '<=', $total_price];
        //$map[] = ['coupon_type', '=', 2];//商城券
        $list = $UserCouponModel
            ->field('id,name,amount,full_amount,start_time,end_time,shop_id')
            ->where($map)->paginate($this->page_size);

        foreach ($list as &$item) {
            //几天后到期
            $item['expire_time'] = $item['end_time'] - time();
            $item['indate_day'] = $item['expire_time'] > 0 ? ceil($item['expire_time'] / 86400) : 0;

        }

        $this->success('获取成功', $list);
    }


    /**
     * @OA\Post(
     *     tags={"商城订单"},
     *     summary="查看物流信息",
     *     path="/wxapp/order/get_express_info",
     *
     *        @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_express_info($order_num = '')
    {
        $this->checkUser();
        $order_info = ShopOrderModel::findDataByOrderNum($order_num, $this->user_id);
        //快递信息

        if (!empty($order_info['express_sn'])) {
            $express_num = $order_info['express_sn'];
            $express_name = $order_info['express_name'];

            $info = Db::name('shop_express')->where(['name' => $express_name])->find();
            $abbr = $info['abbr'];

            $ExpressController = new ExpressController();
            $CustomerName = '';
            if ($abbr == 'SF') {
                //取收件人手机号后四位
                if (!empty($order_info['phone'])) {
                    $CustomerName = substr($order_info['phone'], -4);
                }
            }
            $return = $ExpressController->getOrderTracesByJson($abbr, $express_num, $CustomerName);
            $return['express_name'] = $info['name'];
        } else {
            $return = [];
        }

        $this->success('获取成功', $return);
    }


    /**
     * 退货物流信息添加
     * @OA\Post(
     *tags={"商城订单"},
     *path="/wxapp/order/aftersale_express",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="订单ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="return_express_name",description="快递公司",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="return_express_sn",description="快递单号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function aftersale_express(ShopOrderModel $orderModel)
    {
        $this->checkUser();
        $params = $this->request->param();
        $item = $orderModel->where(['id' => $params['id']])->find();
        if (empty($item)) $this->error('售后订单不存在');
        if ($item['status'] != 20) $this->error('售后订单状态不正确');
        $orderModel->where(['id' => $params['id']])->update([
            'status' => 22,
            'return_express_name' => $params['return_express_name'],
            'return_express_sn' => $params['return_express_sn']]);
        $this->success('保存成功');
    }


    /**
     * 发票填写
     * @OA\Post(
     *     tags={"订单-发票"},
     *     path="/wxapp/order/add_invoice",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="pay_order_num",description="支付订单号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="抬头类型 1：企业，2：个人",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="invoiceKind",description="81增值税专用发票[数电]  82普通发票[数电]",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="title",description="发票抬头",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id_card",description="身份证号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="bank_name",description="开户银行",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="bank_sn",description="开户银行卡号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="tax_sn",description="公司税号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="address",description="公司地址",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="phone",description="公司手机号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="email",description="邮箱",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="notes",description="备注",in="query",required=false, @OA\Schema(type="string",)),
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function add_invoice(OrderInvoiceModel $shopInvoiceModel, ShopOrderModel $orderModel)
    {
        $this->checkUser();
        $param = $this->request->param();
        if (empty($param['title'])) $this->error('抬头不能为空！');
        if (empty($param['invoiceKind'])) $this->error('发票类型不能为空！');

        if ($param['type'] == 1) {
            if (empty($param['tax_sn'])) $this->error('公司税号不能为空！');
            if (strlen($param['invoiceKind']) > 20) $this->error('纳税人识别号长度不能超过20个字符');
        } else {
//            if (empty($param['id_card'])) $this->error('身份证号不能为空！');
        }

        $order_num = $this->request->param('order_num');
        if (!$order_num) $this->error('订单不能为空');
        $order = $orderModel->where('pay_order_num', $order_num)->find();
        if (!$order) $this->error('订单不存在');
        $shopInvoice = $shopInvoiceModel->where('order_num', $order_num)->find();
        if ($shopInvoice && $shopInvoice['status'] < 3) {
            $this->error('发票已存在');
        }
        $param['user_id'] = $this->user_id;
        $param['create_time'] = time();
        $id = $shopInvoiceModel->strict(false)->insert($param, 1);
        if ($id) {
            $this->success('提交成功');
        } else {
            $this->error('创建失败');
        }


    }


    /**
     * 发票-详情
     * @OA\Post(
     *     tags={"订单-发票"},
     *     path="/wxapp/order/get_invoice",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="order_num",description="订单号",in="query",required=false, @OA\Schema(type="string",)),
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function get_invoice(OrderInvoiceModel $shopInvoiceModel, ShopOrderModel $orderModel)
    {
        $this->checkUser();
        $order_num = $this->request->param('order_num');
        if (!$order_num) $this->error('订单不能为空');
        $order = $orderModel->where('pay_order_num', $order_num)->find();
        if (!$order) $this->error('订单不存在');
        $shopInvoice = $shopInvoiceModel->where('order_num', $order_num)->find();
        $shopInvoice=CommonModel::getOne($shopInvoice,['invoice']);
        if ($shopInvoice) {
            $this->success('获取成功', $shopInvoice);
        } else {
            $this->error('获取失败');
        }

    }


}
