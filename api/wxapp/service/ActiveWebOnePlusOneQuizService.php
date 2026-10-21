<?php

namespace api\wxapp\service;

use think\facade\Db;

/**
 * 1+1 活动第 3 天：H5 内嵌垃圾分类单选题（不跳转小程序）
 */
class ActiveWebOnePlusOneQuizService
{
    public static function logTable(): string
    {
        return 'active_web_one_plus_one_day3_quiz';
    }

    /** @return array<int, array<string, mixed>> */
    protected static function questionBank(): array
    {
        return [
            1 => [
                'id' => 1,
                'title' => '下列物品中，属于可回收物的是？',
                'options' => [
                    ['key' => 'A', 'text' => '用过的纸巾'],
                    ['key' => 'B', 'text' => '喝完的易拉罐'],
                    ['key' => 'C', 'text' => '剩菜剩饭'],
                    ['key' => 'D', 'text' => '过期药品'],
                ],
                'answer' => 'B',
                'explain' => '易拉罐属于金属制品，可回收再利用。用过的纸巾属于其他垃圾；剩菜剩饭是厨余垃圾；过期药品是有害垃圾。',
            ],
            2 => [
                'id' => 2,
                'title' => '废旧电池（干电池）应该投放到哪个颜色的垃圾桶？',
                'options' => [
                    ['key' => 'A', 'text' => '蓝色'],
                    ['key' => 'B', 'text' => '绿色'],
                    ['key' => 'C', 'text' => '红色'],
                    ['key' => 'D', 'text' => '灰色'],
                ],
                'answer' => 'C',
                'explain' => '红色垃圾桶用于投放有害垃圾。干电池、废灯管、过期药品等含有有害物质，需单独回收处理。',
            ],
            3 => [
                'id' => 3,
                'title' => '下列选项中，哪个不属于可回收垃圾？',
                'options' => [
                    ['key' => 'A', 'text' => '旧报纸'],
                    ['key' => 'B', 'text' => '玻璃'],
                    ['key' => 'C', 'text' => '陶瓷碗'],
                    ['key' => 'D', 'text' => '易拉罐'],
                ],
                'answer' => 'C',
                'explain' => '破损陶瓷碗属于其他垃圾，不可回收再利用。旧报纸、玻璃、易拉罐（金属）均可回收。',
            ],
            4 => [
                'id' => 4,
                'title' => '过期药品应该怎么扔？',
                'options' => [
                    ['key' => 'A', 'text' => '冲入下水道'],
                    ['key' => 'B', 'text' => '投入厨余垃圾桶'],
                    ['key' => 'C', 'text' => '连同包装一起投入有害垃圾箱'],
                    ['key' => 'D', 'text' => '投入可回收物蓝桶'],
                ],
                'answer' => 'C',
                'explain' => '所有内服、外用的过期药品及其药板、药膏、内包装，均属于有害垃圾，应一并投入有害垃圾桶。',
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function questionsForClient(): array
    {
        $out = [];
        foreach (self::questionBank() as $q) {
            $out[] = [
                'id' => (int)$q['id'],
                'title' => (string)$q['title'],
                'options' => $q['options'],
            ];
        }
        return $out;
    }

    public static function hasPassedInWindow(int $userId, int $activeId, int $cycleNo, int $since): bool
    {
        if ($since <= 0) {
            return false;
        }
        try {
            return (int)Db::name(self::logTable())
                ->where('user_id', $userId)
                ->where('active_id', $activeId)
                ->where('cycle_no', $cycleNo)
                ->where('passed', 1)
                ->where('create_time', '>=', $since)
                ->count() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param array<int|string, string> $answers question_id => A|B|C|D
     * @return array<string, mixed>
     */
    public static function submitAnswers(int $userId, int $activeId, array $answers): array
    {
        ActiveWebOnePlusOneService::loadWebActive($activeId);
        ActiveWebOnePlusOneService::syncAndBuildStatus($userId, $activeId);
        $row = ActiveWebOnePlusOneService::findProgress($userId, $activeId);
        if (empty($row)) {
            throw new \InvalidArgumentException('进度不存在');
        }
        ActiveWebOnePlusOneService::assertCanWorkOnDay($row, 3, $userId);

        $bank = self::questionBank();
        $detail = [];
        $hasWrong = false;
        foreach ($bank as $q) {
            $qid = (int)$q['id'];
            $pick = strtoupper(trim((string)($answers[$qid] ?? $answers[(string)$qid] ?? '')));
            if (!in_array($pick, ['A', 'B', 'C', 'D'], true)) {
                throw new \InvalidArgumentException('请完成全部题目后再提交');
            }
            $ok = ($pick === $q['answer']);
            if (!$ok) {
                $hasWrong = true;
            }
            $detail[] = [
                'id' => $qid,
                'pick' => $pick,
                'correct' => $ok ? 1 : 0,
                'answer' => $q['answer'],
                'explain' => $q['explain'],
            ];
        }

        $cycleNo = (int)($row['cycle_no'] ?? 1);
        $now = time();
        Db::name(self::logTable())->insert([
            'user_id' => $userId,
            'active_id' => $activeId,
            'cycle_no' => $cycleNo,
            'passed' => 1,
            'detail' => json_encode($detail, JSON_UNESCAPED_UNICODE),
            'create_time' => $now,
        ]);

        $status = ActiveWebOnePlusOneService::syncAndBuildStatus($userId, $activeId);

        return [
            'passed' => 1,
            'has_wrong' => $hasWrong ? 1 : 0,
            'detail' => $detail,
            'status' => $status,
        ];
    }
}
