<?php

require_once 'backend/classes/AIAssistant.php';
require_once 'backend/classes/Logger.php';

// 設置錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AI助教功能測試</h1>\n";
echo "<meta charset='UTF-8'>\n";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.test-section { border: 1px solid #ddd; margin: 20px 0; padding: 15px; }
.success { color: green; }
.error { color: red; }
.result { background: #f5f5f5; padding: 10px; margin: 10px 0; }
pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
</style>\n";

try {
    // 建立AI助教實例
    $ai = new \App\AIAssistant();
    $userId = 'test_user_' . time();
    
    echo "<div class='test-section'>\n";
    echo "<h2>1. 解釋程式碼功能測試</h2>\n";
    
    $testCode = "def fibonacci(n):
    if n <= 1:
        return n
    return fibonacci(n-1) + fibonacci(n-2)

print(fibonacci(10))";
    
    echo "<h3>測試代碼：</h3>\n";
    echo "<pre>$testCode</pre>\n";
    
    $result = $ai->explainCode($testCode, $userId, 'basic');
    
    if ($result['success']) {
        echo "<div class='success'>✅ 解釋程式碼功能測試成功</div>\n";
        echo "<div class='result'><strong>AI回應：</strong><br>" . nl2br(htmlspecialchars($result['analysis'])) . "</div>\n";
        echo "<div>執行時間：{$result['execution_time']}秒，Token使用量：{$result['token_usage']}</div>\n";
    } else {
        echo "<div class='error'>❌ 解釋程式碼功能測試失敗：" . htmlspecialchars($result['error']) . "</div>\n";
    }
    echo "</div>\n";
    
    echo "<div class='test-section'>\n";
    echo "<h2>2. 檢查錯誤功能測試</h2>\n";
    
    $buggyCode = "def calculate_average(numbers):
    total = 0
    for num in numbers:
        total += num
    return total / len(numbers)  # 可能除以零

# 測試代碼
result = calculate_average([])
print(result)";
    
    echo "<h3>測試代碼（包含潛在錯誤）：</h3>\n";
    echo "<pre>$buggyCode</pre>\n";
    
    $result = $ai->checkErrors($buggyCode, $userId);
    
    if ($result['success']) {
        echo "<div class='success'>✅ 檢查錯誤功能測試成功</div>\n";
        echo "<div class='result'><strong>AI回應：</strong><br>" . nl2br(htmlspecialchars($result['analysis'])) . "</div>\n";
        echo "<div>執行時間：{$result['execution_time']}秒，Token使用量：{$result['token_usage']}</div>\n";
    } else {
        echo "<div class='error'>❌ 檢查錯誤功能測試失敗：" . htmlspecialchars($result['error']) . "</div>\n";
    }
    echo "</div>\n";
    
    echo "<div class='test-section'>\n";
    echo "<h2>3. 改進建議功能測試</h2>\n";
    
    $improvableCode = "def find_max(list):
    max = list[0]
    for i in range(1, len(list)):
        if list[i] > max:
            max = list[i]
    return max

numbers = [1, 5, 3, 9, 2]
print(find_max(numbers))";
    
    echo "<h3>測試代碼（可改進）：</h3>\n";
    echo "<pre>$improvableCode</pre>\n";
    
    $result = $ai->suggestImprovements($improvableCode, $userId);
    
    if ($result['success']) {
        echo "<div class='success'>✅ 改進建議功能測試成功</div>\n";
        echo "<div class='result'><strong>AI回應：</strong><br>" . nl2br(htmlspecialchars($result['analysis'])) . "</div>\n";
        echo "<div>執行時間：{$result['execution_time']}秒，Token使用量：{$result['token_usage']}</div>\n";
    } else {
        echo "<div class='error'>❌ 改進建議功能測試失敗：" . htmlspecialchars($result['error']) . "</div>\n";
    }
    echo "</div>\n";
    
    echo "<div class='test-section'>\n";
    echo "<h2>4. 衝突分析功能測試</h2>\n";
    
    $originalCode = "def greet(name):
    return f\"Hello, {name}!\"";
    
    $conflictedCode = "def greet(name, greeting=\"Hi\"):
    return f\"{greeting}, {name}!\"";
    
    echo "<h3>原始代碼：</h3>\n";
    echo "<pre>$originalCode</pre>\n";
    echo "<h3>衝突代碼：</h3>\n";
    echo "<pre>$conflictedCode</pre>\n";
    
    $result = $ai->analyzeConflict($originalCode, $conflictedCode, $userId);
    
    if ($result['success']) {
        echo "<div class='success'>✅ 衝突分析功能測試成功</div>\n";
        echo "<div class='result'><strong>AI回應：</strong><br>" . nl2br(htmlspecialchars($result['analysis'])) . "</div>\n";
        echo "<div>執行時間：{$result['execution_time']}秒，Token使用量：{$result['token_usage']}</div>\n";
    } else {
        echo "<div class='error'>❌ 衝突分析功能測試失敗：" . htmlspecialchars($result['error']) . "</div>\n";
    }
    echo "</div>\n";
    
    echo "<div class='test-section'>\n";
    echo "<h2>5. 詢問問題功能測試</h2>\n";
    
    $questions = [
        ['question' => 'Python中的list和tuple有什麼差別？', 'category' => 'python_programming'],
        ['question' => '如何在網頁上顯示動態內容？', 'category' => 'web_operation']
    ];
    
    foreach ($questions as $index => $qa) {
        echo "<h3>問題 " . ($index + 1) . "：{$qa['question']}</h3>\n";
        
        $result = $ai->answerQuestion($qa['question'], $userId, '', $qa['category']);
        
        if ($result['success']) {
            echo "<div class='success'>✅ 問題回答測試成功</div>\n";
            echo "<div class='result'><strong>AI回應：</strong><br>" . nl2br(htmlspecialchars($result['analysis'])) . "</div>\n";
            echo "<div>執行時間：{$result['execution_time']}秒，Token使用量：{$result['token_usage']}</div>\n";
        } else {
            echo "<div class='error'>❌ 問題回答測試失敗：" . htmlspecialchars($result['error']) . "</div>\n";
        }
        echo "<br>\n";
    }
    echo "</div>\n";
    
    echo "<div class='test-section'>\n";
    echo "<h2>📊 測試總結</h2>\n";
    echo "<div class='success'>所有AI助教功能測試已完成！</div>\n";
    echo "<p><strong>測試用戶ID：</strong>$userId</p>\n";
    echo "<p><strong>測試時間：</strong>" . date('Y-m-d H:i:s') . "</p>\n";
    
    // 檢查使用統計
    try {
        $stats = $ai->getUsageStats($userId, '1h');
        if ($stats) {
            echo "<h3>使用統計（1小時內）：</h3>\n";
            echo "<ul>\n";
            echo "<li>總請求數：{$stats['total_requests']}</li>\n";
            echo "<li>總Token使用量：{$stats['total_tokens']}</li>\n";
            echo "<li>平均響應時間：" . round($stats['avg_response_time'], 3) . "秒</li>\n";
            echo "<li>失敗請求數：{$stats['failed_requests']}</li>\n";
            echo "</ul>\n";
        }
    } catch (Exception $e) {
        echo "<div class='error'>統計信息獲取失敗：" . htmlspecialchars($e->getMessage()) . "</div>\n";
    }
    
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='error'>測試過程中發生錯誤：" . htmlspecialchars($e->getMessage()) . "</div>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "\n<div style='margin-top: 30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196F3;'>\n";
echo "<h3>🎯 下一步驟</h3>\n";
echo "<p>如果所有測試都成功，表示AI助教功能已準備好進行實際使用。</p>\n";
echo "<p>您可以繼續整合這些功能到主要的編輯器界面中。</p>\n";
echo "</div>\n";
?> 