<?php

/**
 * AI配置測試文件
 * 用於檢查本地和Zeabur環境的API Key配置狀況
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // 載入AI配置
    $aiConfig = require_once __DIR__ . '/config/ai-config.php';

    // 檢查各種環境變數來源
    $env_checks = [
        'env_direct' => $_ENV['OPENAI_API_KEY'] ?? null,
        'getenv_direct' => getenv('OPENAI_API_KEY') ?: null,
        'server_direct' => $_SERVER['OPENAI_API_KEY'] ?? null
    ];

    // 檢查本地配置文件
    $local_config_path = __DIR__ . '/../ai_config.json';
    $local_config_exists = file_exists($local_config_path);
    $local_config_content = null;
    $local_config_valid = false;

    if ($local_config_exists) {
        $local_config_content = json_decode(file_get_contents($local_config_path), true);
        $local_config_valid = !empty($local_config_content['openai_api_key']) &&
            $local_config_content['openai_api_key'] !== 'your_openai_api_key_here';
    }

    // 安全地顯示API Key（隱藏敏感部分）
    function maskApiKey($key)
    {
        if (empty($key) || $key === 'your_openai_api_key_here') {
            return $key;
        }
        if (strlen($key) < 10) {
            return '***';
        }
        return substr($key, 0, 8) . '***' . substr($key, -4);
    }

    // 檢測環境類型
    $environment_type = 'unknown';
    if (!empty($_SERVER['HTTP_HOST'])) {
        if (strpos($_SERVER['HTTP_HOST'], 'zeabur.app') !== false) {
            $environment_type = 'zeabur';
        } elseif (
            strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
            strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
        ) {
            $environment_type = 'local';
        }
    }

    // 構建回應
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => [
            'type' => $environment_type,
            'host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'platform' => $aiConfig['deployment']['platform'] ?? 'unknown'
        ],
        'api_key_sources' => [
            'env_var_direct' => [
                'value' => maskApiKey($env_checks['env_direct']),
                'available' => !empty($env_checks['env_direct'])
            ],
            'getenv_direct' => [
                'value' => maskApiKey($env_checks['getenv_direct']),
                'available' => !empty($env_checks['getenv_direct'])
            ],
            'server_direct' => [
                'value' => maskApiKey($env_checks['server_direct']),
                'available' => !empty($env_checks['server_direct'])
            ],
            'local_config' => [
                'file_exists' => $local_config_exists,
                'valid' => $local_config_valid,
                'value' => $local_config_valid ? maskApiKey($local_config_content['openai_api_key']) : null
            ]
        ],
        'final_config' => [
            'api_key' => maskApiKey($aiConfig['openai']['api_key']),
            'config_source' => $aiConfig['deployment']['config_source'] ?? 'unknown',
            'is_production' => $aiConfig['openai']['is_production'] ?? false,
            'api_key_configured' => $aiConfig['deployment']['api_key_configured'] ?? false,
            'ai_enabled' => $aiConfig['features']['ai_enabled'] ?? false
        ],
        'recommendations' => []
    ];

    // 根據環境給出建議
    if ($environment_type === 'local') {
        if (!$local_config_valid) {
            $response['recommendations'][] = '本地環境：請複製 ai_config.json.example 為 ai_config.json 並設置您的 OpenAI API Key';
            $response['recommendations'][] = '指令：cp ai_config.json.example ai_config.json';
        } else {
            $response['recommendations'][] = '本地環境：API Key 配置正確！';
        }
    } elseif ($environment_type === 'zeabur') {
        if (empty($env_checks['env_direct']) && empty($env_checks['getenv_direct'])) {
            $response['recommendations'][] = 'Zeabur環境：請在 Zeabur 控制台設置環境變數 OPENAI_API_KEY';
            $response['recommendations'][] = '設置位置：Project Settings → Environment Variables';
        } else {
            $response['recommendations'][] = 'Zeabur環境：環境變數設置正確！';
        }
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
