<?php

namespace app\admin\service;

use app\admin\model\active\ActiveClockModel;
use app\admin\model\active\ActiveModel;
use think\facade\Db;

/**
 * 已结束的非周期性活动 → 自动写入活动风采（与手工风采并存）
 */
class ActivityShowcaseSyncService
{
    const MAX_PHOTOS = 15;

    /**
     * 批量同步最近结束、尚未入库的活动（列表/首页预览前调用）
     */
    public static function syncEligibleEndedActives(int $limit = 30): int
    {
        $limit = max(1, min($limit, 100));
        $scan = max($limit * 8, 80);
        $rows = ActiveModel::where([
            ['type', '=', 1],
            ['status', '=', 1],
            ['delete_time', '=', 0],
            ['is_recurring', '=', 0],
            ['is_date_pending', '=', 0],
        ])->order('id desc')->limit($scan)->select();

        $n = 0;
        foreach ($rows as $row) {
            if ($n >= $limit) {
                break;
            }
            $arr = is_array($row) ? $row : $row->toArray();
            if (!self::isEndedForShowcase($arr)) {
                continue;
            }
            if (self::syncOneActive($arr)) {
                $n++;
            }
        }
        return $n;
    }

    /** 活动是否已结束且可进风采（非周期性、非时间待定） */
    public static function isEndedForShowcase(array $active): bool
    {
        if ((int)($active['is_recurring'] ?? 0) === 1) {
            return false;
        }
        if ((int)($active['is_date_pending'] ?? 0) === 1) {
            return false;
        }
        if ((int)($active['status'] ?? 0) !== 1 || (int)($active['delete_time'] ?? 0) !== 0) {
            return false;
        }
        $now = time();
        if ((int)($active['is_clock'] ?? 0) === 1 || (int)($active['is_clock_challenge'] ?? 0) === 1) {
            $end = (int)($active['clock_end_time'] ?? 0);
            if ($end > 0) {
                return $end < $now;
            }
        }
        return ActiveModel::resolveIsEnd($active) === 1;
    }

    /**
     * 单活动同步；已存在 active_id 风采则跳过
     */
    public static function syncOneActive(array $active): bool
    {
        $activeId = (int)($active['id'] ?? 0);
        if ($activeId <= 0 || !self::isEndedForShowcase($active)) {
            return false;
        }
        if (ActiveModel::isWebDisplay($active)) {
            return false;
        }

        $exists = Db::name('activity_showcase')
            ->where('active_id', $activeId)
            ->where('status', '<>', -1)
            ->find();
        if (!empty($exists)) {
            return false;
        }

        $thumb = trim((string)($active['thumb'] ?? ''));
        $photos = self::collectPhotos($activeId, $thumb);
        if (empty($photos)) {
            return false;
        }

        $title = trim((string)($active['name'] ?? ''));
        if ($title === '') {
            $title = '社区活动回顾';
        }

        $content = htmlspecialchars_decode((string)($active['content'] ?? ''), ENT_QUOTES);
        if (trim(strip_tags($content)) === '') {
            $content = '<p>感谢各位居民的参与，活动已圆满结束。</p>';
        }

        $plain = trim(strip_tags($content));
        $description = mb_strlen($plain, 'UTF-8') > 120
            ? mb_substr($plain, 0, 120, 'UTF-8') . '…'
            : $plain;

        $eventDate = (int)($active['date'] ?? 0);
        if ($eventDate <= 0) {
            $eventDate = (int)($active['clock_end_time'] ?? 0);
        }
        if ($eventDate <= 0) {
            $eventDate = time();
        }

        $now = time();
        Db::name('activity_showcase')->insert([
            'active_id'   => $activeId,
            'title'       => $title,
            'cover'       => $photos[0],
            'description' => $description,
            'content'     => $content,
            'event_date'  => $eventDate,
            'photos'      => implode(',', $photos),
            'list_order'  => 0,
            'status'      => 1,
            'view_count'  => 0,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        return true;
    }

    public static function findShowcaseIdByActive(int $activeId): int
    {
        if ($activeId <= 0) {
            return 0;
        }
        $id = Db::name('activity_showcase')
            ->where('active_id', $activeId)
            ->where('status', 1)
            ->value('id');
        return (int)$id;
    }

    /**
     * 运营迁移：将活动写入风采（可强制，不要求已结束；Web 式仅有封面也可入库）
     */
    public static function archiveActiveToShowcase(array $active, bool $force = false): bool
    {
        $activeId = (int)($active['id'] ?? 0);
        if ($activeId <= 0) {
            return false;
        }
        if (!$force && !self::isEndedForShowcase($active)) {
            return false;
        }
        if ((int)($active['status'] ?? 0) !== 1 || (int)($active['delete_time'] ?? 0) !== 0) {
            return false;
        }

        $exists = Db::name('activity_showcase')
            ->where('active_id', $activeId)
            ->where('status', '<>', -1)
            ->find();
        if (!empty($exists)) {
            return false;
        }

        $thumb = trim((string)($active['thumb'] ?? ''));
        $photos = self::collectPhotos($activeId, $thumb);
        if (empty($photos) && $thumb !== '') {
            $photos = [$thumb];
        }
        if (empty($photos)) {
            return false;
        }

        $title = trim((string)($active['name'] ?? ''));
        if ($title === '') {
            $title = '社区活动回顾';
        }

        $content = htmlspecialchars_decode((string)($active['content'] ?? ''), ENT_QUOTES);
        if (trim(strip_tags($content)) === '') {
            $content = '<p>活动回顾已收录至活动风采。</p>';
        }

        $plain = trim(strip_tags($content));
        $description = mb_strlen($plain, 'UTF-8') > 120
            ? mb_substr($plain, 0, 120, 'UTF-8') . '…'
            : $plain;

        $eventDate = (int)($active['date'] ?? 0);
        if ($eventDate <= 0) {
            $eventDate = (int)($active['clock_end_time'] ?? 0);
        }
        if ($eventDate <= 0) {
            $eventDate = time();
        }

        $now = time();
        Db::name('activity_showcase')->insert([
            'active_id'   => $activeId,
            'title'       => $title,
            'cover'       => $photos[0],
            'description' => $description,
            'content'     => $content,
            'event_date'  => $eventDate,
            'photos'      => implode(',', $photos),
            'list_order'  => 0,
            'status'      => 1,
            'view_count'  => 0,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        return true;
    }

    protected static function collectPhotos(int $activeId, string $thumb): array
    {
        $list = [];
        if ($thumb !== '') {
            $list[] = $thumb;
        }
        $clockUrls = ActiveClockModel::where('active_id', $activeId)
            ->where('image_url', '<>', '')
            ->order('id desc')
            ->limit(self::MAX_PHOTOS)
            ->column('image_url');
        foreach ($clockUrls as $url) {
            $url = trim((string)$url);
            if ($url !== '' && !in_array($url, $list, true)) {
                $list[] = $url;
            }
            if (count($list) >= self::MAX_PHOTOS) {
                break;
            }
        }
        return array_slice($list, 0, self::MAX_PHOTOS);
    }
}
