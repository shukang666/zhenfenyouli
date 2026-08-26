<?php

namespace app\admin\controller;

use think\App;

class SystemMessageController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\member\SystemMessageModel();
    }

    public function outDate()
    {
        $this->assign('type_list', $this->model->get_type());
    }
}