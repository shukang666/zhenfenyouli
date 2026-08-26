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

use app\admin\model\ToolModel;
use think\Model;

class UserCouponModel extends ToolModel
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'coupon_user';

    public static function addData($coupon_id,$user_id,$count=1,$select_type=1,$is_send=0): bool
    {

        for ($i=1;$i<=$count;$i++){
            $CouponModel = new CouponModel();
            $info = $CouponModel->where(['id'=>$coupon_id])->find();
            $data = [];
            $data['user_id'] = $user_id;
            $data['coupon_id'] = $coupon_id;
            $data['status'] = 1;
            $data['amount'] = $info['amount'];
            $data['full_amount'] = $info['full_amount'];
            $data['name'] = $info['name'];
            $data['create_time'] = time();
            $data['type'] = $info['type'];
            $data['discount_type'] = $info['discount_type'];
            $data['discount'] = $info['discount'];
            $data['cate_id'] = $info['cate_id'];
            $data['goods_id'] = $info['goods_id'];
            $data['select_type'] = $select_type;
            $data['coupon_type'] = $info['coupon_type'];
            $data['shop_id'] = $info['user_id'];

            if($info['indate_cate'] == 1){
                $data['start_time'] = time();
                $data['end_time'] = $info['indate_day']*86400+$data['start_time'];
            }else{
                $data['start_time'] = $info['start_time'];
                $data['end_time'] =  $info['end_time'];
            }
            $UserCouponModel = new UserCouponModel();
            $UserCouponModel->insert($data);
            $CouponModel->where(['id'=>$coupon_id])->inc('select_count')->update();
            if($is_send == 0){
                $CouponModel->where(['id'=>$coupon_id])->dec('count')->update();
            }
        }
        return true;
    }


}
