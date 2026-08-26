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
namespace api\wxapp\model;

use api\wxapp\model\CommonModel;
use think\Model;

class GoodsModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'shop_goods';

    public function getInfo($map,$page_size=10,$field='a.*',$list_order='a.list_order,a.id desc',$id=0,$is_wxapp=0){
        $list = $this->alias('a')
            ->field($field.',b.cate_name first_cate_name,c.cate_name second_cate_name,d.shop_name')
            ->leftjoin('shop_category b','a.first_cate = b.id')
            ->leftjoin('shop_category c','a.second_cate = c.id')
            ->leftjoin('shop d','a.shop_id = d.id')
            ->where($map)->order($list_order)->paginate($page_size);
        $SkuModel = new GoodsSkuModel();

        $list = getFullData($list);
        foreach($list as &$item){

            //获取sku
            $map = [];
            $map[] = ['goods_id', '=', $item['id']];
            $map[] = ['status', '=', 1];
            $map[] = ['stock', '>', 0];

            $goods_sku = $SkuModel->field('id sku_id,sku_name,price,stock,sell_count,thumb')->where($map)->select()->toArray();
            $item['sku'] = getFullData($goods_sku);

            if (!empty($goods_sku)) {
                $item['price'] = $SkuModel->where($map)->min('price');
            }

            if($is_wxapp){
                $item['sell_count'] = $item['show_sell_count'] + $item['sell_count'];
                unset($item['show_sell_count']);
            }

        }
        return $id ? $list[0] ?? [] : $list;
    }

    public static function getListField(): string
    {
        return 'a.id,a.thumb,a.goods_name,a.price,a.first_cate,a.second_cate,a.sell_count,a.show_sell_count,a.stock';
    }

}
