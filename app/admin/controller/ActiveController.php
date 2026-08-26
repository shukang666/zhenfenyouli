<?php

namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveCateModel;
use app\admin\model\active\ActiveCommunityCateModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\member\CommunityCateModel;
use think\App;
use think\db\exception\DbException;

class ActiveController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new ActiveModel();
    }



    public function setIndex()
    {

        return $this->model->where($this->setWhere())->order('list_order asc,id desc')->paginate(15);
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

    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');
        $type = $this->request->param('type', '', 'trim');

        $where = [];
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        $where[] = ['type', '=', $type];

        $community_cate_list = ActiveCommunityCateModel::where([])->order('list_order asc,id desc')->select();
        $this->assign('community_cate_list', $community_cate_list);
        $this->assign('cate_list', (new  ActiveCateModel())->select());
        return $where;
    }

    public function get_params()
    {
        $community_cate_list = ActiveCommunityCateModel::where([])->order('list_order asc,id desc')->select();
        $this->assign('community_cate_list', $community_cate_list);
        $this->assign('cate_list', (new  ActiveCateModel())->select());
    }

    public function index1()
    {
        return redirect((string)url('index', ['type' => 1]));
    }

    public function index2()
    {
        return redirect((string)url('index', ['type' => 2]));
    }

    public function index3()
    {
        return redirect((string)url('index', ['type' => 3]));
    }


    public function setData($item, $type = 1)
    {
        if ($type == 1) {
            if (!empty($item['date'])) $item['date'] = strtotime($item['date']);
        } else {
            if (!empty($item['date'])) $item['date'] = date('Y-m-d', $item['date']);
        }

        return $item;
    }

    //操作页面
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
        if (!empty($item['role'])) {
            $item['role'] = explode(',', $item['role']);
        }
        $item = $this->getParamData($item);
        $this->assign('item', $item);
        $badge_list = '';
        $this->assign('badge_list', $badge_list);

        return $this->fetch();
    }

    //操作功能
    public function updatePost()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if(isset($params['role'])) $params['role'] = implode(',',$params['role']);
            if(!empty($params['clock_start_time'])) $params['clock_start_time'] = strtotime($params['clock_start_time']);
            if(!empty($params['clock_end_time'])) $params['clock_end_time'] = strtotime($params['clock_end_time']);
            $params['is_clock'] = empty($params['is_clock']) ? 0 : 1;
            $params['is_badge'] = empty($params['is_badge']) ? 0 : 1;


            $params = $this->setParamData($params);
            $share_url = 'pages/low_carbon/lsxf/details-new';
            if (empty($params['id'])) {
                $result = ActiveModel::create($params);
                //生成小程序码
                $scene = (string)$result['id'];
                $qr_code = CommonModel::setShareImage($scene,'active','dz1111',$share_url);
                $this->model->where('id',$result['id'])->update(['qr_code' => $qr_code]);
            } else {
                $item = ActiveModel::where('id',$params['id'])->find();
                if (empty($item['qr_code'])) {
                    //生成小程序码
                    $scene = (string)$params['id'];
                    $params['qr_code'] = CommonModel::setShareImage($scene,'active','dz1111',$share_url);
                }
                $result = $this->setEditPost($params);
            }


            $this->getMsg(!empty($result));
        } else {
            $this->error('非法请求');
        }
    }
}