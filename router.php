<?php
/**
 * PHP 內建服務器路由文件
 * 處理 API 路由重寫和靜態文件服務
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// 移除查詢參數
$path = strtok($path, '?');

// 調試信息
error_log("Router: 處理請求 " . $path);

// 統一 API 處理 - 支援 /api.php 路徑
if ($path === '/api.php') {
    error_log("Router: 路由到 /api.php");
    $_SERVER['SCRIPT_NAME'] = '/api.php';
    require_once __DIR__ . '/public/api.php';
    return true;
}

// API 路由重寫
if ($path === '/api/auth') {
    error_log("Router: 路由到 /api/auth");
    require_once __DIR__ . '/public/api/auth.php';
    return true;
}

if (preg_match('/^\/api\/history/', $path)) {
    error_log("Router: 路由到 /api/history");
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
if ($path === '/' || $path === '') {
    error_log("Router: 服務根路徑，重定向到 index.html");
    $indexFile = __DIR__ . '/public/index.html';
    if (file_exists($indexFile)) {
        return false; // 讓內建服務器處理
    }
}

// 如果沒有找到文件，返回 404
error_log("Router: 404 - 文件未找到: " . $path);
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => '頁面未找到',
    'path' => $path
]);
return true;
?> 