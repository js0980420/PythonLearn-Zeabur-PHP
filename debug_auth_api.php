<?php
/**
 * 調試認證API腳本
 */

// 啟用錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 開始調試認證API...\n\n";

// 1. 檢查檔案路徑
echo "📁 檢查檔案路徑:\n";
$files = [
    'backend/classes/APIResponse.php',
    'backend/classes/Database.php', 
    'backend/api/auth.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} - 存在\n";
    } else {
        echo "❌ {$file} - 不存在\n";
    }
}

echo "\n";

// 2. 測試類別載入
echo "📦 測試類別載入:\n";
try {
    require_once 'backend/classes/APIResponse.php';
    echo "✅ APIResponse 類別載入成功\n";
} catch (Exception $e) {
    echo "❌ APIResponse 載入失敗: " . $e->getMessage() . "\n";
}

try {
    require_once 'backend/classes/Database.php';
    echo "✅ Database 類別載入成功\n";
} catch (Exception $e) {
    echo "❌ Database 載入失敗: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. 測試數據庫連接
echo "🗄️ 測試數據庫連接:\n";
try {
    $database = App\Database::getInstance();
    $status = $database->getStatus();
    echo "✅ 數據庫狀態: " . json_encode($status, JSON_UNESCAPED_UNICODE) . "\n";
} catch (Exception $e) {
    echo "❌ 數據庫連接失敗: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. 模擬API請求
echo "🌐 模擬API請求:\n";
try {
    // 模擬POST請求數據
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [];
    
    // 模擬JSON輸入
    $input = [
        'action' => 'login',
        'username' => 'Alex Wang',
        'user_type' => 'student'
    ];
    
    echo "📤 請求數據: " . json_encode($input, JSON_UNESCAPED_UNICODE) . "\n";
    
    // 開始輸出緩衝
    ob_start();
    
    // 模擬file_get_contents('php://input')
    $GLOBALS['mock_input'] = json_encode($input);
    
    // 重新定義file_get_contents函數（僅用於測試）
    function file_get_contents($filename) {
        if ($filename === 'php://input') {
            return $GLOBALS['mock_input'];
        }
        return \file_get_contents($filename);
    }
    
    // 包含認證API
    include 'backend/api/auth.php';
    
    // 獲取輸出
    $output = ob_get_clean();
    
    echo "📥 API響應: " . $output . "\n";
    
} catch (Exception $e) {
    echo "❌ API測試失敗: " . $e->getMessage() . "\n";
    echo "📍 錯誤位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "📋 錯誤追蹤:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🎯 調試完成\n";
?> 