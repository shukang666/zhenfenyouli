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
use app\admin\model\forum\ForumModel;
use app\admin\model\member\CheckModel;
use app\admin\model\member\MemberModel;
use cmf\controller\RestBaseController;


class AuthController extends RestBaseController
{
    /** 累计垃圾打卡满 N 个自然日（可不连续）后邀请申请督导员 */
    protected const SUPERVISOR_INVITE_CLOCK_DAYS = 7;

    /*
     * 初始化中判断用户权限
     */
    public $user_info;
    public $user_id;
    public $openid;
    public $token;
    public $page_size;
    public $get_config;
    public function initialize(){

        $this->get_config= $info = cmf_get_option('set_config');
//        if (isset($info['app_endtime']) && strtotime($info['app_endtime']) < time()) {
////            $this->error("账号已到期,请联系管理员！");
//        }


        $openid = $this->request->header('openid');
        if(empty($openid)){
            $openid = $this->request->param('openid');
        }
        $user_info = CommonModel::getUserInfoByOpenid($openid);
        if(!empty($user_info)){
            $this->user_info = $user_info;
            $this->user_id = $user_info['id'];
            $this->openid = $user_info['openid'];
        }
        $this->page_size = $this->request->param('page_size',10);
    }

   /* public function initialize(){
        $token = $this->request->header('token');
        if(empty($token)){
            $token = $this->request->param('token');
        }
        $user_info = CommonModel::getUserInfoByToken($token,'*');
        if(!empty($user_info)){
            $this->user_info = $user_info;
            $this->user_id = $user_info['id'];
            $this->token = $token;
        }
    }*/

    public function checkUser(){
        if(empty($this->user_id)){
            $this->error('用户不存在');
        }

        if($this->user_info['status'] == 2){
            $this->error('您已被禁用');
        }
    }

    /** 垃圾打卡（forum type=2）累计自然日数，同一天多次计 1 天 */
    protected function countJunkClockDistinctDays(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $times = ForumModel::where(['type' => 2, 'user_id' => $userId])->column('create_time');
        if (empty($times)) {
            return 0;
        }
        $days = [];
        foreach ($times as $ts) {
            $days[date('Y-m-d', (int)$ts)] = true;
        }
        return count($days);
    }

    /**
     * 督导员申请邀请状态（供打卡成功、个人中心等展示）
     * check_status: 0 未申请 1 待审 2 通过 3 驳回
     */
    protected function buildSupervisorInvite(int $userId): array
    {
        $need = self::SUPERVISOR_INVITE_CLOCK_DAYS;
        $clockDays = $this->countJunkClockDistinctDays($userId);
        $member = MemberModel::where('id', $userId)->field('is_supervisor')->find();
        $isSupervisor = !empty($member['is_supervisor']);
        $check = CheckModel::where('user_id', $userId)->order('id desc')->find();
        $checkStatus = $check ? (int)$check['status'] : 0;

        $eligible = $clockDays >= $need && !$isSupervisor;
        $showInvite = $eligible && !in_array($checkStatus, [1, 2], true);
        $canApply = $eligible && ($checkStatus === 0 || $checkStatus === 3);

        return [
            'need_days' => $need,
            'clock_days' => $clockDays,
            'show_invite' => $showInvite ? 1 : 0,
            'can_apply' => $canApply ? 1 : 0,
            'check_status' => $checkStatus,
            'is_supervisor' => $isSupervisor ? 1 : 0,
        ];
    }

















































}
