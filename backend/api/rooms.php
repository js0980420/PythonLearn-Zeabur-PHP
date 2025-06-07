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
$database->addTestData(); // 添加測試數據

try {
    session_start();
    
    // 檢查用戶是否登入
    if (!isset($_SESSION['user_id'])) {
        echo APIResponse::error('請先登入', 'E003', 401);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'create':
            handleCreateRoom($database, $input);
            break;
            
        case 'join':
            handleJoinRoom($database, $input);
            break;
            
        case 'leave':
            handleLeaveRoom($database, $input);
            break;
            
        case 'list':
            handleListRooms($database);
            break;
            
        case 'info':
            handleRoomInfo($database, $input);
            break;
            
        case 'users':
            handleRoomUsers($database, $input);
            break;
            
        default:
            echo APIResponse::error('無效的操作', 'E001');
    }
    
} catch (Exception $e) {
    echo APIResponse::error('系統錯誤', 'E010', 500);
}

function handleCreateRoom($database, $input) {
    $roomName = trim($input['room_name'] ?? '');
    $description = trim($input['description'] ?? '');
    $maxUsers = intval($input['max_users'] ?? 10);
    
    if (empty($roomName)) {
        echo APIResponse::error('房間名稱不能為空', 'E001');
        return;
    }
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        echo APIResponse::error('用戶未登入', 'E003');
        return;
    }
    
    // 檢查房間名稱是否已存在
    $existingRoom = $database->fetch(
        "SELECT id FROM rooms WHERE room_name = :room_name",
        ['room_name' => $roomName]
    );
    
    if ($existingRoom) {
        echo APIResponse::error('房間名稱已存在', 'E002');
        return;
    }
    
    // 創建房間
    $roomId = $database->insert('rooms', [
        'room_name' => $roomName,
        'description' => $description,
        'max_users' => $maxUsers,
        'created_by' => $_SESSION['user_id']
    ]);
    
    // 創建者自動加入房間
    $database->insert('room_users', [
        'room_id' => $roomId,
        'user_id' => $_SESSION['user_id'],
        'role' => 'owner'
    ]);
    
    echo APIResponse::success([
        'room_id' => $roomId,
        'room_name' => $roomName,
        'description' => $description,
        'max_users' => $maxUsers
    ], '房間創建成功');
}

function handleJoinRoom($database, $input) {
    $roomId = intval($input['room_id'] ?? 0);
    
    if (!$roomId) {
        echo APIResponse::error('房間ID不能為空', 'E001');
        return;
    }
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        echo APIResponse::error('用戶未登入', 'E003');
        return;
    }
    
    // 檢查房間是否存在
    $room = $database->fetch(
        "SELECT * FROM rooms WHERE id = :id",
        ['id' => $roomId]
    );
    
    if (!$room) {
        echo APIResponse::error('房間不存在', 'E004');
        return;
    }
    
    // 檢查房間人數限制
    $currentUsers = $database->fetch(
        "SELECT COUNT(*) as count FROM room_users WHERE room_id = :room_id",
        ['room_id' => $roomId]
    );
    
    if ($currentUsers['count'] >= $room['max_users']) {
        echo APIResponse::error('房間已滿', 'E006');
        return;
    }
    
    // 檢查用戶是否已在房間中
    $existingUser = $database->fetch(
        "SELECT id FROM room_users WHERE room_id = :room_id AND user_id = :user_id",
        ['room_id' => $roomId, 'user_id' => $_SESSION['user_id']]
    );
    
    if ($existingUser) {
        echo APIResponse::error('您已在此房間中', 'E005');
        return;
    }
    
    // 加入房間
    $database->insert('room_users', [
        'room_id' => $roomId,
        'user_id' => $_SESSION['user_id'],
        'role' => 'member'
    ]);
    
    echo APIResponse::success([
        'room_id' => $roomId,
        'room_name' => $room['room_name']
    ], '成功加入房間');
}

function handleLeaveRoom($database, $input) {
    $roomId = intval($input['room_id'] ?? 0);
    
    if (!$roomId) {
        echo APIResponse::error('房間ID不能為空', 'E001');
        return;
    }
    
    // 檢查用戶是否在房間中
    $roomUser = $database->fetch(
        "SELECT * FROM room_users WHERE room_id = :room_id AND user_id = :user_id",
        ['room_id' => $roomId, 'user_id' => $_SESSION['user_id']]
    );
    
    if (!$roomUser) {
        echo APIResponse::error('您不在此房間中', 'E007');
        return;
    }
    
    // 離開房間
    $database->delete('room_users', [
        'room_id' => $roomId,
        'user_id' => $_SESSION['user_id']
    ]);
    
    echo APIResponse::success(null, '成功離開房間');
}

function handleListRooms($database) {
    $rooms = $database->fetchAll(
        "SELECT r.*, u.username as creator_name,
                (SELECT COUNT(*) FROM room_users ru WHERE ru.room_id = r.id) as current_users
         FROM rooms r
         LEFT JOIN users u ON r.created_by = u.id
         ORDER BY r.created_at DESC"
    );
    
    echo APIResponse::success($rooms);
}

function handleRoomInfo($database, $input) {
    $roomId = intval($input['room_id'] ?? $_GET['room_id'] ?? 0);
    
    if (!$roomId) {
        echo APIResponse::error('房間ID不能為空', 'E001');
        return;
    }
    
    $room = $database->fetch(
        "SELECT r.*, u.username as creator_name,
                (SELECT COUNT(*) FROM room_users ru WHERE ru.room_id = r.id) as current_users
         FROM rooms r
         LEFT JOIN users u ON r.created_by = u.id
         WHERE r.id = :id",
        ['id' => $roomId]
    );
    
    if (!$room) {
        echo APIResponse::error('房間不存在', 'E004');
        return;
    }
    
    echo APIResponse::success($room);
}

function handleRoomUsers($database, $input) {
    $roomId = intval($input['room_id'] ?? $_GET['room_id'] ?? 0);
    
    if (!$roomId) {
        echo APIResponse::error('房間ID不能為空', 'E001');
        return;
    }
    
    $users = $database->fetchAll(
        "SELECT ru.*, u.username, u.user_type
         FROM room_users ru
         JOIN users u ON ru.user_id = u.id
         WHERE ru.room_id = :room_id
         ORDER BY ru.joined_at ASC",
        ['room_id' => $roomId]
    );
    
    echo APIResponse::success($users);
} 