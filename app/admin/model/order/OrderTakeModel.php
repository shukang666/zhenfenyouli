<?php

namespace app\admin\model\order;

use api\wxapp\model\CommonModel;
use app\admin\model\member\MemberModel;

class OrderTakeModel extends \app\admin\model\ToolModel
{

    protected $name = 'shop_order_take';


    public static function add_data($user_id, $order_num)
    {
        $user_info = MemberModel::find($user_id);
       return OrderTakeModel::insert([
            'openid' => $user_info['openid'],
            'order_num' => $order_num,
            'create_time' => time()
        ]);
    }


}