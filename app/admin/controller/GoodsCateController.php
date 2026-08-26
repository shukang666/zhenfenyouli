<?php

namespace app\admin\controller;

use app\admin\model\goods\GoodsCategoryModel;
use think\App;

class GoodsCateController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new GoodsCategoryModel();

    }


    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');
        if ($name) {
            $where[] = ['cate_name', 'like', '%' . $name . '%'];
        }
        $where[] = ['parent_id', '=', 0];
        return $where;
    }

    //子分类列表
    public function cate_index()
    {
        $pid = $this->request->param('pid');
        $list = $this->model->where('parent_id', $pid)->paginate(10);
        return $this->fetch('cate_index', ['list' => $list, 'page' => $list->render(), 'pid' => $pid]);
    }

//子分类添加编辑页面
    public function cate_update()
    {

        $pid = $this->request->param('pid');
        $id = $this->request->param('id');
        if (empty($id)) {
            return $this->fetch('cate_update', ['pid' => $pid]);
        } else {
            $item = $this->model->find($id);
            return $this->fetch('cate_update', ['item' => $item, 'pid' => $pid]);
        }

    }

    //子分类添加编辑提交
    public function cate_update_post()
    {
        $data = $this->request->param();
        if (empty($data['id'])) {
            $this->model->save($data);
            $this->success('添加成功');
        } else {
            $this->model->update($data, ['id' => $data['id']]);
            $this->success('修改成功');
        }

    }


}