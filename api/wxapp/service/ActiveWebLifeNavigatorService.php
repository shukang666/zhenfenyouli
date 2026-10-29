<?php

namespace api\wxapp\service;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\ShotReportModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\active\ActiveSIgnModel;
use app\admin\model\forum\ForumModel;
use think\facade\Db;

/**
 * Web 式活动「绿动生活领航员」：长期招募 + 月度行动清单 + 积分
 */
class ActiveWebLifeNavigatorService
{
    const ACCOUNT_LOG_TYPE = 233;

    const AUDIT_PENDING = 0;
    const AUDIT_APPROVED = 1;
    const AUDIT_REJECTED = 2;

    /** 试运营：提交后自动通过，可改为 false 走人工审核 */
    const AUTO_APPROVE_ON_APPLY = true;

    public static function memberTable(): string
    {
        return 'active_web_life_navigator';
    }

    public static function monthTable(): string
    {
        return 'active_web_life_navigator_month';
    }

    public static function rewardLogTable(): string
    {
        return 'active_web_life_navigator_reward_log';
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
        $webUrl = (string)($active['web_url'] ?? '');
        if (!preg_match('#life-navigator#i', $webUrl)) {
            throw new \InvalidArgumentException('活动 H5 地址不匹配领航员专题');
        }
        return is_array($active) ? $active : $active->toArray();
    }

