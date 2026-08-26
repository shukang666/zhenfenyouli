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

use api\wxapp\model\CommonModel;
use think\Model;

class WithdrawModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'user_withdraw';


    public static function getTypeName($type=-1){
        $w_type_name = [1 => '支付宝', 2 => '微信', 3 => '银行卡'];
        if($type == -1){
            return $w_type_name;
        }else{
            return  $w_type_name[$type] ?? '';
        }
    }

    public static function getStatusName($status  = -1){
        $withdraw_status_name = [0=>'待审核',1=>'已同意',2=>'已拒绝',3=>'提现成功',4=>'提现失败退回'];
        if($status == -1){
            return $withdraw_status_name;
        }else{
            return  $withdraw_status_name[$status] ?? '';
        }
    }

    public function getInfo($map,$id=0,$field='a.*',$page_size=10,$list_order='a.id desc')
    {
        $list = $this->alias('a')
            ->field($field.',b.nickname,b.mobile,b.true_name')
            ->leftjoin('member b','a.user_id = b.id')
            ->where($map)->order($list_order)->paginate($page_size);

        foreach ($list as &$item){
            $item = CommonModel::changeTrueNameFromNickname($item);
            $item['param'] = json_decode($item['param'],true);
            $item['status_name'] = self::getStatusName($item['status']);
            $item['type_name'] = self::getTypeName($item['type']);
        }
        return $id ? $list[0] ?? [] : $list;
    }
}
