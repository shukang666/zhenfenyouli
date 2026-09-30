<?php

namespace api\wxapp\service;

use api\wxapp\model\AccountLogModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\active\MemberBadgeModel;
use think\facade\Db;

class ActiveWebPhotoService
{
    /** 单账号每日上传上限（含待审） */
    public const MAX_UPLOAD_PER_DAY = 3;

    /** 单账号每日从本活动获得积分上限 */
    public const MAX_POINTS_PER_DAY = 15;

    /** 审核通过基础积分 */
    public const POINT_ON_APPROVE = 5;

    /** 单张照片累计点赞达到该值且未发过奖，额外积分 */
    public const BONUS_LIKE_THRESHOLD = 20;

    public const BONUS_LIKE_POINTS = 5;

    /** 累计通过照片数达到该值发荣誉勋章（每活动每用户一次） */
    public const BADGE_APPROVED_COUNT = 5;

    public static function photoTable(): string
    {
        return 'active_web_photo';
    }

    public static function likeTable(): string
    {
        return 'active_web_photo_like';
    }

    public static function reportTable(): string
    {
        return 'active_web_photo_report';
    }

    public static function periodKey(?int $time = null): string
    {
        $time = $time ?? time();
        return date('o-\WW', $time);
    }

    public static function loadWebPhotoActive(int $activeId): array
    {
        $active = ActiveModel::where('id', $activeId)->where('delete_time', 0)->find();
        if (empty($active) || !ActiveModel::isWebDisplay($active)) {
            return [];
        }
        return $active->toArray();
    }

    public static function todayStart(): int
    {
        return strtotime(date('Y-m-d 00:00:00'));
    }

    public static function countTodayUploads(int $userId, int $activeId): int
    {
        return (int)Db::name(self::photoTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->where('create_time', '>=', self::todayStart())
            ->count();
    }

    public static function sumTodayPoints(int $userId, int $activeId): float
    {
        $fromPhoto = (float)Db::name(self::photoTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->where('create_time', '>=', self::todayStart())
            ->sum('point_awarded');
        return $fromPhoto;
    }

    public static function grantPoints(int $userId, int $activeId, float $point, string $remark): float
    {
        if ($point <= 0) {
            return 0;
        }
        $today = self::sumTodayPoints($userId, $activeId);
        if ($today >= self::MAX_POINTS_PER_DAY) {
            return 0;
        }
        $grant = min($point, self::MAX_POINTS_PER_DAY - $today);
        if ($grant <= 0) {
            return 0;
        }
        AccountLogModel::add_account_log(
            $userId,
            2,
            201,
            $grant,
            ['remark' => $remark, 'active_id' => $activeId]
        );
        return $grant;
    }

    public static function maybeAwardBadge(int $userId, int $activeId, string $badgeName): void
    {
        $approved = (int)Db::name(self::photoTable())
            ->where('user_id', $userId)
            ->where('active_id', $activeId)
            ->where('status', 1)
            ->count();
        if ($approved < self::BADGE_APPROVED_COUNT) {
            return;
        }
        $exists = MemberBadgeModel::where([
            'user_id' => $userId,
            'badge_id' => $activeId,
            'name' => $badgeName,
        ])->find();
        if ($exists) {
            return;
        }
        MemberBadgeModel::create([
            'user_id' => $userId,
            'badge_id' => $activeId,
            'name' => $badgeName,
            'image' => '',
        ]);
    }

    public static function refreshLikeCount(int $photoId): int
    {
        $count = (int)Db::name(self::likeTable())->where('photo_id', $photoId)->count();
        Db::name(self::photoTable())->where('id', $photoId)->update(['like_count' => $count]);
        return $count;
    }

    public static function newShareToken(): string
    {
        return bin2hex(random_bytes(8));
    }

    public static function categoryLabels(): array
    {
        return [
            'humanity' => '人文',
            'scenery' => '美景',
            'food' => '美食',
            'other' => '其他',
        ];
    }
}
