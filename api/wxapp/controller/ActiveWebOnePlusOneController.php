<?php

namespace api\wxapp\controller;

use api\wxapp\service\ActiveWebOnePlusOneQuizService;
use api\wxapp\service\ActiveWebOnePlusOneService;
use api\wxapp\service\ClockLearnExamService;

/**
 * Web 式活动「1+1」七日挑战
 */
class ActiveWebOnePlusOneController extends AuthController
{
    public function status()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            ActiveWebOnePlusOneService::loadWebActive($activeId);
            $data = ActiveWebOnePlusOneService::syncAndBuildStatus((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        } catch (\Throwable $e) {
            $this->error('活动进度加载失败，请联系管理员');
        }
        $this->success('请求成功', $data);
    }

    public function day()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $day = (int)$this->request->param('day');
        try {
            ActiveWebOnePlusOneService::loadWebActive($activeId);
            $data = ActiveWebOnePlusOneService::dayDetail((int)$this->user_id, $activeId, $day);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('请求成功', $data);
    }

    /** 第 1 天：完成真分前置学习后回写 1+1 进度 */
    public function pre_learn_ack()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            $data = ActiveWebOnePlusOneService::recordDay1PreLearnCompletion((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('已记录学习完成', $data);
    }

    /** 第 1 天：有害垃圾 Web 打卡记录（历史兼容） */
    public function harmful_list()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            $list = ActiveWebOnePlusOneService::listHarmfulClocks((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('请求成功', ['list' => $list]);
    }

    /** 第 1 天：提交有害垃圾 Web 打卡 */
    public function harmful_submit()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $image = trim((string)$this->request->param('image', ''));
        $remark = trim((string)$this->request->param('remark', ''));
        try {
            $row = ActiveWebOnePlusOneService::submitHarmfulClock((int)$this->user_id, $activeId, $image, $remark);
            $status = ActiveWebOnePlusOneService::syncAndBuildStatus((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('打卡成功', [
            'record' => $row,
            'status' => $status,
        ]);
    }

    /** 第 3 天：获取 H5 答题题库（无正确答案） */
    public function quiz_questions()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            ActiveWebOnePlusOneService::loadWebActive($activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('请求成功', [
            'questions' => ActiveWebOnePlusOneQuizService::questionsForClient(),
            'total' => 4,
        ]);
    }

    /** 第 3 天：提交答题（提交即完成，答错返解析） */
    public function quiz_submit()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $answers = $this->request->param('answers/a', []);
        if (empty($answers) || !is_array($answers)) {
            $this->error('请提交答题结果');
        }
        try {
            $data = ActiveWebOnePlusOneQuizService::submitAnswers((int)$this->user_id, $activeId, $answers);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $msg = !empty($data['has_wrong']) ? '已提交，请查看错题解析' : '提交成功，当日任务已完成';
        $this->success($msg, $data);
    }

    /** 第 1 天：练一练题目（无答案） */
    public function pre_learn_blocks()
    {
        $this->checkUser();
        $this->success('请求成功', ['blocks' => ActiveWebOnePlusOneService::preLearnBlocks()]);
    }

    /** 第 1 天：提交练一练并回写 1+1 进度 */
    public function pre_learn_submit()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $answers = $this->request->param('answers/a', []);
        if (!is_array($answers) || empty($answers)) {
            $this->error('请先答完练一练题目');
        }
        $grade = ClockLearnExamService::gradeAnswers($answers);
        if (empty($grade['pass'])) {
            $this->error('有题目答错了，请修改后再提交', $grade);
        }
        try {
            $data = ActiveWebOnePlusOneService::submitDay1PreLearnExam(
                (int)$this->user_id,
                $activeId,
                $answers
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('学习完成', $data);
    }

    /** 第 2 天：发布闲置（写入社区闲置模块） */
    public function task_secondhand_submit()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            $data = ActiveWebOnePlusOneService::submitTaskSecondhand(
                (int)$this->user_id,
                $this->user_info ?: [],
                $activeId,
                (string)$this->request->param('title', ''),
                (string)$this->request->param('price', ''),
                (string)$this->request->param('contact', ''),
                trim((string)$this->request->param('image', ''))
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('发布成功，请等待审核', $data);
    }

    /** 第 4 天：七贤岭摄影上传 */
    public function task_photo_submit()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            $data = ActiveWebOnePlusOneService::submitTaskPhoto(
                (int)$this->user_id,
                $activeId,
                trim((string)$this->request->param('image', '')),
                trim((string)$this->request->param('intro', ''))
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('上传成功', $data);
    }

    /** 第 6/7 天：巡查点位列表 */
    public function task_dwxj_points()
    {
        $this->checkUser();
        try {
            $data = ActiveWebOnePlusOneService::taskDwxjPoints($this->user_info ?: []);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('请求成功', $data);
    }

    /** 第 6 天桶边督导 / 第 7 天小区巡查 */
    public function task_dwxj_submit()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $day = (int)$this->request->param('day', 0);
        try {
            $data = ActiveWebOnePlusOneService::submitTaskDwxj(
                (int)$this->user_id,
                $this->user_info ?: [],
                $activeId,
                $day,
                (int)$this->request->param('cate_id', 0),
                trim((string)$this->request->param('image_one', '')),
                (int)$this->request->param('inspect_community_id', 0)
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('提交成功', $data);
    }

    /** 第 5 天：用户在 H5 确认分享后标记（可与积分分享记录二选一满足） */
    public function share_ack()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        try {
            ActiveWebOnePlusOneService::loadWebActive($activeId);
            ActiveWebOnePlusOneService::markShareAck((int)$this->user_id, $activeId);
            $data = ActiveWebOnePlusOneService::syncAndBuildStatus((int)$this->user_id, $activeId);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('已记录分享', $data);
    }
}
