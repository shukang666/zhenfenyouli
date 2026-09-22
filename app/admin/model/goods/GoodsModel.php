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

    /** 后台签到列表（join_type=2）供商品前置条件下拉 */
    public static function getSignActiveListForAdmin()
    {
        return ActiveModel::field('id,name')
            ->where('delete_time', 0)
            ->where('join_type', 2)
            ->order('id desc')
            ->select();
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
        return ActiveModel::where('id', $activeId)
                ->where('delete_time', 0)
                ->where('join_type', 2)
                ->count() > 0;
    }

    /** 用户是否已在该签到活动完成至少一次签到（active_clock） */
    public static function userMetSignRequire(int $userId, $goodsInfo): bool
    {
        $activeId = (int)($goodsInfo['require_active_id'] ?? 0);
        if ($activeId <= 0) {
            return true;
        }
        if ($userId <= 0) {
            return false;
        }
        return ActiveClockModel::where('user_id', $userId)
                ->where('active_id', $activeId)
                ->count() > 0;
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
        $activeName = self::getRequireActiveName($activeId) ?: '指定签到活动';
        $goodsName = $goodsInfo['goods_name'] ?? '该商品';
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
        $met = self::userMetSignRequire($userId, $data);
        $data['require_sign_met'] = $met ? 1 : 0;
        if (!$met) {
            $name = $data['require_active_name'] ?: '指定签到活动';
            $data['require_sign_tip'] = '请先完成活动「' . $name . '」的签到后再兑换';
        }
        return $data;
    }


}
