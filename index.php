<?php
/**
 * 主入口文件 - Zeabur 部署環境
 * 處理 HTTP 請求並提供基本的 Web 界面
 */

// 設置錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設置響應頭
header('Content-Type: text/html; charset=utf-8');
header('X-Powered-By: PythonLearn-PHP-Collaboration');

// 獲取請求路徑
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// 路由處理
switch ($requestPath) {
    case '/':
    case '/index.html':
        serveFile('public/index.html');
        break;
        
    case '/websocket-test.html':
        serveFile('public/websocket-test.html');
        break;
        
    case '/js/websocket.js':
        serveFile('public/js/websocket.js', 'application/javascript');
        break;
        
    case '/css/style.css':
        serveFile('public/css/style.css', 'text/css');
        break;
        
    case '/api/status':
        apiStatus();
        break;
        
    case '/api/info':
        apiInfo();
        break;
        
    default:
        // 嘗試提供靜態文件
        $filePath = 'public' . $requestPath;
        if (file_exists($filePath) && is_file($filePath)) {
            serveFile($filePath);
        } else {
            show404();
        }
        break;
}

/**
 * 提供靜態文件
 */
function serveFile($filePath, $contentType = null) {
    if (!file_exists($filePath) || !is_file($filePath)) {
        show404();
        return;
    }
    
    if (!$contentType) {
        $contentType = getMimeType($filePath);
    }
    
    header("Content-Type: {$contentType}");
    header('Content-Length: ' . filesize($filePath));
    
    readfile($filePath);
}

/**
 * 獲取 MIME 類型
 */
function getMimeType($filePath) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'html' => 'text/html',
        'htm' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'txt' => 'text/plain'
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}

/**
 * API 狀態端點
 */
function apiStatus() {
    header('Content-Type: application/json');
    
    $status = [
        'service' => 'PythonLearn PHP Collaboration',
        'status' => 'running',
        'mode' => 'http-only',
        'websocket_note' => 'WebSocket 功能需要整合服務器模式',
        'timestamp' => date('c'),
        'php_version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ];
    
    echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * API 信息端點
 */
function apiInfo() {
    header('Content-Type: application/json');
    
    $info = [
        'project' => 'Python 協作學習平台',
        'version' => '2.0',
        'architecture' => 'Pure PHP',
        'deployment' => 'Zeabur Cloud',
        'features' => [
            'HTTP 靜態文件服務',
            'API 端點',
            'WebSocket 支援 (整合服務器模式)'
        ],
        'endpoints' => [
            '/' => '主頁面',
            '/websocket-test.html' => 'WebSocket 測試頁面',
            '/api/status' => '服務狀態',
            '/api/info' => '項目信息'
        ]
    ];
    
    echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * 404 錯誤頁面
 */
function show404() {
    http_response_code(404);
    
    echo '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - 頁面未找到</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #e74c3c; }
        .info { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .links { margin: 20px 0; }
        .links a { display: inline-block; margin: 10px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .links a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>404 - 頁面未找到</h1>
        <p>抱歉，您請求的頁面不存在。</p>
        
        <div class="info">
            <h3>🔧 當前運行模式</h3>
            <p><strong>HTTP 模式</strong> - 提供基本的 Web 服務</p>
            <p>如需完整的 WebSocket 功能，請使用整合服務器模式</p>
        </div>
        
        <div class="links">
            <a href="/">🏠 返回首頁</a>
            <a href="/websocket-test.html">🔌 WebSocket 測試</a>
            <a href="/api/status">📊 服務狀態</a>
            <a href="/api/info">ℹ️ 項目信息</a>
        </div>
    </div>
</body>
</html>';
}
?> 
?> 