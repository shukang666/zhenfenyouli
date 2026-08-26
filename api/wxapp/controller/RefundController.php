<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use api\wxapp\model\CommonModel;
use api\wxapp\model\ShopMoneyLogModel;
use think\facade\Log;

class RefundController
{

    public $config;


    /**
     * TODO 微信申请退款操作
     * 重要变量：
     *      $order_sn = '2018000082009' 自己服务器的订单编号
     *      $refund_fee                 需要退款的金额 (例:10.50)
     *      $wxPayResultJsonRes         前期微信支付成功后回调保存的数据，
     *                                  原本在数据库中以 json字符串的形式保存，
     *                                  此处是取出后再 json_decode('xxxx',true)转化为了arr数组形式
     * 据库中以 json字符串的形式为 ————
     * {"appid":"wx87xxxxxxxxbc0","bank_type":"CFT",
     * "cash_fee":"2","fee_type":"CNY","is_subscribe":"N",
     * "mch_id":"1xxxxxx02","nonce_str":"t8wcdduity6f6k5acng33wzv5z56o7sh",
     * "openid":"okxsf5YWzAzEPNoV31IRqft-fa1c","out_trade_no":"201xxxxxx2709M15362284007942",
     * "result_code":"SUCCESS","return_code":"SUCCESS","time_end":"20180906180644",
     * "total_fee":"2","trade_type":"JSAPI","transaction_id":"4200000171201809060657362048"}
     */
    public function pay_refund($order_num,$price=0){
        $return_model = CommonModel::getItemModel($order_num);
        $order_info = $return_model['order_info'];

        $pay_price = $order_info['price'];

        if(empty($price)){
            $price = $pay_price;
        }

        if($price > $pay_price){
            return ['code'=>0,'msg'=>"金额大于可退金额"];
        }

        $refund_fee = intval(strval($price*100));
        $total_fee = intval(strval($pay_price*100));

        /*-----TODO 此处是我项目业务的特定处理逻辑，仅供参考---------------高能注释------------------*/
        //$wxPayResultJsonRes 请参考上面的介绍，自行获取
        //$wxPayResultJsonRes = $orderModel->getWxPayResultJsonRes($order_sn);
        /*-----------------------------------------------------------------------------------*/
        if ($refund_fee){

            //$out_refund_no 商户退款单号 自定义而已
            $out_refund_no = $order_num.'refund';

            //统一下单退款参数构造
            $unifiedorder = array(
                'appid' => config('wxapp.appid'),//小程序的appid
                'mch_id' =>  config('wxapp.mch_id'),
                'nonce_str' => self::getNonceStr(),
                'out_trade_no' => $order_num,
                // out_refund_no 这个建议要唯一，避免出现请求一次，实际退款两次的情况
                'out_refund_no' => $out_refund_no,
                'total_fee' => $total_fee,
                //此处refund_fee 注意前面不要使用 number_format（）转化成字符串 ，或者更改为： number_format(5000, 2, '.', '')
                'refund_fee' => $refund_fee,
            );
            $unifiedorder['sign'] = self::makeSign($unifiedorder);
            //请求数据
            $xmldata = self::array2xml($unifiedorder);
            $opUrl = "https://api.mch.weixin.qq.com/secapi/pay/refund";
            $res = self::curl_post_ssl_refund($opUrl, $xmldata);

            Log::write($res,$order_num);
            if (!$res) {
                return ['code'=>0,'msg'=>"Can't connect the server"];
            }
            $content = self::xml2array($res);

            if (isset($content['result_code']) && strval($content['result_code']) == 'FAIL') {
                return ['code'=>0,'msg'=>$content['err_code_des']];
            }
            if (isset($content['return_code']) && strval($content['return_code']) == 'FAIL') {
                return ['code'=>0,'msg'=>strval($content['return_msg'])];
            }
            ShopMoneyLogModel::addLog($order_info['user_id'],$order_num,$order_info['price'],2);
            //成功
            return ['code'=>1,'msg'=>'退款成功','data'=>$content];
        }else{
            return ['code'=>0,'msg'=>'退款金额不符'];
        }
    }

    //---------------------------------------------------------------用到的函数------------------------------------------------------------

