<?php

namespace api\wxapp\controller;

use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveCateModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\active\ActiveUserModel;
use app\admin\model\active\WasteCateModel;
use app\admin\model\active\WasteModel;
use app\admin\model\active\WasteUserModel;
use app\admin\model\forum\ForumCommentModel;
use app\admin\model\forum\ForumLikeModel;
use app\admin\model\member\MemberModel;

class ActiveController extends AuthController
{
    /**
     * 测试
     * @OA\Post(
     *tags={"活动"},
     *path="/wxapp/active/test",
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
     * 低碳活动分类
     * @OA\Post(
     *tags={"活动"},
     *path="/wxapp/active/get_active_cate_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_active_cate_list(ActiveCateModel $activeCateModel)
    {
        $id = $this->request->param('id');
        $type = $this->request->param('type');
        if ($id) {
            $item = $activeCateModel->where('id', $id)->find();
            $this->success('请求成功', $item);
        } else {
            $where = [];
//            if ($type) $where[] = ['type', '=', $type];
            $list = $activeCateModel->where($where)
                ->order('list_order asc')
                ->paginate($this->page_size);
            $list = CommonModel::getMany($list, ['thumb']);
        }
        $this->success('请求成功', $list);
    }


    /**
     * 活动详情列表
     * @OA\Post(
     *tags={"活动"},
     *path="/wxapp/active/get_active_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="cate_id",description="类型  低碳类型",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="1.低碳类型,2.环保活动,3社区活动",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_put",description="1:首页推荐",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="is_my",description="1:我参加的活动",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_active_list(ActiveModel $activeModel, ActiveUserModel $activeUserModel)
    {
        $type = $this->request->param('type');
        $cate_id = $this->request->param('cate_id');
        $id = $this->request->param('id');
        $is_my = $this->request->param('is_my', 0);
        $is_put = $this->request->param('is_put', 0);
        if ($id) {
            $item = $activeModel->where('id', $id)->find();
            $item = CommonModel::getOne($item, ['thumb'], [], ['content']);
            $this->success('请求成功', $item);
        }
        $where = [];
        if ($type) $where[] = ['type', '=', $type];
        if ($cate_id) $where[] = ['cate_id', '=', $cate_id];
        if ($is_put) $where[] = ['is_put', '=', 1];
        if ($is_my) {
            $active_ids = $activeUserModel
                ->where('type', 1)  ->where('user_id', $this->user_id)
                ->column('active_id');
//            var_dump($active_ids);exit;
            $where[] = ['id', 'in', $active_ids];
        }

        $list = $activeModel->where($where)
            ->order('id desc')
            ->hidden(['content'])
            ->paginate($this->page_size);
        foreach ($list as &$item) {
            $item = CommonModel::getOne($item, ['thumb']);
            $item['people_num'] = $activeModel->where('id', $item['id'])->count();
            $item['date'] = date('Y-m-d', ($item['date']));
        }
        $list = CommonModel::getMany($list, ['thumb']);
        $this->success('请求成功', $list);
    }

    /**
     * 添加活动
     * @OA\Post(
     *tags={"活动"},
     *path="/wxapp/active/add_active_user",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="active_id",description="活动ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image",description="多图",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="content",description="内容",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_active_user(ActiveUserModel $activeUserModel, ActiveModel $activeModel)
    {
        $this->checkUser();
        $params = $this->request->param();
        $active = $activeModel->find($params['active_id']);
        if (!$active) $this->error('活动不存在');
        $params['type'] = $active['type'];
        $params['user_id'] = $this->user_id;
        if (!empty($params['image'])) $params['image'] = json_encode($params['image']);
        $activeUserModel->save($params);
        $this->success('提交成功');
    }


    /**
     * 提交活动列表
     * @OA\Post(
     *tags={"活动"},
     *path="/wxapp/active/get_active_user_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="1.低碳类型,2.环保活动,3社区活动",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="active_id",description="活动ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="user_type",description="1:我的",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_active_user_list(ActiveUserModel $activeUserModel)
    {
        $id = $this->request->param('id');
        if ($id) {
            $item = $activeUserModel
                ->alias('a')
                ->join('member b', 'a.user_id=b.id')
                ->field('a.*,b.nickname,b.avatar')
                ->where('a.id', $id)->find();
            if (!$item) $this->error('数据不存在');
            $item = CommonModel::getOne($item, ['avatar'], ['image']);
            $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                ->where('user_id', $this->user_id)->where('type', 1)
                ->where('forum_type', 2)
                ->count();
            $item['comment_total'] = ForumCommentModel::where('info_id', $item['id'])
                ->where('forum_type', 2)->count();
            $activeUserModel->where('id', $id)->inc('view_num')->update();
            $this->success('请求成功', $item);
        }
        $type = $this->request->param('type');
        $user_type = $this->request->param('user_type');
        $active_id = $this->request->param('active_id');
        $where = [];
        if ($type) $where[] = ['a.type', '=', $type];
        if ($active_id) $where[] = ['a.active_id', '=', $active_id];
        if ($user_type == 1) $where[] = ['a.user_id', '=', $this->user_id];
        $list = $activeUserModel
            ->alias('a')
            ->join('member b', 'a.user_id=b.id')
            ->field('a.*,b.nickname,b.avatar')
            ->where($where)->paginate($this->page_size);
        $list = CommonModel::getMany($list, ['avatar'], ['image']);

        foreach ($list as &$item) {
            $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                ->where('user_id', $this->user_id)->where('type', 1)
                ->where('forum_type', 2)
                ->count();
            $item['comment_total'] = ForumCommentModel::where('info_id', $item['id'])
                ->where('forum_type', 2)->count();
            $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                ->where('user_id', $this->user_id)->where('type', 1)
                ->where('forum_type', 2)
                ->count();
            $item['comment_total'] = ForumCommentModel::where('info_id', $item['id'])
                ->where('forum_type', 2)->count();
        }
        $this->success('请求成功', $list);
    }


    /**
     * 低碳生活
     * @OA\Post(
     *tags={"低碳"},
     *path="/wxapp/active/get_waste_cate_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_waste_cate_list(WasteCateModel $cateModel)
    {

        $id = $this->request->param('id');
        $type = $this->request->param('type');
        if ($id) {
            $item = $cateModel->where('id', $id)->find();
            $this->success('请求成功', $item);
        } else {
            $where = [];
//            if ($type) $where[] = ['type', '=', $type];
            $list = $cateModel->where($where)
                ->order('list_order asc')
                ->paginate($this->page_size);
            $list = CommonModel::getMany($list, ['thumb']);
            $this->success('请求成功', $list);

        }
    }


    /**
     * 低碳生活-垃圾分类指导
     * @OA\Post(
     *tags={"低碳"},
     *path="/wxapp/active/get_waste_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="cate_id",description="垃圾",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="详情",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_waste_list(WasteModel $wasteModel, WasteUserModel $wasteUserModel)
    {
        $id = $this->request->param('id');
        if ($id) {
            $item = $wasteModel->where('id', $id)->find();
            if (!$item) $this->error('数据不存在');
            $item = CommonModel::getOne($item, [], [], ['content']);
            //添加学习进度
            $where = ['user_id' => $this->user_id, 'waste_id' => $id, 'cate_id' => $item['cate_id']];
            $wasteUser = $wasteUserModel->where($where)->find();
            if (!$wasteUser) {
                $wasteUserModel->save($where);
            }
            $this->success('请求成功', $item);
        } else {
            $where = [];
            $cate_id = $this->request->param('cate_id');
            if ($cate_id) $where[] = ['cate_id', '=', $cate_id];
            $item['user_count'] = $wasteUser_count = $wasteUserModel->where($where)->where('user_id', $this->user_id)->count();
            $item['waste_count'] = $wasteModel->where($where)->count();
            $list = $wasteModel
                ->where($where)
                ->order('id asc')
                ->hidden(['content'])
                ->paginate($this->page_size)->each(function ($item) use ($wasteUserModel) {
                    $item['is_study'] = $wasteUserModel
                        ->where(['user_id' => $this->user_id, 'waste_id' => $item['id']])
                        ->count();
                });
            $list = CommonModel::getMany($list, ['thumb']);
            $item['list'] = $list;
            $this->success('请求成功', $item);
        }
    }



}