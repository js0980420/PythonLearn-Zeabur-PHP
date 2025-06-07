<?php
/**
 * 測試修復腳本
 * 驗證API認證和數據庫連接
 */

echo "🧪 開始測試修復...\n\n";

// 1. 測試API認證
echo "1️⃣ 測試API認證端點\n";
echo "==================\n";

// 模擬API請求
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/auth';

// 模擬JSON輸入
$testInput = [
    'action' => 'login',
    'username' => 'Alex Wang',
    'user_type' => 'student'
];

// 開始輸出緩衝
ob_start();

try {
    // 模擬file_get_contents('php://input')
    $GLOBALS['test_input'] = json_encode($testInput);
    
    // 重新定義file_get_contents（僅用於測試）
    function file_get_contents($filename) {
        if ($filename === 'php://input') {
            return $GLOBALS['test_input'];
        }
        return \file_get_contents($filename);
    }
    
    // 包含認證API
    include 'backend/api/auth.php';
    
    $apiOutput = ob_get_clean();
    
    echo "📤 API請求: " . json_encode($testInput, JSON_UNESCAPED_UNICODE) . "\n";
    echo "📥 API響應: " . $apiOutput . "\n";
    
    // 解析響應
    $response = json_decode($apiOutput, true);
    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo "✅ API認證測試成功\n";
        } else {
            echo "❌ API認證測試失敗: " . ($response['message'] ?? '未知錯誤') . "\n";
        }
    } else {
        echo "⚠️ API響應格式異常\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ API測試異常: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. 測試數據庫連接
echo "2️⃣ 測試數據庫連接\n";
echo "==================\n";

try {
    require_once 'classes/Database.php';
    
    $database = new Database();
    $status = $database->getStatus();
    
    echo "📊 數據庫狀態:\n";
    echo "   - 類型: " . ($status['type'] ?? '未知') . "\n";
    echo "   - 連接: " . ($status['connected'] ? '✅ 已連接' : '❌ 未連接') . "\n";
    echo "   - 主機: " . ($status['host'] ?? 'N/A') . "\n";
    echo "   - 端口: " . ($status['port'] ?? 'N/A') . "\n";
    echo "   - 數據庫: " . ($status['database'] ?? 'N/A') . "\n";
    
    // 測試代碼載入
    echo "\n📥 測試代碼載入:\n";
    $codeResult = $database->loadCode('test_room_001');
    
    if ($codeResult && isset($codeResult['success']) && $codeResult['success']) {
        echo "✅ 代碼載入成功\n";
        echo "   - 代碼長度: " . strlen($codeResult['code'] ?? '') . " 字符\n";
        echo "   - 槽位ID: " . ($codeResult['slot_id'] ?? 'N/A') . "\n";
    } else {
        echo "❌ 代碼載入失敗\n";
    }
    
} catch (Exception $e) {
    echo "❌ 數據庫測試異常: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. 測試WebSocket服務器配置
echo "3️⃣ 檢查WebSocket服務器配置\n";
echo "============================\n";

$wsServerFile = 'websocket/server.php';
if (file_exists($wsServerFile)) {
    echo "✅ WebSocket服務器檔案存在\n";
    
    // 檢查關鍵修復
    $content = file_get_contents($wsServerFile);
    
    if (strpos($content, 'loadCode($roomId)') !== false) {
        echo "✅ 包含代碼載入邏輯\n";
    } else {
        echo "❌ 缺少代碼載入邏輯\n";
    }
    
    if (strpos($content, 'current_code') !== false) {
        echo "✅ 包含current_code響應\n";
    } else {
        echo "❌ 缺少current_code響應\n";
    }
    
} else {
    echo "❌ WebSocket服務器檔案不存在\n";
}

echo "\n";

// 4. 檢查前端配置
echo "4️⃣ 檢查前端配置\n";
echo "================\n";

$frontendFiles = [
    'public/js/auto-login.js' => 'auto-login.js',
    'public/js/websocket.js' => 'websocket.js',
    'public/index.html' => 'index.html'
];

foreach ($frontendFiles as $file => $name) {
    if (file_exists($file)) {
        echo "✅ {$name} 存在\n";
    } else {
        echo "❌ {$name} 不存在\n";
    }
}

echo "\n🎯 測試完成\n";

// 5. 提供啟動建議
echo "\n💡 啟動建議\n";
echo "============\n";
echo "1. 啟動PHP服務器: php -S localhost:8080 router.php\n";
echo "2. 啟動WebSocket服務器: cd websocket && php server.php\n";
echo "3. 訪問: http://localhost:8080\n";
echo "4. 檢查瀏覽器控制台是否有錯誤\n";
?> 