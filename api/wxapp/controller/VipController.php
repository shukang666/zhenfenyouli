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
use api\wxapp\model\VipCateModel;
use api\wxapp\model\VipModel;
use api\wxapp\model\VipOrderModel;
use api\wxapp\model\VipPayModel;
use think\Model;

class VipController extends AuthController
{

    /**
     * @OA\Post(
     *      tags={"会员"},
     *      summary="获取会员价格",
     *     path="/wxapp/vip/get_vip",
     *        @OA\Parameter(
     *         name="cate_id",
     *         in="query",
     *         description="vip的分类",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function get_vip($cate_id = '')
    {

        $Model = new VipModel();
        $vipModel = new VipCateModel();

        $where[] = ['status', '=', 1];
        if (!empty($cate_id)) {
            $where[] = ['cate_id', '=', $cate_id];
        }
        $list = $Model->where($where)->with('cate')->select()->each(function ($item)use ($vipModel) {
            $vip= $vipModel->where('id', $item->cate_id)->field('mobile_num,top_num,reset_num')->find();
            $item->mobile_num = $vip->mobile_num;
            $item->top_num = $vip->top_num;
            $item->reset_num = $vip->reset_num;

            $item->day = $item->month*30;
            if (($item->month==12)) {
                $item->day = 365;
            }
        });
        $list = getFullData($list);
        $this->success('获取成功', $list);

    }

    /**
     * @OA\Post(
     *      tags={"会员"},
     *      summary="vip分类",
     *     path="/wxapp/vip/get_vip_cate",
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function get_vip_cate()
    {

        $Model = new VipCateModel();
        $list = $Model->select();
        $list = getFullData($list);
        $this->success('获取成功', $list);

    }

    /**
     * @OA\Post(
     *     tags={"会员"},
     *     summary="添加vip订单",
     *     path="/wxapp/vip/add_order",
     *
     *        @OA\Parameter(
     *         name="vip_id",
     *         in="query",
     *         description="vip的id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_order($vip_id = '')
    {
        $this->checkUser();
        if ($this->request->isPost()) {

            $VipOrderModel = new VipOrderModel();
            $VipPayModel = new VipPayModel();
            $VipModel = new VipModel();

            $vip = $VipModel->where(['id' => $vip_id])->find();

            if ($vip['price'] <= 0) {
                $this->error('价格错误');
            }
            $data_i = [];
            $data_i['user_id'] = $this->user_id;
            $data_i['order_num'] = CommonModel::createOrderNumber();
            $data_i['price'] = $vip['price'];
            $data_i['create_time'] = time();
            $data_i['vip_id'] = $vip_id;
            $data_i['month'] = $vip['month'];
            $data_i['status'] = 1;
            $data_i['cate_id'] = $vip['cate_id'];
            $data_i['end_time'] = strtotime(" +{$vip['month']}  month");

            $res = $VipPayModel->save($data_i);

            CommonModel::addOrderPay($data_i['order_num'], 7, $vip['price']);

            if ($res) {
                $this->success('添加成功', ['order_num' => $data_i['order_num']]);
            } else {
                $this->error('添加失败');
            }

        } else {
            $this->error('非法操作');
        }
    }


}
