<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// 使用環境變數設定API密鑰，部署時需設定OPENAI_API_KEY環境變數
return [
    'api_key' => $_ENV['OPENAI_API_KEY'] ?? 'your-openai-api-key-here',
    'model' => 'gpt-3.5-turbo',
    'max_tokens' => 2048,
    'temperature' => 0.7,
    'timeout' => 30,
    'base_url' => 'https://api.openai.com/v1',
    'headers' => [
        'Content-Type' => 'application/json',
        'User-Agent' => 'Python-Teaching-Platform/1.0'
    ]
]; 