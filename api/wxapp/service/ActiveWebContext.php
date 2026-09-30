<?php

namespace api\wxapp\service;

/**
 * Web 式活动 H5 登录上下文：短期签名票据 web_ctx，避免在 URL 明文传 openid。
 */
class ActiveWebContext
{
    /** 票据有效秒数（默认 2 小时） */
    public const TTL = 7200;

    public static function secret(): string
    {
        $s = (string)config('database.authcode');
        if ($s === '') {
            $s = (string)config('database.password');
        }
        return hash('sha256', 'cmf_active_web_ctx_v1:' . $s);
    }

    public static function issue(int $userId, int $activeId, ?int $ttl = null): string
    {
        $ttl = $ttl ?? self::TTL;
        $payload = [
            'uid' => $userId,
            'aid' => $activeId,
            'exp' => time() + max(60, $ttl),
            'n'  => bin2hex(random_bytes(8)),
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $b64 = self::b64urlEncode($json);
        $sig = self::b64urlEncode(hash_hmac('sha256', $b64, self::secret(), true));
        return $b64 . '.' . $sig;
    }

    /**
     * @return array{uid:int,aid:int,exp:int,n:string}|null
     */
    public static function resolve(string $token, ?int $expectedActiveId = null): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }
        [$b64, $sig] = $parts;
        $expectSig = self::b64urlEncode(hash_hmac('sha256', $b64, self::secret(), true));
        if (!hash_equals($expectSig, $sig)) {
            return null;
        }
        $json = self::b64urlDecode($b64);
        $payload = json_decode($json, true);
        if (!is_array($payload) || empty($payload['uid']) || empty($payload['aid'])) {
            return null;
        }
        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }
        if ($expectedActiveId !== null && (int)$payload['aid'] !== $expectedActiveId) {
            return null;
        }
        return [
            'uid' => (int)$payload['uid'],
            'aid' => (int)$payload['aid'],
            'exp' => (int)$payload['exp'],
            'n'   => (string)($payload['n'] ?? ''),
        ];
    }

    public static function appendQueryParam(string $url, string $key, string $value): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $sep . rawurlencode($key) . '=' . rawurlencode($value);
    }

    private static function b64urlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $b64): string
    {
        $pad = 4 - (strlen($b64) % 4);
        if ($pad < 4) {
            $b64 .= str_repeat('=', $pad);
        }
        return base64_decode(strtr($b64, '-_', '+/')) ?: '';
    }
}
