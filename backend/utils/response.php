<?php

class APIResponse {
    public static function success($data = null, $message = '操作成功') {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        
        return json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);
    }
    
    public static function error($message, $errorCode = null, $httpCode = 400) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        
        return json_encode([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);
    }
    
    public static function setCORSHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
} 