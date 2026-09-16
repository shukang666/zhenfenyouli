<?php

namespace app\admin\model\member;


use app\admin\model\ToolModel;

class CheckModel extends ToolModel
{
    protected $name = 'check';

    // cmf_check 表无 delete_time 字段，禁用从 ToolModel 继承的软删除
    protected $deleteTime = false;

    public function get_status()
    {
        return [
            1 => '待审核',
            2 => '审核通过',
            3 => '审核不通过',
        ];
    }

    public function get_type()
    {
        return [
            1 => '小区点位巡检',
            2 => '桶边督导',
            3 => '宣传活动',
        ];
    }

}