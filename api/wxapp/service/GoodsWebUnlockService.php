<?php

namespace api\wxapp\service;

use app\admin\model\active\ActiveModel;
use think\facade\Db;

/**
 * Web 式活动商品兑换解锁（与配置式 require_active_id 签到条件并列）
 *
 * 商品配置 require_web_unlock_key；业务在达成条件时调用 grant() 写入解锁记录。
 */
class GoodsWebUnlockService
{
    public const TABLE = 'member_goods_web_unlock';

    /** 1+1 活动：完成 3 天挑战后解锁指定商品（active_id 以实际 Web 活动为准） */
    public const ONE_PLUS_ONE_ACTIVE_ID = 57;
    public const ONE_PLUS_ONE_MILESTONE_DAYS = 3;

    public static function milestoneKey(int $activeId, int $milestone): string
    {
        return 'web_active:' . max(0, $activeId) . ':milestone:' . max(0, $milestone);
    }

    public static function onePlusOneMilestone3Key(int $activeId = self::ONE_PLUS_ONE_ACTIVE_ID): string
    {
        return self::milestoneKey($activeId, self::ONE_PLUS_ONE_MILESTONE_DAYS);
    }

    /** @return array<int,array{key:string,label:string,active_id:int}> */
    public static function presetOptionsForAdmin(): array
    {
        $activeId = self::ONE_PLUS_ONE_ACTIVE_ID;
        $name = (string)ActiveModel::where('id', $activeId)->where('delete_time', 0)->value('name');
        if ($name === '') {
            $name = 'Web活动#' . $activeId;
        }
        return [
            [
                'key'       => self::onePlusOneMilestone3Key($activeId),
                'label'     => '「' . $name . '」完成 ' . self::ONE_PLUS_ONE_MILESTONE_DAYS . ' 天挑战',
                'active_id' => $activeId,
            ],
        ];
    }

    public static function parseActiveIdFromKey(string $key): int
    {
        if (preg_match('/^web_active:(\d+):/', $key, $m)) {
            return (int)$m[1];
        }
        return 0;
    }

    public static function hasUnlock(int $userId, string $unlockKey): bool
    {
        $unlockKey = trim($unlockKey);
        if ($userId <= 0 || $unlockKey === '') {
            return false;
        }
        return (int)Db::name(self::TABLE)
                ->where('user_id', $userId)
                ->where('unlock_key', $unlockKey)
                ->count() > 0;
    }

    /**
     * 幂等授予解锁
     *
     * @param array<string,mixed> $meta
     */
    public static function grant(int $userId, string $unlockKey, array $meta = []): bool
    {
        $unlockKey = trim($unlockKey);
        if ($userId <= 0 || $unlockKey === '') {
            return false;
        }
        if (self::hasUnlock($userId, $unlockKey)) {
            return true;
        }
        $now = time();
        Db::name(self::TABLE)->insert([
            'user_id'     => $userId,
            'unlock_key'  => $unlockKey,
            'active_id'   => (int)($meta['active_id'] ?? self::parseActiveIdFromKey($unlockKey)),
            'remark'      => (string)($meta['remark'] ?? ($meta['source'] ?? '')),
            'create_time' => $now,
        ]);
        return true;
    }

    /** @param array<string,mixed>|object $goodsInfo */
    public static function userMetWebUnlock(int $userId, $goodsInfo): bool
    {
        $key = '';
        if (is_array($goodsInfo)) {
            $key = trim((string)($goodsInfo['require_web_unlock_key'] ?? ''));
        } elseif (is_object($goodsInfo)) {
            $key = trim((string)($goodsInfo['require_web_unlock_key'] ?? ''));
        }
        if ($key === '') {
            return true;
        }
        if ($userId <= 0) {
            return false;
        }
        return self::hasUnlock($userId, $key);
    }

    /** @param array<string,mixed> $goodsInfo */
    public static function blockMessage(array $goodsInfo): string
    {
        $goodsName = $goodsInfo['goods_name'] ?? '该商品';
        $label = trim((string)($goodsInfo['require_web_unlock_label'] ?? ''));
        if ($label === '') {
            $activeId = self::parseActiveIdFromKey((string)($goodsInfo['require_web_unlock_key'] ?? ''));
            $activeName = $activeId > 0
                ? ((string)ActiveModel::where('id', $activeId)->value('name') ?: '指定活动')
                : '指定活动';
            $label = '完成 Web 活动「' . $activeName . '」对应挑战后';
        }
        return $label . '方可兑换（' . $goodsName . '）';
    }

    /** 1+1：累计完成天数 ≥3 时同步商品解锁（含历史用户补发） */
    public static function syncOnePlusOneMilestone3Unlock(int $userId, int $activeId, int $completedDayCount): void
    {
        if ($userId <= 0 || $activeId <= 0 || $completedDayCount < self::ONE_PLUS_ONE_MILESTONE_DAYS) {
            return;
        }
        self::grant($userId, self::onePlusOneMilestone3Key($activeId), [
            'active_id' => $activeId,
            'source'    => 'one_plus_one_milestone_3',
            'remark'    => '1+1完成3天挑战解锁商品兑换',
        ]);
    }
}
