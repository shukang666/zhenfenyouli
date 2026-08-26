<?php

namespace app\admin\model\active;

class ActiveUserModel extends \think\Model
{
  protected $name  = 'active_user';


    public function get_type()
    {
        return  [
            '1' => '普通活动',
            '2' => '秒杀活动',
            '3' => '拼团活动',
        ];
    }
}