<?php

namespace api\wxapp\service;

use app\admin\model\member\MemberModel;
use think\facade\Db;

/**
 * 真分打卡 · 学考一体（内容写死，判分服务端）
 */
class ClockLearnExamService
{
    public const EXAM_PID = 1;

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

    public static function markCompleted(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        MemberModel::where('id', $userId)->update(['clock_pre_learn' => 1]);
        self::ensureExamPassRecord($userId);
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
        $passed = (int)Db::name('answer')
            ->where('user_id', $userId)
            ->where('pid', self::EXAM_PID)
            ->where('status', 3)
            ->count();
        return $passed > 0;
    }

    private static function ensureExamPassRecord(int $userId): void
    {
        $exists = Db::name('answer')
            ->where('user_id', $userId)
            ->where('pid', self::EXAM_PID)
            ->where('status', 3)
            ->find();
        if (!empty($exists)) {
            return;
        }
        $now = time();
        Db::name('answer')->insert([
            'user_id'     => $userId,
            'exam_id'     => self::EXAM_PID,
            'pid'         => self::EXAM_PID,
            'type'        => 1,
            'status'      => 3,
            'score'       => 100,
            'total_num'   => 5,
            'create_time' => $now,
        ]);
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
                'title' => '为什么要学？',
                'body'  => '学一点、练一点，能分清常见垃圾，减少投错桶，把分类真正做到日常里。',
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
                    ['key' => 'A', 'text' => '随便拍一张照片'],
                    ['key' => 'B', 'text' => '分对类、投进对的桶并拍照留证'],
                    ['key' => 'C', 'text' => '只学习不投放'],
                    ['key' => 'D', 'text' => '把垃圾集中堆在一起'],
                ],
                'answer'   => 'B',
            ],
            [
                'type'     => 'quiz',
                'id'       => 'q4',
                'tag'      => '练一练',
                'question' => '坚持真分打卡，对您有什么好处？',
                'options'  => [
                    ['key' => 'A', 'text' => '养成分类习惯，方便社区收运，还可攒积分'],
                    ['key' => 'B', 'text' => '可以随便混装'],
                    ['key' => 'C', 'text' => '不用管桶的颜色'],
                    ['key' => 'D', 'text' => '与社区环保无关'],
                ],
                'answer'   => 'A',
            ],
            [
                'type'     => 'quiz',
                'id'       => 'q5',
                'tag'      => '练一练',
                'question' => '下面哪种行为算「认真真分」？',
                'options'  => [
                    ['key' => 'A', 'text' => '厨余袋不沥干扔进可回收桶'],
                    ['key' => 'B', 'text' => '电池扔进其他垃圾'],
                    ['key' => 'C', 'text' => '干净纸箱压扁投可回收，并按要求拍照打卡'],
                    ['key' => 'D', 'text' => '所有垃圾装一袋拍张照'],
                ],
                'answer'   => 'C',
            ],
        ];
    }
}
