<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\model\goods;

use api\wxapp\model\CommonModel;
use api\wxapp\model\OrderCommentModel;
use app\admin\model\active\ActiveClockModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\shop\ShopModel;
use app\admin\model\ToolModel;
use think\Model;

class GoodsModel extends ToolModel
{
    protected $pk = 'id';
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'goods';

    public function getInfo($map, $page_size = 10, $id = 0, $field = 'a.*', $list_order = 'a.id desc', $is_wxapp = 0)
    {

        $query = $this->alias('a')
            ->field($field . ',b.cate_name first_name,c.cate_name second_name')
            ->leftjoin('goods_category b', 'a.first_cate = b.id')
            ->leftjoin('goods_category c', 'a.second_cate = c.id')
//            ->leftjoin('shop d', 'a.user_id = d.user_id')
            ->where($map)->order($list_order);

        $list = $page_size == -1 || !empty($id) ? $query->select() : $query->paginate($page_size);
        $CommentModel = new OrderCommentModel();
        $ShopModel = new ShopModel();
        foreach ($list as &$item) {
            if (!empty($item['thumb'])) $item['thumb'] = cmf_get_asset_url($item['thumb']);
            $item['image'] = CommonModel::getArrImageFullUrl($item['image']);

            if ($is_wxapp) {
                //前端
                $item['photos'] = CommonModel::getArrImageFullUrl($item['photos']);
                if (!empty($item['content'])) $item['content'] = cmf_replace_content_file_url(htmlspecialchars_decode($item['content']));
                if (!empty($item['is_attribute'])) {
                    $item['sku'] = self::getSku($item['id']);
                } else {
                    $item['sku'] = [];
                }
            } else {
                //后台
                $item['show_photos'] = implode(',', CommonModel::getArrImageFullUrl($item['photos']));
                $item['photos'] = !empty($item['photos']) ? explode(',', $item['photos']) : '';
//                $item['image'] = !empty($item['image']) ? explode(',', $item['image']) : '';
                if (!empty($item['content'])) $item['content'] = htmlspecialchars_decode($item['content']);
            }


            if ($item['user_id'] > 0) {
                //商家评分
                $shop_info = $ShopModel
                    ->where(['user_id' => $item['user_id']])
                    ->field('id,shop_name,mobile,name,avatar,address,lng,lat')->find();
                $shop_info['point'] = $CommentModel->where(['shop_id' => $item['user_id']])->avg('point');
                $item['shop_info'] = $shop_info;
            } else {
                $point = $CommentModel->where(['shop_id' => 0])->avg('point');
                $item['shop_info'] = [
                    'id' => -1,
                    'shop_name' => '平台',
                    'avatar' => cmf_get_asset_url(cmf_get_option('set_config')['app_logo']),
                    'point' => $point,
                ];

            }

        }
        return $id ? $list[0] ?? [] : $list;
    }

    public static function getListField(): string
    {
        return 'a.id,a.thumb,a.goods_name,a.price,a.first_cate,a.second_cate,a.sell_count,
        a.stock,a.is_attribute,original_price,a.desc,a.point';
    }

    public static function getSku($goods_id, $user_id = 0): array
    {
        //获取sku
        $map = [];
        $map[] = ['goods_id', '=', $goods_id];
        $map[] = ['status', '=', 1];
        $SkuModel = new GoodsSkuModel();
        $goods_sku = $SkuModel
            ->field('id sku_id,goods_id,price,stock,sell_count,thumb')
            ->where($map)->select();

        if (!$goods_sku->isEmpty()) {
            //取sku
            $SkuAttrModel = new GoodsSkuAttrModel();
            $AttrModel = new GoodsAttrModel();
            $AttrValueModel = new GoodsAttrValueModel();
            $CartModel = new CartModel();

            $map = [];
            $map[] = ['goods_id', '=', $goods_id];
            $map[] = ['status', '=', 1];
            $attr_list = $AttrModel->field('id,attr_name name')->where($map)->order('list_order')->select();

            foreach ($attr_list as &$attr_item) {
                $map = [];
                $map[] = ['goods_id', '=', $goods_id];
                $map[] = ['status', '=', 1];
                $map[] = ['attr_id', '=', $attr_item['id']];
                $attr_item['item'] = $AttrValueModel->where($map)->order('list_order')->column('attr_value_name');
            }
            foreach ($goods_sku as &$item) {
                $sku_value_arr = $SkuAttrModel->getSkuNameValue($item['sku_id']);
                $item['sku_name'] = $sku_value_arr['sku_name'];
                $item['sku_name_value'] = $sku_value_arr['sku_name_value'];
            }
            $data['sku_list'] = $goods_sku;
            $data['attr_list'] = $attr_list;
        } else {
            $data['sku_list'] = [];
            $data['attr_list'] = [];
        }
        return $data;
    }

