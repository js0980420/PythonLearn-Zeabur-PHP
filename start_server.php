<?php
// 簡單的PHP內建伺服器路由器
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// 處理靜態文件
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/', $path)) {
    return false; // 讓內建伺服器處理靜態文件
}

// 處理API請求
if (strpos($path, '/backend/api/') === 0) {
    $apiFile = __DIR__ . $path . '.php';
    if (file_exists($apiFile)) {
        include $apiFile;
        return true;
    }
}

// 處理HTML文件
if ($path === '/' || $path === '/test') {
    include __DIR__ . '/test_ai_functions.html';
    return true;
}

// 處理其他PHP文件
$phpFile = __DIR__ . $path;
if (file_exists($phpFile) && pathinfo($phpFile, PATHINFO_EXTENSION) === 'php') {
    include $phpFile;
    return true;
}

// 404
http_response_code(404);
echo "404 - 頁面未找到";
return true;
?> 