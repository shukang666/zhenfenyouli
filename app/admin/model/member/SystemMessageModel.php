<?php

namespace app\admin\model\member;


use app\admin\model\ToolModel;

class SystemMessageModel extends ToolModel
{
    protected $name = 'member_system_message';

    public function get_type()
    {
        return [1 => '系统通知', 2 => '活动通知'];
    }

}