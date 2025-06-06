<?php
// Zeabur 路由器 - 純 HTTP 請求處理 (WebSocket 由 Caddy 代理)

// 設置錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設置響應頭
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// 獲取請求URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 🌐 靜態檔案處理 - 優先處理
$publicFile = __DIR__ . '/public' . $uri;
if (file_exists($publicFile) && is_file($publicFile)) {
    // 設置正確的 MIME 類型
    $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
    $mimeTypes = [
        'js' => 'application/javascript',
        'css' => 'text/css',
        'html' => 'text/html',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml'
    ];
    
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    
    readfile($publicFile);
    exit();
}

// 🏥 健康檢查端點
if ($uri === '/health') {
    header('Content-Type: application/json');
    
    // 檢查WebSocket服務器狀態 (透過連接測試)
    $wsPort = $_ENV['WEBSOCKET_PORT'] ?? 8081;
    $wsHost = $_ENV['WEBSOCKET_HOST'] ?? '127.0.0.1';
    
    $wsStatus = 'unknown';
    $connection = @fsockopen($wsHost, $wsPort, $errno, $errstr, 1);
    if ($connection) {
        $wsStatus = 'running';
        fclose($connection);
    } else {
        $wsStatus = 'stopped';
    }
    
    $health = [
        'status' => 'healthy',  // PHP 服務總是健康的
        'timestamp' => date('c'),
        'architecture' => 'caddy-proxy',
        'services' => [
            'web_server' => 'running',
            'websocket_server' => $wsStatus,
            'reverse_proxy' => 'caddy'
        ],
        'websocket_config' => [
            'host' => $wsHost,
            'port' => $wsPort,
            'enabled' => $_ENV['WEBSOCKET_ENABLED'] ?? 'true',
            'proxy_path' => '/ws'
        ],
        'environment' => $_ENV['ENVIRONMENT'] ?? 'local',
        'php_version' => PHP_VERSION,
        'zeabur_domain' => $_ENV['ZEABUR_DOMAIN'] ?? null
    ];
    
    echo json_encode($health, JSON_PRETTY_PRINT);
    exit();
}

// 📁 API路由處理
if (preg_match('/^\/backend\/api\/(.+)\.php$/', $uri, $matches)) {
    $apiFile = __DIR__ . '/backend/api/' . $matches[1] . '.php';
    if (file_exists($apiFile)) {
        require_once $apiFile;
        exit();
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
        exit();
    }
}

// 🏠 根路徑處理
if ($uri === '/' || $uri === '/index.html') {
    header('Content-Type: text/html');
    readfile(__DIR__ . '/public/index.html');
    exit();
}

// 📊 教師面板
if ($uri === '/teacher-dashboard.html') {
    header('Content-Type: text/html');
    readfile(__DIR__ . '/public/teacher-dashboard.html');
    exit();
}

// ⚙️ 配置頁面
if ($uri === '/config.html') {
    header('Content-Type: text/html');
    readfile(__DIR__ . '/public/config.html');
    exit();
}

// 🚫 404 處理
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'error' => 'Page not found',
    'uri' => $uri,
    'available_endpoints' => [
        '/' => 'Student interface',
        '/teacher-dashboard.html' => 'Teacher dashboard',
        '/config.html' => 'Configuration page',
        '/health' => 'Health check',
        '/backend/api/' => 'API endpoints',
        '/ws' => 'WebSocket (handled by Caddy)'
    ]
]);
?> 