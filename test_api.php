<?php
/**
 * API 測試腳本
 * 用於測試各個 API 端點是否正常工作
 */

echo "🧪 API 測試開始\n";
echo "================\n\n";

// 測試 1: 健康檢查
echo "1. 測試健康檢查 API\n";
$healthUrl = 'http://localhost:8080/health.php';
$healthResponse = @file_get_contents($healthUrl);
if ($healthResponse) {
    $healthData = json_decode($healthResponse, true);
    echo "✅ 健康檢查 API 正常\n";
    echo "   狀態: " . ($healthData['status'] ?? 'unknown') . "\n";
} else {
    echo "❌ 健康檢查 API 失敗\n";
}
echo "\n";

// 測試 2: 認證 API
echo "2. 測試認證 API\n";
$authUrl = 'http://localhost:8080/api/auth';
$authData = json_encode([
    'action' => 'login',
    'username' => 'TestUser',
    'user_type' => 'student'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $authData
    ]
]);

$authResponse = @file_get_contents($authUrl, false, $context);
if ($authResponse) {
    $authResult = json_decode($authResponse, true);
    echo "✅ 認證 API 正常\n";
    echo "   成功: " . ($authResult['success'] ? 'true' : 'false') . "\n";
    echo "   消息: " . ($authResult['message'] ?? 'no message') . "\n";
} else {
    echo "❌ 認證 API 失敗\n";
}
echo "\n";

// 測試 3: 歷史記錄 API
echo "3. 測試歷史記錄 API\n";
$historyUrl = 'http://localhost:8080/api/history?room_id=test_room_001';
$historyResponse = @file_get_contents($historyUrl);
if ($historyResponse) {
    $historyData = json_decode($historyResponse, true);
    echo "✅ 歷史記錄 API 正常\n";
    echo "   成功: " . ($historyData['success'] ? 'true' : 'false') . "\n";
    echo "   記錄數: " . (count($historyData['data']['history'] ?? [])) . "\n";
} else {
    echo "❌ 歷史記錄 API 失敗\n";
}
echo "\n";

// 測試 4: 檢查文件存在性
echo "4. 檢查關鍵文件\n";
$files = [
    'public/health.php' => '健康檢查端點',
    'backend/api/auth.php' => '認證 API',
    'backend/api/history.php' => '歷史記錄 API',
    'backend/classes/APIResponse.php' => 'API 響應類',
    'classes/Database.php' => '數據庫類',
    'router.php' => '路由器'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description ($file)\n";
    } else {
        echo "❌ $description ($file) - 文件不存在\n";
    }
}
echo "\n";

// 測試 5: 檢查 WebSocket 服務器
echo "5. 檢查 WebSocket 服務器\n";
$socket = @fsockopen('localhost', 8081, $errno, $errstr, 1);
if ($socket) {
    echo "✅ WebSocket 服務器運行中 (端口 8081)\n";
    fclose($socket);
} else {
    echo "❌ WebSocket 服務器未運行 (端口 8081)\n";
    echo "   錯誤: $errstr ($errno)\n";
}
echo "\n";

echo "🎯 測試完成\n";
echo "================\n";

// 提供修復建議
echo "\n💡 修復建議:\n";
echo "1. 確保 WebSocket 服務器運行: php websocket/test_server.php\n";
echo "2. 確保 Web 服務器運行: php -S localhost:8080 router.php\n";
echo "3. 檢查數據庫連接配置\n";
echo "4. 確認所有 API 文件路徑正確\n";
?> 