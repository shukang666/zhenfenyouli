<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use think\facade\Log;

class  WxOrderController
{

    public $access_token;
    public $mch_id;

    public function __construct()
    {
        //自己获取access_token 和 商户号
        $this->access_token = CommonModel::getAccessToken();
        $this->mch_id = config('wxapp.mch_id');
    }


    public function upload_many($order_info)
    {
        static $i = 0;
        $WxOrderController = new WxOrderController();
        $MemberModel = new \api\wxapp\model\MemberModel();
        $openid = $MemberModel->where(['id' => $order_info['user_id']])->value('openid');
        $res = $WxOrderController->uploadShippingInfo($order_info['order_num'], $openid, '酒店预定', 4);
        if ($res['code'] == 0) {
            $i++;
            Log::write($i . '次调用成功');
            if ($i < 6) {
                sleep($i * 10);
                $this->upload_many($order_info);
            } else {
                Log::write($order_info['order_num'], '微信发货调用5失败');
            }
        }
        return $res;
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

        /* $WxOrderController = new WxOrderController();
         $res = $WxOrderController->uploadShippingInfo(123,'物品','123',1,'SF','1234','15236182393');*/

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

        $return = json_decode($this->https_request($url, json_encode($param, JSON_UNESCAPED_UNICODE)), true);
        Log::write($return, '推送物流信息返回');
        if (!empty($return) && $return['errcode'] == 0) {
            return ['code' => 1, 'msg' => '调用成功'];
        } else {
            return ['code' => 0, 'msg' => $return['errmsg']];
        }
    }

    function https_request($url, $data)
    {

        $curl = curl_init();
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

}
