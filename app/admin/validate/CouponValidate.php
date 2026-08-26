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

class CouponValidate extends Validate
{
    protected $rule = [
        //'hotel_name' => 'require|unique:ht_hotel,hotel_name',
        'name' => 'require',
        'amount' => 'require',
        'full_amount' => 'require',
        'count' => 'require',
        'start_time' => 'require',
        'end_time' => 'require',
    ];
    protected $message = [
        'name.require' => '名称不能为空',
        'amount.require' => '面额不能为空',
        'full_amount.require' => '满额不能为空',
        'count.require' => '数量不能为空',
        'start_time.require' => '开始时间不能为空',
        'end_time.require' => '结束时间不能为空',
    ];
    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];

}
