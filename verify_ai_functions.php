<?php
// AI助教五大功能API串接驗證腳本
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 AI助教五大功能API串接驗證\n";
echo "================================\n\n";

try {
    // 載入AI助教類別
    require_once __DIR__ . '/backend/classes/AIAssistant.php';
    require_once __DIR__ . '/backend/classes/Logger.php';
    
    $ai = new \App\AIAssistant();
    $testUserId = 'verify_' . time();
    
    // 檢查配置
    $config = require __DIR__ . '/backend/config/openai.php';
    echo "📋 API配置狀況：\n";
    echo "   API密鑰：" . (empty($config['api_key']) ? '❌ 未設置' : '✅ 已設置') . "\n";
    echo "   模型：{$config['model']}\n";
    echo "   最大Token：{$config['max_tokens']}\n\n";
    
    // 簡單的測試代碼和問題
    $testCode = "def hello(name):\n    return f'Hello, {name}!'\n\nprint(hello('World'))";
    $testQuestion = "Python中如何定義函數？";
    
    $functions = [
        '1. 解釋程式碼' => function() use ($ai, $testCode, $testUserId) {
            return $ai->explainCode($testCode, $testUserId, 'basic');
        },
        '2. 檢查錯誤' => function() use ($ai, $testCode, $testUserId) {
            return $ai->checkErrors($testCode, $testUserId);
        },
        '3. 改進建議' => function() use ($ai, $testCode, $testUserId) {
            return $ai->suggestImprovements($testCode, $testUserId);
        },
        '4. 衝突分析' => function() use ($ai, $testCode, $testUserId) {
            $conflictCode = "def hello(name, greeting='Hi'):\n    return f'{greeting}, {name}!'\n\nprint(hello('World'))";
            return $ai->analyzeConflict($testCode, $conflictCode, $testUserId);
        },
        '5. 詢問問題' => function() use ($ai, $testQuestion, $testUserId) {
            return $ai->answerQuestion($testQuestion, $testUserId, '', 'python_programming');
        }
    ];
    
    $results = [];
    $totalTime = 0;
    $totalTokens = 0;
    
    foreach ($functions as $name => $func) {
        echo "測試 {$name}：";
        
        $startTime = microtime(true);
        $result = $func();
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        $totalTime += $executionTime;
        
        if ($result['success']) {
            echo " ✅ 成功";
            echo " (時間: " . round($executionTime, 2) . "s";
            if (isset($result['token_usage'])) {
                echo ", Token: {$result['token_usage']}";
                $totalTokens += $result['token_usage'];
            }
            echo ")\n";
            $results[$name] = '✅ 成功';
        } else {
            echo " ❌ 失敗: " . $result['error'] . "\n";
            $results[$name] = '❌ 失敗';
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📊 驗證結果摘要\n";
    echo str_repeat("=", 50) . "\n";
    
    foreach ($results as $func => $status) {
        echo "{$func}: {$status}\n";
    }
    
    $successCount = count(array_filter($results, function($status) {
        return strpos($status, '✅') !== false;
    }));
    
    echo "\n✨ 成功率：{$successCount}/5 (" . round(($successCount/5)*100, 1) . "%)\n";
    echo "⏱️  總執行時間：" . round($totalTime, 2) . "秒\n";
    echo "🔢 總Token使用：{$totalTokens}\n";
    
    if ($successCount == 5) {
        echo "\n🎉 所有功能API串接成功！系統準備就緒。\n";
        echo "✅ 可以進行Zeabur部署\n";
        echo "✅ 可以整合到主編輯器\n";
        echo "✅ 可以開始教學使用\n";
    } else {
        echo "\n⚠️  部分功能存在問題，請檢查：\n";
        echo "- API密鑰設置\n";
        echo "- 網路連接\n";
        echo "- OpenAI服務狀態\n";
    }
    
} catch (Exception $e) {
    echo "💥 驗證過程發生錯誤：" . $e->getMessage() . "\n";
    echo "錯誤位置：" . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n驗證完成時間：" . date('Y-m-d H:i:s') . "\n";
?> 