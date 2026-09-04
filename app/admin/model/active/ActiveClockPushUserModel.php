<?php

namespace app\admin\model\active;

use think\Model;

/**
 * 推送目标用户记录模型
 */
class ActiveClockPushUserModel extends Model
{
    protected $name = 'active_clock_push_user';

    /**
     * 获取已订阅授权的用户（用于推送）
     */
    public function getSubscribedUsers(int $actionId, int $activeId): array
    {
        return $this->where('action_id', $actionId)
            ->where('active_id', $activeId)
            ->where('is_subscribed', 1)
            ->where('is_pushed', 0)
            ->column('user_id');
    }

    /**
     * 记录用户订阅状态
     */
    public function recordSubscription(int $actionId, int $activeId, int $userId, bool $isSubscribed): void
    {
        $exist = $this->where('action_id', $actionId)
            ->where('active_id', $activeId)
            ->where('user_id', $userId)
            ->find();

        if ($exist) {
            $exist->is_subscribed = $isSubscribed ? 1 : 0;
            $exist->save();
        } else {
            $this->create([
                'push_id' => 0,
                'action_id' => $actionId,
                'active_id' => $activeId,
                'user_id' => $userId,
                'is_subscribed' => $isSubscribed ? 1 : 0,
                'create_time' => time(),
            ]);
        }
    }

    /**
     * 标记推送成功
     */
    public function markPushed(int $pushId, int $userId, string $result = 'success'): void
    {
        $this->where('push_id', $pushId)
            ->where('user_id', $userId)
            ->update([
                'is_pushed' => 1,
                'push_result' => $result,
            ]);
    }

    /**
     * 标记推送失败
     */
    public function markPushFailed(int $pushId, int $userId, string $error): void
    {
        $this->where('push_id', $pushId)
            ->where('user_id', $userId)
            ->update([
                'push_result' => $error,
            ]);
    }
}
