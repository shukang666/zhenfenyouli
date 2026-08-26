<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace api\wxapp\model;

use think\Model;

class GoodsColModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'shop_goods_col';

    // 关闭自动写入update_time字段
    protected $updateTime = false;

    /**
     * 根据条件修改
     * @param array $where
     * @param array $data
     * @return id|false
     */
    public function updateByWhere($where,$data)
    {
        $map = is_array($where) ? $where :  ['id'=>$where];
        $result = $this->where($map)->update($data);
        return (bool)$result;
    }

    public function getInfo($map,$page_size=10,$id=0,$field='a.*',$list_order='a.id desc')
    {
        $query = $this->alias('a')
            ->field('b.*')
            ->join('shop_goods b','a.goods_id = b.id')
            ->where($map)->order($list_order);
        $list = $page_size == -1 ?  $query->select() : $query->paginate($page_size);
        $list = getFullData($list);
        return $id ? $list[0] ?? [] : $list;
    }



}
