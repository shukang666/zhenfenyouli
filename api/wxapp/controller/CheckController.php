<?php

namespace api\wxapp\controller;

use app\admin\model\member\CheckModel;
use app\admin\model\member\MemberModel;

class CheckController extends AuthController
{
    /**
     * 小区文明标兵申请
     * @OA\Post(
     *tags={"小区文明标兵"},
     *path="/wxapp/check/add_check",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="编辑",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="name",description="姓名",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="mobile",description="手机号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="community_id",description="小区id",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="reason",description="申请理由",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="active_type",description="期望参加活动  1 => '小区点位巡检',
    2 => '桶边督导',
    3 => '宣传活动',",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_check(CheckModel $checkModel)
    {
        $this->checkUser();
        $params = $this->request->param();
        if (empty($params['id'])) {
            $params['user_id'] = $this->user_id;
            $checkModel->save($params);
        } else {
            $item = $checkModel->find($params['id']);
            if ($item['status'] == 3) $params['status'] = 1;
            $checkModel->update($params, ['id' => $params['id']]);
        }
        $this->success('提交成功');
    }


    /**
     * 小区文明标兵申请详情
     * @OA\Post(
     *tags={"小区文明标兵"},
     *path="/wxapp/check/get_check",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_check(CheckModel $checkModel)
    {
        $this->checkUser();
        $item = $checkModel->where('user_id', $this->user_id)->find();
        if (empty($item)) $this->error('暂无数据');
        $item['status_name'] = $checkModel->get_status()[$item['status']]??'';
        $item['active_name'] = $checkModel->get_type()[$item['active_type']]??'';
        $this->success('请求成功', $item);
    }


    /**
     *
     * 文明标兵排行榜
     * @OA\Get(
     *      tags={"小区文明标兵"},
     *     path="/wxapp/check/get_ranking",
     *      *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_ranking()
    {
        $this->checkUser();
        $checkModel = new CheckModel();
        $MemberModel = new MemberModel();

        $list = $MemberModel
            ->alias('a')
            ->field('id user_id,nickname,avatar,level')
            ->order('level desc')
            ->paginate($this->page_size);

//        $list = $checkModel
//            ->alias('a')
//            ->rightjoin('member b', 'a.user_id=b.id')
////            ->where(['a.status' => 2])
//            ->field('b.id,nickname,avatar,level')
//            ->order('level desc')
//            ->paginate($this->page_size);
        //排行榜
        foreach ($list as &$item) {
            $item['avatar'] = cmf_get_asset_url($item['avatar']);
        }
        $this->success('获取成功', $list);
    }

}