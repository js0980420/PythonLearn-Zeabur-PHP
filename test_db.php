<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/backend/classes/Database.php';

use App\Database;

try {
    echo "正在測試資料庫連接...\n\n";
    
    $database = Database::getInstance();
    echo "✓ 資料庫連接成功\n\n";
    
    // 測試創建表格
    echo "正在創建資料表...\n";
    $database->createTables();
    echo "✓ 資料表創建成功\n\n";
    
    // 測試插入數據
    echo "正在測試插入數據...\n";
    $userId = $database->insert('users', [
        'username' => 'test_user_' . time(),
        'user_type' => 'student'
    ]);
    echo "✓ 用戶插入成功，ID: $userId\n\n";
    
    // 測試查詢數據
    echo "正在測試查詢數據...\n";
    $user = $database->fetch(
        "SELECT * FROM users WHERE id = :id",
        ['id' => $userId]
    );
    echo "✓ 用戶查詢成功: " . json_encode($user) . "\n\n";
    
    // 測試更新數據
    echo "正在測試更新數據...\n";
    $database->update('users', 
        ['user_type' => 'teacher'],
        ['id' => $userId]
    );
    echo "✓ 用戶更新成功\n\n";
    
    // 測試刪除數據
    echo "正在測試刪除數據...\n";
    $database->delete('users', ['id' => $userId]);
    echo "✓ 用戶刪除成功\n\n";
    
    echo "所有資料庫測試通過！\n";
    
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    echo "詳細信息: " . $e->getTraceAsString() . "\n";
} 