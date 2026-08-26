<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\AnswerItemModel;
use api\wxapp\model\OrderCommentModel;
use api\wxapp\model\OrderPayModel;
use api\wxapp\model\ShopOrderModel;
use app\admin\controller\WxOrderController;
use app\admin\model\forum\ForumModel;
use app\admin\model\health\HealthHomeModel;
use app\admin\model\member\MemberModel;
use app\admin\model\order\OrderTakeModel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;
use think\facade\Log;

class TestController extends AuthController
{


    /**
     * 测试
     * @OA\Post(
     *tags={"基础"},
     *path="/wxapp/test/get_test1",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="user_id",description="用户ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_test1()
    {

/*
        $res = Db::query("SELECT
	id,user_id, order_num,  FROM_UNIXTIME(create_time, '%Y-%m-%d %H:%i:%s') AS formatted_datetime ,
	count(create_time) count,change_amount
FROM
	cmf_user_account_log

	where  create_time>=1757571958 and type=203
	GROUP BY create_time,order_num

	HAVING count(create_time)=2
	");
        $AccountLogModel = new AccountLogModel();

        foreach ($res as $item) {
//            AccountLogModel::add_account_log($item['user_id'], 2, 210, -$item['change_amount'],
//                ['order_num' => $item['order_num'], 'remark' => '订单多扣退回']);
//            $AccountLogModel->where('id', $item['id'])->update(['delete_time' => time(), 'is_op' => 2]);
        }
        $this->success('成功', $res);*/
//        dump(1);exit;


//        $q_id = 4512;
//        $AnswerItemModel = new AnswerItemModel();
//        $AnswerItem = $AnswerItemModel->where(['id' => $q_id])->find();
//


//        $CommentModel=new OrderCommentModel();
//        $shop_info['point'] = $CommentModel->where(['shop_id' => 0])->avg('point');
//            $this->success('成功', $shop_info);
//
        $ShopOrderModel = new ShopOrderModel();
        $OrderPayModel = new OrderPayModel();
        $MemberModel = new MemberModel();
//
//
//
//        $list = $ShopOrderModel
//            ->where('status', 4)
//            ->where('pay_type', 3)
//            ->whereNull('pay_time')
////            ->where('send_time', '<', time() - 7 * 86400)
//            ->select();
//        foreach ($list as $item) {
//            $ShopOrderModel->update(['send_time' => time(),'pay_time'=>$item['create_time']], ['id' => $item['id']]);
//        }
//        $this->success('请求成功',$list);


//        $list = $OrderPayModel
//            ->alias('a')
//            ->join('shop_order b', 'b.order_num=a.order_num')
//            ->where('a.status', 2)
//            ->select();

        $list = $ShopOrderModel
//            ->where('status', '=', 2)
            ->where('pay_type', '=', 1)
            ->where('create_time', '<=', 1751029256)->select();
//        $this->success('成功', count($list));
//
//            ->update(['status'=>8]);
//        $this->success('成功',$list);
//        $array_ids = array_column($list, 'id');
//        var_dump($array_ids);exit;
        cmf_log(json_encode($list, 256), '发货', 'order.txt');
        foreach ($list as $item) {
            $openid = $MemberModel->where('id', $item['user_id'])->value('openid');
            $WxOrderController = new WxOrderController();
            $res = $WxOrderController->uploadShippingInfo($item['pay_order_num'], $openid, '物品', 4);

        }
        $this->success('成功', count($list));

//        //待收货，7天后收货
//
        $list = $ShopOrderModel->where('status', 4)
            ->where('send_time', '<', time() - 7 * 86400)
            ->select();

        $this->success('成功', $list);
        $orderIds = $list->pluck('id')->toArray();

