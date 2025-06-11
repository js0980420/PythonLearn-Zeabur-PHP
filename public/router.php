<?php
// 🚀 PythonLearn Zeabur 路由器 v3.0
// 修復記憶體問題，優化靜態文件處理

// 📊 記憶體限制設置
ini_set('memory_limit', '128M');

// 🔍 獲取請求路徑
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

// 🧹 清理路徑
$path = ltrim($path, '/');

// 📁 靜態文件優先處理
$staticExtensions = ['js', 'css', 'html', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf'];
$pathParts = pathinfo($path);
$extension = $pathParts['extension'] ?? '';

// 🎯 檢查是否為靜態文件請求
if (in_array($extension, $staticExtensions)) {
    // 🔍 在當前目錄尋找文件
    $filePath = __DIR__ . '/' . $path;

    if (file_exists($filePath) && is_file($filePath)) {
        // ✅ 直接提供靜態文件
        $mimeTypes = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'html' => 'text/html',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml'
        ];

        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        header("Content-Type: $mimeType");
        header("Cache-Control: public, max-age=3600");
        readfile($filePath);
        exit;
    } else {
        // ❌ 靜態文件不存在
        http_response_code(404);
        echo "404 - Static File Not Found: /$path";
        exit;
    }
}

// 🔄 API 路由處理
if (strpos($path, 'api.php') !== false || strpos($path, 'api/') !== false) {
    // 📡 API 請求路由到 api.php
    if (file_exists(__DIR__ . '/api.php')) {
        include __DIR__ . '/api.php';
        exit;
    }
}

// 🎓 教師監控後台路由
if ($path === 'teacher' || $path === 'teacher-dashboard') {
    if (file_exists(__DIR__ . '/teacher-dashboard.html')) {
        header('Content-Type: text/html');
        include __DIR__ . '/teacher-dashboard.html';
        exit;
    } else {
        http_response_code(404);
        echo "404 - Teacher Dashboard Not Found";
        exit;
    }
}

// 🏠 預設路由到首頁
if (empty($path) || $path === 'index.html' || $path === '/') {
    if (file_exists(__DIR__ . '/index.html')) {
        header('Content-Type: text/html');
        include __DIR__ . '/index.html';
        exit;
    }
}

// ❌ 404 處理
http_response_code(404);
echo "404 - Page Not Found: /$path";
exit;
