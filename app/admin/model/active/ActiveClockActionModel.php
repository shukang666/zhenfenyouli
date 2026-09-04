<?php

namespace app\admin\model\active;

use think\Model;

/**
 * 活动打卡动作配置模型
 */
class ActiveClockActionModel extends Model
{
    protected $name = 'active_clock_action';

    /**
     * 关联表单字段
     */
    public function forms()
    {
        return $this->hasMany(ActiveClockActionFormModel::class, 'action_id', 'id')
            ->where('delete_time', 0)
            ->order('list_order asc, id asc');
    }

    /**
     * 获取指定活动的所有生效动作配置
     */
    public function getActiveActions(int $activeId): array
    {
        return $this->where('active_id', $activeId)
            ->where('status', 1)
            ->where('delete_time', 0)
            ->order('list_order asc, id asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取通知类型选项
     */
    public static function getNotifyTypeOptions(): array
    {
        return [
            1 => '即时表单通知',
            2 => '滞后服务消息推送',
        ];
    }

    /**
     * 获取打卡次数对应的动作（考虑时效）
     */
    public function getActionByClockCount(int $activeId, int $clockCount): ?array
    {
        $now = time();
        $action = $this->where('active_id', $activeId)
            ->where('clock_count', $clockCount)
            ->where('status', 1)
            ->where('delete_time', 0)
            ->where(function ($query) use ($now) {
                $query->where('expire_time', 0)
                    ->whereOr('expire_time', '>=', $now);
            })
            ->order('list_order asc, id asc')
            ->find();

        if (!$action) {
            return null;
        }

        $action = $action->toArray();
        // 加载表单字段
        $action['forms'] = ActiveClockActionFormModel::where('action_id', $action['id'])
            ->where('delete_time', 0)
            ->order('list_order asc, id asc')
            ->select()
            ->toArray();

        return $action;
    }
}
