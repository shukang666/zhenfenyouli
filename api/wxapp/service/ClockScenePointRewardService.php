<?php

namespace api\wxapp\service;

use api\wxapp\controller\ClockScenePointController;
use api\wxapp\model\AccountLogModel;
use think\facade\Db;

/**
 * 家庭四处布置齐发一次积分；沿街首次布置点位发一次
 */
class ClockScenePointRewardService
{
    public const LOG_TYPE = 228;

    /** 家庭四处布置完成奖励（默认 100，与小程序文案一致） */
    public static function homeSetupRewardAmount(): float
    {
        $cfg = cmf_get_option('set_config');
        $amount = isset($cfg['scene_point_setup_reward']) ? (float)$cfg['scene_point_setup_reward'] : 100.0;
        if ($amount < 0) {
            $amount = 0;
        }
        return round($amount, 2);
    }

    /**
     * 保存点位后尝试发积分
     *
     * @param array<string,mixed>|null $beforeRow
     * @return array{granted:float,log_id:int}
     */
    public static function tryGrantAfterSave(int $pointId, int $userId, ?array $beforeRow, array $afterRow): array
    {
        $hub = trim((string)($afterRow['hub_key'] ?? ''));
        if (in_array($hub, ['home', 'qinzi'], true)) {
            return self::tryGrantHomeFixedBundle($userId);
        }
        if ($hub === 'street') {
            return self::tryGrantStreetSetupOnce($pointId, $userId, $afterRow);
        }
        return ['granted' => 0.0, 'log_id' => 0];
    }

    /** @return array{granted:float,log_id:int} */
    public static function tryGrantHomeFixedBundle(int $userId): array
    {
        $empty = ['granted' => 0.0, 'log_id' => 0];
        if ($userId <= 0) {
            return $empty;
        }
        if (!ClockScenePointController::isHomeFixedSetupComplete($userId)) {
            return $empty;
        }
        if (self::userHasHomeSetupBundleReward($userId)) {
            return $empty;
        }

        $pointIds = ClockScenePointController::homeFixedSetupPointIds($userId);
        if (count($pointIds) < 4) {
            return $empty;
        }

        $amount = self::homeSetupRewardAmount();
        if ($amount <= 0) {
            return $empty;
        }

        $logId = AccountLogModel::add_account_log($userId, 2, self::LOG_TYPE, $amount, [
            'remark'              => '家庭场景点位布置完成',
            'home_setup_bundle'   => 1,
            'clock_scene_point_ids' => $pointIds,
        ]);
        if (!$logId) {
            return $empty;
        }

        self::markPointsRewarded($pointIds, $amount, (int)$logId);
        return ['granted' => $amount, 'log_id' => (int)$logId];
    }

    /** @param array<string,mixed> $afterRow */
    protected static function tryGrantStreetSetupOnce(int $pointId, int $userId, array $afterRow): array
    {
        $empty = ['granted' => 0.0, 'log_id' => 0];
        if ($pointId <= 0 || $userId <= 0 || (int)($afterRow['source'] ?? 0) !== 0) {
            return $empty;
        }
        if (trim((string)($afterRow['point_image'] ?? '')) === '') {
            return $empty;
        }
        if ((float)($afterRow['reward_point'] ?? 0) > 0) {
            return $empty;
        }
        if (self::userHasStreetSetupReward($userId)) {
            return $empty;
        }

        $amount = self::homeSetupRewardAmount();
        if ($amount <= 0) {
            return $empty;
        }

        $logId = AccountLogModel::add_account_log($userId, 2, self::LOG_TYPE, $amount, [
            'remark'               => '沿街商铺点位布置',
            'street_setup_bundle'  => 1,
            'clock_scene_point_id' => $pointId,
        ]);
        if (!$logId) {
            return $empty;
        }

        Db::name('clock_scene_point')->where('id', $pointId)->update([
            'reward_point'  => $amount,
            'reward_log_id' => (int)$logId,
            'reward_time'   => time(),
        ]);

        return ['granted' => $amount, 'log_id' => (int)$logId];
    }

