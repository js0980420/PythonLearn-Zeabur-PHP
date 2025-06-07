<?php

namespace App;

class APIResponse {
    
    /**
     * 設置CORS頭部
     */
    public static function setCORSHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Content-Type: application/json; charset=utf-8');
        
        // 處理預檢請求
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * 成功回應
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 錯誤回應
     */
    public static function error($message = 'Error', $errorCode = 'E000', $httpCode = 400, $details = null) {
        http_response_code($httpCode);
        
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'timestamp' => date('c')
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 分頁回應
     */
    public static function paginated($data, $total, $page, $limit, $message = 'Success') {
        $totalPages = ceil($total / $limit);
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'timestamp' => date('c')
        ];
        
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 驗證錯誤回應
     */
    public static function validationError($errors, $message = 'Validation failed') {
        return self::error($message, 'E001', 422, $errors);
    }
    
    /**
     * 未授權回應
     */
    public static function unauthorized($message = 'Unauthorized') {
        return self::error($message, 'E401', 401);
    }
    
    /**
     * 禁止訪問回應
     */
    public static function forbidden($message = 'Forbidden') {
        return self::error($message, 'E403', 403);
    }
    
    /**
     * 資源不存在回應
     */
    public static function notFound($message = 'Resource not found') {
        return self::error($message, 'E404', 404);
    }
    
    /**
     * 服務器錯誤回應
     */
    public static function serverError($message = 'Internal server error') {
        return self::error($message, 'E500', 500);
    }
} 