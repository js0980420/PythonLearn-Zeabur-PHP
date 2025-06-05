<?php

// 獲取請求 URI
$requestUri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 定義 API 前綴
$apiPrefix = '/backend/api/';

// 檢查是否為 API 請求
if (strpos($requestUri, $apiPrefix) === 0) {
    // 提取 API 檔案路徑
    $apiPath = substr($requestUri, strlen($apiPrefix));
    $targetFile = __DIR__ . '/backend/api/' . $apiPath;

    // 確保路徑安全，防止目錄遍歷
    if (strpos($apiPath, '..') !== false) {
        http_response_code(403); // Forbidden
        echo 'Access Denied: Invalid path';
        exit();
    }

    // 檢查檔案是否存在
    if (file_exists($targetFile) && is_file($targetFile)) {
        // 包含 API 檔案
        require_once $targetFile;
        exit();
    } else {
        // API 檔案不存在，返回 404
        http_response_code(404);
        echo 'API Not Found';
        exit();
    }
} else {
    // 如果不是 API 請求，則嘗試服務靜態檔案
    $filePath = __DIR__ . $requestUri;

    // 如果請求的是根目錄，導向到 index.html
    if ($requestUri === '/') {
        $filePath = __DIR__ . '/index.html';
    }

    if (file_exists($filePath) && is_file($filePath)) {
        // 獲取檔案類型並設置 Content-Type
        $mimeType = mime_content_type($filePath);
        if ($mimeType) {
            header("Content-Type: {$mimeType}");
        }
        readfile($filePath);
        exit();
    } elseif (strpos($requestUri, '.php') !== false) {
        // 如果是其他 .php 檔案，直接執行（例如 teacher-dashboard.php 或其他）
        $targetPhpFile = __DIR__ . $requestUri;
        if (file_exists($targetPhpFile) && is_file($targetPhpFile)) {
            require_once $targetPhpFile;
            exit();
        } else {
            http_response_code(404);
            echo 'File Not Found';
            exit();
        }
    } else {
        // 如果不是 API 請求也不是靜態檔案，返回 404
        http_response_code(404);
        echo 'Page Not Found';
        exit();
    }
}

?> 