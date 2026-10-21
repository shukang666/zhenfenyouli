<?php

namespace api\wxapp\service;

use api\wxapp\model\AccountLogModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\forum\ForumModel;
use think\facade\Db;

/**
 * Web 式活动「1+1」：7 日任务（每日真分 + 指定任务），周期与积分奖励
 */
class ActiveWebOnePlusOneService
{
    const TOTAL_DAYS = 7;

    /** 第 1～3 天格子始终可点进浏览（只读），第 4 天起须按顺序解锁浏览 */
    const BROWSE_ALWAYS_OPEN_DAYS = 3;

    const REWARD_AFTER_3 = 300;

    const REWARD_AFTER_7 = 800;

    /** 第 1 天完成后，须在 10 天内完成剩余任务，否则重置 */
    const DEADLINE_DAYS_AFTER_DAY1 = 10;

    const HARMFUL_CATE_ID = 285;

    const ACCOUNT_TYPE_1PLUS1 = 232;

    /** 运营/测试：日历 7 天均可浏览，且任意未完成天均可操作（不受「每日仅 1 天」与顺序限制） */
    private static $qaFullOpenMobiles = [
        '13050553897',
        '17604092440',
    ];

    protected static function isQaFullOpenUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        static $cache = [];
        if (array_key_exists($userId, $cache)) {
            return $cache[$userId];
        }
        $mobile = preg_replace('/\s+/', '', (string)Db::name('member')->where('id', $userId)->value('mobile'));
        $cache[$userId] = $mobile !== '' && in_array($mobile, self::$qaFullOpenMobiles, true);
        return $cache[$userId];
    }

    public static function progressTable(): string
    {
        return 'active_web_one_plus_one_progress';
    }

    public static function harmfulClockTable(): string
    {
        return 'active_web_one_plus_one_harmful_clock';
    }

    public static function day1PreLearnTable(): string
    {
        return 'active_web_one_plus_one_day1_pre_learn';
    }

    public static function hasDay1PreLearnInWindow(int $userId, int $activeId, int $cycleNo, int $since): bool
    {
        if ($since <= 0) {
            return false;
        }
        try {
            return (int)Db::name(self::day1PreLearnTable())
                ->where('user_id', $userId)
                ->where('active_id', $activeId)
                ->where('cycle_no', $cycleNo)
                ->where('create_time', '>=', $since)
                ->count() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 第 1 天：小程序 pre_learn 学考完成后回写
     *
     * @return array<string, mixed>
     */
    public static function recordDay1PreLearnCompletion(int $userId, int $activeId): array
    {
        self::loadWebActive($activeId);
        self::syncAndBuildStatus($userId, $activeId);
        $row = self::findProgress($userId, $activeId);
        if (empty($row)) {
            throw new \InvalidArgumentException('进度不存在');
        }
        self::assertCanWorkOnDay($row, 1, $userId);

        $cycleNo = (int)($row['cycle_no'] ?? 1);
        $since = self::dayWindowSincePublic($row, 1, $userId);
        if (self::hasDay1PreLearnInWindow($userId, $activeId, $cycleNo, $since)) {
            $status = self::syncAndBuildStatus($userId, $activeId);
            return ['already' => 1, 'status' => $status];
        }

        Db::name(self::day1PreLearnTable())->insert([
            'user_id' => $userId,
            'active_id' => $activeId,
            'cycle_no' => $cycleNo,
            'create_time' => time(),
        ]);

        $status = self::syncAndBuildStatus($userId, $activeId);

        return ['already' => 0, 'status' => $status];
    }

    /** @return array<int, array<string, mixed>> */
    public static function dayDefinitions(): array
    {
        return [
            1 => [
                'title' => '第 1 天',
                'task_title' => '真分前置学习',
                'task_brief' => '完成真分前的分类学习与练一练',
                'task_detail' => '阅读分类知识并完成练一练题目。通过后即打通真分打卡前置条件（老用户也可再学一遍）；完成本项即视为第 1 天挑战完成。',
                'task_kind' => 'pre_learn_mp',
                'mp_path' => '/pages/flhd/pre_learn?from=one_plus_one',
            ],
            2 => [
                'title' => '第 2 天',
                'task_title' => '发布闲置',
                'task_brief' => '上传一个闲置物品',
                'task_detail' => '在积分权益 · 社区闲置中发布一条闲置信息。',
                'mp_path' => '/pages/points/secondhand-publish',
            ],
            3 => [
                'title' => '第 3 天',
                'task_title' => '打卡可回收',
                'task_brief' => '完成一次可回收物真分打卡',
                'task_detail' => '在家庭真分中选择可回收类垃圾种类，拍照提交真分打卡；或前往小程序垃圾分类打卡并完成一次可回收投放记录。',
                'mp_path' => '/pages/activity/scene-clock-flow?mode=theme',
            ],
            4 => [
                'title' => '第 4 天',
                'task_title' => '七贤岭美景',
                'task_brief' => '分享一张美景（七贤岭内）',
                'task_detail' => '在「我所了解的七贤岭」活动中上传一张七贤岭内拍摄的美景照片。',
                'mp_path' => '/pages/h5/h5?url=' . rawurlencode('https://admin.circular-economy.cn/static/mp-active-web/qxl-photo-share/index.html'),
            ],
            5 => [
                'title' => '第 5 天',
                'task_title' => '分享小程序',
                'task_brief' => '分享小程序给好友',
                'task_detail' => '点击右上角或下方按钮，将小程序分享给至少一位好友。分享成功后返回本页刷新即可。',
                'mp_path' => '/pages/index/index',
                'needs_share_ack' => 1,
            ],
            6 => [
                'title' => '第 6 天',
                'task_title' => '分类设施',
                'task_brief' => '拍摄分类垃圾箱/桶',
                'task_detail' => '真分打卡时拍摄厨余、可回收、其他等分类桶/箱。',
                'mp_path' => '/pages/activity/scene-clock-flow?mode=theme',
            ],
            7 => [
                'title' => '第 7 天',
                'task_title' => '点位巡查',
                'task_brief' => '完成一次点位巡查',
                'task_detail' => '在小程序「点位巡查 / 督导监测」中提交一次巡查记录。',
                'mp_path' => '/pages/dtsh/supervision-monitor',
            ],
        ];
    }

    public static function loadWebActive(int $activeId): array
    {
        if ($activeId <= 0) {
            throw new \InvalidArgumentException('缺少活动 ID');
        }
        $active = ActiveModel::where('id', $activeId)->where('delete_time', 0)->find();
        if (empty($active)) {
            throw new \InvalidArgumentException('活动不存在');
        }
        if (!ActiveModel::isWebDisplay($active)) {
            throw new \InvalidArgumentException('该活动未启用 Web 式页面');
        }
        return $active->toArray();
    }

    public static function findProgress(int $userId, int $activeId): ?array
    {
        $row = Db::name(self::progressTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->find();
        return empty($row) ? null : $row;
    }

    /**
     * @return array<string, mixed>
     */
    public static function syncAndBuildStatus(int $userId, int $activeId): array
    {
        self::loadWebActive($activeId);
        $row = self::findProgress($userId, $activeId);
        if (empty($row)) {
            $now = time();
            Db::name(self::progressTable())->insert([
                'user_id' => $userId,
                'active_id' => $activeId,
                'cycle_no' => 1,
                'cycle_start' => $now,
                'anchor_day1_at' => 0,
                'deadline_at' => 0,
                'completed_days' => '',
                'zhenfen_map' => '{}',
                'day_done_at' => '{}',
                'share_ack_at' => 0,
                'reward_300_at' => 0,
                'reward_800_at' => 0,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            $row = self::findProgress($userId, $activeId);
        }

        $row = self::applyDeadlineReset($userId, $activeId, $row);
        $row = self::backfillDayDoneAt($row);
        $row = self::syncZhenfenAssignments($userId, $row);
        $row = self::syncDayCompletions($userId, $activeId, $row);
        $row = self::maybeGrantRewards($userId, $activeId, $row);
        $row = self::maybeResetAfterAllDone($userId, $activeId, $row);
        $row = self::findProgress($userId, $activeId) ?: $row;

        return self::formatStatus($userId, $activeId, $row);
    }

    /**
     * @return array<string, mixed>
     */
    public static function submitHarmfulClock(int $userId, int $activeId, string $image, string $remark): array
    {
        self::loadWebActive($activeId);
        $image = trim($image);
        $remark = trim($remark);
        if ($image === '') {
            throw new \InvalidArgumentException('请上传有害垃圾照片');
        }
        if (mb_strlen($remark, 'UTF-8') > 200) {
            throw new \InvalidArgumentException('说明不超过 200 字');
        }

        self::syncAndBuildStatus($userId, $activeId);
        $row = self::findProgress($userId, $activeId);
        if (empty($row)) {
            throw new \InvalidArgumentException('进度初始化失败');
        }
        self::assertCanWorkOnDay($row, 1, $userId);

        $cycleNo = (int)($row['cycle_no'] ?? 1);
        $now = time();
        $id = (int)Db::name(self::harmfulClockTable())->insertGetId([
            'active_id' => $activeId,
            'user_id' => $userId,
            'cycle_no' => $cycleNo,
            'image' => $image,
            'remark' => $remark,
            'create_time' => $now,
        ]);

        self::syncAndBuildStatus($userId, $activeId);

        return [
            'id' => $id,
            'image' => cmf_get_asset_url($image),
            'remark' => $remark,
            'create_time' => $now,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function listHarmfulClocks(int $userId, int $activeId, int $limit = 30): array
    {
        self::loadWebActive($activeId);
        $rows = Db::name(self::harmfulClockTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->order('id desc')
            ->limit(max(1, min(50, $limit)))
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int)$r['id'],
                'cycle_no' => (int)$r['cycle_no'],
                'image' => empty($r['image']) ? '' : cmf_get_asset_url($r['image']),
                'remark' => (string)($r['remark'] ?? ''),
                'create_time' => (int)$r['create_time'],
            ];
        }
        return $out;
    }

    protected static function hasHarmfulWebClock(int $userId, int $activeId, array $row, int $since): bool
    {
        $cycleNo = (int)($row['cycle_no'] ?? 1);
        if ($since <= 0) {
            return false;
        }
        return (int)Db::name(self::harmfulClockTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->where('cycle_no', $cycleNo)
            ->where('create_time', '>=', $since)
            ->count() > 0;
    }

    /** @return array<int, int> day => unix ts */
    protected static function decodeDayDoneAt(string $json): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $k => $v) {
            $d = (int)$k;
            $t = (int)$v;
            if ($d >= 1 && $d <= self::TOTAL_DAYS && $t > 0) {
                $out[$d] = $t;
            }
        }
        return $out;
    }

    protected static function encodeDayDoneAt(array $map): string
    {
        ksort($map);
        return json_encode($map, JSON_UNESCAPED_UNICODE);
    }

    /** 供子模块判断任务时间窗 */
    public static function dayWindowSincePublic(array $row, int $day, int $userId = 0): int
    {
        return self::dayWindowSince($row, $day, $userId);
    }

    /** 当日任务/真分只统计该时间点之后的行为（第 1 天从 cycle_start 起） */
    protected static function dayWindowSince(array $row, int $day, int $userId = 0): int
    {
        if ($day <= 1) {
            return (int)($row['cycle_start'] ?? 0);
        }
        $doneAt = self::decodeDayDoneAt((string)($row['day_done_at'] ?? '{}'));
        $prev = $day - 1;
        if (isset($doneAt[$prev])) {
            return (int)$doneAt[$prev];
        }
        if ($userId > 0 && self::isQaFullOpenUser($userId)) {
            return (int)($row['cycle_start'] ?? 0);
        }
        return 0;
    }

    protected static function todayStartTs(): int
    {
        return strtotime(date('Y-m-d 00:00:00'));
    }

    protected static function firstIncompleteDay(array $completedFlip): int
    {
        for ($d = 1; $d <= self::TOTAL_DAYS; $d++) {
            if (empty($completedFlip[$d])) {
                return $d;
            }
        }
        return self::TOTAL_DAYS + 1;
    }

    /** 第 1 天为前置学习时：整格完成不要求单独真分打卡（真分从第 2 天起与任务并行） */
    protected static function dayNeedsZhenfen(int $day): bool
    {
        if ($day !== 1) {
            return true;
        }
        $def = self::dayDefinitions()[1] ?? [];
        return ($def['task_kind'] ?? '') !== 'pre_learn_mp';
    }

    /** 自然日内是否已完成某一整天的进度（真分+任务） */
    protected static function dayCompletedToday(array $row): ?int
    {
        $todayStart = self::todayStartTs();
        foreach (self::decodeDayDoneAt((string)($row['day_done_at'] ?? '{}')) as $d => $ts) {
            if ((int)$ts >= $todayStart) {
                return (int)$d;
            }
        }
        return null;
    }

    /** 第 4 天起：须按顺序解锁后才可浏览；前 3 天始终可浏览说明 */
    protected static function canBrowseDay(int $day, array $completedFlip, int $userId = 0): bool
    {
        if ($userId > 0 && self::isQaFullOpenUser($userId)) {
            return true;
        }
        if ($day <= self::BROWSE_ALWAYS_OPEN_DAYS) {
            return true;
        }
        return self::isDayUnlocked($day, $completedFlip);
    }

    /**
     * 是否可在本页提交/跳转完成当日（自然日仅允许完成 1 个进度天；做完须等次日）
     */
    protected static function canActOnDay(array $row, int $day, int $userId = 0): bool
    {
        $completedFlip = array_fill_keys(self::parseCompletedDays((string)($row['completed_days'] ?? '')), true);
        if (!empty($completedFlip[$day])) {
            return false;
        }
        if ($userId > 0 && self::isQaFullOpenUser($userId)) {
            return true;
        }
        if (!self::isDayUnlocked($day, $completedFlip)) {
            return false;
        }
        if ($day !== self::firstIncompleteDay($completedFlip)) {
            return false;
        }
        if (self::dayCompletedToday($row) !== null) {
            return false;
        }
        return true;
    }

    protected static function buildActHint(array $row, int $day, array $completedFlip, int $userId = 0): string
    {
        if (self::canActOnDay($row, $day, $userId)) {
            return '';
        }
        if (!empty($completedFlip[$day])) {
            return '本日已全部完成';
        }
        if (!self::isDayUnlocked($day, $completedFlip)) {
            if ($day <= self::BROWSE_ALWAYS_OPEN_DAYS) {
                $prev = $day - 1;
                $prevLabel = ($prev === 1 && !self::dayNeedsZhenfen(1)) ? '前置学习' : '真分+任务';
                return '请先完成第 ' . $prev . ' 天（' . $prevLabel . '）；可先浏览本日说明，暂不可操作';
            }
            return '请先完成第 ' . ($day - 1) . ' 天后再解锁本日';
        }
        $next = self::firstIncompleteDay($completedFlip);
        if ($day !== $next) {
            return '请先完成第 ' . $next . ' 天（可先浏览后续天数说明）';
        }
        $doneToday = self::dayCompletedToday($row);
        if ($doneToday !== null) {
            return '今日已完成第 ' . $doneToday . ' 天，请明天再来做第 ' . ($doneToday + 1) . ' 天';
        }
        return '当前暂不可操作';
    }

    /** 未解锁或该日已完成则不可再做 */
    public static function assertCanWorkOnDay(array $row, int $day, int $userId = 0): void
    {
        if (!self::canActOnDay($row, $day, $userId)) {
            $completedFlip = array_fill_keys(self::parseCompletedDays((string)($row['completed_days'] ?? '')), true);
            throw new \InvalidArgumentException(self::buildActHint($row, $day, $completedFlip, $userId) ?: '当前不可进行该天任务');
        }
    }

    public static function markShareAck(int $userId, int $activeId): void
    {
        self::loadWebActive($activeId);
        self::syncAndBuildStatus($userId, $activeId);
        $row = self::findProgress($userId, $activeId);
        if (empty($row)) {
            throw new \InvalidArgumentException('进度不存在');
        }
        self::assertCanWorkOnDay($row, 5, $userId);
        Db::name(self::progressTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->update([
                'share_ack_at' => time(),
                'update_time' => time(),
            ]);
        self::syncAndBuildStatus($userId, $activeId);
    }

    protected static function parseCompletedDays(string $csv): array
    {
        $csv = trim($csv);
        if ($csv === '') {
            return [];
        }
        $out = [];
        foreach (explode(',', $csv) as $p) {
            $d = (int)$p;
            if ($d >= 1 && $d <= self::TOTAL_DAYS) {
                $out[$d] = true;
            }
        }
        ksort($out);
        return array_keys($out);
    }

    protected static function completedDaysToCsv(array $days): string
    {
        $days = array_values(array_unique(array_map('intval', $days)));
        sort($days);
        $days = array_filter($days, function ($d) {
            return $d >= 1 && $d <= self::TOTAL_DAYS;
        });
        return implode(',', $days);
    }

    /** @return array<string, int> */
    protected static function decodeZhenfenMap(string $json): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $k => $v) {
            $day = (int)$k;
            $fid = (int)$v;
            if ($day >= 1 && $day <= self::TOTAL_DAYS && $fid > 0) {
                $out[$day] = $fid;
            }
        }
        return $out;
    }

    protected static function encodeZhenfenMap(array $map): string
    {
        ksort($map);
        return json_encode($map, JSON_UNESCAPED_UNICODE);
    }

    protected static function applyDeadlineReset(int $userId, int $activeId, array $row): array
    {
        $completed = self::parseCompletedDays((string)($row['completed_days'] ?? ''));
        $anchor = (int)($row['anchor_day1_at'] ?? 0);
        $deadline = (int)($row['deadline_at'] ?? 0);
        if ($anchor <= 0 || $deadline <= 0) {
            return $row;
        }
        if (count($completed) >= self::TOTAL_DAYS) {
            return $row;
        }
        if (time() <= $deadline) {
            return $row;
        }
        self::resetCycle($userId, $activeId, $row, '已超过第 1 天起 10 日期限，进度已重置');
        return self::findProgress($userId, $activeId) ?: $row;
    }

    protected static function resetCycle(int $userId, int $activeId, array $oldRow, string $reason = ''): void
    {
        $now = time();
        $cycleNo = (int)($oldRow['cycle_no'] ?? 0) + 1;
        Db::name(self::progressTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->update([
                'cycle_no' => $cycleNo,
                'cycle_start' => $now,
                'anchor_day1_at' => 0,
                'deadline_at' => 0,
                'completed_days' => '',
                'zhenfen_map' => '{}',
                'day_done_at' => '{}',
                'share_ack_at' => 0,
                'reward_300_at' => 0,
                'reward_800_at' => 0,
                'update_time' => $now,
            ]);
    }

    /** 旧数据：已 completed_days 但无 day_done_at 时补时间戳，避免下一天窗口为 0 */
    protected static function backfillDayDoneAt(array $row): array
    {
        $completed = self::parseCompletedDays((string)($row['completed_days'] ?? ''));
        if (empty($completed)) {
            return $row;
        }
        $dayDoneAt = self::decodeDayDoneAt((string)($row['day_done_at'] ?? '{}'));
        $changed = false;
        $fallback = (int)($row['update_time'] ?? time());
        foreach ($completed as $d) {
            if (!empty($dayDoneAt[$d])) {
                continue;
            }
            if ($d === 1 && (int)($row['anchor_day1_at'] ?? 0) > 0) {
                $dayDoneAt[1] = (int)$row['anchor_day1_at'];
            } else {
                $dayDoneAt[$d] = $fallback;
            }
            $changed = true;
        }
        if (!$changed) {
            return $row;
        }
        $json = self::encodeDayDoneAt($dayDoneAt);
        Db::name(self::progressTable())->where('id', $row['id'])->update([
            'day_done_at' => $json,
            'update_time' => time(),
        ]);
        $row['day_done_at'] = $json;
        return $row;
    }

    /** 第 N 天可进入/计进度：第 1 天默认可用，其后须前一天已全部完成 */
    protected static function isDayUnlocked(int $day, array $completedFlip): bool
    {
        if ($day <= 1) {
            return true;
        }
        for ($p = 1; $p < $day; $p++) {
            if (empty($completedFlip[$p])) {
                return false;
            }
        }
        return true;
    }

    protected static function syncZhenfenAssignments(int $userId, array $row): array
    {
        $cycleStart = (int)($row['cycle_start'] ?? 0);
        if ($cycleStart <= 0) {
            return $row;
        }
        $completedFlip = array_fill_keys(self::parseCompletedDays((string)($row['completed_days'] ?? '')), true);
        $forums = ForumModel::where('user_id', $userId)
            ->where('type', 2)
            ->where('create_time', '>=', $cycleStart)
            ->where('clock_scene', '<>', '')
            ->order('id asc')
            ->field('id,create_time')
            ->select()
            ->toArray();

        $oldMap = self::decodeZhenfenMap((string)($row['zhenfen_map'] ?? '{}'));
        $map = [];
        $usedForumIds = array_values($oldMap);
        for ($day = 1; $day <= self::TOTAL_DAYS; $day++) {
            if (!empty($completedFlip[$day])) {
                if (!empty($oldMap[$day])) {
                    $map[$day] = (int)$oldMap[$day];
                }
                continue;
            }
            if (!self::canActOnDay($row, $day, $userId)) {
                break;
            }
            $since = self::dayWindowSince($row, $day, $userId);
            if ($since <= 0) {
                break;
            }
            if (!empty($oldMap[$day])) {
                $map[$day] = (int)$oldMap[$day];
                continue;
            }
            foreach ($forums as $f) {
                $fid = (int)$f['id'];
                if (in_array($fid, $usedForumIds, true)) {
                    continue;
                }
                if ((int)$f['create_time'] >= $since) {
                    $map[$day] = $fid;
                    $usedForumIds[] = $fid;
                    break;
                }
            }
        }

        $newJson = self::encodeZhenfenMap($map);
        if ($newJson !== (string)($row['zhenfen_map'] ?? '')) {
            Db::name(self::progressTable())
                ->where('id', $row['id'])
                ->update(['zhenfen_map' => $newJson, 'update_time' => time()]);
            $row['zhenfen_map'] = $newJson;
        }
        return $row;
    }

    protected static function isTaskDone(int $userId, int $activeId, int $day, array $row): bool
    {
        $completedFlip = array_fill_keys(self::parseCompletedDays((string)($row['completed_days'] ?? '')), true);
        if (!empty($completedFlip[$day])) {
            return true;
        }
        if (!self::canActOnDay($row, $day, $userId)) {
            return false;
        }
        $since = self::dayWindowSince($row, $day, $userId);
        if ($since <= 0) {
            return false;
        }

        switch ($day) {
            case 1:
                if (self::hasDay1PreLearnInWindow(
                    $userId,
                    $activeId,
                    (int)($row['cycle_no'] ?? 1),
                    $since
                )) {
                    return true;
                }
                return self::hasHarmfulWebClock($userId, $activeId, $row, $since);
            case 2:
                return (int)Db::name(SecondhandService::TABLE_ITEM)
                    ->where('user_id', $userId)
                    ->where('create_time', '>=', $since)
                    ->count() > 0;
            case 3:
                return self::countRecycleForumClock($userId, $since) > 0;
            case 4:
                return (int)Db::name(ActiveWebPhotoService::photoTable())
                    ->where('user_id', $userId)
                    ->where('create_time', '>=', $since)
                    ->count() > 0;
            case 5:
                if ((int)($row['share_ack_at'] ?? 0) >= $since) {
                    return true;
                }
                return (int)Db::name('user_account_log')
                    ->where('user_id', $userId)
                    ->where('account_type', 2)
                    ->where('type', 223)
                    ->where('create_time', '>=', $since)
                    ->count() > 0;
            case 6:
                return self::countForumClock($userId, $since, ['cate_not' => self::HARMFUL_CATE_ID]) > 0;
            case 7:
                if ((int)Db::name('supervision_monitor')->where('user_id', $userId)->where('create_time', '>=', $since)->count() > 0) {
                    return true;
                }
                return (int)ForumModel::where('user_id', $userId)->where('type', 3)->where('create_time', '>=', $since)->count() > 0;
            default:
                return false;
        }
    }

    protected static function countForumClock(int $userId, int $since, array $opts): int
    {
        $q = ForumModel::where('user_id', $userId)->where('type', 2)->where('create_time', '>=', $since);
        if (!empty($opts['cate_id'])) {
            $q->where('cate_id', (int)$opts['cate_id']);
        }
        if (!empty($opts['cate_not'])) {
            $q->where('cate_id', '<>', (int)$opts['cate_not']);
        }
        return (int)$q->count();
    }

    /** 垃圾打卡 type=2，种类为可回收（basic_select type=2 名称含「可回收」且不含「不可」） */
    protected static function countRecycleForumClock(int $userId, int $since): int
    {
        $cateIds = Db::name('basic_select')
            ->where('type', 2)
            ->where('status', 1)
            ->where('name', 'like', '%可回收%')
            ->where('name', 'not like', '%不可%')
            ->column('id');
        if (empty($cateIds)) {
            return 0;
        }
        return (int)ForumModel::where('user_id', $userId)
            ->where('type', 2)
            ->where('create_time', '>=', $since)
            ->whereIn('cate_id', $cateIds)
            ->count();
    }

    protected static function syncDayCompletions(int $userId, int $activeId, array $row): array
    {
        $cycleStart = (int)($row['cycle_start'] ?? 0);
        $map = self::decodeZhenfenMap((string)($row['zhenfen_map'] ?? '{}'));
        $completed = self::parseCompletedDays((string)($row['completed_days'] ?? ''));
        $completedFlip = array_fill_keys($completed, true);

        $dayDoneAt = self::decodeDayDoneAt((string)($row['day_done_at'] ?? '{}'));
        $changed = false;
        for ($day = 1; $day <= self::TOTAL_DAYS; $day++) {
            if (isset($completedFlip[$day])) {
                continue;
            }
            if (!self::canActOnDay($row, $day, $userId)) {
                continue;
            }
            $hasZ = isset($map[$day]);
            $hasT = self::isTaskDone($userId, $activeId, $day, $row);
            $dayComplete = self::dayNeedsZhenfen($day) ? ($hasZ && $hasT) : $hasT;
            if ($dayComplete) {
                $completed[] = $day;
                $completedFlip[$day] = true;
                $nowDone = time();
                $dayDoneAt[$day] = $nowDone;
                $changed = true;
                if ($day === 1 && (int)($row['anchor_day1_at'] ?? 0) <= 0) {
                    $row['anchor_day1_at'] = $nowDone;
                    $row['deadline_at'] = $nowDone + self::DEADLINE_DAYS_AFTER_DAY1 * 86400;
                }
            }
        }

        if (!$changed) {
            return $row;
        }

        sort($completed);
        $update = [
            'completed_days' => self::completedDaysToCsv($completed),
            'day_done_at' => self::encodeDayDoneAt($dayDoneAt),
            'update_time' => time(),
        ];
        if ((int)($row['anchor_day1_at'] ?? 0) > 0) {
            $update['anchor_day1_at'] = (int)$row['anchor_day1_at'];
            $update['deadline_at'] = (int)$row['deadline_at'];
        }
        Db::name(self::progressTable())->where('id', $row['id'])->update($update);
        return array_merge($row, $update);
    }

    protected static function maybeGrantRewards(int $userId, int $activeId, array $row): array
    {
        $completed = self::parseCompletedDays((string)($row['completed_days'] ?? ''));
        $count = count($completed);
        $now = time();

        if ($count >= 3 && (int)($row['reward_300_at'] ?? 0) <= 0) {
            AccountLogModel::add_account_log($userId, 2, self::ACCOUNT_TYPE_1PLUS1, self::REWARD_AFTER_3, [
                'active_id' => $activeId,
                'milestone' => 3,
                'remark' => '1+1活动完成前3天奖励',
            ]);
            Db::name(self::progressTable())->where('id', $row['id'])->update(['reward_300_at' => $now, 'update_time' => $now]);
            $row['reward_300_at'] = $now;
        }

        if ($count >= self::TOTAL_DAYS && (int)($row['reward_800_at'] ?? 0) <= 0) {
            AccountLogModel::add_account_log($userId, 2, self::ACCOUNT_TYPE_1PLUS1, self::REWARD_AFTER_7, [
                'active_id' => $activeId,
                'milestone' => 7,
                'remark' => '1+1活动完成7天奖励',
            ]);
            Db::name(self::progressTable())->where('id', $row['id'])->update(['reward_800_at' => $now, 'update_time' => $now]);
            $row['reward_800_at'] = $now;
        }

        return $row;
    }

    protected static function maybeResetAfterAllDone(int $userId, int $activeId, array $row): array
    {
        $completed = self::parseCompletedDays((string)($row['completed_days'] ?? ''));
        if (count($completed) < self::TOTAL_DAYS) {
            return $row;
        }
        if ((int)($row['reward_800_at'] ?? 0) <= 0) {
            return $row;
        }
        self::resetCycle($userId, $activeId, $row, '7 天已全部完成，开启新一轮');
        return self::findProgress($userId, $activeId) ?: $row;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function formatStatus(int $userId, int $activeId, array $row): array
    {
        $defs = self::dayDefinitions();
        $cycleStart = (int)($row['cycle_start'] ?? 0);
        $map = self::decodeZhenfenMap((string)($row['zhenfen_map'] ?? '{}'));
        $completed = self::parseCompletedDays((string)($row['completed_days'] ?? ''));
        $completedFlip = array_fill_keys($completed, true);

        $days = [];
        for ($d = 1; $d <= self::TOTAL_DAYS; $d++) {
            $def = $defs[$d];
            $canBrowse = self::canBrowseDay($d, $completedFlip, $userId);
            $canAct = self::canActOnDay($row, $d, $userId);
            $done = isset($completedFlip[$d]);
            $taskDone = $done ? 1 : (self::isTaskDone($userId, $activeId, $d, $row) ? 1 : 0);
            $taskOnlyDay = ($d === 1 && !self::dayNeedsZhenfen(1));
            $zhenfenDone = $taskOnlyDay ? 0 : (isset($map[$d]) && ($done || $canAct) ? 1 : 0);
            $lockHint = '';
            if (!$canBrowse) {
                $lockHint = '请先完成第 ' . ($d - 1) . ' 天后再解锁本日';
            }
            $actHint = self::buildActHint($row, $d, $completedFlip, $userId);
            $mpPath = (string)($def['mp_path'] ?? '');
            if ($d === 1 && ($def['task_kind'] ?? '') === 'pre_learn_mp' && $mpPath !== '') {
                $mpPath .= (strpos($mpPath, '?') !== false ? '&' : '?') . 'active_id=' . $activeId;
            }
            $days[] = [
                'day' => $d,
                'title' => $def['title'],
                'task_title' => $def['task_title'],
                'task_brief' => $def['task_brief'],
                'task_detail' => $def['task_detail'],
                'mp_path' => $mpPath,
                'zhenfen_done' => $zhenfenDone,
                'task_done' => $taskDone,
                'done' => $done ? 1 : 0,
                'locked' => $canBrowse ? 0 : 1,
                'lock_hint' => $lockHint,
                'can_act' => $canAct ? 1 : 0,
                'read_only' => ($canBrowse && !$canAct && !$done) ? 1 : 0,
                'act_hint' => $actHint,
                'needs_share_ack' => !empty($def['needs_share_ack']) ? 1 : 0,
                'task_kind' => (string)($def['task_kind'] ?? ''),
                'task_only' => $taskOnlyDay ? 1 : 0,
            ];
        }

        $deadline = (int)($row['deadline_at'] ?? 0);
        $anchor = (int)($row['anchor_day1_at'] ?? 0);
        $deadlineHint = '';
        if ($anchor > 0 && count($completed) < self::TOTAL_DAYS && $deadline > 0) {
            $left = max(0, $deadline - time());
            $deadlineHint = '第 1 天已完成：请在 ' . date('n月j日 H:i', $deadline) . ' 前完成剩余任务（还剩约 ' . (int)ceil($left / 86400) . ' 天）';
        }

        return [
            'active_id' => $activeId,
            'cycle_no' => (int)($row['cycle_no'] ?? 1),
            'cycle_start' => $cycleStart,
            'completed_count' => count($completed),
            'completed_days' => $completed,
            'deadline_at' => $deadline,
            'deadline_hint' => $deadlineHint,
            'reward_300_granted' => (int)($row['reward_300_at'] ?? 0) > 0 ? 1 : 0,
            'reward_800_granted' => (int)($row['reward_800_at'] ?? 0) > 0 ? 1 : 0,
            'rewards' => [
                'milestone_3' => self::REWARD_AFTER_3,
                'milestone_7' => self::REWARD_AFTER_7,
            ],
            'days' => $days,
            'guide' => [
                'title' => '1+1 七日挑战',
                'lines' => [
                    '第 1 天完成真分前置学习；第 2 天起每天真分 + 当日任务即挑战完成。',
                    '完成 3 次挑战赠送 ' . self::REWARD_AFTER_3 . ' 积分；7 次全部完成再送 ' . self::REWARD_AFTER_7 . ' 积分。',
                    '开始第一次挑战后，每 10 天会重置，请在 10 天内尽量多的完成，重置后可重新挑战。',
                ],
            ],
            'zhenfen_entry' => '/pages/activity/scene-clock-flow?mode=theme',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function dayDetail(int $userId, int $activeId, int $day): array
    {
        if ($day < 1 || $day > self::TOTAL_DAYS) {
            throw new \InvalidArgumentException('无效天数');
        }
        $status = self::syncAndBuildStatus($userId, $activeId);
        foreach ($status['days'] as $item) {
            if ((int)$item['day'] === $day) {
                if (!empty($item['locked'])) {
                    throw new \InvalidArgumentException($item['lock_hint'] ?: '请先完成前一天任务');
                }
                return $item;
            }
        }
        throw new \InvalidArgumentException('天数不存在');
    }
}
