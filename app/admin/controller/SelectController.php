<?php

namespace app\admin\controller;

use app\admin\model\basic\SelectModel;
use app\admin\model\shipping\ShippingCateModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SelectController extends ToolController
{
    protected $model;

    protected function initialize()
    {
        $this->model = new SelectModel();
        return parent::initialize();
    }

    public function get_params()
    {
        // $cate_list = cmf_table('news_cate')->select();
        // $this->assign('cate_list', $cate_list);
    }
    public function setWhere()
    {
        $params = $this->request->param();
        $where = [];


        if (!empty($params['name'])) {
            $where[] = ['name', 'like', '%' . trim($params['name']) . '%'];
        }
        $where[] = ['type', '=', $params['type']];
        $where[] = ['pid', '=', 0];

        //判断类型是否开启导入和子分类
        $import_arr = [1];
        $is_import = 0;
        if (in_array($params['type'], $import_arr)) {
            $is_import = 1;
        }
        $child_arr = [1];
        $is_child = 0;
        if (in_array($params['type'], $child_arr)) {
            $is_child = 1;
        }
        $this->assign('is_import', $is_import);
        $this->assign('is_child', $is_child);
        return $where;
    }


    public function index1()
    {
        return redirect((string)url('index', ['type' => 1]));
    }


    public function waste()//垃圾分类上报类型
    {
        return redirect((string)url('index', ['type' => 2]));
    }


//    public function index2()
//    {
//        return redirect((string)url('index', ['type' => 2]));
//    }

    public function index3()
    {

    }

    public function index4()
    {

    }

    //子分类列表
    public function cate_index()
    {
        $pid = $this->request->param('pid');
        $param = $this->request->param();

        $list = $this->model->where('pid', $pid)->paginate(10);
        $list->appends($param);
        return $this->fetch('cate_index', ['list' => $list, 'pid' => $pid]);
    }

//子分类添加编辑页面
    public function cate_update()
    {

        $pid = $this->request->param('pid');
        $id = $this->request->param('id');
        if (empty($id)) {
            return $this->fetch('cate_update', ['pid' => $pid]);
        } else {
            $item = $this->model->find($id);
            return $this->fetch('cate_update', ['item' => $item, 'pid' => $pid]);
        }

    }

    //子分类添加编辑提交
    public function cate_update_post()
    {
        $data = $this->request->param();


        $data['type'] = $this->model->where('id', $data['pid'])->value('type');
        if (empty($data['id'])) {
            $this->model->save($data);
            $this->success('添加成功');
        } else {
            $this->model->update($data, ['id' => $data['id']]);
            $this->success('修改成功');
        }

    }


    /**编辑提交
     * @return void
     */
    public function updatePost()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $params = $this->setParamData($params);

            if (empty($params['id'])) {
                $result = $this->setAddPost($params);
            } else {
                $result = $this->setEditPost($params);
            }
            $this->getMsg(!empty($result));
        } else {
            $this->error('非法请求');
        }
    }


    public function addMany()
    {
        $params = $this->request->param();
        $this->assign('params', $params);
        if ($this->request->isPost()) {

            $type = $this->request->param('type');
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
                // $highestColumn = $sheet->getHighestColumn();   // 取得总列数

                //定义$usersExits，循环表格的时候，找出已存在的用户。
                $usersExits = [];


                for ($j = 2; $j <= $highestRow; $j++) {
                    if (empty($objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue())) {
                        continue;
                    }
                    $data[$j - 2] = [
                        'pid' => trim($objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue()),
                        'name' => trim($objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue()),
                    ];
                    $data[$j - 2]['type'] = $type;
                    $data[$j - 2]['create_time'] = time();

                }

                $this->model->insertAll($data);

                $this->success('多级联运上传成功！');

            }
        } else {
            return $this->fetch();
        }
    }

}