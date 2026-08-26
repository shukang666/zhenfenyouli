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

use api\wxapp\controller\WeChatController;
use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use api\wxapp\model\UserCouponModel;
use api\wxapp\model\WithdrawModel;
use app\admin\model\hotel\HotelModel;
use cmf\controller\AdminBaseController;

class AccountController extends AdminBaseController
{

    /*
     * 提现管理
     */
    public function withdraw()
    {
        $Model = new WithdrawModel();
        $keyword = $this->request->param('keyword');

        $map = [];

        if (!empty($keyword)) {
            $map[] = ['b.nickname|b.true_name|b.mobile', 'like', "%$keyword%"];
        }
        $list = $Model->getInfo($map);

        $list->appends(['keyword' => $keyword]);
        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        return $this->fetch();
    }


    public function doWithdraw()
    {
        $status = $this->request->param('status');
        $id = $this->request->param('id');
        $type = $this->request->param('type', 1);
        $text = $this->request->param('text', '');
        $Model = new WithdrawModel();
        $item = $Model->where(['id' => $id])->json(['param'], true)->find();
        $data = [
            'status' => $status,
            'refuse_reason' => $text,
            'update_time' => time()];
        if ($status == 2) {
            //拒绝
            $info = $Model->where(['id' => $id])->find();
            AccountLogModel::add_account_log($info['user_id'], 3, 311, $info['num'], ['withdraw_id' => $id, 'reason' => $text, 'update_time' => time()]);
        } elseif ($status == 1) {
            $weChat = new WeChatController();
            $order_num = CommonModel::createOrderNumber();
            $openid = cmf_table('member')->where(['id' => $item['user_id']])->value('openid');
            $res = $weChat->crteateMchPay($openid, $item['money'], $item['param']['ali_name'], $order_num);
            if (empty($res['package_info'])) $this->error('提现失败，请重试', '', $res);
            $data['package_info'] = $res['package_info'];
            $data['mch_id'] = config('wxapp.mch_id');
            $data['order_num'] = $order_num;
        }
        $Model->where(['id' => $id])->save($data);
        $this->success('设置成功');
    }

    /*
   * 用户余额记录
   */
    public function userAccountLog()
    {
        $Model = new AccountLogModel();

        $keyword = $this->request->param('keyword');
        $account_type = $this->request->param('account_type',1);
        $type = $this->request->param('type');
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');

        $map = [];
        if($account_type){
            $map[] = ['a.account_type', '=', $account_type];
        }
        if($type){
            $map[] = ['a.type', '=', $type];
        }
        if (!empty($keyword)) {
            $map[] = ['b.nickname|b.mobile|b.true_name', 'like', "%$keyword%"];
        }
        if($start_time)  $map[] = ['a.create_time','>=',strtotime($start_time)];
        if($end_time)  $map[] = ['a.create_time','<=',strtotime($end_time)+86399];
        $list = $Model->getInfo($map);

        $list->appends(['keyword' => $keyword,'account_type'=>$account_type,'type'=>$type,'start_time'=>$start_time,'end_time'=>$end_time]);
        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('account_type', $account_type);
        $this->assign('type', $type);
        $this->assign('type_list', AccountLogModel::getAccountLogTypeName($account_type,-1));

        return $this->fetch();
    }

    //余额记录
    public function userBalanceLog(){
        return redirect((string) url('userAccountLog',['account_type' => 1]));
    }
    //积分记录
    public function userPointLog(){
        return redirect((string) url('userAccountLog',['account_type' => 2]));
    }

    public function shop(){
        return redirect((string) url('userAccountLog',['account_type' => 3]));
    }

    public function delete_all()
    {
        $ids = $this->request->param('ids');
        AccountLogModel::whereIn('id', $ids)->update(['delete_time'=>time()]);
        $this->success('删除成功');
    }

}