        var_dump($orderIds);
        exit;
//        foreach ($list as $item) {
//            $ShopOrderModel->where('id', $item['id'])->update(['status' => 6, 'finish_time' => time()]);
//        }
        // 记录日志（示例：写入数据库或文件）
//        \think\facade\Log::write($list->);


//        OrderTakeModel::add_data(2,123);
//        $this->success('成功');
//        $order_num = $this->request->get('order_num', 2025062052140791);
        $WxOrderController = new WxOrderController();
        $MemberModel = new MemberModel();
        $ShopOrderModel = new ShopOrderModel();
        $OrderPayModel = new OrderPayModel();

//        $order_nums = $OrderPayModel->where('status', 2)->column('order_num');
////        var_dump($order_nums);exit;
//        $res = $ShopOrderModel->where('status', 2)
//            ->where('pay_type',2)
////            ->whereNotIn('pay_order_num', $order_nums)
//            ->update(['pay_type'=> 3]);
//        $this->success('成功', $res);
        $list = $OrderPayModel
            ->alias('a')
            ->field('a.pay_order_num,a.user_id')
            ->join('shop_order b', 'a.order_num=b.pay_order_num')
            ->where('a.status', 2)
            ->where('b.delivery_type', 2)->select();
        $this->success('测试成功', count($list));
        $order_info = $ShopOrderModel->where('pay_order_num', $order_num)->find();
        $openid = $MemberModel->where(['id' => $order_info['user_id']])->value('openid');
        $res = $WxOrderController->uploadShippingInfo($order_num, $openid, '物品', 4);
        $this->success('成功', $res);

        exit;
        // 记录开始时间（毫秒级精度）
        $startTime = microtime(true);
        $where[] = ['e.createtime', '>=', 1750780800];
        $where[] = ['e.createtime', '<=', 1750867140];
        $where[] = ['e.exam_id', '=', 1];

