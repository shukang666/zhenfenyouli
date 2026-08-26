<?php

namespace app\admin\model\forum;

use app\admin\model\ToolModel;
use think\db\Query;
use tree\Tree;

class ForumModel extends ToolModel
{
    protected $name = 'forum';//


    public function get_status()
    {
        return [1=>'待处理',2=>'已处理',3=>''];
    }
}