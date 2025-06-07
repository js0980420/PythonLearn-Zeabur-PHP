<?php
/**
 * 簡化測試：驗證房間代碼載入修復
 */

require_once 'classes/Database.php';

echo "🔧 簡化測試：房間代碼載入修復\n";
echo "================================\n\n";

try {
    $database = new Database();
    echo "✅ 數據庫連接成功\n";
    
    $testRoomId = 'test_fix_' . time();
    echo "🏠 測試房間: {$testRoomId}\n";
    
    // 加入房間（觸發房間創建）
    $joinResult = $database->joinRoom($testRoomId, 'test_user', 'Test User');
    echo "📥 加入房間: " . ($joinResult['success'] ? '✅ 成功' : '❌ 失敗') . "\n";
    
    // 載入代碼
    $loadResult = $database->loadCode($testRoomId);
    echo "📝 載入代碼: " . ($loadResult['success'] ? '✅ 成功' : '❌ 失敗') . "\n";
    
    if ($loadResult['success']) {
        $codeLength = strlen($loadResult['code']);
        echo "📊 代碼長度: {$codeLength} 字符\n";
        echo "📄 代碼預覽: " . substr($loadResult['code'], 0, 50) . "...\n";
        
        // 模擬WebSocket響應
        $wsResponse = [
            'type' => 'room_joined',
            'room_id' => $testRoomId,
            'current_code' => $loadResult['code']
        ];
        
        $responseCodeLength = strlen($wsResponse['current_code']);
        echo "🌐 WebSocket響應代碼長度: {$responseCodeLength} 字符\n";
        echo "🎯 修復狀態: " . ($responseCodeLength > 0 ? '✅ 成功' : '❌ 失敗') . "\n";
    }
    
    // 清理
    $database->query("DELETE FROM room_users WHERE room_id = ?", [$testRoomId]);
    $database->query("DELETE FROM rooms WHERE id = ?", [$testRoomId]);
    echo "🧹 清理完成\n";
    
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
}

echo "\n🎉 測試完成！\n";
?> 