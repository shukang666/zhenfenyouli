<?php

namespace app\cmf;

/**
 * 上传大小：图片不沿用后台 5MB 配置，上限对齐 PHP upload_max_filesize（COS 侧再压缩）。
 */
class UploadLimit
{
    public static function imageMaxBytes(): int
    {
        $ini = ini_get('upload_max_filesize');
        $bytes = self::parseIniSize($ini);
        if ($bytes > 0) {
            return $bytes;
        }
        return 200 * 1024 * 1024;
    }

    public static function parseIniSize($val): int
    {
        if (!is_string($val) || $val === '') {
            return 0;
        }
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $num = (float)$val;
        if ($last === 'g') {
            return (int)($num * 1024 * 1024 * 1024);
        }
        if ($last === 'm') {
            return (int)($num * 1024 * 1024);
        }
        if ($last === 'k') {
            return (int)($num * 1024);
        }
        return (int)$num;
    }
}
