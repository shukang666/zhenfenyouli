<?php

namespace app\admin\model\active;

use think\Model;

/**
 * 用户动作表单提交记录模型
 */
class ActiveClockActionUserModel extends Model
{
    protected $name = 'active_clock_action_user';

    /**
     * 关联动作配置
     */
    public function action()
    {
        return $this->belongsTo(ActiveClockActionModel::class, 'action_id', 'id');
    }

    /**
     * 获取表单数据解码
     */
    public function getFormDataArrAttr($value, $data): array
    {
        if (empty($data['form_data'])) {
            return [];
        }
        $arr = json_decode($data['form_data'], true);
        return is_array($arr) ? $arr : [];
    }

    /**
     * 检查用户是否已提交
     */
    public function hasUserSubmitted(int $actionId, int $userId, int $signId = 0): bool
    {
        return $this->where('action_id', $actionId)
            ->where('user_id', $userId)
            ->where('sign_id', $signId)
            ->count() > 0;
    }

    /**
     * 用户提交表单
     */
    public function submitForm(int $actionId, int $activeId, int $userId, int $signId, array $formData): bool
    {
        if ($this->hasUserSubmitted($actionId, $userId, $signId)) {
            return false;
        }

        $this->create([
            'action_id' => $actionId,
            'active_id' => $activeId,
            'user_id' => $userId,
            'sign_id' => $signId,
            'form_data' => json_encode($formData, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    }
}
