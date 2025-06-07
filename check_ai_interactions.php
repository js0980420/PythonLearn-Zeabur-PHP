<?php
require_once 'classes/Database.php';

$db = new Database();

echo "=== ai_interactions 表結構 ===\n";

$columns = $db->query('DESCRIBE ai_interactions');
foreach($columns as $row) {
    echo sprintf("%-20s %-30s %-5s %-10s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Default'] ?? 'NULL'
    );
}

echo "\n=== 測試插入數據 ===\n";

// 測試插入一條記錄
try {
    $testData = [
        'room_id' => 'test_room',
        'user_id' => 'test_user',
        'username' => 'Test User',
        'interaction_type' => 'check_errors',
        'user_input' => 'print("hello world")',
        'ai_response' => 'Code looks good!',
        'response_time_ms' => 1000,
        'tokens_used' => 50
    ];
    
    $result = $db->insert('ai_interactions', $testData);
    echo "✅ 測試插入成功，ID: $result\n";
    
    // 清理測試數據
    $db->query('DELETE FROM ai_interactions WHERE room_id = ?', ['test_room']);
    echo "✅ 測試數據已清理\n";
    
} catch (Exception $e) {
    echo "❌ 測試插入失敗: " . $e->getMessage() . "\n";
}
?> 