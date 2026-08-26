<?php

namespace app\admin\model;

use think\model\concern\SoftDelete;

class ToolModel extends \think\Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

}