<?php
/**
 * 清理測試數據腳本
 * 移除所有測試數據，保留數據庫結構
 */

require_once __DIR__ . '/../classes/Database.php';

echo "🧹 開始清理測試數據...\n";

try {
    $database = new Database();
    
    // 清理各個表的測試數據
    $tables = [
        'code_changes' => '代碼變更記錄',
        'chat_messages' => '聊天消息',
        'ai_interactions' => 'AI互動記錄',
        'code_history' => '代碼歷史記錄',
        'room_users' => '房間用戶',
        'rooms' => '房間'
    ];
    
    foreach ($tables as $table => $description) {
        $result = $database->query("DELETE FROM {$table}");
        if ($result !== false) {
            echo "✅ 清理 {$description} 完成\n";
        } else {
            echo "❌ 清理 {$description} 失敗\n";
        }
    }
    
    // 重置自增ID（僅 MySQL）
    try {
        foreach ($tables as $table => $description) {
            $database->query("ALTER TABLE {$table} AUTO_INCREMENT = 1");
        }
        echo "✅ 重置自增ID完成\n";
    } catch (Exception $e) {
        echo "ℹ️ 跳過自增ID重置（可能不是MySQL）\n";
    }
    
    echo "🎉 測試數據清理完成！\n";
    
} catch (Exception $e) {
    echo "❌ 清理失敗: " . $e->getMessage() . "\n";
} 