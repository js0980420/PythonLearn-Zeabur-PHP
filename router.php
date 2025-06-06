<?php
// Zeabur 路由器 - 處理 API 請求和 WebSocket 代理

// 設置錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設置響應頭
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Upgrade, Connection, Sec-WebSocket-Key, Sec-WebSocket-Version, Sec-WebSocket-Protocol');

// 獲取請求URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 🔌 WebSocket 升級請求處理
if (preg_match('/^\/ws/', $uri) || 
    (isset($_SERVER['HTTP_UPGRADE']) && strtolower($_SERVER['HTTP_UPGRADE']) === 'websocket')) {
    
    // 檢查是否為正確的WebSocket升級請求
    $upgrade = $_SERVER['HTTP_UPGRADE'] ?? '';
    $connection = $_SERVER['HTTP_CONNECTION'] ?? '';
    $wsKey = $_SERVER['HTTP_SEC_WEBSOCKET_KEY'] ?? '';
    $wsVersion = $_SERVER['HTTP_SEC_WEBSOCKET_VERSION'] ?? '';
    
    if (strtolower($upgrade) === 'websocket' && 
        strpos(strtolower($connection), 'upgrade') !== false &&
        $wsKey && $wsVersion === '13') {
        
        // 🔄 代理WebSocket請求到內部8081端口
        proxyWebSocketRequest($wsKey);
        exit();
    }
}

// 代理WebSocket請求到內部8081端口
function proxyWebSocketRequest($wsKey) {
    // 生成WebSocket接受密鑰
    $acceptKey = base64_encode(sha1($wsKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    
    // 檢查內部WebSocket服務器是否運行
    $wsPort = $_ENV['WEBSOCKET_PORT'] ?? 8081;
    $wsHost = $_ENV['WEBSOCKET_HOST'] ?? '127.0.0.1';
    
    $internalSocket = @fsockopen($wsHost, $wsPort, $errno, $errstr, 2);
    
    if (!$internalSocket) {
        // WebSocket服務器未運行，返回503錯誤
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'WebSocket服務暫時不可用',
            'message' => 'WebSocket服務器正在啟動中，請稍後重試',
            'retry_after' => 3,
            'debug' => [
                'ws_host' => $wsHost,
                'ws_port' => $wsPort,
                'error' => $errstr
            ]
        ]);
        return;
    }
    
    // 發送WebSocket握手響應
    header('HTTP/1.1 101 Switching Protocols');
    header('Upgrade: websocket');
    header('Connection: Upgrade');
    header('Sec-WebSocket-Accept: ' . $acceptKey);
    
    // 發送WebSocket升級請求到內部服務器
    $upgradeRequest = "GET /ws HTTP/1.1\r\n";
    $upgradeRequest .= "Host: {$wsHost}:{$wsPort}\r\n";
    $upgradeRequest .= "Upgrade: websocket\r\n";
    $upgradeRequest .= "Connection: Upgrade\r\n";
    $upgradeRequest .= "Sec-WebSocket-Key: {$wsKey}\r\n";
    $upgradeRequest .= "Sec-WebSocket-Version: 13\r\n";
    $upgradeRequest .= "\r\n";
    
    fwrite($internalSocket, $upgradeRequest);
    
    // 讀取內部服務器的響應頭
    $response = '';
    while (($line = fgets($internalSocket)) !== false) {
        $response .= $line;
        if (trim($line) === '') break; // 空行表示頭部結束
    }
    
    // 開始雙向代理
    ob_end_flush();
    flush();
    
    // 設置非阻塞模式
    stream_set_blocking(STDIN, false);
    stream_set_blocking($internalSocket, false);
    
    // 代理循環
    while (!feof($internalSocket)) {
        // 從客戶端到內部服務器
        $clientData = fread(STDIN, 4096);
        if ($clientData !== false && $clientData !== '') {
            fwrite($internalSocket, $clientData);
        }
        
        // 從內部服務器到客戶端
        $serverData = fread($internalSocket, 4096);
        if ($serverData !== false && $serverData !== '') {
            echo $serverData;
            flush();
        }
        
        usleep(1000); // 防止CPU占用過高
    }
    
    fclose($internalSocket);
}

// 🌐 靜態檔案處理
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false; // 讓PHP內建伺服器處理靜態檔案
}

// 🏥 健康檢查端點
if ($uri === '/health') {
    header('Content-Type: application/json');
    
    // 檢查WebSocket服務器狀態
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
        'status' => $wsStatus === 'running' ? 'healthy' : 'degraded',
        'timestamp' => date('c'),
        'services' => [
            'web_server' => 'running',
            'websocket_server' => $wsStatus,
            'websocket_config' => [
                'host' => $wsHost,
                'port' => $wsPort,
                'enabled' => $_ENV['WEBSOCKET_ENABLED'] ?? 'true'
            ]
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
    require_once __DIR__ . '/public/index.html';
    exit();
}

// 📊 教師面板
if ($uri === '/teacher-dashboard.html') {
    require_once __DIR__ . '/public/teacher-dashboard.html';
    exit();
}

// ⚙️ 配置頁面
if ($uri === '/config.html') {
    require_once __DIR__ . '/public/config.html';
    exit();
}

// 🎯 公開檔案處理
$publicFile = __DIR__ . '/public' . $uri;
if (file_exists($publicFile)) {
    return false; // 讓PHP內建伺服器處理
}

// 🚫 404 處理
http_response_code(404);
echo json_encode([
    'error' => 'Not Found',
    'uri' => $uri,
    'message' => '請求的資源不存在'
]);
?> 