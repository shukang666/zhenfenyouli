<?php

namespace app\admin\controller;

use api\wxapp\model\AreaModel;
use api\wxapp\model\ProblemModel;
use app\admin\model\consultant\ProblemCateModel;
use cmf\controller\AdminBaseController;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;

class ToolController extends AdminBaseController
{


    public function __construct(App $app)
    {
        parent::__construct($app);

    }


    public function setWhere_test()
    {
        $name = $this->request->param('name', '', 'trim');
        $status = $this->request->param('status', '', 'trim');
        $start_time = $this->request->param('start_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');

        $where = [];
        if ($name) {
            $where[] = ['order_num|name|mobile', 'like', '%' . $name . '%'];
        }
        if (!empty($status)) {
            if ($status == 10) {
                $where[] = ['status', 'in', [10, 16]];
            } elseif ($status == 2) {
                $where[] = ['status', 'in', [2, 14]];
            } else {
                $where[] = ['status', '=', $status];
            }
        }
        if ($start_time) $where[] = ['create_time', '>=', strtotime($start_time)];
        if ($end_time) $where[] = ['create_time', '<=', strtotime($end_time) + 86399];

        $this->assign('status', $status);
        return $where;
    }

    /**
     * 列表的条件方法
     */
    public function setWhere()
    {
        $params = $this->request->param();
        $where = [];


        // 遍历参数数组 模糊匹配
        foreach ($params as $key => $value) {
            if ($key != 'page') {
                if (!empty($value)) {
                    $where[] = [$key, 'like', '%' . trim($value) . '%'];
                }
            }

        }

        return $where;
    }


    /**
     * 列表
     * @return mixed
     * @throws DbException
     */
    public function index()
    {
        $params = $this->request->param();
        $list = $this->setIndex()->each(function ($item) {
            return $this->getList($item);
        });
//        $list = $this->getParamData($list);
        $this->assign('params', $params);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());

