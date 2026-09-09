<?php

namespace app\admin\controller;

use app\admin\model\active\ActiveCateModel;
use app\admin\model\active\ActiveModel;
use think\App;

class ActiveController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new ActiveModel();
    }


    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');
        $type = $this->request->param('type', '', 'trim');

        $where = [];
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        $where[] = ['type', '=', $type];

        $this->assign('cate_list', (new  ActiveCateModel())->select());
        return $where;
    }

    public function get_params()
    {
        $this->assign('cate_list', (new  ActiveCateModel())->select());
    }

    public function index1()
    {
        return redirect((string)url('index', ['type' => 1]));
    }

    public function index2()
    {
        return redirect((string)url('index', ['type' => 2]));
    }

    public function index3()
    {
        return redirect((string)url('index', ['type' => 3]));
    }


    public function setData($item, $type = 1)
    {
        if ($type == 1) {
            if (!empty($item['date'])) $item['date'] = strtotime($item['date']);
        } else {
            if (!empty($item['date'])) $item['date'] = date('Y-m-d', $item['date']);
        }

        return $item;
    }
}