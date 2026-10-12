<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\service\QxlIntroExperienceService;
use api\wxapp\service\QxlLearnModuleService;
use think\facade\Db;

/**
 * 七贤岭考试 - 小程序接口
 * 获取题目 / 提交答案 / 状态检查（独立于垃圾分类的 QuizController）
 */
class QxlQuizController extends AuthController
{
    const QUIZ_LIMIT  = 5;    // 每次随机出的题数（旧版每日考）
    const POINT_PER_Q = 2;    // 每题积分
    const MAX_POINT   = 10;   // 最多积分

    /**
     * 提交某一篇的学考测验
     * POST /wxapp/qxl_quiz/submit_chapter
     * 参数：knowledge_id, answers [{qid, answer}]
     * 本篇全对才算过关，过关后解锁下一篇；未过关可立即重考。首次过关按题发积分。
     */
    public function submit_chapter()
    {
        $this->checkUser();

        $rawBody = file_get_contents('php://input');
        $rawParams = $rawBody ? json_decode($rawBody, true) : [];
        if (!is_array($rawParams)) {
            $rawParams = [];
        }

        $knowledgeId = intval($this->request->param('knowledge_id', 0));
        if ($knowledgeId <= 0 && !empty($rawParams['knowledge_id'])) {
            $knowledgeId = intval($rawParams['knowledge_id']);
        }
        if ($knowledgeId <= 0) {
            $this->error('请指定学习篇章');
        }

        $article = Db::name('qxl_knowledge')->where('id', $knowledgeId)->where('status', 1)->find();
        if (empty($article)) {
            $this->error('学习内容不存在');
        }

        $existLog = Db::name('qxl_chapter_log')
            ->where('user_id', $this->user_id)
            ->where('knowledge_id', $knowledgeId)
            ->find();
        if ($existLog && intval($existLog['passed']) === 1) {
            $this->success('本篇已过关', [
                'passed'         => true,
                'already_passed' => true,
                'correct_count'  => intval($existLog['correct_count']),
                'total_count'    => intval($existLog['total_count']),
                'point'          => 0,
                'answers_detail' => [],
            ]);
        }

        $answers = $this->parseAnswers($rawParams);
        if (empty($answers)) {
            $this->error('请完成本篇测验');
        }

        $bank = Db::name('qxl_quiz_question')
            ->where('knowledge_id', $knowledgeId)
            ->where('status', 1)
            ->column('correct_option', 'id');
        if (empty($bank)) {
            $this->error('本篇暂未配置题目');
        }

        $correctCount = 0;
        $detail = [];
        $answeredIds = [];
        foreach ($answers as $ans) {
            $qid = intval($ans['qid'] ?? 0);
            $answer = strtoupper(trim($ans['answer'] ?? ''));
            if ($qid <= 0 || !isset($bank[$qid])) {
                $this->error('题目不属于本篇学习内容');
            }
            $correctOption = strtoupper($bank[$qid]);
            $isCorrect = ($correctOption && $answer === $correctOption);
            if ($isCorrect) {
                $correctCount++;
            }
            $answeredIds[$qid] = true;
            $detail[] = [
                'qid'        => $qid,
                'answer'     => $answer,
                'correct'    => $correctOption,
                'is_correct' => $isCorrect,
            ];
        }

        if (count($answeredIds) < count($bank)) {
            $this->error('请答完本篇全部题目');
        }

        $totalCount = count($bank);
        $passed = ($correctCount === $totalCount);
        $point = 0;
        if ($passed && empty($existLog)) {
            $point = $correctCount * self::POINT_PER_Q;
        } elseif ($passed && $existLog && intval($existLog['point']) === 0) {
            $point = $correctCount * self::POINT_PER_Q;
        }

        $now = time();
        $save = [
            'user_id'       => $this->user_id,
            'knowledge_id'  => $knowledgeId,
            'total_count'   => $totalCount,
            'correct_count' => $correctCount,
            'passed'        => $passed ? 1 : 0,
            'detail'        => json_encode($detail, JSON_UNESCAPED_UNICODE),
            'update_time'   => $now,
        ];
        if ($existLog) {
            if ($point > 0) {
                $save['point'] = $point;
            }
            Db::name('qxl_chapter_log')->where('id', $existLog['id'])->update($save);
        } else {
            $save['point'] = $point;
            $save['create_time'] = $now;
            Db::name('qxl_chapter_log')->insertGetId($save);
        }

        if ($point > 0) {
            AccountLogModel::add_account_log(
                $this->user_id,
                2,
                201,
                $point,
                ['remark' => '七贤岭学考过关：' . $article['title']]
            );
        }

        $this->success($passed ? '本篇已过关' : '还有错题，请对照文章再试', [
            'passed'         => $passed,
            'already_passed' => false,
            'correct_count'  => $correctCount,
            'total_count'    => $totalCount,
            'point'          => $point,
            'answers_detail' => $detail,
        ]);
    }

