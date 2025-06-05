<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// 載入環境變數
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

return [
    'name' => $_ENV['APP_NAME'] ?? 'Python教學多人協作平台',
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    
    'websocket' => [
        'host' => $_ENV['WEBSOCKET_HOST'] ?? 'localhost',
        'port' => (int)($_ENV['WEBSOCKET_PORT'] ?? 8080),
    ],
    
    'logging' => [
        'level' => $_ENV['LOG_LEVEL'] ?? 'INFO',
        'file' => $_ENV['LOG_FILE'] ?? 'app.log',
        'path' => __DIR__ . '/../../logs/',
    ],
    
    'debug' => [
        'enabled' => filter_var($_ENV['DEBUG_MODE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'profile_enabled' => filter_var($_ENV['PROFILE_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'query_debug' => filter_var($_ENV['QUERY_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'websocket_debug' => filter_var($_ENV['WEBSOCKET_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'ai_debug' => filter_var($_ENV['AI_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ],
    
    'security' => [
        'rate_limit_enabled' => filter_var($_ENV['RATE_LIMIT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'cors_origins' => explode(',', $_ENV['CORS_ORIGINS'] ?? '*'),
    ],
    
    'limits' => [
        'code_max_length' => 10000,
        'room_max_users' => 50,
        'ai_requests_per_minute' => 10,
        'code_saves_per_minute' => 30,
        'room_creates_per_hour' => 5,
    ]
]; 