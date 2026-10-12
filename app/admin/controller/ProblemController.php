<?php

namespace app\admin\controller;


use api\wxapp\model\ProblemModel;
use api\wxapp\model\QuestionModel;


class ProblemController extends ToolController
{
    protected function initialize()
    {
        $this->model = new ProblemModel();
        return parent::initialize();

    }


    public function index()
    {
        // 菜单可能直接打开 index 未带 type，答题考试默认为 type=1
        $type = $this->request->param('type', 1, 'intval');
        if ($type <= 0) {
            $type = 1;
        }

        $title = $this->request->param('title');
        $params = $this->request->param();
        $params['type'] = $type;

        $where = [];

        $where[] = ['type', '=', $type];
        if (!empty($title)) {
            $where[] = ['name', 'like', '%' . trim($title) . '%'];
        }
        $list = $this->model->where($where)->order('id desc')->paginate(15)->each(function ($item, $key) {
//            $item->no = $item->where('id', '<=', $item->id)->where('cate_id', $item->cate_id)->count();

        });
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('params', $params);
        $this->assign('page', $list->render());
        $this->assign('title', $title);
        return $this->fetch();
    }

    public function index1()
    {
        return redirect((string)url('index', ['type' => 1]));
    }


    public function index2()//垃圾分类上报类型
    {
        return redirect((string)url('index', ['type' => 2]));
    }

    public function index3()//垃圾分类上报类型
    {
        return redirect((string)url('index', ['type' => 3]));
    }


    public function selected()
    {
        $Model = new ProblemModel();
        $problemCateModel = new CateModel();
        $cateModel = new ProblemCateModel();
        $params = $this->request->param();
        if ($this->request->isPost()) {
            $list = $params['data'] ?? [];
            $data = [];
            $res = $cateModel->where('school_id', $params['id'])->delete();
            if (!empty($params['data'])) {
                foreach ($list as $value) {
                    $data[] = [
                        'problem_id' => $params['id'],
                        'cate_id' => $value['value'],
                        'create_time' => time()
                    ];
                }
                $cateModel->insertAll($data);
            }

            $this->success('保存成功！');
        } else {

            $categories = $problemCateModel->field(' name,id')->where('status', 1)->select();
            $major_id = $cateModel->where('problem_id', $params['id'])->column('cate_id');
            $ids = json_encode($major_id);

            return $this->fetch('selected', ['categories' => $categories, 'ids' => $ids, 'id' => $params['id']]);
        }

    }


    //修改状态
    public function setStatus()
    {
        $params = $this->request->param();
        $QuestionModel = new QuestionModel();
        $info = $this->model->where(['id' => $params['id']])->find();
        $res = $QuestionModel->where('pid', $params['id'])->count();
        if ($res == 0 && $info['type'] == 1) {
            $this->error('暂无问题请先添加问题！');
        }
        $status[$params['type']] = $params['status'];
        if (!empty($params['pro_type']) && $params['status'] == 1) {
            $this->model
                ->where('type', $params['pro_type'])
                ->update(['status' => 2]);
        }
        $this->model->where(['id' => $params['id']])->update($status);
        $this->success('设置成功');
    }



//    public function update()
//    {
//        $params = $this->request->param();
////        $cate_id = $this->request->param('cate_id');
////        $this->assign('cate_id', $cate_id);
//
//        if (empty($params['id'])) {
//            return $this->fetch();
//        }
//        $item = $this->model->find($params['id']);
////        if (!empty($item['image'])) {
////            $item = CommonModel::getOne($item, [], ['image']);
////        }
//        $this->assign('item', $item);
//        $this->assign('params', $params);
//
//        return $this->fetch();
//    }
//
//    public function updatePost()
//    {
////        $cate_id = $this->request->param('cate_id');
////        $this->assign('cate_id', $cate_id);
//        if ($this->request->isAjax()) {
//            $params = $this->request->param();
//
//            if (empty($params['id'])) {
//                $result = $this->model->save($params);
//                $id = $this->model->id;
//            } else {
//                $result = $this->model->where('id', $params['id'])->save($params);
//                $id = $params['id'];
//            }
//
//
//
//
//            $this->success('保存成功');
//        } else {
//            $this->error('非法请求');
//        }
//    }
//
//


    public function cate_index()
    {
        $cate_id = $this->request->param('cate_id');

        $title = $this->request->param('title');
        $params = $this->request->param();
        $where = [];

        if (!empty($cate_id)) {
            $where[] = ['cate_id', '=', trim($cate_id)];
        }
        if (!empty($title)) {
            $where[] = ['name', 'like', '%' . trim($title) . '%'];
        }
        $list = $this->cateModel->where($where)->order('id desc')->paginate(15)->each(function ($item, $key) {
//            $item['no'] = $this->model
//                    ->where('id', '<', $item['id'])
//                    ->where('cate_id', '=', $item['cate_id'])
//                    ->count() + 1;

            $item->no = $item->where('id', '<=', $item->id)->where('cate_id', $item->cate_id)->count();


        });
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('cate_id', $cate_id);
        $this->assign('title', $title);
        return $this->fetch();
    }


    public function cate_update()
    {
        $params = $this->request->param();
        $cate_id = $this->request->param('cate_id');
        $this->assign('cate_id', $cate_id);

        if (empty($params['id'])) {
            return $this->fetch();
        }

        $item = $this->cateModel->find($params['id']);
        $this->assign('item', $item);
        $this->assign('params', $params);
        return $this->fetch();
    }

    public function cate_updatePost()
    {
        $cate_id = $this->request->param('cate_id');
        $this->assign('cate_id', $cate_id);
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $params['cate_id'] = $cate_id;
            if (empty($params['id'])) {
                $result = $this->cateModel->save($params);
            } else {
                $result = $this->cateModel->where('id', $params['id'])->save($params);
            }
            $this->success('保存成功');
        } else {
            $this->error('非法请求');
        }
    }

    public function get_params()
    {
        // $cate_list = cmf_table('news_cate')->select();
        // $this->assign('cate_list', $cate_list);
    }
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result = $this->model->find($id)->delete();
        $this->success('删除成功');
    }

    public function cate_delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result = $this->cateModel->find($id)->delete();
        $this->success('删除成功');
    }


    public function listOrder()
    {
        parent::listOrders($this->model);
        $this->success("排序更新成功！");
    }

    public function cate_listOrder()
    {
        parent::listOrders($this->cateModel);
        $this->success("排序更新成功！");
    }
}