<?php
// Zeabur 路由器 - 處理 API 請求和 WebSocket 代理

// 防止目錄遍歷攻擊
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, '..') !== false) {
    http_response_code(403);
    exit('Forbidden');
}

// 設置 CORS 標頭
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// WebSocket 代理處理
if (preg_match('/^\/ws/', $uri)) {
    // 檢查是否為 WebSocket 升級請求
    $upgrade = $_SERVER['HTTP_UPGRADE'] ?? '';
    $connection = $_SERVER['HTTP_CONNECTION'] ?? '';
    
    if (strtolower($upgrade) === 'websocket' && strpos(strtolower($connection), 'upgrade') !== false) {
        // WebSocket 升級請求，代理到內部 WebSocket 服務器
        $wsHost = $_ENV['WEBSOCKET_HOST'] ?? '127.0.0.1';
        $wsPort = $_ENV['WEBSOCKET_PORT'] ?? 8081;
        
        // 在生產環境中，這通常由反向代理（如 nginx）處理
        // 這裡提供基本的錯誤響應
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'WebSocket升級需要反向代理支援',
            'message' => '請配置反向代理將 /ws 請求轉發到 ' . $wsHost . ':' . $wsPort,
            'websocket_url' => "ws://{$wsHost}:{$wsPort}"
        ]);
        exit();
    }
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
    $wsPort = $_ENV['WEBSOCKET_PORT'] ?? 8081;
    
    // 使用更適合的WebSocket檢測方法
    $wsContext = stream_context_create([
        'http' => [
            'timeout' => 2,
            'ignore_errors' => true
        ]
    ]);
    
    // 嘗試連接到WebSocket端口
    $wsConnection = @fsockopen('localhost', $wsPort, $errno, $errstr, 2);
    
    if ($wsConnection) {
        fclose($wsConnection);
        $health['services']['websocket'] = [
            'status' => 'running',
            'port' => $wsPort,
            'message' => 'WebSocket 服務器正常運行'
        ];
    } else {
        // 檢查端口是否在監聽
        $netstatOutput = shell_exec("netstat -an | findstr :{$wsPort}");
        if ($netstatOutput && strpos($netstatOutput, 'LISTENING') !== false) {
            $health['services']['websocket'] = [
                'status' => 'running',
                'port' => $wsPort,
                'message' => 'WebSocket 服務器正在監聽端口'
            ];
        } else {
            $health['services']['websocket'] = [
                'status' => 'down',
                'port' => $wsPort,
                'message' => 'WebSocket 服務器未運行',
                'error' => $errstr
            ];
        }
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
        'is_zeabur' => isset($_ENV['ZEABUR_DOMAIN']),
        'is_local' => in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost:8080', '127.0.0.1:8080']),
        'websocket_port' => $wsPort,
        'domain' => $_ENV['ZEABUR_DOMAIN'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost',
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
            'missing_files' => $missingFiles
        ];
        $health['status'] = 'degraded';
    } else {
        $health['services']['files'] = [
            'status' => 'complete',
            'message' => '所有必要文件存在'
        ];
    }
    
    // 根據整體狀態設置 HTTP 狀態碼
    switch ($health['status']) {
        case 'healthy':
            http_response_code(200);
            break;
        case 'degraded':
            http_response_code(200); // 仍然可用，但有問題
            break;
        default:
            http_response_code(503); // 服務不可用
    }
    
    echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// 靜態文件處理
$requestPath = $_SERVER['REQUEST_URI'];
$filePath = __DIR__ . parse_url($requestPath, PHP_URL_PATH);

// 安全檢查
if (strpos(realpath($filePath) ?: '', realpath(__DIR__)) !== 0) {
    http_response_code(403);
    exit('Forbidden');
}

// 處理根目錄請求
if ($requestPath === '/') {
    $filePath = __DIR__ . '/public/index.html';
}

// 檢查文件是否存在
if (is_file($filePath)) {
    // 設置正確的 MIME 類型
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon'
    ];
    
    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
    header("Content-Type: {$mimeType}");
    
    // PHP 文件需要執行
    if ($extension === 'php') {
        include $filePath;
    } else {
        readfile($filePath);
    }
    exit();
}

// 文件不存在
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'error' => 'Not Found',
    'message' => '請求的資源不存在',
    'path' => $requestPath
]);
?> 