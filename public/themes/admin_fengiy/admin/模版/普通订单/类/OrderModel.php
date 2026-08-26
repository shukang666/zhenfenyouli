<?php

namespace themes\admin_fengiy\admin\模版\普通订单\类;

use think\Log;

class OrderModel extends \think\Model
{
    protected $name = 'order';

    //1：待支付 2待接单3待取车 4待洗车 5洗车中 6待还车7待验收 8已完成'
    public function get_status()
    {
        //1：待支付 2待分配，3待接单4待取车 5待洗车 6洗车中 7待还车8待验收9已完成 10取消 ，
        return [
            1 => '待支付',
            2 => '待接单',
            3 => '待取车',
            4 => '待洗车',
            5 => '洗车中',
            6 => '待还车',
            7 => '待验收',
            8 => '已完成',
            9 => '已评价',
            10 => '已取消',


        ];
    }

    public function getStatusMenu(): array
    {
        //每个订单状态的数量
        $new_status_menu = [];
        foreach ($this->get_status() as $status => $status_name) {
            $new_status_menu[$status]['status'] = $status;
            $new_status_menu[$status]['status_name'] = $status_name;
            $new_status_menu[$status]['status_count'] = $this->where('status', $status)->count();
        }


        return $new_status_menu;

    }

}