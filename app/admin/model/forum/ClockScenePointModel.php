<?php

namespace app\admin\model\forum;

use think\Model;
use think\facade\Db;

class ClockScenePointModel extends Model
{
    protected $name = 'clock_scene_point';

    public const SOURCE_USER = 0;
    public const SOURCE_ADMIN = 1;

    /** 仅后台可为用户配置的统一回收点场景（沿街） */
    public static function adminHubLabels()
    {
        return [
            'street' => '沿街商铺',
        ];
    }

    /** 办公大楼回收点（hub=office，存同一张表） */
    public static function officeBuildingHubKey()
    {
        return 'office';
    }

    /** @return array{id:int,name:string}|null */
    public static function resolveRecycleCate()
    {
        $row = Db::name('basic_select')
            ->where('type', 2)
            ->where('pid', 0)
            ->where('name', 'like', '%可回收%')
            ->order('list_order asc,id asc')
            ->find();
        if (empty($row)) {
            return null;
        }
        return ['id' => (int)$row['id'], 'name' => (string)$row['name']];
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
        return array_merge(self::userHubLabels(), self::adminHubLabels(), [
            self::officeBuildingHubKey() => '办公大楼回收点',
        ]);
    }
}
