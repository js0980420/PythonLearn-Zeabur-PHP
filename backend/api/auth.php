<?php
// 禁用錯誤顯示，只記錄到日誌
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 設置CORS頭部
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// 處理預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // 檢查並載入必要的類別
    $apiResponsePath = __DIR__ . '/../classes/APIResponse.php';
    $databasePath = __DIR__ . '/../classes/Database.php';
    
    if (!file_exists($apiResponsePath)) {
        throw new Exception("APIResponse.php 檔案不存在: $apiResponsePath");
    }
    
    if (!file_exists($databasePath)) {
        throw new Exception("Database.php 檔案不存在: $databasePath");
    }
    
    require_once $apiResponsePath;
    require_once $databasePath;
    
    // 檢查類別是否存在
    if (!class_exists('App\APIResponse')) {
        throw new Exception("APIResponse 類別未找到");
    }
    
    if (!class_exists('App\Database')) {
        throw new Exception("Database 類別未找到");
    }
    
    // 初始化數據庫
    $database = App\Database::getInstance();
    
    // 檢查數據庫連接
    if (!$database->isConnected()) {
        error_log("數據庫連接失敗，使用本地存儲模式");
    }
    
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
            echo App\APIResponse::error('無效的操作', 'E001');
    }
    
} catch (Exception $e) {
    // 記錄詳細錯誤信息
    error_log("認證API錯誤: " . $e->getMessage() . " 在 " . $e->getFile() . ":" . $e->getLine());
    
    // 返回用戶友好的錯誤信息
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系統錯誤，請稍後再試',
        'error_code' => 'E500',
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ],
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
}

function handleLogin($database, $input) {
    try {
        // 驗證必要參數
        if (empty($input['username'])) {
            echo App\APIResponse::error('用戶名不能為空', 'E001');
            return;
        }
        
        $username = trim($input['username']);
        $userType = $input['user_type'] ?? 'student';
        
        // 驗證用戶名格式 - 簡化版本
        if (strlen($username) < 2 || strlen($username) > 20) {
            echo App\APIResponse::error('用戶名長度必須在2-20字符之間', 'E001');
            return;
        }
        
        // 檢查用戶是否存在
        $user = null;
        try {
            $user = $database->fetch(
                "SELECT * FROM users WHERE username = :username",
                ['username' => $username]
            );
        } catch (Exception $e) {
            error_log("查詢用戶失敗: " . $e->getMessage());
            // 繼續執行，創建新用戶
        }
        
        if (!$user) {
            // 創建新用戶
            try {
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
            } catch (Exception $e) {
                error_log("創建用戶失敗: " . $e->getMessage());
                echo App\APIResponse::error('創建用戶失敗', 'E005');
                return;
            }
        } else {
            // 用戶已存在，更新登入時間
            try {
                $database->update('users', [
                    'last_login' => date('Y-m-d H:i:s')
                ], [
                    'id' => $user['id']
                ]);
            } catch (Exception $e) {
                error_log("更新登入時間失敗: " . $e->getMessage());
                // 不影響登入流程
            }
        }
        
        // 設置會話
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        
        echo App\APIResponse::success([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'user_type' => $user['user_type'],
            'session_id' => session_id()
        ], '登入成功');
        
    } catch (Exception $e) {
        error_log("登入處理錯誤: " . $e->getMessage());
        echo App\APIResponse::error('登入處理失敗', 'E500');
    }
}

function handleLogout($database, $input) {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            // 記錄登出日誌
            error_log("用戶登出: " . $_SESSION['username']);
        }
        
        session_destroy();
        
        echo App\APIResponse::success(null, '登出成功');
        
    } catch (Exception $e) {
        error_log("登出處理錯誤: " . $e->getMessage());
        echo App\APIResponse::error('登出處理失敗', 'E500');
    }
}

function handleCurrentUser($database) {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo App\APIResponse::error('未登入', 'E003', 401);
            return;
        }
        
        $user = null;
        try {
            $user = $database->fetch(
                "SELECT id, username, user_type, created_at FROM users WHERE id = :id",
                ['id' => $_SESSION['user_id']]
            );
        } catch (Exception $e) {
            error_log("查詢當前用戶失敗: " . $e->getMessage());
        }
        
        if (!$user) {
            session_destroy();
            echo App\APIResponse::error('用戶不存在', 'E003', 401);
            return;
        }
        
        echo App\APIResponse::success([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'user_type' => $user['user_type'],
            'session_id' => session_id()
        ]);
        
    } catch (Exception $e) {
        error_log("獲取當前用戶錯誤: " . $e->getMessage());
        echo App\APIResponse::error('獲取用戶信息失敗', 'E500');
    }
} 
?> 