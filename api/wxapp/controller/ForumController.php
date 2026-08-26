<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveUserModel;
use app\admin\model\basic\CollectModel;
use app\admin\model\basic\NewsModel;
use app\admin\model\basic\SelectModel;
use app\admin\model\forum\ForumCateModel;
use app\admin\model\forum\ForumCommentModel;
use app\admin\model\forum\ForumHandleModel;
use app\admin\model\forum\ForumLikeModel;
use app\admin\model\forum\ForumModel;
use app\admin\model\forum\ForumMsgLogModel;
use app\admin\model\forum\ForumMsgUserModel;
use app\admin\model\member\CommunityCateModel;
use app\admin\model\member\CommunityModel;
use app\admin\model\member\CommunityParentModel;
use app\admin\model\member\MemberLevelModel;
use app\admin\model\member\MemberModel;
use think\facade\Db;


class ForumController extends AuthController
{
    /**
     * 测试
     * @OA\Post(
     *tags={"帖子"},
     *path="/wxapp/forum/test",
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
     * 添加帖子
     * @OA\Post(
     *tags={"帖子"},
     *path="/wxapp/forum/add_forum",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="编辑",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="类型  1健康生活社区 ,2.垃圾打卡,3.点位巡检,4.:桶边督导,5.互助平台,6.优秀事迹,7环保论坛",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="cate_id",description="类型  健康生活社区 的类型",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="title",description="标题",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="content",description="内容",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image",description="多图 传数组",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_one",description="点位照片",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_two",description="投放指南",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_three",description="垃圾桶照片",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_forum(ForumModel $forumModel, ForumCateModel $forumCateModel)
    {
        $this->checkUser();
        $setting = cmf_get_option('set_config');
        $params = $this->request->param();
        $type = $this->request->param('type', 1);
        $cate_id = $this->request->param('cate_id');
//        if (empty($params['content'])) $this->error('内容不能为空');
        if (empty($type)) $this->error('类型不能为空');
        if ($type == 2 && empty($this->user_info['community_id'])) $this->error('请完善小区');
        //腾讯校验
//        $res = CommonModel::checkContent($this->user_info['openid'], $params['content']);
//        if (!$res) {
//            $this->error('含有违规内容，请重新发布');
//        }

        $params['community_id'] = $this->user_info['subject_community_id'];

        if (!empty($params['image'])) {
            $params['image'] = json_encode($params['image']);
        }
        if (empty($params['id'])) {
            $params['user_id'] = $this->user_id;

            if($type == 3){

                $cate_info = CommunityCateModel::where('id',$cate_id)->find();
                if(empty($cate_info)) $this->error('点位不能为空');


                $map = [];
                $map[] = ['type', '=', $type];
                $map[] = ['user_id', '=', $this->user_id];
                $map[] = ['cate_id', '=', $cate_id];
                $handle_info = $forumModel->where($map)->order('id desc')->find();
                if(!empty($handle_info)){
                    if($handle_info['create_time'] > strtotime('-'.$cate_info['inspection_hour'].'hour')){
                        $remaining_seconds = $handle_info['create_time'] - strtotime('-'.$cate_info['inspection_hour'].'hour');
                        $this->error('当前还不可打卡',['remaining_seconds' => $remaining_seconds]);
                    }
                }

                $point = $cate_info['inspection_point'];
                AccountLogModel::add_account_log($this->user_id, 2, 222, $point, []);
            }elseif($type == 2){
                if(empty($params['image'])) $this->error('图片不能为空');
            }

            $forumModel->save($params);
            $id = $forumModel->id;
            MemberLevelModel::isLevel($this->user_id);
            if ($type == 1) {
                $health_point = $this->get_config['health_point'];
                if ($health_point > 0) {
                    AccountLogModel::add_account_log($this->user_id, 2, 200, $health_point, ['from_uid' => $id]);
                    $this->success("获得{$health_point}积分！");
                }
            } elseif ($type == 2) {
                $carbon = $this->get_config['carbon'];
                if ($carbon > 0) {
                    //每天只能领取一次积分
                    $count = $forumModel->where('user_id', $this->user_id)
                        ->where('type', 2)
                        ->whereBetweenTime('create_time', strtotime(date('Y-m-d')), time())
                        ->count();
                    if ($count > 1) {
                        $this->success('发布成功');
                    }
                    $forumModel->where('id', $id)->update(['point' => $carbon]);
                    AccountLogModel::add_account_log($this->user_id, 2, 200, $carbon, ['from_uid' => $id]);
                    $this->success("获得{$carbon}积分！");
                }
            }
//            if ($type == 6) {
//
//            }
        } else {
            $params['status'] = 1;
            $id = $params['id'];
            $forumModel->update($params, ['id' => $params['id']]);
        }

        //当前还不是环保达人
        if($this->user_info['is_environmental'] == 0 && $type == 2){
            //判断是否连续天数达标
            $array = ForumModel::where(['type' => $type, 'user_id' => $this->user_id])->column('create_time');
            $clock_count = $this->getMaxContinueSignDays($array);
            if($clock_count >= $setting['continuous_check']){
                MemberModel::where('id', $this->user_id)->update(['is_environmental' => 1]);
            }
        }

        $data = [];
        $data['point'] = $point ?? 0;
        $this->success('发布成功！', $data);
    }

    /**
     * 帖子分类列表
     * @OA\Post(
     *tags={"帖子"},
     *path="/wxapp/forum/get_cate_list",
     *@OA\Parameter(name="pid",description="上级id 默认0",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_cate_list(ForumCateModel $forumCateModel)
    {
        $pid = $this->request->param('pid', 2);
        if (empty($pid)) {
            $list = $forumCateModel->where('status', 2)->where('pid', 0)->select();
            foreach ($list as $k => $v) {
                $list[$k]['child'] = $forumCateModel->where('pid', '=', $v['id'])->select();
            }
            $this->success('请求成功', $list);
        }
        $where = [];
        $where[] = ['pid', '=', $pid];
//        $where[] = ['status', '=', 2];
        $list = $forumCateModel->where($where)->select();
        if ($list->isEmpty()) $this->error('暂无分类');
        $this->success('请求成功', $list);
    }

    /**
     * 帖子列表和详情
     * @OA\Post(
     *tags={"帖子"},
     *path="/wxapp/forum/get_forum_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="keyword",description="关键字",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="cate_id",description="分类ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="类型 1健康生活社区 ,2.垃圾打卡,3.点位巡检,4.:桶边督导,5.互助平台,6.优秀事迹,7环保论坛",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_my",description="1：我发布的",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="status",description="点位巡检用 1：未处理，2：已处理",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_forum_list(ForumModel       $forumModel, ForumCateModel $forumCateModel,
                                   ForumHandleModel $handleModel, CommunityCateModel $communityCateModel, CommunityModel $communityModel,
                                   MemberModel      $memberModel)
    {
        $id = $this->request->param('id');
        $cate_id = $this->request->param('cate_id');
        $cate_child_id = $this->request->param('cate_child_id');
        $type = $this->request->param('type', 1);
        $is_my = $this->request->param('is_my', 0);
        $status = $this->request->param('status');
        $keyword = $this->request->param('keyword');
        $where = [];
        if (empty($id)) {

            //巡检/桶边督导 需要改成  用户只能看到自己小区的，管理人员可以看到全部

//            $where[] = ['delete_time', '=', 0];
//            $where[] = ['a.status', '=', 2];
            if ($type == 3 || $type == 4) {
                if (empty($is_my)) $where[] = ['user_id', '<>', $this->user_id];
                if ($this->user_info['type'] == 1) {
                    $where[] = ['b.community_id', '=', $this->user_info['community_id']];
                }
            }
            if (!empty($cate_id)) $where[] = ['cate_id', '=', $cate_id];
            if (!empty($cate_child_id)) $where[] = ['cate_child_id', '=', $cate_child_id];
            if (!empty($is_my)) $where[] = ['user_id', '=', $this->user_id];
            if (!empty($status)) $where[] = ['a.status', '=', $status];
            if (!empty($type)) $where[] = ['a.type', '=', $type];
            if (!empty($keyword)) $where[] = ['a.content|a.title', 'like', "%$keyword%"];

//            $this->error('测试',$where);
            $list = $forumModel
                ->alias('a')
                ->join('member b', 'a.user_id=b.id')
                ->where($where)
                ->order('a.id desc')
                ->field('a.id,title,a.type,image,a.status,a.create_time,user_id,nickname,
                avatar,view_num,cate_id,content,like_total,a.point,b.community_id,a.image_one,a.image_two,a.image_three')
                ->paginate($this->page_size)->each(function ($item) use ($memberModel, $forumCateModel, $forumModel, $handleModel, $communityModel, $communityCateModel) {
                    $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                        ->where('user_id', $this->user_id)->where('type', 1)->count();
                    $item['comment_total'] = ForumCommentModel::where('info_id', $item['id'])->count();
                    $item['community_name'] = $communityModel->where('id', $item['community_id'])->value('name');

                    if (empty($item['community_name'])) {
                        $community = $communityModel->where(['status' => 1])->find();
                        $item['community_name'] = $community['name'];
                    }
                    if ($item['type'] == 1) {
                        $item['cate_name'] = $forumCateModel->where('id', $item['cate_id'])->value('name');
                    } elseif ($item['type'] == 2) {
                        $item['cate_name'] = SelectModel::where('id', $item['cate_id'])->value('name');
                    } elseif ($item['type'] == 3 || $item['type'] == 4) {//定位巡检
                        $item['cate_name'] = $communityCateModel->where('id', $item['cate_id'])->value('name', '');
                        $item['status_name'] = $forumModel->get_status()[$item['status']];
                        $handle = $handleModel->where('forum_id', $item['id'])->find();
                        if ($handle) {
                            $user_info = $memberModel->find($handle['user_id']);
                            $handle['avatar'] = cmf_get_asset_url($user_info['avatar']);
                            $handle['nickname'] = $user_info['nickname'];
                            $item['handle_info'] = CommonModel::getOne($handle, [], ['image']);
                        }

                    }
                    return CommonModel::getOne($item, ['thumb', 'avatar', 'image_one', 'image_two', 'image_three'], ['image']);
                });

            $this->success('请求成功', $list);
        } else {
            $item = $forumModel
                ->alias('a')
                ->join('member b', 'a.user_id=b.id')
                ->field('a.*,nickname,avatar,b.community_id')
                ->where('a.id', $id)->find();
//            $item['status_name'] = $forumModel->get_status()[$item['status']];
            $item['community_name'] = $communityModel->where('id', $item['community_id'])->value('name');
            $item['cate_name'] = $forumCateModel->where('id', $item['cate_id'])->value('name');
            $item = CommonModel::getOne($item, ['thumb', 'avatar', 'image_one', 'image_two', 'image_three'], ['image']);
            $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                ->where('user_id', $this->user_id)->where('type', 1)->count();
            $item['comment_total'] = ForumCommentModel::where('info_id', $item['id'])->count();
            if ($item['type'] == 1) {
                $item['cate_name'] = $forumCateModel->where('id', $item['cate_id'])->value('name');
            } elseif ($item['type'] == 2) {
                $item['cate_name'] = SelectModel::where('id', $item['cate_id'])->value('name');
            } elseif ($item['type'] == 3 || $item['type'] == 4) {//定位巡检
                $item['cate_name'] = $communityModel->where('id', $item['cate_id'])->value('name', '');
                $item['status_name'] = $forumModel->get_status()[$item['status']];
                $handle = $handleModel->where('forum_id', $item['id'])->find();
                if ($handle) {
                    $user_info = $memberModel->find($handle['user_id']);
                    $handle['avatar'] = cmf_get_asset_url($user_info['avatar']);
                    $handle['nickname'] = $user_info['nickname'];
                    $item['handle_info'] = CommonModel::getOne($handle, [], ['image']);
                }

            }
            $forumModel->where('id', $id)->inc('view_num')->update();
        }
        $this->success('请求成功', $item);
    }


    /**
     * 删除帖子
     * @OA\Post(
     *tags={"帖子"},
     *path="/wxapp/forum/del_forum",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function del_forum(ForumModel $forumModel)
    {
        $id = $this->request->param('id');
        $forumModel->update(['delete_time' => time()], ['id' => $id]);
        $this->success('删除成功');
    }


    /**
     * 添加评论
     * @OA\Post(
     *     tags={"帖子-评论"},
     *     path="/wxapp/forum/add_comment",
     *@OA\Parameter(name="id",description="当前的主文章id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="forum_type",description="默认1帖子，2：活动，3：资讯",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="c_id",description="评论的某个评论id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="content",description="内容",in="query",required=false, @OA\Schema(type="string",)),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public
    function add_comment()
    {

        $this->checkUser();
        $CommentModel = new ForumCommentModel();

        $info_id = $this->request->param('id');
        $forum_type = $this->request->param('forum_type', 1);
        $c_id = $this->request->param('c_id');
        $content = $this->request->param('content');
        if (empty($info_id) || empty($content)) $this->error('error');

        $res = CommonModel::checkContent($this->user_info['openid'], $content);
        if (!$res) {
            $this->error('评论非法');
        }
        $data = [
            'info_id' => $info_id,
            'forum_type' => $forum_type,
            'user_id' => $this->user_id,
            'content' => $content,
            'create_time' => time(),
            'status' => 1,
            'c_id' => 0,
        ];
        $c_info = [];
        if (!empty($c_id)) {
            $data['tc_id'] = $c_id;
            $c_info = $CommentModel->where(['id' => $c_id])->find();
            if (empty($c_info)) $this->error('error');

            if (!empty($c_info['c_id'])) {
                $data['c_id'] = $c_info['c_id'];
            } else {
                $data['c_id'] = $c_id;
            }
            $data['to_uid'] = $c_info['user_id'];
        }

        $res = $CommentModel->insertGetId($data);

        if ($res) {
            $data['child'] = [];
            $data['id'] = $res;
            $MemberModel = new MemberModel();
            $add_user = $MemberModel->where(['id' => $this->user_id])->find();
            $data['add_nickname'] = $add_user['nickname'];
            $data['add_avatar'] = $add_user['avatar'];
            if (!empty($data['to_uid'])) {
                $to_user = $MemberModel->where(['id' => $data['to_uid']])->find();
                $data['to_nickname'] = $to_user['nickname'];
                $data['to_avatar'] = $to_user['avatar'];
            }
            $this->success('评论成功', $data);
        } else {
            $this->error('添加失败');
        }
    }


    /**
     * 评论列表
     * @OA\Post(
     *     tags={"帖子-评论"},
     *     path="/wxapp/forum/get_comment",
     *@OA\Parameter(name="id",description="文章id",in="query",required=false, @OA\Schema(type="string",)),
     *      @OA\Parameter( name="forum_type", description="默认1帖子，2：活动，3：资讯",in="query",required=false, @OA\Schema(type="string", )),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     *
     * )
     */

