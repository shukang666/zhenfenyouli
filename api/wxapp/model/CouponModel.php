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

class CouponModel extends Model
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'coupon';

    public function getInfo($map,$page_size=10,$is_page=1){


        $list =  $this->alias('a')
            ->field('a.*,c.shop_name')
//            ->leftjoin('user b','a.add_admin_id = b.id')
            ->leftjoin('shop c','a.user_id = c.user_id')
//            ->leftjoin('shop_goods d','a.goods_id = d.id')
//            ->leftjoin('shop_category e','a.cate_id = e.id')
            ->where($map)->order('a.id desc');

        if($is_page == 1){
            $data = $list->paginate($page_size);
        }else{
            $data = $list->select();
        }

        return $this->changeData($data);

    }

    public function changeData($list){

        $type_name = $this->getType();
        $indate_cate_name = $this->getIndateCate();
        $discount_type_name = $this->getDiscountType();
        $used_name = $this->getUsedName();

        foreach($list as &$item){
            $item['type_name'] = $type_name[$item['type']] ?? '';
            if(!empty($item['discount_type'])){
                $item['discount_type_name'] = $discount_type_name[$item['discount_type']] ?? '';
            }
            if(!empty($item['indate_cate'])){
                $item['indate_cate_name'] = $indate_cate_name[$item['indate_cate']] ?? '';
            }
            $used  = $item['used'] ?? '';
            $item['used_name'] = $used_name[$used] ?? '';
        }
        return $list;
    }

    public function getType($type=-1){
        $type_name = [1=>'通用优惠券',2=>'品类优惠券',3=>'单品优惠券'];
        if($type == -1){
            return $type_name;
        }else{
            return $type_name[$type] ?? '';
        }
    }

    public function getIndateCate($indate_cate=-1){
        $indate_cate_name = [1=>'几天内有效',2=>'时间段内有效'];
        if($indate_cate == -1){
            return $indate_cate_name;
        }else{
            return $indate_cate_name[$indate_cate] ?? '';
        }
    }

    public function getUsedName($used = -1){
        $used_name = ['待使用','已使用','已过期'];
        if($used == -1){
            return $used_name;
        }else{
            return $used_name[$used] ?? '';
        }
    }

    public function getDiscountType($discount_type=-1){
        $discount_type_name = [1=>'老人卷',2=>'儿童卷',3=>'孕妇卷'];//1满减券 2现金券 3折扣券
        if($discount_type == -1){
            return $discount_type_name;
        }else{
            return $discount_type_name[$discount_type] ?? '';
        }
    }

}
