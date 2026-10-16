<?php

namespace app\admin\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\service\SecondhandService;
use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 社区闲置审核与意向交割
 */
class SecondhandItemController extends AdminBaseController
{
    protected function itemTable()
    {
        return Db::name(SecondhandService::TABLE_ITEM);
    }

    protected function intentTable()
    {
        return Db::name(SecondhandService::TABLE_INTENT);
    }

    public function index()
    {
        $params = $this->request->param();
        $status = $this->request->param('status', '');
        $keyword = trim((string)$this->request->param('keyword', ''));

        $where = [];
        if ($status !== '') {
            $where[] = ['i.status', '=', (int)$status];
        }
        if ($keyword !== '') {
            $where[] = ['i.title', 'like', '%' . $keyword . '%'];
        }

        $list = $this->itemTable()->alias('i')
            ->join('member m', 'm.id = i.user_id', 'LEFT')
            ->field('i.*,m.nickname,m.mobile')
            ->where($where)
            ->order('i.id desc')
            ->paginate(15);

        foreach ($list as &$vo) {
            $imgs = SecondhandService::decodeImages($vo['images']);
            $vo['cover'] = !empty($imgs[0]) ? cmf_get_asset_url($imgs[0]) : '';
            $vo['status_name'] = SecondhandService::statusLabel((int)$vo['status']);
        }

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('status', $status);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    public function pass()
    {
        $id = (int)$this->request->param('id');
        $row = $this->itemTable()->where('id', $id)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        if ((int)$row['status'] !== SecondhandService::STATUS_PENDING) {
            $this->error('当前状态不可审核通过');
        }
        if (empty($row['contact']) || ((float)($row['price'] ?? 0) <= 0 && (int)($row['point_price'] ?? 0) > 0)) {
            $this->error('该旧发布缺少人民币售价或联系方式，请联系用户重新发布');
        }
        $this->itemTable()->where('id', $id)->update([
            'status' => SecondhandService::STATUS_ON_SALE,
            'audit_time' => time(),
            'update_time' => time(),
            'reject_reason' => '',
        ]);
        $this->success('已通过，已在小程序展示');
    }

    public function reject()
    {
        $id = (int)$this->request->param('id');
        $reason = trim((string)$this->request->param('reason', '不符合发布规范'));
        $row = $this->itemTable()->where('id', $id)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        if ((int)$row['status'] !== SecondhandService::STATUS_PENDING) {
            $this->error('当前状态不可拒绝');
        }
        $this->itemTable()->where('id', $id)->update([
            'status' => SecondhandService::STATUS_REJECTED,
            'reject_reason' => $reason,
            'audit_time' => time(),
            'update_time' => time(),
        ]);
        $this->success('已拒绝');
    }

    public function intent_index()
    {
        $params = $this->request->param();
        $status = $this->request->param('intent_status', '');
        $where = [];
        if ($status !== '') {
            $where[] = ['t.status', '=', (int)$status];
        }

        $list = $this->intentTable()->alias('t')
            ->join('secondhand_item i', 'i.id = t.item_id', 'LEFT')
            ->join('member buyer', 'buyer.id = t.buyer_user_id', 'LEFT')
            ->join('member seller', 'seller.id = t.seller_user_id', 'LEFT')
            ->field('t.*,i.title as item_title,buyer.nickname as buyer_nickname,buyer.mobile as buyer_mobile,seller.nickname as seller_nickname')
            ->where($where)
            ->order('t.id desc')
            ->paginate(15);

        $statusMap = [
            SecondhandService::INTENT_REGISTERED => '已登记',
            SecondhandService::INTENT_WAIT_HANDOVER => '待现场交割',
            SecondhandService::INTENT_DONE => '已完成',
            SecondhandService::INTENT_CANCEL => '已取消',
        ];
        foreach ($list as &$vo) {
            $vo['status_name'] = $statusMap[(int)$vo['status']] ?? '';
        }

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('intent_status', $status);
        return $this->fetch('intent_index');
    }

    /** 现场交割完成：扣买家积分、加卖家积分 */
    public function complete_intent()
    {
        $this->error('社区闲置已改为买卖双方自行联系，旧积分意向不再办理');
        $id = (int)$this->request->param('id');
        $intent = $this->intentTable()->where('id', $id)->find();
        if (empty($intent)) {
            $this->error('意向不存在');
        }
        if (!in_array((int)$intent['status'], [SecondhandService::INTENT_REGISTERED, SecondhandService::INTENT_WAIT_HANDOVER], true)) {
            $this->error('该意向不可完成');
        }

        $item = $this->itemTable()->where('id', (int)$intent['item_id'])->find();
        if (empty($item)) {
            $this->error('物品不存在');
        }

        $price = (int)$intent['point_price'];
        $buyerId = (int)$intent['buyer_user_id'];
        $sellerId = (int)$intent['seller_user_id'];

        Db::startTrans();
        try {
            $param = ['secondhand_intent_id' => $id, 'item_id' => (int)$item['id']];
            $deduct = AccountLogModel::add_account_log($buyerId, 2, 229, -$price, $param);
            if (!$deduct) {
                throw new \RuntimeException('买家积分不足或扣减失败');
            }
            AccountLogModel::add_account_log($sellerId, 2, 230, $price, $param);

            $this->intentTable()->where('id', $id)->update([
                'status' => SecondhandService::INTENT_DONE,
                'update_time' => time(),
                'complete_time' => time(),
            ]);
            SecondhandService::cancelActiveIntentsForItem((int)$item['id'], $id);
            $this->itemTable()->where('id', (int)$item['id'])->update([
                'status' => SecondhandService::STATUS_CLOSED,
                'update_time' => time(),
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error($e->getMessage() ?: '操作失败');
        }
        $this->success('交割完成，积分已划转');
    }

    public function cancel_intent()
    {
        $id = (int)$this->request->param('id');
        $intent = $this->intentTable()->where('id', $id)->find();
        if (empty($intent)) {
            $this->error('意向不存在');
        }
        if (!in_array((int)$intent['status'], [SecondhandService::INTENT_REGISTERED, SecondhandService::INTENT_WAIT_HANDOVER], true)) {
            $this->error('该意向不可取消');
        }

        Db::startTrans();
        try {
            $this->intentTable()->where('id', $id)->update([
                'status' => SecondhandService::INTENT_CANCEL,
                'update_time' => time(),
            ]);
            $itemId = (int)$intent['item_id'];
            $other = $this->intentTable()
                ->where('item_id', $itemId)
                ->whereIn('status', [SecondhandService::INTENT_REGISTERED, SecondhandService::INTENT_WAIT_HANDOVER])
                ->count();
            if ($other === 0) {
                $item = $this->itemTable()->where('id', $itemId)->find();
                if ($item && (int)$item['status'] === SecondhandService::STATUS_RESERVED) {
                    $this->itemTable()->where('id', $itemId)->update([
                        'status' => SecondhandService::STATUS_ON_SALE,
                        'update_time' => time(),
                    ]);
                }
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error('操作失败');
        }
        $this->success('已取消意向');
    }
}
