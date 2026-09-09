<?php

namespace app\admin\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveClockModel;
use think\App;
use think\db\exception\DbException;

class ActiveClockController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new ActiveClockModel();
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
            $where[] = ['a.date_time', '>=', strtotime($params['begintime'] . ' 00:00')];
        }
        if (!empty($params['endtime'])) {
            $where[] = ['a.date_time', '<=', strtotime($params['endtime'] . ' 23:59')];
        }


        if ($export) {
            $this->outData($where);
        }

        $list = ActiveClockModel::alias('a')
            ->join('member b', 'a.user_id = b.id','left')
            ->join('active c', 'a.active_id = c.id','left')
            ->field('a.*,b.nickname,b.avatar,b.mobile as phone,c.name,c.thumb as image')
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

        $list = ActiveClockModel::alias('a')
            ->join('member b', 'a.user_id = b.id','left')
            ->join('active c', 'a.active_id = c.id','left')
            ->field('a.*,b.nickname,b.avatar,b.mobile as phone,c.name,c.thumb as image')
            ->where($where)->order('id desc')->select()->each(function ($item){
                $item['date_time'] = date('Y-m-d', $item['date_time']);
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
            })->toArray();

        $filename = '点位打卡' . date('Y-m-d');
        $head = ['ID', '用户信息', '手机号', '活动名称', '打卡日期',];//设置表头
        $keys = ['id', 'nickname', 'phone', 'name', 'date_time',];//设置表头
        CommonModel::outData($filename, $list, $head, $keys);
        exit;
    }



    /**删除
     * @return void
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $item = $this->model->find($id);
        if($item['send_point'] > 0){
            $send_point = $item['send_point'];
            $user_id = $item['user_id'];
            //扣除积分
            AccountLogModel::add_account_log($user_id, 2, 226, -$send_point, ['remark' => '社区活动打卡扣除']);
        }

        $result = $this->model->find($id)->delete();
        $this->getMsg($result, '删除');
    }
}