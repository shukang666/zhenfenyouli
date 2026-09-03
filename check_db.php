<?php
$host = '124.223.56.252';
$port = 3306;
$dbname = 'xcxkf052_aubye_c';
$user = 'xcxkf052_aubye_c';
$pass = 'acacQQndx12ADXMm';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== 1. 检查表是否存在 ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'cmf_forum_delete_log'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo empty($tables) ? "❌ cmf_forum_delete_log 表不存在\n" : "✅ cmf_forum_delete_log 表已存在\n";

    echo "\n=== 2. forum 相关菜单项 ===\n";
    $stmt = $pdo->query("SELECT id, parent_id, name, controller, action, status FROM cmf_admin_menu WHERE controller='forum' AND (action IN ('delete_log','delete_log_index','default','index2')) ORDER BY parent_id, id");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "id={$r['id']} parent_id={$r['parent_id']} | {$r['controller']}/{$r['action']} | {$r['name']} | status={$r['status']}\n";
    }

    echo "\n=== 3. 一级菜单(parent_id=0)用于参考 ===\n";
    $stmt = $pdo->query("SELECT id, name, controller, action FROM cmf_admin_menu WHERE parent_id=0 ORDER BY id");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo "id={$r['id']} | {$r['controller']}/{$r['action']} | {$r['name']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
