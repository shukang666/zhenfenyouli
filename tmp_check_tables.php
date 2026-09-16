<?php
try {
    $pdo = new PDO('mysql:host=124.223.56.252;dbname=xcxkf052_aubye_c;charset=utf8mb4', 'xcxkf052_aubye_c', 'acacQQndx12ADXMm');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 查找社区相关表
    $stmt = $pdo->query("SHOW TABLES LIKE '%community%'");
    echo "=== community tables ===\n";
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) echo "$t\n";

    // 查看 cmf_volunteer_supervision 表结构
    echo "\n=== cmf_volunteer_supervision columns ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM cmf_volunteer_supervision");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) echo "$col[Field] ($col[Type])\n";

    // 查看 cmf_check 表结构
    echo "\n=== cmf_check columns ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM cmf_check");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) echo "$col[Field] ($col[Type])\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
