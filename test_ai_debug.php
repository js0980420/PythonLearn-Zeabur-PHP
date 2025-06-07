<?php
// 啟用錯誤顯示來調試
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "開始測試AI API...\n";

try {
    // 測試基本依賴
    echo "1. 測試autoload...\n";
    require_once 'vendor/autoload.php';
    echo "✅ autoload成功\n";
    
    // 測試配置檔案
    echo "2. 測試配置檔案...\n";
    $config = require 'backend/config/openai.php';
    echo "✅ 配置檔案載入成功\n";
    echo "API Key: " . (isset($config['api_key']) ? substr($config['api_key'], 0, 10) . '...' : '未設置') . "\n";
    
    // 測試Database類
    echo "3. 測試Database類...\n";
    require_once 'backend/classes/Database.php';
    $database = App\Database::getInstance();
    echo "✅ Database類載入成功\n";
    
    // 測試AIAssistant類
    echo "4. 測試AIAssistant類...\n";
    require_once 'backend/classes/AIAssistant.php';
    $aiAssistant = new App\AIAssistant();
    echo "✅ AIAssistant類載入成功\n";
    
    // 測試API調用
    echo "5. 測試API調用...\n";
    $result = $aiAssistant->checkErrors('print("hello")', 'test_user');
    echo "✅ API調用成功\n";
    echo "結果: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    echo "檔案: " . $e->getFile() . "\n";
    echo "行號: " . $e->getLine() . "\n";
    echo "堆疊追蹤:\n" . $e->getTraceAsString() . "\n";
} 