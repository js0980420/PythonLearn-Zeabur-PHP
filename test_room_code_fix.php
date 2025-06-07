<?php
/**
 * 測試房間代碼載入修復
 * 驗證新房間是否能正確載入預設代碼
 */

require_once 'classes/Database.php';

echo "🧪 測試房間代碼載入修復\n";
echo "========================\n\n";

try {
    // 初始化數據庫
    $database = new Database();
    echo "✅ 數據庫連接成功\n\n";
    
    // 測試房間ID
    $testRoomId = 'test_room_' . time();
    echo "🏠 測試房間ID: {$testRoomId}\n\n";
    
    // 1. 測試加入新房間（這會觸發 createRoomIfNotExists）
    echo "1️⃣ 測試加入新房間...\n";
    $joinResult = $database->joinRoom($testRoomId, 'test_user', 'Test User');
    if ($joinResult['success']) {
        echo "   ✅ 成功加入房間\n";
    } else {
        echo "   ❌ 加入房間失敗: " . ($joinResult['error'] ?? '未知錯誤') . "\n";
    }
    
    // 2. 測試載入房間代碼
    echo "\n2️⃣ 測試載入房間代碼...\n";
    $loadResult = $database->loadCode($testRoomId);
    
    if ($loadResult['success']) {
        echo "   ✅ 代碼載入成功\n";
        echo "   📝 代碼內容: " . substr($loadResult['code'], 0, 50) . "...\n";
        echo "   📊 代碼長度: " . strlen($loadResult['code']) . " 字符\n";
        echo "   🏷️ 槽位ID: " . ($loadResult['slot_id'] ?? 'null') . "\n";
        echo "   📛 保存名稱: " . ($loadResult['save_name'] ?? 'null') . "\n";
    } else {
        echo "   ❌ 代碼載入失敗: " . ($loadResult['error'] ?? '未知錯誤') . "\n";
    }
    
    // 3. 測試直接查詢 rooms 表
    echo "\n3️⃣ 測試直接查詢 rooms 表...\n";
    $roomQuery = $database->query("SELECT id, name, current_code, created_at FROM rooms WHERE id = ?", [$testRoomId]);
    
    if ($roomQuery && count($roomQuery) > 0) {
        $room = $roomQuery[0];
        echo "   ✅ 房間記錄存在\n";
        echo "   🆔 房間ID: " . $room['id'] . "\n";
        echo "   📛 房間名稱: " . $room['name'] . "\n";
        echo "   📝 當前代碼: " . (empty($room['current_code']) ? '❌ 空' : '✅ 有內容 (' . strlen($room['current_code']) . ' 字符)') . "\n";
        echo "   📅 創建時間: " . $room['created_at'] . "\n";
        
        if (!empty($room['current_code'])) {
            echo "   📄 代碼預覽: " . substr($room['current_code'], 0, 100) . "...\n";
        }
    } else {
        echo "   ❌ 房間記錄不存在\n";
    }
    
    // 4. 測試 WebSocket 服務器的邏輯模擬
    echo "\n4️⃣ 模擬 WebSocket 服務器邏輯...\n";
    
    // 模擬 handleJoinRoom 中的 loadCode 調用
    try {
        $codeData = $database->loadCode($testRoomId);
        $currentCode = $codeData['code'] ?? '';
        
        // 模擬 WebSocket 響應數據
        $responseData = [
            'type' => 'room_joined',
            'room_id' => $testRoomId,
            'user_id' => 'test_user',
            'username' => 'Test User',
            'message' => "成功加入房間 {$testRoomId}",
            'current_code' => $currentCode,
            'users' => [
                [
                    'user_id' => 'test_user',
                    'username' => 'Test User',
                    'status' => 'active'
                ]
            ],
            'timestamp' => date('c')
        ];
        
        echo "   ✅ WebSocket 響應數據生成成功\n";
        echo "   📝 current_code 字段: " . (empty($responseData['current_code']) ? '❌ 空' : '✅ 有內容 (' . strlen($responseData['current_code']) . ' 字符)') . "\n";
        echo "   👥 用戶數量: " . count($responseData['users']) . "\n";
        
        // 檢查前端期待的字段
        $requiredFields = ['type', 'room_id', 'user_id', 'username', 'current_code', 'users'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($responseData[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (empty($missingFields)) {
            echo "   ✅ 所有必要字段都存在\n";
        } else {
            echo "   ❌ 缺少字段: " . implode(', ', $missingFields) . "\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ WebSocket 邏輯模擬失敗: " . $e->getMessage() . "\n";
    }
    
    // 5. 清理測試數據
    echo "\n5️⃣ 清理測試數據...\n";
    try {
        $database->query("DELETE FROM room_users WHERE room_id = ?", [$testRoomId]);
        $database->query("DELETE FROM rooms WHERE id = ?", [$testRoomId]);
        echo "   ✅ 測試數據清理完成\n";
    } catch (Exception $e) {
        echo "   ⚠️ 清理測試數據時出錯: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 測試完成！\n";
    echo "如果看到 current_code 字段有內容，說明修復成功。\n";
    
} catch (Exception $e) {
    echo "❌ 測試失敗: " . $e->getMessage() . "\n";
    echo "📍 錯誤位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?> 