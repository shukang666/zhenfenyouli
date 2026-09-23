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
use cmf\model\RoleUserModel;
use app\user\model\ThirdPartyUserModel;
use cmf\controller\AdminBaseController;
use think\exception\HttpResponseException;
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
        $params = $this->request->param();
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


        if(!empty($params['is_export'])){
            return $this->export($map);
        }

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
        if (!$this->canManageOrderRefund()) {
            $this->error('您没有权限');
        }

        $pay_order_num = $order_info['pay_order_num'];
        $order_num = $order_info['order_num'];

        if ($order_info['status'] == 12) {
            if ($status == 14) {
                //拒绝退款
                $order_info->save(['status' => 14, 'refuse_reason' => $text]);
                $this->success('操作成功');
            } elseif ($status == 16) {

                Db::startTrans();

                if ($order_info['pay_type'] == 1) {
                    $RefundController = new RefundController();
                    $re = $RefundController->pay_refund($pay_order_num);
                    if ($re['code'] == 0) $this->error($re['msg']);

                } elseif ($order_info['pay_type'] == 2) {
                    AccountLogModel::add_account_log($order_info['user_id'], 1, 101, $order_info['balance_pay'], ['order_num' => $pay_order_num]);
                } elseif ($order_info['pay_type'] == 3) {
                    AccountLogModel::add_account_log($order_info['user_id'], 2, 204, $order_info['point_pay'], ['order_num' => $pay_order_num]);
                }

                //退回已抵扣积分
                if($order_info['point'] > 0) AccountLogModel::add_account_log($order_info['user_id'], 2, 204, $order_info['point'], ['order_num' => $pay_order_num]);

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
                $order_info->save(['status' => 14, 'refuse_reason' => $text]);
                $this->success('操作成功');
            } elseif ($status == 20) {

                $order_info->save(['status' => 20, 'agree_return_time' => time()]);
                $this->success('操作成功');
            } elseif ($status == 22) {
                if ($order_info['pay_type'] == 1) {
                    $RefundController = new RefundController();
                    $re = $RefundController->pay_refund($pay_order_num);

                    if ($re['code'] == 0) $this->error($re['msg']);

                } elseif ($order_info['pay_type'] == 2) {
                    AccountLogModel::add_account_log($order_info['user_id'], 1, 101, $order_info['balance_pay'], ['order_num' => $pay_order_num]);
                }
                if (!empty($order_info['coupon_id'])) {
                    $UserCouponModel = new UserCouponModel();
                    $UserCouponModel->where(['id' => $order_info['coupon_id']])->save(['used' => 0]);
                }
            }

            //退回已抵扣积分（仅完成退款 status=22 时执行，避免同意退货等中间态误还库存）
            if ((int)$status === 22) {
                if ($order_info['point'] > 0) {
                    AccountLogModel::add_account_log($order_info['user_id'], 2, 204, $order_info['point'], ['order_num' => $pay_order_num]);
                }
                ShopOrderModel::setSellCountOrStock('stock_inc', $order_num);
                ShopOrderModel::setSellCountOrStock('sell_count_dec', $order_num);
                $order_info->save(['status' => 24, 'return_price_time' => time()]);
            }
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

    public function delete()
    {
        $id = $this->request->param('id');
        ShopOrderModel::where('id', $id)->update(['delete_time' => time()]);
        $this->success('删除成功');
    }

    /**
     * 退款并删除：支付/积分原路退回，软删除订单（列表不再展示）
     */
    public function refundAndDelete()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        if (!$this->canManageOrderRefund()) {
            $this->error('您没有权限');
        }

        $id = (int)$this->request->param('id');
        $orderInfo = ShopOrderModel::where('id', $id)->where('delete_time', 0)->find();
        if (empty($orderInfo)) {
            $this->error('订单不存在或已删除');
        }

        $status = (int)$orderInfo['status'];
        if (in_array($status, [16, 24], true)) {
            $this->error('订单已退款，请勿重复操作');
        }
        if ($status === 10) {
            $this->error('订单已取消，请使用普通删除');
        }
        if ($status === 1) {
            $this->error('待付款订单请让用户取消或仅做删除');
        }

        Db::startTrans();
        try {
            $err = $this->rollbackOrderPaymentAndStock($orderInfo);
            if ($err !== null) {
                Db::rollback();
                $this->error($err !== '' ? $err : '退款失败，请稍后重试');
            }

            ShopOrderModel::where('id', $id)->update([
                'status'      => 16,
                'delete_time' => time(),
            ]);

            Db::commit();
            $this->success('已退款，积分/支付已原路退回，订单已删除');
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Db::rollback();
            $detail = trim($e->getMessage());
            Log::error('refundAndDelete id=' . $id . ': ' . $detail . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->error('操作失败：' . ($detail !== '' ? $detail : '系统异常，请查看后台日志'));
        }
    }

    /**
     * 与 confirmRefund 一致：退微信/余额/积分、退抵扣积分、还券、加库存减销量
     * @return string|null 失败原因
     */
    protected function rollbackOrderPaymentAndStock($orderInfo): ?string
    {
        $payOrderNum = $orderInfo['pay_order_num'];
        $orderNum = $orderInfo['order_num'];
        $payType = (int)$orderInfo['pay_type'];

        if ($payType === 1 && (float)$orderInfo['price'] > 0) {
            $refundController = new RefundController();
            $re = $refundController->pay_refund($payOrderNum);
            if (($re['code'] ?? 0) == 0) {
                $msg = trim((string)($re['msg'] ?? ''));
                return $msg !== '' ? $msg : '微信退款失败，请检查商户证书或订单是否已退过款';
            }
        } elseif ($payType === 2 && (float)$orderInfo['balance_pay'] > 0) {
            AccountLogModel::add_account_log(
                $orderInfo['user_id'],
                1,
                101,
                $orderInfo['balance_pay'],
                ['order_num' => $payOrderNum, 'remark' => '后台退款并删除']
            );
        } elseif ($payType === 3 && (float)$orderInfo['point_pay'] > 0) {
            AccountLogModel::add_account_log(
                $orderInfo['user_id'],
                2,
                204,
                $orderInfo['point_pay'],
                ['order_num' => $payOrderNum, 'remark' => '后台退款并删除']
            );
        }

        if ((float)$orderInfo['point'] > 0) {
            AccountLogModel::add_account_log(
                $orderInfo['user_id'],
                2,
                204,
                $orderInfo['point'],
                ['order_num' => $payOrderNum, 'remark' => '后台退抵扣积分']
            );
        }

        if (!empty($orderInfo['coupon_id'])) {
            (new UserCouponModel())->where(['id' => $orderInfo['coupon_id']])->save(['used' => 0]);
        }

        ShopOrderModel::setSellCountOrStock('stock_inc', $orderNum);
        ShopOrderModel::setSellCountOrStock('sell_count_dec', $orderNum);

        return null;
    }

    //导出：列表页设置 is_export=1 提交到 index，由 index 构建与列表一致的筛选条件后调用本方法
    public function export($map){
        //不限制时间
        set_time_limit(0);
        //设置文件大小
        ini_set('memory_limit','18000M');
        // PHPExcel 内部使用了花括号字符串偏移等旧语法，PHP 7.4+ 会抛 Deprecated；
        // 服务器 display_errors 开启时这些提示会混进 xlsx 二进制流导致文件损坏，
        // 导出期间必须屏蔽弃用类错误的输出
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        @ini_set('display_errors', '0');

        $Model = new ShopOrderModel();

        // ShopOrderModel::getInfo 签名为 ($map, $field, $page_size)，page_size 必须传 -1 才会全量查询
        $list = $Model->getInfo($map,'a.*',-1);

        $cellName = ['订单号', '下单用户', '商品名称', '用户ID', '单价', '数量', '优惠券', '实付款', '状态', '上传时间'];


        #导出内容组合
        foreach ($list as $kk => $v) {
            $data[$kk + 1]['order_num'] = ' ' . $v['order_num'];
            $data[$kk + 1]['user_info'] = $v['nickname'] . '，' . $v['mobile'];
            $data[$kk + 1]['user_id'] = $v['user_id'];

            //商品三要素分列存储：商品名称(C列)、单价(E列)、数量(F列)，用户ID(D列)跨行合并
            $goods = [];
            foreach ($v['goods_list'] as $item) {
                $goods[] = [
                    'goods_name' => $item['goods_name'],
                    'one_price'  => $item['one_price'],
                    'count'      => $item['count'],
                ];
            }
            $data[$kk + 1]['coupon_price'] = $v['coupon_price'];
            $data[$kk + 1]['price'] = $v['price'];
            $data[$kk + 1]['status_name'] = $v['status_name'];
            $data[$kk + 1]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

            $data[$kk + 1]['goods'] = $goods;
            $data[$kk + 1]['address'] = $v['user_address'] ?? '' . '，' . $v['username'] ?? '' . '，' . $v['phone'] ?? '';
            $data[$kk + 1]['remark'] = $v['remark'];

        }


        $title = '订单列表';
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
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(50);

        //处理数据
        //列布局：A订单号 B下单用户(跨行合并) | C商品名称 D用户ID(跨行合并) E单价 F数量(每个商品一行) | G优惠券 H实付款 I状态 J上传时间(跨行合并)
        $start = $topNumber + 1;
        $j = $topNumber + 1;
        foreach ($data as $k => $v) {
            foreach ($v['goods'] as $k1 => $v1) {
                if ($k1 == 0) {
                    //计算初步当前单元格标识，以及需要合并的单元格标识
                    $end = $start + count($v['goods']) - 1;
                    $objPHPExcel->getActiveSheet()->mergeCells("A" . $start . ':' . "A" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("B" . $start . ':' . "B" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("D" . $start . ':' . "D" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("G" . $start . ':' . "G" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("H" . $start . ':' . "H" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("I" . $start . ':' . "I" . $end);
                    $objPHPExcel->getActiveSheet()->mergeCells("J" . $start . ':' . "J" . $end);

                    $objPHPExcel->getActiveSheet()->setCellValue("A" . $start, $v['order_num']);
                    $objPHPExcel->getActiveSheet()->setCellValue("B" . $start, $v['user_info']);
                    $objPHPExcel->getActiveSheet()->setCellValue("D" . $start, $v['user_id']);
                    $objPHPExcel->getActiveSheet()->setCellValue("G" . $start, $v['coupon_price']);
                    $objPHPExcel->getActiveSheet()->setCellValue("H" . $start, $v['price']);
                    $objPHPExcel->getActiveSheet()->setCellValue("I" . $start, $v['status_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue("J" . $start, $v['create_time']);
                }
                $objPHPExcel->getActiveSheet()->setCellValue("C" . $j, $v1['goods_name']);
                $objPHPExcel->getActiveSheet()->setCellValueExplicit("E" . $j, $v1['one_price'], \PHPExcel_Cell_DataType::TYPE_STRING);
                $objPHPExcel->getActiveSheet()->setCellValue("F" . $j, $v1['count']);
                $j++;
            }

            $start += count($v['goods']);
        }

        //导出execl
        //关闭所有层级输出缓冲，丢弃前面任何已产生的输出（防止残留文本混入 xlsx）
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xlsx"');
        header("Content-Disposition:attachment;filename=$fileName.xlsx");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    /** 订单退款处理：超级管理员（user_id=1 或 角色「超级管理员」） */
    protected function canManageOrderRefund(): bool
    {
        $adminId = (int)$this->admin_id;
        if ($adminId === 1) {
            return true;
        }
        $roleIds = RoleUserModel::where('user_id', $adminId)->column('role_id');
        return in_array(1, $roleIds, true);
    }
}
