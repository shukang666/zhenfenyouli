<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

class ToolCateController extends AdminBaseController
{
//    public function __construct(App $app, NewsModel $newsModel, NewsCateModel $cateModel)
//    {
//        parent::__construct($app);
//        $this->model = $newsModel;
//        $this->cateModel = $cateModel;
//    }

    // 列表
    public function index()
    {
        $Model = $this->model;
        $keyword = $this->request->param('keyword');
        $cate_id = $this->request->param('cate_id');
        $map = [];
        $map[] = ['status', 'in', [1, 2]];
        if ($keyword) $map[] = ['title', 'like', "%$keyword%"];
        if ($cate_id) $map[] = ['cate_id', '=', $cate_id];
        $list = $Model->with('cate')->where($map)->paginate(15);
        $list->appends(['keyword' => $keyword, 'cate_id' => $cate_id]);
        // 获取分页显示
        $page = $list->render();
        $this->assign('cate_id', $cate_id);
        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('cate_list', $this->getNewsCate());
        return $this->fetch();
    }

    //修改状态
    public function setStatus()
    {
        $params = $this->request->param();
        $status[$params['type']] = $params['status'];
        if ($params['cate'] == 'true') {
            $this->cateModel->where(['id' => $params['id']])->update($status);
        } else {
            $this->model->where(['id' => $params['id']])->update($status);
        }
        $this->success('设置成功');
    }

    //编辑和添加
    public function update()
    {
        $model = $this->model;
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['id'])) {

                $res = $model->save($data);
                $msg = '添加成功';
            } else {
                $res = $model->where(['id' => $data['id']])->save($data);
                $msg = '更新成功';
            }
            if ($res) {
                if (empty($data['id'])) {
                    $this->success($msg, url('index'));
                } else {
                    $this->success($msg);
                }
            } else {
                $this->error('失败');
            }
        } else {
            $id = $this->request->param('id');
            $data = $model->where(['id' => $id])->find();
            $data['content'] = htmlspecialchars_decode($data['content']);
            $this->assign('data', $data);
            $this->assign('cate_list', $this->getNewsCate());
            return $this->fetch();
        }
    }

    //分类编辑和添加
    public function cate_update()
    {
        $model = $this->cateModel;
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($id)) {
                $res = $model->save($data);
                $msg = '添加成功';
            } else {
                $res = $model->where(['id' => $id])->save($data);
                $msg = '更新成功';
            }
            if ($res) {
                $this->success($msg);
            } else {
                $this->error('失败');
            }
        } else {
            $id = $this->request->param('id');
            $data = $model->where(['id' => $id])->find();
            $this->assign('data', $data);
            return $this->fetch();
        }
    }


    //分类列表
    public function cateList()
    {
        $Model = $this->cateModel;
        $keyword = $this->request->param('keyword');
        $map = [];
        $map[] = ['status', 'in', [1, 2]];
        if (!empty($keyword)) {
            $map[] = ['cate_name', 'like', "%$keyword%"];
        }
        $list = $Model->where($map)->order('id desc')->paginate(10);
        $list->appends(['keyword' => $keyword]);
        // 获取分页显示
        $page = $list->render();
        $this->assign("page", $page);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function deleteCate()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id');
            $Model = $this->cateModel->find($id);
            if (empty($Model)) {
                $this->error('不存在');
            }
            $re = $Model->save(['status' => 0]);
            if ($re) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('非法操作');
        }
    }

    public function listOrder()
    {
        parent::listOrders($this->model);
        $this->success("排序更新成功！");
    }

    //分类列表
    public function getNewsCate()
    {
        return $this->cateModel->field('id,cate_name')->where(['status' => 1])->select();
    }




}