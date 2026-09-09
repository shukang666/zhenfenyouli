<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use api\user\model\UserModel;
use api\wxapp\controller\RefundController;
use api\wxapp\model\AccountLogModel;
use api\wxapp\model\GoodsModel;
use api\wxapp\model\GoodsSkuModel;
use api\wxapp\model\OrderCommentModel;
use api\wxapp\model\OrderStatusModel;
use api\wxapp\model\ShopOrderDetailModel;
use api\wxapp\model\ShopOrderModel;
use api\wxapp\model\UserCouponModel;
use app\admin\model\member\MemberModel;
use app\admin\model\order\AfterSaleModel;
use app\user\model\ThirdPartyUserModel;
use cmf\controller\AdminBaseController;
use think\facade\Db;
use think\facade\Log;
use think\Model;

class OrderController extends AdminBaseController
{
    /**
     * 订单
     */
    public function index($pay_type = '',$delivery_type = '', $order_num = '', $goods_name = '', $username = '', $phone = '', $status = '', $start_time = '', $end_time = '')
    {
        $Model = new ShopOrderModel();
        $map = [];
        $map[] = ['a.delete_time', '=', 0];
        if (!empty($order_num)) $map[] = ['a.order_num|a.pay_order_num', 'like', "%$order_num%"];
        if (!empty($goods_name)) $map[] = ['c.goods_name', 'like', "%$goods_name%"];
        if (!empty($username)) $map[] = ['a.username|nickname', 'like', "%$username%"];
        if (!empty($phone)) $map[] = ['a.phone|mobile', 'like', "%$phone%"];
        if (!empty($delivery_type)) $map[] = ['a.delivery_type', '=', "$delivery_type"];
        if (!empty($pay_type)) $map[] = ['a.pay_type', '=', "$pay_type"];

        if (!empty($status)) {
            if ($status == 10) {
                $map[] = ['a.status', 'in', [10, 16]];
            } elseif ($status == 2) {
                $map[] = ['a.status', 'in', [2, 14]];
            } elseif ($status == 20) {
                $map[] = ['a.status', 'in', [24, 18, 20, 22]];
            } else {
                $map[] = ['a.status', '=', $status];
            }
        }

        if ($start_time) $map[] = ['a.create_time', '>=', strtotime($start_time)];
        if ($end_time) $map[] = ['a.create_time', '<=', strtotime($end_time) + 86399];

        $list = $Model->getInfo($map);

        $list->appends(['order_num' => $order_num, 'goods_name' => $goods_name, 'username' => $username,
            'phone' => $phone, 'status' => $status, 'start_time' => $start_time, 'end_time' => $end_time,
        ]);
        // 获取分页显示
        $page = $list->render();
//        $param = $this->request->param();
        $this->assign('delivery_type', $delivery_type);
        $this->assign('pay_type', $pay_type);
        $this->assign('status', $status);
        $params=$this->request->param();
        $this->assign('params', $params);

        $this->assign("page", $page);
        $this->assign('list', $list);

        $this->assign('status_menu', ShopOrderModel::getStatusMenu());
        $this->assign('status_list', ShopOrderModel::getStatusList());
        $this->assign('check_status_list', ShopOrderModel::getStatusName(-1));
        $this->assign('delivery_type_list', ShopOrderModel::getDeliveryTypeName(-1));
        $this->assign('pay_list', [1 => '微信支付', 3 => '积分支付']);

        return $this->fetch();
    }

    public function details()
    {
        $Model = new ShopOrderModel();
        $id = $this->request->param('id');
        $map[] = ['a.id', '=', $id];
        $data = $Model->getInfo($map);
        $data = $data[0] ?? [];
        $this->assign('data', $data);
        $this->assign('goods_list', $data['goods_list']);
        return $this->fetch();

    }


