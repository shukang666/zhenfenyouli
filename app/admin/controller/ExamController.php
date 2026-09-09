<?php
// +----------------------------------------------------------------------
// |考试管理
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use api\wxapp\controller\ExcelExporter;
use api\wxapp\model\CouponModel;
use app\admin\model\health\HealthHomeModel;
use app\admin\model\member\ReservationModel;
use cmf\controller\AdminBaseController;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\facade\Db;
use app\admin\model\ExamQuestionModel;


use think\facade\Cache;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Log;


error_reporting(0);

class ExamController extends AdminBaseController
{


    public function index()
    {

        $param = $this->request->param();
        $where = array();

        if (!empty($param['health_id'])) {
            $where[] = ['health_id', '=', $param['health_id']];
        } else {
            $param['health_id'] = 0;
        }

        $this->assign('health_id', $param['health_id']);
        if ($param['keyword']) {
            $where[] = ['name', 'like', "%" . trim($param['keyword']) . "%"];
        }
        $list = Db::name("exam")->where($where)->paginate(10)->each(function ($item, $key) {
            $item['cate_name'] = Db::name('health_home')->where('id', $item['cate_id'])->value('name');
            return $item;
        });
        $list->appends($param);

        $this->assign('list', $list);
        $this->assign('params', $param);
        $this->assign('page', $list->render());

        return $this->fetch();
    }


    public function add()
    {
        $health_id = $this->request->param('health_id');
        $this->assign('health_id', $health_id);
        $cate_list = Db::name('exam_cate')->select();
        $this->assign("cate_list", $cate_list);
        return $this->fetch();

    }


    public function addPost()
    {
        $data = $this->request->param();
        $health_id = $this->request->param('health_id');
        $this->assign('health_id', $health_id);
        $data['begintime'] = strtotime($data['begintime']);
        $data['endtime'] = strtotime($data['endtime']);

        $data['createtime'] = time();
        $data['updatetime'] = time();

        $option = $data['option'];
        $next = $data['next'];
        $list = [];
        foreach ($option as $key => $value) {
            if (!empty($value)) {
                $list[] = array(
                    'option' => $value,
                    'next' => $next[$key]
                );
            }

        }
        $data['option'] = json_encode($list, true);
        unset($data['next']);

        $result = Db::name("exam")->strict(false)->insertGetId($data);
        if ($result) {
            //$this->gen_qrcode($result);
            $this->success("添加成功！", url("exam/index"));
        } else {
            $this->error(Db::name("exam")->getError());
        }
    }


    public function edit()
    {
        $id = $this->request->param('id');
        $health_id = $this->request->param('health_id');
        $this->assign('health_id', $health_id);
        $result = Db::name("exam")->where("id", $id)->find();
        $result['content'] = cmf_replace_content_file_url(htmlspecialchars_decode($result['content']));
        $result['secret'] = cmf_replace_content_file_url(htmlspecialchars_decode($result['secret']));
        $result['option'] = json_decode($result['option'], true);
        if (!empty($result['image'])) $result['image'] = json_decode($result['image'], true);
        $this->assign('item', $result);
        $cate_list = Db::name('exam_cate')->select();
        $CouponModel = new CouponModel();
        $this->assign('type_list', $CouponModel->getDiscountType());
        $this->assign('cate_list', (new HealthHomeModel())->where('id', '>', 5)->select());
//        $this->assign("cate_list", $cate_list);
        return $this->fetch();
    }

