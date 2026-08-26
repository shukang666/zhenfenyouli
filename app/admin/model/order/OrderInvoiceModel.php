<?php

namespace app\admin\model\order;

class OrderInvoiceModel extends \app\admin\model\ToolModel
{
    protected $name = 'shop_order_invoice';

    public function getType()
    {
        return [1 => '企业单位', 2 => '个人'];
    }

    public function getStatus()
    {
        return [1 => '待开票', 2 => '已开票', 3 => '已红冲', 4 => '终止'];//
    }

    public function invoiceKind()
    {
        return [81 => '增值税专用发票', 82 => '普通发票'];//
    }

}