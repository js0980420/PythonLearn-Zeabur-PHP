<?php

// 簡化的OpenAI配置 - 只使用Zeabur環境變數
$apiKey = $_ENV['OPENAI_API_KEY'] ?? '';

if (empty($apiKey)) {
    throw new Exception('OPENAI_API_KEY環境變數未設置。請在Zeabur控制台設置此環境變數。');
}

return [
    'api_key' => $apiKey,
    'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo',
    'max_tokens' => intval($_ENV['OPENAI_MAX_TOKENS'] ?? 2048),
    'temperature' => floatval($_ENV['OPENAI_TEMPERATURE'] ?? 0.7),
    'timeout' => intval($_ENV['OPENAI_TIMEOUT'] ?? 60000),
    'base_url' => 'https://api.openai.com/v1',
    'headers' => [
        'Content-Type' => 'application/json',
        'User-Agent' => 'Python-Teaching-Platform/1.0'
    ]
]; 