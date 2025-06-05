<?php
// 關閉錯誤顯示，避免破壞JSON響應
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../classes/MockDatabase.php';
require_once __DIR__ . '/../classes/Logger.php';

use App\MockDatabase as Database;
use App\Logger;

// 設置CORS頭
APIResponse::setCORSHeaders();

// 初始化
$database = Database::getInstance();
$database->addTestData(); // 添加測試數據
$logger = new Logger('auth.log');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'login':
            handleLogin($database, $logger, $input);
            break;
            
        case 'logout':
            handleLogout($database, $logger, $input);
            break;
            
        case 'current':
            handleCurrentUser($database, $logger);
            break;
            
        default:
            echo APIResponse::error('無效的操作', 'E001');
    }
    
} catch (Exception $e) {
    $logger->error('認證API錯誤', ['error' => $e->getMessage()]);
    echo APIResponse::error('系統錯誤', 'E010', 500);
}

function handleLogin($database, $logger, $input) {
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
        
        $logger->info('新用戶註冊', ['user_id' => $userId, 'username' => $username]);
    } else {
        $logger->info('用戶登入', ['user_id' => $user['id'], 'username' => $username]);
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

function handleLogout($database, $logger, $input) {
    session_start();
    
    if (isset($_SESSION['user_id'])) {
        $logger->info('用戶登出', [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username']
        ]);
    }
    
    session_destroy();
    
    echo APIResponse::success(null, '登出成功');
}

function handleCurrentUser($database, $logger) {
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