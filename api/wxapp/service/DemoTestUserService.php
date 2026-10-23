<?php

namespace api\wxapp\service;

use api\wxapp\model\AccountLogModel;
use app\admin\model\member\MemberModel;
use think\facade\Db;

/**
 * 现场演示专用测试账号（服务端识别，不依赖前端传参）
 *
 * 规则：
 * - 仅根据已登录 user_id 关联 cmf_member.mobile 判定，手机号列表集中在本文件维护。
 * - 不授予后台/审核/督导等管理权限；能力以 capability 开关逐项声明。
 * - isolate_points：积分流水记入 user_account_log（param.demo_test=1），不改动 member 真实余额，避免污染排行与运营统计。
 * - one_plus_one_calendar_accelerate：1+1 七日挑战可在同一自然日内按顺序连续完成各天（仍须完成前置天、仍须满足任务与审核条件）。
 * - one_plus_one_full_open：历史运营账号用，可浏览全部天数且不受「每日仅 1 天」与顺序限制（勿随意新增）。
 */
class DemoTestUserService
{
    /** @var array<string, array{capabilities: string[]}> */
    private static $profilesByMobile = [
        // 现场演示主账号：顺序加速 + 积分隔离
        '19909851865' => [
            'capabilities' => [
                'isolate_points',
                'one_plus_one_calendar_accelerate',
            ],
        ],
        // 历史运营/联调账号（保持原 1+1 全开行为，避免影响既有联调）
        '13050553897' => [
            'capabilities' => [
                'one_plus_one_full_open',
            ],
        ],
        '17604092440' => [
            'capabilities' => [
                'one_plus_one_full_open',
            ],
        ],
    ];

    /** @var array<int, string|null> userId => normalized mobile | null(未加载) */
    private static $mobileByUserId = [];

    /** @var array<int, bool> userId => is demo */
    private static $isDemoCache = [];

    public static function normalizeMobile(string $mobile): string
    {
        return preg_replace('/\s+/', '', trim($mobile));
    }

    /**
     * 从库读取并缓存绑定手机号（须为账号已验证绑定的 mobile 字段）
     */
    public static function mobileForUser(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }
        if (!array_key_exists($userId, self::$mobileByUserId)) {
            $raw = (string)Db::name('member')->where('id', $userId)->value('mobile');
            self::$mobileByUserId[$userId] = self::normalizeMobile($raw);
        }
        return (string)self::$mobileByUserId[$userId];
    }

    public static function isDemoUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if (array_key_exists($userId, self::$isDemoCache)) {
            return self::$isDemoCache[$userId];
        }
        $mobile = self::mobileForUser($userId);
        self::$isDemoCache[$userId] = $mobile !== '' && isset(self::$profilesByMobile[$mobile]);
        return self::$isDemoCache[$userId];
    }

    /** @return string[] */
    public static function capabilitiesForUser(int $userId): array
    {
        if (!self::isDemoUser($userId)) {
            return [];
        }
        $mobile = self::mobileForUser($userId);
        return self::$profilesByMobile[$mobile]['capabilities'] ?? [];
    }

    public static function hasCapability(int $userId, string $capability): bool
    {
        return in_array($capability, self::capabilitiesForUser($userId), true);
    }

    public static function shouldIsolatePoints(int $userId): bool
    {
        return self::hasCapability($userId, 'isolate_points');
    }

    public static function isOnePlusOneFullOpen(int $userId): bool
    {
        return self::hasCapability($userId, 'one_plus_one_full_open');
    }

    public static function isOnePlusOneCalendarAccelerate(int $userId): bool
    {
        return self::hasCapability($userId, 'one_plus_one_calendar_accelerate');
    }

    /**
     * 演示积分：写流水但不改 member 余额（left_amount 保持当前真实余额）
     *
     * @param array<string, mixed> $param
     */
    public static function addIsolatedPointLog(int $userId, int $type, float $change, array $param = []): bool
    {
        if ($userId <= 0 || abs($change) < 0.00001) {
            return false;
        }
        $MemberModel = new MemberModel();
        $userInfo = $MemberModel->where(['id' => $userId])->find();
        if (empty($userInfo)) {
            return false;
        }
        $change = round($change, 2);
        $oldAmount = round((float)$userInfo['point'], 2);
        $param['demo_test'] = 1;
        $remark = isset($param['remark']) ? (string)$param['remark'] : '';
        if ($remark !== '' && strpos($remark, '演示') === false) {
            $param['remark'] = $remark . '（演示不计入正式积分）';
        }
        if (!empty($param['remark'])) {
            $logRemark = (string)$param['remark'];
            unset($param['remark']);
        } else {
            $logRemark = '演示积分（不计入正式账户）';
        }
        $log = [
            'user_id' => $userId,
            'change_amount' => $change,
            'left_amount' => $oldAmount,
            'type' => $type,
            'account_type' => 2,
            'create_time' => time(),
            'remark' => $logRemark,
            'param' => json_encode($param, JSON_UNESCAPED_UNICODE),
        ];
        return (bool)(new AccountLogModel())->insert($log, true);
    }

    /**
     * 积分发放统一入口：演示账号走隔离，其余走原逻辑
     *
     * @param array<string, mixed> $param
     */
    public static function addPointLog(int $userId, int $type, float $change, array $param = []): bool
    {
        if (self::shouldIsolatePoints($userId) && $change > 0) {
            return self::addIsolatedPointLog($userId, $type, $change, $param);
        }
        return (bool)AccountLogModel::add_account_log($userId, 2, $type, $change, $param);
    }
}
