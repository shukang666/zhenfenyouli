<?php

namespace api\wxapp\service;

use api\wxapp\model\AccountLogModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\forum\ForumModel;
use app\admin\model\member\CommunityCateModel;
use app\admin\model\member\MemberLevelModel;
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
                'task_brief' => '阅读要点并完成练一练',
                'task_detail' => '下方完成练一练全部答对即视为第 1 天完成。',
                'task_kind' => 'pre_learn_web',
                'mp_path' => '',
            ],
            2 => [
                'title' => '第 2 天',
                'task_title' => '发布闲置',
                'task_brief' => '在本页填写并发布一条闲置',
                'task_detail' => '填写名称、价格、联系方式并上传照片，提交后进入社区闲置审核。',
                'task_kind' => 'secondhand_web',
                'mp_path' => '',
            ],
            3 => [
                'title' => '第 3 天',
                'task_title' => '打卡可回收',
                'task_brief' => '拍摄可回收物投放照片并提交即可完成本日挑战',
                'task_detail' => '',
                'task_kind' => 'recycle_clock_web',
                'mp_path' => '',
            ],
            4 => [
                'title' => '第 4 天',
                'task_title' => '七贤岭美景',
                'task_brief' => '上传一张七贤岭内美景照片',
                'task_detail' => '拍摄地点须在七贤岭范围内，附一句简要介绍。',
                'task_kind' => 'photo_web',
                'mp_path' => '',
            ],
            5 => [
                'title' => '第 5 天',
                'task_title' => '分享小程序',
                'task_brief' => '分享给好友后在本页确认',
                'task_detail' => '点击右上角「…」分享小程序给好友，返回后点「我已分享」记录完成。',
                'task_kind' => 'share_web',
                'mp_path' => '',
                'needs_share_ack' => 1,
            ],
            6 => [
                'title' => '第 6 天',
                'task_title' => '点位督导',
                'task_brief' => '桶边督导留证（与督导工作台一致）',
                'task_detail' => '选择巡检点位，上传点位环境照片，提交后写入桶边督导记录。',
                'task_kind' => 'supervise_web',
                'mp_path' => '',
            ],
            7 => [
                'title' => '第 7 天',
                'task_title' => '小区巡查',
                'task_brief' => '点位巡查留证（与督导工作台一致）',
                'task_detail' => '选择本小区巡查点位并上传环境照片，提交后写入小区巡查记录。',
                'task_kind' => 'community_inspect_web',
                'mp_path' => '',
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

    /** 前置学习 / 仅可回收打卡等整日任务：不要求单独真分打卡 */
    protected static function dayNeedsZhenfen(int $day): bool
    {
        $def = self::dayDefinitions()[$day] ?? [];
        $kind = (string)($def['task_kind'] ?? '');
        if (in_array($kind, ['pre_learn_mp', 'pre_learn_web', 'recycle_clock_web'], true)) {
            return false;
        }
        return true;
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
                return (int)ForumModel::where('user_id', $userId)->where('type', 4)->where('create_time', '>=', $since)->count() > 0;
            case 7:
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
            $taskOnlyDay = !self::dayNeedsZhenfen($d);
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

    public static function preLearnBlocks(): array
    {
        return ClockLearnExamService::getBlocksForClient();
    }

    /**
     * @param array<string, string> $answers
     * @return array<string, mixed>
     */
    public static function submitDay1PreLearnExam(int $userId, int $activeId, array $answers): array
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

        ClockLearnExamService::markCompleted($userId);

        return self::recordDay1PreLearnCompletion($userId, $activeId);
    }

    /**
     * AuthController 的 user_info 多为 Member Model，须转成数组再读 community_id 等字段。
     *
     * @param array<string, mixed>|object|null $userInfo
     * @return array<string, mixed>
     */
    protected static function normalizeMemberInfo($userInfo): array
    {
        if (empty($userInfo)) {
            return [];
        }
        if (is_array($userInfo)) {
            return $userInfo;
        }
        if (is_object($userInfo)) {
            if ($userInfo instanceof \think\Model) {
                return $userInfo->getData();
            }
            if (method_exists($userInfo, 'toArray')) {
                return $userInfo->toArray();
            }
        }

        return (array)$userInfo;
    }

    /**
     * @param array<string, mixed>|object $userInfo
     */
    public static function submitTaskSecondhand(
        int $userId,
        $userInfo,
        int $activeId,
        string $title,
        string $priceRaw,
        string $contact,
        string $imagePath
    ): array {
        $userInfo = self::normalizeMemberInfo($userInfo);
        self::loadWebActive($activeId);
        self::syncAndBuildStatus($userId, $activeId);
        $row = self::findProgress($userId, $activeId);
        if (empty($row)) {
            throw new \InvalidArgumentException('进度不存在');
        }
        self::assertCanWorkOnDay($row, 2, $userId);

        $title = trim($title);
        $contact = trim($contact);
        $imagePath = trim($imagePath);
        if ($title === '' || mb_strlen($title) > 40) {
            throw new \InvalidArgumentException('请填写物品名称（不超过40字）');
        }
        $price = (float)$priceRaw;
        if ($price < 0 || $price > 9999999) {
            throw new \InvalidArgumentException('请填写正确售价');
        }
        if ($contact === '' || mb_strlen($contact) > 40) {
            throw new \InvalidArgumentException('请填写联系方式');
        }
        if ($imagePath === '') {
            throw new \InvalidArgumentException('请上传物品照片');
        }

        $communityId = (int)($userInfo['community_id'] ?? 0);
        $parentId = (int)($userInfo['community_parent_id'] ?? 0);
        if ($parentId <= 0 && $communityId > 0) {
            $parentId = (int)Db::name('member_community')->where('id', $communityId)->value('pid');
        }
        $scopeType = $communityId > 0 ? 'community' : ($parentId > 0 ? 'parent' : 'all');
        $scopeId = $scopeType === 'community' ? $communityId : ($scopeType === 'parent' ? $parentId : 0);
        if ($scopeType === 'community' && $scopeId <= 0) {
            throw new \InvalidArgumentException('请先绑定小区后再发布闲置');
        }

        $now = time();
        $publishMeta = SecondhandService::newItemPublishFields($now);
        $id = (int)Db::name(SecondhandService::TABLE_ITEM)->insertGetId([
            'user_id' => $userId,
            'title' => $title,
            'description' => '1+1活动发布',
            'images' => SecondhandService::encodeImages([$imagePath]),
            'price' => number_format($price, 2, '.', ''),
            'contact' => $contact,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'category' => 'other',
            'condition' => 'used',
            'area_text' => '',
            'status' => $publishMeta['status'],
            'reject_reason' => $publishMeta['reject_reason'],
            'create_time' => $now,
            'update_time' => $now,
            'audit_time' => $publishMeta['audit_time'],
        ]);

        $status = self::syncAndBuildStatus($userId, $activeId);

        return ['id' => $id, 'status' => $status];
    }

    public static function submitTaskPhoto(int $userId, int $activeId, string $image, string $intro): array
    {
        self::loadWebActive($activeId);
        self::syncAndBuildStatus($userId, $activeId);
        $row = self::findProgress($userId, $activeId);
        if (empty($row)) {
            throw new \InvalidArgumentException('进度不存在');
        }
        self::assertCanWorkOnDay($row, 4, $userId);

        $image = trim($image);
        $intro = trim($intro);
        if ($image === '') {
            throw new \InvalidArgumentException('请上传照片');
        }
        if ($intro === '' || mb_strlen($intro) > 200) {
            throw new \InvalidArgumentException('请填写简要介绍（200字内）');
        }

        $photoActiveId = self::resolveQxlPhotoActiveId();
        ActiveWebPhotoService::loadWebPhotoActive($photoActiveId);

        $now = time();
        $token = ActiveWebPhotoService::newShareToken();
        $point = ActiveWebPhotoService::grantPoints(
            $userId,
            $photoActiveId,
            ActiveWebPhotoService::POINT_ON_APPROVE,
            '七贤岭摄影·1+1活动'
        );
        Db::name(ActiveWebPhotoService::photoTable())->insert([
            'active_id' => $photoActiveId,
            'user_id' => $userId,
            'image' => $image,
            'intro' => $intro,
            'category' => 'scenery',
            'status' => 1,
            'like_count' => 0,
            'period_key' => ActiveWebPhotoService::periodKey($now),
            'point_awarded' => $point,
            'bonus_point_awarded' => 0,
            'share_token' => $token,
            'create_time' => $now,
            'audit_time' => $now,
        ]);

        $status = self::syncAndBuildStatus($userId, $activeId);

        return ['status' => $status];
    }

    /**
     * 小区巡查/桶边督导可选点位（与 forum/get_community_cate_list 一致）
     *
     * @return array{community_id:int,points:array<int,array{id:int,name:string}>}
     */
    public static function taskDwxjPoints($userInfo): array
    {
        $userInfo = self::normalizeMemberInfo($userInfo);
        $communityId = (int)($userInfo['community_id'] ?? 0);
        if ($communityId <= 0) {
            throw new \InvalidArgumentException('请先绑定小区后再进行巡查/督导');
        }
        $points = CommunityCateModel::field('id,name')
            ->where('cate_id', $communityId)
            ->select()
            ->toArray();

        return ['community_id' => $communityId, 'points' => $points];
    }

    /**
     * 第 6 天桶边督导(type=4)、第 7 天小区巡查(type=3)，写入 cmf_forum（同 dwxj/add）
     *
     * @param array<string, mixed>|object $userInfo
     */
    public static function submitTaskDwxj(
        int $userId,
        $userInfo,
        int $activeId,
        int $day,
        int $cateId,
        string $imageOne,
        int $inspectCommunityId = 0
    ): array {
        if ($day !== 6 && $day !== 7) {
            throw new \InvalidArgumentException('无效任务天');
        }
        $userInfo = self::normalizeMemberInfo($userInfo);
        $forumType = $day === 6 ? 4 : 3;

        self::loadWebActive($activeId);
        self::syncAndBuildStatus($userId, $activeId);
        $row = self::findProgress($userId, $activeId);
        if (empty($row)) {
            throw new \InvalidArgumentException('进度不存在');
        }
        self::assertCanWorkOnDay($row, $day, $userId);

        $imageOne = trim($imageOne);
        if ($imageOne === '') {
            throw new \InvalidArgumentException('请上传点位环境照片');
        }
        if ($cateId <= 0) {
            throw new \InvalidArgumentException('请选择巡检点位');
        }

        $cateInfo = CommunityCateModel::where('id', $cateId)->find();
        if (empty($cateInfo)) {
            throw new \InvalidArgumentException('点位不存在');
        }

        if ($inspectCommunityId <= 0) {
            $inspectCommunityId = (int)($userInfo['community_id'] ?? 0);
        }
        if ($inspectCommunityId <= 0) {
            throw new \InvalidArgumentException('请先绑定小区');
        }

        $forumModel = new ForumModel();
        $last = $forumModel->where('type', $forumType)
            ->where('user_id', $userId)
            ->where('cate_id', $cateId)
            ->order('id desc')
            ->find();
        if (!empty($last)) {
            $hours = (int)($cateInfo['inspection_hour'] ?? 0);
            if ($hours > 0 && (int)$last['create_time'] > strtotime('-' . $hours . ' hour')) {
                throw new \InvalidArgumentException('该点位尚在间隔时间内，请稍后再提交');
            }
        }

        $content = "现场整洁：整洁\n整改意见：无\n工作情况：1+1活动" . ($forumType === 4 ? '桶边督导' : '小区巡查');
        $now = time();
        $point = (float)($cateInfo['inspection_point'] ?? 0);
        $forumModel->save([
            'user_id' => $userId,
            'type' => $forumType,
            'cate_id' => $cateId,
            'content' => $content,
            'image_one' => $imageOne,
            'image_two' => '',
            'image_three' => '',
            'community_id' => (int)($userInfo['subject_community_id'] ?? $inspectCommunityId),
            'subject_community_id' => $inspectCommunityId,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        $forumId = (int)$forumModel->id;
        if ($point > 0) {
            AccountLogModel::add_account_log($userId, 2, 222, $point, ['from_uid' => $forumId]);
        }
        MemberLevelModel::isLevel($userId);

        $status = self::syncAndBuildStatus($userId, $activeId);

        return ['forum_id' => $forumId, 'point' => $point, 'status' => $status];
    }

    protected static function resolveQxlPhotoActiveId(): int
    {
        $id = (int)ActiveModel::where('delete_time', 0)
            ->where('web_url', 'like', '%qxl-photo-share%')
            ->value('id');
        if ($id <= 0) {
            throw new \InvalidArgumentException('七贤岭摄影活动未配置，请联系管理员');
        }
        return $id;
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
