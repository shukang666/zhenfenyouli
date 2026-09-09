<?php

namespace app\admin\controller;

use app\cron\TaskRegistry;
use app\cron\TaskRunner;
use cmf\controller\AdminBaseController;
use think\App;
use think\facade\Db;

/**
 * PC 后台 - 定时任务配置
 *
 * 任务本体由代码注册(不可新增/删除)，本控制器只负责：启停、调整周期、配置参数(JSON)、
 * 查看运行状态、手动立即执行一次。
 */
class CronTaskController extends AdminBaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /** 列表 */
    public function index()
    {
        TaskRunner::ensureReady();
        $list = Db::name('cron_task')->order('id')->select();
        $rows = [];
        foreach ($list as $row) {
            $item = $row->toArray();
            $meta = TaskRegistry::byKey($item['task_key']);
            if ($meta === null) {
                continue; // 代码已下线的任务不展示
            }
            $item['meta']          = $meta;
            $item['cycle_text']    = TaskRunner::cycleText($item);
            $item['param_text']    = $this->buildParamText($meta, json_decode((string)$item['param'], true) ?: []);
            $item['state_text']    = $this->buildStateText($item['state']);
            $item['last_run_text'] = $item['last_run_time'] ? date('Y-m-d H:i:s', (int)$item['last_run_time']) : '从未执行';
            $item['next_run_text'] = $item['next_run_time'] ? date('Y-m-d H:i:s', (int)$item['next_run_time']) : '未排期';
            $rows[] = $item;
        }
        $this->assign('list', $rows);
        return $this->fetch('index');
    }

    /** 编辑页(周期/启停/参数JSON) */
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $row = Db::name('cron_task')->find($id);
        if (empty($row)) {
            $this->error('任务不存在');
        }
        $meta = TaskRegistry::byKey($row['task_key']);
        if ($meta === null) {
            $this->error('该任务未在代码中注册，无法编辑');
        }
        $param = array_merge(
            $meta['param_defaults'],
            json_decode((string)$row['param'], true) ?: []
        );
        $this->assign('item', $row);
        $this->assign('meta', $meta);
        $this->assign('param', $param);
        $this->assign('state', json_decode((string)$row['state'], true) ?: []);
        $this->assign('last_run_text', $row['last_run_time'] ? date('Y-m-d H:i:s', (int)$row['last_run_time']) : '从未执行');
        $this->assign('next_run_text', $row['next_run_time'] ? date('Y-m-d H:i:s', (int)$row['next_run_time']) : '未排期');
        return $this->fetch('edit');
    }

    /** 保存编辑 */
    public function edit_post()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id = $this->request->param('id', 0, 'intval');
        $row = Db::name('cron_task')->find($id);
        if (empty($row)) {
            $this->error('任务不存在');
        }
        $meta = TaskRegistry::byKey($row['task_key']);
        if ($meta === null) {
            $this->error('该任务未在代码中注册');
        }

        $cycleType = (int)$this->request->param('cycle_type', 1);
        if (!in_array($cycleType, [1, 2, 3], true)) {
            $this->error('周期类型不合法');
        }
        $cycleInterval = max(1, (int)$this->request->param('cycle_interval', 1));
        $runTime = trim((string)$this->request->param('run_time', ''));
        if ($cycleType === 3 && $runTime === '') {
            $this->error('定点执行必须填写执行时间');
        }

        // 按参数 schema 组装 param JSON（每个 JSON 键对应任务的一个参数属性）
        $param = [];
        foreach ($meta['param_schema'] as $field) {
            $key = $field['key'];
            $val = $this->request->param('f_' . $key, null);
            if ($field['type'] === 'switch') {
                $param[$key] = (int)($this->request->param('f_' . $key, 0)) ? 1 : 0;
                continue;
            }
            if ($val === null || $val === '') {
                $param[$key] = $field['default'] ?? '';
            } elseif ($field['type'] === 'number') {
                $param[$key] = strpos((string)$val, '.') !== false ? (float)$val : (int)$val;
            } else {
                $param[$key] = (string)$val;
            }
        }

        $data = [
            'cycle_type'     => $cycleType,
            'cycle_interval' => $cycleInterval,
            'run_time'       => $runTime,
            'status'         => (int)$this->request->param('status', 0) ? 1 : 0,
            'param'          => json_encode($param, JSON_UNESCAPED_UNICODE),
            'update_time'    => time(),
        ];

        // 可选：重置运行进度(下次运行从最新数据锚定，适合调参后不想触碰存量)
        if ((int)$this->request->param('reset_state', 0) === 1) {
            $data['state'] = '';
            $data['last_run_result'] = '运行进度已重置(重新锚定)';
        }

        // 启用任务时把调度时间置为已到期，让下一次系统调度立刻跑一次
        if ($data['status'] === 1) {
            $data['next_run_time'] = time() - 60;
            $data['running']       = 0;
        }

        Db::name('cron_task')->where('id', $id)->update($data);
        $this->success('保存成功', url('index'));
    }

    /** 启用/停用 */
    public function toggle_status()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id     = (int)$this->request->param('id', 0);
        $status = (int)$this->request->param('status', 0) ? 1 : 0;
        $row = Db::name('cron_task')->find($id);
        if (empty($row)) {
            $this->error('任务不存在');
        }
        $data = ['status' => $status, 'update_time' => time()];
        if ($status === 1) {
            $data['next_run_time'] = time() - 60; // 启用后立即进入待执行队列
            $data['running']       = 0;
        }
        Db::name('cron_task')->where('id', $id)->update($data);
        $this->success($status ? '已启用，将在下一个调度周期执行' : '已停用');
    }

    /** 立即执行一次(不改变自动调度计划) */
    public function run_now()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        $id = (int)$this->request->param('id', 0);
        $row = Db::name('cron_task')->find($id);
        if (empty($row)) {
            $this->error('任务不存在');
        }
        $result = TaskRunner::runNow($row['task_key']);
        if ($result['ok']) {
            $this->success('执行完成：' . $result['message']);
        }
        $this->error($result['message']);
    }

    /** 参数摘要文案 */
    private function buildParamText(array $meta, array $param): string
    {
        if (empty($param)) {
            return '';
        }
        $labels = [];
        foreach ($meta['param_schema'] as $field) {
            $key = $field['key'];
            if (!array_key_exists($key, $param)) {
                continue;
            }
            $val = $param[$key];
            if ($field['type'] === 'switch') {
                $labels[] = $field['label'] . ': ' . ($val ? '开' : '关');
            } else {
                $labels[] = $field['label'] . ': ' . $val;
            }
        }
        return implode('，', $labels);
    }

    private function buildStateText(string $stateJson): string
    {
        $state = json_decode($stateJson, true);
        if (!is_array($state) || empty($state)) {
            return '未初始化';
        }
        $parts = [];
        foreach ($state as $k => $v) {
            $parts[] = $k . '=' . $v;
        }
        return implode('，', $parts);
    }
}
