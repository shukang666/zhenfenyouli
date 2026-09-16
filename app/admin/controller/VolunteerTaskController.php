<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

/**
 * 整改任务管理
 * 在垃圾分类活动菜单下
 */
class VolunteerTaskController extends AdminBaseController
{
    protected $taskModel;
    protected $logModel;

    protected function initialize()
    {
        parent::initialize();
        $this->taskModel = Db::name('volunteer_task');
        $this->logModel  = Db::name('volunteer_task_log');
    }

    /**
     * 任务列表
     */
    public function index()
    {
        $params = $this->request->param();
        $where = [];
        $status = $this->request->param('status', '');
        if ($status !== '') {
            $where[] = ['status', '=', intval($status)];
        }

        $list = $this->taskModel->where($where)
            ->order('id desc')
            ->paginate(15);

        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('status', $status);
        return $this->fetch();
    }

    /**
     * 新增/编辑任务
     */
    public function add()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $row = [
                'title'      => htmlspecialchars(trim($data['title'] ?? '')),
                'content'    => htmlspecialchars(trim($data['content'] ?? '')),
                'point'      => intval($data['point'] ?? 0),
                'status'     => intval($data['status'] ?? 1),
                'admin_id'   => cmf_get_current_admin_id(),
                'update_time' => time(),
            ];
            if ($id) {
                $this->taskModel->where('id', $id)->update($row);
                $this->success('更新成功', url('VolunteerTask/index'));
            } else {
                $row['create_time'] = time();
                $this->taskModel->insert($row);
                $this->success('创建成功', url('VolunteerTask/index'));
            }
            return;
        }

        if ($id) {
            $vo = $this->taskModel->where('id', $id)->find();
            $this->assign('vo', $vo);
        }
        return $this->fetch();
    }

    /**
     * 切换状态
     */
    public function toggle()
    {
        $id = $this->request->param('id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        $this->taskModel->where('id', $id)->update([
            'status' => $status,
            'update_time' => time(),
        ]);
        $this->success('操作成功');
    }

    /**
     * 删除
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $this->taskModel->where('id', $id)->delete();
        $this->success('删除成功');
    }

    /**
     * 任务提交记录
     */
    public function records()
    {
        $params = $this->request->param();
        $where = [];

        $taskId = $this->request->param('task_id', '');
        if ($taskId !== '') {
            $where[] = ['tl.task_id', '=', intval($taskId)];
        }

        $status = $this->request->param('status', '');
        if ($status !== '') {
            $where[] = ['tl.status', '=', intval($status)];
        }

        $keyword = $this->request->param('keyword', '');
        if (!empty($keyword)) {
            $where[] = ['m.nickname|m.mobile', 'like', '%' . trim($keyword) . '%'];
        }

        $list = $this->logModel->alias('tl')
            ->join('cmf_member m', 'm.id = tl.user_id', 'LEFT')
            ->join('cmf_volunteer_task vt', 'vt.id = tl.task_id', 'LEFT')
            ->field('tl.*, m.nickname, m.mobile, vt.title as task_title')
            ->where($where)
            ->order('tl.id desc')
            ->paginate(15);

        $taskList = $this->taskModel->where('status', 1)->column('title', 'id');
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());
        $this->assign('params', $params);
        $this->assign('task_list', $taskList);
        $this->assign('task_id', $taskId);
        $this->assign('status', $status);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    /**
     * 审核通过
     */
    public function approve()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $log = $this->logModel->where('id', $id)->find();
        if (empty($log)) {
            $this->error('记录不存在');
        }
        if ($log['status'] != 0) {
            $this->error('该记录已审核');
        }

        $task = $this->taskModel->where('id', $log['task_id'])->find();
        $point = $task ? intval($task['point']) : 0;

        Db::startTrans();
        try {
            $this->logModel->where('id', $id)->update([
                'status' => 1,
                'point' => $point,
                'admin_id' => cmf_get_current_admin_id(),
                'update_time' => time(),
            ]);
            if ($point > 0) {
                \app\admin\model\AccountLogModel::add_account_log(
                    $log['user_id'], 2, 225, $point,
                    ['task_id' => $log['task_id']]
                );
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败：' . $e->getMessage());
        }

        $this->success('审核通过，已下发积分');
    }

    /**
     * 审核拒绝
     */
    public function deny()
    {
        $id = $this->request->param('id', 0, 'intval');
        $reason = $this->request->param('reason', '');
        $log = $this->logModel->where('id', $id)->find();
        if (empty($log)) {
            $this->error('记录不存在');
        }
        $this->logModel->where('id', $id)->update([
            'status' => 2,
            'reason' => $reason,
            'admin_id' => cmf_get_current_admin_id(),
            'update_time' => time(),
        ]);
        $this->success('已拒绝');
    }
}
