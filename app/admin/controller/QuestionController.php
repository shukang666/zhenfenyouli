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

use api\wxapp\model\AnswerLogModel;
use api\wxapp\model\ProblemModel;
use api\wxapp\model\QuestionModel;
use api\wxapp\model\QuestionOptionModel;
use cmf\controller\AdminBaseController;
use think\Model;

class QuestionController extends AdminBaseController
{
    /**
     * 列表
     */
    public function index()
    {
        $Model = new QuestionModel();
        $keyword = $this->request->param('keyword', '', 'trim');
        $type = $this->request->param('type');
        $pid = $this->request->param('pid', 0);
        $map = [];
        $map[] = ['status', 'in', [1, 2]];
        $map[] = ['pid', '=', $pid];
        if ($type) $map[] = ['type', '=', $type];

        if (!empty($keyword)) $map[] = ['title', 'like', "%$keyword%"];
        $ProblemModel = new ProblemModel();

        $problem_type=  $ProblemModel->where(['id' => $pid])->value('type');
        $this->assign('problem_type', $problem_type);
        $list = $Model->getInfo($map);
        $list->appends(['keyword' => $keyword, 'type' => $type, 'pid' => $pid]);
        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        $this->assign('type', $type);
        $this->assign('pid', $pid);
        $this->assign('type_list', QuestionModel::getType(-1, $pid));
        return $this->fetch();

    }

    /**
     * 添加
     */
    public function add()
    {
        $q_type = $this->request->param('q_type', 1);
        $pid = $this->request->param('pid', 0);
        if ($this->request->isPost()) {
            $param = $this->request->param();

            $Model = new QuestionModel();
            $param['create_time'] = time();
            $param['status'] = 1;
            if (!empty($param['photos'])) {
                $param['photos'] = implode(',', $param['photos']);
            }
            $id = $Model->insertGetId($param);
            if ($id) {
                //增加五个选择

//                $m = [1 => "A", 2 => "B", 3 => "C", 4 => "D", 5 => "E"];
//                $m1 = [1 => "没有", 2 => "很轻", 3 => "中等", 4 => "偏重", 5 => "严重"];
//
//                $data = [];
//                for ($i = 1; $i <= 5; $i++) {
//                    $data[] = [
//                        'q_id' => $id,
//                        'score' => $i,
//                        'letter' => $m[$i],
//                        'option' => $m1[$i],
//                        'create_time' => time(),
//                    ];
//                }
//                $optionModel = new QuestionOptionModel();
//                $optionModel->saveAll($data);
                $this->success('添加成功');//, url("index", ['pid' => $pid, 'q_type' => $q_type])

            } else {
                $this->error('添加失败');
            }

        } else {
            $this->assign('q_type', $q_type);
            $this->assign('pid', $pid);
            $this->assign('type_list', QuestionModel::getType(-1, $pid));
            $this->assign('letter_list', QuestionOptionModel::getLetter());
            return $this->fetch();
        }
    }


    /**
     * 编辑
     */
    public
    function edit()
    {
        $id = $this->request->param('id');
        $Model = new QuestionModel();
        $data = $Model->where(['id' => $id])->find();
        if ($this->request->isPost()) {
            $param = $this->request->param();
            if (!empty($param['photos'])) {
                $param['photos'] = implode(',', $param['photos']);
            }
            $re = $data->save($param);
            if ($re) {
                if (!empty($data['pid'])) {
                    $this->success('更新成功', url("index", ['pid' => $data['pid']]));
                } else {
                    $this->success('更新成功');
                }
            } else {
                $this->error('更新失败');
            }
        } else {
            $data['content'] = htmlspecialchars_decode($data['content']);
            if (!empty($data['photos'])) {
                $data['photos'] = explode(',', $data['photos']);
            }
            $data['title'] = htmlspecialchars_decode($data['title']);
            $data['desc'] = htmlspecialchars_decode($data['desc']);

            $this->assign('data', $data);
            $this->assign('pid', $data['pid']);
            $this->assign('type_list', QuestionModel::getType(-1, $data['pid']));
            return $this->fetch();
        }
    }

    /**
     * 详情
     */
    public
    function detail()
    {
        $id = $this->request->param('id');
        $Model = new QuestionModel();
        $data = $Model->getInfo(['a.id' => $id], 1, $id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 删除
     */
    public
    function delete()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id');
            $Model = new QuestionModel();
            $re = $Model->where(['id' => $id])->save(['status' => 0]);
            if ($re) {
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
    public
    function setStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $field = $this->request->param('field');
        $data = QuestionModel::find($id);
        $data->save([$field => $status]);
        $this->success('设置成功');
    }

    /**
     * 审核
     */
    public
    function check()
    {
        $id = $this->request->param('id');
        $check_status = $this->request->param('check_status');
        $check_reason = $this->request->param('check_reason');
        $data = QuestionModel::find($id);
        $data->save(['check_status' => $check_status, 'check_reason' => $check_reason, 'check_time' => time()]);
        $this->success('审核成功');
    }

    public
    function listOrder()
    {
        $Model = new QuestionModel();
        parent::listOrders($Model);
        $this->success("排序更新成功！");
    }

    public
    function qType1()
    {
        return redirect((string)url('index', ['q_type' => 1]));
    }

    public
    function qType2()
    {
        return redirect((string)url('index', ['q_type' => 2]));
    }


}