    /** 活动是否可作为商品「前置签到/打卡」条件（线下签到 或 打卡兑礼） */
    public static function activeHasSignConfigured($active): bool
    {
        if (empty($active)) {
            return false;
        }
        if ((int)($active['join_type'] ?? 0) === 2) {
            return false;
        }
        if ((int)($active['is_clock_challenge'] ?? 0) === 1 && (int)($active['is_clock'] ?? 0) === 1) {
            return (int)($active['clock_challenge_days'] ?? 0) >= 1;
        }
        if (!ActiveModel::isOfflineEvent($active)) {
            return false;
        }
        $start = (int)($active['offline_sign_start'] ?? 0);
        $end = (int)($active['offline_sign_end'] ?? 0);
        return $start > 0 && $end > 0 && $end > $start;
    }

    /** 查询条件：可绑定为商品前置条件的活动 */
    protected static function applySignConfiguredWhere($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($q2) {
                $q2->where('join_type', '<>', 2)
                    ->where('event_kind', '=', ActiveModel::EVENT_KIND_OFFLINE)
                    ->where('offline_sign_start', '>', 0)
                    ->where('offline_sign_end', '>', 0)
                    ->whereRaw('offline_sign_end > offline_sign_start');
            })->whereOr(function ($q3) {
                $q3->where('is_clock_challenge', '=', 1)
                    ->where('is_clock', '=', 1)
                    ->where('clock_challenge_days', '>', 0);
            });
        });
    }

    /** 后台下拉：已配置签到的活动 */
    public static function getSignActiveListForAdmin(int $ensureIncludeActiveId = 0)
    {
        $query = ActiveModel::field('id,name,join_type,event_kind,offline_sign_start,offline_sign_end,is_clock,is_clock_challenge,clock_challenge_days')
            ->where('delete_time', 0)
            ->where('status', 1);
        self::applySignConfiguredWhere($query);
        $list = $query->order('id desc')->select();

        $ensureIncludeActiveId = (int)$ensureIncludeActiveId;
        if ($ensureIncludeActiveId > 0) {
            $exists = false;
            foreach ($list as $row) {
                if ((int)$row['id'] === $ensureIncludeActiveId) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $extra = ActiveModel::field('id,name,join_type,event_kind,offline_sign_start,offline_sign_end,is_clock,is_clock_challenge,clock_challenge_days')
                    ->where('id', $ensureIncludeActiveId)
                    ->where('delete_time', 0)
                    ->find();
                if ($extra) {
                    $list->unshift($extra);
                }
            }
        }

        return $list;
    }

    public static function normalizeRequireActiveId($activeId): int
    {
        return max(0, (int)$activeId);
    }

    /** 是否为有效的签到活动前置条件 */
    public static function isValidSignRequireActiveId(int $activeId): bool
    {
        if ($activeId <= 0) {
            return true;
        }
        $active = ActiveModel::where('id', $activeId)->where('delete_time', 0)->find();
        return self::activeHasSignConfigured($active);
    }

    /** 用户在本活动的累计打卡天数（按自然日去重） */
    public static function countUserClockDays(int $userId, int $activeId): int
    {
        if ($userId <= 0 || $activeId <= 0) {
            return 0;
        }
        $timestamps = ActiveClockModel::where('user_id', $userId)
            ->where('active_id', $activeId)
            ->column('date_time');
        if (empty($timestamps)) {
            return 0;
        }
        $days = [];
        foreach ($timestamps as $ts) {
            $days[date('Y-m-d', (int)$ts)] = true;
        }
        return count($days);
    }

    /** 用户是否满足商品绑定的活动前置条件 */
    public static function userMetSignRequire(int $userId, $goodsInfo): bool
    {
        $activeId = (int)($goodsInfo['require_active_id'] ?? 0);
        if ($activeId <= 0) {
            return true;
        }
        if ($userId <= 0) {
            return false;
        }
        $active = ActiveModel::where('id', $activeId)->where('delete_time', 0)->find();
        if (empty($active)) {
            return false;
        }
        if ((int)($active['is_clock_challenge'] ?? 0) === 1) {
            $need = max(1, (int)($active['clock_challenge_days'] ?? 0));
            return self::countUserClockDays($userId, $activeId) >= $need;
        }
        return ActiveClockModel::where('user_id', $userId)
                ->where('active_id', $activeId)
                ->count() > 0;
    }

    /** 前置条件进度（小程序展示） */
    public static function getSignRequireProgress(int $userId, int $activeId): array
    {
        $active = ActiveModel::where('id', $activeId)->where('delete_time', 0)->find();
        if (empty($active)) {
            return [
                'require_mode' => 'none',
                'require_clock_days' => 0,
                'user_clock_days' => 0,
            ];
        }
        if ((int)($active['is_clock_challenge'] ?? 0) === 1) {
            $need = max(1, (int)($active['clock_challenge_days'] ?? 0));
            $cur = self::countUserClockDays($userId, $activeId);
            return [
                'require_mode' => 'clock_days',
                'require_clock_days' => $need,
                'user_clock_days' => $cur,
            ];
        }
        return [
            'require_mode' => 'sign_once',
            'require_clock_days' => 1,
            'user_clock_days' => ActiveClockModel::where('user_id', $userId)->where('active_id', $activeId)->count() > 0 ? 1 : 0,
        ];
    }

    /** 绑定某活动、可兑换的礼品列表（小程序活动页） */
    public static function listRedeemGoodsForActive(int $activeId): array
    {
        if ($activeId <= 0) {
            return [];
        }
        $list = self::where('require_active_id', $activeId)
            ->where('delete_time', 0)
            ->where('status', 1)
            ->field('id,goods_name,thumb,point,price,stock,sell_count')
            ->order('list_order asc,id desc')
            ->select();
        $out = [];
        foreach ($list as $row) {
            $item = is_array($row) ? $row : $row->toArray();
            $thumb = $item['thumb'] ?? '';
            $item['thumb'] = $thumb ? cmf_get_asset_url($thumb) : '';
            $item['point'] = (int)($item['point'] ?? 0);
            $item['price'] = $item['price'] ?? 0;
            $out[] = $item;
        }
        return $out;
    }

    public static function getRequireActiveName(int $activeId): string
    {
        if ($activeId <= 0) {
            return '';
        }
        $name = ActiveModel::where('id', $activeId)->value('name');
        return $name ? (string)$name : '';
    }

    public static function signRequireErrorMessage($goodsInfo): string
    {
        $activeId = (int)($goodsInfo['require_active_id'] ?? 0);
        if ($activeId <= 0) {
            return '';
        }
        $activeName = self::getRequireActiveName($activeId) ?: '指定活动';
        $goodsName = $goodsInfo['goods_name'] ?? '该商品';
        $active = ActiveModel::where('id', $activeId)->where('delete_time', 0)->find();
        if (!empty($active) && (int)($active['is_clock_challenge'] ?? 0) === 1) {
            $need = max(1, (int)($active['clock_challenge_days'] ?? 0));
            return '请先在活动「' . $activeName . '」累计打卡满 ' . $need . ' 天后再兑换（' . $goodsName . '）';
        }
        return '请先完成活动「' . $activeName . '」的签到后再兑换（' . $goodsName . '）';
    }

    /** 小程序商品详情：附加前置签到展示字段 */
    public static function appendSignRequireForWxapp(array $data, int $userId = 0): array
    {
        $activeId = (int)($data['require_active_id'] ?? 0);
        $data['require_active_id'] = $activeId;
        $data['require_active_name'] = '';
        $data['require_sign_met'] = 1;
        $data['require_sign_tip'] = '';
        if ($activeId <= 0) {
            return $data;
        }
        $data['require_active_name'] = self::getRequireActiveName($activeId);
        $progress = self::getSignRequireProgress($userId, $activeId);
        $data['require_mode'] = $progress['require_mode'];
        $data['require_clock_days'] = (int)$progress['require_clock_days'];
        $data['user_clock_days'] = (int)$progress['user_clock_days'];
        $met = self::userMetSignRequire($userId, $data);
        $data['require_sign_met'] = $met ? 1 : 0;
        if (!$met) {
            $name = $data['require_active_name'] ?: '指定活动';
            if ($progress['require_mode'] === 'clock_days') {
                $data['require_sign_tip'] = '请先在活动「' . $name . '」累计打卡 '
                    . $progress['user_clock_days'] . '/' . $progress['require_clock_days'] . ' 天后再兑换';
            } else {
                $data['require_sign_tip'] = '请先完成活动「' . $name . '」的签到后再兑换';
            }
        }
        return $data;
    }

}
