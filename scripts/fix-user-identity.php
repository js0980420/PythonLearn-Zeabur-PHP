<?php
/**
 * 修復用戶身份問題腳本
 * 解決 username 欄位被錯誤設置為 user_id 的問題
 */

require_once __DIR__ . '/../classes/Database.php';

echo "=====================================\n";
echo "用戶身份修復腳本\n";
echo "=====================================\n\n";

try {
    $database = new Database();
    
    echo "🔍 檢查數據庫中的用戶身份問題...\n\n";
    
    // 檢查 code_history 表中的問題記錄
    $sql = "SELECT id, room_id, user_id, username, save_name, created_at 
            FROM code_history 
            WHERE user_id = username 
            ORDER BY created_at DESC";
    
    $problemRecords = $database->query($sql);
    
    echo "📊 發現問題記錄: " . count($problemRecords) . " 條\n\n";
    
    if (count($problemRecords) > 0) {
        echo "問題記錄詳情:\n";
        echo "ID\t房間ID\t\t用戶ID\t\t用戶名稱\t保存名稱\t\t時間\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($problemRecords as $record) {
            printf("%d\t%s\t%s\t%s\t%s\t%s\n",
                $record['id'],
                substr($record['room_id'], 0, 12),
                substr($record['user_id'], 0, 12),
                substr($record['username'], 0, 12),
                substr($record['save_name'] ?? 'N/A', 0, 15),
                substr($record['created_at'], 0, 19)
            );
        }
        echo "\n";
        
        // 嘗試修復用戶名稱
        echo "🔧 開始修復用戶名稱...\n\n";
        
        $fixedCount = 0;
        foreach ($problemRecords as $record) {
            $userId = $record['user_id'];
            $newUsername = null;
            
            // 嘗試從用戶ID推斷正確的用戶名稱
            if (preg_match('/^學生(\d+)$/', $userId, $matches)) {
                $newUsername = "學生" . $matches[1];
            } elseif (preg_match('/^student(\d+)$/', $userId, $matches)) {
                $newUsername = "學生" . $matches[1];
            } elseif (preg_match('/^user(\d+)$/', $userId, $matches)) {
                $newUsername = "用戶" . $matches[1];
            } else {
                // 如果無法推斷，使用默認格式
                $newUsername = "用戶_" . substr($userId, -3);
            }
            
            // 使用 Database 的 update 方法
            try {
                $result = $database->update('code_history', 
                    ['username' => $newUsername], 
                    ['id' => $record['id']]
                );
                
                if ($result) {
                    echo "✅ 修復記錄 ID {$record['id']}: {$userId} -> {$newUsername}\n";
                    $fixedCount++;
                } else {
                    echo "❌ 修復失敗 ID {$record['id']}\n";
                }
            } catch (Exception $e) {
                echo "❌ 修復失敗 ID {$record['id']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n📈 修復統計:\n";
        echo "   總問題記錄: " . count($problemRecords) . "\n";
        echo "   成功修復: {$fixedCount}\n";
        echo "   修復率: " . round(($fixedCount / count($problemRecords)) * 100, 2) . "%\n\n";
        
    } else {
        echo "✅ 沒有發現用戶身份問題\n\n";
    }
    
    // 檢查 room_users 表
    echo "🔍 檢查房間用戶表...\n";
    $sql = "SELECT COUNT(*) as count FROM room_users WHERE user_id = username";
    $result = $database->query($sql);
    $roomUserIssues = isset($result[0]) ? $result[0]['count'] : 0;
    
    $fixedRoomUsers = 0;
    if ($roomUserIssues > 0) {
        echo "⚠️ 發現房間用戶表中有 {$roomUserIssues} 條問題記錄\n";
        
        // 獲取問題記錄並逐一修復
        $sql = "SELECT id, user_id FROM room_users WHERE user_id = username";
        $problemUsers = $database->query($sql);
        
        foreach ($problemUsers as $user) {
            $newUsername = "用戶_" . substr($user['user_id'], -3);
            try {
                $result = $database->update('room_users', 
                    ['username' => $newUsername], 
                    ['id' => $user['id']]
                );
                if ($result) $fixedRoomUsers++;
            } catch (Exception $e) {
                // 忽略錯誤，繼續處理
            }
        }
        
        echo "✅ 修復房間用戶記錄: {$fixedRoomUsers} 條\n";
    } else {
        echo "✅ 房間用戶表沒有問題\n";
    }
    
    // 檢查 chat_messages 表
    echo "\n🔍 檢查聊天消息表...\n";
    $sql = "SELECT COUNT(*) as count FROM chat_messages WHERE user_id = username";
    $result = $database->query($sql);
    $chatIssues = isset($result[0]) ? $result[0]['count'] : 0;
    
    $fixedChatMessages = 0;
    if ($chatIssues > 0) {
        echo "⚠️ 發現聊天消息表中有 {$chatIssues} 條問題記錄\n";
        
        // 獲取問題記錄並逐一修復
        $sql = "SELECT id, user_id FROM chat_messages WHERE user_id = username";
        $problemMessages = $database->query($sql);
        
        foreach ($problemMessages as $message) {
            $newUsername = "用戶_" . substr($message['user_id'], -3);
            try {
                $result = $database->update('chat_messages', 
                    ['username' => $newUsername], 
                    ['id' => $message['id']]
                );
                if ($result) $fixedChatMessages++;
            } catch (Exception $e) {
                // 忽略錯誤，繼續處理
            }
        }
        
        echo "✅ 修復聊天消息記錄: {$fixedChatMessages} 條\n";
    } else {
        echo "✅ 聊天消息表沒有問題\n";
    }
    
    echo "\n=====================================\n";
    echo "✅ 用戶身份修復完成！\n";
    echo "=====================================\n\n";
    
    echo "💡 修復摘要:\n";
    echo "   • 代碼歷史記錄修復: " . ($fixedCount ?? 0) . " 條\n";
    echo "   • 房間用戶記錄修復: {$fixedRoomUsers} 條\n";
    echo "   • 聊天消息記錄修復: {$fixedChatMessages} 條\n\n";
    
    echo "🚀 現在可以測試用戶身份是否正確:\n";
    echo "   1. 訪問: http://localhost:8080/test-user-identity.php\n";
    echo "   2. 測試多用戶保存功能\n";
    echo "   3. 檢查數據庫記錄是否正確\n\n";
    
} catch (Exception $e) {
    echo "❌ 修復過程中發生錯誤!\n";
    echo "錯誤: " . $e->getMessage() . "\n\n";
    
    echo "🔧 故障排除:\n";
    echo "   1. 確保數據庫連接正常\n";
    echo "   2. 檢查表結構是否完整\n";
    echo "   3. 確認有足夠的數據庫權限\n\n";
    
    exit(1);
}
?> 