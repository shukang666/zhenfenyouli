<?php

namespace app\admin\controller;

use app\admin\model\forum\ForumCateModel;
use think\App;

class ForumCateController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ForumCateModel();
    }

    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');
        $status = $this->request->param('status', '', 'trim');
        $start_time = $this->request->param('start_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');

        $where = [];
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
//        if (!empty($status)) {
//            if ($status == 10) {
//                $where[] = ['status', 'in', [10, 16]];
//            } elseif ($status == 2) {
//                $where[] = ['status', 'in', [2, 14]];
//            } else {
//
//            }
//        }
//        if ($start_time) $where[] = ['create_time', '>=', strtotime($start_time)];
//        if ($end_time) $where[] = ['create_time', '<=', strtotime($end_time) + 86399];
        $where[] = ['pid', '=', 2];
        $this->assign('status', $status);
        return $where;
    }




}