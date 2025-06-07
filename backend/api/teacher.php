<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 包含基礎文件
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Room.php';

// 定義響應函數
function sendResponse($success, $data = null, $message = '') {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    
    // 解析路徑
    $pathParts = explode('/', trim($pathInfo, '/'));
    
    if ($method === 'GET') {
        // GET /api/teacher/rooms - 獲取所有房間列表
        if (empty($pathInfo) || $pathInfo === '/rooms') {
            handleGetRooms();
        } 
        // GET /api/teacher/room/{roomId} - 獲取特定房間詳情
        elseif (count($pathParts) >= 2 && $pathParts[0] === 'room') {
            $roomId = $pathParts[1];
            handleGetRoomDetails($roomId);
        } 
        else {
            sendResponse(false, null, '未知的API端點');
        }
    } else {
        sendResponse(false, null, '不支援的請求方法');
    }

} catch (Exception $e) {
    error_log("教師API錯誤: " . $e->getMessage());
    sendResponse(false, null, '服務器內部錯誤: ' . $e->getMessage());
}

/**
 * 處理獲取所有房間列表
 */
function handleGetRooms() {
    try {
        $roomManager = new Room();
        $rooms = $roomManager->getAllRoomsWithUsers();
        
        // 計算統計數據
        $totalRooms = count($rooms);
        $totalUsers = 0;
        $studentsInRooms = 0;
        $nonTeacherUsers = 0;
        
        foreach ($rooms as &$room) {
            $userCount = count($room['users']);
            $totalUsers += $userCount;
            $studentsInRooms += $userCount;
            $nonTeacherUsers += $userCount; // 假設房間中的都是學生
            
            // 為房間添加額外信息
            $room['userCount'] = $userCount;
            $room['lastActivity'] = $room['last_activity'] ?? time();
            
            // 計算代碼長度（估算）
            $codeLength = strlen($room['current_code'] ?? '');
            $room['codeLength'] = $codeLength;
        }
        
        $response = [
            'rooms' => $rooms,
            'totalRooms' => $totalRooms,
            'totalUsers' => $totalUsers,
            'studentsInRooms' => $studentsInRooms,
            'nonTeacherUsers' => $nonTeacherUsers
        ];
        
        sendResponse(true, $response, '房間列表載入成功');
        
    } catch (Exception $e) {
        error_log("獲取房間列表錯誤: " . $e->getMessage());
        sendResponse(false, null, '獲取房間列表失敗: ' . $e->getMessage());
    }
}

/**
 * 處理獲取特定房間詳情
 */
function handleGetRoomDetails($roomId) {
    try {
        $roomManager = new Room();
        
        // 獲取房間基本信息
        $roomInfo = $roomManager->getRoomInfo($roomId);
        if (!$roomInfo) {
            sendResponse(false, null, '房間不存在');
            return;
        }
        
        // 獲取房間中的用戶
        $users = $roomManager->getRoomUsers($roomId);
        
        // 獲取房間當前代碼
        $currentCode = $roomManager->getRoomCode($roomId);
        
        $response = [
            'id' => $roomId,
            'name' => $roomInfo['name'] ?? $roomId,
            'code' => $currentCode,
            'version' => $roomInfo['version'] ?? 1,
            'users' => $users,
            'userCount' => count($users),
            'created_at' => $roomInfo['created_at'] ?? date('c'),
            'last_activity' => $roomInfo['last_activity'] ?? time()
        ];
        
        sendResponse(true, $response, '房間詳情載入成功');
        
    } catch (Exception $e) {
        error_log("獲取房間詳情錯誤: " . $e->getMessage());
        sendResponse(false, null, '獲取房間詳情失敗: ' . $e->getMessage());
    }
}
?> 