    /**
     * 模块随机抽题（未过关时每次调用重新抽 3 道）
     * GET /wxapp/qxl_quiz/get_module_quiz?category=
     */
    public function get_module_quiz()
    {
        $category = trim((string)$this->request->param('category', ''));
        if (!QxlLearnModuleService::isValidModuleCategory($category)) {
            $this->error('模块无效');
        }
        if (!empty($this->user_id)) {
            $passed = QxlLearnModuleService::passedModuleMap($this->user_id);
            if (isset($passed[$category])) {
                $this->success('success', [
                    'category'   => $category,
                    'passed'     => 1,
                    'questions'  => [],
                    'draw_count' => QxlLearnModuleService::MODULE_QUIZ_DRAW,
                ]);
            }
        }
        $draw = QxlLearnModuleService::MODULE_QUIZ_DRAW;
        $questions = QxlLearnModuleService::drawModuleQuestions($category, $draw);
        if ($questions === null) {
            $this->error('该模块暂无题目，请联系管理员补充');
        }
        $this->success('success', [
            'category'   => $category,
            'passed'     => 0,
            'questions'  => $questions,
            'draw_count' => count($questions),
        ]);
    }

    /**
     * 提交模块测验（3 题全对即板块过关；未过关不记历史，前端重新抽题）
     * POST /wxapp/qxl_quiz/submit_module_quiz
     */
    public function submit_module_quiz()
    {
        $this->checkUser();

        $rawBody = file_get_contents('php://input');
        $rawParams = $rawBody ? json_decode($rawBody, true) : [];
        if (!is_array($rawParams)) {
            $rawParams = [];
        }

        $category = trim((string)$this->request->param('category', ''));
        if ($category === '' && !empty($rawParams['category'])) {
            $category = trim((string)$rawParams['category']);
        }
        if (QxlIntroExperienceService::isIntroCategory($category)) {
            $this->submitIntroQuiz($rawParams, $category);
            return;
        }

        if (!QxlLearnModuleService::isValidModuleCategory($category)) {
            $this->error('请指定有效模块');
        }

        $passedMap = QxlLearnModuleService::passedModuleMap($this->user_id);
        if (isset($passedMap[$category])) {
            $this->success('本模块已过关', [
                'passed'         => true,
                'already_passed' => true,
                'correct_count'  => QxlLearnModuleService::MODULE_QUIZ_DRAW,
                'total_count'    => QxlLearnModuleService::MODULE_QUIZ_DRAW,
                'point'          => 0,
            ]);
        }

        $answers = $this->parseAnswers($rawParams);
        $draw = count($answers);
        if ($draw < 1) {
            $this->error('请答完本模块测验');
        }
        $poolSize = count(QxlLearnModuleService::questionIdsForCategory($category));
        $maxDraw = min(QxlLearnModuleService::MODULE_QUIZ_DRAW, $poolSize);
        if ($poolSize < 1) {
            $this->error('该模块暂无题目');
        }
        if ($draw > $maxDraw) {
            $this->error('题目数量不对，请刷新后重试');
        }

        $qids = [];
        foreach ($answers as $ans) {
            $qid = intval($ans['qid'] ?? 0);
            if ($qid <= 0) {
                $this->error('题目无效');
            }
            $qids[] = $qid;
        }
        if (count(array_unique($qids)) !== $draw) {
            $this->error('题目重复或数量不对');
        }

        $bank = QxlLearnModuleService::filterQuestionBankForCategory($category, $qids);
        if (count($bank) !== $draw) {
            $this->error('题目不属于本模块题库');
        }

        $correctCount = 0;
        foreach ($answers as $ans) {
            $qid = intval($ans['qid']);
            $answer = strtoupper(trim($ans['answer'] ?? ''));
            if ($answer !== '' && isset($bank[$qid]) && $answer === $bank[$qid]) {
                $correctCount++;
            }
        }

        $passed = ($correctCount === $draw);
        if (!$passed) {
            $this->success('尚未全部答对，已为你重新抽题', [
                'passed'         => false,
                'already_passed' => false,
                'correct_count'  => $correctCount,
                'total_count'    => $draw,
                'point'          => 0,
                'redraw'         => 1,
            ]);
        }

        $existLog = Db::name('qxl_module_log')
            ->where('user_id', $this->user_id)
            ->where('category', $category)
            ->find();
        $point = 0;
        if (empty($existLog) || intval($existLog['point']) === 0) {
            $point = $draw * self::POINT_PER_Q;
        }

        $now = time();
        $save = [
            'user_id'       => $this->user_id,
            'category'      => $category,
            'total_count'   => $draw,
            'correct_count' => $correctCount,
            'passed'        => 1,
            'detail'        => '',
            'update_time'   => $now,
        ];
        if ($existLog) {
            if ($point > 0) {
                $save['point'] = $point;
            }
            Db::name('qxl_module_log')->where('id', $existLog['id'])->update($save);
        } else {
            $save['point'] = $point;
            $save['create_time'] = $now;
            Db::name('qxl_module_log')->insertGetId($save);
        }

        if ($point > 0) {
            AccountLogModel::add_account_log(
                $this->user_id,
                2,
                201,
                $point,
                ['remark' => '七贤岭学考过关：' . $category]
            );
        }

        $this->success('本模块已过关', [
            'passed'         => true,
            'already_passed' => false,
            'correct_count'  => $correctCount,
            'total_count'    => $draw,
            'point'          => $point,
            'redraw'         => 0,
        ]);
    }

