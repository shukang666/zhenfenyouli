<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use think\facade\Db;

/**
 * 七贤岭考试 - 小程序接口
 * 获取题目 / 提交答案 / 状态检查（独立于垃圾分类的 QuizController）
 */
class QxlQuizController extends AuthController
{
    const QUIZ_LIMIT  = 5;    // 每次随机出的题数
    const POINT_PER_Q = 2;    // 每题积分
    const MAX_POINT   = 10;   // 最多积分

    /**
     * 获取今日答题情况 + 组卷
     * GET /wxapp/qxl_quiz/get_quiz
     */
    public function get_quiz()
    {
        $this->checkUser();

        $today = date('Y-m-d');

        $existLog = Db::name('qxl_quiz_log')
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
        $allQuestions = Db::name('qxl_quiz_question')
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

        // 不返回正确答案
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
     * POST /wxapp/qxl_quiz/submit_quiz
     * 参数：answers [{qid: 1, answer: 'A'}, ...]
     */
    public function submit_quiz()
    {
        $this->checkUser();

        $today = date('Y-m-d');

        $existLog = Db::name('qxl_quiz_log')
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

        if (is_string($answers)) {
            $answers = json_decode($answers, true);
        }
        if (empty($answers) || !is_array($answers)) {
            $this->error('答案格式错误');
        }

        $qids = array_column($answers, 'qid');
        if (empty($qids)) {
            $this->error('答案为空');
        }

        $questions = Db::name('qxl_quiz_question')
            ->where('id', 'in', $qids)
            ->where('status', 1)
            ->column('correct_option', 'id');

        if (empty($questions)) {
            $this->error('题目不存在');
        }

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
                'qid'        => $qid,
                'answer'     => $answer,
                'correct'    => $correctOption,
                'is_correct' => $isCorrect,
            ];
        }

        $point = max(0, $correctCount * self::POINT_PER_Q);
        $point = min($point, self::MAX_POINT);

        Db::name('qxl_quiz_log')->insertGetId([
            'user_id'       => $this->user_id,
            'quiz_date'     => $today,
            'total_count'   => $totalCount,
            'correct_count' => $correctCount,
            'point'         => $point,
            'detail'        => json_encode($detail, JSON_UNESCAPED_UNICODE),
            'create_time'   => time(),
        ]);

        if ($point > 0) {
            AccountLogModel::add_account_log(
                $this->user_id,
                2,    // account_type: 积分
                201,
                $point,
                ['remark' => '七贤岭答题答对 ' . $correctCount . ' 题']
            );
        }

        $this->success('提交成功', [
            'correct_count'  => $correctCount,
            'total_count'    => $totalCount,
            'point'          => $point,
            'answers_detail' => $detail,
        ]);
    }

    /**
     * 检查今日是否已答题
     * GET /wxapp/qxl_quiz/check_today
     */
    public function check_today()
    {
        $this->checkUser();
        $today = date('Y-m-d');

        $log = Db::name('qxl_quiz_log')
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