    /** @return array<string, mixed>|null */
    public static function findMember(int $userId, int $activeId): ?array
    {
        if ($userId <= 0 || $activeId <= 0) {
            return null;
        }
        $row = Db::name(self::memberTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->find();
        return empty($row) ? null : $row;
    }

    public static function apply(int $userId, int $activeId): array
    {
        self::loadWebActive($activeId);
        $existing = self::findMember($userId, $activeId);
        if ($existing) {
            return self::formatMemberRow($existing);
        }
        $now = time();
        $audit = self::AUTO_APPROVE_ON_APPLY ? self::AUDIT_APPROVED : self::AUDIT_PENDING;
        $id = (int)Db::name(self::memberTable())->insertGetId([
            'user_id' => $userId,
            'active_id' => $activeId,
            'member_code' => '',
            'audit_status' => $audit,
            'apply_time' => $now,
            'audit_time' => $audit === self::AUDIT_APPROVED ? $now : 0,
            'update_time' => $now,
        ]);
        $code = 'L' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        Db::name(self::memberTable())->where('id', $id)->update([
            'member_code' => $code,
            'update_time' => time(),
        ]);
        $row = Db::name(self::memberTable())->where('id', $id)->find();
        return self::formatMemberRow($row);
    }

    /** @param array<string, mixed> $row */
    protected static function formatMemberRow(array $row): array
    {
        $status = (int)($row['audit_status'] ?? 0);
        $labels = [
            self::AUDIT_PENDING => '审核中',
            self::AUDIT_APPROVED => '已认证',
            self::AUDIT_REJECTED => '未通过',
        ];
        return [
            'member_id' => (int)($row['id'] ?? 0),
            'member_code' => (string)($row['member_code'] ?? ''),
            'audit_status' => $status,
            'audit_label' => $labels[$status] ?? '未知',
            'is_navigator' => $status === self::AUDIT_APPROVED ? 1 : 0,
            'apply_time' => (int)($row['apply_time'] ?? 0),
        ];
    }

    public static function introPayload(int $userId, int $activeId, array $sessionUser): array
    {
        self::loadWebActive($activeId);
        $member = $userId > 0 ? self::findMember($userId, $activeId) : null;
        $memberOut = $member ? self::formatMemberRow($member) : [
            'member_id' => 0,
            'member_code' => '',
            'audit_status' => -1,
            'audit_label' => '未申请',
            'is_navigator' => 0,
            'apply_time' => 0,
        ];
        return [
            'active' => [
                'id' => $activeId,
                'name' => '绿动生活领航员',
            ],
            'member' => $memberOut,
            'organizer' => [
                'name' => '甄分友礼运营团队',
                'verified' => 1,
            ],
            'zone_url' => self::zonePageUrl($activeId),
        ];
    }

    protected static function zonePageUrl(int $activeId): string
    {
        $base = rtrim(cmf_get_domain(), '/');
        return $base . '/static/mp-active-web/life-navigator/zone.html?active_id=' . $activeId;
    }

    public static function zoneStatus(int $userId, int $activeId, array $sessionUser): array
    {
        self::loadWebActive($activeId);
        $member = self::findMember($userId, $activeId);
        if (empty($member) || (int)$member['audit_status'] !== self::AUDIT_APPROVED) {
            throw new \InvalidArgumentException('请先申请并通过领航员认证');
        }
        $ym = self::currentYm();
        $monthStart = self::monthStartTs($ym);
        $monthEnd = self::monthEndTs($ym);
        $todayStart = strtotime(date('Y-m-d 00:00:00'));

        $zhenfenMonth = self::countZhenfenBetween($userId, $monthStart, $monthEnd);
        $zhenfenToday = self::countZhenfenBetween($userId, $todayStart, time());
        $inspectMonth = self::countInspectBetween($userId, $monthStart, $monthEnd);
        $reportMonth = (int)ShotReportModel::where('user_id', $userId)
            ->where('create_time', '>=', $monthStart)
            ->where('create_time', '<=', $monthEnd)
            ->count();
        $greenMonth = self::countGreenParticipation($userId, $monthStart, $monthEnd);
        $monthRow = self::ensureMonthRow($userId, $activeId, $ym);
        $inviteDone = (int)($monthRow['invite_ack'] ?? 0) === 1;

        $tasks = self::buildMonthTasks(
            $zhenfenMonth,
            $inspectMonth,
            $greenMonth,
            $inviteDone
        );
        self::syncTaskRewards($userId, $activeId, $ym, $tasks);

        $progress = self::summarizeTasks($tasks);
        $completed = $progress['completed'];
        $totalTasks = $progress['total'];
        $baseClaimed = self::hasBaseRewardClaimed($userId, $activeId, $ym);
        $baseReady = $completed >= $totalTasks && $totalTasks > 0;

        $nickname = trim((string)($sessionUser['nickname'] ?? ''));
        if ($nickname === '') {
            $nickname = '邻居';
        }

        return [
            'greeting' => self::greetingPrefix() . '，' . $nickname,
            'member' => self::formatMemberRow($member),
            'month' => [
                'ym' => $ym,
                'label' => (int)substr((string)$ym, 4, 2) . '月行动清单',
                'completed' => $completed,
                'total' => $totalTasks,
                'progress_percent' => $totalTasks > 0 ? (int)floor($completed * 100 / $totalTasks) : 0,
                'max_points_hint' => 420,
            ],
            'quick_actions' => [
                [
                    'key' => 'zhenfen',
                    'title' => '真分打卡',
                    'sub' => '记录分类行为并上传照片',
                    'tag' => $zhenfenToday > 0 ? '今日已完成' : '今日未完成',
                    'tag_tone' => $zhenfenToday > 0 ? 'ok' : 'warn',
                    'mp_path' => '/pages/activity/scene-clock-flow?mode=theme',
                ],
                [
                    'key' => 'inspect',
                    'title' => '点位巡查',
                    'sub' => '检查环境/设施并提交结果',
                    'tag' => '每周任务',
                    'tag_tone' => 'muted',
                    'mp_path' => '/pages/zzhd/dwxj/dwxj?type=3',
                ],
                [
                    'key' => 'feedback',
                    'title' => '问题反馈',
                    'sub' => '定位、拍照、描述，便于跟进',
                    'tag' => '随时提交',
                    'tag_tone' => 'muted',
                    'mp_path' => '/pages/dtsh/report?location_type=1&can_submit=1',
                ],
                [
                    'key' => 'monthly',
                    'title' => '本月任务',
                    'sub' => '查看主题行动并参与绿色活动',
                    'tag' => ($totalTasks - $completed) . ' 项待完成',
                    'tag_tone' => 'accent',
                    'mp_path' => '/pages/low_carbon/low_carbon',
                ],
            ],
            'tasks' => $tasks,
            'stats' => [
                'zhenfen_month' => $zhenfenMonth,
                'inspect_month' => $inspectMonth,
                'report_month' => $reportMonth,
            ],
            'benefit' => [
                'title' => '本月领航员保底积分 +70',
                'sub' => '完成基础行动并通过审核后自动发放',
                'ready' => $baseReady ? 1 : 0,
                'claimed' => $baseClaimed ? 1 : 0,
                'point' => 70,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    protected static function buildMonthTasks(int $zhenfenMonth, int $inspectMonth, int $greenMonth, bool $inviteDone): array
    {
        $zhenfenNeed = 3;
        $zhenfenDone = $zhenfenMonth >= $zhenfenNeed;
        return [
            [
                'key' => 'zhenfen_3',
                'title' => '完成3次日常真分',
                'status_text' => $zhenfenDone ? '已完成 ' . $zhenfenNeed . '/' . $zhenfenNeed : '当前 ' . min($zhenfenMonth, $zhenfenNeed) . '/' . $zhenfenNeed,
                'done' => $zhenfenDone ? 1 : 0,
                'point' => 90,
            ],
            [
                'key' => 'inspect_1',
                'title' => '完成1次点位巡查',
                'status_text' => $inspectMonth >= 1 ? '审核通过' : '待完成',
                'done' => $inspectMonth >= 1 ? 1 : 0,
                'point' => 80,
            ],
            [
                'key' => 'green_action',
                'title' => '参加本月绿色行动',
                'status_text' => $greenMonth >= 1 ? '已参与' : '截止 ' . date('n月j日', self::monthEndTs(self::currentYm())),
                'done' => $greenMonth >= 1 ? 1 : 0,
                'point' => 120,
            ],
            [
                'key' => 'invite_1',
                'title' => '邀请1位伙伴共同参与',
                'status_text' => $inviteDone ? '已完成 1/1' : '当前 0/1',
                'done' => $inviteDone ? 1 : 0,
                'point' => 60,
                'needs_share_ack' => 1,
            ],
        ];
    }

    /** @param list<array<string, mixed>> $tasks */
    protected static function syncTaskRewards(int $userId, int $activeId, int $ym, array $tasks): void
    {
        foreach ($tasks as $task) {
            if (empty($task['done'])) {
                continue;
            }
            $key = (string)($task['key'] ?? '');
            $point = (int)($task['point'] ?? 0);
            if ($key === '' || $point <= 0) {
                continue;
            }
            self::grantTaskOnce($userId, $activeId, $ym, $key, $point, (string)($task['title'] ?? '领航员行动'));
        }
    }

    protected static function grantTaskOnce(int $userId, int $activeId, int $ym, string $taskKey, int $point, string $title): void
    {
        $exists = Db::name(self::rewardLogTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->where('ym', $ym)
            ->where('task_key', $taskKey)
            ->find();
        if (!empty($exists)) {
            return;
        }
        AccountLogModel::add_account_log($userId, 2, self::ACCOUNT_LOG_TYPE, $point, [
            'active_id' => $activeId,
            'ym' => $ym,
            'task_key' => $taskKey,
            'title' => $title,
            'remark' => '绿动生活领航员：' . $title,
        ]);
        Db::name(self::rewardLogTable())->insert([
            'user_id' => $userId,
            'active_id' => $activeId,
            'ym' => $ym,
            'task_key' => $taskKey,
            'point' => $point,
            'create_time' => time(),
        ]);
    }

    public static function claimBaseReward(int $userId, int $activeId): array
    {
        self::loadWebActive($activeId);
        $member = self::findMember($userId, $activeId);
        if (empty($member) || (int)$member['audit_status'] !== self::AUDIT_APPROVED) {
            throw new \InvalidArgumentException('尚未成为领航员');
        }
        $ym = self::currentYm();
        if (self::hasBaseRewardClaimed($userId, $activeId, $ym)) {
            return ['claimed' => 1, 'point' => 70];
        }
        $progress = self::computeMonthProgress($userId, $activeId, $ym);
        if ($progress['completed'] < $progress['total']) {
            throw new \InvalidArgumentException('请先完成本月全部行动项');
        }
        AccountLogModel::add_account_log($userId, 2, self::ACCOUNT_LOG_TYPE, 70, [
            'active_id' => $activeId,
            'ym' => $ym,
            'task_key' => 'base_monthly',
            'remark' => '绿动生活领航员：本月保底积分',
        ]);
        Db::name(self::rewardLogTable())->insert([
            'user_id' => $userId,
            'active_id' => $activeId,
            'ym' => $ym,
            'task_key' => 'base_monthly',
            'point' => 70,
            'create_time' => time(),
        ]);
        return ['claimed' => 1, 'point' => 70];
    }

    public static function ackInviteShare(int $userId, int $activeId): array
    {
        self::loadWebActive($activeId);
        $member = self::findMember($userId, $activeId);
        if (empty($member) || (int)$member['audit_status'] !== self::AUDIT_APPROVED) {
            throw new \InvalidArgumentException('尚未成为领航员');
        }
        $ym = self::currentYm();
        $row = self::ensureMonthRow($userId, $activeId, $ym);
        if ((int)($row['invite_ack'] ?? 0) !== 1) {
            Db::name(self::monthTable())->where('id', $row['id'])->update([
                'invite_ack' => 1,
                'update_time' => time(),
            ]);
        }
        return ['ok' => 1];
    }

    protected static function hasBaseRewardClaimed(int $userId, int $activeId, int $ym): bool
    {
        return (int)Db::name(self::rewardLogTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->where('ym', $ym)
            ->where('task_key', 'base_monthly')
            ->count() > 0;
    }

    /** @return array<string, mixed> */
    protected static function ensureMonthRow(int $userId, int $activeId, int $ym): array
    {
        $row = Db::name(self::monthTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->where('ym', $ym)
            ->find();
        if (!empty($row)) {
            return $row;
        }
        $id = (int)Db::name(self::monthTable())->insertGetId([
            'user_id' => $userId,
            'active_id' => $activeId,
            'ym' => $ym,
            'invite_ack' => 0,
            'update_time' => time(),
        ]);
        return Db::name(self::monthTable())->where('id', $id)->find();
    }

    protected static function countZhenfenBetween(int $userId, int $start, int $end): int
    {
        return (int)ForumModel::where('user_id', $userId)
            ->where('type', 2)
            ->where('clock_scene', '<>', '')
            ->where('create_time', '>=', $start)
            ->where('create_time', '<=', $end)
            ->count();
    }

    protected static function countInspectBetween(int $userId, int $start, int $end): int
    {
        return (int)ForumModel::where('user_id', $userId)
            ->where('type', 3)
            ->where('create_time', '>=', $start)
            ->where('create_time', '<=', $end)
            ->count();
    }

    protected static function countGreenParticipation(int $userId, int $start, int $end): int
    {
        return (int)ActiveSIgnModel::alias('s')
            ->join('active a', 's.active_id = a.id')
            ->where('s.user_id', $userId)
            ->where('s.create_time', '>=', $start)
            ->where('s.create_time', '<=', $end)
            ->where('a.delete_time', 0)
            ->where(function ($q) {
                $q->where('a.community_cate_id', 5)
                    ->whereOr('a.tags', 'like', '%公益%')
                    ->whereOr('a.is_month_recommend', 1);
            })
            ->count();
    }

    /** @return array{completed:int,total:int,tasks:list<array<string,mixed>>} */
    protected static function computeMonthProgress(int $userId, int $activeId, int $ym): array
    {
        $monthStart = self::monthStartTs($ym);
        $monthEnd = self::monthEndTs($ym);
        $monthRow = self::ensureMonthRow($userId, $activeId, $ym);
        $tasks = self::buildMonthTasks(
            self::countZhenfenBetween($userId, $monthStart, $monthEnd),
            self::countInspectBetween($userId, $monthStart, $monthEnd),
            self::countGreenParticipation($userId, $monthStart, $monthEnd),
            (int)($monthRow['invite_ack'] ?? 0) === 1
        );
        $sum = self::summarizeTasks($tasks);
        return ['completed' => $sum['completed'], 'total' => $sum['total'], 'tasks' => $tasks];
    }

    /** @param list<array<string, mixed>> $tasks @return array{completed:int,total:int} */
    protected static function summarizeTasks(array $tasks): array
    {
        $completed = 0;
        foreach ($tasks as $t) {
            if (!empty($t['done'])) {
                $completed++;
            }
        }
        return ['completed' => $completed, 'total' => count($tasks)];
    }

    protected static function currentYm(): int
    {
        return (int)date('Ym');
    }

    protected static function monthStartTs(int $ym): int
    {
        $y = (int)floor($ym / 100);
        $m = $ym % 100;
        return strtotime(sprintf('%04d-%02d-01 00:00:00', $y, $m));
    }

    protected static function monthEndTs(int $ym): int
    {
        $y = (int)floor($ym / 100);
        $m = $ym % 100;
        return strtotime(date('Y-m-t 23:59:59', strtotime(sprintf('%04d-%02d-01', $y, $m))));
    }

    protected static function greetingPrefix(): string
    {
        $h = (int)date('G');
        if ($h < 11) {
            return '早上好';
        }
        if ($h < 14) {
            return '中午好';
        }
        if ($h < 18) {
            return '下午好';
        }
        return '晚上好';
    }
}
