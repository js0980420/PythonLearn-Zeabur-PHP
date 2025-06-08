<?php
/**
 * Router.php - Zeabur 兼容性路由文件
 * 重定向所有請求到 index.php 主入口文件
 */

// 檢查是否為 PHP 內建服務器
if (php_sapi_name() === 'cli-server') {
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);
    
    // 如果請求的是靜態文件且文件存在，直接返回
    if ($path !== '/' && file_exists(__DIR__ . '/public' . $path)) {
        return false; // 讓內建服務器處理靜態文件
    }
}

$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// 移除查詢字符串
$path = parse_url($request_uri, PHP_URL_PATH);

// 記錄請求
error_log("Router: {$request_method} {$path}");

// API 路由處理
if (strpos($path, '/api/') === 0) {
    // WebSocket 降級 API
    if ($path === '/api/websocket' && $request_method === 'POST') {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
            exit;
        }
        
        // 簡單的消息處理 (HTTP 模式降級)
        $response = [
            'success' => true,
            'message' => 'Message received in HTTP mode',
            'type' => $input['type'] ?? 'unknown',
            'timestamp' => date('c'),
            'note' => 'WebSocket not available, using HTTP fallback'
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // 狀態檢查 API
    if ($path === '/api/status' && $request_method === 'GET') {
        header('Content-Type: application/json');
        
        $status = [
            'success' => true,
            'status' => 'healthy',
            'mode' => 'http',
            'websocket_available' => false,
            'timestamp' => date('c'),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'uptime' => time()
            ]
        ];
        
        echo json_encode($status);
        exit;
    }
    
    // 歷史記錄 API
    if ($path === '/api/history' && $request_method === 'GET') {
        header('Content-Type: application/json');
        
        $room_id = $_GET['room_id'] ?? 'default';
        
        // 模擬歷史記錄數據
        $history = [
            [
                'id' => 1,
                'room_id' => $room_id,
                'code' => "# 歷史記錄範例\nprint('Hello, World!')",
                'saved_at' => date('c', time() - 3600),
                'saved_by' => 'user1'
            ],
            [
                'id' => 2,
                'room_id' => $room_id,
                'code' => "# 更新的代碼\nfor i in range(5):\n    print(f'數字: {i}')",
                'saved_at' => date('c', time() - 1800),
                'saved_by' => 'user2'
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'history' => $history,
            'room_id' => $room_id,
            'count' => count($history)
        ]);
        exit;
    }
    
    // 其他 API 請求轉發到 index.php
    require_once 'index.php';
    exit;
}

// WebSocket 請求處理 (降級到 HTTP)
if ($path === '/ws') {
    header('Content-Type: application/json');
    http_response_code(426); // Upgrade Required
    
    echo json_encode([
        'error' => 'WebSocket not supported in this environment',
        'message' => 'Please use HTTP API endpoints instead',
        'fallback_endpoint' => '/api/websocket',
        'status_endpoint' => '/api/status'
    ]);
    exit;
}

// 靜態文件處理
$file_path = __DIR__ . '/public' . $path;

// 如果是根路徑，重定向到 index.html
if ($path === '/' || $path === '') {
    $file_path = __DIR__ . '/public/index.html';
}

// 檢查文件是否存在
if (file_exists($file_path) && is_file($file_path)) {
    // 設置正確的 MIME 類型
    $mime_types = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject'
    ];
    
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_type = $mime_types[$extension] ?? 'application/octet-stream';
    
    header("Content-Type: {$mime_type}");
    
    // 對於文本文件，添加字符編碼
    if (in_array($extension, ['html', 'css', 'js', 'json'])) {
        header("Content-Type: {$mime_type}; charset=utf-8");
    }
    
    // 輸出文件內容
    readfile($file_path);
    exit;
}

// 文件不存在，返回 404
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html>
<html>
<head>
    <title>404 - 頁面不存在</title>
    <meta charset="utf-8">
</head>
<body>
    <h1>404 - 頁面不存在</h1>
    <p>請求的頁面 "' . htmlspecialchars($path) . '" 不存在。</p>
    <p><a href="/">返回首頁</a></p>
</body>
</html>';
?> 