<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\MemberModel;
use api\wxapp\model\ShopOrderModel;
use api\wxapp\model\UserCouponModel;
use app\admin\controller\WxOrderController;
use app\admin\model\finance\FinanceModel;
use app\admin\model\hotel\GroupOrderModel;
use app\admin\model\hotel\MeetingOrderModel;
use app\admin\model\hotel\RoomOrderModel;
use app\admin\model\order\OrderTakeModel;
use think\Log;

class TaskController extends AuthController
{
    /**
     * 测试
     * @OA\Post(
     *tags={"计划任务"},
     *path="/wxapp/task/test",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function test()
    {


    }


    /**
     * 订单计划任务
     * @OA\Post(
     *tags={"计划任务"},
     *path="/wxapp/task/get_task",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_task()
    {
        $ShopOrderModel = new ShopOrderModel();
        $UserCouponModel = new UserCouponModel();

        $ShopOrderModel->where('status', 1)
            ->where('create_time', '<', time() - 15 * 60)
            ->update(['status' => 10, 'cancel_time' => time()]);


//        //待收货，7天后收货
//
        $list = $ShopOrderModel->where('status', 4)
            ->where('send_time', '<', time() - 7 * 86400)
            ->select();
        foreach ($list as $item) {
            if (!empty($item['shop_id'])) {
                AccountLogModel::add_account_log($item['shop_id'], 3, 300, $item['price'], ['order_num' => $item['order_num']]);
            }
            $ShopOrderModel->where('id', $item['id'])->update(['status' => 6, 'receive_time' => time()]);
        }
//        // 记录日志（示例：写入数据库或文件）
//        \think\facade\Log::write();

        $UserCouponModel->where('used', 0)
            ->where('end_time', '<', time())
            ->update(['used' => 2]);

        //同步发货
        $WxOrderController = new WxOrderController();
        $OrderTakeModel = new OrderTakeModel();
        $list = $OrderTakeModel->where('status', 1)->select();
        foreach ($list as $item) {
            $res = $WxOrderController->uploadShippingInfo($item['order_num'], $item['openid'], '物品', 4);
            if ($res['code'] == 1) {
                $status = 2;
            } else {
                $status = 3;
            }
            $OrderTakeModel->where('id', $item['id'])->update(['status' => $status]);
        }

        $this->success('成功');
    }


    /**
     * 计算商家价格
     * @OA\Post(
     *tags={"计划任务"},
     *path="/wxapp/task/count_shop_price",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function count_shop_price()
    {
        $FinanceModel = new FinanceModel();
        $AccountLogModel = new AccountLogModel();
        $RoomOrderModel = new RoomOrderModel();
        $where = [];
        $where[] = ['status', '=', 1];
//        $where[] = ['end_time', '<', time()];
        $list = $FinanceModel->where($where)->select();
        foreach ($list as $item) {

            $where = $map = [];
            $where[] = ['hotel_id', '=', $item['hotel_id']];
            $where[] = ['order_type', '=', $item['type']];
            $start_date = strtotime(date('Y-m-d', $item['create_time']));
            $where[] = ['create_time', 'between', [$start_date, $item['end_time']]];
            if ($item['type'] == 1) {
                $map[] = ['pay_type', '<', 4];
            } else {
                $map[] = ['pay_type', '=', 4];
            }
            $night = $RoomOrderModel->where($where)->where('status', 'in', [2, 4, 6, 7])->count();
            $where[] = ['account_type', '=', 3];
            $info = $AccountLogModel->where($where)
                ->field('sum(change_amount) change_amount,sum(price) price')->find();

            $data = [
                'service_price' => $info['price'],
                'pay_price' => $info['change_amount'],
                'night' => $night,
                'status' => 2,
            ];
//            var_dump($data);exit;
            $FinanceModel->where('id', $item['id'])->update($data);
        }
        $this->success('成功');
    }


}