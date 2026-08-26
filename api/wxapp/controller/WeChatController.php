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
use api\wxapp\model\CommonModel;
use api\wxapp\model\WithdrawModel;
use app\admin\model\forum\ForumCateModel;
use app\admin\model\forum\ForumModel;
use app\admin\model\member\MemberModel;
use cmf\lib\Storage;
use HttpResponseException;
use think\facade\Log;
use think\Model;
use think\Response;

class WeChatController extends AuthController
{
    //证书序列号：4B8AA4C75D08A9D88A1E0BE137F3E2EF8CF90A40

    public $appid = '';//小程序appid
    public $mch_id; //商户号
    public $key;//商户秘钥
    public $access_token;
    public $serial_no = '423474C1642C4269DC387CE1810A52208CD37CB7 '; // 微信提现证书序列号

    public function __construct()
    {
        parent::__construct();
        //自己获取access_token 和 商户号
        $this->access_token = CommonModel::getAccessToken();
        $this->mch_id = config('wxapp.mch_id');
        $this->appid = config('wxapp.appid');
        $this->key = config('wxapp.key');
    }

    //小程序支付
    /* 首先在服务器端调用微信【统一下单】接口，返回prepay_id和sign签名等信息给前端，前端调用微信支付接口 */
    public function pay($total_fee, $openid, $order_id): array
    {
        $appid = $this->appid;;//小程序的appid
        $body = '订单支付';
        $mch_id = $this->mch_id;
        $key = $this->key;
        $nonce_str = mt_rand(10000, 99999) . time();//随机字符串
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
        $sign = $this->makeSign($post, $key); //签名
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
        $xml = curl_post($url, $post_xml); //POST方式请求http
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
            $data['paySign'] = $this->makeSign($tmp, $key); //签名,具体签名方案参见微信公众号支付帮助文档;
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
            $data['code'] = 0;
            $data['msg'] = $msg;
        }
        return $data;
    }

    /** * 生成签名 */
    public function makeSign($params, $KEY): string
    {
        ksort($params);

        $string = '';
        if (!empty($params)) {
            $array = array();
            foreach ($params as $key => $value) {
                $array[] = $key . '=' . $value;
            }
            $string = implode("&", $array);
        }
        $string = $string . "&key=" . $KEY;
        $string = md5($string);
        return strtoupper($string);
    }

    //获取xml里面数据，转换成array
    public function xml2array($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    //推送
    //admin.qiangqiangya.com/api/wxapp/weChat/linkPush

//    public function test()
//    {
//        $this->success('推送成功');
//    }
    public function linkPush()
    {
//        $this->success('推送成功');
        //获得参数 signature nonce token timestamp echostr
        $nonce = $this->request->param('nonce');
        $token = 'weijubao';
        $timestamp = $this->request->param('timestamp');
        $echostr = $this->request->param('echostr');
        $signature = $this->request->param('signature');
        //形成数组，然后按字典序排序
        $array = [$nonce, $timestamp, $token];
        sort($array);
        //拼接成字符串,sha1加密 ，然后与signature进行校验
        $str = sha1(implode($array));
        if ($str == $signature && $echostr) {
            //第一次接入weixin api接口的时候
            ob_clean();
            echo $echostr;
            exit;
        } else {
            $this->responseMsg();
        }
    }

    //回应消息
    public function responseMsg()
    {

        //1.获取到微信推送过来post数据（xml格式）
        $xml = file_get_contents('php://input');
        $data = $this->xml2array($xml);

        Log::write($data, '公众号推送数据');
        cmf_table('cmf_basic_wx_msg')
            ->insert(['create_time'=>time(),
                'content'=>json_encode($data,256)]);
        //2.处理消息类型，并设置回复类型和内容
        //判断该数据包是否是订阅的事件推送

        if (strtolower($data['MsgType']) == 'event') {


            if (strtolower($data['Event'] == 'subscribe')) {
                $MemberModel = new MemberModel();
                $openid = $data['FromUserName'];
                $res = CommonModel::getUserInfo($openid);
                $MemberModel->where('unionid', $res['unionid'])->update(['wx_openid' => $openid]);
                //回复用户消息(纯文本格式)
                $toUser = $data['FromUserName'];
                $fromUser = $data['ToUserName'];
                $time = time();
                $msgType = 'text';
                $content = '亲，很高兴遇见你~';
                $template = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							</xml>";
                $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                echo $info;
            } elseif (strtolower($data['Event'] == 'debug_demo')) {//bug

            } elseif (strtolower($data['Event'] == 'unsubscribe')) {
                //用户取消事件
                $openid = $data['FromUserName'];
            } elseif ($data['Event'] == 'SCAN') {
                //用户已关注扫码事件
                Log::write('到了这');
                $openid = $data['FromUserName'];
                //查帖子

                $forumModel = new ForumModel();
                $forumCateModel = new ForumCateModel();
                $forum = $forumModel->where('id', $data['EventKey'])->find();
                $forumCate = $forumCateModel->where('id', $forum['cate_id_2'])->find();
                $forumCate1 = $forumCateModel->where('id', $forum['cate_id_1'])->find();
                if (empty($forumCate['name'])) {
                    $forumCate['name'] = $forumCate1['name'];
                }
                $time = time();
                $msgType = 'news';
                $ArticleCount = 1;
                $toUser = $data['FromUserName'];
                $fromUser = $data['ToUserName'];
                $Title = "【{$forumCate['name']}】" . $forum['content'];

                $Description = '来自掘金模具机加工订单采购公众号【' . $forumCate['name'] . '】的信息';
                if (!empty($forum['image'][0])) {
                    $PicUrl = $forum['image'][0];
                } else {
                    $set_config = cmf_get_option('set_config');
                    $PicUrl = $set_config['app_logo'];
                }
                $memberModel = new MemberModel();
                $vcode = $memberModel->where('id', $forum['user_id'])->value('vcode');
                $Url = 'https://dzkf249.aejust.com/h5/#/pages/index/detail?id=' . $data['EventKey'] . '&scene=' . $vcode . "&title=崛金科技{$forumCate1['name']}信息汇总";
                $template = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<ArticleCount><![CDATA[%s]]></ArticleCount>  
							<Articles>
                            <item>
                              <Title><![CDATA[%s]]></Title>
                              <Description><![CDATA[%s]]></Description>
                              <PicUrl><![CDATA[%s]]></PicUrl>
                              <Url><![CDATA[%s]]></Url>
                               </item>
                             </Articles>
							</xml>";
                $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $ArticleCount, $Title, $Description, $PicUrl, $Url);
                echo $info;


            }
            //如果是关注 subscribe 事件


        } elseif (strtolower($data['MsgType'] == 'text')) {
            //用户发送文本
            //回复用户消息(纯文本格式)
            exit;
            $toUser = $data['FromUserName'];
            $fromUser = $data['ToUserName'];
            $Content = $data['Content'];


//            $WxReplyModel = new WxReplyModel();

            $map = [];
            $map[] = ['keyword', '=', $Content];
            $map[] = ['status', '=', 1];
            $data = $WxReplyModel->where($map)->find();

            if (!empty($data)) {
                $reply_content = json_decode($data['content'], true);
                if ($data['reply_type'] == 'news') {
                    $time = time();
                    $msgType = 'news';
                    $ArticleCount = 1;
                    $Title = $reply_content['title'];
                    $Description = $reply_content['desc'];
                    $PicUrl = cmf_get_asset_url($reply_content['thumb']);
                    $Url = $reply_content['url'];
                    $template = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<ArticleCount><![CDATA[%s]]></ArticleCount>  
							<Articles>
                            <item>
                              <Title><![CDATA[%s]]></Title>
                              <Description><![CDATA[%s]]></Description>
                              <PicUrl><![CDATA[%s]]></PicUrl>
                              <Url><![CDATA[%s]]></Url>
                               </item>
                             </Articles>
							</xml>";
                    $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $ArticleCount, $Title, $Description, $PicUrl, $Url);
                    echo $info;
                } elseif ($data['reply_type'] == 'text') {
                    $time = time();
                    $msgType = 'text';
                    $content = $reply_content['content'];
                    $template = "<xml>
                          <ToUserName><![CDATA[%s]]></ToUserName>
                          <FromUserName><![CDATA[%s]]></FromUserName>
                          <CreateTime>%s</CreateTime>
                          <MsgType><![CDATA[%s]]></MsgType>
                          <Content><![CDATA[%s]]></Content>
                        </xml>";
                    $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                    echo $info;
                }
            } else {
                echo 'success';
                exit;
            }
        }
    }


    //增加公众号菜单
    public function addMenu()
    {
        $data = [
            'button' => [
                [
                    'name' => '菜单1',
                    'sub_button' => [
                        [
                            'type' => 'view',
                            'name' => '子菜单1',
                            'url' => 'http://www.domain.com',
                        ],
                        [
                            'type' => 'click',
                            'name' => '子菜单2',
                            'key' => 'V1001_GOOD',
                        ],
                    ],
                ],
                [
                    'type' => 'view',
                    'name' => '菜单2',
                    'url' => 'http://www.baidu.com',
                ],
                ['type' => 'click',
                    'name' => '菜单3',
                    'key' => 'V1001_GOOD',]
            ],
        ];
//https://dzkf249.aejust.com/h5/#/pages/index/detail?id=12
        //api/wxapp/weChat/addMenu
        //https://admin.qiangqiangya.com/api/wxapp/weChat/addMenu


        $data = [
            'button' => [
                [
                    'name' => '工作1',
                    'type' => 'view',//miniprogram
                    'url' => 'https://dzkf249.aejust.com/h5/#/pages/index/cateList'
                ],
            ],
        ];

        $menu = [
            'button' => [
                [
                    'name' => '跟墙说',
                    'type' => 'miniprogram',
                    'appid' => config('wxapp.appid'),
                    'pagepath' => 'pages/index'
                ],
            ],
        ];

        $access_token = CommonModel::getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$access_token";
        $data = urldecode(json_encode($menu, JSON_UNESCAPED_UNICODE));
        $result = json_decode(curl_post($url, $data), true);

        var_dump($result);

    }


    //转换小程序码链接
    public function toUrl()
    {

        $url = 'https://api.weixin.qq.com/wxa/generatescheme?access_token=' . CommonModel::getAccessToken();
        $result = curl_post($url, '');
        $result = json_decode($result, true);
        var_dump($result['openlink']);
    }

    //上传图片
    public function uploadImg($file_url, $save_to)
    {
        $content = file_get_contents($file_url);
        file_put_contents($save_to, $content);
    }

    //上传资源文件
    public function upload_media()
    {
        $path = './upload/shop/wifi2.jpg';
        $upload_url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=" . CommonModel::getWxAccessToken() . "&type=image";

        $json = curl_post($upload_url, [], $path);

        // json转为数组
        $arr = json_decode($json, true);
        var_dump($arr);

    }


    /**
     * 生成随机32位字符串
     * @param $length
     * @return string
     */
    public function createNonceStr($length = 16)
    { //生成随机16个字符的字符串
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取私钥
     * @param $filepath
     * @return false|resource
     */
    private function getPrivateKey($filepath = '')
    {
        if (empty($filepath)) {
            //私钥位置
            // $cert_dir = ROOT_PATH.DS."config".DS."payment_cert".DS."wechatpay".DS;
            $filepath = './wx_pem/apiclient_key.pem';
        }

        return openssl_get_privatekey(file_get_contents($filepath));
    }


    /**
     *
     * @param $order_sn : 订单编号，我们自己生成的订单编号
     * @param int $logistics_type : 物流模式 1、实体物流配送采用快递公司进行实体物流配送形式 2、同城配送 3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式 4、用户自提
     * @param string $delivery_mode : 发货模式，发货模式枚举值：1、UNIFIED_DELIVERY（统一发货）2、SPLIT_DELIVERY（分拆发货） 示例值: UNIFIED_DELIVERY  暂不使用
     * @param $item_desc :物品信息 必填
     * @param string $express_company :物流公司编码  配合腾讯的物流公司编码数据表
     * @param string $tracking_no :物流单号
     * @param string $receiver_contact :顺丰是需要收货人手机号  15236182399
     */

    public function uploadShippingInfo($order_sn, $openid, $item_desc, int $logistics_type = 1, string $express_company = '', string $tracking_no = '', string $receiver_contact = ''): array
    {

        $url = 'https://api.weixin.qq.com/wxa/sec/order/upload_shipping_info?access_token=' . $this->access_token;

        $shipping_list = [
            'tracking_no' => $tracking_no,//物流单号，物流快递发货时必填
            'express_company' => $express_company,//物流公司编码，快递公司ID，参见「查询物流公司编码列表」，物流快递发货时必填
            'item_desc' => $item_desc,//商品信息，例如：微信红包抱枕*1个  必填
        ];
        if ($express_company == 'SF') {
            if (empty($receiver_contact)) return ['code' => 0, 'msg' => '顺丰快递需填写收件人手机号'];
            $shipping_list['contact']['receiver_contact'] = substr_replace($receiver_contact, '****', -8, 4);
            //联系方式，当发货的物流公司为顺丰时，联系方式为必填，收件人或寄件人联系方式二选一
            // 'receiver_contact'  //收件人联系方式，收件人联系方式为，采用掩码传输，最后4位数字不能打掩码 示例值: `189****1234, 021-****1234, ****1234, 0**2-***1234, 0**2-******23-10, ****123-8008` 值限制: 0 ≤ value ≤ 1024 字段加密: 使用APIv3定义的方式加密
        }

        $param = [
            'order_key' => [
                'order_number_type' => 1,
                'mchid' => $this->mch_id,//商户号
                'out_trade_no' => $order_sn,
            ],
            'logistics_type' => $logistics_type,
            'delivery_mode' => 1,
            'shipping_list' => [$shipping_list],
            'upload_time' => date("c", time()),
            'payer' => [
                'openid' => $openid
            ]
        ];

        $return = json_decode(curl_post($url, json_encode($param, JSON_UNESCAPED_UNICODE)), true);

        if (!empty($return) && $return['errcode'] == 0) {
            return ['code' => 1, 'msg' => '调用成功'];
        } else {
            return ['code' => 0, 'msg' => $return['errmsg']];
        }
    }


    public function getDelivery(): array
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/delivery/getall?access_token=' . $this->access_token;

        $res = json_decode(curl_get($url), true);

        if (!empty($res['errcode'])) {
            return ['code' => 0, 'msg' => $res['errmsg']];
        } else {
            return ['code' => 1, 'msg' => '调用成功', 'data' => $res['data']];
        }
    }


    //调用示例
    public function example($openid, $order_num, $amount, $user_name, $transfer_scene_report_infos)
    {
        if (empty($wx_config)) $wx_config = $this->wx_config;
        $payment = [
            'app_id' => $wx_config['appid'],
            'payment' => [
                'wx_certificates' => $wx_config['serial_no'],//证书序列号
                'cert_path' => $wx_config['ssl_cer'],
                'key_path' => $wx_config['ssl_key'],
                'merchant_id' => $wx_config['mch_id']
            ],
        ];
//        $money = round($amount * 100,0);
//        $money = 1;
        $money = $amount;
        $res = $this->crteateMchPay($payment, $openid, $money, $user_name, $transfer_scene_report_infos, $order_num);
        return $res;
        /*if(isset($res['result_code']) && $res['result_code'] == 'SUCCESS'){

            $out_bill_no = $res['out_bill_no']; //商户内部单号
            $package_info  = $res['package_info'];//前端确认收款需要使用
            $transfer_bill_no = $res['transfer_bill_no'];//微信单号


            $this->success('转账成功，请等待用户确认收款');
        }else{
            $this->error($res['err_code_des'] ?? '转账失败');
        }*/

    }

    public function withdraw($order_num, $openid, $money, $user_name)
    {
        $weChat = new WeChatController();


        $mch_id = config('wxapp.mch_id');
        $appid = config('wxapp.appid');
        $key = config('wxapp.key');
        $serial_no = $this->serial_no;
        $payment = [
            'app_id' => $appid,
            'payment' => [
                'wx_certificates' => $serial_no,//证书序列号
                'cert_path' => 'wx_pem/apiclient_cert.pem',
                'key_path' => 'wx_pem/apiclient_key.pem',
                'merchant_id' => $mch_id
            ],
        ];
        $transfer_scene_report_infos = [
            [
                "info_type" => "活动名称",
                "info_content" => "新会员有礼"],
            [
                "info_type" => "奖励说明",
                "info_content" => "注册会员抽奖一等奖"]
        ];
        $res = $weChat->crteateMchPay($payment, $openid, $money, $user_name, $transfer_scene_report_infos, $order_num);
        return $res;
    }

    /**
     * @param $payment
     * @param $money
     * @param $openid
     * @功能说明:新版商家付款到零钱
     */
    public function crteateMchPay($openid,$money,$user_name='',$order_code=''){

        //接口文档
        //https://pay.weixin.qq.com/doc/v3/merchant/4012716434
        $url = 'https://api.mch.weixin.qq.com/v3/fund-app/mch-transfer/transfer-bills';//新版地址

        $pars = [];
        if(empty($order_code)) $order_code = 'WD'.time().mt_rand(10000, 99999);

        $transfer_scene_report_infos = [
            [
                "info_type" => "岗位类型",
                "info_content" => "分享"],
            [
                "info_type" => "报酬说明",
                "info_content" => "工资"]
        ];
        $payment = [
            'app_id' => config('wxapp.appid'),
            'payment' => [
                'wx_certificates' =>config('wxapp.serial_no'),//证书序列号
                'cert_path' => 'wx_pem/apiclient_cert.pem',
                'key_path' => 'wx_pem/apiclient_key.pem',
                'merchant_id' => config('wxapp.mch_id')
            ],
        ];
        $pars['appid'] = $payment['app_id'];//直连商户的appid

        $pars['out_bill_no'] = $order_code;//【商户单号】 商户系统内部的商家单号，要求此参数只能由数字、大小写字母组成，在商户系统内部唯一

        $pars['transfer_scene_id'] = '1005';//【转账场景ID】 该笔转账使用的转账场景，可前往“商户平台-产品中心-商家转账”中申请。如：1001-现金营销

        $pars['openid'] = $openid;//【收款用户OpenID】 商户AppID下，某用户的OpenID

        $pars['transfer_amount'] = round($money * 100);//【转账金额】 转账金额单位为“分”。

        $pars['notify_url'] = cmf_get_domain() . '/api/wxapp/WeChat/Notify';

        $Wechatpay = 0;
        //大于等于2000必须要姓名
        if($pars['transfer_amount']>=200000 &&!empty($user_name)){

            $pars['user_name'] = $this->getEncrypt($user_name,$payment);//【收款用户姓名】 收款方真实姓名。需要加密传入，支持标准RSA算法和国密算法，公钥由微信侧提供。转账金额 >= 2,000元时，该笔明细必须填写若商户传入收款用户姓名，微信支付会校验收款用户与输入姓名是否一致，并提供电子回单

            $Wechatpay = 1;
        }

        $pars['transfer_remark'] = '商家转账';//【转账备注】 转账备注，用户收款时可见该备注信息，UTF8编码，最多允许32个字符

//        var_dump($pars);exit;

        if(empty($transfer_scene_report_infos)){
            $transfer_scene_report_infos =  [
                [

                    'info_type'  => '回收商品名称',

                    'info_content'=> '耳机',

                ],
            ];//转账明细列表
        }
        $pars['transfer_scene_report_infos'] = $transfer_scene_report_infos;

        $token  = $this->getToken($pars,$payment);

        $res    = $this->http_post($url,json_encode($pars),$token,$payment,$Wechatpay);//发送请求

        $resArr = json_decode($res,true);

//        if(!empty($resArr['code'])){
//
//            $resArr['result_code'] = $resArr['return_code'] = 'FAIL';
//            $resArr['err_code_des'] = $resArr['message'];
//
//        }else{
//            $resArr['result_code'] = $resArr['return_code'] = 'SUCCESS';
//            //state
//            /*ACCEPTED: 转账已受理
//            PROCESSING: 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够
//            WAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认
//            TRANSFERING: 转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款
//            SUCCESS: 转账成功
//            FAIL: 转账失败
//            CANCELING: 商户撤销请求受理成功，该笔转账正在撤销中
//            CANCELLED: 转账撤销完成*/
//
//        }
        Log::write($pars,'转账发送信息');
        Log::write($resArr,'转账返回信息');

        return $resArr;
        //成功返回
    }

    /**
     * @param $str
     * @param $payment
     * @功能说明:根据平台证书加密
     */
    public function getEncrypt($str, $payment)
    {
        //$str是待加密字符串
        $public_key_path = $payment['payment']['wx_certificates'];

        if (empty($public_key_path)) {

            return '';
        }

        $public_key = file_get_contents($public_key_path);

        $encrypted = '';

        if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64编码
            $sign = base64_encode($encrypted);
        } else {
            throw new Exception('encrypt failed');
        }
        return $sign;
    }

    /**
     * @param $pars
     * @param $payment
     * @功能说明:获取token
     */
    public function getToken($pars, $payment, $http_method = 'POST', $usrl_data = '')
    {

        $url = !empty($usrl_data) ? $usrl_data : 'https://api.mch.weixin.qq.com/v3/fund-app/mch-transfer/transfer-bills';
        // $http_method = 'POST';//请求方法（GET,POST,PUT）
        $timestamp = time();//请求时间戳
        $url_parts = parse_url($url);//获取请求的绝对URL
        $nonce = $timestamp . rand(10000, 99999);//请求随机串
        $body = !empty($pars) ? json_encode((object)$pars) : '';//请求报文主体
        $stream_opts = [
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ];
        $apiclient_cert_path = $payment['payment']['cert_path'];
        $apiclient_key_path = $payment['payment']['key_path'];
        $apiclient_cert_arr = openssl_x509_parse(file_get_contents($apiclient_cert_path, false, stream_context_create($stream_opts)));
        $serial_no = $apiclient_cert_arr['serialNumberHex'];//证书序列号
        $mch_private_key = file_get_contents($apiclient_key_path, false, stream_context_create($stream_opts));//密钥
        $merchant_id = $payment['payment']['merchant_id'];//商户id
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message = $http_method . "\n" .
            $canonical_url . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');

        $sign = base64_encode($raw_sign);//签名
        $schema = 'WECHATPAY2-SHA256-RSA2048';
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $merchant_id, $nonce, $timestamp, $serial_no, $sign);//微信返回token
        return $token;

    }

    /**
     * POST 请求
     */
    private function http_post($url, $data, $token, $payment, $Wechatpay = 0)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, (string)$url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //添加请求头
        $headers = [

            'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $token,
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
        ];
        //需要加密
        if ($Wechatpay == 1) {

            $certificates = $this->get_Certificates($payment);

            $headers[] = 'Wechatpay-Serial:' . $certificates;
        }
        if (!empty($headers)) {

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        $output = curl_exec($curl);
        curl_close($curl);

        return $output;
    }

    /**
     * @param $payment
     * @功能说明:获取证书序列号
     */
    public function get_Certificates($payment)
    {

        $platformCertificateFilePath = $payment['payment']['wx_certificates'];

        if (empty($platformCertificateFilePath)) {

            return '';
        }

        $a = openssl_x509_parse(file_get_contents($platformCertificateFilePath));

        return !empty($a['serialNumberHex']) ? $a['serialNumberHex'] : '';
    }

    public function Notify()
    {
        $data = $this->request->param();
        Log::write($data, '回调通知');
        $AesUtil = new AesUtilController($this->key);
        $resource = $data['resource'];
        $result = $AesUtil->decryptToString($resource['associated_data'], $resource['nonce'], $resource['ciphertext']);
        $result = json_decode($result, true);
        Log::write($result, '回调解密后内容');
        $info = WithdrawModel::where('order_num', $result['out_bill_no'])->find();
        if ($info['status'] > 2) return false;
        if ($result['state'] == 'SUCCESS') {
            $status = 3;
        } else {
            $status = 4;
            $id = $info['user_id'];
            AccountLogModel::add_account_log($info['user_id'], 3, 311, $info['num'], ['withdraw_id' => $id, 'reason' => '未确认退回', 'update_time' => time()]);
        }
        WithdrawModel::where('order_num', $result['out_bill_no'])
            ->update(['status' => $status, 'transfer_bill_no' => $result['transfer_bill_no']]);
        //修改状态

        //回复内容
        $header = [];

        $result = [
            'code' => 200,
            'message' => $result['state'],
        ];
        exit(json_encode($result, 256));

    }
}
