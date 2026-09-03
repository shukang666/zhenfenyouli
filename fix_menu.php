<?php
$host = '124.223.56.252';
$port = 3306;
$dbname = 'xcxkf052_aubye_c';
$user = 'xcxkf052_aubye_c';
$pass = 'acacQQndx12ADXMm';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. 删除旧菜单项
    echo "1. 删除旧菜单项 (forum/delete_log)...\n";
    $affected = $pdo->exec("DELETE FROM `cmf_admin_menu` WHERE `controller`='forum' AND `action`='delete_log'");
    echo "   删除了 {$affected} 行\n";

    // 2. 查找健康生活社区的ID
    echo "2. 查找健康生活社区菜单ID...\n";
    $stmt = $pdo->query("SELECT id FROM `cmf_admin_menu` WHERE `controller`='forum' AND `action`='default' LIMIT 1");
    $parentId = $stmt->fetchColumn();
    echo "   健康生活社区 parent_id = {$parentId}\n";

    // 3. 检查新菜单项是否已存在
    echo "3. 检查新菜单项是否已存在...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM `cmf_admin_menu` WHERE `controller`='forum' AND `action`='delete_log_index'");
    $exists = $stmt->fetchColumn();
    if ($exists > 0) {
        echo "   新菜单项已存在，跳过插入\n";
    } else {
        echo "   插入新菜单项...\n";
        $sql = "INSERT INTO `cmf_admin_menu` (`parent_id`, `app`, `controller`, `action`, `param`, `name`, `status`, `list_order`)
                VALUES ({$parentId}, 'admin', 'forum', 'delete_log_index', '', '删除记录', 1, 100)";
        $pdo->exec($sql);
        echo "   ✅ 插入成功\n";
    }

    // 4. 验证结果
    echo "\n=== 验证结果 ===\n";
    $stmt = $pdo->query("SELECT id, parent_id, name, controller, action FROM `cmf_admin_menu` WHERE `controller`='forum' AND (action IN ('delete_log','delete_log_index','default','index2')) ORDER BY parent_id, id");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $isNew = $r['action'] === 'delete_log_index' ? ' ← ✅ 新菜单项（挂在健康生活社区下）' : '';
        echo "id={$r['id']} parent_id={$r['parent_id']} | {$r['controller']}/{$r['action']} | {$r['name']}{$isNew}\n";
    }

    echo "\n✨ 修复完成！\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
