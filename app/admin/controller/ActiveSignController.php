<?php

namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\active\ActiveSIgnModel;
use app\admin\model\member\MemberModel;
use think\App;
use think\db\exception\DbException;

class ActiveSignController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new ActiveSIgnModel();
    }

    /**
     * 列表
     * @return mixed
     * @throws DbException
     */
    public function index()
    {
        $export = $this->request->param('export');
        $params = $this->request->param();
        $where = [];
        if(!empty($params['keyword'])) $where[] = ['b.nickname|b.mobile', 'like', '%' . $params['keyword'] . '%'];
        if(!empty($params['name'])) $where[] = ['c.name', 'like', '%' . $params['name'] . '%'];
        if (!empty($params['begintime'])) {
            $where[] = ['a.create_time', '>=', strtotime($params['begintime'] . ' 00:00')];
        }
        if (!empty($params['endtime'])) {
            $where[] = ['a.create_time', '<=', strtotime($params['endtime'] . ' 23:59')];
        }


        if ($export) {
            $this->outData($where);
        }

        $list = ActiveSIgnModel::alias('a')
            ->join('member b', 'a.user_id = b.id','left')
            ->join('active c', 'a.active_id = c.id','left')
            ->field('a.*,b.nickname,b.avatar,b.mobile as phone,c.name')
            ->where($where)->order('id desc')->paginate(10)->each(function ($item){
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
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

        $where = [];
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        return $where;
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

    public function outData($where)
    {

        $list = ActiveSIgnModel::alias('a')
            ->join('member b', 'a.user_id = b.id','left')
            ->join('active c', 'a.active_id = c.id','left')
            ->field('a.*,b.nickname,b.avatar,b.mobile as phone,c.name')
            ->where($where)->order('id desc')->select()->each(function ($item){
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
            })->toArray();

        $image_array = [];
        foreach($list as &$item){

            if(!empty($item['avatar'])){
                $item['avatar'] = cmf_get_asset_url($item['avatar']);
                //蓝底照片
                $avatar = file_get_contents($item['avatar']);
                $value = "upload/file/" . md5(time() . rand(1,99999) ) . ".png";
                file_put_contents($value, $avatar);
                $item['avatar'] = $value;
                //本地图
                $image_array[] = $value;
            }
        }


        $filename = '报名列表' . date('Y-m-d');
        $head = ['ID', '用户头像', '用户信息', '手机号', '活动名称', '创建时间',];//设置表头
        $keys = ['id', 'avatar', 'nickname', 'phone', 'name', 'create_time',];//设置表头
        $types = ['1', '2', '1', '1', '1', '1',];//设置表头
        CommonModel::outData($filename, $list, $head, $keys, $types, $image_array);
        exit;
    }
}