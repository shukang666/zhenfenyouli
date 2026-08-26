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

use api\wxapp\model\CommonModel;
use think\Model;

class OrderCommentModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'shop_order_comment';

    public function getList($map,$page_size=10){
        $OrderCommentModel = new OrderCommentModel();
        $list = $OrderCommentModel->alias('a')
            ->field('a.*,b.nickname,b.avatar,c.goods_name,b.mobile')
            ->leftjoin('member b','a.user_id = b.id')
            ->leftjoin('goods c','a.goods_id = c.id')
            ->where($map)->order('a.id desc')->paginate($page_size);

        foreach($list as &$item){
            $item['photos'] = CommonModel::getArrImageFullUrl($item['photos']);
            $item['avatar'] = get_true_asset_url($item['avatar']);
        }

        return $list;

    }

}
