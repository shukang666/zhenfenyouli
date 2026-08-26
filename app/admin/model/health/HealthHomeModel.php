<?php

namespace app\admin\model\health;

class HealthHomeModel extends \think\Model
{
  protected $name  = 'health_home';


    public function get_type()
    {
        return  [
            '1' => '普通活动',
            '2' => '秒杀活动',
            '3' => '拼团活动',
        ];
    }
}