    /**
     * 此方法是为了进行 微信退款操作的 专属定制哦
     * (嘁，其实就是照搬了 人家官方的PHP Demo代码咯)
     * TODO 尤其注意代码中涉及到的 "证书使用方式（二选一）"
     * TODO 证书的路径要求为 服务器中的绝对路径[我的服务器为 CentOS6.5]
     * TODO 证书是 在微信支付开发文档中有所提及，可自行获取保存
     */
    protected function curl_post_ssl_refund($url, $vars, $second=30,$aHeader=array())
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        //TODO 以下两种方式需选择一种
        /*------- --第一种方法，cert 与 key 分别属于两个.pem文件--------------------------------*/
        //默认格式为PEM，可以注释
        //curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLCERT,'./wx_pem/apiclient_cert.pem');
        //默认格式为PEM，可以注释
        //curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLKEY,'./wx_pem/apiclient_key.pem');
        /**
         * 补充 当找不到ca根证书的时候还需要rootca.pem文件
         * TODO 注意，微信给出的压缩包中，有提示信息：
         *      由于绝大部分操作系统已内置了微信支付服务器证书的根CA证书,
         *      2018年3月6日后, 不再提供CA证书文件（rootca.pem）下载
         */
        //curl_setopt($ch, CURLOPT_CAINFO,'/mnt/www/Public/certxxxxxxxxxxxxxxxxxxxx755/rootca.pem');

        /*----------第二种方式，两个文件合成一个.pem文件----------------------------------------*/
        //curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');

        if( count($aHeader) >= 1 ){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }

        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
        $data = curl_exec($ch);
        if($data){
            curl_close($ch);
            return $data;
        }else {
            $error = curl_errno($ch);
            //echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }
    /**
     * 错误返回提示
     * @param string $errMsg 错误信息
     * @param string $status 错误码
     * @return  json的数据
     */
    protected function return_err($errMsg = 'error', $status = 0)
    {
        exit(json_encode(array('status' => $status, 'result' => 'fail', 'errmsg' => $errMsg)));
    }


    /**
     * 正确返回
     * @param    array $data 要返回的数组
     * @return  json的数据
     */
    protected function return_data($data = array())
    {
        exit(json_encode(array('status' => 1, 'result' => 'success', 'data' => $data)));
    }

    /**
     * 将一个数组转换为 XML 结构的字符串
     * @param array $arr 要转换的数组
     * @param int $level 节点层级, 1 为 Root.
     * @return string XML 结构的字符串
     */
    protected function array2xml($arr, $level = 1)
    {
        $s = $level == 1 ? "<xml>" : '';
        foreach ($arr as $tagname => $value) {
            if (is_numeric($tagname)) {
                $tagname = $value['TagName'];
                unset($value['TagName']);
            }
            if (!is_array($value)) {
                $s .= "<{$tagname}>" . (!is_numeric($value) ? '<![CDATA[' : '') . $value . (!is_numeric($value) ? ']]>' : '') . "</{$tagname}>";
            } else {
                $s .= "<{$tagname}>" . $this->array2xml($value, $level + 1) . "</{$tagname}>";
            }
        }
        $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
        return $level == 1 ? $s . "</xml>" : $s;
    }

    /**
     * 将xml转为array
     * @param  string $xml xml字符串
     * @return array    转换得到的数组
     */
    protected function xml2array($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    protected function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 生成签名
     * @return 签名
     */
    protected function makeSign($data)
    {
        //获取微信支付秘钥
        $key =config('wxapp.key');
        // 去空
        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        //签名步骤二：在string后加入KEY
        //$config=$this->config;
        $string_sign_temp = $string_a . "&key=" . $key;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
        return $result;
    }

    /**
     * 微信支付发起请求
     */
    protected function curl_post_ssl($url, $xmldata, $second = 30, $aHeader = array())
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmldata);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }


    public function getFormatTime($time_str,$status){
        if(!empty($time_str)){
            $time_arr = json_decode($time_str,true);
            $time_arr[$status.'_time'] = time();
        }else{
            $time_arr[$status.'_time'] = time();
        }
        $new_time_str = json_encode($time_arr);
        return $new_time_str;
    }

}
