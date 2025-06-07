<?php
// 命令行AI助教API測試腳本
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧪 AI助教API命令行測試\n";
echo "========================\n\n";

try {
    // 載入必要的類
    require_once __DIR__ . '/backend/classes/AIAssistant.php';
    require_once __DIR__ . '/backend/classes/Logger.php';
    
    echo "✅ 成功載入AI助教類別\n\n";
    
    // 建立AI助教實例
    $ai = new \App\AIAssistant();
    $testUserId = 'cli_test_' . time();
    
    echo "🔧 測試用戶ID: $testUserId\n\n";
    
    // 檢查配置
    $config = require __DIR__ . '/backend/config/openai.php';
    echo "🔧 配置信息：\n";
    echo "   API密鑰: " . (empty($config['api_key']) ? '❌ 未設置' : '✅ 已設置 (' . substr($config['api_key'], 0, 10) . '...)') . "\n";
    echo "   模型: {$config['model']}\n";
    echo "   最大Token: {$config['max_tokens']}\n";
    echo "   溫度: {$config['temperature']}\n\n";
    
    // 檢查是否使用真實API
    if (empty($config['api_key']) || $config['api_key'] === 'your_openai_api_key_here') {
        echo "ℹ️  當前使用模擬響應模式（未設置有效的OpenAI API密鑰）\n\n";
    } else {
        echo "✅ 當前使用真實OpenAI API\n\n";
    }
    
    // 測試1: 解釋程式碼
    echo "1. 測試解釋程式碼功能\n";
    echo "===================\n";
    
    $testCode1 = "def fibonacci(n):\n    if n <= 1:\n        return n\n    return fibonacci(n-1) + fibonacci(n-2)\n\nprint(fibonacci(10))";
    
    echo "測試代碼：\n$testCode1\n\n";
    
    $startTime = microtime(true);
    $result1 = $ai->explainCode($testCode1, $testUserId, 'basic');
    $endTime = microtime(true);
    
    if ($result1['success']) {
        echo "✅ 解釋程式碼功能測試成功\n";
        echo "AI回應：\n" . $result1['analysis'] . "\n";
        echo "執行時間: " . round($endTime - $startTime, 3) . "秒 | Token使用量: {$result1['token_usage']}\n\n";
    } else {
        echo "❌ 解釋程式碼功能測試失敗: " . $result1['error'] . "\n\n";
    }
    
    // 測試2: 檢查錯誤
    echo "2. 測試檢查錯誤功能\n";
    echo "=================\n";
    
    $testCode2 = "def calculate_average(numbers):\n    total = 0\n    for num in numbers:\n        total += num\n    return total / len(numbers)  # 可能除以零\n\nresult = calculate_average([])\nprint(result)";
    
    echo "測試代碼（包含潛在錯誤）：\n$testCode2\n\n";
    
    $startTime = microtime(true);
    $result2 = $ai->checkErrors($testCode2, $testUserId);
    $endTime = microtime(true);
    
    if ($result2['success']) {
        echo "✅ 檢查錯誤功能測試成功\n";
        echo "AI回應：\n" . $result2['analysis'] . "\n";
        echo "執行時間: " . round($endTime - $startTime, 3) . "秒 | Token使用量: {$result2['token_usage']}\n\n";
    } else {
        echo "❌ 檢查錯誤功能測試失敗: " . $result2['error'] . "\n\n";
    }
    
    // 測試3: 改進建議
    echo "3. 測試改進建議功能\n";
    echo "=================\n";
    
    $testCode3 = "def find_max(list):\n    max = list[0]\n    for i in range(1, len(list)):\n        if list[i] > max:\n            max = list[i]\n    return max\n\nnumbers = [1, 5, 3, 9, 2]\nprint(find_max(numbers))";
    
    echo "測試代碼（可改進）：\n$testCode3\n\n";
    
    $startTime = microtime(true);
    $result3 = $ai->suggestImprovements($testCode3, $testUserId);
    $endTime = microtime(true);
    
    if ($result3['success']) {
        echo "✅ 改進建議功能測試成功\n";
        echo "AI回應：\n" . $result3['analysis'] . "\n";
        echo "執行時間: " . round($endTime - $startTime, 3) . "秒 | Token使用量: {$result3['token_usage']}\n\n";
    } else {
        echo "❌ 改進建議功能測試失敗: " . $result3['error'] . "\n\n";
    }
    
    // 測試4: 衝突分析
    echo "4. 測試衝突分析功能\n";
    echo "=================\n";
    
    $originalCode = "def greet(name):\n    return f\"Hello, {name}!\"";
    $conflictCode = "def greet(name, greeting=\"Hi\"):\n    return f\"{greeting}, {name}!\"";
    
    echo "原始代碼：\n$originalCode\n\n";
    echo "衝突代碼：\n$conflictCode\n\n";
    
    $startTime = microtime(true);
    $result4 = $ai->analyzeConflict($originalCode, $conflictCode, $testUserId);
    $endTime = microtime(true);
    
    if ($result4['success']) {
        echo "✅ 衝突分析功能測試成功\n";
        echo "AI回應：\n" . $result4['analysis'] . "\n";
        echo "執行時間: " . round($endTime - $startTime, 3) . "秒 | Token使用量: {$result4['token_usage']}\n\n";
    } else {
        echo "❌ 衝突分析功能測試失敗: " . $result4['error'] . "\n\n";
    }
    
    // 測試5: 詢問問題
    echo "5. 測試詢問問題功能\n";
    echo "=================\n";
    
    $questions = [
        ['question' => 'Python中的list和tuple有什麼差別？', 'category' => 'python_programming'],
        ['question' => '如何在網頁上顯示動態內容？', 'category' => 'web_operation']
    ];
    
    foreach ($questions as $index => $qa) {
        echo "問題 " . ($index + 1) . "：" . $qa['question'] . "\n";
        echo "類別：{$qa['category']}\n\n";
        
        $startTime = microtime(true);
        $result = $ai->answerQuestion($qa['question'], $testUserId, '', $qa['category']);
        $endTime = microtime(true);
        
        if ($result['success']) {
            echo "✅ 問題回答測試成功\n";
            echo "AI回應：\n" . $result['analysis'] . "\n";
            echo "執行時間: " . round($endTime - $startTime, 3) . "秒 | Token使用量: {$result['token_usage']}\n\n";
        } else {
            echo "❌ 問題回答測試失敗: " . $result['error'] . "\n\n";
        }
    }
    
    // 測試總結
    echo "📊 測試總結\n";
    echo "==========\n";
    echo "🎉 所有AI助教功能測試完成！\n";
    echo "測試時間：" . date('Y-m-d H:i:s') . "\n\n";
    
    echo "🎯 下一步驟：\n";
    echo "如果測試成功：\n";
    echo "  ✅ AI助教功能已準備就緒\n";
    echo "  🌐 可以進行Zeabur部署\n";
    echo "  🔗 可以整合到主要編輯器界面\n\n";
    echo "如果測試失敗：\n";
    echo "  🔍 檢查錯誤信息\n";
    echo "  🔑 確認API密鑰設置\n";
    echo "  🌐 檢查網路連接\n";
    
} catch (Exception $e) {
    echo "💥 測試過程中發生錯誤：" . $e->getMessage() . "\n";
    echo "錯誤詳情：\n" . $e->getTraceAsString() . "\n";
}
?> 