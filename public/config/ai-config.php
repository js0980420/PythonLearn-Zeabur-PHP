<?php

/**
 * AI 助教配置文件 - 簡化版
 * 只使用 Zeabur 環境變數 OPENAI_API_KEY
 */

// 從環境變數獲取 API 密鑰
$openai_api_key = $_ENV['OPENAI_API_KEY'] ?? '';

if (empty($openai_api_key)) {
    throw new Exception('OPENAI_API_KEY環境變數未設置。請在Zeabur控制台設置此環境變數。');
}

// OpenAI API 配置
return [
    'openai' => [
        'api_key' => $openai_api_key,
        'api_url' => 'https://api.openai.com/v1/chat/completions',
        'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo',
        'max_tokens' => (int)($_ENV['OPENAI_MAX_TOKENS'] ?? 1000),
        'temperature' => (float)($_ENV['OPENAI_TEMPERATURE'] ?? 0.7),
        'timeout' => (int)($_ENV['OPENAI_TIMEOUT'] ?? 30)
    ],

    'features' => [
        'code_analysis' => true,
        'error_checking' => true,
        'code_suggestions' => true,
        'code_explanation' => true,
        'code_execution_simulation' => true,
        'ai_enabled' => true
    ],

    'rate_limiting' => [
        'enabled' => true,
        'max_requests_per_minute' => (int)($_ENV['AI_RATE_LIMIT_MINUTE'] ?? 20),
        'max_requests_per_hour' => (int)($_ENV['AI_RATE_LIMIT_HOUR'] ?? 100)
    ]
];
