<?php
/**
 * AI API 測試檔案
 * 用於診斷 AI 助教功能的連接和配置問題
 */

// 設定錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🤖 AI API 測試開始...\n";
echo "==========================================\n";

// 1. 檢查 AI 配置
echo "📋 步驟 1: 檢查 AI 配置\n";

$ai_config = null;

// 優先檢查環境變數
if (isset($_ENV['OPENAI_API_KEY']) && !empty($_ENV['OPENAI_API_KEY'])) {
    $ai_config = [
        'api_key' => $_ENV['OPENAI_API_KEY'],
        'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo',
        'max_tokens' => intval($_ENV['OPENAI_MAX_TOKENS'] ?? 1000),
        'temperature' => floatval($_ENV['OPENAI_TEMPERATURE'] ?? 0.3),
        'timeout' => intval($_ENV['OPENAI_TIMEOUT'] ?? 30),
        'source' => 'environment'
    ];
    echo "✅ 找到環境變數配置\n";
} 
// 檢查本地配置檔案
elseif (file_exists('ai_config.json')) {
    $config_content = file_get_contents('ai_config.json');
    $ai_config = json_decode($config_content, true);
    if ($ai_config && isset($ai_config['openai_api_key'])) {
        $ai_config = [
            'api_key' => $ai_config['openai_api_key'],
            'model' => $ai_config['model'] ?? 'gpt-3.5-turbo',
            'max_tokens' => $ai_config['max_tokens'] ?? 1000,
            'temperature' => $ai_config['temperature'] ?? 0.3,
            'timeout' => $ai_config['timeout'] ?? 30,
            'source' => 'local_file'
        ];
        echo "✅ 找到本地配置檔案\n";
    } else {
        echo "❌ 本地配置檔案格式錯誤\n";
    }
} else {
    echo "❌ 未找到 AI 配置\n";
    echo "💡 請設定環境變數 OPENAI_API_KEY 或創建 ai_config.json 檔案\n";
    exit(1);
}

// 顯示配置信息（隱藏 API 密鑰）
echo "📊 配置來源: " . $ai_config['source'] . "\n";
echo "🔑 API 密鑰: " . substr($ai_config['api_key'], 0, 10) . "..." . substr($ai_config['api_key'], -4) . "\n";
echo "🤖 模型: " . $ai_config['model'] . "\n";
echo "📝 最大 tokens: " . $ai_config['max_tokens'] . "\n";
echo "🌡️ 溫度: " . $ai_config['temperature'] . "\n";
echo "⏱️ 超時: " . $ai_config['timeout'] . " 秒\n";

echo "\n";

// 2. 測試 API 連接
echo "📋 步驟 2: 測試 OpenAI API 連接\n";

function testOpenAIAPI($config, $prompt, $test_name) {
    echo "🧪 測試: $test_name\n";
    
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $data = [
        'model' => $config['model'],
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => $config['max_tokens'],
        'temperature' => $config['temperature']
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $config['api_key']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['timeout']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $end_time = microtime(true);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    $response_time = round(($end_time - $start_time) * 1000, 2);
    
    echo "📊 HTTP 狀態碼: $http_code\n";
    echo "⏱️ 響應時間: {$response_time}ms\n";
    
    if ($curl_error) {
        echo "❌ cURL 錯誤: $curl_error\n";
        return false;
    }
    
    if ($http_code !== 200) {
        echo "❌ API 請求失敗\n";
        echo "📄 響應內容: " . substr($response, 0, 500) . "\n";
        return false;
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        echo "❌ 響應格式錯誤\n";
        echo "📄 原始響應: " . substr($response, 0, 500) . "\n";
        return false;
    }
    
    $ai_response = trim($result['choices'][0]['message']['content']);
    echo "✅ API 請求成功\n";
    echo "🤖 AI 回應: " . substr($ai_response, 0, 100) . "...\n";
    
    return $ai_response;
}

// 3. 測試不同的 AI 功能
echo "📋 步驟 3: 測試 AI 功能\n";

$test_code = 'print("Hello, World!")
for i in range(5):
    print(i)';

// 測試代碼解釋功能
echo "\n🔍 測試代碼解釋功能:\n";
$explain_prompt = "請解釋以下 Python 代碼的功能：\n\n```python\n$test_code\n```\n\n請用繁體中文簡潔回答。";
$explain_result = testOpenAIAPI($ai_config, $explain_prompt, "代碼解釋");

echo "\n";

// 測試錯誤檢查功能
echo "🔍 測試錯誤檢查功能:\n";
$error_code = 'print "Hello World"
for i in range(5)
    print(i)';
$error_prompt = "請檢查以下 Python 代碼是否有錯誤：\n\n```python\n$error_code\n```\n\n請指出錯誤並提供修正建議，用繁體中文回答。";
$error_result = testOpenAIAPI($ai_config, $error_prompt, "錯誤檢查");

echo "\n";

// 測試改進建議功能
echo "🔍 測試改進建議功能:\n";
$improve_prompt = "請為以下 Python 代碼提供改進建議：\n\n```python\n$test_code\n```\n\n請用繁體中文提供具體的改進建議。";
$improve_result = testOpenAIAPI($ai_config, $improve_prompt, "改進建議");

echo "\n";

// 4. 總結測試結果
echo "📋 步驟 4: 測試結果總結\n";
echo "==========================================\n";

$success_count = 0;
if ($explain_result) $success_count++;
if ($error_result) $success_count++;
if ($improve_result) $success_count++;

echo "✅ 成功測試: $success_count / 3\n";

if ($success_count === 3) {
    echo "🎉 所有 AI 功能測試通過！\n";
    echo "💡 AI 助教功能應該可以正常工作\n";
} elseif ($success_count > 0) {
    echo "⚠️ 部分 AI 功能正常，可能存在間歇性問題\n";
    echo "💡 建議檢查網路連接和 API 配額\n";
} else {
    echo "❌ 所有 AI 功能測試失敗\n";
    echo "💡 請檢查 API 密鑰是否正確，以及網路連接是否正常\n";
}

echo "\n🔧 如果問題持續存在，請檢查:\n";
echo "1. API 密鑰是否有效且有足夠配額\n";
echo "2. 網路連接是否正常\n";
echo "3. 防火牆是否阻擋了 HTTPS 請求\n";
echo "4. OpenAI 服務是否正常運行\n";

echo "\n🤖 AI API 測試完成！\n";
?> 