<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$cfg  = ai_talk_config();
$path = (string)$cfg['storage_file'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? 'history';

if ($method === 'OPTIONS') {
    header('Allow: GET, POST, OPTIONS');
    exit;
}

try {
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
            'last_id'  => empty($data['messages']) ? 0 : (int)end($data['messages'])['id'],
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

        if ($content === '') {
            ai_talk_json(0, '请输入消息内容');
        }
        if (mb_strlen($content) > $maxLen) {
            ai_talk_json(0, '消息过长（最多 ' . $maxLen . ' 字）');
        }

        $newItems = ai_talk_with_store($path, static function (array &$data) use ($nickname, $content, $cfg) {
            $ts   = time();
            $uid  = ai_talk_next_id($data['messages']);
            $user = [
                'id'       => $uid,
                'role'     => 'user',
                'nickname' => $nickname,
                'content'  => $content,
                'ts'       => $ts,
            ];
            $data['messages'][] = $user;

            $glmMessages = ai_talk_build_glm_messages($data['messages'], $cfg);
            $replyText   = ai_talk_call_glm($glmMessages, $cfg);

            $aid = ai_talk_next_id($data['messages']);
            $bot = [
                'id'       => $aid,
                'role'     => 'assistant',
                'nickname' => '讨论助手',
                'content'  => $replyText,
                'ts'       => time(),
            ];
            $data['messages'][] = $bot;

            // 只保留最近 200 条落盘，避免文件无限增大
            if (count($data['messages']) > 200) {
                $data['messages'] = array_slice($data['messages'], -200);
            }

            return ['user' => $user, 'assistant' => $bot];
        });

        ai_talk_json(1, 'ok', $newItems);
    }

    ai_talk_json(0, '未知操作');
} catch (Throwable $e) {
    ai_talk_json(0, $e->getMessage());
}
