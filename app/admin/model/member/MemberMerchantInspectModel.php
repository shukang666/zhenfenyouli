<?php

namespace app\admin\model\member;

use app\admin\model\ToolModel;

class MemberMerchantInspectModel extends ToolModel
{
    protected $name = 'member_merchant_inspect';

    // 禁用软删除（表中无 delete_time 字段）
    protected $deleteTime = false;
}
