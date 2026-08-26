<?php

namespace app\admin\model\member;


use api\wxapp\model\CommonModel;
use app\admin\model\forum\ForumModel;
use app\admin\model\ToolModel;

class MemberLevelModel extends ToolModel
{
    protected $name = 'member_level';

    //帖子数量

    public  static  function isLevel($user_id)
    {
        //帖子数 点赞数
        $ForumModel = new ForumModel();
        $levelModel = new MemberLevelModel();
        $where = [
            'user_id' => $user_id,
//            'type' => 6
        ];

        $forum_total = $ForumModel->where($where)->count();
        $like_total = $ForumModel->where($where)->sum('like_total');
        $level = $levelModel
            ->where('forum_num', '<=', $forum_total)
            ->where('like_num', '<=', $like_total)
            ->order('forum_num')
            ->find();
//        var_dump($forum_total,$like_total,$level);exit;
        if ($level) {
            CommonModel::updateUser($user_id, ['level' => $level['id']]);
        }


    }

}