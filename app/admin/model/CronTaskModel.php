<?php

namespace app\admin\model;

use think\Model;

/**
 * 定时任务配置模型 (cmf_cron_task)
 * 只存运行配置：启停、周期、param JSON。任务本体由代码注册，不可新增/删除。
 */
class CronTaskModel extends Model
{
    protected $name = 'cron_task';
}
