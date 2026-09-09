<?php

namespace app\cron\command;

use app\cron\TaskRunner;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 定时任务调度入口（ThinkPHP 命令行）
 *
 * 部署方式：在服务器 crontab(或宝塔计划任务) 中每分钟调用一次：
 *   * * * * * php /网站根目录/think cron:run >> /www/wwwroot/xiayuxuan.com/data/runtime/cron.log 2>&1
 *
 * 框架会遍历 cmf_cron_task 中“启用且到期”的任务逐个执行，
 * 并把执行结果写回 last_run_time / last_run_result / state。
 */
class CronRun extends Command
{
    protected function configure()
    {
        $this->setName('cron:run')
            ->setDescription('执行所有到期且启用的定时任务(建议每分钟由系统计划任务调用)');
    }

    protected function execute(Input $input, Output $output)
    {
        @set_time_limit(0);
        try {
            $init = TaskRunner::ensureReady();
            if ($init['created']) {
                $output->writeln('<info>cmf_cron_task 表不存在，已自动创建。</info>');
            }
            if ($init['synced'] > 0) {
                $output->writeln('<info>已自动补全注册任务的配置行，新增 ' . $init['synced'] . ' 条。</info>');
            }
            $executed = TaskRunner::runDue();
            if ($executed > 0) {
                $output->writeln('<info>本次执行到期任务 ' . $executed . ' 个。</info>');
            } else {
                $output->writeln('没有到期需要执行的任务。');
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>定时任务调度异常：' . $e->getMessage() . '</error>');
        }
        return 0;
    }
}
