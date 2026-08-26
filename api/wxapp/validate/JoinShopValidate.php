<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace api\wxapp\validate;

use think\Validate;

class JoinShopValidate extends Validate
{
    protected $rule = [
        'shop_name' => 'require',
        'mobile' => 'require',
        'province' => 'require',
        'city' => 'require',
        'county' => 'require',
        'lon' => 'require',
        'lat' => 'require',
        'address' => 'require',
        'business_license' => 'require',
        'content' => 'require',
        'cate_id' => 'require',
        'avatar' => 'require',
    ];
    protected $message = [

    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];
}