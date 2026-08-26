<?php

namespace app\admin\controller;

use app\admin\model\member\CommunityCateModel;
use app\admin\model\member\CommunityModel;
use think\App;

class CommunityCateController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new CommunityCateModel();
    }


    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');
        $data_id = $this->request->param('data_id', '', 'trim');
        $where[] = ['cate_id', '=', $data_id];
        return $where;
    }

}