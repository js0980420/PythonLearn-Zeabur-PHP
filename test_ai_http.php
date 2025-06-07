<?php
/**
 * 測試 AI API HTTP 請求
 */

echo "🧪 測試 AI API HTTP 請求...\n";
echo "================================\n";

// 模擬 HTTP 請求環境
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/ai';

// 模擬 POST 數據
$testData = [
    'action' => 'explain',
    'code' => 'print("Hello World")'
];

// 將測試數據寫入 php://input 的模擬
$tempFile = tempnam(sys_get_temp_dir(), 'test_input');
file_put_contents($tempFile, json_encode($testData));

// 重定向 php://input
$originalInput = 'php://input';

// 直接設置 POST 數據
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($testData);

// 捕獲輸出
ob_start();

try {
    echo "📝 包含 AI API 檔案...\n";
    
    // 包含 AI API 檔案
    include __DIR__ . '/backend/api/ai.php';
    
    $output = ob_get_contents();
    echo "✅ API 響應:\n";
    echo $output . "\n";
    
} catch (Exception $e) {
    echo "❌ API 錯誤: " . $e->getMessage() . "\n";
    echo "📍 錯誤位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "📋 錯誤追蹤:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ PHP 錯誤: " . $e->getMessage() . "\n";
    echo "📍 錯誤位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
} finally {
    ob_end_clean();
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
}

echo "\n🎉 測試完成！\n"; 