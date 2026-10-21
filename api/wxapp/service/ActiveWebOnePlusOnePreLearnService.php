<?php

namespace api\wxapp\service;

/** @deprecated 逻辑已并入 ActiveWebOnePlusOneService，保留类名避免旧部署缺文件 */
class ActiveWebOnePlusOnePreLearnService
{
    public static function hasCompletedInWindow(int $userId, int $activeId, int $cycleNo, int $since): bool
    {
        return ActiveWebOnePlusOneService::hasDay1PreLearnInWindow($userId, $activeId, $cycleNo, $since);
    }

    public static function recordCompletion(int $userId, int $activeId): array
    {
        return ActiveWebOnePlusOneService::recordDay1PreLearnCompletion($userId, $activeId);
    }
}
