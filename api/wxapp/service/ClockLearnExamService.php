<?php

namespace api\wxapp\service;

use api\wxapp\model\AnswerLogModel;
use api\wxapp\model\AnswerModel;
use app\admin\model\member\MemberModel;

/**
 * 真分打卡 · 学考一体（内容写死，判分服务端）
 */
class ClockLearnExamService
{
    public const EXAM_PID = 1;

    /** 学考一体固定题写入 answer_log 的 q_id（与题库表 id 错开） */
    private const SYNTHETIC_QID = [
        'q1' => 900001,
        'q2' => 900002,
        'q3' => 900003,
        'q5' => 900005,
    ];

    /** @return array<int,array<string,mixed>> */
    public static function getBlocksForClient(): array
    {
        $blocks = self::blocksWithAnswers();
        $out = [];
        foreach ($blocks as $b) {
            if (($b['type'] ?? '') === 'quiz') {
                unset($b['answer']);
            }
            $out[] = $b;
        }
        return $out;
    }

    /**
     * @param array<string,string> $answers 如 ['q1'=>'B','q2'=>'A']
     * @return array{pass:bool,wrong_ids:array<int,string>,need:int,correct:int}
     */
    public static function gradeAnswers(array $answers): array
    {
        $wrong = [];
        $total = 0;
        $correct = 0;
        foreach (self::blocksWithAnswers() as $b) {
            if (($b['type'] ?? '') !== 'quiz') {
                continue;
            }
            $id = (string)($b['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $total++;
            $expect = strtoupper(trim((string)($b['answer'] ?? '')));
            $got = strtoupper(trim((string)($answers[$id] ?? '')));
            if ($got === $expect && $expect !== '') {
                $correct++;
            } else {
                $wrong[] = $id;
            }
        }
        return [
            'pass'       => $total > 0 && count($wrong) === 0,
            'wrong_ids'  => $wrong,
            'need'       => $total,
            'correct'    => $correct,
        ];
    }

    /**
     * @param array<string,string> $answers 练一练提交答案，如 q1=>B
     */
    public static function markCompleted(int $userId, array $answers = []): void
    {
        if ($userId <= 0) {
            return;
        }
        MemberModel::where('id', $userId)->update(['clock_pre_learn' => 1]);
        self::ensureExamPassRecord($userId, $answers);
    }

    public static function isSyntheticLearnQid(int $qid): bool
    {
        return in_array($qid, self::SYNTHETIC_QID, true);
    }

    /** @return array<int,array{qid:int,quiz_key:string,title:string}> */
    public static function exportQuizColumns(): array
    {
        $out = [];
        foreach (self::blocksWithAnswers() as $b) {
            if (($b['type'] ?? '') !== 'quiz') {
                continue;
            }
            $key = (string)($b['id'] ?? '');
            $qid = self::SYNTHETIC_QID[$key] ?? 0;
            if ($qid <= 0) {
                continue;
            }
            $out[] = [
                'qid'      => $qid,
                'quiz_key' => $key,
                'title'    => (string)($b['question'] ?? $key),
            ];
        }
        return $out;
    }

    /** @return array<string,mixed>|null */
    public static function syntheticQuizMeta(int $qid): ?array
    {
        if (!self::isSyntheticLearnQid($qid)) {
            return null;
        }
        foreach (self::blocksWithAnswers() as $b) {
            if (($b['type'] ?? '') !== 'quiz') {
                continue;
            }
            $key = (string)($b['id'] ?? '');
            if ((self::SYNTHETIC_QID[$key] ?? 0) !== $qid) {
                continue;
            }
            return [
                'quiz_key'    => $key,
                'title'       => (string)($b['question'] ?? ''),
                'true_answer' => strtoupper((string)($b['answer'] ?? '')),
                'options'     => $b['options'] ?? [],
            ];
        }
        return null;
    }

    public static function isCompleted(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $learn = (int)MemberModel::where('id', $userId)->value('clock_pre_learn');
        if ($learn <= 0) {
            return false;
        }
        $answerModel = new AnswerModel();
        $passed = (int)$answerModel
            ->where('user_id', $userId)
            ->where('type', 1)
            ->where('status', 3)
            ->count();
        return $passed > 0;
    }

    /**
     * @param array<string,string> $answers
     */
    private static function ensureExamPassRecord(int $userId, array $answers = []): void
    {
        $answerModel = new AnswerModel();
        $exists = $answerModel
            ->where('user_id', $userId)
            ->where('type', 1)
            ->where('status', 3)
            ->find();
        if (!empty($exists)) {
            if (!empty($answers)) {
                self::persistLearnAnswerLogs((int)$exists['id'], $userId, $answers);
            }
            return;
        }
        $now = time();
        $answerId = (int)$answerModel->insertGetId([
            'user_id'     => $userId,
            'exam_id'     => self::EXAM_PID,
            'pid'         => self::EXAM_PID,
            'type'        => 1,
            'status'      => 3,
            'score'       => 100,
            'total_num'   => 4,
            'create_time' => $now,
        ]);
        if ($answerId > 0 && !empty($answers)) {
            self::persistLearnAnswerLogs($answerId, $userId, $answers);
        }
    }

    /**
     * @param array<string,string> $answers
     */
    private static function persistLearnAnswerLogs(int $answerId, int $userId, array $answers): void
    {
        if ($answerId <= 0 || $userId <= 0) {
            return;
        }
        $syntheticIds = array_values(self::SYNTHETIC_QID);
        $logModel = new AnswerLogModel();
        $logModel->where('answer_id', $answerId)->whereIn('q_id', $syntheticIds)->delete();

        $listNum = 0;
        $now = time();
        foreach (self::blocksWithAnswers() as $b) {
            if (($b['type'] ?? '') !== 'quiz') {
                continue;
            }
            $key = (string)($b['id'] ?? '');
            $qid = self::SYNTHETIC_QID[$key] ?? 0;
            if ($qid <= 0) {
                continue;
            }
            $expect = strtoupper(trim((string)($b['answer'] ?? '')));
            $got = strtoupper(trim((string)($answers[$key] ?? '')));
            $ok = ($got !== '' && $got === $expect);
            $listNum++;
            $logModel->insert([
                'answer_id'     => $answerId,
                'option_id'     => '',
                'user_answer'   => $got,
                'answer_status' => $ok ? 1 : 2,
                'user_id'       => $userId,
                'exam_id'       => self::EXAM_PID,
                'q_id'          => $qid,
                'true_answer'   => $expect,
                'create_time'   => $now,
                'list_num'      => $listNum,
            ]);
        }
    }

    /** @return array<int,array<string,mixed>> */
    private static function blocksWithAnswers(): array
    {
        return [
            [
                'type'     => 'hero',
                'icon'     => '🌍',
                'title'    => '欢迎来真分',
                'subtitle' => '真分不只是随手一拍，而是从投放到末端，您的认真分类让资源少浪费、社区更干净。',
            ],
            [
                'type'  => 'why',
                'title' => '为什么要学习',
                'body'  => '学习帮助你识别四类垃圾、避开常见误区，并把正确分类落实到真实行动中',
            ],
            [
                'type'  => 'steps',
                'title' => '学一点 · 考一点',
                'items' => [
                    ['icon' => '📖', 'label' => '学习知识'],
                    ['icon' => '🧠', 'label' => '提升判断'],
                    ['icon' => '🗑️', 'label' => '真实投放'],
                    ['icon' => '✅', 'label' => '开始真分'],
                ],
            ],
            [
                'type'    => 'learn',
                'id'      => 'basic',
                'icon'    => '♻️',
                'title'   => '垃圾分类基础知识',
                'bullets' => [
                    ['icon' => '✓', 'title' => '厨余垃圾', 'text' => '菜叶、果皮、剩饭剩菜、茶叶渣等，尽量沥干。'],
                    ['icon' => '✓', 'title' => '可回收物', 'text' => '纸、塑料、金属、玻璃、干净织物等，保持干燥。'],
                    ['icon' => '✓', 'title' => '有害垃圾', 'text' => '电池、灯管、过期药品等，单独收集。'],
                    ['icon' => '✓', 'title' => '其他垃圾', 'text' => '除以上三类以外的废弃物。'],
                ],
            ],
            [
                'type'     => 'quiz',
                'id'       => 'q1',
                'tag'      => '练一练',
                'question' => '剩菜、树叶、西瓜皮属于哪一类？',
                'options'  => [
                    ['key' => 'A', 'text' => '有害垃圾'],
                    ['key' => 'B', 'text' => '厨余垃圾'],
                    ['key' => 'C', 'text' => '其他垃圾'],
                    ['key' => 'D', 'text' => '可回收物'],
                ],
                'answer'   => 'B',
            ],
            [
                'type'     => 'quiz',
                'id'       => 'q2',
                'tag'      => '练一练',
                'question' => '过期药品、废电池一般属于哪一类？',
                'options'  => [
                    ['key' => 'A', 'text' => '有害垃圾'],
                    ['key' => 'B', 'text' => '厨余垃圾'],
                    ['key' => 'C', 'text' => '其他垃圾'],
                    ['key' => 'D', 'text' => '可回收物'],
                ],
                'answer'   => 'A',
            ],
            [
                'type'    => 'learn',
                'id'      => 'zhenfen',
                'icon'    => '👨‍👩‍👧',
                'title'   => '什么是真分，为什么要做',
                'bullets' => [
                    ['icon' => '•', 'title' => '真分是什么', 'text' => '按类别投放到对应桶/点，并拍照记录，表示您确实分类到位。'],
                    ['icon' => '•', 'title' => '为什么要做', 'text' => '混装难回收、易污染；分开收运更干净，也能攒积分参与社区活动。'],
                ],
            ],
            [
                'type'     => 'quiz',
                'id'       => 'q3',
                'tag'      => '练一练',
                'question' => '「真分打卡」主要是指？',
                'options'  => [
                    ['key' => 'A', 'text' => '随便拍一张'],
                    ['key' => 'B', 'text' => '分对类投桶并拍照'],
                    ['key' => 'C', 'text' => '只学不投放'],
                    ['key' => 'D', 'text' => '垃圾堆一起'],
                ],
                'answer'   => 'B',
            ],
            [
                'type'     => 'quiz',
                'id'       => 'q5',
                'tag'      => '练一练',
                'question' => '下面哪种行为算「认真真分」？',
                'options'  => [
                    ['key' => 'A', 'text' => '厨余扔可回收'],
                    ['key' => 'B', 'text' => '电池扔其他'],
                    ['key' => 'C', 'text' => '纸箱投可回收并打卡'],
                    ['key' => 'D', 'text' => '混装拍一张'],
                ],
                'answer'   => 'C',
            ],
        ];
    }
}
