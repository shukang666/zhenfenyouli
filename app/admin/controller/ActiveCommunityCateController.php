<?php

namespace app\admin\controller;

use app\admin\model\active\ActiveCateModel;
use app\admin\model\active\ActiveCommunityCateModel;
use app\admin\model\active\ActiveModel;
use think\App;
use think\db\exception\DbException;

class ActiveCommunityCateController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new ActiveCommunityCateModel();
    }



    public function setIndex()
    {

        return $this->model->where($this->setWhere())->order('list_order asc,id desc')->paginate(15);
    }

    /**
     * 列表
     * @return mixed
     * @throws DbException
     */
    public function index()
    {
        $params = $this->request->param();
        $list = $this->setIndex()->each(function ($item) {
            return $this->getList($item);
        });
//        $list = $this->getParamData($list);
        $this->assign('params', $params);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());

        return $this->fetch();
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

    //操作页面
    public function update()
    {
        $params = $this->request->param();

        $this->get_params();

        $this->assign('params', $params);
        $this->outParam();
        if (empty($params['id'])) {
            return $this->fetch();
        }
        $item = $this->setEdit($params);
        $item = $this->getParamData($item);
        $this->assign('item', $item);
        $badge_list = '';
        $this->assign('badge_list', $badge_list);

        return $this->fetch();
    }

    //操作功能
    public function updatePost()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();

            $params = $this->setParamData($params);
            if (empty($params['id'])) {
                $result = $this->setAddPost($params);
            } else {
                $result = $this->setEditPost($params);
            }
            $this->getMsg(!empty($result));
        } else {
            $this->error('非法请求');
        }
    }
}