    public function editPost()
    {
        $health_id = $this->request->param('health_id');
        $this->assign('health_id', $health_id);
        $data = $this->request->param();
        if ($data['cate_id'] != 6) {
            $data['is_exam'] = 1;
            $data['is_demand'] = 2;
        } else {
            $data['is_exam'] = 1;
            $data['is_demand'] = 2;
        }
        $data['begintime'] = strtotime($data['begintime']);
        $data['endtime'] = strtotime($data['endtime']);

        $data['updatetime'] = time();

        $option = $data['option'];
        $next = $data['next'];
        $list = [];
        foreach ($option as $key => $value) {
            $value = trim($value);
            if (!empty($value)) {
                $list[] = array(
                    'option' => $value,
                    'next' => $next[$key]
                );
            }
        }
        //file_put_contents('qq4.txt',print_r($list,true),FILE_APPEND);
        $data['option'] = json_encode($list, true);
        if (!empty($data['image'])) $data['image'] = json_encode($data['image'], true);
        unset($data['next']);
        if (empty($data['id'])) {
            $data['create_time'] = time();
            $result = Db::name("exam")->strict(false)->insert($data);
        } else {
            $result = Db::name("exam")->strict(false)->update($data);
        }

        if ($result) {
            //$this->gen_qrcode($data['id']);

            $this->success("保存成功！", url("exam/index"));
        } else {
            $this->error(Db::name("exam")->getError());
        }
    }


    public function reservation()
    {
        $param = $this->request->param();
        $ReservationModel = new ReservationModel();
        $item = $ReservationModel->where('id', $param['id'])->find();
        $this->assign('item', $item);
        return $this->fetch();
    }

    public function export_reservation()
    {
        $param = $this->request->param();
        $ReservationModel = new ReservationModel();
        $list = $ReservationModel->select();
        $name = '预约记录';
        $head = ['姓名', '手机号', '地区', '机构', '具体需求'];
        $keys = ['name', 'mobile', 'area', 'institution', 'demand'];
        $this->outdata($name, $list, $head, $keys);

    }


