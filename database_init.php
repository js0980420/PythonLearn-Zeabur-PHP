<?php

/**
 * 數據庫初始化腳本
 * 用於設置 XAMPP MySQL 數據庫和創建必要的表格
 */

require_once 'backend/classes/Database.php';

echo "🔄 開始初始化數據庫...\n";

try {
    // 1. 創建數據庫（如果不存在）
    $config = require 'backend/config/database.php';
    
    // 連接到 MySQL 服務器（不指定數據庫）
    $serverDsn = sprintf(
        "mysql:host=%s;port=%d;charset=%s",
        $config['host'],
        $config['port'],
        $config['charset']
    );
    
    $serverConnection = new PDO(
        $serverDsn,
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 創建數據庫
    $dbName = $config['database'];
    $serverConnection->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ 數據庫 '{$dbName}' 已創建或已存在\n";
    
    // 2. 初始化 Database 類別
    $database = Database::getInstance();
    echo "✅ 數據庫連接已建立\n";
    
    // 3. 創建表格
    $database->createTables();
    echo "✅ 數據庫表格已創建\n";
    
    // 4. 插入測試數據
    $database->initialize();
    echo "✅ 測試數據已插入\n";
    
    // 5. 顯示數據庫狀態
    echo "\n📊 數據庫狀態：\n";
    echo "數據庫類型: " . $database->getDatabaseType() . "\n";
    
    $users = $database->fetchAll("SELECT * FROM users");
    echo "用戶數量: " . count($users) . "\n";
    
    foreach ($users as $user) {
        echo "  - {$user['username']} ({$user['user_type']})\n";
    }
    
    echo "\n🎉 數據庫初始化完成！\n";
    
} catch (PDOException $e) {
    echo "❌ 數據庫錯誤: " . $e->getMessage() . "\n";
    echo "⚠️ 請確保 XAMPP MySQL 服務已啟動\n";
    
    // 提供解決方案提示
    echo "\n💡 解決方案：\n";
    echo "1. 啟動 XAMPP 控制面板\n";
    echo "2. 啟動 Apache 和 MySQL 服務\n";
    echo "3. 確認 MySQL 運行在 localhost:3306\n";
    echo "4. 重新運行此腳本\n";
    
} catch (Exception $e) {
    echo "❌ 系統錯誤: " . $e->getMessage() . "\n";
}

?> 