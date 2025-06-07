<?php
/**
 * PHP 內建服務器路由文件
 * 處理 API 路由重寫和靜態文件服務
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// 移除查詢參數
$path = strtok($path, '?');

// 調試信息（可選）
error_log("Router: 處理請求 " . $path);

// API 路由重寫
if ($path === '/api/auth') {
    require_once __DIR__ . '/public/api/auth.php';
    return true;
}

if (preg_match('/^\/api\/history/', $path)) {
    require_once __DIR__ . '/public/api/history.php';
    return true;
}

// 檢查文件是否存在於 public 目錄
$file = __DIR__ . DIRECTORY_SEPARATOR . 'public' . str_replace('/', DIRECTORY_SEPARATOR, $path);

// 如果是靜態文件且存在，讓 PHP 內建服務器處理
if (is_file($file)) {
    error_log("Router: 服務靜態文件 " . $file);
    return false; // 讓內建服務器處理
}

// 如果請求的是根路徑，服務 index.html
if ($path === '/' || $path === '/index.html') {
    $indexFile = __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.html';
    if (file_exists($indexFile)) {
        require_once $indexFile;
        return true;
    }
}

// 對於其他不存在的路由，返回404
error_log("Router: 文件不存在 " . $file);
http_response_code(404);
echo "404 - File Not Found: " . $path;
return true;
?> 