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

class GoodsValidate extends Validate
{
    protected $rule = [
       // 'category_id' => 'require',
        'goods_name' => 'require',
        //'price' => 'require',
        //'original_price' => 'require',
      //  'stock' => 'require',
        'content' => 'require',
        'photos' => 'require',
    ];

    protected $message = [
       // 'category_id.require' => '分类不能为空',
        'goods_name.require' => '名称不能为空',
      //  'price.require' => '现价不能为空',
        //'original_price.require' => '原价不能为空',
       // 'stock.require' => '数量不能为空',
        'content.require' => '详情不能为空',
        'photos.require' => '图片不能为空',
    ];

    protected $scene = [
        'add' => ['name'],
    ];
}
