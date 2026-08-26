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

class ShopMoneyLogModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'shop_money_log';

    public static function addLog($user_id,$order_num,$amount,$type=1,$remark=''){
        $ShopMoneyLogModel = new ShopMoneyLogModel();
        $ShopMoneyLogModel->insert([
            'user_id'=>$user_id,
            'order_num'=>$order_num,
            'amount'=>$amount,
            'remark'=>$remark,
            'create_time'=>time(),
            'type'=>$type,
        ]);
    }


}
