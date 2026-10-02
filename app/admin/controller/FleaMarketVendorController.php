<?php

namespace app\admin\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\service\FleaMarketService;
use cmf\controller\AdminBaseController;
use think\facade\Db;

class FleaMarketVendorController extends AdminBaseController
{
    public function index()
    {
        $status = $this->request->param('status', '');
        $sessionId = (int)$this->request->param('session_id', 0);

        $query = Db::name(FleaMarketService::vendorTable())->alias('v')
            ->join('member m', 'm.id = v.user_id', 'LEFT')
            ->join('cmf_flea_market_session s', 's.id = v.session_id', 'LEFT')
            ->field('v.*, m.nickname, m.mobile, s.title as session_title, s.date_text, s.address');
        if ($status !== '') {
            $query->where('v.status', (int)$status);
        }
        if ($sessionId > 0) {
            $query->where('v.session_id', $sessionId);
        }
        $list = $query->order('v.id desc')->paginate(15);

        $sessions = Db::name(FleaMarketService::sessionTable())->order('id desc')->column('title', 'id');

        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $this->assign('status', $status);
        $this->assign('session_id', $sessionId);
        $this->assign('sessions', $sessions);
        return $this->fetch();
    }

    public function pass()
    {
        $id = (int)$this->request->param('id');
        $row = Db::name(FleaMarketService::vendorTable())->where('id', $id)->find();
        if (empty($row) || (int)$row['status'] !== 0) {
            $this->error('记录不可审核');
        }
        $now = time();
        Db::name(FleaMarketService::vendorTable())->where('id', $id)->update([
            'status' => 1,
            'audit_time' => $now,
        ]);
        Db::name(FleaMarketService::highlightTable())
            ->where('vendor_id', $id)
            ->update(['status' => 1]);
        $this->success('已通过，申请人可在小程序查看状态');
    }

    public function reject()
    {
        $id = (int)$this->request->param('id');
        $row = Db::name(FleaMarketService::vendorTable())->where('id', $id)->find();
        if (empty($row) || (int)$row['status'] !== 0) {
            $this->error('记录不可审核');
        }
        Db::name(FleaMarketService::vendorTable())->where('id', $id)->update([
            'status' => 2,
            'audit_time' => time(),
        ]);
        $highlight = Db::name(FleaMarketService::highlightTable())->where('vendor_id', $id)->find();
        if ($highlight && (float)$highlight['point_spent'] > 0 && (int)$highlight['status'] !== 2) {
            AccountLogModel::add_account_log(
                (int)$row['user_id'],
                2,
                210,
                (float)$highlight['point_spent'],
                ['remark' => '二手集市宣传驳回退回积分', 'vendor_id' => $id]
            );
            Db::name(FleaMarketService::highlightTable())->where('vendor_id', $id)->update(['status' => 2]);
        }
        $this->success('已拒绝');
    }
}
