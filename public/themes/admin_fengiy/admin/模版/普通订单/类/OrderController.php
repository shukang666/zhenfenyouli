<?php

namespace themes\admin_fengiy\admin\模版\普通订单\类;

use api\wxapp\controller\RefundController;
use api\wxapp\model\CommonModel;
use api\wxapp\model\MemberModel;
use app\admin\controller\CommissionModel;
use app\admin\controller\ToolController;
use app\admin\model\CarCateModel;
use app\admin\model\CommentModel;
use app\admin\model\OrderModel;
use app\admin\model\WorkModel;
use think\App;

class OrderController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new OrderModel();
    }

    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');
        $status = $this->request->param('status', '', 'trim');
        $start_time = $this->request->param('start_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');

        $where = [];
        if ($name) {
            $where[] = ['a.order_num|a.name|a.mobile', 'like', '%' . $name . '%'];
        }
        if (!empty($status)) {
            $where[] = ['a.status', '=', $status];
        }
        if ($start_time) $where[] = ['a.create_time', '>=', strtotime($start_time)];
        if ($end_time) $where[] = ['a.create_time', '<=', strtotime($end_time) + 86399];

        $this->assign('status', $status);
        return $where;
    }

    public function setIndex()
    {

        $this->assign('status_menu', $this->model->getStatusMenu());
//        $this->assign('status_list', OrderModel::getStatusList());
        return $this->model->alias('a')
            ->join('member m', 'a.user_id=m.id')
            ->field('a.*,m.nickname,m.mobile,m.avatar')
            ->where($this->setWhere())->order('id desc')
            ->paginate(15)->each(function ($item) {
                $item['cate_name'] = CarCateModel::where('id',$item['car_cate_id'])->value('name')??'';
                $item['status_name'] = $item->get_status()[$item['status']];
            });

    }


    public function details()
    {

        $id = $this->request->param('id');
        $item = $this->model->alias('a')
            ->join('member m', 'a.user_id=m.id')
            ->field('a.*,m.nickname,m.mobile,m.avatar')
            ->where('a.id', $id)->find();
        $array = ['create_time', 'pay_time', 'receive_time', 'take_time', 'wash_start_time', 'wash_end_time', 'return_time', 'finish_time', 'cancel_time'];
        foreach ($array as $v) {
            if (!empty($item[$v])) {
                $item[$v]= date('Y-m-d H:i:s', $item[$v]);
            }else{
                $item[$v] = '';
            }

        }
        $item = CommonModel::getOne($item, ['take_car_video', 'wash_video', 'return_car_video'], ['take_car_image', 'return_car_image']);
        $WorkModel = new WorkModel();
        $item['work_info'] =$WorkModel->where('id', $item['work_id'])->field('name,mobile')->find();
        $this->assign('item', $item);
        return $this->fetch();
    }


    //评论
    public
    function orderComment()
    {
        $Model = new CommentModel();
        $keyword = $this->request->param('keyword', '', 'trim');
        $map = [];
        $map[] = ['a.status', 'in', [1, 2]];
        if (!empty($keyword)) {
            $map[] = ['b.mobile|b.nickname|c.goods_name', 'like', "%$keyword%"];
        }

        $list = $Model->getList($map);

        $list->appends(['keyword' => $keyword]);
        // 获取分页显示
        $page = $list->render();

        $this->assign("page", $page);
        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }



    public function allocation()
    {

        $params = $this->request->param();
        $work_id = $this->model->where('id', $params['id'])->value('work_id');
        $this->assign('work_id', empty($work_id) ? '' : $work_id);
        $DoctorModel = new WorkModel();
        $name = $DoctorModel->column('name', 'id');
        $this->assign('name', $name);
        $this->assign('params', $params);
        return $this->fetch();
    }

    public function allocation_post()
    {
        $params = $this->request->param();
        if (empty($params['id'])) {
            $this->error('师傅不能为空！');
        }
        $params['status'] = 3;//待服务
        $params['receive_time'] = time();
        $order = $this->model->where('id', $params['id'])->find();
        $this->model->where('id', $params['id'])->save($params);
        $this->success('保存成功！');
    }

    public function confirmRefund()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $text = $this->request->param('text');
        $order_info = OrderModel::find($id);
        if ($this->admin_id != 1) {
            $this->error('您没有权限');
        }
        $order_num = $order_info['order_num'];
        if ($order_info['status'] == 12) {
            if ($status == 14) {
                //拒绝退款
                $order_info->save(['status' => 14, 'refuse_time' => time(), 'refuse_reason' => $text]);
                $this->success('操作成功');
            } elseif ($status == 16) {
                $RefundController = new RefundController();
                $re = $RefundController->pay_refund($order_num);
                if ($re['code'] == 0) $this->error($re['msg']);
                $order_info->save(['status' => 16, 'refund_time' => time()]);
                $this->success('退款成功');

            } else {
                $this->error('错误');
            }
        }


    }


    public function commission()
    {
        $id = $this->request->param('id');
        $commission = $this->request->param('commission');
        $order = $this->model->where('id', $id)->find();
        $result = $order->save(['commission_price' => $commission, 'status' => 9]);
        if ($result) {
            $commissionModel = new CommissionModel();
            MemberModel::where(['id' => $order['murse_id']])->inc('balance', $commission)->update();
            $commissionModel->save(['order_num' => $order['order_num'], 'user_id' => $order['murse_id'], 'price' => $commission]);
            $this->success('保存成功！');
        } else {
            $this->error('保存失败！');
        }
    }

    public function order_end()
    {
        $id = $this->request->param('id');
        $order = $this->model->where('id', $id)->find();
        $result = $order->save(['status' => 8, 'end_time' => time()]);
        if ($result) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

}