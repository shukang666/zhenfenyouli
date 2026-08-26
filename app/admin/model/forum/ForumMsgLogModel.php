<?php

namespace app\admin\model\forum;

class ForumMsgLogModel extends \think\Model
{
    protected $name = 'forum_msg_log';

    public static function add_data($user_id, $msg_id, $type = 1)
    {
        $ForumMsgLogModel = new ForumMsgLogModel();
        $data = [
            'user_id' => $user_id,
            'msg_id' => $msg_id,
            'type' => $type,
        ];
        $is = $ForumMsgLogModel->where($data)->count();
        if (!$is) {
            $data['create_time'] = time();
            $ForumMsgLogModel->insert($data);
        }
    }
}