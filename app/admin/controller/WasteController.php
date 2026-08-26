<?php

namespace app\admin\controller;

use app\admin\model\active\WasteCateModel;
use app\admin\model\active\WasteModel;
use think\App;

class WasteController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);


        $this->model = new WasteModel();
        $this->cate_model = new WasteCateModel();
    }


    public function outParam()
    {
       $cate_list= $this->cate_model->select();
       $this->assign('cate_list',$cate_list);
    }

    //子分类列表
    public function cate_index()
    {
        $where  = [];
        $list = $this->cate_model->where($where)->paginate(10);
        return $this->fetch('cate_index', ['list' => $list,'page'=>$list->render()]);
    }

//子分类添加编辑页面
    public function cate_update()
    {

        $pid = $this->request->param('pid');
        $id = $this->request->param('id');
        if (empty($id)) {
            return $this->fetch('cate_update', ['pid' => $pid]);
        } else {
            $item = $this->cate_model->find($id);
            return $this->fetch('cate_update', ['item' => $item, 'pid' => $pid]);
        }

    }

    //子分类添加编辑提交
    public function cate_updatePost()
    {
        $data = $this->request->param();
        if (empty($data['id'])) {
            $this->cate_model->save($data);
            $this->success('添加成功');
        } else {
            $this->cate_model->update($data, ['id' => $data['id']]);
            $this->success('修改成功');
        }

    }


    public function cate_delete()
    {
       $id=$this->request->param('id');
       $this->cate_model->destroy($id);
       $this->success('删除成功');
    }

}