    public static function userHasHomeSetupBundleReward(int $userId): bool
    {
        if ($userId <= 0) {
            return true;
        }
        $hit = Db::name('user_account_log')
            ->where('user_id', $userId)
            ->where('account_type', 2)
            ->where('type', self::LOG_TYPE)
            ->where('param', 'like', '%"home_setup_bundle":1%')
            ->find();
        if (!empty($hit)) {
            return true;
        }
        // 已写入点位表也视为发过（防重复补发）
        $bundlePointIds = ClockScenePointController::homeFixedSetupPointIds($userId);
        if (count($bundlePointIds) >= 4) {
            $cnt = (int)Db::name('clock_scene_point')
                ->whereIn('id', $bundlePointIds)
                ->where('reward_point', '>', 0)
                ->count();
            if ($cnt >= 4) {
                return true;
            }
        }
        return false;
    }

    protected static function userHasStreetSetupReward(int $userId): bool
    {
        return !empty(Db::name('user_account_log')
            ->where('user_id', $userId)
            ->where('account_type', 2)
            ->where('type', self::LOG_TYPE)
            ->where('param', 'like', '%"street_setup_bundle":1%')
            ->find());
    }

    /** @param list<int> $pointIds */
    protected static function markPointsRewarded(array $pointIds, float $totalAmount, int $logId): void
    {
        $n = count($pointIds);
        if ($n <= 0) {
            return;
        }
        $each = round($totalAmount / $n, 2);
        $now = time();
        $assigned = 0.0;
        foreach ($pointIds as $i => $pid) {
            $part = ($i === $n - 1) ? round($totalAmount - $assigned, 2) : $each;
            $assigned += $part;
            Db::name('clock_scene_point')->where('id', (int)$pid)->update([
                'reward_point'  => $part,
                'reward_log_id' => $logId,
                'reward_time'   => $now,
            ]);
        }
    }

    /** 为已四处布置齐、尚未发家庭套装积分的用户补发 */
    public static function backfillHomeSetupBundles(int $userLimit = 5000): array
    {
        $userLimit = max(1, min($userLimit, 50000));
        $userIds = Db::name('clock_scene_point')
            ->where('source', 0)
            ->whereIn('hub_key', ['home', 'qinzi'])
            ->where('point_image', '<>', '')
            ->distinct(true)
            ->column('user_id');
        if (count($userIds) > $userLimit) {
            $userIds = array_slice($userIds, 0, $userLimit);
        }

        $grantedUsers = 0;
        $skipped = 0;
        foreach ($userIds as $uid) {
            $uid = (int)$uid;
            if ($uid <= 0) {
                continue;
            }
            if (!ClockScenePointController::isHomeFixedSetupComplete($uid)) {
                $skipped++;
                continue;
            }
            if (self::userHasHomeSetupBundleReward($uid)) {
                $skipped++;
                continue;
            }
            $res = self::tryGrantHomeFixedBundle($uid);
            if ((float)$res['granted'] > 0) {
                $grantedUsers++;
            }
        }

        return ['granted_users' => $grantedUsers, 'skipped' => $skipped];
    }

    /** 从积分流水回填 reward_point */
    public static function backfillFromAccountLogs(int $limit = 5000): int
    {
        $limit = max(1, min($limit, 20000));
        $rows = Db::name('user_account_log')
            ->where('account_type', 2)
            ->where('type', self::LOG_TYPE)
            ->where('change_amount', '>', 0)
            ->order('id asc')
            ->limit($limit)
            ->select()
            ->toArray();

        $updated = 0;
        foreach ($rows as $log) {
            $param = json_decode((string)($log['param'] ?? ''), true);
            if (!is_array($param)) {
                continue;
            }
            $amount = round((float)$log['change_amount'], 2);
            $logId = (int)$log['id'];
            $time = (int)($log['create_time'] ?? time());

            if (!empty($param['home_setup_bundle']) && !empty($param['clock_scene_point_ids']) && is_array($param['clock_scene_point_ids'])) {
                $ids = array_map('intval', $param['clock_scene_point_ids']);
                self::markPointsRewarded($ids, $amount, $logId);
                $updated++;
                continue;
            }

            $pointId = (int)($param['clock_scene_point_id'] ?? 0);
            if ($pointId <= 0) {
                continue;
            }
            $res = Db::name('clock_scene_point')
                ->where('id', $pointId)
                ->where('reward_point', 0)
                ->update([
                    'reward_point'  => $amount,
                    'reward_log_id' => $logId,
                    'reward_time'   => $time,
                ]);
            if ($res) {
                $updated++;
            }
        }

        return $updated;
    }
}
