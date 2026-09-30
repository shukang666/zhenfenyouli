<?php

namespace app\admin\controller;

use api\wxapp\service\ActiveWebPhotoService;
use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 「我所了解的七贤岭」照片审核
 */
class QxlPhotoShareController extends AdminBaseController
{
    protected $table;

    protected function initialize()
    {
        $this->table = Db::name(ActiveWebPhotoService::photoTable());
    }

    public function index()
    {
        $params = $this->request->param();
        $status = $this->request->param('status', '');
        $activeId = (int)$this->request->param('active_id', 0);

        $query = $this->table->alias('p')
            ->join('member m', 'm.id = p.user_id', 'LEFT')
            ->field('p.*, m.nickname, m.mobile');
        if ($status !== '') {
            $query->where('p.status', (int)$status);
        }
        if ($activeId > 0) {
            $query->where('p.active_id', $activeId);
        }
        $keyword = trim((string)$this->request->param('keyword', ''));
        if ($keyword !== '') {
            $query->where('p.intro', 'like', '%' . $keyword . '%');
        }

        $list = $query->order('p.id desc')->paginate(15);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('status', $status);
        $this->assign('active_id', $activeId);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    public function pass()
    {
        $id = (int)$this->request->param('id');
        $row = $this->table->where('id', $id)->find();
        if (empty($row) || (int)$row['status'] !== 0) {
            $this->error('记录不可审核');
        }
        $activeId = (int)$row['active_id'];
        $userId = (int)$row['user_id'];
        $point = ActiveWebPhotoService::grantPoints(
            $userId,
            $activeId,
            ActiveWebPhotoService::POINT_ON_APPROVE,
            '七贤岭摄影·审核通过'
        );
        $this->table->where('id', $id)->update([
            'status' => 1,
            'audit_time' => time(),
            'point_awarded' => $point,
        ]);
        ActiveWebPhotoService::maybeAwardBadge($userId, $activeId, '七贤岭影像达人');
        $this->success('已通过' . ($point > 0 ? '，已发放 ' . $point . ' 积分' : '（今日积分已达上限）'));
    }

    public function reject()
    {
        $id = (int)$this->request->param('id');
        $row = $this->table->where('id', $id)->find();
        if (empty($row) || (int)$row['status'] !== 0) {
            $this->error('记录不可审核');
        }
        $this->table->where('id', $id)->update([
            'status' => 2,
            'audit_time' => time(),
        ]);
        $this->success('已拒绝');
    }

    public function report_index()
    {
        $activeId = (int)$this->request->param('active_id', 0);
        $query = Db::name(ActiveWebPhotoService::reportTable());
        if ($activeId > 0) {
            $query->where('active_id', $activeId);
        }
        $list = $query->order('id desc')->paginate(15);
        $this->assign('list', $list);
        $this->assign('active_id', $activeId);
        $this->assign('page', $list->render());
        return $this->fetch('report_index');
    }
}
