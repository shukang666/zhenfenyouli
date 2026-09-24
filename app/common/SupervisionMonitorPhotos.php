<?php

namespace app\common;

/**
 * 督导监测照片字段：投放点、整改后、收运链条支持多图。
 * 库内：空字符串 | 单张相对/绝对 URL | JSON 数组 URL（2 张及以上）。
 */
class SupervisionMonitorPhotos
{
    public const MULTI_FIELDS = [
        'point_photo',
        'point_rectify_photo',
        'transport_photo',
    ];

    public static function isMultiField(string $field): bool
    {
        return in_array($field, self::MULTI_FIELDS, true);
    }

    /**
     * 从库内原始值解析为 URL 列表（兼容历史单 URL）。
     *
     * @return string[]
     */
    public static function urlsFromStored($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_array($raw)) {
            return self::filterUrlList($raw);
        }
        $s = trim((string)$raw);
        if ($s === '') {
            return [];
        }
        if ($s[0] === '[') {
            $decoded = json_decode($s, true);
            if (is_array($decoded)) {
                return self::filterUrlList($decoded);
            }
        }
        return [$s];
    }

    /**
     * 提交入库：多图字段归一化；可选对每张 path 做相对路径转换。
     */
    public static function normalizeStoredValue($input, ?callable $pathNormalizer = null): string
    {
        $list = self::parseSubmitInput($input);
        if ($pathNormalizer !== null) {
            $normalized = [];
            foreach ($list as $item) {
                $n = $pathNormalizer($item);
                if ($n !== null && $n !== '') {
                    $normalized[] = $n;
                }
            }
            $list = $normalized;
        } else {
            $list = self::filterUrlList($list);
        }
        if (count($list) === 0) {
            return '';
        }
        if (count($list) === 1) {
            return $list[0];
        }
        return json_encode(array_values($list), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param mixed $input 字符串、JSON 字符串或数组
     * @return string[]
     */
    public static function parseSubmitInput($input): array
    {
        if ($input === null || $input === '') {
            return [];
        }
        if (is_array($input)) {
            return self::filterUrlList($input);
        }
        $s = trim((string)$input);
        if ($s === '') {
            return [];
        }
        if ($s[0] === '[') {
            $decoded = json_decode($s, true);
            if (is_array($decoded)) {
                return self::filterUrlList($decoded);
            }
        }
        return [$s];
    }

    /**
     * @param string[] $list
     * @return string[]
     */
    private static function filterUrlList(array $list): array
    {
        $out = [];
        foreach ($list as $item) {
            if ($item === null) {
                continue;
            }
            $s = trim((string)$item);
            if ($s !== '') {
                $out[] = $s;
            }
        }
        return array_values($out);
    }
}