    public function export_to_csv($filename, $titleArr, $dataArr)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 0);
        ob_end_clean();
        ob_start();
        header("Content-Type: text/csv;charset=utf-8");
        header("Content-Disposition:filename=" . $filename);
        $fp = fopen('php://output', 'w');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, $titleArr);
        $index = 0;
        foreach ($dataArr as $item) {
// 			if($index==1000){
// 				$index=0;
// 				ob_flush();
// 				flush();
// 			}
// 			$index++;
            fputcsv($fp, $item);
        }
        ob_flush();
        flush();
        ob_end_clean();
    }

    public function gift_list()
    {

        $param = $this->request->param();
        $where = array();


        if ($param['keyword']) {
            $where[] = ['m.nickname|m.true_name|m.mobile|e.mobile', 'like', "%" . trim($param['keyword']) . "%"];
        }
        if (!empty($param['begintime'])) {
            $where[] = ['createtime', '>=', strtotime($param['begintime'] . ' 00:00')];
        }
        if (!empty($param['endtime'])) {
            $where[] = ['createtime', '<=', strtotime($param['endtime'] . ' 23:59')];
        }
        if (!empty($param['exam_id'])) {
            $where[] = ['exam_id', '=', $param['exam_id']];
        }
        if (!empty($param['id'])) {
            $where[] = ['e.id', '=', $param['id']];
        }

        if ($param['is_export']) {


            ini_set('memory_limit', '2048M');
            ini_set('max_execution_time', 0);
            $list = Db::name("exam_gift")->alias('e')
                ->leftjoin('member m', 'e.uid=m.id')
                ->field('e.createtime,e.uid,e.gift_name,e.gift_code,e.status,m.nickname,
m. nickname as username,name,e.mobile,e.id,m.openid,m.avatar')
                ->where($where)->order('e.createtime asc')->select()
                ->each(function ($item, $key) use (&$headerRow) {
                    $headerRow = [
                        ['rowName' => '创建时间', 'rowVal' => 'createtime', 'width' => 30],
                        ['rowName' => '姓名', 'rowVal' => 'name', 'width' => 30],
                        ['rowName' => '手机号', 'rowVal' => 'mobile', 'width' => 30],
                        ['rowName' => '微信openid', 'rowVal' => 'openid', 'width' => 30],
                        ['rowName' => '昵称', 'rowVal' => 'username', 'width' => 30],
                        ['rowName' => '头像', 'rowVal' => 'avatar', 'width' => 30],
                    ];
                    if (empty($item['name'])) {
                        $item['name'] = $item['nickname'];
                    }
                    $item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
//                      $item['username'] = $item['name'] ;
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

                        if ($question['type'] == 3) {//填空
                            $sn = $key;//第几题的第几个选项
                            $headerRow[] = ['rowName' => '填空', 'rowVal' => 'answer' . $sn, 'width' => 30];
                            $item['answer' . $sn] = $question['select_option'];//只默认为空

                        } else {
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


                    }
                    return $item;
                });

//            dump($list);exit;

            $exporter = new ExcelExporter();
            $exporter->excelExport($list->toArray(), $headerRow, ['fileName' => '问卷记录']);
//              $this->excelExport($list, $headerRow, ['fileName' => '问卷记录']);
            die();


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
            $exporter = new ExcelExporter();
            $exporter->excelExport($list, $headerRow, ['fileName' => '问卷记录']);
//              $this->excelExport($list, $headerRow, ['fileName' => '问卷记录']);
            die();
        }


        $list = Db::name("exam_gift")->alias('e')
            ->join('member m', 'e.uid=m.id')
            ->field('e.*,m.nickname,m.mobile,m. true_name username,m.sex')
            ->order('e.id desc')
            ->where($where)->order('e.createtime asc')->paginate(10)->each(function ($item, $key) {
                $item['status_name'] = ['未兑换', '已兑换'][$item['status']];
                if (empty($item['name'])) {
                    $item['name'] = $item['nickname'];
                }
                $item['cate_name'] = Db::name("exam")->where('id', $item['exam_id'])->value('name');
                return $item;

            });
        $list->appends($param);

        $this->assign('list', $list);
        $this->assign('params', $param);
        $this->assign('page', $list->render());

        $exam_list = Db::name("exam")->select();
        $this->assign('exam_list', $exam_list);
        return $this->fetch();
    }

    public function record_list()
    {

        $param = $this->request->param();
        $where['e.gift_id'] = $param['id'];

        $list = Db::name("exam_record")->alias('e')
            ->join('exam_question m', 'e.exam_id=m.exam_id and e.question_id=m.id')
            ->field('e.*,m.question,m.option,m.type')->where($where)
            ->select()->each(function ($item, $key) {
                $answer = [];
                if ($item['type'] == 3) {
                    array_push($answer, $item['select_option']);
                } else {
                    $select_option = explode(',', $item['select_option']);
                    $options = json_decode($item['option'], true);
                    foreach ($options as $key => $option) {
                        if (in_array($key + 1, $select_option)) {
                            array_push($answer, $option['option']);
                        }
                    }

                }

                $item['answer'] = implode(',', $answer);
                if (!empty($item['hospital'])) {
                    $item['answer'] = $item['answer'] . ',' . $item['hospital'];
                }
                return $item;
            });

        $this->assign('list', $list);

        return $this->fetch();
    }

    public function delete_gift()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result = Db::name("exam_gift")->where("id", $id)->delete();
        if ($result) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败");
        }
    }


    /**
     * 更新核销状态
     */
    public function update_status()
    {
        $param = $this->request->param();
        $result = Db::name("exam_gift")->where('id', $param['id'])->update(['status' => 1, 'updatetime' => time()]);
        if ($result) {
            $this->success("处理成功！");
        } else {
            $this->error('处理失败');
        }
    }

    /**
     * 使用PHPEXECL导入
     *
     * @param string $file 文件地址
     * @param int $sheet 工作表sheet(传0则获取第一个sheet)
     * @param int $columnCnt 列数(传0则自动获取最大列)
     * @param array $options 操作选项
     *                          array mergeCells 合并单元格数组
     *                          array formula    公式数组
     *                          array format     单元格格式数组
     *
     * @return array
     * @throws Exception
     */
    function importExecl($file = '', $sheet = 0, $columnCnt = 0, &$options = [])
    {
        try {
            /* 转码 */
            $file = iconv("utf-8", "gb2312", $file);
            if (empty($file) or !file_exists($file)) {
                throw new \Exception('文件不存在!');
            }
            /** @var Xlsx $objRead */
            $objRead = IOFactory::createReader('Xlsx');
            if (!$objRead->canRead($file)) {
                /** @var Xls $objRead */
                $objRead = IOFactory::createReader('Xls');
                if (!$objRead->canRead($file)) {
                    throw new \Exception('只支持导入Excel文件！');
                }
            }
            /* 如果不需要获取特殊操作，则只读内容，可以大幅度提升读取Excel效率 */
            empty($options) && $objRead->setReadDataOnly(true);
            /* 建立excel对象 */
            $obj = $objRead->load($file);
            /* 获取指定的sheet表 */
            $currSheet = $obj->getSheet($sheet);
            if (isset($options['mergeCells'])) {
                /* 读取合并行列 */
                $options['mergeCells'] = $currSheet->getMergeCells();
            }
            if (0 == $columnCnt) {
                /* 取得最大的列号 */
                $columnH = $currSheet->getHighestColumn();
                /* 兼容原逻辑，循环时使用的是小于等于 */
                $columnCnt = Coordinate::columnIndexFromString($columnH);
            }
            /* 获取总行数 */
            $rowCnt = $currSheet->getHighestRow();
            $data = [];
            /* 读取内容 */
            for ($_row = 1; $_row <= $rowCnt; $_row++) {
                $isNull = true;
                for ($_column = 1; $_column <= $columnCnt; $_column++) {
                    $cellName = Coordinate::stringFromColumnIndex($_column);
                    $cellId = $cellName . $_row;
                    $cell = $currSheet->getCell($cellId);
                    // if (isset($options['format'])) {
                    // 	/* 获取格式 */
                    // 	$format = $cell->getStyle()->getNumberFormat()->getFormatCode();
                    // 	/* 记录格式 */
                    // 	$options['format'][$_row][$cellName] = $format;
                    // }
                    // if (isset($options['formula'])) {
                    // 	/* 获取公式，公式均为=号开头数据 */
                    // 	$formula = $currSheet->getCell($cellId)->getValue();
                    // 	if (0 === strpos($formula, '=')) {
                    // 		$options['formula'][$cellName . $_row] = $formula;
                    // 	}
                    // }
                    // if (isset($format) && 'm/d/yyyy' == $format) {
                    // 	/* 日期格式翻转处理 */
                    // 	$cell->getStyle()->getNumberFormat()->setFormatCode('yyyy/mm/dd');
                    // }
                    $data[$_row][$cellName] = trim($currSheet->getCell($cellId)->getFormattedValue());
                    if (!empty($data[$_row][$cellName])) {
                        $isNull = false;
                    }
                }
                /* 判断是否整行数据为空，是的话删除该行数据 */
                if ($isNull) {
                    unset($data[$_row]);
                }
            }
            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result = Db::name("exam")->where("id", $id)->delete();
        if ($result) {
            $this->success("添加成功！", url("exam/index"));
        } else {
            $this->error(Db::name("exam")->getError());
        }
    }


    /**
     * 答题结果
     */
    public function exam_result()
    {
        $param = $this->request->param();
        $where = array();


        if ($param['keyword']) {
            $where[] = ['name', 'like', "%" . trim($param['keyword']) . "%"];
        }
        $list = Db::name("exam_result")->where($where)->paginate(10);
        $list->appends($param);

        $this->assign('list', $list);
        $this->assign('params', $param);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    /**
     * 题库管理
     */
    public function question_list()
    {
        $param = $this->request->param();
        $where = array();
        $where[] = ['exam_id', '=', $param['exam_id']];

        if ($param['keyword']) {
            $where[] = ['question', 'like', "%" . trim($param['keyword']) . "%"];
        }
        $list = Db::name("exam_question")->where($where)->order('list_order asc')->paginate(10);
        $list->appends($param);

        $this->assign('list', $list);
        $this->assign('params', $param);
        $this->assign('exam_id', $param['exam_id']);

        $this->assign('page', $list->render());
        return $this->fetch();
    }

    /**
     * 添加题目
     */
    public function add_question()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['option']) && $data['type'] != 3) {
                $this->error("选项不能为空！");
            }
            $option = $data['option'];
            $option_type = $data['option_type'];
            $next = $data['next'];
            $list = [];
            foreach ($option as $key => $value) {
                $value = trim($value);
                if (!empty($value)) {
                    $list[] = array(
                        'id' => $key + 1,
                        'option' => $value,
                        'next' => $next[$key],
                        'option_type' => $option_type[$key]
                    );
                }
            }
            $data['option'] = json_encode($list, 256);
            unset($data['next']);
            unset($data['option_type']);

            $data['createtime'] = time();
            $data['updatetime'] = time();
            $result = Db::name("exam_question")->strict(false)->insert($data);
            if ($result) {
                $this->success("添加成功！", url("exam/question_list", array("exam_id" => $data['exam_id'])));
            } else {
                $this->error(Db::name("exam_question")->getError());
            }
        }


        $param = $this->request->param();
        $this->assign('exam_id', $param['exam_id']);

        return $this->fetch();
    }

    /**
     * 编辑题目
     */
    public function edit_question()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['option']) && $data['type'] != 3) {
                $this->error("选项不能为空！");
            }
