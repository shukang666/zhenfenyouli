<?php

namespace app\cron;

/**
 * 定时任务处理器接口
 *
 * 每个定时任务 = 实现本接口的一个类，并在 TaskRegistry::TASK_CLASSES 中注册。
 * 任务本身不可在后台新增/删除，只能改周期、启停与参数 —— 代码即配置源。
 */
interface CronTaskInterface
{
    /**
     * 任务元信息（代码写死）。
     *
     * 返回结构示例：
     * [
     *   'task_key'       => 'forum_photo_dedup',          // 唯一标识，稳定不变
     *   'title'          => '垃圾分类打卡-重复图片清理',    // 后台展示名称
     *   'description'    => '原理说明',                    // 后台展示描述/原理
     *   'status_default' => 1,                             // 首次安装默认是否启用
     *   'cycle_type'     => 3,   // 1=每N分钟 2=每N小时 3=每天/每N天定点执行
     *   'cycle_interval' => 1,
     *   'run_time'       => '02:00', // cycle_type=3 时的定点时间 HH:MM
     *   'param_defaults' => [ 'similarity_threshold' => 100, ... ], // param JSON 默认值
     *   'param_schema'   => [                                        // 后台“参数”表单的动态渲染结构
     *       [ 'key' => 'similarity_threshold', 'label' => '相似度阈值(%)',
     *         'type' => 'number', 'default' => 100,
     *         'desc' => '...' , 'min' => 0, 'max' => 100 ],
     *   ],
     * ]
     *
     * @return array
     */
    public static function meta(): array;

    /**
     * 执行一次任务。
     *
     * @param array $task  DB 中该任务的配置行
     * @param array $param 合并 param_defaults 后的任务参数
     * @param array $state 任务运行状态(JSON 落库, 引用传参可写回)
     * @return array 必须包含 'summary' 字符串，用于写入 last_run_result
     */
    public function run(array $task, array $param, array &$state): array;
}
