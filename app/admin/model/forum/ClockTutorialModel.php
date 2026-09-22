<?php

namespace app\admin\model\forum;

use think\Model;

class ClockTutorialModel extends Model
{
    protected $name = 'clock_tutorial';

    /** 表字段 cate_key 存打卡场景标识 */
    public static function sceneKeyLabels()
    {
        return [
            'home'        => '家庭',
            'qinzi'       => '亲子',
            'street'      => '沿街',
            'zhenfenjiao' => '办公（真分角）',
        ];
    }

    public static function cateKeyLabels()
    {
        return self::sceneKeyLabels();
    }

    public static function normalizeSceneKey($key)
    {
        $key = trim((string)$key);
        if ($key === 'office') {
            return 'zhenfenjiao';
        }
        $allowed = array_keys(self::sceneKeyLabels());
        return in_array($key, $allowed, true) ? $key : '';
    }

    /**
     * 将旧版分栏字段拼成 HTML（content 为空时后台编辑页预填、接口回退展示）
     */
    public static function buildLegacyHtml(array $row)
    {
        $esc = static function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $html = '';
        if (!empty($row['intro'])) {
            $html .= '<p>' . nl2br($esc($row['intro'])) . '</p>';
        }
        if (!empty($row['examples'])) {
            $html .= '<p><strong>包括哪些</strong></p><p>' . nl2br($esc($row['examples'])) . '</p>';
        }
        $photo = self::linesToArray($row['photo_lines'] ?? '');
        if ($photo) {
            $html .= '<p><strong>拍照要求</strong></p><ul>';
            foreach ($photo as $line) {
                $html .= '<li>' . $esc($line) . '</li>';
            }
            $html .= '</ul>';
        }
        $notice = self::linesToArray($row['notice_lines'] ?? '');
        if ($notice) {
            $html .= '<p><strong>注意事项</strong></p><ul>';
            foreach ($notice as $line) {
                $html .= '<li>' . $esc($line) . '</li>';
            }
            $html .= '</ul>';
        }
        return $html;
    }

    /**
     * 小程序用 HTML（绝对地址图片）
     */
    public static function htmlForWxapp(array $row)
    {
        $content = trim((string)($row['content'] ?? ''));
        if ($content !== '') {
            $html = htmlspecialchars_decode($content, ENT_QUOTES);
            if (function_exists('cmf_replace_content_file_url')) {
                $html = cmf_replace_content_file_url($html);
            }
            return $html;
        }
        $legacy = self::buildLegacyHtml($row);
        if ($legacy !== '' && function_exists('cmf_replace_content_file_url')) {
            $legacy = cmf_replace_content_file_url($legacy);
        }
        return $legacy;
    }

    /**
     * 转为小程序展示结构
     */
    public static function formatRow($row)
    {
        if (empty($row)) {
            return null;
        }
        $row = is_array($row) ? $row : $row->toArray();
        $html = self::htmlForWxapp($row);
        if ($html === '') {
            return null;
        }
        return [
            'title'   => $row['title'] ?? '',
            'content' => $html,
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
            $formatted = self::formatRow($row);
            if ($formatted) {
                $map[$key] = $formatted;
            }
        }
        return $map;
    }
}
