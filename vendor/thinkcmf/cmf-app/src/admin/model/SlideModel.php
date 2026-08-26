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
namespace app\admin\model;

use think\Model;

class SlideModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'slide';

    /**
     * 一对一关联模型 关联分类下的幻灯片
     * @return \think\model\relation\HasMany
     */
    protected function items()
    {
        return $this->hasMany('SlideItemModel')->order('list_order ASC');
    }
}
