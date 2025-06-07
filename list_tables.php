<?php
require_once 'backend/classes/Database.php';

$db = App\Database::getInstance();

echo "=== 數據庫表列表 ===\n";

$tables = $db->fetchAll('SHOW TABLES');
foreach($tables as $table) {
    $tableName = array_values($table)[0];
    echo "- $tableName\n";
}

echo "\n=== 檢查 AI 相關表 ===\n";

// 檢查是否有 ai_requests 表
$aiRequestsExists = $db->fetchAll("SHOW TABLES LIKE 'ai_requests'");
if (count($aiRequestsExists) > 0) {
    echo "✅ ai_requests 表存在\n";
    $columns = $db->fetchAll('DESCRIBE ai_requests');
    foreach ($columns as $row) {
        echo "  - {$row['Field']}: {$row['Type']} (Null: {$row['Null']})\n";
    }
} else {
    echo "❌ ai_requests 表不存在\n";
}

// 檢查是否有 ai_interactions 表
$aiInteractionsExists = $db->fetchAll("SHOW TABLES LIKE 'ai_interactions'");
if (count($aiInteractionsExists) > 0) {
    echo "✅ ai_interactions 表存在\n";
    $columns = $db->fetchAll('DESCRIBE ai_interactions');
    foreach ($columns as $row) {
        echo "  - {$row['Field']}: {$row['Type']} (Null: {$row['Null']})\n";
    }
} else {
    echo "❌ ai_interactions 表不存在\n";
}
?> 