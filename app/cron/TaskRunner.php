<?php

namespace app\cron;

use think\facade\Db;

/**
 * 定时任务调度/执行引擎（命令行与后台“立即执行”共用）
 */
class TaskRunner
{
    /**
     * 建表 + 补全注册任务配置行。返回 ['created'=>bool,'synced'=>int]
     */
    public static function ensureReady(): array
    {
        return TaskRegistry::ensureReady();
    }

    /**
     * 运行所有到期且启用的任务（供系统计划任务每分钟调用 cron:run）。
     * @return int 实际执行任务数
     */
    public static function runDue(): int
    {
        $now  = time();
        $ids  = Db::name('cron_task')
            ->where('status', 1)
            ->where('running', 0)
            ->where('next_run_time', '<=', $now)
            ->column('id');
        $executed = 0;
        foreach ($ids as $id) {
            // 原子抢锁，防止多个调度器进程并发执行同一任务
            $lockOk = Db::name('cron_task')
                ->where('id', $id)
                ->where('running', 0)
                ->update(['running' => 1, 'update_time' => time()]);
            if (!$lockOk) {
                continue;
            }
            $row = Db::name('cron_task')->find($id);
            if (empty($row)) {
                Db::name('cron_task')->where('id', $id)->update(['running' => 0]);
                continue;
            }
            $executed++;
            $now = time();
            try {
                $result = self::executeRow($row, true);
                Db::name('cron_task')->where('id', $id)->update([
                    'last_run_time'   => $now,
                    'last_run_result' => mb_substr($result['summary'], 0, 490),
                    'next_run_time'   => self::computeNextRun($row, $now),
                    'state'           => json_encode($result['state'] ?? [], JSON_UNESCAPED_UNICODE),
                    'running'         => 0,
                    'update_time'     => time(),
                ]);
            } catch (\Throwable $e) {
                Db::name('cron_task')->where('id', $id)->update([
                    'last_run_time'   => $now,
                    'last_run_result' => '执行异常: ' . mb_substr($e->getMessage(), 0, 400),
                    'running'         => 0,
                    'update_time'     => time(),
                ]);
            }
        }
        return $executed;
    }

    /**
     * 立即执行指定任务（后台“立即执行”按钮用）。
     * 不影响 next_run_time 的自动调度计划。
     * @return array ['ok'=>bool,'message'=>string]
     */
    public static function runNow(string $taskKey): array
    {
        $row = Db::name('cron_task')->where('task_key', $taskKey)->find();
        if (empty($row)) {
            return ['ok' => false, 'message' => '任务不存在或未注册'];
        }
        if ((int)$row['running'] === 1) {
            return ['ok' => false, 'message' => '任务正在执行中，请稍后再试'];
        }
        $lockOk = Db::name('cron_task')->where('id', $row['id'])->where('running', 0)
            ->update(['running' => 1, 'update_time' => time()]);
        if (!$lockOk) {
            return ['ok' => false, 'message' => '任务正在执行中，请稍后再试'];
        }
        try {
            $result = self::executeRow($row, false);
            Db::name('cron_task')->where('id', $row['id'])->update([
                'last_run_time'   => time(),
                'last_run_result' => mb_substr($result['summary'], 0, 490),
                'state'           => json_encode($result['state'] ?? [], JSON_UNESCAPED_UNICODE),
                'running'         => 0,
                'update_time'     => time(),
            ]);
            return ['ok' => true, 'message' => $result['summary']];
        } catch (\Throwable $e) {
            Db::name('cron_task')->where('id', $row['id'])->update([
                'last_run_time'   => time(),
                'last_run_result' => '执行异常: ' . mb_substr($e->getMessage(), 0, 400),
                'running'         => 0,
                'update_time'     => time(),
            ]);
            return ['ok' => false, 'message' => '执行异常: ' . $e->getMessage()];
        }
    }

    /**
     * 真正执行一个任务处理器，返回 ['summary'=>..,'state'=>..]
     * @throws \Throwable
     */
    public static function executeRow(array $row, bool $recomputeNext): array
    {
        $class = TaskRegistry::getClassByKey($row['task_key']);
        if ($class === null) {
            throw new \RuntimeException("任务 {$row['task_key']} 未在代码 TaskRegistry 中注册");
        }
        $meta  = $class::meta();
        $savedParam = json_decode((string)($row['param'] ?? ''), true);
        $param = array_merge($meta['param_defaults'] ?? [], is_array($savedParam) ? $savedParam : []);
        $savedState = json_decode((string)($row['state'] ?? ''), true);
        $state = is_array($savedState) ? $savedState : [];

        $handler = new $class();
        $return = $handler->run($row, $param, $state);

        $summary = is_array($return) && isset($return['summary']) ? (string)$return['summary'] : (string)$return;
        return ['summary' => $summary, 'state' => $state];
    }

    /**
     * 根据周期配置计算下一次执行时间。
     * 1=每N分钟  2=每N小时  3=每天/每N天在 run_time 定点
     */
    public static function computeNextRun(array $row, int $now): int
    {
        $cycle = (int)($row['cycle_type'] ?? 1);
        $iv    = max(1, (int)($row['cycle_interval'] ?? 1));
        if ($cycle === 1) {
            return $now + $iv * 60;
        }
        if ($cycle === 2) {
            return $now + $iv * 3600;
        }
        // cycle_type=3：定点执行
        $runTime = (string)($row['run_time'] ?? '');
        if ($runTime === '' || !preg_match('/^\d{1,2}:\d{2}/', $runTime)) {
            $runTime = '02:00';
        }
        $last = (int)($row['last_run_time'] ?? 0);
        for ($d = 0; $d < 370; $d++) {
            $ts = strtotime(date('Y-m-d', $now + $d * 86400) . ' ' . $runTime);
            if ($ts <= $now + 30) {
                continue; // 今天已过该时刻则看下一天
            }
            if ($last > 0) {
                $lastDay = mktime(0, 0, 0, (int)date('m', $last), (int)date('d', $last), (int)date('Y', $last));
                $dayGap  = (int)(($ts - $lastDay) / 86400);
                if ($dayGap < $iv) {
                    continue; // 距上次执行不足 interval 天
                }
            }
            return $ts;
        }
        return $now + 86400;
    }

    /** 周期描述文案（列表展示用） */
    public static function cycleText(array $row): string
    {
        $cycle = (int)($row['cycle_type'] ?? 1);
        $iv    = (int)($row['cycle_interval'] ?? 1);
        if ($cycle === 1) {
            return "每 {$iv} 分钟";
        }
        if ($cycle === 2) {
            return "每 {$iv} 小时";
        }
        return "每 {$iv} 天 " . ((string)($row['run_time'] ?? '')). ' 执行';
    }
}
