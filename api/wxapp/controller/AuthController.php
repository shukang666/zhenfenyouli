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
use cmf\controller\RestBaseController;


class AuthController extends RestBaseController
{

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

















































}
