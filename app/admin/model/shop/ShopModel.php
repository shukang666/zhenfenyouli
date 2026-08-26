<?php

namespace app\admin\model\shop;

class ShopModel extends \app\admin\model\ToolModel
{
    protected $name = 'shop';

    public function get_status()
    {
        return [
            1 => '待审核',
            2 => '审核通过',
            3 => '审核不通过',
        ];
    }

    public function getAvatarAttr( $name)
    {
        return $name ? cmf_get_asset_url($name) : '';
    }

}