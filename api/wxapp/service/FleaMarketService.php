<?php

namespace api\wxapp\service;

use api\wxapp\model\AccountLogModel;
use app\admin\model\active\ActiveModel;
use think\facade\Db;

class FleaMarketService
{
    /** 贵重品宣传消耗积分（可选） */
    public const PROMOTE_POINT_COST = 10;

    public static function sessionTable(): string
    {
        return 'flea_market_session';
    }

    public static function vendorTable(): string
    {
        return 'flea_market_vendor';
    }

    public static function highlightTable(): string
    {
        return 'flea_market_highlight';
    }

    public static function loadWebActive(int $activeId): array
    {
        $active = ActiveModel::where('id', $activeId)->where('delete_time', 0)->find();
        if (empty($active) || !ActiveModel::isWebDisplay($active)) {
            return [];
        }
        return $active->toArray();
    }

    public static function staticPageBase(): string
    {
        return rtrim(cmf_get_domain(), '/') . '/static/mp-active-web/flea-market/';
    }
}