        return $this->fetch();
    }

    // 渲染列表
    public function getList($item)
    {
        return $this->setData($item, 2);
    }
    public function get_params()
    {
        // $cate_list = cmf_table('news_cate')->select();
        // $this->assign('cate_list', $cate_list);
    }

    /**编辑页面
     * @return mixed
     */
    public function update()
    {
        $params = $this->request->param();
        
        $this->get_params();

        $this->assign('params', $params);
        $this->outParam();
        if (empty($params['id'])) {
            return $this->fetch();
        }
        $item = $this->setEdit($params);
        if (!empty($item['content'])) {
            $item['content'] = htmlspecialchars_decode($item['content']);
        }
        $item = $this->getParamData($item);
        $this->assign('item', $item);

        return $this->fetch();
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


    /**删除
     * @return void
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result = $this->model->find($id)->delete();
        $this->getMsg($result, '删除');
    }


    //子分类列表
    public function cate_index()
    {
        $pid = $this->request->param('pid');
        $list = $this->model->where('pid', $pid)->paginate(10);
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
        if (empty($data['id'])) {
            $this->model->save($data);
            $this->success('添加成功');
        } else {
            $this->model->update($data, ['id' => $data['id']]);
            $this->success('修改成功');
        }

    }


    //添加数据格式化 查询数据格式化
    public function setData($item, $type = 1)
    {
        if ($type == 1) {

        } else {

        }

        return $item;
    }

    //添加数据格式化
    public function getParamData($item)
    {
        return $this->setData($item, 2);
    }

    //展示数据格式化
    public function setParamData($item)
    {
        return $this->setData($item, 1);
    }

    //排序
    public function listOrder()
    {
        parent::listOrders($this->model);
        $this->success("排序更新成功！");
    }

    //修改状态
    public function setStatus()
    {
        $params = $this->request->param();
        $status[$params['type']] = $params['status'];
        $this->model->where(['id' => $params['id']])->update($status);
        $this->success('设置成功');
    }

    //审核
    public function fix_status()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $msg = $this->request->param('text');
        if ($status == 1) {
            $save = ['status' => 2];
            $this->model->where('id', $id)->save($save);
            $this->success('审核通过！');
        } else {
            $save = ['msg' => $msg, 'status' => 3];
            $this->model->where('id', $id)->save($save);
            $this->success('保存成功！');
        }

    }


    /**
     * Notes:
     * User: bian
     * Date: 2023/12/16
     * Time:17:39
     * @return mixed
     */
    public function setIndex()
    {

        return $this->model->where($this->setWhere())->order('id desc')->paginate(15);
    }

    public function setAddPost($params)
    {
        return $this->model->save($params);
    }

    public function setEdit($params)
    {
        return $this->model->find($params['id']);
    }

    public function outParam()
    {
        return '';
    }

    public function firstDate()
    {
        return '';
    }

    public function setEditPost($params)
    {
        return $this->model->find($params['id'])->save($params);
    }


    /**
     * Notes:
     * User: 0
     * Date: 2023/12/16
     * Time:17:32
     * @param $result bool
     * @param $msg 删除or保存
     *  *
     */
    public function getMsg(bool $result, $msg = '保存')
    {
        if ($result) {
            $this->success($msg . '成功！');
        } else {
            $this->error($msg . '失败！');
        }
    }


    /**查询页面
     * @return mixed
     */
    public function add()
    {
        $params = $this->request->param();
        $this->assign('params', $params);
        $this->outParam();
        return $this->fetch();
    }

    /**添加提交
     * @return void
     */
    public function addPost()
    {
        if ($this->request->isPost()) {
            $params = $this->request->param();
            if (!empty($params['content'])) {
                $params['content'] = htmlspecialchars_decode($params['content']);
            }
            $result = $this->setAddPost($params);
            $this->getMsg(!empty($result));
        } else {
            $this->error('非法请求');
        }
    }


    /**编辑页面
     * @return mixed
     */
    public function edit()
    {
        $params = $this->request->param();

        if (empty($params['id'])) {
            $this->error('id不能为空');
        }
        if (!empty($item['content'])) $item['content'] = cmf_replace_content_file_url(htmlspecialchars_decode($item['content']));
        $item = $this->setEdit($params);
        $this->assign('item', $item);
        $this->assign('params', $params);
        $this->outParam();
        return $this->fetch();
    }

    /**编辑提交
     * @return void
     */
    public function editPost()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();

            if (empty($params['id'])) {
                $this->error('id不能为空');
            }
            if (!empty($params['content'])) {
                $params['content'] = htmlspecialchars_decode($params['content']);
            }
            $result = $this->setEditPost($params);
            $this->getMsg(!empty($result));
        } else {
            $this->error('非法请求');
        }
    }


    public function selected()
    {
        $Model = $this->model;
        $cateModel = $this->cate_model;
        $params = $this->request->param();

        if ($this->request->isPost()) {
            $list = $params['data'] ?? [];
            $data = [];
            $res = $cateModel->where('school_id', $params['id'])->delete();
            if (!empty($params['data'])) {
                foreach ($list as $value) {
                    $data[] = [
                        'problem_id' => $params['id'],
                        'cate_id' => $value['value'],
                        'create_time' => time()
                    ];
                }
                $cateModel->insertAll($data);
            }

            $this->success('保存成功！');
        } else {

            $categories = $Model->field(' name,id')->where('status', 1)->select();
            $major_id = $cateModel->where('problem_id', $params['id'])->column('cate_id');
            $ids = json_encode($major_id);
            return $this->fetch('selected', ['categories' => $categories, 'ids' => $ids, 'id' => $params['id']]);
        }

    }


    //获取省市区选择的数据
    public function getJsonAreaList()
    {
        $pid = $this->request->param('pid',0);
        $map = [];
        $AreaModel = new AreaModel();
        if($pid != 0) {
            $pid = $AreaModel->where(['code' => $pid])->value('id');
        }
        $map['pid'] = $pid;
        $result = $AreaModel->field('id,name,code')->where($map)->select();

        $this->success('', '', $result);
    }







    //条件
    public function setWhere1()
    {
        $params = $this->request->param();
        $where = [];


        if (!empty($params['name'])) {
            $where[] = ['b.true_name', 'like', '%' . trim($params['name']) . '%'];
        }

        if (!empty($params['type'])) {
            $where[] = ['a.type', '=', $params['type']];
        }else{
            $params['type'] = 0;
        }

        if (!empty($params['status'])) {
            $where[] = ['a.status', '=', $params['status']];
        } else {
            $params['status'] = 0;
        }

        $this->assign('status', $params['status']);
        $this->assign('type', $params['type']);

        $enter_type = $this->request->param('enter_type', '');
        $material_type = $this->request->param('material_type', '');
        if ($enter_type) {
            $where[] = ['a.enter_type', '=', $enter_type];
        }
        if ($material_type) {
            $where[] = ['a.type', '=', $material_type];
        }

        return $where;
    }
    //多级联动
    public function getCateList()
    {
        $pid = $this->request->param('pid');
        $type = $this->request->param('type');
        $map = [];

        if ($type == 'second_cate') {
            $map['material_type'] = $pid;
            $model = new EnterModel();
            $result = $model->where($map)->field('id,name cate_name')->order('id')->select();
        } else {
            $model = new SelectModel();
            $result = $model->where($map)->where('type=1')->field('id,name cate_name')->select();
        }

        $this->success('', '', $result);
    }



    /**
     * 地址转换为坐标
     */
    public function search_address(){
        $address = $this->request->param('address');
        $key = config('wxapp.tx_map_key');
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?key=".$key."&address=".$address;
        $result = file_get_contents($url);
        exit($result);
    }

    /**
     * 坐标转换地址
     */
    public function reverse_address(){
        $lnglat = $this->request->param('lnglat');
        $key = config('wxapp.tx_map_key');
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?location=".$lnglat."&key=".$key."&get_poi=1";

        $result = file_get_contents($url);
        exit($result);
    }


}