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
use app\admin\model\forum\ForumModel;
use app\admin\model\health\HealthHomeModel;
use app\admin\model\member\CheckModel;
use app\admin\model\member\CommunityModel;
use app\admin\model\member\CommunityParentModel;
use app\admin\model\member\MemberModel;
use app\admin\model\order\OrderQrcodeModel;
use app\admin\model\order\ScanLogModel;
use app\admin\model\shop\ShopModel;
use app\admin\model\active\WasteModel;
use app\admin\model\active\WasteUserModel;
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
            ,shop_balance_total,shop_balance,point,shop_balance balance,is_environmental,is_supervisor,is_merchant,community_parent_id
            ')->find();
        $user['community'] = $CommunityModel->where(['id' => $user['community_id']])->value('name');
        $user['community_parent'] = CommunityParentModel::where(['id' => $user['community_parent_id']])->value('name');
        $user['is_answer'] = $AnswerModel//垃圾分类答题考试 是否及格
        ->where('status', 3)
            ->where('type', 1)
            ->where(['user_id' => $user['id']])
            ->count() ? 1 : 0;
        //是否学完所有垃圾分类指导内容（user_count >= waste_count 且 waste_count > 0）
        $waste_count = WasteModel::count();
        $user_count = WasteUserModel::where('user_id', $user['id'])->count();
        $user['is_learn'] = ($waste_count > 0 && $user_count >= $waste_count) ? 1 : 0;
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


        $setting = cmf_get_option('set_config');
        $user['continuous_check'] = $setting['continuous_check'];
        //当前还不是环保达人
        if($this->user_info['is_environmental'] == 0){
            //判断是否连续天数达标
            $array = ForumModel::where(['type' => 2, 'user_id' => $this->user_id])->column('create_time');
            $clock_count = $this->getNowContinueSignDays($array);
        }
        $user['clock_count'] = $clock_count ?? 0;
        $user['remaining_count'] = $user['continuous_check'] - $user['clock_count'];
        $user['junk_clock_days'] = $this->countJunkClockDistinctDays((int)$user['id']);
        $user['supervisor_invite'] = $this->buildSupervisorInvite((int)$user['id']);

        if (!empty($user['avatar'])) {
            $user['avatar'] = cmf_get_asset_url($user['avatar']);
        }

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
     *      *@OA\Parameter(name="community_parent_id",description="社区ID",in="query",required=false, @OA\Schema(type="string",)),
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


    /**
     * 获取一组打卡时间戳中 当前连续打卡天数（从最后一次打卡往前算）
     * @param array $timeStampArr 打卡时间戳数组
     * @return int 当前连续天数
     */
    function getNowContinueSignDays(array $timeStampArr): int
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

        $oneDaySecond = 86400;
        $count = 1;
        $lastTs = end($uniqueDayTs); // 最后打卡日期零点时间戳
        $todayZero = strtotime(date('Y-m-d'));

        // 校验最后打卡是不是今天或昨天，断档直接返回1或0
        // 情况1：最后打卡不是今天、也不是昨天，连续只有当天那1天
        if ($lastTs < $todayZero - $oneDaySecond) {
            return 1;
        }

        // 从倒数第二位往前倒推连续天数
        $reverseList = array_reverse($uniqueDayTs);
        $prev = $reverseList[0];
        foreach ($reverseList as $ts) {
            if ($ts === $prev) {
                continue;
            }
            if ($prev - $ts === $oneDaySecond) {
                $count++;
                $prev = $ts;
            } else {
                // 中间断开，终止统计
                break;
            }
        }

        return $count;
    }

    /**
     * @OA\Post(
     *      tags={"小程序-用户"},
     *      summary="每日签到",
     *     path="/wxapp/user/daily_sign",
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function daily_sign()
    {
        $this->checkUser();

        $today = date('Y-m-d');
        $todayTs = strtotime($today);

        // 检查今日是否已签到
        $exist = Db::name('user_sign')
            ->where('user_id', $this->user_id)
            ->where('sign_date', $today)
            ->find();
        if ($exist) {
            $this->error('今日已签到');
        }

        // 读取签到积分配置
        $config = cmf_get_option('set_config');
        $signPoint = intval($config['sign_point'] ?? 5);

        // 记录签到并发放积分
        Db::name('user_sign')->insert([
            'user_id'    => $this->user_id,
            'sign_date'  => $today,
            'point'      => $signPoint,
            'create_time'=> time(),
        ]);

        if ($signPoint > 0) {
            AccountLogModel::add_account_log($this->user_id, 2, 200, $signPoint, [
                'remark' => '每日签到',
            ]);
        }

        // 计算连续签到天数
        $signDates = Db::name('user_sign')
            ->where('user_id', $this->user_id)
            ->column('create_time');
        $continueDays = $this->getNowContinueSignDays($signDates);

        $this->success('签到成功', [
            'point'         => $signPoint,
            'continue_days' => $continueDays,
        ]);
    }

    /**
     * @OA\Post(
     *      tags={"小程序-用户"},
     *      summary="获取签到状态（是否已签到 + 连续天数）",
     *     path="/wxapp/user/sign_status",
     *     @OA\Response(response="200", description="An example resource"),
     *    @OA\Response(response="default", description="An example resource")
     * )
     */
    public function sign_status()
    {
        $this->checkUser();

        $today = date('Y-m-d');
        $todaySigned = Db::name('user_sign')
            ->where('user_id', $this->user_id)
            ->where('sign_date', $today)
            ->value('id') ? true : false;

        $signDates = Db::name('user_sign')
            ->where('user_id', $this->user_id)
            ->column('create_time');
        $continueDays = $this->getNowContinueSignDays($signDates);

        $this->success('获取成功', [
            'today_signed'  => $todaySigned,
            'continue_days' => $continueDays,
        ]);
    }

}
