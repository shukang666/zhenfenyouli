<?php

namespace api\wxapp\model;

class VipCateModel extends \think\Model
{
    protected $name = 'vip_cate';

    public function getIconAttr($name)
    {
        return empty($name) ? '' : cmf_get_asset_url($name);
    }

    public function getThumbAttr($name)
    {
        return empty($name) ? '' : cmf_get_asset_url($name);
    }

    public function getContentAttr($name)
    {
        return empty($name) ? '' : htmlspecialchars_decode($name);
    }


}