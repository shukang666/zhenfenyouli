<?php

namespace app\admin\controller;

use app\admin\model\member\CommunityParentModel;
use think\App;

class CommunityParentController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new CommunityParentModel();
    }


    public function setWhere()
    {
        $where = [];
        $name = $this->request->param('name', '', 'trim');
        $where[] = ['name', 'like', '%' . $name . '%'];
        return $where;
    }

}