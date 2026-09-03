<?php
$host = '124.223.56.252';
$port = 3306;
$dbname = 'xcxkf052_aubye_c';
$user = 'xcxkf052_aubye_c';
$pass = 'acacQQndx12ADXMm';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/fix_menu.sql');
    // 按分号分割多条SQL
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $stmt) {
        if (empty($stmt) || strpos($stmt, '--') === 0) continue;
        // 跳过注释行
        $lines = explode("\n", $stmt);
        $cleanLines = array_filter($lines, function($line) {
            return trim($line) !== '' && strpos(trim($line), '--') !== 0;
        });
        $cleanStmt = implode("\n", $cleanLines);
        if (empty(trim($cleanStmt))) continue;
        
        echo "Executing: " . substr($cleanStmt, 0, 60) . "...\n";
        $pdo->exec($cleanStmt);
        echo "✅ OK\n";
    }

    echo "\n=== 验证结果 ===\n";
    $stmt = $pdo->query("SELECT id, parent_id, name, controller, action FROM cmf_admin_menu WHERE controller='forum' AND (action IN ('delete_log','delete_log_index','default','index2')) ORDER BY parent_id, id");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $isNew = $r['action'] === 'delete_log_index' ? ' ← 新菜单项' : '';
        echo "id={$r['id']} parent_id={$r['parent_id']} | {$r['controller']}/{$r['action']} | {$r['name']}{$isNew}\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
