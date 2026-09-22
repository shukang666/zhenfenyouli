<?php

namespace api\wxapp\controller;

use api\wxapp\model\AccountLogModel;
use think\facade\Db;

/**
 * 整改任务（小程序端）
 * 路径：/wxapp/volunteer_task/*
 */
class VolunteerTaskController extends AuthController
{
    /**
     * 可领取的整改任务列表
     * GET /wxapp/volunteer_task/task_list
     */
    public function task_list()
    {
        $this->checkUser();

        // 查所有启用的任务
        $allTasks = Db::name('volunteer_task')
            ->where('status', 1)
            ->order('id desc')
            ->select()
            ->toArray();

        // 查用户已领取的任务ID
        $acceptedTaskIds = Db::name('volunteer_task_log')
            ->where('user_id', $this->user_id)
            ->column('task_id');

        $dailyLimit = $this->todayClaimCount() >= 1;

        foreach ($allTasks as &$task) {
            $task['accepted'] = in_array($task['id'], $acceptedTaskIds);
            $task['daily_limit'] = $dailyLimit;
            $task['point']    = intval($task['point']);
            $task['from_shot'] = intval($task['shot_id'] ?? 0) > 0;
            if (!empty($task['image'])) {
                $task['image'] = cmf_get_asset_url($task['image']);
            } else {
                $task['image'] = '';
            }
        }
        unset($task);

        $this->success('获取成功', $allTasks);
    }

    /**
     * 领取任务
     * POST /wxapp/volunteer_task/accept
     * 参数：task_id
     */
    public function accept()
    {
        $this->checkUser();
        $taskId = intval($this->request->param('task_id', 0));
        if (empty($taskId)) {
            $this->error('参数错误');
        }

        $task = Db::name('volunteer_task')->where('id', $taskId)->where('status', 1)->find();
        if (empty($task)) {
            $this->error('任务不存在或已下架');
        }

        // 判断是否已领取
        $exist = Db::name('volunteer_task_log')
            ->where('user_id', $this->user_id)
            ->where('task_id', $taskId)
            ->find();
        if ($exist) {
            $this->error('请勿重复领取');
        }

        if ($this->todayClaimCount() >= 1) {
            $this->error('每人每天最多领取 1 条，明天再来');
        }

        Db::name('volunteer_task_log')->insert([
            'user_id'    => $this->user_id,
            'task_id'    => $taskId,
            'images'     => '',
            'content'    => '',
            'status'     => 0,      // 待提交整改内容
            'point'      => 0,
            'reason'     => '',
            'admin_id'   => 0,
            'create_time'=> time(),
            'update_time'=> time(),
        ]);

        $this->success('领取成功');
    }

    /**
     * 我的整改任务列表
     * GET /wxapp/volunteer_task/my_tasks
     * 返回：已领取/已提交/审核结果
     */
    public function my_tasks()
    {
        $this->checkUser();
        $list = Db::name('volunteer_task_log')->alias('tl')
            ->join('cmf_volunteer_task vt', 'vt.id = tl.task_id', 'LEFT')
            ->field('tl.*, vt.title as task_title, vt.content as task_content, vt.point as task_point, vt.image as task_image, vt.shot_id')
            ->where('tl.user_id', $this->user_id)
            ->order('tl.id desc')
            ->paginate(15)
            ->each(function ($item) {
                return $this->enrichMyTaskRow($item);
            });

        $this->success('获取成功', $list);
    }

    /**
     * 我的任务列表行：补全任务信息，任务被删时不返回 null
     */
    protected function enrichMyTaskRow($item)
    {
        $item = is_array($item) ? $item : $item->toArray();

        $imgs = \app\admin\controller\VolunteerTaskController::parseLogImages($item['images'] ?? '');
        $item['images_arr'] = array_values($imgs);

        $title = $item['task_title'] ?? '';
        $content = $item['task_content'] ?? '';
        $point = $item['task_point'] ?? 0;
        $image = $item['task_image'] ?? '';
        $shotId = intval($item['shot_id'] ?? 0);

        if ($title === null || $title === '') {
            $task = Db::name('volunteer_task')->where('id', $item['task_id'])->find();
            if (!empty($task)) {
                $title = $task['title'];
                $content = $task['content'];
                $point = $task['point'];
                $image = $task['image'];
                $shotId = intval($task['shot_id'] ?? 0);
            } else {
                $title = '整改任务（已下架）';
                if ($content === null || $content === '') {
                    $content = '关联任务已删除，请等待管理员审核您的提交';
                }
                $point = 0;
                $image = '';
            }
        }

        $item['task_title'] = (string)$title;
        $item['task_content'] = (string)($content ?? '');
        $item['task_point'] = intval($point);
        $item['task_image'] = !empty($image) ? cmf_get_asset_url($image) : '';
        $item['shot_id'] = $shotId;
        $item['from_shot'] = $shotId > 0;
        $item['status_name'] = $this->logStatusName(intval($item['status']));
        return $item;
    }

    /**
     * 提交整改内容
     * POST /wxapp/volunteer_task/submit
     * 参数：log_id, images(逗号/JSON), content
     */
    public function submit()
    {
        $this->checkUser();
        $logId   = intval($this->request->param('log_id', 0));
        $images  = $this->request->param('images', '');
        $content = trim($this->request->param('content', ''));

        if (empty($logId)) {
            $this->error('参数错误');
        }

        $log = Db::name('volunteer_task_log')->where('id', $logId)->where('user_id', $this->user_id)->find();
        if (empty($log)) {
            $this->error('记录不存在');
        }
        if (!in_array(intval($log['status']), [0, 2], true)) {
            $this->error('该任务已提交，请等待审核');
        }

        // 处理 images 参数
        $imagesStr = '';
        if (!empty($images)) {
            if (is_array($images)) {
                $imagesStr = json_encode(array_values(array_map('trim', $images)), JSON_UNESCAPED_UNICODE);
            } elseif ($images[0] === '[') {
                $imagesStr = $images;
            } else {
                // 逗号分隔的多图 URL
                $parts = array_map('trim', explode(',', $images));
                $parts = array_filter($parts);
                $imagesStr = json_encode(array_values($parts), JSON_UNESCAPED_UNICODE);
            }
        }

        if (empty($imagesStr) && empty($content)) {
            $this->error('请上传整改照片或填写整改说明');
        }

        Db::name('volunteer_task_log')->where('id', $logId)->update([
            'images'      => $imagesStr,
            'content'     => $content,
            'status'      => 1,  // 待审核
            'update_time' => time(),
        ]);

        $this->success('提交成功，请等待审核');
    }

    /** 当天 0 点起已领取条数（Asia/Shanghai） */
    protected function todayClaimCount()
    {
        $start = strtotime(date('Y-m-d 00:00:00'));
        return (int) Db::name('volunteer_task_log')
            ->where('user_id', $this->user_id)
            ->where('create_time', '>=', $start)
            ->count();
    }

    private function logStatusName($status)
    {
        $names = [
            0 => '待提交',
            1 => '待审核',
            2 => '审核失败',
            3 => '审核通过',
        ];
        return $names[$status] ?? '未知';
    }
}
