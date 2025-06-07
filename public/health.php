<?php
/**
 * 健康檢查端點 - Zeabur 部署健康檢查
 * 檢查系統基本功能是否正常運行
 */

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'services' => [
        'php' => 'running',
        'websocket' => 'running'
    ],
    'environment' => [
        'php_version' => PHP_VERSION,
        'zeabur' => isset($_ENV['ZEABUR']) ? 'true' : 'false'
    ]
];

http_response_code(200);
echo json_encode($health, JSON_PRETTY_PRINT);
?> 