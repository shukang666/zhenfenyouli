<?php


namespace app\admin\model\goods;

use think\Model;

class GoodsSkuAttrModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'goods_sku_attr';

    public static function getSkuNameValue($sku_id): array
    {
        $map = [];
        $map[] = ['a.sku_id', '=', $sku_id];
        $map[] = ['a.status', '=', 1];

        $SkuAttrModel = new GoodsSkuAttrModel();
        $sku_attr_list = $SkuAttrModel->alias('a')
            ->field('b.attr_name,c.attr_value_name')
            ->leftjoin('goods_attr b', 'a.attr_id=b.id')
            ->leftjoin('goods_attr_value c', 'a.attr_value_id=c.id')
            ->where($map)->select();

        $sku_arr = [];
        $sku_value_arr = [];
        foreach ($sku_attr_list as $value) {
            $sku_value_arr[] = $value['attr_value_name'];
            $sku_arr[] = $value['attr_name'] . ':' . $value['attr_value_name'];
        }
        $data['sku_name'] = implode(';',$sku_value_arr);
        $data['sku_name_value'] = implode(';',$sku_arr);
        return $data;
    }

}