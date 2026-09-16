<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 督导员申请审核
 * 在垃圾分类活动菜单下
 */
class CheckController extends AdminBaseController
{
    protected $model;

    protected function initialize()
    {
        parent::initialize();
        $this->model = Db::name('check');
    }

    /**
     * 申请列表
     */
    public function index()
    {
        $params = $this->request->param();
        $where = [];

        $status = $this->request->param('status', '');
        if ($status !== '') {
            $where[] = ['c.status', '=', intval($status)];
        }

        $roleType = $this->request->param('role_type', '');
        if ($roleType !== '') {
            $where[] = ['c.role_type', '=', intval($roleType)];
        }

        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['c.name|m.nickname|m.mobile', 'like', '%' . trim($keyword) . '%'];
        }

        $list = $this->model->alias('c')
            ->join('cmf_member m', 'm.id = c.user_id', 'LEFT')
            ->join('cmf_community_parent cp', 'cp.id = c.community_id', 'LEFT')
            ->field('c.*, m.nickname, m.mobile as user_mobile, cp.name as community_name')
            ->where($where)
            ->order('c.id desc')
            ->paginate(15);

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('status', $status);
        $this->assign('role_type', $roleType);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 审核通过
     */
    public function pass()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }

        $row = $this->model->where('id', $id)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        if ($row['status'] != 1) {
            $this->error('该记录已审核');
        }

        $this->model->where('id', $id)->update([
            'status' => 2,
            'msg' => '',
            'update_time' => time(),
        ]);

        $this->success('已通过');
    }

    /**
     * 审核拒绝
     */
    public function reject()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }

        $msg = $this->request->param('msg', '');
        $row = $this->model->where('id', $id)->find();
        if (empty($row)) {
            $this->error('记录不存在');
        }
        if ($row['status'] != 1) {
            $this->error('该记录已审核');
        }

        $this->model->where('id', $id)->update([
            'status' => 3,
            'msg' => $msg,
            'update_time' => time(),
        ]);

        $this->success('已拒绝');
    }

    /**
     * 删除
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $this->model->where('id', $id)->delete();
        $this->success('删除成功');
    }
}
