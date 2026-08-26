<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use app\admin\model\forum\ForumCommentModel;
use app\admin\model\forum\ForumLikeModel;
use app\admin\model\forum\ForumMsgLogModel;
use app\admin\model\forum\ForumMsgUserModel;
use app\admin\model\member\MemberModel;
use app\admin\model\member\SystemMessageModel;

class MessageController extends AuthController
{
    /**
     * 测试
     * @OA\Post(
     *tags={"消息通知"},
     *path="/wxapp/message/test",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function test()
    {

        $memberModel = new MemberModel();
        $list = $memberModel->where('status', 1)->fetchSql(true)->select();
        $this->success('请求成功', $list);
    }


    /**
     * 消息通知数量
     * @OA\Post(
     *tags={"消息通知"},
     *path="/wxapp/message/my_msg_num",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function my_msg_num(ForumMsgLogModel $logModel,SystemMessageModel $systemMessageModel,
                               AccountLogModel $accountLogModel,ForumMsgUserModel $msgUserModel)
    {

        $sys_num= $systemMessageModel->count();

        $where = [];
        $where[] = ['user_id','=',$this->user_id];

        $account_num = $accountLogModel->where($where)->where('account_type',2)->count();
        $msgUser = $msgUserModel->where($where)->count();
        $sys_msg_num = $logModel->where($where)->where('type','<',4)->count();
        $forum_msg_num = $logModel->where($where)->where('type','=',4)->count();

        $item['sys_msg_num'] = ($sys_num + $account_num )- $sys_msg_num;
        $item['forum_msg_num'] = $msgUser - $forum_msg_num;
        $this->success('请求成功', $item);
    }


    /**
     * 系统消息
     * @OA\Post(
     *tags={"消息通知"},
     *path="/wxapp/message/get_system_msg_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="1:系统通知，2：活动通知，3：积分变动",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_system_msg_list(SystemMessageModel $systemMessageModel, AccountLogModel $accountLogModel)
    {
        $type = $this->request->param('type', 0);
        if ($type != 3) {
            $where = [];
            if ($type) $where[] = ['type', '=', $type];
            $where[] = ['status', '=', 1];
            $list = $systemMessageModel->where($where)
                ->field('id,name,type,content,create_time')
                ->order('id desc')->paginate($this->page_size);
            if ($list->isEmpty())$this->error('暂无数据');
        } else {
            $where = [
                'user_id' => $this->user_id,
                'account_type' => 2,
            ];
            $list = $accountLogModel->where($where)
                ->field('id,user_id,change_amount,left_amount,create_time')
                ->order('id desc')->paginate($this->page_size);
        }

        foreach ($list as $item) {
            ForumMsgLogModel::add_data($this->user_id, $item['id'], $type);
        }

        $this->success('请求成功', $list);
    }


    /**
     * 互动消息列表
     * @OA\Post(
     *tags={"消息通知"},
     *path="/wxapp/message/get_msg_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_msg_list(ForumMsgUserModel $msgUserModel)
    {

        $this->checkUser();
        $type = [1 => '点赞', 2 => '评论'];
        $list = $msgUserModel->alias('a')
            ->where('a.delete_time', 0)
            ->where('a.user_id', 3)
            ->field('a.*,b.content,b.image,c.nickname,c.avatar,b.id forum_id,b.is_admin')
            ->join('forum b', 'a.forum_id = b.id')
            ->leftjoin('member c', 'a.from_uid = c.id')
            ->order('a.create_time desc')
            ->paginate($this->page_size)->each(function ($item) use ($type) {
                $item['type_name'] = $type[$item['type']];
                if ($item['type'] == 2) {
                    $item['is_like'] = ForumLikeModel::where('rid', $item['c_id'])
                        ->where('user_id', $this->user_id)->where('type', 2)->count();
                    $item['comment'] = ForumCommentModel::where('id', $item['c_id'])->value('content');
                }
                ForumMsgLogModel::add_data($this->user_id, $item['id'], 4);
                return CommonModel::getOne($item, ['avatar'], ['image']);
            });
        $this->success('请求成功', $list);
    }


}