    //自提完成时间
    public function finish()
    {
        $id = $this->request->param('id');
        $order_info = ShopOrderModel::find($id);
        $data_i['status'] = 4;
        $data_i['send_time'] = time();
        $res = $order_info->save($data_i);
        if ($res) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /*
  * 发货
  */
    public function send()
    {
        $order_id = $this->request->param('id');
        $ShopOrderModel = new ShopOrderModel();
        $order_info = $ShopOrderModel->getInfo([['a.id', '=', $order_id]]);
        $order_info = $order_info[0] ?? '';

        if ($this->request->isPost()) {
            $express_name = $this->request->param('express_name');
            $express_sn = $this->request->param('express_sn');
            if (empty($express_sn)) $this->error('单号不能为空');

            $ShopOrderModel->where(['id' => $order_id])->save(['express_name' => $express_name, 'express_sn' => $express_sn, 'send_time' => time(), 'status' => 4]);

            $WxOrderController = new WxOrderController();
            $MemberModel = new MemberModel();
            $openid = $MemberModel->where(['id' => $order_info['user_id']])->value('openid');

            $exp_code = Db::name('shop_express')->where(['name' => $express_name])->value('code');
            //给腾讯推送
            $WxOrderController->uploadShippingInfo($order_info['order_num'], $openid, '物品', 1, $exp_code, $express_sn, $order_info['phone']);

            $this->success('发货成功');
        } else {
            $this->assign('data', $order_info);

            $express_list = Db::name('shop_express')->select();
            $this->assign('express_list', $express_list);

            return $this->fetch();
        }
    }


    public function goods_detail()
    {

        $params = $this->request->param();
        $ShopOrderDetailModel = new ShopOrderDetailModel();
        $AfterSaleModel = new AfterSaleModel();
        $where = [];
        $where[] = ['a.order_num', '=', $params['order_num']];
        $list = $ShopOrderDetailModel->alias('a')
            ->field('a.goods_name,a.thumb,b.*,a.sku_name,a.count')
            ->join('shop_order_aftersale b ', 'a.id=b.order_goods_id')
            ->where($where)->paginate(10)->each(function ($item) use ($AfterSaleModel) {
                $item['status_name'] = $AfterSaleModel->get_status()[$item['aftersale_status']] ?? '';
                $item['type_name'] = $AfterSaleModel->get_type()[$item['aftersale_type']] ?? '';
            });
        $this->assign('params', $params);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());

        return $this->fetch();
    }

    public function refund_goods()
    {
        $params = $this->request->param();
        $ShopOrderModel = new ShopOrderModel();
        $AfterSaleModel = new AfterSaleModel();
        $item = $AfterSaleModel->where(['id' => $params['id']])->find();
        if ($item['aftersale_status'] == 3) $params['status'] = 4;
        $AfterSaleModel->where(['id' => $params['id']])->update(['aftersale_status' => $params['status']]);
        $this->success('操作成功');
    }


    public function confirmRefund()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $text = $this->request->param('text');
        $order_info = ShopOrderModel::find($id);
        if ($this->admin_id != 1) {
            $this->error('您没有权限');
        }

        $order_num = $order_info['pay_order_num'];