    /**
     * 「认识七贤岭」固定 3 题小互动
     */
    private function submitIntroQuiz(array $rawParams, $category)
    {
        if (QxlIntroExperienceService::userPassedIntro($this->user_id)) {
            $this->success('已完成认识七贤岭', [
                'passed'         => true,
                'already_passed' => true,
                'correct_count'  => QxlIntroExperienceService::expectedQuestionCount(),
                'total_count'    => QxlIntroExperienceService::expectedQuestionCount(),
                'point'          => 0,
                'redraw'         => 0,
            ]);
        }

        $answers = $this->parseAnswers($rawParams);
        $expected = QxlIntroExperienceService::expectedQuestionCount();
        if (count($answers) < $expected) {
            $this->error('请完成全部小互动题目');
        }

        $qids = [];
        foreach ($answers as $ans) {
            $qid = intval($ans['qid'] ?? 0);
            if ($qid <= 0) {
                $this->error('题目无效');
            }
            $qids[] = $qid;
        }
        if (count(array_unique($qids)) !== $expected) {
            $this->error('请答完全部题目');
        }

        $bank = QxlIntroExperienceService::answerBankForSubmitted($qids);
        if (count($bank) !== $expected) {
            $this->error('题目不属于认识七贤岭');
        }

        $correctCount = 0;
        foreach ($answers as $ans) {
            $qid = intval($ans['qid']);
            $answer = strtoupper(trim($ans['answer'] ?? ''));
            if ($answer !== '' && isset($bank[$qid]) && $answer === $bank[$qid]) {
                $correctCount++;
            }
        }

        if ($correctCount !== $expected) {
            $this->success('还有题目没选对，请结合视频内容再试', [
                'passed'         => false,
                'already_passed' => false,
                'correct_count'  => $correctCount,
                'total_count'    => $expected,
                'point'          => 0,
                'redraw'         => 0,
            ]);
        }

        $existLog = Db::name('qxl_module_log')
            ->where('user_id', $this->user_id)
            ->where('category', $category)
            ->find();
        $point = 0;
        if (empty($existLog) || intval($existLog['point']) === 0) {
            $point = $expected * self::POINT_PER_Q;
        }

        $now = time();
        $save = [
            'user_id'       => $this->user_id,
            'category'      => $category,
            'total_count'   => $expected,
            'correct_count' => $correctCount,
            'passed'        => 1,
            'detail'        => '',
            'update_time'   => $now,
        ];
        if ($existLog) {
            if ($point > 0) {
                $save['point'] = $point;
            }
            Db::name('qxl_module_log')->where('id', $existLog['id'])->update($save);
        } else {
            $save['point'] = $point;
            $save['create_time'] = $now;
            Db::name('qxl_module_log')->insertGetId($save);
        }

        if ($point > 0) {
            AccountLogModel::add_account_log(
                $this->user_id,
                2,
                201,
                $point,
                ['remark' => '认识七贤岭小互动完成']
            );
        }

        $this->success('认识七贤岭已完成', [
            'passed'         => true,
            'already_passed' => false,
            'correct_count'  => $correctCount,
            'total_count'    => $expected,
            'point'          => $point,
            'redraw'         => 0,
        ]);
    }

    private function parseAnswers($rawParams = [])
    {
        $answers = [];
        if (is_array($rawParams) && isset($rawParams['answers'])) {
            $answers = $rawParams['answers'];
        }
        if (empty($answers)) {
            $answers = $this->request->param('answers', '');
        }
        if (is_string($answers)) {
            $answers = json_decode($answers, true);
        }
        return (is_array($answers) && !empty($answers)) ? $answers : [];
    }

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
