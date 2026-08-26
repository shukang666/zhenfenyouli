<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use api\wxapp\model\CouponModel;
use api\wxapp\model\GoodsModel;
use api\wxapp\model\OrderCommentModel;
use api\wxapp\model\ShopOrderDetailModel;
use api\wxapp\model\ShopOrderModel;
use api\wxapp\model\UserCouponModel;
use app\admin\controller\WxOrderController;
use app\admin\model\member\CheckModel;
use app\admin\model\member\MemberModel;
use app\admin\model\order\OrderQrcodeModel;
use app\admin\model\shop\ShopModel;
use think\facade\Log;

class ShopController extends AuthController
{
    /**
     * 商家申请
     * @OA\Post(
     *tags={"商家"},
     *path="/wxapp/shop/add_shop",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="name",description="姓名",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="mobile",description="手机号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="shop_name",description="门店名称",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="avatar",description="门店头像",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="address",description="门店位置",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="lng",description="经度",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="lat",description="纬度",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="desc",description="介绍",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="license",description="营业执照",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_shop(ShopModel $model)
    {
        $this->checkUser();

        $params = $this->request->param();
        $item = $model->where('user_id', $this->user_id)->find();
        if (empty($item)) {
            $params['user_id'] = $this->user_id;
            $model->save($params);
        } else {
            $model->update($params, ['id' => $item['id']]);

        }
        $this->success('提交成功');
    }


    /**
     * 商家申请详情
     * @OA\Post(
     *tags={"商家"},
     *path="/wxapp/shop/get_shop",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_shop(ShopModel $model, OrderCommentModel $CommentModel)
    {

        $id = $this->request->param('id',0);
        if ($id == -1) {
            $point = $CommentModel->where(['shop_id' => 0])->avg('point');
            $item = [
                'id' => -1,
                'shop_name' => '平台',
                'mobile'=>$this->get_config['platform_mobile'],
                'avatar' => cmf_get_asset_url($this->get_config['app_logo'])
                , 'point' => $point
            ];
            $this->success('请求成功', $item);
        } elseif ($id > 0) {
            $item = $model->where('id', $id)->find();
            $this->success('请求成功', $item);
        } elseif ($id == 0) {
            $this->checkUser();
            $item = $model->where('user_id', $this->user_id)->find();
            if (empty($item)) $this->error('暂无数据');
            $item = CommonModel::getOne($item, ['license', 'avatar']);
            $item['status_name'] = $model->get_status()[$item['status']] ?? '';
            $this->success('请求成功', $item);
        }

    }

    /**
     * 商家订单
     * @OA\Post(
     *tags={"商家"},
     *path="/wxapp/shop/get_shop_order_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="status",description="不传取全部，订单状态 1待付款 2待发货 4待收货  6待评价 8已完成 10退款/取消",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_shop_order_list($status = 0)
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
        $map[] = ['a.shop_id', '=', $this->user_id];
        $list = $ShopOrderModel->getInfo($map, 'a.id,a.order_num,a.price,a.total_price,a.status,a.create_time,a.need_pay_time,a.pay_type,a.point', $this->page_size);
        $this->success('获取成功', $list);
    }


    /**
     * 商家发货
     * @OA\Post(
     *tags={"商家"},
     *path="/wxapp/shop/shop_send",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="订单id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="express_name",description="快递公司",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="express_sn",description="快递单号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function shop_send(ShopOrderModel $orderModel)
    {
        $this->checkUser();
        $id = $this->request->param('id');
        $order = $orderModel->find($id);
        if (empty($order)) $this->error('订单不存在');
        $data = [
            'status' => 4,
            'express_name' => $this->request->param('express_name', ''),
            'express_sn' => $this->request->param('express_sn', ''),
        ];
        $order->save($data);
//        $WxOrderController=  new WxOrderController();
//
//        $openid= MemberModel::where('user_id', $order['user_id'])->value('openid');
//        //给腾讯推送
//        $WxOrderController->uploadShippingInfo($order['order_num'],$openid,'物品',1,$data['express_name'],$data['express_sn'],$order['phone']);
        $this->success('发货成功');
    }


    /**
     * 添加优惠卷
     * @OA\Post(
     *tags={"商家"},
     *path="/wxapp/shop/add_coupon",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="编辑",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="name",description="优惠卷名称",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="full_amount",description="折扣金额",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="amount",description="满多少可用",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="indate_day",description="有效期(天)",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_coupon(CouponModel $couponModel)
    {
        $this->checkUser();
        $param = $this->request->param();
        if (!empty($param['user_id'])) {
            $item = $couponModel->update($param);
        } else {
            $param['user_id'] = $this->user_id;
            $param['coupon_type'] = 2;
            $param['indate_cate'] = 1;
            $param['status'] = 1;
            $item = $couponModel->save($param);
        }

        $this->success('保存成功');
    }


    /**
     * 商家的优惠卷
     * @OA\Post(
     *tags={"商家"},
     *path="/wxapp/shop/get_shop_coupon_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_shop_coupon_list(CouponModel $couponModel)
    {
        $id = $this->request->param('id');
        if ($id) {
            $item = $couponModel->alias('a')
                ->field('a.id,a.name,a.amount,a.full_amount,c.shop_name,indate_day,c.id  as shop_id')
                ->leftjoin('shop c', 'a.user_id = c.user_id')
                ->where('a.id', $id)->find($id);
//            $item['stauts'] = 1;
            $map = [];
            $map['user_id'] = $this->user_id;
            $map['coupon_id'] = $item['id'];

            $UserCouponModel = new UserCouponModel();
            $item['status'] = $have_count = $UserCouponModel->where($map)->count() ? 1 : 0;
//            if ($have_count >= $item['limit']) {
//                $item['status'] = 2;
//            } else {
//                $item['status'] = 1;
//            }
            $this->success('成功', $item);
        }
        //$map[] = ['a.coupon_type', '=', 2];
        $map[] = ['a.status', '=', 1];
        $list = $couponModel->alias('a')
            ->field('a.id,a.name,a.amount,a.full_amount,c.shop_name,indate_day,c.id  as shop_id')
            ->leftjoin('shop c', 'a.user_id = c.user_id')
            ->where($map)->order('a.id desc')->paginate($this->page_size);
        foreach ($list as $item) {
            $map = [];
            $map['user_id'] = $this->user_id;
            $map['coupon_id'] = $item['id'];
            $UserCouponModel = new UserCouponModel();
            $item['status'] = $have_count = $UserCouponModel->where($map)->count() ? 1 : 0;
        }
        $this->success('获取成功', $list);
    }


    /**
     * 我的的优惠卷列表
     * @OA\Post(
     *tags={"商家"},
     *path="/wxapp/shop/my_shop_coupon_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function my_shop_coupon_list(CouponModel $couponModel)
    {
        $this->checkUser();
        $map[] = ['a.coupon_type', '=', 2];
        $map[] = ['a.user_id', '=', $this->user_id];
        $map[] = ['a.status', '=', 1];
        $list = $couponModel->getInfo($map, $this->page_size);
        $this->success('获取成功', $list);
    }


    /**
     * 领取商家优惠卷
     * @OA\Post(
     *tags={"商家"},
     *path="/wxapp/shop/receive_coupon",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="优惠卷id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function receive_coupon(UserCouponModel $UserCouponModel)
    {
        $this->checkUser();
        $id = $this->request->param('id');
        $res = $UserCouponModel->addData($id, $this->user_id);
        if ($res) {
            $this->success('领取成功');
        } else {
            $this->error('领取失败');
        }
    }


    /**
     * 商家订单统计
     * @OA\Post(
     *tags={"商家"},
     *path="/wxapp/shop/get_shop_order_total",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_shop_order_total()
    {
        $this->checkUser();
        $type = $this->request->param('type', 1);
        $shopOrderModel = new  ShopOrderModel();
        $status = [2, 4];// 6,, 8
        foreach ($status as $k => $v) {
            $map = [];
            $map[] = ['shop_id', '=', $this->user_id];
            $map[] = ['delivery_type', '=', 1];
            $data['status' . $v] = $shopOrderModel->getOrderStatusCount($v, $map);
        }
        $map = [];
        $map[] = ['shop_id', '=', $this->user_id];
        $map[] = ['delivery_type', '=', 2];
        $data['status6']=$shopOrderModel->getOrderStatusCount($v, $map);
        $map = [];
        $map[] = ['shop_id', '=', $this->user_id];
        $map[] = ['delivery_type', '=', 1];
        $map[] = ['status', 'in', [6,8]];
        $data['status8'] = $shopOrderModel->where($map)->count();
        $this->success('获取成功', $data);
    }


    /**
     * 商家商品统计
     * @OA\Post(
     *tags={"商家"},
     *path="/wxapp/shop/get_shop_goods_total",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="start_date",description="开始时间",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="end_date",description="结束时间",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_shop_goods_total(\app\admin\model\goods\GoodsModel $goodsModel, ShopOrderModel $shopOrderModel, ShopOrderDetailModel $detailModel)
    {
        $this->checkUser();
        $start_date = $this->request->param('start_date');
        $end_date = $this->request->param('end_date');

        $where[] = ['user_id', '=', $this->user_id];
        $info = $goodsModel
            ->field('sum(sell_amount) sell_amount,sum(sell_count) sell_count')
            ->where($where)->find()->toArray();
        $list = $goodsModel->field('id,goods_name,thumb')
            ->where($where)->paginate($this->page_size);
        foreach ($list as &$item) {
            if (!empty($item['thumb'])) $item['thumb'] = cmf_get_asset_url($item['thumb']);
            $map = [];
            if ($start_date) $map[] = ['create_time', '>=', strtotime($start_date)];
            if ($end_date) $map[] = ['create_time', '<=', strtotime($end_date)];
            $map[] = ['goods_id', '=', $item['id']];
            $item['goods_num'] = $detailModel->where($map)->sum('count');
            $item['goods_price'] = $detailModel->where($map)->sum('one_price');
        }
        $info['list'] = $list;
        $this->success('获取成功', $info);
    }


    /**
     * 核销二维码
     * @OA\Post(
     *tags={"小程序-用户"},
     *path="/wxapp/user/scan_qrcode_price",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="qrcode",description="二维内容",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function scan_qrcode_price(OrderQrcodeModel $qrcodeModel, ShopOrderModel $orderModel)
    {
        $this->checkUser();
        $qrcode = $this->request->param('qrcode');
        $qrcode = $qrcodeModel->where(['qrcode_text' => $qrcode])->find();
        if (!$qrcode) $this->error('二维码不存在');
        $order = $orderModel->find($qrcode['order_id']);
        AccountLogModel::add_account_log($this->user_id, 3, 300, $order['price']);
        $qrcode->save(['status' => 2]);
        $order->save(['status' => 6]);
        $this->success('扫码成功');
    }

}