        if ($order_info['status'] == 12) {
            if ($status == 14) {
                //拒绝退款
                $order_info->save(['status' => 14, 'refuse_time' => time(), 'refuse_reason' => $text]);
                $this->success('操作成功');
            } elseif ($status == 16) {

                Db::startTrans();

                if ($order_info['pay_type'] == 1) {
                    $RefundController = new RefundController();
                    $re = $RefundController->pay_refund($order_num);
                    if ($re['code'] == 0) $this->error($re['msg']);

                } elseif ($order_info['pay_type'] == 2) {
                    AccountLogModel::add_account_log($order_info['user_id'], 1, 101, $order_info['balance_pay'], ['order_num' => $order_num]);
                } elseif ($order_info['pay_type'] == 3) {
                    AccountLogModel::add_account_log($order_info['user_id'], 2, 204, $order_info['point_pay'], ['order_num' => $order_num]);
                }

                $data_i['status'] = 16;
                $res = $order_info->save($data_i);
                ShopOrderModel::setSellCountOrStock('stock_inc', $order_num);
                ShopOrderModel::setSellCountOrStock('sell_count_dec', $order_num);
                Db::commit();
                if ($res) {
                    $this->success('退款成功');
                } else {
                    $this->error('退款失败');
                }
            }
        } elseif ($order_info['status'] >= 18) {
            //拒绝退款
            if ($status == 14) {
                //拒绝退款
                $order_info->save(['status' => 14, 'refuse_time' => time(), 'refuse_reason' => $text]);
                $this->success('操作成功');
            } elseif ($status == 20) {

                $order_info->save(['status' => 20, 'agree_return_time' => time()]);
                $this->success('操作成功');
            } elseif ($status == 22) {
                if ($order_info['pay_type'] == 1) {
                    $RefundController = new RefundController();
                    $re = $RefundController->pay_refund($order_num);

                    if ($re['code'] == 0) $this->error($re['msg']);

                } elseif ($order_info['pay_type'] == 2) {
                    AccountLogModel::add_account_log($order_info['user_id'], 1, 101, $order_info['balance_pay'], ['order_num' => $order_num]);
                }
                if (!empty($order_info['coupon_id'])) {
                    $UserCouponModel = new UserCouponModel();
                    $UserCouponModel->where(['id' => $order_info['coupon_id']])->save(['used' => 0]);
                }
            }
            ShopOrderModel::setSellCountOrStock('stock_inc', $order_num);
            ShopOrderModel::setSellCountOrStock('sell_count_dec', $order_num);
            $order_info->save(['status' => 24, 'return_price_time' => time()]);
            $this->success('操作成功');
        } else {
            $this->error('错误');
        }
    }

    //取消原因
    public function reason()
    {
        $order_id = $this->request->param('id');
        $ShopOrderModel = new ShopOrderModel();
        $order_info = $ShopOrderModel->getInfo(['a.id' => $order_id]);
        $this->assign('data', $order_info[0]);
        return $this->fetch();
    }

    //评论
    public function orderComment()
    {
        $Model = new OrderCommentModel();
        $keyword = $this->request->param('keyword', '', 'trim');
        $map = [];
        $map[] = ['a.status', 'in', [1, 2]];
        if (!empty($keyword)) {
            $map[] = ['b.mobile|b.nickname|c.name|d.name', 'like', "%$keyword%"];
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


    public function deleteComment()
    {
        $id = $this->request->param('id');
        $Model = new OrderCommentModel();
        $Model->where(['id' => $id])->save(['status' => 0]);
        $this->success('删除成功');
    }

    public function addAdminRemark()
    {
        $id = $this->request->param('id');
        $admin_remark = $this->request->param('admin_remark');
        $ShopOrderModel = new ShopOrderModel();
        $ShopOrderModel->where(['id' => $id])->save(['admin_remark' => $admin_remark]);
        $this->success('添加成功');
    }


    //导出
    public function outData($keyword = '', $status = '', $start_time = '', $end_time = '')
    {
        set_time_limit(0);
        $map = [];
        $map[] = ['a.delete_time', '=', 0];
        if (!empty($keyword)) {
            $map[] = ['b.nickname|b.mobile|b.true_name|a.order_num', 'like', "%$keyword%"];
        }
        if ($status) {
            $map[] = ['a.status', '=', $status];
        }

        if ($start_time) {
            $map[] = ['a.create_time', '>=', strtotime($start_time)];
        }
        if ($end_time) {
            $map[] = ['a.create_time', '<=', strtotime($end_time) + 86399];
        }
        $ShopOrderModel = new ShopOrderModel();
        $list = $ShopOrderModel->getInfo($map, 'a.*', -1);

        if ($list->isEmpty()) {
            $this->error('当前没有可以导出的订单');
        }

        $excel = new \PHPExcel();
        $excel->getProperties()
            ->setCreator("FastAdmin")
            ->setLastModifiedBy("FastAdmin")
            ->setTitle("标题")
            ->setSubject("Subject");
        $excel->getDefaultStyle()->getFont()->setName('Microsoft Yahei');
        $excel->getDefaultStyle()->getFont()->setSize(12);
        $this->sharedStyle = new \PHPExcel_Style();
        $this->sharedStyle->applyFromArray(
            array(
                'fill' => array(
                    'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '000000')
                ),
                'font' => array(
                    'color' => array('rgb' => "000000"),
                ),
                'alignment' => array(
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'indent' => 1
                ),
                'borders' => array(
                    'allborders' => array('style' => \PHPExcel_Style_Border::BORDER_THIN),
                )
            ));

        $worksheet = $excel->setActiveSheetIndex(0);
        $worksheet->setTitle('标题');


        $data = [];
        #表头
        $data1 = ['订单编号', '下单人', '下单时间', '下单商品', '订单状态', '商品金额', '优惠金额', '支付金额', '收货地址', '备注'];
        #导出内容组合
        foreach ($list as $kk => $v) {
            $data[$kk + 1]['order_num'] = ' ' . $v['order_num'];
            $data[$kk + 1]['user_info'] = $v['nickname'] . '，' . $v['mobile'];
            $data[$kk + 1]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

            $goods = [];
            foreach ($v['goods_list'] as $item) {
                $goods[] = $item['goods_name'] . '，单价：' . $item['one_price'] . '，数量:' . $item['count'];
            }
            $data[$kk + 1]['goods'] = $goods;
            $data[$kk + 1]['status_name'] = $v['status_name'];
            $data[$kk + 1]['goods_price'] = $v['goods_price'];
            $data[$kk + 1]['coupon_price'] = $v['coupon_price'];
            $data[$kk + 1]['price'] = $v['price'];
            $data[$kk + 1]['address'] = $v['user_address'] ?? '' . '，' . $v['username'] ?? '' . '，' . $v['phone'] ?? '';
            $data[$kk + 1]['remark'] = $v['remark'];

        }

        $this->exportOrderExcel2("订单列表", $data1, $data);
        $this->success('导出成功');

    }

    public function exportOrderExcel2($title, $cellName, $data)
    {
        //引入核心文件
        $objPHPExcel = new \PHPExcel();
        //定义配置
        $topNumber = 2;//表头有几行占用
        $xlsTitle = iconv('utf-8', 'gb2312', $title);//文件名称
        $fileName = $title . date('_YmdHis');//文件名称
        $cellKey = array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
            'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
        );

        $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);//所有单元格（列）默认宽度

        //垂直居中
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        //处理表头标题
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $cellKey[count($cellName) - 1] . '1');//合并单元格（如果要拆分单元格是需要先合并再拆分的，否则程序会报错）
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $title);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        //处理表头
        foreach ($cellName as $k => $v) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellKey[$k] . $topNumber, $v);//设置表头数据
        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(80);

        //处理数据
        $start = $topNumber + 1;
        $j = $topNumber + 1;
        foreach ($data as $k => $v) {
//            $j = $start;
            foreach ($v['goods'] as $k1 => $v1) {
                if ($k1 == 0) {
                    //计算初步当前单元格标识，以及 需要合并的单元格标识 A B C D E F G M
                    $end = $start + count($v['goods']) - 1;
                    $objPHPExcel->getActiveSheet()->mergeCells("A" . $start . ':' . "A" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("B" . $start . ':' . "B" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("C" . $start . ':' . "C" . $end);
//                    $objPHPExcel->getActiveSheet()->mergeCells("D".$start.':'."D".$end);
                    $objPHPExcel->getActiveSheet()->mergeCells("E" . $start . ':' . "E" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("F" . $start . ':' . "F" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("G" . $start . ':' . "G" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("H" . $start . ':' . "H" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("I" . $start . ':' . "I" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("J" . $start . ':' . "J" . $end);

                    $objPHPExcel->getActiveSheet()->setCellValue("A" . $start, $v['order_num']);
                    $objPHPExcel->getActiveSheet()->setCellValue("B" . $start, $v['user_info']);
                    $objPHPExcel->getActiveSheet()->setCellValue("C" . $start, $v['create_time']);

                    $objPHPExcel->getActiveSheet()->setCellValue("E" . $start, $v['status_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue("F" . $start, $v['goods_price']);
                    $objPHPExcel->getActiveSheet()->setCellValue("G" . $start, $v['coupon_price']);
                    $objPHPExcel->getActiveSheet()->setCellValue("H" . $start, $v['price']);
                    $objPHPExcel->getActiveSheet()->setCellValue("I" . $start, $v['address']);
                    $objPHPExcel->getActiveSheet()->setCellValue("J" . $start, $v['remark']);
                }
                $objPHPExcel->getActiveSheet()->setCellValue("D" . $j, $v1);
                $j++;
            }

            $start += count($v['goods']);
        }

        //导出execl
        ob_end_clean();//防止乱码
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xlsx"');
        header("Content-Disposition:attachment;filename=$fileName.xlsx");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }


    public function delete()
    {
        $id = $this->request->param('id');
        ShopOrderModel::where('id', $id)->update(['delete_time' => time()]);
        $this->success('删除成功');
    }
}
