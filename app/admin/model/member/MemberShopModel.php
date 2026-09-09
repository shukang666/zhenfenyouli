<?php

namespace app\admin\model\member;


use app\admin\model\ToolModel;

class MemberShopModel extends ToolModel
{
    protected $name = 'member_shop';

    public function getStatusNameAttr($value,$data)
    {
        $arr = [
            1 => '待审核',
            2 => '审核通过',
            3 => '审核不通过',
        ];
        return $arr[$data['status']] ?? '';
    }

    public function getTypeNameAttr($value,$data)
    {
        //类型，1：机构，2：经营场所
        $arr = [
            1 => '机构',
            2 => '经营场所',
        ];
        return $arr[$data['type']] ?? '';
    }

    //所在社区
    public function getCommunityNameAttr($value,$data){
        $item = CommunityParentModel::where('id',$data['community'])->find();
        return $item['name'] ?? $data['community'];
    }

}