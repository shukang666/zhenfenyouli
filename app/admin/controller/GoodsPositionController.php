<?php

namespace app\admin\controller;

use api\wxapp\model\MemberModel;
use app\admin\model\goods\GoodsPositionModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\App;

class GoodsPositionController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new GoodsPositionModel();
    }
    

    public function get_params()
    {
        
    }

    public function addMany()
    {

        if ($this->request->isPost()) {
            //获取表格的大小，限制上传表格的大小5M
            $file_size = $_FILES['myfile']['size'];
            if ($file_size > 5 * 1024 * 1024) {
                $this->error('文件大小不能超过5M');
                exit();
            }

            //限制上传表格类型
            $fileExtendName = substr(strrchr($_FILES['myfile']["name"], '.'), 1);
            //application/vnd.ms-excel  为xls文件类型
            if ($fileExtendName != 'xls' && $fileExtendName != 'xlsx') {
                $this->error('必须为excel表格');
                exit();
            }

            if (is_uploaded_file($_FILES['myfile']['tmp_name'])) {
                // 有Xls和Xlsx格式两种
                $objReader = IOFactory::createReader('Xlsx');

                $filename = $_FILES['myfile']['tmp_name'];
                $objPHPExcel = $objReader->load($filename);  //$filename可以是上传的表格，或者是指定的表格
                $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
                $highestRow = $sheet->getHighestRow();       // 取得总行数
                $data_i = [];

                $keys = ['A' => 'name', 'B' => 'address',];
                //循环读取excel表格，整合成数组。如果是不指定key的二维，就用$data[i][j]表示。
                for ($j = 2; $j <= $highestRow; $j++) {
                    foreach ($keys as $k => $item) {
                        $data_i[$j][$item] = trim($objPHPExcel->getActiveSheet()->getCell($k . $j)->getValue());
                    }
                    if (empty($data_i[$j]['address'])) {
                        $this->error($k . $j . '存在数据为空');
                    }

                    $item =  $this->model->where(['name' => $data_i[$j]['name']])->find();
                    if (empty($item)) {
                        $data_i[$j]['create_time'] = time();
                    } else {
                        $this->error($data_i[$j]['name'] . '已存在');
                    }


                }

                $this->model->insertAll($data_i);
                $this->success('导入成功！', url("index"));

            }
        } else {
            return $this->fetch();
        }


    }

}