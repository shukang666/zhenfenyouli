<?php

namespace api\wxapp\service;

use api\wxapp\model\CommonModel;
use api\wxapp\model\ShopOrderDetailModel;
use api\wxapp\model\ShopOrderModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\forum\ForumModel;
use app\admin\model\goods\GoodsModel;
use app\admin\model\goods\GoodsSellModel;
use think\facade\Db;

/**
 * Web 式活动：2026-10-15 起新用户完成 3 次真分（type=2）可 0 积分兑赠品二选一
 */
class ActiveWebZhenfenGiftService
{
    /** @var int[] 亚麻籽姜黄芝麻盐 / 南美绿檀木按摩梳 */
    const GIFT_GOODS_IDS = [95, 96];

    const REQUIRED_CLOCKS = 3;

    const CLOCK_START = '2026-10-15 00:00:00';

    public static function claimTable(): string
    {
        return 'active_web_zhenfen_gift_claim';
    }

    public static function clockStartTs(): int
    {
        return strtotime(self::CLOCK_START);
    }

    public static function loadWebGiftActive(int $activeId): array
    {
        if ($activeId <= 0) {
            throw new \InvalidArgumentException('缺少活动 ID');
        }
        $active = ActiveModel::where('id', $activeId)->where('delete_time', 0)->find();
        if (empty($active)) {
            throw new \InvalidArgumentException('活动不存在');
        }
        if (!ActiveModel::isWebDisplay($active)) {
            throw new \InvalidArgumentException('该活动未启用 Web 式页面');
        }
        return $active->toArray();
    }

    /**
     * @return array{eligible:int,reason:string,prior_clock_count:int,since_start_clock_count:int,required:int,start_date:string}
     */
    public static function evaluateEligibility(int $userId): array
    {
        $startTs = self::clockStartTs();
        $prior = (int)ForumModel::where('user_id', $userId)
            ->where('type', 2)
            ->where('create_time', '<', $startTs)
            ->count();

        $since = (int)ForumModel::where('user_id', $userId)
            ->where('type', 2)
            ->where('create_time', '>=', $startTs)
            ->count();

        $required = self::REQUIRED_CLOCKS;
        $reason = '';
        $eligible = 0;

        if ($prior > 0) {
            $reason = '您在活动开始前已有真分打卡记录，本活动仅限「首次参与真分」的新朋友';
        } elseif ($since < $required) {
            $left = $required - $since;
            $reason = '还需完成 ' . $left . ' 次真分打卡（自 2026-10-15 起累计，不要求连续）';
        } else {
            $eligible = 1;
            $reason = '已达成兑换条件，请任选一件赠品并填写收货地址';
        }

        return [
            'eligible' => $eligible,
            'reason' => $reason,
            'prior_clock_count' => $prior,
            'since_start_clock_count' => $since,
            'required' => $required,
            'start_date' => date('Y-m-d', $startTs),
        ];
    }

    public static function findClaim(int $userId, int $activeId): ?array
    {
        $row = Db::name(self::claimTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->find();
        return empty($row) ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function giftGoodsList(): array
    {
        $list = [];
        foreach (self::GIFT_GOODS_IDS as $gid) {
            $g = GoodsModel::where('id', $gid)->where('delete_time', 0)->where('status', 1)->find();
            if (empty($g)) {
                continue;
            }
            $row = $g->toArray();
            $list[] = [
                'id' => (int)$row['id'],
                'goods_name' => (string)$row['goods_name'],
                'point' => (float)$row['point'],
                'exchange_point' => 0,
                'stock' => (int)$row['stock'],
                'thumb' => cmf_get_asset_url($row['thumb']),
            ];
        }
        return $list;
    }

    public static function isAllowedGoodsId(int $goodsId): bool
    {
        return in_array($goodsId, self::GIFT_GOODS_IDS, true);
    }

    /**
     * 0 积分兑换单：写入 shop_order / detail，扣库存，待发货
     *
     * @throws \Throwable
     */
    public static function createFreeGiftOrder(int $userId, int $activeId, int $goodsId, array $addressInfo): string
    {
        $goods = GoodsModel::where('id', $goodsId)->where('delete_time', 0)->where('status', 1)->find();
        if (empty($goods)) {
            throw new \InvalidArgumentException('商品不存在或已下架');
        }
        if ((int)$goods['stock'] < 1) {
            throw new \InvalidArgumentException('库存不足');
        }

        $shopId = (int)($goods['shop_id'] ?? 0);
        if ($shopId <= 0) {
            $shopId = (int)($goods['user_id'] ?? 0);
        }

        $payOrderNum = CommonModel::createOrderNumber();
        $orderNum = CommonModel::createOrderNumber();
        $now = time();
        $onePrice = round((float)$goods['price'], 2);
        $remark = '三次真分活动免费兑换（活动ID:' . $activeId . '）';

        $orderRow = [
            'user_id' => $userId,
            'order_num' => $orderNum,
            'goods_price' => $onePrice,
            'status' => 2,
            'create_time' => $now,
            'pay_time' => $now,
            'need_pay_time' => $now + 86400,
            'remark' => $remark,
            'pay_type' => 3,
            'username' => $addressInfo['username'] ?? '',
            'phone' => $addressInfo['phone'] ?? '',
            'province' => $addressInfo['province'] ?? '',
            'city' => $addressInfo['city'] ?? '',
            'county' => $addressInfo['county'] ?? '',
            'address' => $addressInfo['address'] ?? '',
            'express_price' => 0,
            'pay_order_num' => $payOrderNum,
            'point' => 0,
            'point_pay' => 0,
            'point_amount' => 0,
            'shop_id' => $shopId,
            'delivery_type' => 1,
            'order_type' => 1,
            'goods_type' => 1,
            'position_id' => 0,
            'total_price' => $onePrice,
            'coupon_price' => 0,
            'coupon_id' => 0,
            'price' => 0,
        ];

        $detailRow = [
            'order_num' => $orderNum,
            'goods_id' => $goodsId,
            'goods_name' => $goods['goods_name'],
            'thumb' => $goods['thumb'],
            'user_id' => $userId,
            'count' => 1,
            'pay_order_num' => $payOrderNum,
            'sku_id' => 0,
            'sku_name' => '',
            'sku_value' => '',
            'sku_name_value' => '',
            'one_price' => $onePrice,
            'total_price' => $onePrice,
        ];

        Db::startTrans();
        try {
            Db::name('shop_order')->insert($orderRow);
            Db::name('shop_order_detail')->insert($detailRow);
            CommonModel::addOrderPay($orderNum, 1, 0, $userId);
            CommonModel::addOrderPay($payOrderNum, 2, 0, $userId);

            ShopOrderModel::setSellCountOrStock('stock_dec', $orderNum);

            $detail = ShopOrderDetailModel::where('order_num', $orderNum)->find();
            if (!empty($detail)) {
                GoodsModel::where('id', $goodsId)->inc('sell_count', 1)->update();
                GoodsModel::where('id', $goodsId)->inc('sell_amount', $onePrice)->update();
                GoodsSellModel::insert([
                    'goods_id' => $goodsId,
                    'order_num' => $orderNum,
                    'count' => 1,
                    'total_price' => $onePrice,
                ]);
            }

            Db::name(self::claimTable())->insert([
                'user_id' => $userId,
                'active_id' => $activeId,
                'goods_id' => $goodsId,
                'order_num' => $orderNum,
                'create_time' => $now,
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $orderNum;
    }
}
