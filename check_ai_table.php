<?php
require_once 'backend/classes/Database.php';

$db = App\Database::getInstance();

echo "=== 檢查 ai_requests 表結構 ===\n";

// 檢查表是否存在
$result = $db->fetchAll("SHOW TABLES LIKE 'ai_requests'");
if (count($result) > 0) {
    echo "✅ ai_requests 表存在\n\n";
    
    // 顯示表結構
    $columns = $db->fetchAll('DESCRIBE ai_requests');
    echo "欄位結構:\n";
    foreach ($columns as $row) {
        echo sprintf("%-20s %-20s %-5s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'],
            $row['Default'] ?? 'NULL'
        );
    }
    
    echo "\n";
    
    // 顯示創建語句
    $createTable = $db->fetch('SHOW CREATE TABLE ai_requests');
    echo "創建語句:\n";
    echo $createTable['Create Table'] . "\n";
    
} else {
    echo "❌ ai_requests 表不存在\n";
}
?> 