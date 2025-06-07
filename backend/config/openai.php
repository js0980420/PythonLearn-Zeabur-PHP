<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// 嘗試從多個來源獲取 API 密鑰
if (!function_exists('getOpenAIApiKey')) {
    function getOpenAIApiKey() {
    // 1. 優先使用環境變數 (生產環境)
    if (!empty($_ENV['OPENAI_API_KEY'])) {
        return $_ENV['OPENAI_API_KEY'];
    }
    
    // 2. 嘗試從本地配置檔案讀取 (開發環境)
    $localConfigPath = __DIR__ . '/../../ai_config.json';
    if (file_exists($localConfigPath)) {
        $localConfig = json_decode(file_get_contents($localConfigPath), true);
        if (!empty($localConfig['openai_api_key'])) {
            return $localConfig['openai_api_key'];
        }
    }
    
    // 3. 返回預設值 (將使用模擬響應)
    return 'your-openai-api-key-here';
    }
}

// 使用環境變數設定API密鑰，部署時需設定OPENAI_API_KEY環境變數
$apiKey = getOpenAIApiKey();
return [
    'api_key' => $apiKey,
    'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo',
    'max_tokens' => intval($_ENV['OPENAI_MAX_TOKENS'] ?? 2048),
    'temperature' => floatval($_ENV['OPENAI_TEMPERATURE'] ?? 0.7),
    'timeout' => intval($_ENV['OPENAI_TIMEOUT'] ?? 60000), // 修正為毫秒
    'enabled' => !empty($apiKey) && $apiKey !== 'your-openai-api-key-here',
    'base_url' => 'https://api.openai.com/v1',
    'headers' => [
        'Content-Type' => 'application/json',
        'User-Agent' => 'Python-Teaching-Platform/1.0'
    ]
]; 