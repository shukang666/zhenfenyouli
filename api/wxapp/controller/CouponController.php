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
use api\wxapp\model\CouponModel;
use api\wxapp\model\UserCouponModel;
use api\wxapp\model\VipOrderModel;
use cmf\controller\RestBaseController;
use think\Model;

class CouponController extends AuthController
{

    /**
     * @OA\Get(
     *     tags={"小程序-优惠券"},
     *     summary="优惠券列表",
     *     path="/wxapp/coupon/get_coupon_list",
     *      @OA\Parameter(
     *         name="discount_type",
     *         in="query",
     *         description=" 1老人 2孩子 3孕妇",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="exam_id",
     *         in="query",
     *         description=" 问卷ID",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description=" openid",
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
    public function get_coupon_list($exam_id = 0, $type = '', $discount_type = '')
    {
        $this->checkUser();

        $CouponModel = new CouponModel();
        if ($exam_id) {
            $exam_info = cmf_table('exam')->find($exam_id);
            $type = $exam_info['coupon_type'];
        }

        $map[] = ['a.status', '=', 1];
        if (!empty($type)) {
            $map[] = ['a.type', '=', $type];
        }
        if (!empty($discount_type)) {
            $map[] = ['a.discount_type', '=', $discount_type];
        }
        $map[] = ['a.coupon_type', '=', 1];
        $UserCouponModel = new UserCouponModel();
        $list = $CouponModel->getInfo($map, $this->page_size);
        foreach ($list as &$item) {
            $map = [];
            $map['user_id'] = $this->user_id;
            $map['coupon_id'] = $item['id'];
            $have_count = $UserCouponModel->where($map)->count();
            if ($have_count >= $item['limit']) {
                $item['status'] = 2;
            } else {
                $item['status'] = 1;
            }
        }
        $this->success('获取成功', $list);
    }

    /**
     * @OA\Post(
     *     tags={"小程序-优惠券"},
     *     summary="领取优惠券",
     *     path="/wxapp/coupon/select_coupon",
     *
     *      @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="优惠券id",
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
    public function select_coupon($id = '')
    {
        $this->checkUser();

        $FindModel = CouponModel::find($id);
        if (empty($FindModel)) {
            $this->error('优惠券不存在');
        }
        if ($FindModel['count'] <= 0) {
            $this->error('优惠券已领取完');
        }
        $UserCouponModel = new UserCouponModel();

        $limit = $FindModel['limit'];
        $map = [];
        $map['user_id'] = $this->user_id;
        $map['coupon_id'] = $id;
        $have_count = $UserCouponModel->where($map)->count();
        if ($have_count >= $limit) {
            $this->error('超出可领取数量');
        }
        $res = $UserCouponModel->addData($id, $this->user_id);

        if ($res) {
            $this->success('领取成功');
        } else {
            $this->error('领取失败');
        }
    }

    /**
     * @OA\Post(
     *     tags={"小程序-优惠券"},
     *     summary="我的优惠券",
     *     path="/wxapp/coupon/my_coupon",
     *
     *      @OA\Parameter(
     *         name="coupon_type",
     *         in="query",
     *         description=" 1:问卷，2：商家优惠卷",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Parameter(
     *         name="discount_type",
     *         in="query",
     *         description=" 1老人 2孩子 3孕妇",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *       @OA\Parameter(
     *         name="used",
     *         in="query",
     *         description="0未使用 1已使用 2已过期",
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
    public function my_coupon($coupon_type = 0,$type = '', $used = '', $discount_type = '')
    {
        $this->checkUser();
        $UserCouponModel = new UserCouponModel();
        $map = [];
        $map['a.user_id'] = $this->user_id;
        if ($type) $map['a.type'] = $type;
        if ($coupon_type) $map['a.coupon_type'] = $coupon_type;
        if ($discount_type)  $map['a.discount_type'] = $discount_type;
        if (!empty($used) || $used === '0' || $used === 0) {
            $map['a.used'] = $used;
        }
        $list = $UserCouponModel->alias('a')
            ->field('a.*')
            ->where($map)->order('a.id desc')->paginate($this->page_size);

        $CouponModel = new CouponModel();
//        $list = $CouponModel->changeData($list);

        $this->success('获取成功', $list);

    }



    /**
     * @OA\Post(
     *     tags={"小程序-优惠券"},
     *     summary="我的优惠券",
     *     path="/wxapp/coupon/my_shop_coupon",
     *
     *       @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     * *
     *       @OA\Parameter(
     *         name="used",
     *         in="query",
     *         description="0未使用 1已使用 2已过期",
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
    public function my_shop_coupon($coupon_type = 2,$type = '', $used = '', $discount_type = '')
    {
        $this->checkUser();
        $UserCouponModel = new UserCouponModel();
        $map = [];
        $map['a.user_id'] = $this->user_id;
        if ($type) $map['a.type'] = $type;
        //if ($coupon_type) $map['a.coupon_type'] = $coupon_type;
        if ($discount_type)  $map['a.discount_type'] = $discount_type;
        if (!empty($used) || $used === '0' || $used === 0) {
            $map['a.used'] = $used;
        }
        $list = $UserCouponModel
            ->alias('a')
            ->field('a.*,b.shop_name,b.id shop_id')
            ->leftJoin('shop b','a.shop_id = b.user_id')
            ->where($map)->order('a.id desc')
            ->paginate($this->page_size);

        foreach ($list as &$item) {
            //几天后到期
            $item['expire_time'] = $item['end_time'] - time();
            $item['indate_day'] = $item['expire_time'] > 0 ? ceil($item['expire_time'] / 86400) : 0;
            if(empty($item['shop_id'])){
                $item['shop_id'] = -1;
            }

        }

//        $CouponModel = new CouponModel();
////        $list = $CouponModel->changeData($list);

        $this->success('获取成功', $list);

    }


}
