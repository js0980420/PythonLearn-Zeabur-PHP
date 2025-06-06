<?php
// 禁用錯誤顯示
error_reporting(0);
ini_set('display_errors', 0);

require_once '../classes/APIResponse.php';
require_once '../classes/Database.php';

use App\APIResponse;
use App\Database;

// 設置CORS頭
APIResponse::setCORSHeaders();

// 初始化
$database = Database::getInstance();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'login':
            handleLogin($database, $input);
            break;
            
        case 'logout':
            handleLogout($database, $input);
            break;
            
        case 'current':
            handleCurrentUser($database);
            break;
            
        default:
            echo APIResponse::error('無效的操作', 'E001');
    }
    
} catch (Exception $e) {
    echo APIResponse::error('系統錯誤', 'E010', 500);
}

function handleLogin($database, $input) {
    // 驗證必要參數
    if (empty($input['username'])) {
        echo APIResponse::error('用戶名不能為空', 'E001');
        return;
    }
    
    $username = trim($input['username']);
    $userType = $input['user_type'] ?? 'student';
    
    // 驗證用戶名格式 - 簡化版本
    if (strlen($username) < 2 || strlen($username) > 20) {
        echo APIResponse::error('用戶名長度必須在2-20字符之間', 'E001');
        return;
    }
    
    // 檢查用戶是否存在
    $user = $database->fetch(
        "SELECT * FROM users WHERE username = :username",
        ['username' => $username]
    );
    
    if (!$user) {
        // 創建新用戶
        $userId = $database->insert('users', [
            'username' => $username,
            'user_type' => $userType
        ]);
        
        $user = [
            'id' => $userId,
            'username' => $username,
            'user_type' => $userType,
            'created_at' => date('Y-m-d H:i:s')
        ];
    } else {
        // 用戶已存在，更新登入時間
        $database->update('users', [
            'last_login' => date('Y-m-d H:i:s')
        ], [
            'id' => $user['id']
        ]);
    }
    
    // 設置會話
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = $user['user_type'];
    
    echo APIResponse::success([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'user_type' => $user['user_type'],
        'session_id' => session_id()
    ], '登入成功');
}

function handleLogout($database, $input) {
    session_start();
    
    if (isset($_SESSION['user_id'])) {
        // 記錄登出日誌
    }
    
    session_destroy();
    
    echo APIResponse::success(null, '登出成功');
}

function handleCurrentUser($database) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        echo APIResponse::error('未登入', 'E003', 401);
        return;
    }
    
    $user = $database->fetch(
        "SELECT id, username, user_type, created_at FROM users WHERE id = :id",
        ['id' => $_SESSION['user_id']]
    );
    
    if (!$user) {
        session_destroy();
        echo APIResponse::error('用戶不存在', 'E003', 401);
        return;
    }
    
    echo APIResponse::success([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'user_type' => $user['user_type'],
        'session_id' => session_id()
    ]);
} 