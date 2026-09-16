<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 志愿督导记录
 * 在垃圾分类活动菜单下
 */
class VolunteerSupervisionController extends AdminBaseController
{
    protected $model;

    protected function initialize()
    {
        parent::initialize();
        $this->model = Db::name('volunteer_supervision');
    }

    /**
     * 志愿督导记录列表
     */
    public function index()
    {
        $params = $this->request->param();
        $where = [];

        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['m.nickname|m.mobile|cp.name|cc.name', 'like', '%' . trim($keyword) . '%'];
        }

        $date_start = $this->request->param('date_start', '');
        $date_end   = $this->request->param('date_end', '');
        if ($date_start) {
            $where[] = ['vs.supervision_date', '>=', $date_start];
        }
        if ($date_end) {
            $where[] = ['vs.supervision_date', '<=', $date_end];
        }

        $list = $this->model->alias('vs')
            ->join('cmf_member m', 'm.id = vs.user_id', 'LEFT')
            ->join('cmf_community_parent cp', 'cp.id = vs.community_id', 'LEFT')
            ->join('cmf_community_cate cc', 'cc.id = vs.point_id', 'LEFT')
            ->field('vs.*, m.nickname, m.mobile, cp.name as community_real_name')
            ->where($where)
            ->order('vs.id desc')
            ->paginate(15);

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('keyword', $keyword);
        $this->assign('date_start', $date_start);
        $this->assign('date_end', $date_end);
        return $this->fetch();
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
