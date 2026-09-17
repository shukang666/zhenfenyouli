<?php

namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveCateModel;
use app\admin\model\active\ActiveCommunityCateModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\active\ActiveSIgnModel;
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

    /**
     * 列表默认展示的参加方式：1=小程序参加（活动列表） 2=扫码参加（签到列表）
     * @var int
     */
    protected $joinType = 1;

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
        if ($type !== '') {
            $where[] = ['type', '=', $type];
        }
        // 参加方式：1=小程序参加 2=扫码参加，两类活动分开管理，列表互不混显
        $join_type = $this->request->param('join_type', '', 'trim');
        if ($join_type === '') {
            $join_type = $this->joinType;
        }
        $where[] = ['join_type', '=', $join_type];

        // 发现七贤岭(id=6)是小程序"我和七贤岭"页固定栏目，非活动分类，不可作为活动的七贤岭分类选项
        $community_cate_list = ActiveCommunityCateModel::whereNotIn('id', [6])->order('list_order asc,id desc')->select();
        $this->assign('community_cate_list', $community_cate_list);
        $this->assign('cate_list', (new  ActiveCateModel())->select());
        return $where;
    }

    public function get_params()
    {
        // 发现七贤岭(id=6)是小程序"我和七贤岭"页固定栏目，非活动分类，不可作为活动的七贤岭分类选项
        $community_cate_list = ActiveCommunityCateModel::whereNotIn('id', [6])->order('list_order asc,id desc')->select();
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

    /**
     * 签到列表：扫码参加的活动（join_type=2），不限制活动类型
     * @return mixed
     * @throws DbException
     */
    public function sign_list()
    {
        // 固定只查扫码参加的活动
        $this->joinType = 2;

        $params = $this->request->param();
        $params['join_type'] = 2;

        $list = $this->setIndex()->each(function ($item) {
            $item = $this->getList($item);
            $item['sign_count'] = ActiveSIgnModel::where('active_id', $item['id'])->where('delete_time', 0)->count();
            return $item;
        });
        $this->assign('params', $params);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());

        return $this->fetch();
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
            // 添加模式也渲染 update.html，赋空 item 避免模板读取 $item.xxx 报"未定义变量"
            $this->assign('item', []);
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
            $params['is_month_recommend'] = empty($params['is_month_recommend']) ? 0 : 1;


            $params = $this->setParamData($params);
            $share_url = 'pages/low_carbon/lsxf/details-new';
            if (empty($params['id'])) {
                $result = ActiveModel::create($params);
                //生成小程序码
                $scene = (string)$result['id'];
                $qr_code = CommonModel::setShareImage($scene,'active_'.$result['id'],'dz1111',$share_url);
                $this->model->where('id',$result['id'])->update(['qr_code' => $qr_code]);
            } else {
                $item = ActiveModel::where('id',$params['id'])->find();
                if (empty($item['qr_code'])) {
                    //生成小程序码
                    $scene = (string)$params['id'];
                    $params['qr_code'] = CommonModel::setShareImage($scene,'active_'.$params['id'],'dz1111',$share_url);
                }
                $result = $this->setEditPost($params);
            }


            $this->getMsg(!empty($result));
        } else {
            $this->error('非法请求');
        }
    }
}