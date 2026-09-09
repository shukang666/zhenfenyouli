<?php

namespace app\cron;

use app\cron\task\ForumPhotoDedupTask;
use think\facade\Db;

/**
 * 定时任务注册中心
 *
 * 所有定时任务在代码里写死（TASK_CLASSES），后台/命令行都只维护 cmf_cron_task 表里的
 * “运行配置”行（启停、周期、param JSON），任务不可新增与删除。
 *
 * 新增定时任务的步骤：实现 CronTaskInterface 的类 + 在 TASK_CLASSES 末尾追加类名即可，
 * 后台页面会自动出现并补建配置行，其余全部通用。
 */
class TaskRegistry
{
    /** 代码注册的定时任务类（只读，写死） */
    const TASK_CLASSES = [
        ForumPhotoDedupTask::class,
    ];

    /** 完整表名（含前缀） */
    public static function tableName(): string
    {
        $default = (string)config('database.default');
        $prefix  = (string)config('database.connections.' . ($default ?: 'mysql') . '.prefix');
        if ($prefix === '') {
            $prefix = 'cmf_';
        }
        return $prefix . 'cron_task';
    }

    public static function tableExists(): bool
    {
        $list = Db::query("SHOW TABLES LIKE '" . addslashes(self::tableName()) . "'");
        return !empty($list);
    }

    /** 表不存在则自动创建（幂等），便于全新部署零手工 SQL */
    public static function ensureTable(): bool
    {
        if (self::tableExists()) {
            return false;
        }
        $table = self::tableName();
        $sql   = <<<SQL
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_key` varchar(64) NOT NULL DEFAULT '' COMMENT '任务标识(与代码注册key一致,写死)',
  `title` varchar(120) NOT NULL DEFAULT '' COMMENT '任务名称',
  `description` text COMMENT '任务描述/原理',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用 0停用',
  `cycle_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1每N分钟 2每N小时 3每天/每N天定点',
  `cycle_interval` smallint(6) NOT NULL DEFAULT '1' COMMENT '周期间隔N',
  `run_time` varchar(5) NOT NULL DEFAULT '' COMMENT '定点时间HH:MM(cycle_type=3生效)',
  `param` text COMMENT '任务参数(JSON),各键含义由任务代码定义',
  `state` text COMMENT '运行状态(JSON),由任务代码管理',
  `running` tinyint(1) NOT NULL DEFAULT '0' COMMENT '运行锁:1执行中',
  `last_run_time` int(11) NOT NULL DEFAULT '0' COMMENT '上次执行时间',
  `last_run_result` varchar(500) NOT NULL DEFAULT '' COMMENT '上次执行结果摘要',
  `next_run_time` int(11) NOT NULL DEFAULT '0' COMMENT '下次应执行时间',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_key` (`task_key`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='定时任务配置(代码注册,后台只可启停/改周期参数)'
SQL;
        Db::execute($sql);
        return true;
    }

    /**
     * 获取所有注册任务的 meta（规范化）
     * @return array[]
     */
    public static function all(): array
    {
        $list = [];
        foreach (self::TASK_CLASSES as $class) {
            if (!is_subclass_of($class, CronTaskInterface::class)) {
                continue;
            }
            $meta = $class::meta();
            $list[] = self::normalize($meta);
        }
        return $list;
    }

    /** 按 task_key 取 meta（未注册返回 null） */
    public static function byKey(string $taskKey): ?array
    {
        foreach (self::all() as $meta) {
            if ($meta['task_key'] === $taskKey) {
                return $meta;
            }
        }
        return null;
    }

    public static function getClassByKey(string $taskKey): ?string
    {
        foreach (self::TASK_CLASSES as $class) {
            $meta = $class::meta();
            if ($meta['task_key'] === $taskKey) {
                return $class;
            }
        }
        return null;
    }

    /**
     * 确保每个注册任务在 cmf_cron_task 都有一行配置（缺失自动补默认值）。
     * @return int 本次新增行数
     */
    public static function syncRows(): int
    {
        $added = 0;
        foreach (self::all() as $meta) {
            $exists = Db::name('cron_task')->where('task_key', $meta['task_key'])->find();
            if (!empty($exists)) {
                continue;
            }
            $now = time();
            Db::name('cron_task')->insert([
                'task_key'       => $meta['task_key'],
                'title'          => $meta['title'],
                'description'    => $meta['description'],
                'status'         => $meta['status_default'],
                'cycle_type'     => $meta['cycle_type'],
                'cycle_interval' => $meta['cycle_interval'],
                'run_time'       => $meta['run_time'],
                'param'          => json_encode($meta['param_defaults'], JSON_UNESCAPED_UNICODE),
                'state'          => '',
                'running'        => 0,
                'last_run_time'  => 0,
                'last_run_result'=> '',
                // 设为已到期, 让下一个调度周期立即跑一次(首次运行会自己锚定处理进度)
                'next_run_time'  => $now - 60,
                'create_time'    => $now,
                'update_time'    => $now,
            ]);
            $added++;
        }
        return $added;
    }

    /** 手动执行/页面读取前的一次性就绪(建表+补行) */
    public static function ensureReady(): array
    {
        $created = self::ensureTable();
        $synced  = self::syncRows();
        return ['created' => $created, 'synced' => $synced];
    }

    private static function normalize(array $meta): array
    {
        return [
            'task_key'       => (string)($meta['task_key'] ?? ''),
            'title'          => (string)($meta['title'] ?? ''),
            'description'    => (string)($meta['description'] ?? ''),
            'status_default' => (int)($meta['status_default'] ?? 1),
            'cycle_type'     => (int)($meta['cycle_type'] ?? 1),
            'cycle_interval' => (int)($meta['cycle_interval'] ?? 1),
            'run_time'       => (string)($meta['run_time'] ?? ''),
            'param_defaults' => (array)($meta['param_defaults'] ?? []),
            'param_schema'   => (array)($meta['param_schema'] ?? []),
        ];
    }
}
