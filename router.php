<?php
// Zeabur 路由器 - 處理 API 請求和 WebSocket 代理

// 設置錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 防止目錄遍歷攻擊
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, '..') !== false) {
    http_response_code(403);
    exit('Forbidden');
}

// 設置 CORS 標頭
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Upgrade, Connection, Sec-WebSocket-Key, Sec-WebSocket-Version, Sec-WebSocket-Protocol');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 🔌 WebSocket 升級請求處理
if (preg_match('/^\/ws/', $uri) || 
    (isset($_SERVER['HTTP_UPGRADE']) && strtolower($_SERVER['HTTP_UPGRADE']) === 'websocket')) {
    
    // 檢查是否為正確的 WebSocket 升級請求
    $upgrade = $_SERVER['HTTP_UPGRADE'] ?? '';
    $connection = $_SERVER['HTTP_CONNECTION'] ?? '';
    $wsKey = $_SERVER['HTTP_SEC_WEBSOCKET_KEY'] ?? '';
    $wsVersion = $_SERVER['HTTP_SEC_WEBSOCKET_VERSION'] ?? '';
    
    if (strtolower($upgrade) === 'websocket' && 
        strpos(strtolower($connection), 'upgrade') !== false &&
        $wsKey && $wsVersion === '13') {
        
        // 🚀 啟動內建WebSocket服務器 (如果還沒運行)
        startWebSocketServerIfNeeded();
        
        // 🔄 代理WebSocket請求到內部服務器
        proxyWebSocketRequest($wsKey);
        exit();
    }
}

// 啟動WebSocket服務器 (僅在Zeabur環境且服務器未運行時)
function startWebSocketServerIfNeeded() {
    static $serverStarted = false;
    
    if ($serverStarted) return;
    
    $isZeabur = isset($_ENV['ZEABUR_DOMAIN']) || isset($_ENV['ZEABUR_WEB_DOMAIN']);
    $wsPort = 8081;
    
    if ($isZeabur) {
        // 檢查WebSocket服務器是否已經運行
        $connection = @fsockopen('127.0.0.1', $wsPort, $errno, $errstr, 1);
        if (!$connection) {
            // 🔄 後台啟動WebSocket服務器
            $cmd = 'php ' . __DIR__ . '/websocket/server.php > /dev/null 2>&1 &';
            exec($cmd);
            
            // 等待服務器啟動
            $maxWait = 5; // 5秒超時
            $waited = 0;
            while ($waited < $maxWait) {
                usleep(500000); // 等待0.5秒
                $waited += 0.5;
                
                $testConnection = @fsockopen('127.0.0.1', $wsPort, $errno, $errstr, 1);
                if ($testConnection) {
                    fclose($testConnection);
                    break;
                }
            }
            
            error_log("WebSocket服務器啟動完成 (內部端口: {$wsPort})");
        } else {
            fclose($connection);
        }
        
        $serverStarted = true;
    }
}

// 代理WebSocket請求
function proxyWebSocketRequest($wsKey) {
    // 生成WebSocket接受密鑰
    $acceptKey = base64_encode(sha1($wsKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    
    // 發送WebSocket握手響應
    header('HTTP/1.1 101 Switching Protocols');
    header('Upgrade: websocket');
    header('Connection: Upgrade');
    header('Sec-WebSocket-Accept: ' . $acceptKey);
    
    // 🔄 建立到內部WebSocket服務器的連接
    $internalSocket = @fsockopen('127.0.0.1', 8081, $errno, $errstr, 5);
    
    if (!$internalSocket) {
        // WebSocket服務器未運行，發送錯誤響應
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'WebSocket服務暫時不可用',
            'message' => 'WebSocket服務器正在啟動中，請稍後重試',
            'retry_after' => 3
        ]);
        return;
    }
    
    // 發送WebSocket升級請求到內部服務器
    $upgradeRequest = "GET /ws HTTP/1.1\r\n";
    $upgradeRequest .= "Host: 127.0.0.1:8081\r\n";
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
    
    // 🔄 開始雙向代理
    ob_end_flush();
    flush();
    
    // 設置非阻塞模式
    stream_set_blocking(STDIN, false);
    stream_set_blocking($internalSocket, false);
    
    // 代理數據
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
        
        usleep(1000); // 防止CPU佔用過高
    }
    
    fclose($internalSocket);
}

// API 請求處理
if (preg_match('/^\/backend\/api\/(.+)\.php$/', $uri, $matches)) {
    $apiFile = $matches[1];
    $apiPath = __DIR__ . "/backend/api/{$apiFile}.php";
    
    if (file_exists($apiPath)) {
        // 設置 $_GET 參數
        parse_str($_SERVER['QUERY_STRING'] ?? '', $_GET);
        
        // 包含並執行 API 文件
        include $apiPath;
        exit();
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "API 文件不存在: {$apiFile}.php",
            'requested_path' => $uri
        ]);
        exit();
    }
}

