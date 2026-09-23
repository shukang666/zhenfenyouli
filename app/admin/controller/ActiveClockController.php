<?php

namespace app\admin\controller;

use api\wxapp\model\AccountLogModel;
use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveClockModel;
use think\App;
use think\db\exception\DbException;
use think\facade\Db;

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
        return $this->renderList(0, 'index', '点位打卡');
    }

    /**
     * 签到记录：只显示扫码参加活动（join_type=2）的签到/打卡记录
     * @return mixed
     * @throws DbException
     */
    public function sign_record()
    {
        return $this->renderList(2, 'sign_record', '活动签到');
    }

    /**
     * 签到/打卡记录列表
     * @param int $join_type 0=不限；2=只查扫码参加的活动
     * @param string $tpl 模板名
     * @param string $filename 导出文件名前缀
     * @return mixed
     * @throws DbException
     */
    public function renderList($join_type, $tpl, $filename)
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
        // 只统计扫码参加（签到）活动的记录
        if ($join_type) {
            $where[] = ['c.join_type', '=', $join_type];
        }


        if ($export) {
            $this->outData($where, $filename);
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

        return $this->fetch($tpl);
    }

    /**
     * 打卡详情（含经验分享、打卡图）
     */
    public function detail()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $item = ActiveClockModel::alias('a')
            ->join('member b', 'a.user_id = b.id', 'left')
            ->join('active c', 'a.active_id = c.id', 'left')
            ->field('a.*,b.nickname,b.avatar,b.mobile as phone,c.name,c.thumb as active_thumb')
            ->where('a.id', $id)
            ->find();
        if (empty($item)) {
            $this->error('记录不存在');
        }
        $item['date_time_fmt'] = date('Y-m-d', $item['date_time']);
        $item['create_time_fmt'] = date('Y-m-d H:i:s', $item['create_time']);
        $from = $this->request->param('from', 'index', 'trim');
        if ($from !== 'sign_record') {
            $from = 'index';
        }
        $listUrl = $from === 'sign_record' ? url('sign_record') : url('index');
        $this->assign('from', $from);
        $this->assign('listUrl', (string)$listUrl);
        $this->assign('item', $item);
        return $this->fetch('detail');
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

    public function outData($where, $filename = '点位打卡')
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

        $filename = $filename . date('Y-m-d');
        $head = ['ID', '用户信息', '手机号', '活动名称', '打卡日期', '经验分享'];//设置表头
        $keys = ['id', 'nickname', 'phone', 'name', 'date_time', 'experience_share'];//设置表头
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

    /**批量删除
     * @return void
     */
    public function delete_all()
    {
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('请选择要删除的记录');
        }
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            $this->error('请选择要删除的记录');
        }

        Db::startTrans();
        try {
            $list = $this->model->where('id', 'in', $ids)->select();
            foreach ($list as $item) {
                //有赠送积分的记录，删除时同步扣除用户积分
                if (!empty($item['send_point']) && $item['send_point'] > 0) {
                    AccountLogModel::add_account_log($item['user_id'], 2, 226, -$item['send_point'], ['remark' => '社区活动打卡扣除']);
                }
            }
            $this->model->where('id', 'in', $ids)->delete();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('删除失败：' . $e->getMessage());
        }

        $this->success('删除成功');
    }
}