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

// 所有其他請求都重定向到 index.php
require_once __DIR__ . '/index.php'; 