<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 志愿者整改清单
 * 在垃圾分类活动菜单下
 */
class ShotRectifyController extends AdminBaseController
{
    protected $model;

    protected function initialize()
    {
        $this->model = Db::name('active_shot_rectify');
    }

    /**
     * 整改清单列表
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

        // 搜索
        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['r.remark', 'like', '%' . trim($keyword) . '%'];
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
        return $this->fetch();
    }

    /**
     * 标记为已整改
     */
    public function rectified()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }

        $this->model->where('id', $id)->update([
            'status' => 1,
            'rectify_time' => time()
        ]);

        $this->success('已标记为已整改');
    }
}
