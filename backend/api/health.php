<?php

/**
 * 健康檢查 API
 * 用於檢查系統各組件的運行狀態
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 健康檢查函數
function checkHealth() {
    $health = [
        'status' => 'healthy',
        'timestamp' => time(),
        'datetime' => date('c'),
        'services' => [],
        'performance' => [],
        'environment' => 'PHP ' . phpversion()
    ];
    
    // 檢查 PHP 服務
    $health['services']['webserver'] = 'running';
    
    // 檢查數據庫連接
    try {
        require_once __DIR__ . '/../classes/Database.php';
        $database = \App\Database::getInstance();
        $dbStatus = $database->getStatus();
        
        // 測試連接
        if ($database->isConnected()) {
            $health['services']['database'] = [
                'status' => 'connected',
                'mode' => $dbStatus['mode'],
                'environment' => $dbStatus['environment'],
                'message' => $dbStatus['mode'] === 'MySQL' ? 'MySQL 數據庫已連接' : '本地存儲模式已啟用'
            ];
            
            // 如果是本地模式，顯示統計信息
            if ($dbStatus['mode'] === 'localStorage') {
                $stats = $database->getLocalStorageStats();
                $health['services']['database']['local_stats'] = $stats;
            }
        } else {
            $health['services']['database'] = [
                'status' => 'disconnected',
                'message' => '數據庫連接失敗'
            ];
            $health['status'] = 'degraded';
        }
        
    } catch (Exception $e) {
        $health['services']['database'] = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
        $health['status'] = 'degraded';
    }
    
    // 檢查 WebSocket 端口
    $websocketPort = 8081;
    $websocketStatus = @fsockopen('localhost', $websocketPort, $errno, $errstr, 1);
    
    if ($websocketStatus) {
        $health['services']['websocket'] = [
            'status' => 'running',
            'port' => $websocketPort,
            'message' => 'WebSocket 服務器正在運行'
        ];
        fclose($websocketStatus);
    } else {
        $health['services']['websocket'] = [
            'status' => 'stopped',
            'port' => $websocketPort,
            'message' => 'WebSocket 服務器未運行',
            'note' => 'WebSocket 服務器應在端口 8081 運行'
        ];
        $health['status'] = 'degraded';
    }
    
    // 檢查 AI 助教配置
    $aiConfig = null;
    $aiConfigPaths = [
        __DIR__ . '/../../ai_config.json',
        __DIR__ . '/../../config/ai.json'
    ];
    
    foreach ($aiConfigPaths as $path) {
        if (file_exists($path)) {
            $aiConfig = json_decode(file_get_contents($path), true);
            break;
        }
    }
    
    $openaiKey = $_ENV['OPENAI_API_KEY'] ?? ($aiConfig['openai_api_key'] ?? null);
    
    if ($openaiKey && strlen($openaiKey) > 10) {
        $health['services']['ai_assistant'] = [
            'status' => 'enabled',
            'message' => 'AI 助教功能已啟用'
        ];
    } else {
        $health['services']['ai_assistant'] = [
            'status' => 'disabled',
            'message' => 'AI 助教功能未配置（需要 OpenAI API 金鑰）'
        ];
    }
    
    // 性能指標
    $health['performance'] = [
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
        'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB',
        'uptime' => getServerUptime(),
        'load_average' => getLoadAverage()
    ];
    
    // 檢查關鍵目錄
    $health['filesystem'] = [
        'data_dir' => is_writable(__DIR__ . '/../../data') ? 'writable' : 'readonly',
        'logs_dir' => is_writable(__DIR__ . '/../../logs') ? 'writable' : 'readonly',
        'temp_dir' => is_writable(sys_get_temp_dir()) ? 'writable' : 'readonly'
    ];
    
    // 系統資源檢查
    $health['system'] = [
        'php_version' => PHP_VERSION,
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        'disk_free' => round(disk_free_space('.') / 1024 / 1024, 2) . ' MB'
    ];
    
    // 檢查必要檔案
    $requiredFiles = [
        'public/index.html',
        'public/teacher-dashboard.html',
        'public/js/websocket.js',
        'public/js/ai-assistant.js',
        'backend/classes/Database.php'
    ];
    
    $missingFiles = [];
    foreach ($requiredFiles as $file) {
        if (!file_exists(__DIR__ . '/../../' . $file)) {
            $missingFiles[] = $file;
        }
    }
    
    if (!empty($missingFiles)) {
        $health['services']['files'] = [
            'status' => 'missing',
            'missing_files' => $missingFiles
        ];
        $health['status'] = 'degraded';
    } else {
        $health['services']['files'] = [
            'status' => 'complete',
            'message' => '所有必要檔案存在'
        ];
    }
    
    return $health;
}

function getServerUptime() {
    if (PHP_OS_FAMILY === 'Windows') {
        return 'N/A (Windows)';
    }
    
    $uptime = @file_get_contents('/proc/uptime');
    if ($uptime) {
        $seconds = floatval(explode(' ', $uptime)[0]);
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return "{$days}d {$hours}h {$minutes}m";
    }
    
    return 'unknown';
}

function getLoadAverage() {
    if (PHP_OS_FAMILY === 'Windows') {
        return 'N/A (Windows)';
    }
    
    $load = sys_getloadavg();
    if ($load) {
        return [
            '1min' => round($load[0], 2),
            '5min' => round($load[1], 2),
            '15min' => round($load[2], 2)
        ];
    }
    
    return 'unknown';
}

// 主要處理邏輯
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit();
    }
    
    $health = checkHealth();
    
    // 根據健康狀態設置 HTTP 狀態碼
    switch ($health['status']) {
        case 'healthy':
            http_response_code(200);
            break;
        case 'degraded':
            http_response_code(200); // 仍然返回 200，但標記為降級
            break;
        default:
            http_response_code(503);
    }
    
    echo json_encode($health, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => time()
    ]);
} 