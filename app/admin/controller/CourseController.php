<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kane <chengjin005@163.com> 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use api\wxapp\controller\AutoDoController;
use api\wxapp\model\AnswerLogModel;
use api\wxapp\model\AnswerModel;
use api\wxapp\model\CommonModel;
use api\wxapp\model\ChapterPlayModel;
use api\wxapp\model\CourseOrderModel;
use api\wxapp\model\CoursePlayModel;
use api\wxapp\model\MemberModel;
use api\wxapp\model\ProblemModel;
use api\wxapp\model\QuestionModel;
use cmf\controller\AdminBaseController;
use api\wxapp\model\CourseModel;
use api\wxapp\model\CourseChapterModel;
use cmf\controller\RestBaseController;
use think\Model;


/**
 * Class
 * @package app\admin\controller
 */
class CourseController extends AdminBaseController
{

    /*
       * 课程列表
     */
    public function courseList()
    {

        $AutoDoController = new AutoDoController();
        //$AutoDoController->getDuration();
        $CourseModel = new CourseModel();
        $title = $this->request->param('title');
        $type_id = $this->request->param('type_id');
        $map = [];
        $map[] = ['a.status', 'in', [1, 2]];
        if (!empty($title)) {
            $map[] = ['a.title', 'like', "%$title%"];
        }
        if (!empty($type_id)) {
            $map[] = ['a.type_id', '=', $type_id];
        }

        $list = $CourseModel->getInfo($map);

        $list->appends(['title' => $title, 'type_id' => $type_id]);

        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('type_id', $type_id);
        return $this->fetch();

    }


    /*
     * 添加课程
     */
    public function addCourse()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();

