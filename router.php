<?php
/**
 * PHP 內建服務器路由器
 * 處理靜態檔案和API請求路由
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// 移除查詢參數
$cleanPath = strtok($path, '?');

// 設定正確的CORS頭部
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// 處理預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API 路由處理
if (strpos($cleanPath, '/api/') === 0) {
    // 移除 /api 前綴
    $apiPath = substr($cleanPath, 4);
    
    // 根據路徑路由到相應的API檔案
    switch (true) {
        case strpos($apiPath, '/auth') === 0:
            require_once __DIR__ . '/backend/api/auth.php';
            break;
            
        case strpos($apiPath, '/rooms') === 0:
            require_once __DIR__ . '/backend/api/rooms.php';
            break;
            
        case strpos($apiPath, '/code') === 0:
            require_once __DIR__ . '/backend/api/code.php';
            break;
            
        case strpos($apiPath, '/history') === 0:
            require_once __DIR__ . '/backend/api/history.php';
            break;
            
        case strpos($apiPath, '/ai') === 0:
            require_once __DIR__ . '/backend/api/ai.php';
            break;
            
        case strpos($apiPath, '/teacher') === 0:
            require_once __DIR__ . '/backend/api/teacher.php';
            break;
            
        case strpos($apiPath, '/health') === 0:
        case $apiPath === '/health':
            require_once __DIR__ . '/backend/api/health.php';
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "API端點不存在: $apiPath",
                'timestamp' => date('c'),
                'available_endpoints' => [
                    '/api/auth',
                    '/api/rooms', 
                    '/api/code',
                    '/api/history',
                    '/api/ai',
                    '/api/teacher',
                    '/api/health'
                ]
            ]);
            break;
    }
    return;
}

// 專門處理健康檢查
if ($cleanPath === '/health' || $cleanPath === '/health.php') {
    require_once __DIR__ . '/public/health.php';
    return;
}

// 靜態檔案處理
$publicDir = __DIR__ . '/public';
$filePath = $publicDir . $cleanPath;

// 根目錄重定向到 index.html
if ($cleanPath === '/') {
    $filePath = $publicDir . '/index.html';
}

// 檢查檔案是否存在
if (file_exists($filePath) && is_file($filePath)) {
    // 設定適當的Content-Type
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    
    switch ($ext) {
        case 'html':
            header('Content-Type: text/html; charset=utf-8');
            break;
        case 'css':
            header('Content-Type: text/css');
            break;
        case 'js':
            header('Content-Type: application/javascript');
            break;
        case 'json':
            header('Content-Type: application/json');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
        case 'gif':
            header('Content-Type: image/gif');
            break;
        case 'ico':
            header('Content-Type: image/x-icon');
            break;
        default:
            header('Content-Type: text/plain');
            break;
    }
    
    // 讀取並輸出檔案內容
    readfile($filePath);
    return;
}

// 檔案不存在，返回404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - 檔案不存在</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .error-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        h1 {
            font-size: 6rem;
            margin: 0;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        h2 {
            font-size: 2rem;
            margin: 1rem 0;
            font-weight: 300;
        }
        p {
            font-size: 1.1rem;
            margin: 1rem 0;
            opacity: 0.8;
        }
        .code {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            display: inline-block;
            margin: 1rem 0;
        }
        .home-link {
            display: inline-block;
            margin-top: 2rem;
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: transform 0.3s ease;
        }
        .home-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <h2>檔案不存在</h2>
        <p>抱歉，您請求的檔案無法找到。</p>
        <div class="code"><?= htmlspecialchars($cleanPath) ?></div>
        <br>
        <a href="/" class="home-link">🏠 返回首頁</a>
    </div>
</body>
</html> 