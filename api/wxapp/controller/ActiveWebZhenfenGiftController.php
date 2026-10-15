<?php

namespace api\wxapp\controller;

use api\wxapp\model\AddressModel;
use api\wxapp\service\ActiveWebZhenfenGiftService;

/**
 * Web 式活动：三次真分赠品兑换
 */
class ActiveWebZhenfenGiftController extends AuthController
{
    public function status()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            $active = ActiveWebZhenfenGiftService::loadWebGiftActive($activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }

        $elig = ActiveWebZhenfenGiftService::evaluateEligibility((int)$this->user_id);
        $claim = ActiveWebZhenfenGiftService::findClaim((int)$this->user_id, $activeId);

        $claimedGoods = null;
        if (!empty($claim)) {
            foreach (ActiveWebZhenfenGiftService::giftGoodsList() as $g) {
                if ((int)$g['id'] === (int)$claim['goods_id']) {
                    $claimedGoods = $g;
                    break;
                }
            }
        }

        $this->success('请求成功', [
            'active_id' => $activeId,
            'active_name' => (string)$active['name'],
            'goods' => ActiveWebZhenfenGiftService::giftGoodsList(),
            'eligibility' => $elig,
            'has_claimed' => empty($claim) ? 0 : 1,
            'claim' => empty($claim) ? null : [
                'goods_id' => (int)$claim['goods_id'],
                'order_num' => (string)$claim['order_num'],
                'create_time' => (int)$claim['create_time'],
                'goods' => $claimedGoods,
            ],
        ]);
    }

    public function claim()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $goodsId = (int)$this->request->param('goods_id');
        $addressId = (int)$this->request->param('address_id');

        try {
            ActiveWebZhenfenGiftService::loadWebGiftActive($activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }

        if (!ActiveWebZhenfenGiftService::isAllowedGoodsId($goodsId)) {
            $this->error('请选择活动指定赠品');
        }
        if ($addressId <= 0) {
            $this->error('请选择收货地址');
        }

        if (ActiveWebZhenfenGiftService::findClaim((int)$this->user_id, $activeId)) {
            $this->error('您已兑换过，每人限领一次');
        }

        $elig = ActiveWebZhenfenGiftService::evaluateEligibility((int)$this->user_id);
        if (empty($elig['eligible'])) {
            $this->error($elig['reason'] ?: '暂不符合兑换条件');
        }

        $address = AddressModel::where('id', $addressId)->where('user_id', $this->user_id)->where('status', 1)->find();
        if (empty($address)) {
            $this->error('收货地址无效');
        }

        try {
            $orderNum = ActiveWebZhenfenGiftService::createFreeGiftOrder(
                (int)$this->user_id,
                $activeId,
                $goodsId,
                $address->toArray()
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage() ?: '兑换失败');
        }

        $this->success('兑换成功，可在「我的-兑换记录」查看订单', [
            'order_num' => $orderNum,
            'point' => 0,
        ]);
    }
}
