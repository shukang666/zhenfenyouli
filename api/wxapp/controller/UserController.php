<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\AnswerModel;
use api\wxapp\model\CommonModel;
use api\wxapp\model\AddressModel;
use api\wxapp\model\ShopOrderModel;
use api\wxapp\model\VipModel;
use app\admin\model\health\HealthHomeModel;
use app\admin\model\member\CheckModel;
use app\admin\model\member\CommunityModel;
use app\admin\model\member\MemberModel;
use app\admin\model\order\OrderQrcodeModel;
use app\admin\model\order\ScanLogModel;
use app\admin\model\shop\ShopModel;
use cmf\lib\Storage;
use think\facade\Db;
use think\Model;

class UserController extends AuthController
{

    /**
     * @OA\Get(
     *      tags={"小程序-用户"},
     *      summary="用户-获取用户信息",
     *     path="/wxapp/user/get_user_info",
     *      *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_user_info()
    {
        $this->checkUser();
        $memberModel = new MemberModel();
        $CommunityModel = new CommunityModel();
        $AnswerModel = new AnswerModel();
        $CheckModel = new CheckModel();
        $user = $memberModel->where(['id' => $this->user_id])
            ->field('id,nickname,true_name,mobile,avatar,openid,level,community_id
            ,shop_balance_total,shop_balance,point,shop_balance balance
            ')->find();
        $user['community'] = $CommunityModel->where(['id' => $user['community_id']])->value('name');
        $user['is_answer'] = $AnswerModel//垃圾分类答题考试 是否及格
        ->where('status', 3)
            ->where('type', 1)
            ->where(['user_id' => $user['id']])
            ->count() ? 1 : 0;
        $user['check_status'] = $CheckModel->where('user_id', $user['id'])->value('status') ?? 0;
        $user['min_app_id'] = $this->get_config['min_app_id'];
        $shop_info = ShopModel::where('user_id', $user['id'])->field('status,msg')->find();
        if ($shop_info) {
            $user['shop_status'] = $shop_info['status'];
            $user['shop_msg'] = $shop_info['msg'];
        } else {
            $user['shop_status'] = 0;
            $user['shop_msg'] = '';
        }

        $HealthHomeModel = new HealthHomeModel();
        $Healthids = $HealthHomeModel->where('type',1)->column('id');
        $user['is_exam'] = Db::name('exam_gift')
            ->where('exam_id','in', $Healthids)
            ->where(['openid' => $user['openid']])->count() ? 1 : 0;

        $this->success('获取成功', $user);
    }


    /**
     * @OA\Post(
     *      tags={"小程序-用户"},
     *      summary="用户-更新用户信息",
     *     path="/wxapp/user/update_user",
     *      *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *      *@OA\Parameter(name="true_name",description="姓名",in="query",required=false, @OA\Schema(type="string",)),
     *      *@OA\Parameter(name="nickname",description="昵称",in="query",required=false, @OA\Schema(type="string",)),
     *      *@OA\Parameter(name="avatar",description="头像",in="query",required=false, @OA\Schema(type="string",)),
     *      *@OA\Parameter(name="mobile",description="手机号",in="query",required=false, @OA\Schema(type="string",)),
     *      *@OA\Parameter(name="community_id",description="小区ID",in="query",required=false, @OA\Schema(type="string",)),
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function update_user()
    {
        $this->checkUser();
        $data = $this->request->param();
//        $allow_field = ['nickname', 'true_name', 'avatar', 'mobile', 'ali_account', 'ali_name'];
//
//        foreach ($data as $k => $item) {
//            if (!in_array($k, $allow_field)) {
//                $this->error($k . '字段不允许修改');
//            }
//        }


        CommonModel::updateUser($this->user_id, $data);
        $this->success('更新成功');
    }


    /**
     * @OA\Get(
     *     tags={"小程序-用户"},
     *     summary="获取小程序分享码",
     *     path="/wxapp/user/get_share_img",
     *      *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_share_img()
    {

        $this->checkUser();
        $UserModel = new MemberModel();
        $user = $this->user_info;
        if (!empty($user['share_img'])) {
            $share_img = $user['share_img'];
            $this->success('获取成功', cmf_get_asset_url($share_img));
        } else {
            $vcode = $this->user_info['vcode'];
            $ali_file_path = CommonModel::setShareImage($vcode, $vcode . '.png', 'dz1111');
            $UserModel->where(['id' => $this->user_id])->save(['share_img' => $ali_file_path]);
            $this->success('获取成功', cmf_get_asset_url($ali_file_path));
        }
    }


    /**
     * @OA\Get(
     *     tags={"小程序-用户"},
     *     summary="我的团队",
     *     path="/wxapp/user/my_child",
     *      *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function my_child()
    {

        $this->checkUser();
        $Model = new MemberModel();
        $map = [];
        $map['pid'] = $this->user_id;
        $data = $Model->field('id user_id,nickname,avatar,mobile,true_name,create_time')->where($map)->order('id desc')->paginate($this->page_size);
        $data = getFullData($data);
        $this->success('获取成功', $data);

    }


    /**
     * @OA\Post(
     *     tags={"小程序-用户"},
     *     summary="添加、编辑、删除、设置默认收货地址",
     *     path="/wxapp/user/post_address_data",
     *
     *       @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="add，edit，delete,default",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *       @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="编辑、删除、设置默认时传",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *       @OA\Parameter(
     *         name="province_code",
     *         in="query",
     *         description="省code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *        @OA\Parameter(
     *         name="province",
     *         in="query",
     *         description="省值",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *       @OA\Parameter(
     *         name="city_code",
     *         in="query",
     *         description="市code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *        @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="市",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *        @OA\Parameter(
     *         name="county_code",
     *         in="query",
     *         description="县区",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *        @OA\Parameter(
     *         name="county",
     *         in="query",
     *         description="县区",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *        @OA\Parameter(
     *         name="address",
     *         in="query",
     *         description="详细地址",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *       @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="姓名",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *       @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="电话",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="is_default",
     *         in="query",
     *         description="1默认",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function post_address_data()
    {
        if ($this->request->isPost()) {
            $this->checkUser();
            $data = $this->request->param();
            $type = $this->request->param('type');
            $id = $this->request->param('id');
            $is_default = $this->request->param('is_default', 0);

            $AddressModel = new AddressModel();

            if ($type == 'add' || $type == 'edit') {
                $check_res = $this->validate($data, 'Address');

                if ($check_res !== true) {
                    $this->error($check_res);
                }
            }

            if ($type == 'add') {

                if ($is_default == 1) {
                    //清除其他的默认
                    $AddressModel->where(['is_default' => 1, 'user_id' => $this->user_id])->update(['is_default' => 0]);
                }

                $data['create_time'] = time();
                $data['user_id'] = $this->user_id;
                $data['is_default'] = $is_default;

                /*  //经纬度
                  $lon_lat = $this->search_address($data['province'].$data['city'].$data['county'].$data['address']);

                  $data['lon'] = $lon_lat['lon'];
                  $data['lat'] = $lon_lat['lat'];*/

