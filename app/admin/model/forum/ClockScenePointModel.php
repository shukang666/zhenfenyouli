<?php

namespace app\admin\model\forum;

use think\Model;

class ClockScenePointModel extends Model
{
    protected $name = 'clock_scene_point';

    public const SOURCE_USER = 0;
    public const SOURCE_ADMIN = 1;

    /** 仅后台可为用户配置的统一回收点场景 */
    public static function adminHubLabels()
    {
        return [
            'street' => '沿街商铺',
            'office' => '办公真分角',
        ];
    }

    public static function userHubLabels()
    {
        return [
            'home'  => '家庭场景',
            'qinzi' => '亲子场景',
        ];
    }

    public static function allHubLabels()
    {
        return array_merge(self::userHubLabels(), self::adminHubLabels());
    }
}
