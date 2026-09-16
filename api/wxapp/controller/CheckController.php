<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use app\admin\model\member\CheckModel;
use app\admin\model\member\CommunityCateModel;
use app\admin\model\member\CommunityParentModel;
use app\admin\model\member\MemberModel;
use app\admin\model\member\MemberMerchantInspectModel;
use app\admin\model\member\MemberShopHandleModel;
use app\admin\model\member\MemberShopModel;
use app\admin\model\shop\ShopModel;

class CheckController extends AuthController
{
    /**
     * 小区文明标兵申请文档内容
     * @OA\Post(
     *tags={"小区文明标兵"},
     *path="/wxapp/check/add_check_html",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="sign_image",description="签名图",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_check_html(){
        $sign_image = $this->request->param('sign_image');
        $public = new PublicController();
        $html = $public->pdf_html_show($sign_image);
        $this->success('成功',$html);
    }

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
     *@OA\Parameter(name="sign_image",description="签名图",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_check(CheckModel $checkModel)
    {
        $this->checkUser();
        $params = $this->request->param();

        // role_type: 1=督导员, 2=指导员（默认1）
        if (empty($params['id'])) {
            if(!empty($params['sign_image'])){
                $public = new PublicController();
                $pdf_file = $public->pdf($params['sign_image']);
                $params['pdf_file'] = $pdf_file;
            }

            $params['user_id'] = $this->user_id;
            if (empty($params['role_type'])) {
                $params['role_type'] = 1;
            }
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
        $item = $checkModel->where('user_id', $this->user_id)->order('id desc')->find();
        if (empty($item)) $this->error('暂无数据');
        $item['status_name'] = $checkModel->get_status()[$item['status']]??'';
        $getType = $checkModel->get_type();
        $item['active_name'] = $getType[$item['active_type']]??'';
        $item['role_type'] = intval($item['role_type'] ?? 1);
        // 申请通过后，role_type(1/2) 表示角色身份
        $item['is_approved'] = ($item['status'] == 2);
        $item['role_name'] = $item['role_type'] == 2 ? '指导员' : '督导员';

        $item['sign_image'] = empty($item['sign_image'])?'':cmf_get_asset_url($item['sign_image']);
        $item['pdf_file'] = empty($item['pdf_file'])?'':cmf_get_asset_url($item['pdf_file']);
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

    /**
     * 商家申请
     * @OA\Post(
     *tags={"商家申请"},
     *path="/wxapp/check/add_shop",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="编辑",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="name",description="姓名",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="contact",description="联系人",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="mobile",description="手机号",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="community",description="所在社区",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="type",description="类型，1：机构，2：经营场所",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_shop()
    {
        $this->checkUser();
        $params = $this->request->param();
        if (empty($params['id'])) {
            $params['user_id'] = $this->user_id;
            MemberShopModel::create($params);
        } else {
            $item = MemberShopModel::find($params['id']);
            if ($item['status'] == 3) $params['status'] = 1;
            unset($params['openid']);
            MemberShopModel::where(['id' => $params['id']])->update($params);
        }
        $this->success('提交成功');
    }


    /**
     * 商家申请详情
     * @OA\Post(
     *tags={"商家申请"},
     *path="/wxapp/check/get_shop",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_shop()
    {
        $this->checkUser();
        $item = MemberShopModel::where('user_id', $this->user_id)->append(['status_name', 'type_name', 'community_name'])->find();
        if (empty($item)) $this->error('暂无数据');
        $this->success('请求成功', $item);
    }


    /**
     *商家 上传处理凭证
     * @OA\Post(
     *tags={"商家申请"},
     *path="/wxapp/check/add_handle",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_one",description="门头照片",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_two",description="宣传品照片",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_three",description="投放点位照片",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_four",description="管理制度/收运协议照片",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="image_five",description="桶内照片",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function add_handle()
    {

        $this->checkUser();
        $params = $this->request->param();
        $map = [];
        $map[] = ['user_id', '=', $this->user_id];
        $handle_info = MemberShopModel::where($map)->find();
        if(empty($handle_info)) $this->error('请先申请商家');
        if($handle_info['status'] != 2) $this->error('商家未审核通过');

        $parent_info = CommunityParentModel::where('id',$this->user_info['community_parent_id'])->find();
        if(empty($parent_info)) $this->error('社区不能为空');


        $map = [];
        $map[] = ['shop_id', '=', $handle_info['id']];
        $map[] = ['user_id', '=', $this->user_id];
        $handle_handle_info = MemberShopHandleModel::where($map)->order('id desc')->find();
        if(!empty($handle_handle_info)){
            if($handle_handle_info['create_time'] > strtotime('-'.$parent_info['inspection_hour'].'hour')){
                $remaining_seconds = $handle_handle_info['create_time'] - strtotime('-'.$parent_info['inspection_hour'].'hour');
                $this->error('当前还不可打卡',['remaining_seconds' => $remaining_seconds]);
            }
        }

        $point = $parent_info['inspection_point'];
        AccountLogModel::add_account_log($this->user_id, 2, 224, $point, []);

        $params['user_id'] = $this->user_id;
        $params['shop_id'] = $handle_info['id'];
        MemberShopHandleModel::create($params);
        $this->success('提交成功');
    }



    /**
     *商家通知列表
     * @OA\Post(
     *tags={"商家申请"},
     *path="/wxapp/check/get_handle_list",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_handle_list(){
//        $this->checkUser();
        $page_size = $this->request->param('page_size', 10);
        $list = MemberShopHandleModel::order('id','desc')->paginate($page_size)->each(function ($item){
            $item['image_one'] = empty($item['image_one'])?'':cmf_get_asset_url($item['image_one']);
            $item['image_two'] = empty($item['image_two'])?'':cmf_get_asset_url($item['image_two']);
            $item['image_three'] = empty($item['image_three'])?'':cmf_get_asset_url($item['image_three']);
            $item['image_four'] = empty($item['image_four'])?'':cmf_get_asset_url($item['image_four']);
            $item['image_five'] = empty($item['image_five'])?'':cmf_get_asset_url($item['image_five']);
            return $item;
        });
        $this->success('请求成功',$list);
    }

    /**
     * 商户共治 - 提交（指导员）
     * POST /wxapp/check/merchant_inspect
     */
    public function merchant_inspect()
    {
        $this->checkUser();
        $params = $this->request->param();

        if (empty($params['company'])) {
            $this->error('请输入公司名称');
        }

        $data = [
            'user_id'     => $this->user_id,
            'company'     => trim($params['company']),
            'image_one'   => trim($params['image_one'] ?? ''),
            'image_two'   => trim($params['image_two'] ?? ''),
            'image_three' => trim($params['image_three'] ?? ''),
            'image_four'  => trim($params['image_four'] ?? ''),
            'image_five'  => trim($params['image_five'] ?? ''),
            'status'      => 0,
            'create_time' => time(),
        ];

        MemberMerchantInspectModel::create($data);
        $this->success('提交成功');
    }

    /**
     * 商户共治 - 我的提交记录
     * GET /wxapp/check/merchant_inspect_list
     */
    public function merchant_inspect_list()
    {
        $this->checkUser();
        $page_size = $this->request->param('page_size', 10);
        $list = MemberMerchantInspectModel::where('user_id', $this->user_id)
            ->order('id', 'desc')
            ->paginate($page_size)
            ->each(function ($item) {
                $item['image_one']   = empty($item['image_one']) ? '' : cmf_get_asset_url($item['image_one']);
                $item['image_two']   = empty($item['image_two']) ? '' : cmf_get_asset_url($item['image_two']);
                $item['image_three'] = empty($item['image_three']) ? '' : cmf_get_asset_url($item['image_three']);
                $item['image_four']  = empty($item['image_four']) ? '' : cmf_get_asset_url($item['image_four']);
                $item['image_five']  = empty($item['image_five']) ? '' : cmf_get_asset_url($item['image_five']);
                return $item;
            });
        $this->success('请求成功', $list);
    }

}