            if (empty($param['thumb'])) $this->error('请上传主图');
            if(empty($param['media'])) $this->error('请上传视频');
           // if (empty($param['content'])) $this->error('请录入详情');
            $Model = new CourseModel();
            $param['create_time'] = time();
            $param['status'] = 1;
            $re = $Model->save($param);
            if ($re) {
                $this->success('添加成功', url("course/courseList"));
            } else {
                $this->error('添加失败');
            }
        } else {
            //分类
            $this->assign('type_list', CourseModel::getCourseType());
            return $this->fetch();
        }
    }

    /*
     * 编辑课程
     */
    public function editCourse()
    {
        $id = $this->request->param('id');
        $data = CourseModel::find($id);
        if (empty($data)) {
            $this->error('课程不存在');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param();
            if (empty($param['thumb'])) $this->error('请上传主图');
            if(empty($param['media'])) $this->error('请上传视频');
          //  if (empty($param['content'])) $this->error('请录入详情');
            $param['update_time'] = time();
            if($data['media'] != $param['media']){
                $param['duration'] = 0;
            }
            $re = $data->save($param);
            if ($re) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        } else {
            $data['content'] = htmlspecialchars_decode($data['content']);
            $this->assign('data', $data);

            return $this->fetch();
        }
    }

    /*
     * 删除课程
     */
    public function deleteCourse()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id');
            $data = CourseModel::find($id);
            if (empty($data)) {
                $this->error('课程不存在');
            }
            $re = $data->save(['status' => 0]);
            if ($re) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('非法操作');
        }
    }

    public function setCourseStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $type = $this->request->param('type', 1);
        $Model = CourseModel::find($id);
        if (empty($Model)) {
            $this->error('不存在');
        }
        if ($type == 1) {
            $Model->save(['status' => $status]);
        } elseif ($type == 2) {
            $Model->save(['is_hot' => $status]);
        }


        $this->success('设置成功');
    }

    /*
   * 课程排序
   */
    public function listOrder()
    {
        $CourseModel = new CourseModel();
        parent::listOrders($CourseModel);
        $this->success("排序更新成功！");
    }

    /*
        * 播放记录
  */
    public function playLog()
    {

        $Model = new CoursePlayModel();
        $keyword = trim($this->request->param('keyword'));
        $shop_id = trim($this->request->param('shop_id'));
        $course_id = trim($this->request->param('course_id'));
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');


        $map = [];
        if (!empty($keyword)) {
            $map[] = ['b.nickname|b.true_name|b.mobile|c.title', 'like', "%$keyword%"];
        }
        if($shop_id){
            $map[] = ['b.shop_id', '=', $shop_id];
        }
        if($course_id){
            $map[] = ['a.course_id', '=', $course_id];
        }
        if($start_time)  $map[] = ['a.last_time','>=',strtotime($start_time)];
        if($end_time)  $map[] = ['a.last_time','<=',strtotime($end_time)+86399];
        $list = $Model->getInfo($map,20);
        $list->appends(['keyword' => $keyword,'shop_id'=>$shop_id,'course_id'=>$course_id,'start_time'=>$start_time,'end_time'=>$end_time]);
        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('shop_id', $shop_id);
        $this->assign('keyword', $keyword);
        $this->assign('course_id', $course_id);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
//        $this->assign('shop_list', CommonModel::getCateList(1));
        $CourseModel = new CourseModel();
        $map = [];
        $map[] = ['status','in',[1,2]];
        $course_list = $CourseModel->where($map)->order('id desc')->select();
        $this->assign('course_list',$course_list);
        return $this->fetch();
    }

    public function outData(){
        $Model = new CoursePlayModel();
        $keyword = trim($this->request->param('keyword'));
        $shop_id = trim($this->request->param('shop_id'));
        $course_id = trim($this->request->param('course_id'));
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');
        $map = [];
        if (!empty($keyword)) {
            $map[] = ['b.nickname|b.true_name|b.mobile|c.title', 'like', "%$keyword%"];
        }
        if($shop_id){
            $map[] = ['b.shop_id', '=', $shop_id];
        }
        if($course_id){
            $map[] = ['a.course_id', '=', $course_id];
        }
        if($start_time)  $map[] = ['a.last_time','>=',strtotime($start_time)];
        if($end_time)  $map[] = ['a.last_time','<=',strtotime($end_time)+86399];


        $list = $Model->getInfo($map,-1);
        $played_name = [0=>'否',1=>'是'];
        foreach ($list as &$item){
            $item['played_name'] = $played_name[$item['played']];
            $item['last_time'] = date('Y-m-d H:i:s',$item['last_time']);
        }
        $filename = '播放列表'.date('Y-m-d');
        $head = ['ID','昵称','姓名','手机号','备注','门店','视频名称','总时长','当前播放时长','上次播放时间','是否已看完'];//设置表头
        $keys = ['id','nickname','true_name','mobile','remark','shop_name','title','show_duration','show_play_time','last_time','played_name'];//设置表头
        // 必须静态调用，实例调用会经 __call 转发到查询构造器导致 cmf_common_model 表不存在报错
        CommonModel::outData($filename,$list,$head,$keys);
    }

    public function answer(){
        $Model = new AnswerModel();
        $keyword = $this->request->param('keyword','','trim');
        $exam_id = $this->request->param('exam_id');
        $shop_id = trim($this->request->param('shop_id'));
        $map = [];
        if($shop_id)  $map[] = ['shop_id','=',$shop_id];
        if($exam_id)  $map[] = ['exam_id','=',$exam_id];
        if(!empty($keyword)) $map[] = ['b.true_name|b.nickname|b.mobile','like',"%$keyword%"];

        $list = $Model->getInfo($map,20);
        $list->appends(['keyword' => $keyword,'exam_id'=>$exam_id,'shop_id'=>$shop_id]);
        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        $this->assign('exam_id', $exam_id);
        $this->assign('shop_id', $shop_id);
//        $this->assign('shop_list', CommonModel::getCateList(1));
        return $this->fetch();
    }

    public function outAnswer()
    {
        // 弹窗 iframe 与导出并发时 PHP 默认 session 锁会导致导出一直 pending
        if (function_exists('session_write_close')) {
            session_write_close();
        }
        @set_time_limit(0);

        $keyword = $this->request->param('keyword', '', 'trim');
        $exam_id = (int)$this->request->param('exam_id', 0);
        $shop_id = trim($this->request->param('shop_id', ''));
        if ($exam_id <= 0) {
            $this->error('缺少考试 ID');
        }

        $map = [['a.exam_id', '=', $exam_id]];
        if ($shop_id !== '') {
            $map[] = ['a.shop_id', '=', $shop_id];
        }
        if ($keyword !== '') {
            $map[] = ['b.true_name|b.nickname|b.mobile', 'like', '%' . $keyword . '%'];
        }

        $QuestionModel = new QuestionModel();
        $q_list = $QuestionModel->where([
            ['pid', '=', $exam_id],
            ['status', '=', 1],
        ])->order('id asc')->select()->toArray();

        $q_head = [];
        $q_ids = [];
        foreach ($q_list as $v) {
            $q_ids[] = (int)$v['id'];
            $q_head[] = strip_tags(htmlspecialchars_decode((string)$v['title']));
        }

        $AnswerModel = new AnswerModel();
        $list = $AnswerModel->alias('a')
            ->field('a.*,b.true_name,b.mobile,b.nickname')
            ->join('member b', 'a.user_id = b.id')
            ->where($map)
            ->order('a.id desc')
            ->select()
            ->toArray();

        if (empty($list)) {
            $this->error('暂无数据可导出');
        }

        $problemName = (string)ProblemModel::where('id', $exam_id)->value('name');
        $answerIds = array_column($list, 'id');
        $logByAnswer = [];
        if (!empty($answerIds) && !empty($q_ids)) {
            $logs = AnswerLogModel::where('answer_id', 'in', $answerIds)
                ->where('q_id', 'in', $q_ids)
                ->field('answer_id,q_id,answer_status')
                ->select()
                ->toArray();
            foreach ($logs as $log) {
                $logByAnswer[(int)$log['answer_id']][(int)$log['q_id']] = (int)$log['answer_status'];
            }
        }

        $answer_status_name = [1 => '正确', 2 => '错误', 3 => '未回答'];
        $head = array_merge(
            ['ID', '昵称', '姓名', '手机号', '考试名称', '答题数', '得分', '答题时间'],
            $q_head
        );

        $filename = '答题记录' . ($problemName !== '' ? '_' . $problemName : '') . '_' . date('Y-m-d');
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=UTF-8');
        $encodedName = rawurlencode($filename . '.csv');
        header('Content-Disposition: attachment; filename="answer_export.csv"; filename*=UTF-8\'\'' . $encodedName);
        header('Cache-Control: max-age=0');
        header('X-Accel-Buffering: no');

        $fp = fopen('php://output', 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, $head);

        foreach ($list as $row) {
            $row = CommonModel::changeTrueNameFromNickname($row);
            $line = [
                $row['id'],
                $row['nickname'] ?? '',
                $row['true_name'] ?? '',
                $row['mobile'] ?? '',
                $problemName,
                $row['total_num'] ?? '',
                $row['score'] ?? '',
                !empty($row['create_time']) ? date('Y-m-d H:i:s', (int)$row['create_time']) : '',
            ];
            foreach ($q_list as $q) {
                $qid = (int)$q['id'];
                $status = $logByAnswer[(int)$row['id']][$qid] ?? null;
                $line[] = $status !== null ? ($answer_status_name[$status] ?? '错误') : '错误';
            }
            fputcsv($fp, $line);
        }

        fclose($fp);
        exit;
    }

    public function answerLog(){
        $Model = new AnswerLogModel();
        $answer_id = $this->request->param('answer_id');
        $map = [];
        if($answer_id)  $map[] = ['answer_id','=',$answer_id];

        $list = $Model->getInfo($map);
        $list->appends(['answer_id' => $answer_id]);
        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('answer_id', $answer_id);
        return $this->fetch();
    }

    public function answer_delete()
    {
        $id = $this->request->param('id');
        $answerModel = new AnswerModel();
        $answerLogModel = new AnswerLogModel();
        $answerModel->where('id', $id)->delete();
        $answerLogModel->where('answer_id', $id)->delete();
         $this->success('删除成功');
    }

}