// 健康檢查端點
if ($uri === '/health' || $uri === '/api/health') {
    header('Content-Type: application/json');
    
    $health = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'uptime' => time(),
        'services' => [],
        'environment' => [],
        'performance' => []
    ];
    
    // 檢查 Web 服務器
    $health['services']['web'] = [
        'status' => 'running',
        'php_version' => PHP_VERSION,
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
        'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
    ];
    
    // 檢查 WebSocket 服務器
    $wsPort = 8081;
    $wsConnection = @fsockopen('127.0.0.1', $wsPort, $errno, $errstr, 2);
    
    if ($wsConnection) {
        fclose($wsConnection);
        $health['services']['websocket'] = [
            'status' => 'running',
            'port' => $wsPort,
            'message' => 'WebSocket 服務器正常運行'
        ];
    } else {
        $health['services']['websocket'] = [
            'status' => 'starting',
            'port' => $wsPort,
            'message' => 'WebSocket 服務器正在啟動或未運行',
            'action' => 'auto_start_enabled'
        ];
    }
    
    // 檢查數據庫連接
    try {
        if (file_exists(__DIR__ . '/backend/classes/Database.php')) {
            require_once __DIR__ . '/backend/classes/Database.php';
            $database = Database::getInstance();
            $dbStatus = $database->getStatus();
            
            $health['services']['database'] = [
                'status' => $dbStatus['connected'] ? 'connected' : 'disconnected',
                'type' => $dbStatus['type'],
                'tables' => $dbStatus['tables_count'] ?? 0,
                'message' => $dbStatus['connected'] ? 
                    ($dbStatus['type'] === 'MySQL' ? 'MySQL 數據庫已連接' : 'SQLite 降級模式') : 
                    '數據庫連接失敗'
            ];
        } else {
            $health['services']['database'] = [
                'status' => 'unavailable',
                'message' => '數據庫類文件不存在'
            ];
        }
    } catch (Exception $e) {
        $health['services']['database'] = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
        $health['status'] = 'degraded';
    }
    
    // 檢查 AI 配置
    $openaiKey = $_ENV['OPENAI_API_KEY'] ?? null;
    if (!$openaiKey && file_exists(__DIR__ . '/ai_config.json')) {
        $aiConfig = json_decode(file_get_contents(__DIR__ . '/ai_config.json'), true);
        $openaiKey = $aiConfig['openai_api_key'] ?? null;
    }
    
    if ($openaiKey && strlen($openaiKey) > 10) {
        $health['services']['ai_assistant'] = [
            'status' => 'enabled',
            'api_key_length' => strlen($openaiKey),
            'message' => 'AI 助教功能已啟用'
        ];
    } else {
        $health['services']['ai_assistant'] = [
            'status' => 'disabled',
            'message' => 'AI 助教功能未配置（需要 OpenAI API 金鑰）'
        ];
    }
    
    // 環境信息
    $health['environment'] = [
        'is_zeabur' => isset($_ENV['ZEABUR_DOMAIN']) || isset($_ENV['ZEABUR_WEB_DOMAIN']),
        'is_local' => in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost:8080', '127.0.0.1:8080']),
        'websocket_mode' => 'integrated_proxy',
        'domain' => $_ENV['ZEABUR_DOMAIN'] ?? $_ENV['ZEABUR_WEB_DOMAIN'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost',
        'protocol' => isset($_SERVER['HTTPS']) ? 'https' : 'http'
    ];
    
    // 性能指標
    $health['performance'] = [
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'memory_limit' => ini_get('memory_limit'),
        'execution_time' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . 'ms'
    ];
    
    // 檢查關鍵文件
    $requiredFiles = [
        'public/index.html',
        'public/js/websocket.js',
        'public/js/ai-assistant.js',
        'backend/api/ai.php',
        'websocket/server.php'
    ];
    
    $missingFiles = [];
    foreach ($requiredFiles as $file) {
        if (!file_exists(__DIR__ . '/' . $file)) {
            $missingFiles[] = $file;
        }
    }
    
    if (!empty($missingFiles)) {
        $health['services']['files'] = [
            'status' => 'incomplete',
            'missing_files' => $missingFiles,
            'message' => '某些關鍵文件缺失'
        ];
        $health['status'] = 'degraded';
    } else {
        $health['services']['files'] = [
            'status' => 'complete',
            'message' => '所有關鍵文件存在'
        ];
    }
    
    echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// 靜態文件處理
$requestedFile = ltrim($uri, '/');

// 如果請求根路徑，重定向到 public/index.html
if ($requestedFile === '' || $requestedFile === '/' || $requestedFile === 'index') {
    $requestedFile = 'public/index.html';
}

// 如果請求的是相對路徑且文件存在於 public 目錄
if (!str_contains($requestedFile, '..') && file_exists(__DIR__ . '/' . $requestedFile)) {
    $filePath = __DIR__ . '/' . $requestedFile;
    $mimeType = getMimeType($filePath);
    
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit();
}

// 檢查是否在 public 目錄中
if (!str_contains($requestedFile, '..')) {
    $publicPath = __DIR__ . '/public/' . $requestedFile;
    
    if (file_exists($publicPath)) {
        $mimeType = getMimeType($publicPath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($publicPath));
        readfile($publicPath);
        exit();
    }
}

// 特殊文件處理
switch($requestedFile) {
    case 'teacher-dashboard.html':
        if (file_exists(__DIR__ . '/public/teacher-dashboard.html')) {
            header('Content-Type: text/html; charset=utf-8');
            readfile(__DIR__ . '/public/teacher-dashboard.html');
            exit();
        }
        break;
        
    case 'config.html':
        if (file_exists(__DIR__ . '/public/config.html')) {
            header('Content-Type: text/html; charset=utf-8');
            readfile(__DIR__ . '/public/config.html');
            exit();
        }
        break;
}

// 404 處理
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'error' => '頁面不存在',
    'requested_path' => $uri,
    'message' => '請檢查 URL 是否正確',
    'available_paths' => [
        '/' => '學生界面',
        '/teacher-dashboard.html' => '教師監控面板',
        '/config.html' => '系統配置',
        '/health' => '系統健康檢查'
    ]
]);

// MIME 類型檢測函數
function getMimeType($filePath) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf'
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}
?> 