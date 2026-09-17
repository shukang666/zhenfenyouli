<?php
require 'vendor/autoload.php';
(new think\App())->initialize();

echo "=== tables with qxl ===\n";
$tables = \think\facade\Db::query("SHOW TABLES LIKE '%qxl%'");
foreach ($tables as $t) {
    echo implode("\t", $t) . "\n";
}

echo "\n=== tables with knowledge ===\n";
$tables2 = \think\facade\Db::query("SHOW TABLES LIKE '%knowledge%'");
foreach ($tables2 as $t) {
    echo implode("\t", $t) . "\n";
}

echo "\n=== tables with quiz ===\n";
$tables3 = \think\facade\Db::query("SHOW TABLES LIKE '%quiz%'");
foreach ($tables3 as $t) {
    echo implode("\t", $t) . "\n";
}
