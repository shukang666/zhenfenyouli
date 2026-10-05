<?php

namespace app\admin\model\active;

class ActiveSIgnModel extends \think\Model
{
  protected $name  = 'active_sign';

    /** 活动参与人数（同一用户重复报名只计 1 人） */
    public static function countParticipants(int $activeId): int
    {
        if ($activeId <= 0) {
            return 0;
        }
        return (int) self::where('active_id', $activeId)->count('DISTINCT user_id');
    }

    /**
     * 打卡类活动详情页自动报名：并发请求下避免重复插入
     */
    public static function ensureClockAutoSign(int $userId, int $activeId, array $fields): void
    {
        if ($userId <= 0 || $activeId <= 0) {
            return;
        }
        $exists = self::where(['active_id' => $activeId, 'user_id' => $userId])->find();
        if ($exists) {
            return;
        }
        self::transaction(function () use ($userId, $activeId, $fields) {
            $locked = self::where(['active_id' => $activeId, 'user_id' => $userId])->lock(true)->find();
            if ($locked) {
                return;
            }
            self::create(array_merge([
                'user_id'   => $userId,
                'active_id' => $activeId,
            ], $fields));
        });
    }
}