<?php

namespace app\admin\model\active;

use think\Model;

/**
 * 活动动作表单字段配置模型
 */
class ActiveClockActionFormModel extends Model
{
    protected $name = 'active_clock_action_form';

    /**
     * 字段类型选项
     */
    public static function getFieldTypeOptions(): array
    {
        return [
            'radio' => '单选按钮',
            'textarea' => '文本框',
            'text' => '纯文本说明',
        ];
    }

    /**
     * 解码选项JSON
     */
    public function getFieldOptionsArrAttr($value, $data): array
    {
        if (empty($data['field_options'])) {
            return [];
        }
        $arr = json_decode($data['field_options'], true);
        return is_array($arr) ? $arr : [];
    }

    /**
     * 批量保存表单字段
     */
    public static function batchSave(int $actionId, array $forms): void
    {
        // 删除旧数据
        self::where('action_id', $actionId)->update(['delete_time' => time()]);

        $now = time();
        foreach ($forms as $index => $form) {
            if (empty($form['field_type'])) {
                continue;
            }

            $options = '';
            if ($form['field_type'] === 'radio' && !empty($form['options'])) {
                // 过滤空选项
                $optionsArr = array_filter($form['options'], function ($v) {
                    return trim($v) !== '';
                });
                $options = json_encode(array_values($optionsArr), JSON_UNESCAPED_UNICODE);
            }

            self::create([
                'action_id' => $actionId,
                'field_type' => $form['field_type'],
                'field_label' => $form['field_label'] ?? '',
                'field_options' => $options,
                'field_placeholder' => $form['field_placeholder'] ?? '',
                'is_required' => $form['is_required'] ?? 0,
                'list_order' => $index,
                'create_time' => $now,
            ]);
        }
    }
}
