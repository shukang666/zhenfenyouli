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
use api\wxapp\model\BalanceOrderModel;
use api\wxapp\model\WithdrawModel;
use app\admin\model\accompany\RechargeModel;

use app\admin\model\member\MemberModel;
use think\Model;

class BalanceController extends AuthController
{

    /**
     * @OA\Post(
     *     tags={"余额、积分等"},
     *     summary="充值",
     *     path="/wxapp/balance/add_balance_order",
     *
     *      @OA\Parameter(
     *         name="num",
     *         in="query",
     *         description="充值金额",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Parameter(
     *         name="rid",
     *         in="query",
     *         description="充值金额列表充值 送积分 填id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function add_balance_order($num = '', $rid = 0)
    {
        $this->checkUser();
        $BalanceOrderModel = new BalanceOrderModel();
        $rechargeModel = new RechargeModel();

        $order_num = CommonModel::createOrderNumber();
        if (!empty($rid)) {
            //充值金额列表充值 送积分
            $recharge = $rechargeModel->where('id', $rid)->find();
            if (!empty($recharge)) {
                $num = $recharge['balance'];
            } else {
                $this->error('充值金额列表不存在');
            }
        }

        if (!is_numeric($num)) $this->error('充值金额为数字');
        $data_i = [];
        $data_i['order_num'] = $order_num;
        $data_i['price'] = $num;
        $data_i['user_id'] = $this->user_id;
        $data_i['status'] = 1;
        $res1 = $BalanceOrderModel->save($data_i);
        $res2 = CommonModel::addOrderPay($order_num, 1, $num, ['rid' => $rid, 'user_id' => $this->user_id]);
        if ($res1 && $res2) {
            $this->success('添加成功', ['order_num' => $order_num]);
        } else {
            $this->error('添加失败');
        }

    }

    /**
     * @OA\Post(
     *     tags={"余额、积分等"},
     *     summary="提现",
     *     path="/wxapp/balance/withdraw",
     *     @OA\Parameter(
     *         name="num",
     *         in="query",
     *         description="金额",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="ali_account",
     *         in="query",
     *         description="支付宝账户 这2个字段可不传，再更新用户接口更新也行",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="ali_name",
     *         in="query",
     *         description="支付宝姓名",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public
    function withdraw($num = '', $ali_account = '', $ali_name = '', $account_type = 1, $type = 2, $bank_id = '')
    {
        $this->checkUser();
        $Model = new WithdrawModel();

        if (empty($num) || !is_numeric($num)) $this->error('金额错误');
        if ($num <= 0) $this->error('提现金额错误');
        if ($account_type == 1) {
            if ($num > $this->user_info['shop_balance']) $this->error('余额不足');
        }
        if ($num<0.1)$this->error('提现金额不能小于0.1');

//        if (!empty($ali_account) && !empty($ali_name)) {
//            CommonModel::updateUser($this->user_id, ['ali_account' => $ali_account, 'ali_name' => $ali_name]);
//        } else {
//            if (empty($this->user_info['ali_account']) || empty($this->user_info['ali_name'])) $this->error('请先完善支付宝账户');
//            $ali_account = $this->user_info['ali_account'];
//            $ali_name = $this->user_info['ali_name'];
//        }


        //手续费
        $info = cmf_get_option('set_config');
        $withdraw_fee = $info['fee_price'] ?? 0;
        //判断是否已填写支付宝账号 用户名
        $param = ['ali_account' => $ali_account, 'ali_name' => $ali_name];

        $data_i = [
            'user_id' => $this->user_id, 'num' => $num, 'fee' => round($num * $withdraw_fee / 100, 2),
            'money' => round($num - $num * $withdraw_fee / 100, 2) , 'create_time' => time(),
            'param' => json_encode($param), 'account_type' => $account_type, 'type' => $type,
        ];
        $w_id = $Model->insertGetId($data_i);
        if ($w_id) {
            //提现记录
            $a_id = AccountLogModel::add_account_log($this->user_id, 3, 310, -$num, ['withdraw_id' => $w_id]);
            $Model->where(['id' => $w_id])->update(['account_id' => $a_id]);
            $this->success('申请成功，请等待管理员审核');
        } else {
            $this->error('申请失败');
        }
    }

    /**
     * @OA\Post(
     *     tags={"余额、积分等"},
     *     summary="提现记录",
     *     path="/wxapp/balance/withdraw_list",
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public
    function withdraw_list($account_type = 1)
    {
        $this->checkUser();
        $Model = new WithdrawModel();

        $map = [];
        $map[] = ['a.user_id', '=', $this->user_id];
        $map[] = ['a.account_type', '=', $account_type];

        $list = $Model->getInfo($map, 0, 'a.*', $this->page_size);
        $this->success('获取成功', $list);
    }

    /**
     * @OA\Post(
     *     tags={"余额、积分等"},
     *     summary="获取交易类型",
     *     path="/wxapp/balance/get_type",
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public
    function get_type($account_type = 1)
    {
        $list = AccountLogModel::getAccountLogTypeName($account_type);
        foreach ($list as $key => $item) {
            $new_list[] = ['type' => $key, 'name' => $item];
        }
        $this->success('获取成功', $list);
    }

    /**
     * @OA\Post(
     *     tags={"余额、积分等"},
     *     summary="交易记录",
     *     path="/wxapp/balance/get_log_list",
     *      @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="日期,2012-12-12",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *       @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="日期,2012-12-12",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="account_type",
     *         in="query",
     *         description="1:余额，2：积分，3商家",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="交易类型",
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

    public
    function get_log_list($account_type = 3, $type = 0, $start_date = '', $end_date = '')
    {
        $this->checkUser();
        $AccountLogModel = new AccountLogModel();

        $map = [];
        $map[] = ['a.user_id', '=', $this->user_id];
        if ($account_type) {
            $map[] = ['a.account_type', '=', 3];
        }
        if ($type) {
            $map[] = ['a.type', '=', $type];
        }

        if ($start_date) {
            $map[] = ['a.create_time', '>=', strtotime($start_date)];
        }
        if ($end_date) {
            $map[] = ['a.create_time', '<=', strtotime($end_date) + 86399];
        }

        $list = $AccountLogModel->getInfo($map, $this->page_size);
        $this->success('获取成功', $list);
    }

    /**
     * 充值金额列表
     * @OA\Post(
     *     tags={"余额、积分等"},
     *     path="/wxapp/balance/get_recharge",
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public
    function get_recharge(RechargeModel $rechargeModel)
    {
        $list = $rechargeModel->select();
        $this->success('查询成功！', $list);
    }


}
