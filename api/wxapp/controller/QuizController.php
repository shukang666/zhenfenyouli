<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use think\facade\Db;

class QuizController extends AuthController
{
    const QUIZ_LIMIT  = 5;    // 每次随机出的题数
    const POINT_PER_Q = 2;    // 每题积分
    const MAX_POINT   = 10;   // 最多积分

    /**
     * 获取今日答题情况 + 组卷
     * GET /wxapp/quiz/get_quiz
     * 返回：已答题提示 或 随机5道题
     */
    public function get_quiz()
    {
        $this->checkUser();

        $today = date('Y-m-d');

        // 检查今天是否已答过
        $existLog = Db::name('quiz_log')
            ->where('user_id', $this->user_id)
            ->where('quiz_date', $today)
            ->find();

        if ($existLog) {
            $this->success('今日已答题', [
                'answered'      => true,
                'correct_count' => intval($existLog['correct_count']),
                'point'         => intval($existLog['point']),
                'total_count'   => intval($existLog['total_count']),
            ]);
        }

        // 获取所有启用的题目
        $allQuestions = Db::name('quiz_question')
            ->where('status', 1)
            ->field('id, title, option_a, option_b, option_c, option_d, correct_option')
            ->select()
            ->toArray();

        if (count($allQuestions) < self::QUIZ_LIMIT) {
            $this->error('题库题目数量不足，请联系管理员添加更多题目');
        }

        // 随机抽5道题
        shuffle($allQuestions);
        $questions = array_slice($allQuestions, 0, self::QUIZ_LIMIT);

        // 不返回 correct_option（答案）给前端
        foreach ($questions as &$q) {
            unset($q['correct_option']);
        }
        unset($q);

        $this->success('获取成功', [
            'answered'  => false,
            'questions' => $questions,
            'limit'     => self::QUIZ_LIMIT,
        ]);
    }

    /**
     * 提交答题
     * POST /wxapp/quiz/submit_quiz
     * 参数：answers [{qid: 1, answer: 'A'}, ...]
     */
    public function submit_quiz()
    {
        $this->checkUser();

        $today = date('Y-m-d');

        // 检查今天是否已答过
        $existLog = Db::name('quiz_log')
            ->where('user_id', $this->user_id)
            ->where('quiz_date', $today)
            ->find();

        if ($existLog) {
            $this->error('今日已答题，请明天再来');
        }

        $answers = $this->request->param('answers', '');
        if (empty($answers)) {
            $this->error('答案为空');
        }

        // 支持 JSON 字符串或数组
        if (is_string($answers)) {
            $answers = json_decode($answers, true);
        }
        if (empty($answers) || !is_array($answers)) {
            $this->error('答案格式错误');
        }

        // 获取题目ID列表
        $qids = array_column($answers, 'qid');
        if (empty($qids)) {
            $this->error('答案为空');
        }

        // 查询对应题目及答案
        $questions = Db::name('quiz_question')
            ->where('id', 'in', $qids)
            ->where('status', 1)
            ->column('correct_option', 'id');

        if (empty($questions)) {
            $this->error('题目不存在');
        }

        // 逐题判分
        $correctCount = 0;
        $totalCount   = count($answers);
        $detail       = [];

        foreach ($answers as $ans) {
            $qid    = intval($ans['qid']);
            $answer = strtoupper(trim($ans['answer']));

            $correctOption = isset($questions[$qid]) ? strtoupper($questions[$qid]) : '';
            $isCorrect     = ($correctOption && $answer === $correctOption);

            if ($isCorrect) {
                $correctCount++;
            }

            $detail[] = [
                'qid'           => $qid,
                'answer'        => $answer,
                'correct'       => $correctOption,
                'is_correct'    => $isCorrect,
            ];
        }

        // 计算积分：每题2分，封顶最低都是0
        $point = max(0, $correctCount * self::POINT_PER_Q);
        $point = min($point, self::MAX_POINT);

        // 写入答题记录
        $logId = Db::name('quiz_log')->insertGetId([
            'user_id'      => $this->user_id,
            'quiz_date'    => $today,
            'total_count'  => $totalCount,
            'correct_count'=> $correctCount,
            'point'        => $point,
            'detail'       => json_encode($detail, JSON_UNESCAPED_UNICODE),
            'create_time'  => time(),
        ]);

        // 发放积分（大于0 时）
        if ($point > 0) {
            AccountLogModel::add_account_log(
                $this->user_id,
                2,    // account_type: 积分
                201,  // type: 沿用问卷获得/答题获得
                $point,
                ['remark' => '分类知识挑战答对 ' . $correctCount . ' 题']
            );
        }

        $this->success('提交成功', [
            'correct_count' => $correctCount,
            'total_count'   => $totalCount,
            'point'         => $point,
            'answers_detail' => $detail,
        ]);
    }

    /**
     * 获取积分明细（与今日已答题状态合并的接口，方便前端统一调用）
     * GET /wxapp/quiz/check_today
     */
    public function check_today()
    {
        $this->checkUser();
        $today = date('Y-m-d');

        $log = Db::name('quiz_log')
            ->where('user_id', $this->user_id)
            ->where('quiz_date', $today)
            ->find();

        if ($log) {
            $this->success('今日已答题', [
                'answered'      => true,
                'correct_count' => intval($log['correct_count']),
                'point'         => intval($log['point']),
            ]);
        }

        $this->success('今日未答题', ['answered' => false]);
    }
}
