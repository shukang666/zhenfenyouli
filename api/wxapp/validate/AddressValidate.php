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
namespace api\wxapp\validate;

use think\Validate;

class AddressValidate extends Validate
{
    protected $rule = [
        'type' => 'require',
        'province' => 'require',
        'city' => 'require',
        'county' => 'require',
        'address' => 'require',
        'username' => 'require',
        'phone' => 'require',
    ];
    protected $message = [
        'type.require' => '类型不能为空',
        'province.require' => '省份不能为空',
        'city.require' => '市区不能为空',
        'county.require' => '县城不能为空',
        'address.require' => '地址不能为空',
        'username.require' => '姓名不能为空',
        'phone.require' => '电话不能为空',
    ];
    protected $scene = [

    ];
}
