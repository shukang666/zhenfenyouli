<?php

namespace api\wxapp\support;

use think\facade\Cache;

/**
 * 五个 Tab 首页聚合接口短时缓存（ThinkPHP Cache，可配 Redis/file）
 * 传 refresh=1 跳过缓存；用户相关数据 TTL 较短
 */
class TabHomeCache
{
    /** _banner、分类、配置等公共块 */
    const TTL_PUBLIC = 90;

    /** 含报名/签到/优惠券领取态等用户态字段 */
    const TTL_USER = 45;

    public static function shouldBypass(): bool
    {
        return (int)request()->param('refresh', 0) === 1;
    }

    /**
     * @param string $scope 如 index_tab / activity_tab
     * @param int $userId 未登录为 0
     * @param array $parts 参与 cache key 的参数（page、tag 等）
     */
    public static function remember(string $scope, int $userId, array $parts, int $ttl, callable $producer)
    {
        if (self::shouldBypass()) {
            return $producer();
        }
        ksort($parts);
        $key = 'wxapp_tab_' . $scope . ':' . $userId . ':' . md5(json_encode($parts, JSON_UNESCAPED_UNICODE));
        $hit = Cache::get($key);
        if ($hit !== null) {
            return $hit;
        }
        $data = $producer();
        Cache::set($key, $data, max(1, $ttl));
        return $data;
    }
}
