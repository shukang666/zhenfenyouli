<?php

namespace app\admin\controller;

use app\admin\model\active\ActiveTabModel;
use think\App;

class ActiveTabController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new ActiveTabModel();
    }


    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');

        $where = [];
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        return $where;
    }


    //小程序活动页标签最多4个
    public function setAddPost($params)
    {
        $total = $this->model->count();
        if ($total >= 4) {
            $this->error('标签最多配置4个');
        }
        return parent::setAddPost($params);
    }
}
