<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use api\wxapp\model\QuestionModel;
use api\wxapp\model\QuestionOptionModel;
use cmf\controller\AdminBaseController;
use think\Model;

class QuestionOptionController extends AdminBaseController
{
    /**
     * 列表
     */
    public function index()
    {
        $Model = new QuestionOptionModel();
        $keyword = $this->request->param('keyword', '', 'trim');
        $q_id = $this->request->param('q_id');
        $problem_type = $this->request->param('problem_type');
        $this->assign('problem_type', $problem_type);
        $map = [];
        $map[] = ['status', 'in', [1, 2]];
        $map[] = ['q_id', '=', $q_id];
        if (!empty($keyword)) $map[] = ['option', 'like', "%$keyword%"];

        $list = $Model->getInfo($map);

        $list->appends(['keyword' => $keyword, 'q_id' => $q_id]);
        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        $this->assign('q_id', $q_id);
        return $this->fetch();

    }

    /**
     * 添加
     */
    public function add()
    {
        $q_id = $this->request->param('q_id');
        $problem_type = $this->request->param('problem_type');
        $this->assign('problem_type', $problem_type);
        if ($this->request->isPost()) {
            $param = $this->request->param();

            $Model = new QuestionOptionModel();
            $param['create_time'] = time();
            $param['status'] = 1;
            if (!empty($param['photos'])) {
                $param['photos'] = implode(',', $param['photos']);
            }
            if (empty($param['true_answer'])) {
                $param['true_answer'] = 0;
            } else {
                $param['true_answer'] = 1;
            }

            $q_info = QuestionModel::find($q_id);

            if ($q_info['type'] == 1 || $q_info['type'] == 4) {
                if ($param['true_answer'] == 1) {
                    $map = [];
                    $map[] = ['q_id', '=', $q_id];
                    $map[] = ['true_answer', '=', 1];
                    $map[] = ['status', '=', 1];
                    $is = $Model->where($map)->find();
                    if ($is) {
                        $this->error('此题目已有正确选项');
                    }
                }
            }

            $re = $Model->save($param);
            if ($re) {
                self::countTrueAnswer($q_id);
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }

        } else {
            $this->assign('q_id', $q_id);
            $this->assign('letter_list', QuestionOptionModel::getLetter());
            return $this->fetch();
        }
    }

    public static function countTrueAnswer($q_id)
    {

        $q_info = QuestionModel::find($q_id);
        if ($q_info['type'] == 1 || $q_info['type'] == 2 || $q_info['type'] == 4) {
            $QuestionOptionModel = new QuestionOptionModel();
            $map = [];
            $map[] = ['q_id', '=', $q_id];
            $map[] = ['true_answer', '=', 1];
            $letter_arr = $QuestionOptionModel->where($map)->column('letter');
            sort($letter_arr);
            $QuestionModel = new QuestionModel();
            $QuestionModel->where(['id' => $q_id])->save(['true_answer' => implode(',', $letter_arr)]);
        }
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $id = $this->request->param('id');
        $problem_type = $this->request->param('problem_type');
        $this->assign('problem_type', $problem_type);
        $Model = new QuestionOptionModel();
        $data = $Model->where(['id' => $id])->find();
        if ($this->request->isPost()) {
            $param = $this->request->param();
            unset($param['problem_type']);
            if (!empty($param['photos'])) {
                $param['photos'] = implode(',', $param['photos']);
            }
            if (empty($param['true_answer'])) {
                $param['true_answer'] = 0;
            } else {
                $param['true_answer'] = 1;
            }


            $q_info = QuestionModel::find($data['q_id']);

            if ($q_info['type'] == 1 || $q_info['type'] == 4) {
                if ($param['true_answer'] == 1) {
                    $map = [];
                    $map[] = ['q_id', '=', $data['q_id']];
                    $map[] = ['true_answer', '=', 1];
                    $map[] = ['status', '=', 1];
                    $map[] = ['id', '<>', $id];
                    $is = $Model->where($map)->find();
                    if ($is) {
                        $this->error('此题目已有正确选项');
                    }
                }

            }

            $re = $data->save($param);
            if ($re) {
                self::countTrueAnswer($data['q_id']);

                $this->success('更新成功', url('index', ['q_id' => $data['q_id'], 'problem_type' => $problem_type]));
            } else {
                $this->error('更新失败');
            }
        } else {
            if (!empty($data['photos'])) {
                $data['photos'] = explode(',', $data['photos']);
            }
            $data['option'] = cmf_replace_content_file_url(htmlspecialchars_decode($data['option']));
            $this->assign('data', $data);
            $this->assign('q_id', $data['q_id']);
            $this->assign('letter_list', QuestionOptionModel::getLetter());
            return $this->fetch();
        }
    }

    /**
     * 详情
     */
    public function detail()
    {
        $id = $this->request->param('id');
        $Model = new QuestionOptionModel();
        $data = $Model->getInfo(['a.id' => $id], 1, $id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 删除
     */
    public function delete()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id');
            $Model = new QuestionOptionModel();
            $re = $Model->where(['id' => $id])->find();
            if ($re) {
                $re->delete();
                self::countTrueAnswer($re['q_id']);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('非法操作');
        }
    }

    /**
     * 设置
     */
    public function setStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $field = $this->request->param('field');
        $data = QuestionOptionModel::find($id);
        $data->save([$field => $status]);
        $this->success('设置成功');
    }

    public function listOrder()
    {
        $Model = new QuestionOptionModel();
        parent::listOrders($Model);
        $this->success("排序更新成功！");
    }
}
