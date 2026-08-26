<?php

namespace app\admin\controller;

use app\admin\model\member\MemberLevelModel;
use think\App;

class MemberLevelController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MemberLevelModel();
    }

}