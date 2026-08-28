<?php

namespace app\admin\controller;

use app\admin\model\active\ActiveCateModel;
use app\admin\model\active\ActiveTabModel;
use think\App;

class ActiveTabController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new ActiveTabModel();
    }


    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');

        $where = [];
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        return $where;
    }


    //列表按排序字段升序，固定标签(list_order=0)永远排第一
    public function setIndex()
    {
        return $this->model->where($this->setWhere())->order('list_order asc,id asc')->paginate(15);
    }

    //编辑页分类下拉数据
    public function get_params()
    {
        $cate_list = ActiveCateModel::where('status', 1)->order('list_order asc,id desc')->select();
        $this->assign('cate_list', $cate_list);
    }

    //列表展示分类名
    public function setData($item, $type = 1)
    {
        if ($type == 2) {
            $item['cate_name'] = $item['cate_id'] > 0 ? ActiveCateModel::where('id', $item['cate_id'])->value('name') : '';
        }

        return $item;
    }

    //提交校验：普通标签必须绑定活动分类（固定标签表单不带cate_id）
    public function setParamData($params)
    {
        if (isset($params['cate_id']) && empty($params['cate_id'])) {
            $this->error('请选择绑定的活动分类');
        }
        return $params;
    }

    //小程序活动页标签最多4个
    public function setAddPost($params)
    {
        $total = $this->model->count();
        if ($total >= 4) {
            $this->error('标签最多配置4个');
        }
        return parent::setAddPost($params);
    }
}
