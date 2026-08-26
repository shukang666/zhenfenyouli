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
namespace app\admin\validate;

use think\Validate;

class SkuValidate extends Validate
{
    protected $rule = [
        'sku_name' => 'require',
        'price' => 'require',
        'stock' => 'require',
        'goods_id' => 'require',
       // 'thumb' => 'require',

    ];

    protected $message = [
        'sku_name.require' => '规格不能为空',
        'price.require' => '价格不能为空',
        'stock.require' => '数量不能为空',
        'goods_id.require' => '商品id不能为空',
       // 'thumb.require' => '缩略图不能为空',
    ];

    protected $scene = [
        'add' => ['name'],
    ];
}
