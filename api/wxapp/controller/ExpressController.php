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


class ExpressController extends RestBaseController{



    public function getOrderTracesByJson($abbr,$express_num,$CustomerName=''){
        $EBusinessID  = '1806837';
        $ApiKey = '4b5d0139-0d4b-4d36-90a2-5b4e163ff3fd';
        $ReqURL = 'https://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
        // 组装应用级参数

        $requestDataArr = ['CustomerName'=>$CustomerName,'OrderCode'=>'','ShipperCode'=>$abbr,'LogisticCode'=>$express_num];
        $requestData = json_encode($requestDataArr);
        /*$requestData= "{".
            "'CustomerName': '',".
            "'OrderCode': '',".
            "'ShipperCode': 'YTO',".
            "'LogisticCode': 'YT00003618100',".
            "}";*/
        // 组装系统级参数
        $datas = array(
            'EBusinessID' => $EBusinessID,
            'RequestType' => '1002', //免费即时查询接口指令1002/在途监控即时查询接口指令8001/地图版即时查询接口指令8003
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $ApiKey);
        //以form表单形式提交post请求，post请求体中包含了应用级参数和系统级参数
        $result=$this->sendPost($ReqURL, $datas);

        //根据公司业务处理返回的信息......

        return json_decode($result,true);
    }

    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    public function sendPost($url, $datas) {
        $postdata = http_build_query($datas);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param ApiKey ApiKey
     * @return DataSign签名
     */
    public function encrypt($data, $ApiKey) {
        return urlencode(base64_encode(md5($data.$ApiKey)));
    }





}
