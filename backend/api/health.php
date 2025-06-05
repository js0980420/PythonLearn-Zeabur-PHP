<?php
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
        require_once __DIR__ . '/../classes/MockDatabase.php';
        $db = App\MockDatabase::getInstance();
        $health['services']['database'] = 'connected';
    } catch (Exception $e) {
        $health['services']['database'] = 'error: ' . $e->getMessage();
        $health['status'] = 'degraded';
    }
    
    // 檢查 WebSocket 服務器
    $websocketStatus = checkWebSocketServer();
    $health['services']['websocket'] = $websocketStatus;
    if ($websocketStatus !== 'running') {
        $health['status'] = 'degraded';
    }
    
    // 檢查 AI 服務
    $aiStatus = checkAIService();
    $health['services']['ai_assistant'] = $aiStatus;
    
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
    
    return $health;
}

function checkWebSocketServer() {
    // 嘗試連接到 WebSocket 端口
    $host = 'localhost';
    $port = 8080;
    
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($connection) {
        fclose($connection);
        return 'running';
    } else {
        return 'stopped (port not listening)';
    }
}

function checkAIService() {
    // 檢查 OpenAI API 密鑰配置
    $apiKey = $_ENV['OPENAI_API_KEY'] ?? null;
    
    if (!$apiKey) {
        return 'disabled (no API key)';
    }
    
    if (strpos($apiKey, 'sk-') === 0) {
        return 'enabled';
    } else {
        return 'misconfigured (invalid API key format)';
    }
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