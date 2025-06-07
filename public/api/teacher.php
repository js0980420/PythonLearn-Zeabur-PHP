<?php
/**
 * 教師監控API端點
 * 提供房間數據和學生信息
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

// 獲取房間數據目錄
function getRoomsDataDir() {
    $dataDir = __DIR__ . '/../../data/rooms/';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    return $dataDir;
}

// 載入房間數據
function loadRoomData($roomId) {
    $dataDir = getRoomsDataDir();
    $roomFile = $dataDir . $roomId . '.json';
    
    if (!file_exists($roomFile)) {
        return null;
    }
    
    $data = file_get_contents($roomFile);
    return json_decode($data, true);
}

// 獲取所有房間列表
function getAllRooms() {
    $dataDir = getRoomsDataDir();
    $rooms = [];
    $totalUsers = 0;
    
    $roomFiles = glob($dataDir . '*.json');
    
    foreach ($roomFiles as $roomFile) {
        $roomId = basename($roomFile, '.json');
        $roomData = loadRoomData($roomId);
        
        if ($roomData) {
            // 模擬用戶數據（實際應該從WebSocket服務器獲取）
            $users = [];
            $userCount = rand(1, 5); // 模擬1-5個用戶
            
            for ($i = 1; $i <= $userCount; $i++) {
                $users[] = [
                    'id' => "user_{$roomId}_{$i}",
                    'name' => "學生{$i}",
                    'lastActivity' => time() - rand(0, 3600)
                ];
            }
            
            $totalUsers += $userCount;
            
            $rooms[] = [
                'id' => $roomId,
                'name' => $roomData['name'] ?? $roomId,
                'users' => $users,
                'userCount' => $userCount,
                'current_code' => $roomData['code'] ?? '',
                'version' => $roomData['version'] ?? 1,
                'created_at' => $roomData['created_at'] ?? date('c'),
                'last_activity' => $roomData['last_activity'] ?? time(),
                'codeLength' => strlen($roomData['code'] ?? '')
            ];
        }
    }
    
    return [
        'rooms' => $rooms,
        'totalRooms' => count($rooms),
        'totalUsers' => $totalUsers,
        'studentsInRooms' => $totalUsers,
        'nonTeacherUsers' => $totalUsers
    ];
}

// 獲取特定房間詳情
function getRoomDetails($roomId) {
    $roomData = loadRoomData($roomId);
    
    if (!$roomData) {
        return null;
    }
    
    // 模擬用戶數據
    $users = [];
    $userCount = rand(1, 5);
    
    for ($i = 1; $i <= $userCount; $i++) {
        $users[] = [
            'id' => "user_{$roomId}_{$i}",
            'name' => "學生{$i}",
            'lastActivity' => time() - rand(0, 3600)
        ];
    }
    
    return [
        'id' => $roomId,
        'name' => $roomData['name'] ?? $roomId,
        'code' => $roomData['code'] ?? '',
        'version' => $roomData['version'] ?? 1,
        'users' => $users,
        'userCount' => count($users),
        'created_at' => $roomData['created_at'] ?? date('c'),
        'last_activity' => $roomData['last_activity'] ?? time()
    ];
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    
    // 解析路徑
    $pathParts = explode('/', trim($pathInfo, '/'));
    
    if ($method === 'GET') {
        // GET /api/teacher.php/rooms - 獲取所有房間列表
        if (empty($pathInfo) || $pathInfo === '/rooms') {
            $data = getAllRooms();
            // 直接返回數據，不包裝在data字段中
            echo json_encode($data);
            exit();
        } 
        // GET /api/teacher.php/room/{roomId} - 獲取特定房間詳情
        elseif (count($pathParts) >= 2 && $pathParts[0] === 'room') {
            $roomId = $pathParts[1];
            $data = getRoomDetails($roomId);
            
            if ($data) {
                // 直接返回數據，不包裝在data字段中
                echo json_encode($data);
                exit();
            } else {
                http_response_code(404);
                echo json_encode(['error' => '房間不存在']);
                exit();
            }
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
?> 