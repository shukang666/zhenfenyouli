<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace api\wxapp\model;

use think\Model;

class VipModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'vip';
    public function cate()
    {
        return $this->belongsTo(VipCateModel::class,'cate_id')->bind([
            'cate_name'=>'name',
            'content'=>'content',
            'thumb'=>'thumb',
        ]);
    }


    public function getThumbAttr($name)
    {
        return empty($name) ? '' : cmf_get_asset_url($name);
    }

    public function getIconAttr($name)
    {
        return empty($name) ? '' : cmf_get_asset_url($name);
    }

}
