<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

try {
    require __DIR__ . '/bootstrap.php';

    $cfg  = ai_talk_config();
    $path = ai_talk_storage_path();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = isset($_GET['action']) ? (string)$_GET['action'] : '';
    if ($action === '' && $method === 'POST') {
        $action = 'send';
    }
    if ($action === '') {
        $action = 'history';
    }

    if ($method === 'OPTIONS') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Allow: GET, POST, OPTIONS');
        exit;
    }

    if ($action === 'history' && $method === 'GET') {
        $sinceId = (int)($_GET['since_id'] ?? 0);
        $data    = ai_talk_read_store($path);
        ai_talk_maybe_clear_stale_busy($data, (int)($cfg['ai_busy_max_sec'] ?? 180));
        $list = $data['messages'];
        if ($sinceId > 0) {
            $list = array_values(array_filter($list, static function ($m) use ($sinceId) {
                return (int)($m['id'] ?? 0) > $sinceId;
            }));
        }
        ai_talk_json(1, 'ok', [
            'messages' => $list,
            'last_id'  => ai_talk_last_message_id($data['messages']),
            'ai_busy'  => ai_talk_store_ai_busy($data),
        ]);
    }

    if ($action === 'send' && $method === 'POST') {
        @set_time_limit(300);
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw ?: '{}', true);
        if (!is_array($body)) {
            ai_talk_json(0, '请求体无效');
        }
        $nickname = ai_talk_sanitize_nickname((string)($body['nickname'] ?? ''));
        $content  = ai_talk_sanitize_content((string)($body['content'] ?? ''));
        $maxLen   = (int)($cfg['max_message_len'] ?? 800);
        $cap      = (int)($cfg['storage_cap'] ?? 200);
        $busyMax  = (int)($cfg['ai_busy_max_sec'] ?? 180);

        if ($content === '') {
            ai_talk_json(0, '请输入消息内容');
        }
        if (mb_strlen($content) > $maxLen) {
            ai_talk_json(0, '消息过长（最多 ' . $maxLen . ' 字）');
        }

        try {
            $user = ai_talk_with_store($path, static function (array &$data) use ($nickname, $content, $cap, $busyMax) {
                $data = ai_talk_normalize_store($data);
                ai_talk_maybe_clear_stale_busy($data, $busyMax);
                if (!empty($data['ai_busy'])) {
                    throw new AiTalkBusyException('讨论助手正在回复，请稍候再发送');
                }
                $user = [
                    'id'       => ai_talk_next_id($data['messages']),
                    'role'     => 'user',
                    'nickname' => $nickname,
                    'content'  => $content,
                    'ts'       => time(),
                ];
                $data['messages'][]    = $user;
                $data['ai_busy']       = 1;
                $data['ai_busy_since'] = time();
                ai_talk_apply_storage_cap($data, $cap);
                return $user;
            });
        } catch (AiTalkBusyException $e) {
            ai_talk_json(0, $e->getMessage(), ['ai_busy' => 1]);
        }

        try {
            $store       = ai_talk_read_store($path);
            $glmMessages = ai_talk_build_glm_messages($store['messages'], $cfg);
            $replyText   = ai_talk_call_glm_with_retry($glmMessages, $cfg);

            $bot = ai_talk_with_store($path, static function (array &$data) use ($replyText, $cap) {
                $data = ai_talk_normalize_store($data);
                $bot  = [
                    'id'       => ai_talk_next_id($data['messages']),
                    'role'     => 'assistant',
                    'nickname' => '讨论助手',
                    'content'  => $replyText,
                    'ts'       => time(),
                ];
                $data['messages'][]    = $bot;
                $data['ai_busy']       = 0;
                $data['ai_busy_since'] = 0;
                ai_talk_apply_storage_cap($data, $cap);
                return $bot;
            });

            ai_talk_json(1, 'ok', [
                'user'    => $user,
                'assistant' => $bot,
                'ai_busy' => 0,
            ]);
        } catch (Throwable $e) {
            ai_talk_release_ai_busy($path);
            ai_talk_json(0, $e->getMessage(), [
                'user'    => $user,
                'ai_busy' => 0,
            ]);
        }
    }

    ai_talk_json(0, '未知操作');
} catch (Throwable $e) {
    if (function_exists('ai_talk_json')) {
        ai_talk_json(0, $e->getMessage());
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 0, 'msg' => $e->getMessage(), 'data' => null], JSON_UNESCAPED_UNICODE);
}
