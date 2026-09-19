<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 随手拍审核
 * 在垃圾分类活动菜单下
 */
class ShotReportController extends AdminBaseController
{
    protected $model;

    protected function initialize()
    {
        $this->model = Db::name('active_shot_report');
    }

    /**
     * 随手拍审核列表
     */
    public function index()
    {
        $params = $this->request->param();
        $where = [];

        // 状态筛选
        $status = $this->request->param('status', '');
        if ($status !== '') {
            $where[] = ['r.status', '=', intval($status)];
        }

        // 标题搜索（备注）
        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['r.remark', 'like', '%' . trim($keyword) . '%'];
        }

        // 问题类型
        $locationType = $this->request->param('location_type', '');
        if ($locationType !== '') {
            $where[] = ['r.location_type', '=', intval($locationType)];
        }

        $list = $this->model->alias('r')
            ->join('cmf_member m', 'm.id = r.user_id', 'LEFT')
            ->field('r.*, m.nickname, m.mobile')
            ->where($where)
            ->order('r.id desc')
            ->paginate(15);

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('status', $status);
        $this->assign('keyword', $keyword);
        $this->assign('location_type', $locationType);
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

        $report = $this->model->where('id', $id)->find();
        if (empty($report)) {
            $this->error('记录不存在');
        }

        if ($report['status'] != 0) {
            $this->error('该记录已审核');
        }

        // 更新审核状态
        $this->model->where('id', $id)->update([
            'status' => 1,
            'audit_time' => time()
        ]);

        VolunteerTaskController::createFromShotReport($report);

        $this->success('审核已通过，已发布到整改任务');
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

        $report = $this->model->where('id', $id)->find();
        if (empty($report)) {
            $this->error('记录不存在');
        }

        if ($report['status'] != 0) {
            $this->error('该记录已审核');
        }

        $this->model->where('id', $id)->update([
            'status' => 2,
            'audit_time' => time()
        ]);

        $this->success('已拒绝');
    }
}