        if (1) {
            ini_set('memory_limit', '2048M');
            ini_set('max_execution_time', 0);
            $list = Db::name("exam_gift")->alias('e')
                ->leftjoin('member m', 'e.uid=m.id')
                ->field('e.createtime,e.uid,e.gift_name,e.gift_code,e.status,m.nickname,
m. nickname as username,name,e.mobile,e.id')
                ->where($where)->order('e.createtime asc')->select()
                ->each(function ($item, $key) use (&$headerRow) {
                    $headerRow = [
                        ['rowName' => '创建时间', 'rowVal' => 'createtime', 'width' => 30],
                        ['rowName' => '姓名|手机号', 'rowVal' => 'username', 'width' => 30],
                    ];
                    if (empty($item['name'])) {
                        $item['name'] = $item['nickname'];
                    }
                    $item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
                    $item['username'] = $item['name'] . '|' . $item['mobile'];
                    $question_list = Db::name("exam_record")
                        ->alias('e')
                        ->rightjoin('exam_question m', 'e.exam_id=m.exam_id and e.question_id=m.id')
                        ->field('e.*,m.question,m.option,m.type,e.id')
                        ->where('e.gift_id', $item['id'])
                        ->select()->each(function ($nitem, $nkey) {
                            $answer = [];
                            if ($nitem['type'] == 3) {
                                array_push($answer, $nitem['select_option']);
                            } else {
                                $select_option = explode(',', $nitem['select_option']);
                                $options = json_decode($nitem['option'], true);
                                foreach ($options as $key => $option) {
                                    if (in_array($key + 1, $select_option)) {
                                        array_push($answer, $option['option']);
                                    }
                                }
                            }
                            $nitem['answer'] = implode(',', $answer);
                            return $nitem;
                        });

                    foreach ($question_list as $key => $question) {
                        $item['question' . ($key + 1)] = $question['question'];//问题几
                        $headerRow[] = ['rowName' => '问题' . ($key + 1), 'rowVal' => 'question' . ($key + 1), 'width' => 30];
//答案多个都渲染出来
                        $option_arr = json_decode($question['option'], true);//问题的所有选项
                        foreach ($option_arr as $key2 => &$option) {
                            $sn = ($key + 1) . ($option['id']);//第几题的第几个选项
//选项标题
                            $headerRow[] = ['rowName' => $option['option'], 'rowVal' => 'answer' . $sn, 'width' => 30];
                            $item['answer' . $sn] = '';//只默认为空
                            $select_option = explode(',', $question['select_option']);
                            foreach ($select_option as $so) {//多选包含单选
                                if ($so == $option['id']) {
                                    $item['answer' . $sn] = '选择';
                                    if (!empty($question['hospital'])) {
                                        $item['answer' . $sn] = $question['hospital'];
                                    }
                                }
                            }


                        }


                    }
                    return $item;
                });
// exit;

            $list = $list->toArray();
// var_dump($list, $headerRow);
// exit;

            // 记录结束时间并计算总耗时
            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 3); // 保留3位小数（秒）

            // 输出执行时间（可写入日志或直接显示）
            echo "导出完成，总耗时：{$totalTime} 秒";
            die();

            $this->excelExport($list, $headerRow, ['fileName' => '问卷记录']);
            die();
        } //优化下 查数据很慢
    }


    /**
     * 测试
     * @OA\Post(
     *tags={"基础"},
     *path="/wxapp/test/get_test",
     *@OA\Parameter(name="openid",description="openid",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="id",description="ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Parameter(name="user_id",description="用户ID",in="query",required=false, @OA\Schema(type="string",)),
     *@OA\Response(response="200", description="An example resource"),
     *@OA\Response(response="default", description="An example resource")
     * )
     */
    public function get_test()
    {
        // 记录开始时间（毫秒级精度）
        $startTime = microtime(true);
        $where = [

        ];
        $where[] = ['e.createtime', '>=', 1750780800];
        $where[] = ['e.createtime', '<=', 1750867140];
        $where[] = ['e.exam_id', '=', 1];

        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 0);

        // 1. 先获取所有exam_gift数据
        $gifts = Db::name("exam_gift")->alias('e')
            ->leftjoin('member m', 'e.uid=m.id')
            ->field('e.createtime,e.uid,e.gift_name,e.gift_code,e.status,m.nickname, 
        m.nickname  as username,e.mobile,e.id')
            ->where($where)
            ->order('e.createtime  asc')
            ->select()
            ->toArray();

        // 2. 批量获取所有相关exam_record数据
        $giftIds = array_column($gifts, 'id');
        $records = Db::name("exam_record")
            ->alias('e')
            ->rightjoin('exam_question m', 'e.exam_id=m.exam_id  and e.question_id=m.id')
            ->field('e.*,m.question,m.option,m.type,m.exam_id,e.id  as record_id,e.gift_id')
            ->where('e.gift_id', 'in', $giftIds)
            ->select()
            ->toArray();

        // 3. 按gift_id分组记录
        $groupedRecords = [];
        foreach ($records as $record) {
            $groupedRecords[$record['gift_id']][] = $record;
        }

        // 4. 处理数据
        $headerRow = [];
        $list = [];
        foreach ($gifts as $item) {
            if (empty($item['name'])) {
                $item['name'] = $item['nickname'];
            }
            $item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
            $item['username'] = $item['name'] . '|' . $item['mobile'];

            // 初始化表头(只生成一次)
            if (empty($headerRow)) {
                $headerRow = [
                    ['rowName' => '创建时间', 'rowVal' => 'createtime', 'width' => 30],
                    ['rowName' => '姓名|手机号', 'rowVal' => 'username', 'width' => 30],
                ];
            }

            // 处理问题记录
            $questionList = $groupedRecords[$item['id']] ?? [];
            foreach ($questionList as $key => $question) {
                $itemKey = 'question' . ($key + 1);
                $item[$itemKey] = $question['question'];

                // 只添加一次表头
                if (count($headerRow) < count($questionList) + 2) {
                    $headerRow[] = ['rowName' => '问题' . ($key + 1), 'rowVal' => $itemKey, 'width' => 30];
                }

                // 处理选项
                $options = json_decode($question['option'], true) ?: [];
                $selectOptions = explode(',', $question['select_option'] ?? '');

                foreach ($options as $optKey => $option) {
                    $sn = ($key + 1) . $option['id'];
                    $answerKey = 'answer' . $sn;

                    // 只添加一次表头
                    if (!in_array(['rowName' => $option['option'], 'rowVal' => $answerKey, 'width' => 30], $headerRow)) {
                        $headerRow[] = ['rowName' => $option['option'], 'rowVal' => $answerKey, 'width' => 30];
                    }

                    $item[$answerKey] = in_array($option['id'], $selectOptions) ?
                        (!empty($question['hospital']) ? $question['hospital'] : '选择') : '';
                }
            }

            $list[] = $item;
        }

        $endTime = microtime(true);
        $totalTime = round($endTime - $startTime, 3); // 保留3位小数（秒）

        // 输出执行时间（可写入日志或直接显示）
        echo "导出完成，总耗时：{$totalTime} 秒";
        die();

        $this->excelExport($list, $headerRow, ['fileName' => '问卷记录']);
        die();
        exit;

        // `createtime` >= 1750780800
        //            AND `createtime` <= 1750867140 AND `exam_id` = 1


        // 数据导出功能终极优化版（2025-06-26）
        if (1) {
            // ==================== 初始化配置 ====================
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            gc_enable();
            Db::startTrans(); // 启用事务保证数据一致性

            try {
                // ==================== 阶段1：主查询优化 ====================
                $baseQuery = Db::name("exam_gift")
                    ->alias('e')
                    ->leftJoin('cmf_member m FORCE INDEX(PRIMARY)', 'e.uid  = m.id')  // 强制索引
                    ->field('e.id,  e.createtime,  e.uid,  e.gift_name,  e.gift_code,  e.status,  
                    m.nickname,  m.true_name  as name, e.mobile')
                    ->where(array_map(function ($cond) { // 自动补全表别名
                        return strpos($cond[0], '.') ? $cond : ['e.' . $cond[0], $cond[1], $cond[2]];
                    }, $where))
                    ->order('e.createtime', 'asc');

                // ==================== 阶段2：预加载优化 ====================
                $giftIds = $baseQuery->column('e.id');
                $questionsData = Db::name("exam_record")
                    ->alias('er')
                    ->join('exam_question eq', 'er.exam_id  = eq.exam_id  AND er.question_id  = eq.id')
                    ->field('er.gift_id, er.id, er.exam_id,  er.select_option,  eq.question,  eq.option,  eq.type')
                    ->whereIn('er.gift_id', $giftIds ?: [0])
                    ->group('gift_id')
                    ->select()->toArray(); // 按gift_id分组

                // ==================== 阶段3：流式处理 ====================
                $exportData = [];
                $headerRow = [
                    ['rowName' => '创建时间', 'rowVal' => 'createtime', 'width' => 30],
                    ['rowName' => '姓名|手机号', 'rowVal' => 'username', 'width' => 30]
                ];


                // 分块处理（每次1000条）
                $baseQuery->chunk(1000, function ($chunk) use (&$exportData, &$headerRow, $questionsData) {
                    foreach ($chunk as $item) {
                        // 基础字段处理
                        $processed = [
                            'createtime' => date('Y-m-d H:i:s', $item['createtime']),
                            'username' => ($item['name'] ?: $item['nickname']) . '|' . $item['mobile']
                        ];
                        var_dump($questionsData);
                        exit;
//                        var_dump($questionsData[$item['id']]);exit;
                        // 动态生成问题列
                        $res = $questionsData[$item['id']] ?? [];
//                        var_dump($item['id']);
                        foreach ($res as $qIndex => $q) {

                            $qKey = 'question' . ($qIndex + 1);
                            $processed[$qKey] = $q['question'];

                            // 动态添加表头（仅首次）
                            if (!isset($headerRow[$qKey])) {
                                $headerRow[] = ['rowName' => "问题" . ($qIndex + 1), 'rowVal' => $qKey, 'width' => 30];
                            }
                            // 处理选项
                            foreach (json_decode($q['option'], true) ?: [] as $opt) {
                                $optKey = 'answer' . ($qIndex + 1) . $opt['id'];
                                $isSelected = in_array($opt['id'], explode(',', $q['select_option']));
                                $processed[$optKey] = $isSelected ? ($q['hospital'] ?? '选择') : '';

                                if (!isset($headerRow[$optKey])) {
                                    $headerRow[] = ['rowName' => $opt['option'], 'rowVal' => $optKey, 'width' => 30];
                                }
                            }
                        }
                        $exportData[] = $processed;
//                        var_dump($processed);exit;
                    }
                }, 'e.id');  // 明确分块字段

                Db::commit();
                var_dump($headerRow);
                exit;
                $this->excelExport($exportData, $headerRow, ['fileName' => '问卷记录']);
                exit;
//                return ['header' => $headerRow, 'data' => $exportData];

            } catch (\Exception $e) {
                Db::rollback();
                Log::error("导出失败@" . date('Y-m-d H:i:s') . ": " . $e->getMessage());
                throw $e;
            }
        }


        exit;


        $user_id = $this->request->param('user_id');
        $forumModel = new ForumModel();
        $count = $forumModel->where('user_id', $user_id)
            ->where('type', 2)
            ->whereBetweenTime('create_time', strtotime(date('Y-m-d')), time())
            ->count();
        $this->success('成功', ['count' => $count]);

        $MemberModel = new MemberModel();
        $id = $this->request->param('id');
        $item = $MemberModel->where('id', $id)->cache(2)->find();
        $sql = $MemberModel->getLastSql();
        $this->success($sql, $item);


        exit;


        $Healthids = HealthHomeModel::where('type', 1)->column('id');
//        var_dump($Healthids);exit;
        $is_answer = Db::name('exam_gift')
            ->where('exam_id', 'in', $Healthids)
            ->where(['uid' => 3])->count() ? 1 : 0;
        $this->success('成功', ['is_answer' => $is_answer]);
    }


    /**
     * excel
     * @param array $data 数据data
     * @param array $headerRow 首行数据data
     * @param array $conf [fileName] string 文件名 | [format] string 文件格式后缀 xls
     * @param array $subtitle [rowName] string 列名 | [acrossCells] string 跨越列数
     * @return void
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function excelExport(array $data, array $headerRow, array $conf = [], array $subtitle = [])
    {
        /*$headerRow = [
            ['rowName' => 'ID', 'rowVal' => 'id', 'width' => 10],
            ['rowName' => '下单时间', 'rowVal' => 'create_time', 'width' => 30],
            ['rowName' => '订单号', 'rowVal' => 'order_num', 'width' => 30],
            ['rowName' => '订单状态', 'rowVal' => 'is_status', 'width' => 30],
            ['rowName' => '位号信息', 'rowVal' => 'title', 'width' => 30],
            ['rowName' => '预约时间', 'rowVal' => 'time', 'width' => 30],
            ['rowName' => '支付时间', 'rowVal' => 'pay_time', 'width' => 30],
            ['rowName' => '支付金额', 'rowVal' => 'amount', 'width' => 30],
            ['rowName' => '核销码', 'rowVal' => 'cav_code', 'width' => 30],
            ['rowName' => '身份证号', 'rowVal' => 'id_card', 'width' => 30],
            ['rowName' => '姓名', 'rowVal' => 'username', 'width' => 30],
            ['rowName' => '电话', 'rowVal' => 'phone', 'width' => 30],
        ];
        $this->Export($list, $headerRow, ['fileName' => '订单列表']);*/

        //生成文件信息
        if (isset($conf['fileName']) && $conf['fileName']) {
            $fileName = $conf['fileName'];
        } else {
            $fileName = date('YmdHis') . rand(1000, 9999) . rand(1000, 9999);
        }
        $format = $conf['format'] ?? 'Xlsx'; //'Xls'
        $newExcel = new Spreadsheet(); //创建一个新的excel文档

        $objSheet = $newExcel->getActiveSheet(); //获取当前操作sheet的对象
        $objSheet->setTitle($fileName); //设置当前sheet的标题

        $sort = 0;
        //首行标题
        if (isset($conf['fileName']) && $conf['fileName']) {
            //首行标题
            $styleArray = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ];
            $objSheet->setCellValue('A1', $fileName);
            $objSheet->getStyle('A1')->applyFromArray($styleArray);

            $length = count($headerRow) - 1;
            $objSheet->mergeCells('A1:' . $this->intToChr($length) . '1');
            $sort = 1;
        }

        /** 副标题列 */
        if ($subtitle) {
            $subSort = 0;
            foreach ($subtitle as $key) {
                $objSheet->setCellValue($this->intToChr($subSort) . "2", $key['rowName'] ?? ' ');
                $endSort = $subSort + $key['acrossCells'] - 1;
                $objSheet->mergeCells($this->intToChr($subSort) . "2:" . $this->intToChr($endSort) . "2");
                $subSort = $endSort + 1;
            }
            $sort = 2;
        }

        /** 字段渲染列 */
        $sort += 1;
        $sheetConfig = false; //根据 $headerRow 配置数据读取方式
        //标题栏设置 & 行宽设置
        $headerRowCount = count($headerRow);
        for ($i = 0; $i < $headerRowCount; $i++) {
            $rowLetter = $this->intToChr($i);

            //设置第一栏的标题
            if (is_array($headerRow[$i])) {
                $sheetConfig = true;
                $objSheet->setCellValue($rowLetter . $sort, $headerRow[$i]['rowName']);
                $newExcel->getActiveSheet()->getColumnDimension($rowLetter)->setWidth($headerRow[$i]['width']);
            } else {
                $objSheet->setCellValue($rowLetter . $sort, $headerRow[$i]);
                $newExcel->getActiveSheet()->getColumnDimension($rowLetter)->setWidth(30);
            }
        }

        //样式设置 - 水平、垂直居中
        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $newExcel->getActiveSheet()->getStyle('A1:' . $this->intToChr($headerRowCount) . (count($data) + 2))->applyFromArray($styleArray);

        //第二行起，每一行的值
        $row_key = $sort + 1;
        if (!$sheetConfig) {
            foreach ($data as $key) {
                foreach ($key as $val) {
                    $rowLetter = $this->intToChr($key);
                    $objSheet->setCellValue($rowLetter . $row_key, $val);
                }
                $row_key++;
            }
        } else {
            foreach ($data as $rowVal) {
                foreach ($headerRow as $key => $val) {
                    $rowLetter = $this->intToChr($key);
                    $objSheet->setCellValue($rowLetter . $row_key, $rowVal[$val['rowVal']]);
                }
                $row_key++;
            }
        }

        // $format只能为 Xlsx 或 Xls
        if ($format == 'Xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } elseif ($format == 'Xls') {
            header('Content-Type: application/vnd.ms-excel');
        }

        //直接输出文件
        header("Content-Disposition: attachment;filename=" . $fileName . '.' . strtolower($format));
        header('Cache-Control: max-age=0');
        $objWriter = IOFactory::createWriter($newExcel, $format);
        $objWriter->save('php://output');
        exit;
    }

    public function intToChr($num)
    {
        $data = [];
        for ($i = 0; $i <= 701; $i++) {
            $y = ($i / 26);
            if ($y >= 1) {
                $y = intval($y);
                $data[$i] = chr($y + 64) . chr($i - $y * 26 + 65);
            } else {
                $data[$i] = chr($i + 65);
            }
        }
        return $data[$num];
    }


}