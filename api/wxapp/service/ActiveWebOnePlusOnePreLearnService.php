<?php

namespace api\wxapp\service;

use think\facade\Db;

/**
 * 1+1 活动第 1 天：复用真分前置学习（小程序 pre_learn），按轮次记录完成
 */
class ActiveWebOnePlusOnePreLearnService
{
    public static function logTable(): string
    {
        return 'active_web_one_plus_one_day1_pre_learn';
    }

    public static function hasCompletedInWindow(int $userId, int $activeId, int $cycleNo, int $since): bool
    {
        if ($since <= 0) {
            return false;
        }
        return (int)Db::name(self::logTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->where('cycle_no', $cycleNo)
            ->where('create_time', '>=', $since)
            ->count() > 0;
    }

    /**
     * 用户完成 pre_learn 学考后回调，写入当日任务完成记录
     *
     * @return array<string, mixed>
     */
    public static function recordCompletion(int $userId, int $activeId): array
    {
        ActiveWebOnePlusOneService::loadWebActive($activeId);
        ActiveWebOnePlusOneService::syncAndBuildStatus($userId, $activeId);
        $row = ActiveWebOnePlusOneService::findProgress($userId, $activeId);
        if (empty($row)) {
            throw new \InvalidArgumentException('进度不存在');
        }
        ActiveWebOnePlusOneService::assertCanWorkOnDay($row, 1);

        $cycleNo = (int)($row['cycle_no'] ?? 1);
        $since = ActiveWebOnePlusOneService::dayWindowSincePublic($row, 1);
        if (self::hasCompletedInWindow($userId, $activeId, $cycleNo, $since)) {
            $status = ActiveWebOnePlusOneService::syncAndBuildStatus($userId, $activeId);
            return ['already' => 1, 'status' => $status];
        }

        Db::name(self::logTable())->insert([
            'user_id' => $userId,
            'active_id' => $activeId,
            'cycle_no' => $cycleNo,
            'create_time' => time(),
        ]);

        $status = ActiveWebOnePlusOneService::syncAndBuildStatus($userId, $activeId);

        return ['already' => 0, 'status' => $status];
    }
}
