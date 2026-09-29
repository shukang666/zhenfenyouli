<?php

namespace app\admin\model\forum;

use think\Model;

class ClockSceneGuideModel extends Model
{
    protected $name = 'clock_scene_guide';

    public static function hubLabels()
    {
        return [
            'home'   => '家庭场景',
            'qinzi'  => '亲子场景',
            'office' => '办公场景',
            'street' => '沿街商铺',
        ];
    }

    public static function subLabels()
    {
        return [
            ''            => '（无子场景）',
            'after_meal'  => '餐后清理',
            'room_clean'  => '居室扫除',
            'express'     => '拆取快递',
            'daily'       => '日常整理',
            'zhenfenjiao' => '真分角',
        ];
    }

    public static function normalizeHub($hub)
    {
        $hub = trim((string)$hub);
        if ($hub === 'qinzi') {
            return 'home';
        }
        $allowed = array_keys(self::hubLabels());
        return in_array($hub, $allowed, true) ? $hub : '';
    }

    /**
     * @return array|null
     */
    public static function findOne($hub, $subKey, $cateLabel)
    {
        $hub = self::normalizeHub($hub);
        if ($hub === '' || trim((string)$cateLabel) === '') {
            return null;
        }
        $subKey = trim((string)$subKey);
        $row = (new self())
            ->where('hub_key', $hub)
            ->where('sub_key', $subKey)
            ->where('cate_label', trim((string)$cateLabel))
            ->where('status', 1)
            ->find();
        if (empty($row)) {
            return null;
        }
        return self::formatRow($row->toArray());
    }

    public static function formatRow(array $row)
    {
        $tips = trim((string)($row['preset_tips'] ?? ''));
        $list = [];
        if ($tips !== '') {
            if ($tips[0] === '[') {
                $decoded = json_decode($tips, true);
                if (is_array($decoded)) {
                    $list = $decoded;
                }
            }
            if (!$list) {
                $list = self::linesToArray($tips);
            }
        }
        return [
            'hub_key'          => $row['hub_key'] ?? '',
            'sub_key'          => $row['sub_key'] ?? '',
            'cate_label'       => $row['cate_label'] ?? '',
            'handle_guide'     => $row['handle_guide'] ?? '',
            'dispose_time'     => $row['dispose_time'] ?? '',
            'survey_question'  => $row['survey_question'] ?? '',
            'preset_tips'      => $list,
            'learn_path'       => $row['learn_path'] ?? '/pages/flhd/list',
        ];
    }

    public static function linesToArray($text)
    {
        $text = str_replace(["\r\n", "\r"], "\n", (string)$text);
        $lines = explode("\n", $text);
        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }
        return $out;
    }
}
