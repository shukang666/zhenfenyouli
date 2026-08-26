<?php

namespace app\admin\model\order;

use api\wxapp\model\CommonModel;

class OrderQrcodeModel extends \app\admin\model\ToolModel
{

    protected $name = 'shop_qrcode';


    public function getQrcodeAttr( $name)
    {
        return $name ? cmf_get_asset_url($name) : '';
    }

    public static function set_qrcode($order_id, $count,$user_id=0,$shop_id=0)
    {
        $OrderQrcodeModel = new OrderQrcodeModel();
        for ($i = 0; $i < $count; $i++) {
            $text = md5($order_id . time(). $i);
            $qrcode = CommonModel::createQrcode($text, $text.'.png');
            $OrderQrcodeModel->insert([
                'order_id' => $order_id,
                'qrcode' => $qrcode,
                'user_id' => $user_id,
                'shop_id' => $shop_id,
                'qrcode_text' => $text,
                'create_time' => time(),
            ]);
        }
        return true;
    }
}