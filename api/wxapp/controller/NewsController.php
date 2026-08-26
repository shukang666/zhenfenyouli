<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use api\wxapp\model\CommonModel;
use app\admin\model\basic\NewsCateModel;
use app\admin\model\basic\NewsModel;
use app\admin\model\forum\ForumCommentModel;
use app\admin\model\forum\ForumLikeModel;
use think\facade\Log;
use think\Model;

class NewsController extends AuthController
{

    /**
     * 资讯分类列表
     * @OA\Post(
     *     tags={"资讯"},
     *     path="/wxapp/news/get_news_cate",
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     *
     * )
     */
    public function get_news_cate()
    {
        //获取分类
        $NewsCateModel = new NewsCateModel();
        $map = [];
        $map[] = ['status', '=', 1];
        $data = $NewsCateModel->where($map)->select();
        $this->success('获取成功', $data);
    }

    /**
     * 获取资讯列表或详情
     * @OA\Post(
     *     tags={"资讯"},
     *     summary="",
     *     path="/wxapp/news/get_news",
     *@OA\Parameter(name="id",description="详情id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="cate_id",description="分类id",in="query",required=false, @OA\Schema(type="string",)),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     *
     * )
     */
    public function get_news($id = '', $cate_id = '', $keyword = '')
    {

        $Model = new NewsModel();
        if (empty($id)) {
            $map = [];
            $map[] = ['a.status', '=', 1];
            if (!empty($cate_id)) {
                $map[] = ['a.cate_id', '=', $cate_id];
            }
            if (!empty($keyword)) {
                $map[] = ['a.title', 'like', "%$keyword%"];
            }
            $data = $Model->where($map)
                ->alias('a')
//                ->join('news_cate b', 'a.cate_id=b.id')
                ->field('a.id,a.title,a.thumb,a.create_time')
                ->order('a.is_top desc,a.list_order,a.id desc')
                ->paginate($this->page_size);
            $data = CommonModel::getMany($data, ['thumb'], [], ['content']);
            $this->success('获取成功', $data);
        } else {
            $map = [];
            $map[] = ['id', '=', $id];
            $item = $Model->where($map)->find();
            $item = CommonModel::getOne($item, ['thumb'], [], ['content']);
            $item['is_like'] = ForumLikeModel::where('rid', $item['id'])
                ->where('user_id', $this->user_id)->where('type', 1)
                ->where('forum_type',3)
                ->count();
            $item['comment_total'] = ForumCommentModel::where('info_id', $item['id'])
                ->where('forum_type',3)->count();
            $this->success('获取成功', $item);
        }

    }


}
