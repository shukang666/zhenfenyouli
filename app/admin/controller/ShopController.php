<?php

namespace app\admin\controller;

use think\App;

class ShopController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\shop\ShopModel();
    }


    public function setIndex()
    {
        $list = $this->model
            ->alias('a')
            ->join('member b', 'a.user_id = b.id')
            ->field('a.*,b.nickname')
            ->where($this->setWhere())->order('id desc')->paginate(15);

        foreach ($list as $key => $value) {
            $list[$key]['status_name'] = $this->model->get_status()[$value['status']];

        }
        return $list;
    }

}