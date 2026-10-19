<?php
declare(strict_types=1);

define('AI_TALK_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
define('CMF_ROOT', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
define('CMF_DATA', CMF_ROOT . 'data' . DIRECTORY_SEPARATOR);

function ai_talk_parse_dotenv(string $root): array
{
    $env  = [];
    $file = $root . '/.env';
    if (!is_file($file)) {
        return $env;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $env;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name === '') {
            continue;
        }
        $env[$name] = $value;
    }
    return $env;
}

function ai_talk_env(): array
{
    static $env;
    if ($env !== null) {
        return $env;
    }
    $env = [];
    foreach ([CMF_ROOT, AI_TALK_ROOT] as $root) {
        if ($root === '' || !is_dir($root)) {
            continue;
        }
        $env = array_merge($env, ai_talk_parse_dotenv($root));
    }
    if (isset($_SERVER['AI_TALK_API_KEY']) && is_scalar($_SERVER['AI_TALK_API_KEY'])) {
        $env['AI_TALK_API_KEY'] = (string)$_SERVER['AI_TALK_API_KEY'];
    }
    foreach ($_ENV as $name => $value) {
        if (is_string($name) && $name !== '' && !isset($env[$name])) {
            $env[$name] = is_scalar($value) ? (string)$value : '';
        }
    }
    return $env;
}

function ai_talk_env_get(string $key, string $default = ''): string
{
    $env = ai_talk_env();
    if (!isset($env[$key])) {
        return $default;
    }
    $v = $env[$key];
    return is_scalar($v) ? trim((string)$v) : $default;
}

function ai_talk_default_config(): array
{
    return [
        'model'           => 'glm-4.7-flash',
        'max_history'     => 30,
        'max_message_len' => 800,
        'storage_cap'     => 200,
        'storage_file'    => AI_TALK_ROOT . 'data' . DIRECTORY_SEPARATOR . 'room.json',
        'system_prompt'   => '你正在一个开放的群聊讨论组里，多名用户会用昵称发言。请根据最近对话内容，以「讨论助手」的身份回复：简洁、友好、有信息量，可以总结观点、提问或补充建议。不要冒充某个具体昵称，不要输出过长段落。',
    ];
}

function ai_talk_config(): array
{
    static $cfg;
    if ($cfg !== null) {
        return $cfg;
    }
    $cfg = ai_talk_default_config();
    $optional = CMF_DATA . 'ai_talk.config.php';
    if (is_readable($optional)) {
        $over = require $optional;
        if (is_array($over)) {
            unset($over['storage_file']);
            $cfg = array_merge($cfg, $over);
        }
    }
    $local = AI_TALK_ROOT . 'config.local.php';
    if (is_readable($local)) {
        $over = require $local;
        if (is_array($over)) {
            $cfg = array_merge($cfg, $over);
        }
    }
    $cfg['api_key'] = ai_talk_env_get('AI_TALK_API_KEY');
    $keyId          = ai_talk_env_get('AI_TALK_KEY_ID');
    $cfg['key_id']  = $keyId !== '' ? $keyId : 'product-shukang';
    return $cfg;
}

function ai_talk_register_shutdown_json(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    register_shutdown_function(static function () {
        $err = error_get_last();
        if ($err === null) {
            return;
        }
        $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($err['type'], $fatal, true)) {
            return;
        }
        if (!function_exists('ai_talk_json')) {
            return;
        }
        ai_talk_json(0, '服务异常：' . ($err['message'] ?? 'fatal'));
    });
}

ai_talk_register_shutdown_json();

function ai_talk_json(int $code, string $msg, $data = null): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'msg'  => $msg,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function ai_talk_ensure_storage_dir(string $path): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function ai_talk_read_store(string $path): array
{
    ai_talk_ensure_storage_dir($path);
    if (!is_file($path)) {
        return ['messages' => []];
    }
    $fp = fopen($path, 'r');
    if ($fp === false) {
        return ['messages' => []];
    }
    $raw = '';
    try {
        if (flock($fp, LOCK_SH)) {
            $raw = stream_get_contents($fp);
            flock($fp, LOCK_UN);
        }
    } finally {
        fclose($fp);
    }
    if ($raw === false || $raw === '') {
        return ['messages' => []];
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['messages']) || !is_array($data['messages'])) {
        return ['messages' => []];
    }
    return $data;
}

