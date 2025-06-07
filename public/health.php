<?php
/**
 * 健康檢查端點 - Zeabur 部署健康檢查
 * 檢查系統基本功能是否正常運行
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $health = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'services' => [
            'webserver' => 'running',
            'php' => 'running'
        ],
        'environment' => $_ENV['ENVIRONMENT'] ?? 'production',
        'version' => '1.0.0'
    ];
    
    // 檢查基本 PHP 功能
    if (!function_exists('json_encode')) {
        throw new Exception('JSON extension not available');
    }
    
    // 檢查必要目錄
    $required_dirs = ['../data', '../logs', '../storage'];
    foreach ($required_dirs as $dir) {
        if (!is_dir($dir)) {
            $health['warnings'][] = "Directory " . basename($dir) . " not found";
        }
    }
    
    // 檢查 WebSocket 支援
    if (extension_loaded('sockets')) {
        $health['services']['websocket'] = 'available';
    } else {
        $health['services']['websocket'] = 'unavailable';
        $health['warnings'][] = 'Sockets extension not loaded';
    }
    
    // 檢查 AI 配置 (可選)
    if (!empty($_ENV['OPENAI_API_KEY'])) {
        $health['services']['ai_assistant'] = 'configured';
    } else {
        $health['services']['ai_assistant'] = 'not_configured';
        $health['info'][] = 'AI assistant not configured (optional)';
    }
    
    http_response_code(200);
    echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?> 