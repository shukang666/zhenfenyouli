<?php

/**
 * 命令行自定义命令注册(app 级)
 *
 * 该文件会被 ThinkCMF 控制台自动扫描(app/xxx/command.php)加载,
 * 避免依赖被 .gitignore 忽略的 data/config/console.php,保证 git 部署后命令即生效。
 */
return [
    \app\command\ForumHashBackfill::class,
];