function ai_talk_with_store(string $path, callable $fn)
{
    ai_talk_ensure_storage_dir($path);
    $fp = fopen($path, 'c+');
    if ($fp === false) {
        throw new RuntimeException('无法打开聊天记录文件');
    }
    try {
        if (!flock($fp, LOCK_EX)) {
            throw new RuntimeException('聊天记录锁定失败');
        }
        $raw = stream_get_contents($fp);
        $data = ['messages' => []];
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['messages']) && is_array($decoded['messages'])) {
                $data = $decoded;
            }
        }
        $result = $fn($data);
        ftruncate($fp, 0);
        rewind($fp);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('聊天记录序列化失败');
        }
        fwrite($fp, $json);
        fflush($fp);
        return $result;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function ai_talk_next_id(array $messages): int
{
    $max = 0;
    foreach ($messages as $m) {
        $id = (int)($m['id'] ?? 0);
        if ($id > $max) {
            $max = $id;
        }
    }
    return $max + 1;
}

function ai_talk_build_glm_messages(array $messages, array $cfg): array
{
    $limit = (int)($cfg['max_history'] ?? 30);
    $slice = array_slice($messages, -$limit);
    $out   = [
        ['role' => 'system', 'content' => (string)$cfg['system_prompt']],
    ];
    foreach ($slice as $m) {
        $role = ($m['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
        if ($role === 'assistant') {
            $content = (string)($m['content'] ?? '');
        } else {
            $nick    = trim((string)($m['nickname'] ?? '访客'));
            $content = '[' . $nick . ']: ' . (string)($m['content'] ?? '');
        }
        if ($content === '') {
            continue;
        }
        $out[] = ['role' => $role, 'content' => $content];
    }
    return $out;
}

function ai_talk_call_glm(array $glmMessages, array $cfg): string
{
    $apiKey = trim((string)($cfg['api_key'] ?? ''));
    if ($apiKey === '') {
        throw new RuntimeException('未配置 AI_TALK_API_KEY');
    }
    $payload = json_encode([
        'model'       => (string)($cfg['model'] ?? 'glm-4.7-flash'),
        'messages'    => $glmMessages,
        'temperature' => 0.75,
    ], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        throw new RuntimeException('请求体编码失败');
    }

    $url     = 'https://open.bigmodel.cn/api/paas/v4/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ];
    $keyId = trim((string)($cfg['key_id'] ?? ''));
    if ($keyId !== '') {
        $headers[] = 'X-Api-Key-Id: ' . $keyId;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER => ai_talk_ssl_verify(),
        ]);
        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $err   = curl_error($ch);
        $http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno !== 0 || $body === false) {
            throw new RuntimeException('AI 接口请求失败：' . ($err ?: 'network'));
        }
        if ($http < 200 || $http >= 300) {
            throw new RuntimeException('AI 接口 HTTP ' . $http . '：' . mb_substr((string)$body, 0, 200));
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", $headers) . "\r\n",
                'content' => $payload,
                'timeout' => 120,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            throw new RuntimeException('AI 接口请求失败');
        }
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $http = (int)$m[1];
            if ($http < 200 || $http >= 300) {
                throw new RuntimeException('AI 接口 HTTP ' . $http . '：' . mb_substr((string)$body, 0, 200));
            }
        }
    }

    $res = json_decode((string)$body, true);
    if (!is_array($res)) {
        throw new RuntimeException('AI 返回无法解析');
    }
    $text = $res['choices'][0]['message']['content'] ?? '';
    if (!is_string($text) || trim($text) === '') {
        $errMsg = $res['error']['message'] ?? json_encode($res, JSON_UNESCAPED_UNICODE);
        throw new RuntimeException('AI 无有效回复：' . mb_substr((string)$errMsg, 0, 200));
    }
    return trim($text);
}

function ai_talk_sanitize_nickname(string $nick): string
{
    $nick = preg_replace('/[\x00-\x1F\x7F]/u', '', $nick) ?? '';
    $nick = trim($nick);
    if ($nick === '') {
        return '访客';
    }
    if (mb_strlen($nick) > 20) {
        $nick = mb_substr($nick, 0, 20);
    }
    return $nick;
}

function ai_talk_sanitize_content(string $text): string
{
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
    return trim($text);
}

function ai_talk_ssl_verify(): bool
{
    $v = ai_talk_env_get('AI_TALK_SSL_VERIFY');
    if ($v !== '') {
        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }
    $cos = ai_talk_env_get('COS_CURL_SSL_VERIFY');
    if ($cos === 'false' || $cos === '0') {
        return false;
    }
    return true;
}

function ai_talk_last_message_id(array $messages): int
{
    if ($messages === []) {
        return 0;
    }
    $last = end($messages);
    return (int)($last['id'] ?? 0);
}
