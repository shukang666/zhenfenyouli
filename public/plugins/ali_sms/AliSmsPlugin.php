<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2018 http://www.wuwuseo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\ali_sms;

use cmf\lib\Plugin;

class AliSmsPlugin extends Plugin
{
    public $info = array(
        'name'=>'AliSms',
        'title'=>'阿里云通信手机验证码',
        'description'=>'阿里云通信手机验证码',
        'status'=>1,
        'author'=>'五五',
        'version'=>'1.2'
    );
    
    public $has_admin=0;//插件是否有后台管理界面
    
    public function install()
    {//安装方法必须实现
        return true;//安装成功返回true，失败false
    }
    
    public function uninstall()
    {//卸载方法必须实现
        return true;//卸载成功返回true，失败false
    }
    
    //实现的send_mobile_verification_code钩子方法
    public function sendMobileVerificationCode($param)
    {
        $mobile        = $param['mobile'];//手机号
        $code          = $param['code'];//验证码
        $config        = $this->getConfig();
        $expire_minute = intval($config['expire_minute']);
        $expire_minute = empty($expire_minute) ? 30 : $expire_minute;
        $expire_time   = time() + $expire_minute * 60;
        $result        = false;
        //send message
        if ($code!==false) {
            $params['PhoneNumbers'] = $mobile;
            $params['SignName'] = $config['SignName'];
            $params['TemplateCode'] = $config['TemplateCode'];
            $params['TemplateParam'] = array(
                $config['codeKey']=>$code
            );
            $sms = new \plugins\ali_sms\lib\Sms($config['accessKeyId'], $config['accessKeySecret']);
            $reponse = $sms->sendSms($params);
            if ($reponse) {
                $result = array(
                    'Ok'=>0,
                    'message'=>'发送成功！'
                );
            } else {
                $result = array(
                    'Ok'=>1,
                    'message'=> $sms->getError()
                );
            }
        } else {
            $result = array(
                'Ok'=>1,
                'message'=>'发送次数过多，不能再发送'
            );
        }
        return $result;
    }

    public function sendReceiveMsg($param){

      /*  $ali_sms = cmf_get_plugin_class("AliSms");
        $sms = new $ali_sms();

        $params1 = array(
            "mobile" => $send_mobile1 ,
            'sn' => $Model['sn'],
            'pack_sn' => $Model['pack_sn'],
            'spec' => $Model['spec'],
            'length' => $Model['length'],
            'customer_name' => $Model['customer_name'],
            'to_name' => $Model['to_name'],
        );

        $sms->sendReceiveMsg($params1);*/

        $mobile        = $param['mobile'];//手机号
        $sn        = $param['sn'];
        $pack_sn        = $param['pack_sn'];
        $spec        = $param['spec'];
        $length        = $param['length'];
        $customer_name        = $param['customer_name'];
        $to_name        = $param['to_name'];

        $config        = $this->getConfig();

        $params['PhoneNumbers'] = $mobile;
        $params['SignName'] = '普洛兰';
        $params['TemplateCode'] = 'SMS_226401245';
        //TemplateParam没有值直接删除掉
        $params['TemplateParam'] = array(
            'sn' => $sn,
            'pack_sn' => $pack_sn,
            'spec' => $spec,
            'length' => $length,
            'customer_name' => $customer_name,
            'to_name' => $to_name,
        );
        $sms = new \plugins\ali_sms\lib\Sms($config['accessKeyId'], $config['accessKeySecret']);
        $reponse = $sms->sendSms($params);
        if ($reponse) {
            $result = array(
                'error'=>0,
                'message'=>'发送成功！'
            );
        } else {
            $result = array(
                'error'=>1,
                'message'=> $sms->getError()
            );
        }

        return $result;
    }




}
