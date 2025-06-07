<?php
/**
 * 認證 API 端點
 * 處理用戶登入和會話管理
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // 只處理 POST 請求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => '只支持 POST 請求'
        ]);
        exit();
    }
    
    // 獲取請求數據
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => '無效的 JSON 數據'
        ]);
        exit();
    }
    
    $action = $data['action'] ?? '';
    $username = $data['username'] ?? '';
    $userType = $data['user_type'] ?? 'student';
    
    if ($action !== 'login' || empty($username)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => '缺少必要參數 (action, username)'
        ]);
        exit();
    }
    
    // 模擬用戶登入（實際應用中應該驗證用戶憑證）
    $userId = crc32($username) & 0x7FFFFFFF; // 生成一個基於用戶名的ID
    
    // 啟動會話
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 設置會話數據
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['user_type'] = $userType;
    $_SESSION['login_time'] = time();
    
    // 返回成功響應
    echo json_encode([
        'success' => true,
        'message' => '登入成功',
        'data' => [
            'user_id' => $userId,
            'username' => $username,
            'user_type' => $userType,
            'session_id' => session_id(),
            'login_time' => date('c')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '服務器錯誤: ' . $e->getMessage()
    ]);
}
?> 