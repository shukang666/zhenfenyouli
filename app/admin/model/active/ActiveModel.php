<?php

namespace app\admin\model\active;

class ActiveModel extends \think\Model
{
  protected $name  = 'active';

    /** 活动形态：正常活动（线上报名等） */
    const EVENT_KIND_NORMAL = 1;
    /** 线下活动：报名 + 限时定位/口令签到 */
    const EVENT_KIND_OFFLINE = 2;

    /** 显示形式：配置式（现有详情页 + 后台各项开关） */
    const DISPLAY_MODE_CONFIG = 1;
    /** 显示形式：Web 式（仅配置 H5 URL，小程序 web-view 打开） */
    const DISPLAY_MODE_WEB = 2;

    public static function displayModeOptions(): array
    {
        return [
            self::DISPLAY_MODE_CONFIG => '配置式',
            self::DISPLAY_MODE_WEB => 'Web式',
        ];
    }

    public static function isWebDisplay($active): bool
    {
        return (int)($active['display_mode'] ?? self::DISPLAY_MODE_CONFIG) === self::DISPLAY_MODE_WEB
            && trim((string)($active['web_url'] ?? '')) !== '';
    }

    /** 小程序 web-view 最终打开的地址（附带 active_id 便于 H5 调接口） */
    public static function buildWebActivityUrl(string $webUrl, int $activeId): string
    {
        $webUrl = trim($webUrl);
        if ($webUrl === '') {
            return '';
        }
        if (preg_match('/[?&]active_id=/', $webUrl)) {
            return $webUrl;
        }
        return $webUrl . (strpos($webUrl, '?') !== false ? '&' : '?') . 'active_id=' . rawurlencode((string)$activeId);
    }

    public static function eventKindOptions(): array
    {
        return [
            self::EVENT_KIND_NORMAL => '正常活动',
            self::EVENT_KIND_OFFLINE => '线下活动',
        ];
    }

    public static function isOfflineEvent($active): bool
    {
        return (int)($active['event_kind'] ?? self::EVENT_KIND_NORMAL) === self::EVENT_KIND_OFFLINE;
    }

    /** 活动截止日期是否标记为「时间待定」 */
    public static function isDatePending($active): bool
    {
        return (int)($active['is_date_pending'] ?? 0) === 1;
    }

    /** 是否已结束（时间待定时不因截止日期自动结束） */
    public static function resolveIsEnd($active): int
    {
        if (self::isDatePending($active)) {
            return 0;
        }
        $date = $active['date'] ?? 0;
        if (is_string($date) && strpos($date, '-') !== false) {
            $date = strtotime($date);
        }
        $date = (int)$date;
        return ($date > 0 && $date < time()) ? 1 : 0;
    }

    public function get_type()
    {
        return  [
            '1' => '普通活动',
            '2' => '秒杀活动',
            '3' => '拼团活动',
        ];
    }
}