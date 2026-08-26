<?php

namespace app\admin\controller;

use app\admin\model\member\MemberModel;
use app\admin\model\order\OrderInvoiceModel;
use think\App;

class OrderInvoiceController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new  OrderInvoiceModel();
    }


    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');
        $status = $this->request->param('status', '', 'trim');
        $type = $this->request->param('type', '', 'trim');
        $start_time = $this->request->param('start_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');

        $where = [];
        if ($name) $where[] = ['order_num', 'like', '%' . $name . '%'];
        if ($status) $where[] = ['status', '=', $status];
        if ($type) $where[] = ['type', '=', $type];
        if ($start_time) $where[] = ['create_time', '>=', strtotime($start_time)];
        if ($end_time) $where[] = ['create_time', '<=', strtotime($end_time) + 86399];

        return $where;
    }

    public function outParam()
    {
        $this->assign('type_list', $this->model->getType());
    }

    public function setIndex()
    {
        $this->outParam();
        $this->assign('status_list', $this->model->getStatus());
        return $this->model->where($this->setWhere())->order('id desc')->paginate(15)
            ->each(function ($item) {
                $item->type_name = $this->model->getType()[$item->type];
                $item->status_name = $this->model->getStatus()[$item->status];
                $item->invoiceKind_name = $this->model->invoiceKind()[$item->invoiceKind] ?? '';

                $user_info = MemberModel::where('id', $item['user_id'])->find();
                if ($user_info) {
                    $item['user_info'] = $user_info['nickname'] . "({$user_info['mobile']})";
                } else {
                    $item['user_info'] = '用户已删除';
                }
                if (!empty($item['invoice'])) $item['status_name'] = '已开票';

            });

    }

}