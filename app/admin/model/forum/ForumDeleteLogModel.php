<?php

namespace app\admin\model\forum;

use think\Model;

/**
 * 论坛打卡删除记录模型
 * 用于替代外部Excel，记录管理员删除打卡时的原因
 */
class ForumDeleteLogModel extends Model
{
    protected $name = 'forum_delete_log';

    /**
     * 添加删除记录
     * @param array $data 包含 forum_id, user_id, content, image, point, reason, operator_id
     * @return bool|int
     */
    public function addLog(array $data)
    {
        $this->allowField(['forum_id', 'user_id', 'content', 'image', 'point', 'reason', 'operator_id', 'create_time']);
        return $this->save($data);
    }

    /**
     * 按用户ID查询删除记录
     * @param int $userId
     * @return array
     */
    public function getByUserId(int $userId)
    {
        return $this->where('user_id', $userId)->order('id desc')->select();
    }

    /**
     * 按打卡ID查询删除记录
     * @param int $forumId
     * @return array
     */
    public function getByForumId(int $forumId)
    {
        return $this->where('forum_id', $forumId)->order('id desc')->select();
    }

    /**
     * 按管理员ID查询删除记录
     * @param int $operatorId
     * @return array
     */
    public function getByOperatorId(int $operatorId)
    {
        return $this->where('operator_id', $operatorId)->order('id desc')->select();
    }

    /**
     * 处理图片数据为逗号分隔字符串存储
     * @param mixed $image
     * @return string
     */
    public static function imageToString($image): string
    {
        if (is_array($image)) {
            return implode(',', $image);
        }
        if (is_string($image)) {
            $decoded = json_decode($image, true);
            if (is_array($decoded)) {
                return implode(',', $decoded);
            }
            return $image;
        }
        return '';
    }
}