    public function get_comment()
    {
        $info_id = $this->request->param('id');
        $forum_type = $this->request->param('forum_type', 1);
        if (empty($info_id)) $this->error('id不能为空');
        $forumModel = new ForumModel();
        $CommentModel = new ForumCommentModel();

        $map = [];
        $map[] = ['a.info_id', '=', $info_id];
        $map[] = ['a.status', '=', 1];
        $map[] = ['a.c_id', '=', 0];
        $map[] = ['forum_type', '=', $forum_type];


        $list = $CommentModel->alias('a')
            ->field('a.*,b.nickname add_nickname,b.avatar add_avatar')
            ->leftjoin('member b', 'a.user_id = b.id')
            ->where($map)->order('a.id asc')->paginate($this->page_size);

        foreach ($list as &$item) {
//            $item['create_time'] = $this->formatDate($item['create_time']);
            $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                ->where('user_id', $this->user_id)->where('type', 2)->count();
            $item['is_my_comment'] = $item['user_id'] == $this->user_id ? 1 : 0;
            $item['add_avatar'] = cmf_get_asset_url($item['add_avatar']);
            $map = [];
            $map[] = ['a.info_id', '=', $info_id];
            $map[] = ['a.status', '=', 1];
            $map[] = ['a.c_id', '=', $item['id']];
            $child = $CommentModel->alias('a')
                ->field('a.*,b.nickname add_nickname,b.avatar add_avatar,c.nickname to_nickname,c.avatar to_avatar')
                ->leftjoin('member b', 'a.user_id = b.id')
                ->leftjoin('member c', 'a.to_uid = c.id')
                ->where($map)->order('a.id')->select()->toArray();
            foreach ($child as &$value) {
//                $value['create_time'] = $this->formatDate($value['create_time']);
                $value['is_my_comment'] = $value['user_id'] == $this->user_id ? 1 : 0;
                $value['add_avatar'] = cmf_get_asset_url($value['add_avatar']);
                $value['to_avatar'] = cmf_get_asset_url($value['to_avatar']);
                $value['is_like'] = ForumLikeModel::where('rid', $value['id'])
                    ->where('user_id', $this->user_id)->where('type', 2)->count();
            }
            $item['child'] = $child;
            $item['child_count'] = count($child);
        }

        $this->success('获取成功', $list);
    }


