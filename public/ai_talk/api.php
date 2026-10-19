<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

try {
    require __DIR__ . '/bootstrap.php';

    $cfg  = ai_talk_config();
    $path = (string)$cfg['storage_file'];

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
        $list    = $data['messages'];
        if ($sinceId > 0) {
            $list = array_values(array_filter($list, static function ($m) use ($sinceId) {
                return (int)($m['id'] ?? 0) > $sinceId;
            }));
        }
        ai_talk_json(1, 'ok', [
            'messages' => $list,
            'last_id'  => ai_talk_last_message_id($data['messages']),
        ]);
    }

    if ($action === 'send' && $method === 'POST') {
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw ?: '{}', true);
        if (!is_array($body)) {
            ai_talk_json(0, '请求体无效');
        }
        $nickname = ai_talk_sanitize_nickname((string)($body['nickname'] ?? ''));
        $content  = ai_talk_sanitize_content((string)($body['content'] ?? ''));
        $maxLen   = (int)($cfg['max_message_len'] ?? 800);
        $cap      = (int)($cfg['storage_cap'] ?? 200);

        if ($content === '') {
            ai_talk_json(0, '请输入消息内容');
        }
        if (mb_strlen($content) > $maxLen) {
            ai_talk_json(0, '消息过长（最多 ' . $maxLen . ' 字）');
        }

        $newItems = ai_talk_with_store($path, static function (array &$data) use ($nickname, $content, $cfg, $cap) {
            $user = [
                'id'       => ai_talk_next_id($data['messages']),
                'role'     => 'user',
                'nickname' => $nickname,
                'content'  => $content,
                'ts'       => time(),
            ];
            $data['messages'][] = $user;

            $glmMessages = ai_talk_build_glm_messages($data['messages'], $cfg);
            $replyText   = ai_talk_call_glm($glmMessages, $cfg);

            $bot = [
                'id'       => ai_talk_next_id($data['messages']),
                'role'     => 'assistant',
                'nickname' => '讨论助手',
                'content'  => $replyText,
                'ts'       => time(),
            ];
            $data['messages'][] = $bot;

            if ($cap > 0 && count($data['messages']) > $cap) {
                $data['messages'] = array_slice($data['messages'], -$cap);
            }

            return ['user' => $user, 'assistant' => $bot];
        });

        ai_talk_json(1, 'ok', $newItems);
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
