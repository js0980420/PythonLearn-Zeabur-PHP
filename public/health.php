<?php

/**
 * 🏥 系統健康檢查端點 - 純 HTTP 輪詢模式
 * PythonLearn-Zeabur-PHP 專案健康檢查
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$startTime = microtime(true);

try {
    // 🔍 檢查基本系統狀態
    $status = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'version' => '3.0.0',
        'mode' => 'http_polling',
        'platform' => $_ENV['PLATFORM'] ?? 'local',
        'services' => [],
        'performance' => []
    ];

    // 📊 PHP 服務狀態
    $status['services']['php'] = [
        'status' => 'running',
        'version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ];

    // 🌐 HTTP 服務器狀態
    $status['services']['http_server'] = [
        'status' => 'running',
        'port' => $_ENV['HTTP_PORT'] ?? '8080',
        'connection_mode' => 'http_polling'
    ];

    // 📁 文件系統檢查
    $dataDir = __DIR__ . '/../data';
    $storageDir = __DIR__ . '/../storage';

    $status['services']['filesystem'] = [
        'status' => 'ready',
        'data_writable' => is_writable($dataDir),
        'storage_writable' => is_writable($storageDir)
    ];

    // 📊 性能統計
    $status['performance'] = [
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'uptime' => time() - $_SERVER['REQUEST_TIME']
    ];

    // 🗄️ 數據庫檢查（如果配置了）
    if (isset($_ENV['MYSQL_HOST'])) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['MYSQL_HOST'],
                $_ENV['MYSQL_PORT'] ?? '3306',
                $_ENV['MYSQL_DATABASE'] ?? 'pythonlearn'
            );

            $pdo = new PDO($dsn, $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD'], [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $status['services']['database'] = [
                'status' => 'connected',
                'type' => 'mysql',
                'host' => $_ENV['MYSQL_HOST']
            ];
        } catch (Exception $e) {
            $status['services']['database'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    } else {
        $status['services']['database'] = [
            'status' => 'not_configured',
            'note' => 'Using file-based storage'
        ];
    }

    // 🤖 AI 助教檢查
    if (isset($_ENV['OPENAI_API_KEY'])) {
        $status['services']['ai_assistant'] = [
            'status' => 'enabled',
            'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo'
        ];
    } else {
        $status['services']['ai_assistant'] = [
            'status' => 'disabled',
            'note' => 'API key not configured'
        ];
    }

    // 📈 系統負載檢查
    $loadAvg = sys_getloadavg();
    if ($loadAvg !== false) {
        $status['performance']['load_average'] = [
            '1min' => $loadAvg[0],
            '5min' => $loadAvg[1],
            '15min' => $loadAvg[2]
        ];
    }

    // 🎯 整體健康狀態判斷
    $healthyServices = 0;
    $totalServices = 0;

    foreach ($status['services'] as $service) {
        $totalServices++;
        if (in_array($service['status'], ['running', 'connected', 'enabled', 'ready', 'not_configured'])) {
            $healthyServices++;
        }
    }

    $healthRatio = $totalServices > 0 ? $healthyServices / $totalServices : 1;

    if ($healthRatio >= 0.8) {
        $status['status'] = 'healthy';
        http_response_code(200);
    } elseif ($healthRatio >= 0.5) {
        $status['status'] = 'degraded';
        http_response_code(200);
    } else {
        $status['status'] = 'unhealthy';
        http_response_code(503);
    }

    echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // 💥 緊急錯誤處理
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'timestamp' => date('c'),
        'error' => $e->getMessage(),
        'mode' => 'http_polling'
    ], JSON_PRETTY_PRINT);
}
