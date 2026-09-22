<?php

namespace app\admin\model\forum;

use think\Model;

class ClockTutorialModel extends Model
{
    protected $name = 'clock_tutorial';

    public static function cateKeyLabels()
    {
        return [
            'recycle' => '可回收物',
            'other'   => '其他垃圾',
            'harm'    => '有害垃圾',
            'food'    => '厨余垃圾',
        ];
    }

    /**
     * 转为小程序展示结构
     */
    public static function formatRow($row)
    {
        if (empty($row)) {
            return null;
        }
        $photo = self::linesToArray($row['photo_lines'] ?? '');
        $notice = self::linesToArray($row['notice_lines'] ?? '');
        return [
            'title'    => $row['title'] ?? '',
            'intro'    => $row['intro'] ?? '',
            'examples' => $row['examples'] ?? '',
            'photo'    => $photo,
            'notice'   => $notice,
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

    /**
     * @return array<string, array>
     */
    public static function mapForWxapp()
    {
        $list = (new self())->where('status', 1)->order('list_order asc,id asc')->select();
        $map = [];
        foreach ($list as $row) {
            $row = $row->toArray();
            $key = $row['cate_key'] ?? '';
            if ($key === '') {
                continue;
            }
            $map[$key] = self::formatRow($row);
        }
        return $map;
    }
}