    /**
     * 帖子评论点赞和取消
     * @OA\Post(
     *     tags={"帖子-点赞"},
     *     path="/wxapp/forum/add_like",
     *      @OA\Parameter( name="openid", description="openid",in="query",required=false, @OA\Schema(type="string", )),
     *      @OA\Parameter( name="rid", description="点赞ID 帖子或评论",in="query",required=false, @OA\Schema(type="string", )),
     *      @OA\Parameter( name="forum_type", description="默认1帖子，2：活动，3：资讯",in="query",required=false, @OA\Schema(type="string", )),
     *      @OA\Parameter( name="type", description="1：帖子，2：评论",in="query",required=false, @OA\Schema(type="string", )),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_like(ForumLikeModel    $likeModel, ForumModel $forumModel,
                             ForumCommentModel $commentModel, ActiveUserModel $activeUserModel, NewsModel $newsModel)
    {
        $this->checkUser();
        $rid = $this->request->param('rid');
        $type = $this->request->param('type');
        $forum_type = $this->request->param('forum_type', 1);
        if (empty($type)) $this->error('type不能为空');
        if (empty($rid)) $this->error('rid不能为空');


        if ($type == 1) {
            if ($forum_type == 1) {
                $forum = $forumModel->find($rid);
            } elseif ($forum_type == 2) {
                $forumModel = $activeUserModel;
                $forum = $activeUserModel->find($rid);
            } elseif ($forum_type == 3) {
                $forum = $newsModel->find($rid);
            }
            if (empty($forum)) $this->error('帖子不存在');
        } elseif ($type == 2) {
            $comment = $commentModel->find($rid);
            if (empty($comment)) $this->error('评论不存在');
        }

        $where = [
            'user_id' => $this->user_id,
            'rid' => $rid,
            'type' => $type,
            'forum_type' => $forum_type,
        ];
        $like = $likeModel->where($where)->find();
        if (empty($like)) {
            try {
                Db::startTrans();
                $likeModel->save($where);
                if ($type == 1) {
                    $forumModel->where('id', $rid)->inc('like_total', 1)->update();
                    if ($forum_type == 1) MemberLevelModel::isLevel($forum->user_id);//$forum['type'] == 6 &&
                } elseif ($type == 2) {
                    $commentModel->where('id', $rid)->inc('like_total', 1)->update();
                } elseif ($type == 3) {
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('点赞失败');
            }
            $this->success('点赞成功');
        } else {
            try {
                $likeModel->startTrans();
                $likeModel->where($where)->delete();
                if ($type == 1) {
                    $forumModel->where('id', $rid)->dec('like_total', 1)->update();
                } elseif ($type == 2) {
                    $commentModel->where('id', $rid)->dec('like_total', 1)->update();
                } elseif ($type == 3) {
                }
                $likeModel->commit();
            } catch (\Exception $e) {
                $likeModel->rollback();
                $this->error('取消点赞失败');
            }
            $this->success('取消点赞成功');
        }


    }


    /**
     *社区列表
     * @OA\Post(
     *tags={"小区"},
     *path="/wxapp/forum/get_community_parent_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_community_parent_list(CommunityParentModel $communityModel)
    {
        $list = $communityModel->field('id,name')->order('id desc')->select();
        $this->success('成功', $list);
    }


    /**
     *小区列表
     * @OA\Post(
     *tags={"小区"},
     *path="/wxapp/forum/get_community_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="pid",description="父级id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_community_list(CommunityModel $communityModel)
    {
        $pid = $this->request->param('pid');
        $where = [];
        if(!empty($pid)) $where[] = ['pid', '=', $pid];
        $list = $communityModel->field('id,name')->where($where)->order('list_order asc')->select();
//        foreach ($list as &$item) {
//            $item['county_name'] = CommonModel::getAreaName($item['county']);
//            $item = CommonModel::getOne($item, ['thumb']);
//        }
        $this->success('成功', $list);
    }


    /**
     * 小区点位
     * @OA\Post(
     *tags={"小区"},
     *path="/wxapp/forum/get_community_cate_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="cate_id",description="分类ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_community_cate_list(CommunityCateModel $communityCateModel)
    {
        $cate_id = $this->request->param('cate_id', 0);
        $where = [];
        if ($cate_id) {
            $where[] = ['cate_id', '=', $cate_id];
        } else {
            $this->checkUser();
            $where[] = ['cate_id', '=', $this->user_info['community_id']];
        }
        $list = $communityCateModel->field('id,name')->where($where)->select();
        $this->success('成功', $list);
    }


    /**
     *点位巡检 上传处理凭证
     * @OA\Post(
     *tags={"帖子"},
     *path="/wxapp/forum/add_handle",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="forum_id",description="帖子ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image",description="多图传数组",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_one",description="点位照片",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_two",description="投放指南",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_three",description="垃圾桶照片",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="content",description="内容",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_handle(ForumHandleModel $handleModel, ForumModel $forumModel)
    {

        $this->checkUser();
        $setting = cmf_get_option('set_config');
        $params = $this->request->param();
        $forum_id = $params['forum_id'];
        $map = [];
        $map[] = ['forum_id', '=', $forum_id];
        $map[] = ['user_id', '=', $this->user_id];
        $handle_info = $handleModel->where($map)->order('id desc')->find();
        if(!empty($handle_info)){
            if($handle_info['create_time'] > strtotime('-'.$setting['inspection_hour'].'hour')){
                $remaining_seconds = $handle_info['create_time'] - strtotime('-'.$setting['inspection_hour'].'hour');
                $this->error('当前还不可打卡',['remaining_seconds' => $remaining_seconds]);
            }
        }


        $forum = $forumModel->find($params['forum_id']);
        if (!$forum) $this->error('帖子不存在');
        $params['user_id'] = $this->user_id;
        if (!empty($params['image'])) $params['image'] = json_encode($params['image']);
        $result = $handleModel->save($params);
        if($result){
            $point = $setting['inspection_point'];
            AccountLogModel::add_account_log($this->user_id, 2, 222, $point, ['forum_id' => $forum_id]);
        }
        $forum->save(['status' => 2]);
        $this->success('提交成功');
    }


    /**
     *我的评论
     * @OA\Post(
     *tags={"帖子"},
     *path="/wxapp/forum/my_comment_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function my_comment_list(ForumCommentModel $commentModel, ForumModel $forumModel)
    {
        $this->checkUser();
        $list = $commentModel
            ->alias('a')
            ->where('a.user_id', $this->user_id)
            ->join('member b', 'b.id=a.user_id')
            ->join('forum f', 'f.id=a.info_id')
            ->field('a.user_id,a.type,a.create_time,a.content comment,b.nickname,b.avatar,f.content,f.image,f.id forum_id')
            ->order('a.id desc')
            ->paginate($this->page_size);
        foreach ($list as &$item) {
//            $item['county_name'] = CommonModel::getAreaName($item['county']);
            $item = CommonModel::getOne($item, ['thumb', 'avatar'], ['image']);

//            $item['forum_info'] = $forumModel->where('id', $item['info_id'])->find();

        }
        $this->success('成功', $list);
    }


    /**
     * 删除评论
     * @OA\Post(
     *tags={"小区"},
     *path="/wxapp/forum/del_comment",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function del_comment(ForumCommentModel $model)
    {
        $id = $this->request->param('id', 0);
        $model->find($id)->delete();
        $this->success('删除成功');
    }



    /**
     * 获取一组打卡时间戳中 最长连续打卡天数
     * @param array $timeStampArr 打卡时间戳数组
     * @return int 最长连续天数
     */
    function getMaxContinueSignDays(array $timeStampArr): int
    {
        // 无打卡数据直接返回0
        if (empty($timeStampArr)) {
            return 0;
        }

        // 1、全部转为当天0点时间戳
        $dayTsArr = array_map(function ($ts) {
            return strtotime(date('Y-m-d', $ts));
        }, $timeStampArr);

        // 2、去重：同一天多次打卡只保留1条
        $uniqueDayTs = array_unique($dayTsArr);
        // 3、升序排序
        sort($uniqueDayTs);

        $oneDaySecond = 86400; // 一天总秒数
        $maxDays = 1;    // 最大连续天数，最少1天
        $currentDays = 1;// 当前连续计数
        $prevTs = $uniqueDayTs[0];

        foreach ($uniqueDayTs as $ts) {
            // 跳过第一条自身
            if ($ts === $prevTs) {
                continue;
            }
            // 和前一天刚好差一整天 = 连续
            if (($ts - $prevTs) === $oneDaySecond) {
                $currentDays++;
                // 更新最大值
                $maxDays = max($maxDays, $currentDays);
            } else {
                // 断签，重置当前连续计数
                $currentDays = 1;
            }
            $prevTs = $ts;
        }

        return $maxDays;
    }
}