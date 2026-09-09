<?php

namespace app\cron\lib;

/**
 * 图像感知哈希工具（aHash，Average Hash 平均哈希，64bit）
 *
 * 原理：把图片无损缩放为 8x8 灰度，用 64 个像素的平均灰度作为阈值，
 * 每个像素与均值比较得到 64 位二进制指纹（hash）。
 * “感知哈希”的相似度比较用汉明距离：两指纹逐位不同的个数。
 *     相似度百分比 = (1 - 汉明距离 / 64) * 100
 * 相似度 100% ⇔ 汉明距离为 0 ⇔ 两图在感知上完全相同。
 *
 * 图片来自阿里云 OSS，数据库里存的是带样式参数的展示 URL(如 ?watermark)，
 * 计算指纹前会先剥离 query/样式参数，直接请求原始图片。
 */
class ImagePerceptualHash
{
    const BIT_LENGTH = 64;

    /** 进程内下载缓存(原始URL => hash|null), 避免同一次执行重复下载同一张图 */
    private static $fetchCache = [];

    /**
     * 数据库中的图片 URL 归一化为“原始图片 URL”。
     * 会去掉 query(?watermark) 与 fragment，取到原图。
     * 非 http(s) 或解析失败返回 null（历史脏数据跳过，不做判定）。
     */
    public static function rawUrl($url): ?string
    {
        $url = trim((string)$url);
        if ($url === '') {
            return null;
        }
        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }
        $raw = $scheme . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $raw .= ':' . $parts['port'];
        }
        $raw .= isset($parts['path']) ? $parts['path'] : '/';
        return $raw;
    }

    /** 下载远程文件字节；失败返回 null（SSL 证书校验失败自动降级重试） */
    public static function fetchBytes(string $url, int $timeout = 25): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $doFetch = function (string $url, bool $verify, int $timeout): array {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
                CURLOPT_USERAGENT      => 'xiayuxuan-cron/1.0',
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = (string)curl_error($ch);
            curl_close($ch);
            if ($body === false || $code < 200 || $code >= 300) {
                return ['ok' => false, 'err' => $err, 'body' => ''];
            }
            return ['ok' => true, 'err' => '', 'body' => $body];
        };

        $r = $doFetch($url, true, $timeout);
        if (!$r['ok'] && stripos($r['err'], 'SSL') !== false) {
            // 部分服务器未安装 CA 证书，降级为不校验证书重试一次
            $r = $doFetch($url, false, $timeout);
        }
        return $r['ok'] ? $r['body'] : null;
    }

    /**
     * 计算单张图片的感知哈希指纹（带进程内缓存）。
     * @return string|null 16 位 hex 字符串；图片下载失败/无法解码时返回 null
     */
    public static function fingerprintFromUrl(string $rawUrl): ?string
    {
        if (array_key_exists($rawUrl, self::$fetchCache)) {
            return self::$fetchCache[$rawUrl];
        }
        $bytes = self::fetchBytes($rawUrl);
        if ($bytes === null) {
            return self::$fetchCache[$rawUrl] = null;
        }
        return self::$fetchCache[$rawUrl] = self::fingerprintFromBytes($bytes);
    }

    /** 从图片二进制内容计算 aHash 指纹；无法解码返回 null */
    public static function fingerprintFromBytes(string $bytes): ?string
    {
        $src = @imagecreatefromstring($bytes);
        if (!$src) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $tw = 8;
        $th = 8;
        $thumb = imagecreatetruecolor($tw, $th);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
        imagedestroy($src);

        $gray = [];
        $sum  = 0;
        for ($y = 0; $y < $th; $y++) {
            for ($x = 0; $x < $tw; $x++) {
                $rgb = imagecolorat($thumb, $x, $y);
                $g   = (int)(0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF));
                $gray[$y][$x] = $g;
                $sum += $g;
            }
        }
        imagedestroy($thumb);

        $avg = $sum / self::BIT_LENGTH;
        $bits = '';
        for ($y = 0; $y < $th; $y++) {
            for ($x = 0; $x < $tw; $x++) {
                $bits .= ($gray[$y][$x] > $avg) ? '1' : '0';
            }
        }
        // 64 位二进制每 4 位转一个十六进制字符 → 16 位 hex
        $hex = '';
        for ($i = 0; $i < 64; $i += 4) {
            $hex .= base_convert(substr($bits, $i, 4), 2, 16);
        }
        return $hex;
    }

    /** 汉明距离（两串 hex 指纹逐位不同个数） */
    public static function hammingDistance(string $h1, string $h2): int
    {
        $h1 = (string)$h1;
        $h2 = (string)$h2;
        if (strlen($h1) !== strlen($h2)) {
            return self::BIT_LENGTH;
        }
        $dist = 0;
        for ($i = 0, $len = strlen($h1); $i < $len; $i++) {
            $d = hexdec($h1[$i]) ^ hexdec($h2[$i]);
            while ($d) {
                $dist += $d & 1;
                $d >>= 1;
            }
        }
        return $dist;
    }

    /** 两指纹的相似度百分比：100% = 汉明距离 0 = 感知完全相同 */
    public static function similarityPercent(string $h1, string $h2): float
    {
        $dist = self::hammingDistance($h1, $h2);
        return round((1 - $dist / self::BIT_LENGTH) * 100, 2);
    }
}
