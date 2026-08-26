<?php

namespace app\admin\model\health;

class DiseaseModel extends \think\Model
{
  protected $name  = 'health_disease';

    public function getThumbAttr( $name)
    {
        return $name ? cmf_get_asset_url($name) : '';
    }

}