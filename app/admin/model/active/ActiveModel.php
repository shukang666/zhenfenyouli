<?php

namespace app\admin\model\active;

class ActiveModel extends \think\Model
{
  protected $name  = 'active';

    /** 活动形态：正常活动（线上报名等） */
    const EVENT_KIND_NORMAL = 1;
    /** 线下活动：报名 + 限时定位/口令签到 */
    const EVENT_KIND_OFFLINE = 2;

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

    public function get_type()
    {
        return  [
            '1' => '普通活动',
            '2' => '秒杀活动',
            '3' => '拼团活动',
        ];
    }
}