<?php

namespace api\wxapp\service;

use api\wxapp\model\AccountLogModel;
use think\facade\Db;

/**
 * 用户上传场景真分点布置照片后发放积分（首次上传该点位照片）
 */
class ClockScenePointRewardService
{
    /** @see AccountLogModel::getAccountLogTypeName account_type=2 */
    public const LOG_TYPE = 228;

    public static function perPointRewardAmount(): float
    {
        $cfg = cmf_get_option('set_config');
        $amount = isset($cfg['scene_point_setup_reward']) ? (float)$cfg['scene_point_setup_reward'] : 25.0;
        if ($amount < 0) {
            $amount = 0;
        }
        return round($amount, 2);
    }

    /**
     * 首次保存布置照片时尝试发积分
     *
     * @param array<string,mixed>|null $beforeRow 更新前记录；新增时为 null
     * @return array{granted:float,log_id:int}
     */
    public static function tryGrantAfterSave(int $pointId, int $userId, ?array $beforeRow, array $afterRow): array
    {
        $out = ['granted' => 0.0, 'log_id' => 0];
        if ($pointId <= 0 || $userId <= 0) {
            return $out;
        }
        if ((int)($afterRow['source'] ?? 0) !== 0) {
            return $out;
        }
        if ((float)($afterRow['reward_point'] ?? 0) > 0) {
            return $out;
        }
        $imgAfter = trim((string)($afterRow['point_image'] ?? ''));
        if ($imgAfter === '') {
            return $out;
        }
        $imgBefore = $beforeRow ? trim((string)($beforeRow['point_image'] ?? '')) : '';
        if ($imgBefore !== '') {
            return $out;
        }

        $amount = self::perPointRewardAmount();
        if ($amount <= 0) {
            return $out;
        }

        $hub = trim((string)($afterRow['hub_key'] ?? ''));
        $pointName = trim((string)($afterRow['point_name'] ?? ''));
        $remark = '场景点位布置';
        if ($pointName !== '') {
            $remark .= '：' . mb_substr($pointName, 0, 20);
        }

        $logId = AccountLogModel::add_account_log($userId, 2, self::LOG_TYPE, $amount, [
            'remark'               => $remark,
            'clock_scene_point_id' => $pointId,
            'hub_key'              => $hub,
        ]);
        if (!$logId) {
            return $out;
        }

        Db::name('clock_scene_point')->where('id', $pointId)->update([
            'reward_point'  => $amount,
            'reward_log_id' => (int)$logId,
            'reward_time'   => time(),
        ]);

        return ['granted' => $amount, 'log_id' => (int)$logId];
    }

    /** 从积分流水回填 reward_point（type=228 且 param 含 clock_scene_point_id） */
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
            $pointId = (int)($param['clock_scene_point_id'] ?? 0);
            if ($pointId <= 0) {
                continue;
            }
            $amount = round((float)$log['change_amount'], 2);
            $res = Db::name('clock_scene_point')
                ->where('id', $pointId)
                ->where('reward_point', 0)
                ->update([
                    'reward_point'  => $amount,
                    'reward_log_id' => (int)$log['id'],
                    'reward_time'   => (int)($log['create_time'] ?? time()),
                ]);
            if ($res) {
                $updated++;
            }
        }

        return $updated;
    }
}
