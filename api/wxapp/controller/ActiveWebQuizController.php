<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use app\admin\model\active\ActiveModel;
use think\facade\Db;

/**
 * Web 式活动内嵌答题（按 active_id 绑定题库）
 */
class ActiveWebQuizController extends AuthController
{
    const DRAW_COUNT = 5;

    protected function loadWebQuizActive(int $activeId): array
    {
        if ($activeId <= 0) {
            $this->error('缺少活动 ID');
        }
        $active = ActiveModel::where('id', $activeId)->where('delete_time', 0)->find();
        if (empty($active)) {
            $this->error('活动不存在');
        }
        if (!ActiveModel::isWebDisplay($active)) {
            $this->error('该活动未启用 Web 式答题');
        }
        return $active->toArray();
    }

    protected function questionTable(): string
    {
        return 'active_web_quiz_question';
    }

    protected function logTable(): string
    {
        return 'active_web_quiz_log';
    }

    /**
     * GET/POST 活动答题概览 + 是否已拿过满分奖励
     */
    public function status()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $active = $this->loadWebQuizActive($activeId);

        $bankCount = (int)Db::name($this->questionTable())
            ->where('active_id', $activeId)->where('status', 1)->count();

        $rewarded = Db::name($this->logTable())
            ->where('user_id', $this->user_id)
            ->where('active_id', $activeId)
            ->where('point_awarded', '>', 0)
            ->find();

        $this->success('请求成功', [
            'active_id' => $activeId,
            'active_name' => (string)$active['name'],
            'reward_point' => (float)($active['send_point'] ?? 0),
            'draw_count' => self::DRAW_COUNT,
            'bank_count' => $bankCount,
            'has_claimed_reward' => empty($rewarded) ? 0 : 1,
            'claimed_point' => empty($rewarded) ? 0 : (float)$rewarded['point_awarded'],
        ]);
    }

    /**
     * 随机抽题（不含答案）
     */
    public function start()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $this->loadWebQuizActive($activeId);

        $rows = Db::name($this->questionTable())
            ->where('active_id', $activeId)
            ->where('status', 1)
            ->field('id,title,option_a,option_b,option_c,option_d,list_order')
            ->order('list_order asc,id asc')
            ->select()
            ->toArray();

        if (count($rows) < self::DRAW_COUNT) {
            $this->error('题库不足，请联系管理员补充题目');
        }

        shuffle($rows);
        $picked = array_slice($rows, 0, self::DRAW_COUNT);
        usort($picked, function ($a, $b) {
            return ($a['list_order'] <=> $b['list_order']) ?: ($a['id'] <=> $b['id']);
        });

        $out = [];
        foreach ($picked as $i => $q) {
            $out[] = [
                'index' => $i + 1,
                'id' => (int)$q['id'],
                'title' => (string)$q['title'],
                'options' => [
                    'A' => (string)$q['option_a'],
                    'B' => (string)$q['option_b'],
                    'C' => (string)$q['option_c'],
                    'D' => (string)$q['option_d'],
                ],
            ];
        }

        $this->success('请求成功', [
            'questions' => $out,
            'total' => self::DRAW_COUNT,
        ]);
    }

    /**
     * 单题揭晓：正确答案 + 知识点（选题后调用）
     */
    public function reveal()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $qid = (int)$this->request->param('question_id');
        $this->loadWebQuizActive($activeId);

        $row = Db::name($this->questionTable())
            ->where('id', $qid)
            ->where('active_id', $activeId)
            ->where('status', 1)
            ->field('id,correct_option,explain_text')
            ->find();
        if (empty($row)) {
            $this->error('题目不存在');
        }

        $correct = strtoupper(trim((string)$row['correct_option']));
        $this->success('请求成功', [
            'question_id' => $qid,
            'correct_option' => $correct,
            'explain' => (string)($row['explain_text'] ?? ''),
        ]);
    }

    /**
     * 交卷：首次全对发 send_point 积分（仅一次）
     * answers: [{ question_id, answer: 'A'|'B'|'C'|'D' }, ...]
     */
    public function submit()
    {
        $this->checkUser();
        $activeId = (int)$this->request->param('active_id');
        $active = $this->loadWebQuizActive($activeId);

        $answers = $this->request->param('answers', '');
        if (is_string($answers)) {
            $answers = json_decode($answers, true);
        }
        if (!is_array($answers) || count($answers) !== self::DRAW_COUNT) {
            $this->error('请提交 ' . self::DRAW_COUNT . ' 道题的答案');
        }

        $qids = [];
        foreach ($answers as $ans) {
            $qids[] = (int)($ans['question_id'] ?? $ans['qid'] ?? 0);
        }
        $qids = array_values(array_unique(array_filter($qids)));
        if (count($qids) !== self::DRAW_COUNT) {
            $this->error('题目 ID 不完整');
        }

        $questionRows = Db::name($this->questionTable())
            ->where('active_id', $activeId)
            ->where('id', 'in', $qids)
            ->where('status', 1)
            ->field('id,correct_option,explain_text,title')
            ->select()
            ->toArray();
        $questionMap = [];
        foreach ($questionRows as $qr) {
            $questionMap[(int)$qr['id']] = $qr;
        }

        if (count($questionMap) !== self::DRAW_COUNT) {
            $this->error('题目与活动不匹配');
        }

        $correctCount = 0;
        $detail = [];
        foreach ($answers as $ans) {
            $qid = (int)($ans['question_id'] ?? $ans['qid'] ?? 0);
            $pick = strtoupper(trim((string)($ans['answer'] ?? '')));
            $row = $questionMap[$qid] ?? null;
            $correct = $row ? strtoupper(trim((string)$row['correct_option'])) : '';
            $isCorrect = ($correct !== '' && $pick === $correct);
            if ($isCorrect) {
                $correctCount++;
            }
            $detail[] = [
                'question_id' => $qid,
                'answer' => $pick,
                'correct_option' => $correct,
                'is_correct' => $isCorrect ? 1 : 0,
                'explain' => $row ? (string)($row['explain_text'] ?? '') : '',
                'title' => $row ? (string)($row['title'] ?? '') : '',
            ];
        }

        $isPerfect = ($correctCount === self::DRAW_COUNT) ? 1 : 0;
        $rewardPoint = (float)($active['send_point'] ?? 0);
        $pointAwarded = 0;
        $hadRewardBefore = (bool)Db::name($this->logTable())
            ->where('user_id', $this->user_id)
            ->where('active_id', $activeId)
            ->where('point_awarded', '>', 0)
            ->find();

        if ($isPerfect && $rewardPoint > 0 && !$hadRewardBefore) {
            $pointAwarded = $rewardPoint;
            AccountLogModel::add_account_log(
                $this->user_id,
                2,
                201,
                $pointAwarded,
                ['remark' => '七贤岭知识小问答首次满分', 'active_id' => $activeId]
            );
        }

        Db::name($this->logTable())->insert([
            'user_id' => $this->user_id,
            'active_id' => $activeId,
            'correct_count' => $correctCount,
            'total_count' => self::DRAW_COUNT,
            'is_perfect' => $isPerfect,
            'point_awarded' => $pointAwarded,
            'detail' => json_encode($detail, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
        ]);

        $this->success('提交成功', [
            'correct_count' => $correctCount,
            'total_count' => self::DRAW_COUNT,
            'is_perfect' => $isPerfect,
            'point_awarded' => $pointAwarded,
            'reward_point_config' => $rewardPoint,
            'already_claimed_reward' => ($hadRewardBefore || $pointAwarded > 0) ? 1 : 0,
            'detail' => $detail,
        ]);
    }
}