//            var_dump($data['type']);exit;
//            file_put_contents('qq1.txt', print_r($data, true), FILE_APPEND);
            $option = $data['option'];
            $option_type = $data['option_type'];
            $next = $data['next'];
            $list = [];
            foreach ($option as $key => $value) {
                $value = trim($value);
                if (!empty($value)) {
                    $list[] = array(
                        'id' => $key + 1,
                        'option' => $value,
                        'next' => $next[$key],
                        'option_type' => $option_type[$key]
                    );
                }
            }
            unset($data['next']);
            unset($data['option_type']);

            $data['option'] = json_encode($list, 256);

            $data['updatetime'] = time();
            // file_put_contents('qq1.txt',print_r($data,true),FILE_APPEND);
            $result = Db::name("exam_question")->update($data);
            if ($result) {
                $this->success("保存成功！", url("exam/question_list", array("exam_id" => $data['exam_id'])));
            } else {
                $this->error(Db::name("exam")->getError());
            }
        }

        $id = $this->request->param('id', 0, 'intval');
        $result = Db::name("exam_question")->where("id", $id)->find();
        //$result['option'] = explode(",", $result['option']);
        $result['option'] = json_decode($result['option'], true);
//         var_dump($result['option']);
        $this->assign('exam_id', $result['exam_id']);
        $this->assign('option', $result['option']);
        $this->assign('item', $result);
        return $this->fetch();
    }

    /**
     * 删除题目
     */
    public function delete_question()
    {
        $id = $this->request->param('id', 0, 'intval');
        $exam_id = $this->request->param('exam_id', 0, 'intval');
        $result = Db::name("exam_question")->where("id", $id)->delete();
        if ($result) {
            $this->success("删除成功！", url("exam/question_list", array("exam_id" => $exam_id)));
        } else {
            $this->error(Db::name("exam")->getError());
        }
    }

    public function listOrder()
    {
        $examQuestionModel = Db::name('exam_question');
        parent::listOrders($examQuestionModel);
        $this->success("排序更新成功！");
    }


    /**
     * 分类列表
     */
    public function cate_list()
    {
        $list = Db::name("exam_cate")->select();
        $this->assign("list", $list);
        return $this->fetch();
    }

    /**
     * 新增分类
     */
    public function add_cate()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['updatetime'] = time();
            $data['createtime'] = time();
            $result = Db::name("exam_cate")->insert($data);
            if ($result) {
                $this->success("保存成功！", url("exam/cate_list"));
            } else {
                $this->error("保存失败，请稍后再试");
            }
        }
        return $this->fetch();
    }

    /**
     * 编辑分类
     */
    public function edit_cate()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['updatetime'] = time();
            $result = Db::name("exam_cate")->update($data);
            if ($result) {
                $this->success("保存成功！", url("exam/cate_list"));
            } else {
                $this->error("保存失败，请稍后再试");
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        $result = Db::name("exam_cate")->where("id", $id)->find();
        $this->assign('exam_id', $result['exam_id']);
        $this->assign('item', $result);
        return $this->fetch();
    }

    /**
     * 删除分类
     */
    public function delete_cate()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result = Db::name("exam_cate")->where("id", $id)->delete();
        if ($result) {
            $this->success("删除成功！", url("exam/cate_list"));
        } else {
            $this->error(Db::name("exam")->getError());
        }
    }


    public function gen_qrcode($id)
    {
        // $data = $this->request->param();
        // if(empty($data['goods_id'])){
        //     $this->error("样衣id不能为空!");
        // }
        $exam = Db::name("exam")->where('id', $id)->find();
        $data['id'] = $id;
        $filename = time();
        $path = "upload/qrcode/" . $exam['id'] . "_" . $filename . ".jpg";
        $rel_path = "qrcode/" . $exam['id'] . "_" . $filename . ".jpg";
        if (!file_exists($path)) {
            $wxappSettings = cmf_get_option('wxapp_settings');
            $defaultWxapp = $wxappSettings['default'];
            $appId = $defaultWxapp['app_id'];
            $appSecret = $defaultWxapp['app_secret'];
            //先获取access_token
            $accesstoken = Cache::get('wxapp_accesstoken');
            if (empty($accesstoken)) {
                //https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET

                $response = cmf_curl_get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret");
                $response = json_decode($response, true);
                $accesstoken = $response['access_token'];
                Cache::set('wxapp_accesstoken', $response['access_token'], $response['expires_in']);
            }
            //调用二维码接口
            $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$accesstoken";
            $params = array(
                "scene" => $id,
                "page" => "pages/index/detail"
            );
            $result = cmf_curl_post($url, $params);
            file_put_contents($path, $result);
        }

        $url = cmf_get_asset_url($rel_path);
        Db::name("exam")->where("id", $id)->update(array("qrcode_url" => $rel_path, "updatetime" => time()));
        // echo $url;
        // $this->success("请求成功！",array('url'=>$url));
    }


    /**
     * 导出excel表
     * $data：要导出excel表的数据，接受一个二维数组
     * $name：excel表的表名
     * $head：excel表的表头，接受一个一维数组
     * $key：$data中对应表头的键的数组，接受一个一维数组
     * 备注：此函数缺点是，表头（对应列数）不能超过26；
     *循环不够灵活，一个单元格中不方便存放两个数据库字段的值
     */
    public function outdata($name = '测试表', $data = [], $head = [], $keys = [])
    {
        $count = count($head);  //计算表头数量
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        for ($i = 65; $i < $count + 65; $i++) {
            //数字转字母从65开始，循环设置表头：
            $sheet->setCellValue(strtoupper(chr($i)) . '1', $head[$i - 65]);
        }
        /*--------------开始从数据库提取信息插入Excel表中------------------*/
        foreach ($data as $key => $item) {
            //循环设置单元格：
            //$key+2,因为第一行是表头，所以写到表格时   从第二行开始写
            for ($i = 65; $i < $count + 65; $i++) {
                //数字转字母从65开始：
                $sheet->setCellValue(strtoupper(chr($i)) . ($key + 2), $item[$keys[$i - 65]]);
                // $spreadsheet->getActiveSheet()->getColumnDimension(strtoupper(chr($i)))->setWidth(20); //固定列宽
            }
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');//删除清空：
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
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


    public function delete_all()
    {
        $ids = $this->request->param('ids');
        Db::name('exam_gift')->whereIn('id', $ids)->delete();
        $this->success('删除成功');
    }
    //修改状态
    public function setStatus()
    {
        $params = $this->request->param();
        $status[$params['type']] = $params['status'];

       Db::name('exam')->where(['id' => $params['id']])->update($status);
        $this->success('设置成功');
    }

}