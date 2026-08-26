<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use cmf\controller\RestBaseController;
use think\Validate;
use api\wxapp\controller\AuthController;

class WxPayController extends RestBaseController
{

    /* 首先在服务器端调用微信【统一下单】接口，返回prepay_id和sign签名等信息给前端，前端调用微信支付接口 */
    public function pay($total_fee, $openid, $order_id)
    {
        $appid = config('wxapp.appid');;//小程序的appid
        $body = '订单支付';
        $mch_id = config('wxapp.mch_id');;
        $KEY = config('wxapp.key');

        $nonce_str = (string)mt_rand(10000, 99999) . time();//随机字符串
        $notify_url = cmf_get_domain() . '/api/wxapp/pay/notify'; //支付完成回调地址url,不能带参数
        $out_trade_no = $order_id; //商户订单号
        $spbill_create_ip = $_SERVER['SERVER_ADDR'];
        $trade_type = 'JSAPI';//交易类型 默认JSAPI
        //这里是按照顺序的 因为下面的签名是按照(字典序)顺序 排序错误 肯定出错
        $post['appid'] = $appid;
        $post['body'] = $body;
        $post['mch_id'] = $mch_id;
        $post['nonce_str'] = $nonce_str;//随机字符串
        $post['notify_url'] = $notify_url;
        $post['openid'] = $openid;
        $post['out_trade_no'] = $out_trade_no;
        $post['spbill_create_ip'] = $spbill_create_ip;//服务器终端的ip
        $post['total_fee'] = intval($total_fee); //总金额 最低为一分钱 必须是整数
        $post['trade_type'] = $trade_type;
        $sign = $this->MakeSign($post, $KEY); //签名
        $post_xml = '<xml> <appid>' . $appid . '</appid>
　　　　　　<body>' . $body . '</body>
　　　　　　<mch_id>' . $mch_id . '</mch_id>
　　　　　　<nonce_str>' . $nonce_str . '</nonce_str>
　　　　　　<notify_url>' . $notify_url . '</notify_url>
　　　　　　<openid>' . $openid . '</openid>
　　　　　　<out_trade_no>' . $out_trade_no . '</out_trade_no>
　　　　　　<spbill_create_ip>' . $spbill_create_ip . '</spbill_create_ip>
　　　　　　<total_fee>' . $total_fee . '</total_fee>
　　　　　　<trade_type>' . $trade_type . '</trade_type>
　　　　　　<sign>' . $sign . '</sign></xml> ';

        //统一下单接口prepay_id
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $xml = $this->http_request($url, $post_xml); //POST方式请求http

        $array = $this->xml2array($xml); //将【统一下单】api返回xml数据转换成数组，全要大写

        if ($array['return_code'] == 'SUCCESS' && $array['result_code'] == 'SUCCESS') {

            $time = time();
            $tmp = []; //临时数组用于签名
            $tmp['appId'] = $appid;
            $tmp['nonceStr'] = $nonce_str;
            $tmp['package'] = 'prepay_id=' . $array['prepay_id'];
            $tmp['signType'] = 'MD5';
            $tmp['timeStamp'] = "$time";
            $data['code'] = 1;
            $data['timeStamp'] = "$time"; //时间戳
            $data['nonceStr'] = $nonce_str; //随机字符串
            $data['signType'] = 'MD5'; //签名算法，暂支持 MD5
            $data['package'] = 'prepay_id=' . $array['prepay_id']; //统一下单接口返回的 prepay_id 参数值，提交格式如：prepay_id=*
            $data['paySign'] = $this->MakeSign($tmp, $KEY); //签名,具体签名方案参见微信公众号支付帮助文档;
            $data['out_trade_no'] = $out_trade_no;
        } else {
            if (!empty($array['err_code_des'])) {
                $msg = $array['err_code_des'];
            } else {
                if (!empty($array['return_msg'])) {
                    $msg = $array['return_msg'];
                } else {
                    $msg = '错误';
                }
            }
            //$msg = !empty($array['return_msg']) ? $array['return_msg'] : !empty($array['err_code_des']) ? $array['err_code_des'] : '错误';
            $data['code'] = 0;
            $data['msg'] = $msg;
        }
        return $data;
    }

    /** * 生成签名, $KEY就是支付key * @return 签名 */
    public function MakeSign($params, $KEY)
    {
        //签名步骤一：按字典序排序数组参数
        ksort($params);
        $string = $this->ToUrlParams($params); //参数进行拼接key=value&k=v
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /** * 将参数拼接为url: key=value&key=value * @param $params * @return string */
    public function ToUrlParams($params)
    {
        $string = '';
        if (!empty($params)) {
            $array = array();
            foreach ($params as $key => $value) {
                $array[] = $key . '=' . $value;
            }
            $string = implode("&", $array);
        }
        return $string;
    }

    /** * 调用接口， $data是数组参数 * @return 签名 */
    public function http_request($url, $data = null, $headers = array())
    {
        $curl = curl_init();
        if (count($headers) >= 1) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    //获取xml里面数据，转换成array
    public function xml2array($xml)
    {
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }


}