                $res = $AddressModel->save($data);
                if ($res) {
                    $this->success("添加成功!");
                } else {
                    $this->error('添加失败');
                }
            } elseif ($type == 'edit') {

                if ($is_default == 1) {
                    //清除其他的默认
                    $AddressModel->where(['is_default' => 1, 'user_id' => $this->user_id])->update(['is_default' => 0]);
                }

                $Model = AddressModel::where(['id' => $id, 'user_id' => $this->user_id])->find();
                if (empty($Model)) {
                    $this->error('地址不存在');
                } else {
                    $data['update_time'] = time();
                    $data['is_default'] = $is_default;

                    /* //经纬度
                     $lon_lat = $this->search_address($data['province'].$data['city'].$data['county'].$data['address']);

                     $data['lon'] = $lon_lat['lon'];
                     $data['lat'] = $lon_lat['lat'];*/

                    $Model->save($data);
                }
                $this->success("更新成功!");
            } elseif ($type == 'delete') {

                $Model = AddressModel::where(['id' => $id, 'user_id' => $this->user_id])->find();
                if (empty($Model)) {
                    $this->error('地址不存在');
                } else {
                    $Model->save(['status' => 0]);
                }
                $this->success("删除成功!");
            } elseif ($type == 'default') {

                $Model = AddressModel::where(['id' => $id, 'user_id' => $this->user_id])->find();
                if (empty($Model)) {
                    $this->error('地址不存在');
                } else {
                    $AddressModel->where(['is_default' => 1, 'user_id' => $this->user_id])->update(['is_default' => 0]);
                    $Model->save(['is_default' => 1]);
                }
                $this->success("设置成功!");
            }
        } else {
            $this->error('非法传参');
        }
    }

    /**
     * @OA\Get(
     *     tags={"小程序-用户"},
     *     summary="获取地址信息",
     *     path="/wxapp/user/get_address_info",
     *
     *
     *       @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="传id获取详情 不传获取列表",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */

    public function get_address_info()
    {
        $this->checkUser();
        $AddressModel = new AddressModel();
        $id = $this->request->param('id');

        if (empty($id)) {
            $map = [];
            $map['status'] = 1;
            $map['user_id'] = $this->user_id;
            $data = $AddressModel->where($map)->order('is_default desc')->select();
        } else {
            $data = $AddressModel->where(['id' => $id])->find();
        }

        $this->success('获取成功', $data);
    }


}
