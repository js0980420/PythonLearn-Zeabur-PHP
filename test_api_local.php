<?php
// 本地AI助教API測試腳本
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 AI助教API本地測試</h1>\n";
echo "<meta charset='UTF-8'>\n";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.test-container { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
</style>\n";

try {
    // 載入必要的類
    require_once __DIR__ . '/backend/classes/AIAssistant.php';
    require_once __DIR__ . '/backend/classes/Logger.php';
    
    echo "<div class='info'>✅ 成功載入AI助教類別</div>\n";
    
    // 建立AI助教實例
    $ai = new \App\AIAssistant();
    $testUserId = 'test_user_' . time();
    
    echo "<div class='info'>🔧 測試用戶ID: $testUserId</div>\n";
    
    // 測試1: 解釋程式碼
    echo "<div class='test-container'>\n";
    echo "<h2>1. 測試解釋程式碼功能</h2>\n";
    
    $testCode1 = "def fibonacci(n):
    if n <= 1:
        return n
    return fibonacci(n-1) + fibonacci(n-2)

print(fibonacci(10))";
    
    echo "<strong>測試代碼：</strong>\n";
    echo "<pre>" . htmlspecialchars($testCode1) . "</pre>\n";
    
    $startTime = microtime(true);
    $result1 = $ai->explainCode($testCode1, $testUserId, 'basic');
    $endTime = microtime(true);
    
    if ($result1['success']) {
        echo "<div class='success'>✅ 解釋程式碼功能測試成功</div>\n";
        echo "<strong>AI回應：</strong>\n";
        echo "<pre>" . htmlspecialchars($result1['analysis']) . "</pre>\n";
        echo "<div class='info'>執行時間: " . round($endTime - $startTime, 3) . "秒 | Token使用量: {$result1['token_usage']}</div>\n";
    } else {
        echo "<div class='error'>❌ 解釋程式碼功能測試失敗: " . htmlspecialchars($result1['error']) . "</div>\n";
    }
    echo "</div>\n";
    
    // 測試2: 檢查錯誤
    echo "<div class='test-container'>\n";
    echo "<h2>2. 測試檢查錯誤功能</h2>\n";
    
    $testCode2 = "def calculate_average(numbers):
    total = 0
    for num in numbers:
        total += num
    return total / len(numbers)  # 可能除以零

result = calculate_average([])
print(result)";
    
    echo "<strong>測試代碼（包含潛在錯誤）：</strong>\n";
    echo "<pre>" . htmlspecialchars($testCode2) . "</pre>\n";
    
    $startTime = microtime(true);
    $result2 = $ai->checkErrors($testCode2, $testUserId);
    $endTime = microtime(true);
    
    if ($result2['success']) {
        echo "<div class='success'>✅ 檢查錯誤功能測試成功</div>\n";
        echo "<strong>AI回應：</strong>\n";
        echo "<pre>" . htmlspecialchars($result2['analysis']) . "</pre>\n";
        echo "<div class='info'>執行時間: " . round($endTime - $startTime, 3) . "秒 | Token使用量: {$result2['token_usage']}</div>\n";
    } else {
        echo "<div class='error'>❌ 檢查錯誤功能測試失敗: " . htmlspecialchars($result2['error']) . "</div>\n";
    }
    echo "</div>\n";
    
    // 測試3: 改進建議
    echo "<div class='test-container'>\n";
    echo "<h2>3. 測試改進建議功能</h2>\n";
    
    $testCode3 = "def find_max(list):
    max = list[0]
    for i in range(1, len(list)):
        if list[i] > max:
            max = list[i]
    return max

numbers = [1, 5, 3, 9, 2]
print(find_max(numbers))";
    
    echo "<strong>測試代碼（可改進）：</strong>\n";
    echo "<pre>" . htmlspecialchars($testCode3) . "</pre>\n";
    
    $startTime = microtime(true);
    $result3 = $ai->suggestImprovements($testCode3, $testUserId);
    $endTime = microtime(true);
    
    if ($result3['success']) {
        echo "<div class='success'>✅ 改進建議功能測試成功</div>\n";
        echo "<strong>AI回應：</strong>\n";
        echo "<pre>" . htmlspecialchars($result3['analysis']) . "</pre>\n";
        echo "<div class='info'>執行時間: " . round($endTime - $startTime, 3) . "秒 | Token使用量: {$result3['token_usage']}</div>\n";
    } else {
        echo "<div class='error'>❌ 改進建議功能測試失敗: " . htmlspecialchars($result3['error']) . "</div>\n";
    }
    echo "</div>\n";
    
    // 測試4: 衝突分析
    echo "<div class='test-container'>\n";
    echo "<h2>4. 測試衝突分析功能</h2>\n";
    
    $originalCode = "def greet(name):
    return f\"Hello, {name}!\"";
    
    $conflictCode = "def greet(name, greeting=\"Hi\"):
    return f\"{greeting}, {name}!\"";
    
    echo "<strong>原始代碼：</strong>\n";
    echo "<pre>" . htmlspecialchars($originalCode) . "</pre>\n";
    echo "<strong>衝突代碼：</strong>\n";
    echo "<pre>" . htmlspecialchars($conflictCode) . "</pre>\n";
    
    $startTime = microtime(true);
    $result4 = $ai->analyzeConflict($originalCode, $conflictCode, $testUserId);
    $endTime = microtime(true);
    
    if ($result4['success']) {
        echo "<div class='success'>✅ 衝突分析功能測試成功</div>\n";
        echo "<strong>AI回應：</strong>\n";
        echo "<pre>" . htmlspecialchars($result4['analysis']) . "</pre>\n";
        echo "<div class='info'>執行時間: " . round($endTime - $startTime, 3) . "秒 | Token使用量: {$result4['token_usage']}</div>\n";
    } else {
        echo "<div class='error'>❌ 衝突分析功能測試失敗: " . htmlspecialchars($result4['error']) . "</div>\n";
    }
    echo "</div>\n";
    
    // 測試5: 詢問問題
    echo "<div class='test-container'>\n";
    echo "<h2>5. 測試詢問問題功能</h2>\n";
    
    $questions = [
        ['question' => 'Python中的list和tuple有什麼差別？', 'category' => 'python_programming'],
        ['question' => '如何在網頁上顯示動態內容？', 'category' => 'web_operation']
    ];
    
    foreach ($questions as $index => $qa) {
        echo "<h3>問題 " . ($index + 1) . "：" . htmlspecialchars($qa['question']) . "</h3>\n";
        echo "<strong>類別：</strong> {$qa['category']}\n";
        
        $startTime = microtime(true);
        $result = $ai->answerQuestion($qa['question'], $testUserId, '', $qa['category']);
        $endTime = microtime(true);
        
        if ($result['success']) {
            echo "<div class='success'>✅ 問題回答測試成功</div>\n";
            echo "<strong>AI回應：</strong>\n";
            echo "<pre>" . htmlspecialchars($result['analysis']) . "</pre>\n";
            echo "<div class='info'>執行時間: " . round($endTime - $startTime, 3) . "秒 | Token使用量: {$result['token_usage']}</div>\n";
        } else {
            echo "<div class='error'>❌ 問題回答測試失敗: " . htmlspecialchars($result['error']) . "</div>\n";
        }
        echo "<br>\n";
    }
    echo "</div>\n";
    
    // 測試總結
    echo "<div class='test-container'>\n";
    echo "<h2>📊 測試總結</h2>\n";
    
    // 檢查配置
    $config = require __DIR__ . '/backend/config/openai.php';
    echo "<div class='info'>\n";
    echo "<strong>🔧 配置信息：</strong><br>\n";
    echo "API密鑰: " . (empty($config['api_key']) ? '❌ 未設置' : '✅ 已設置 (' . substr($config['api_key'], 0, 10) . '...)') . "<br>\n";
    echo "模型: {$config['model']}<br>\n";
    echo "最大Token: {$config['max_tokens']}<br>\n";
    echo "溫度: {$config['temperature']}<br>\n";
    echo "</div>\n";
    
    // 檢查是否使用真實API
    if (empty($config['api_key']) || $config['api_key'] === 'your_openai_api_key_here') {
        echo "<div class='info'>ℹ️ 當前使用模擬響應模式（未設置有效的OpenAI API密鑰）</div>\n";
    } else {
        echo "<div class='success'>✅ 當前使用真實OpenAI API</div>\n";
    }
    
    echo "<div class='success'>🎉 所有AI助教功能測試完成！</div>\n";
    echo "<div class='info'><strong>測試時間：</strong>" . date('Y-m-d H:i:s') . "</div>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='error'>💥 測試過程中發生錯誤：" . htmlspecialchars($e->getMessage()) . "</div>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "\n<div style='margin-top: 30px; padding: 20px; background: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 5px;'>\n";
echo "<h3>🎯 下一步驟</h3>\n";
echo "<p><strong>如果測試成功：</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ AI助教功能已準備就緒</li>\n";
echo "<li>🌐 可以進行Zeabur部署</li>\n";
echo "<li>🔗 可以整合到主要編輯器界面</li>\n";
echo "</ul>\n";
echo "<p><strong>如果測試失敗：</strong></p>\n";
echo "<ul>\n";
echo "<li>🔍 檢查錯誤信息</li>\n";
echo "<li>🔑 確認API密鑰設置</li>\n";
echo "<li>🌐 檢查網路連接</li>\n";
echo "</ul>\n";
echo "</div>\n";
?> 