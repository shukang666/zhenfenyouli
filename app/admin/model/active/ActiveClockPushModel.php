<?php

namespace app\admin\model\active;

use think\Model;

/**
 * 活动打卡推送消息配置模型
 */
class ActiveClockPushModel extends Model
{
    protected $name = 'active_clock_push';

    /**
     * 关联动作配置
     */
    public function action()
    {
        return $this->belongsTo(ActiveClockActionModel::class, 'action_id', 'id');
    }

    /**
     * 解码推送字段JSON
     */
    public function getPushFieldsArrAttr($value, $data): array
    {
        if (empty($data['push_fields'])) {
            return [];
        }
        $arr = json_decode($data['push_fields'], true);
        return is_array($arr) ? $arr : [];
    }

    /**
     * 编码推送字段为JSON
     */
    public function setPushFieldsAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }
}
