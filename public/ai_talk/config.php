<?php
return [
    'model'           => 'glm-4.7-flash',
    'max_history'     => 30,
    'max_message_len' => 800,
    'storage_cap'       => 200,
    'glm_retry_max'     => 10,
    'glm_retry_delay_ms'=> 1000,
    'ai_busy_max_sec'   => 180,
    'system_prompt'   => '你正在一个开放的群聊讨论组里，多名用户会用昵称发言。请根据最近对话内容，以「讨论助手」的身份回复：简洁、友好、有信息量，可以总结观点、提问或补充建议。不要冒充某个具体昵称，不要输出过长段落。',
];
