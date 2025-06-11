<?php

/**
 * 教師監控API端點
 * 提供真實的房間數據和學生信息
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
function sendResponse($success, $data = null, $message = '')
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit();
}

// 獲取數據目錄
function getDataDir()
{
    return __DIR__ . '/../../data/';
}

// 載入JSON數據
function loadJSONFile($filePath)
{
    if (!file_exists($filePath)) {
        return null;
    }

    $data = file_get_contents($filePath);
    return json_decode($data, true);
}

// 從同步數據中獲取最新代碼
function getLatestCodeFromSync($roomId)
{
    $dataDir = getDataDir();
    $syncFile = $dataDir . 'sync_' . $roomId . '.json';
    $syncData = loadJSONFile($syncFile);

    if (!$syncData || !is_array($syncData) || empty($syncData)) {
        return ['code' => '', 'version' => 0, 'lastActivity' => time()];
    }

    // 找到最新的代碼記錄
    $latestRecord = null;
    foreach ($syncData as $record) {
        if (
            !$latestRecord ||
            (isset($record['timestamp']) && $record['timestamp'] > $latestRecord['timestamp'])
        ) {
            $latestRecord = $record;
        }
    }

    return [
        'code' => $latestRecord['code'] ?? '',
        'version' => count($syncData), // 使用記錄數量作為版本號
        'lastActivity' => isset($latestRecord['timestamp']) ?
            intval($latestRecord['timestamp'] / 1000) : time()
    ];
}

// 獲取所有房間列表
function getAllRooms()
{
    $dataDir = getDataDir();
    $rooms = [];
    $totalUsers = 0;

    // 查找所有 users_*.json 文件
    $userFiles = glob($dataDir . 'users_*.json');

    foreach ($userFiles as $userFile) {
        // 從文件名提取房間ID (users_房間名.json)
        $fileName = basename($userFile);
        if (preg_match('/^users_(.+)\.json$/', $fileName, $matches)) {
            $roomId = $matches[1];

            // 加載用戶數據
            $usersData = loadJSONFile($userFile);
            if (!$usersData) {
                $usersData = [];
            }

            // 檢查是否有用戶結構
            $users = [];
            if (isset($usersData['users'])) {
                $users = $usersData['users'];
            } elseif (is_array($usersData)) {
                $users = $usersData;
            }

            // 清理過期用戶 (60秒內活躍)
            $currentTime = time();
            $activeUsers = array_filter($users, function ($user) use ($currentTime) {
                if (isset($user['last_active'])) {
                    $lastActive = strtotime($user['last_active']);
                    return ($currentTime - $lastActive) < 60;
                } elseif (isset($user['last_seen'])) {
                    return ($currentTime - $user['last_seen']) < 60;
                }
                return false;
            });

            $userCount = count($activeUsers);
            $totalUsers += $userCount;

            // 獲取代碼數據（優先從sync文件）
            $codeInfo = getLatestCodeFromSync($roomId);

            // 如果沒有sync數據，嘗試從單獨的code文件讀取
            if (empty($codeInfo['code'])) {
                $codeFile = $dataDir . 'code_' . $roomId . '.json';
                $codeData = loadJSONFile($codeFile);

                if ($codeData && is_array($codeData)) {
                    $latestCodeRecord = null;
                    foreach ($codeData as $record) {
                        if (!$latestCodeRecord || $record['timestamp'] > $latestCodeRecord['timestamp']) {
                            $latestCodeRecord = $record;
                        }
                    }
                    if ($latestCodeRecord) {
                        $codeInfo['code'] = $latestCodeRecord['code'] ?? '';
                        $codeInfo['version'] = $latestCodeRecord['version'] ?? 0;
                    }
                }
            }

            $rooms[] = [
                'id' => $roomId,
                'name' => ucfirst(str_replace('-', ' ', $roomId)),
                'users' => array_values($activeUsers),
                'userCount' => $userCount,
                'current_code' => $codeInfo['code'],
                'version' => $codeInfo['version'],
                'created_at' => date('c'),
                'last_activity' => $codeInfo['lastActivity'],
                'codeLength' => strlen($codeInfo['code']),
                'isActive' => $userCount > 0
            ];
        }
    }

    // 按用戶數量和最後活動時間排序
    usort($rooms, function ($a, $b) {
        if ($a['userCount'] != $b['userCount']) {
            return $b['userCount'] - $a['userCount']; // 用戶多的在前
        }
        return $b['last_activity'] - $a['last_activity']; // 活動時間晚的在前
    });

    return [
        'rooms' => $rooms,
        'totalRooms' => count($rooms),
        'totalUsers' => $totalUsers,
        'studentsInRooms' => $totalUsers,
        'nonTeacherUsers' => $totalUsers,
        'activeRooms' => count(array_filter($rooms, function ($room) {
            return $room['userCount'] > 0;
        })),
        'timestamp' => time() * 1000
    ];
}

// 獲取特定房間詳情
function getRoomDetails($roomId)
{
    $dataDir = getDataDir();

    // 加載用戶數據
    $userFile = $dataDir . 'users_' . $roomId . '.json';
    $usersData = loadJSONFile($userFile);

    if (!$usersData) {
        return null;
    }

    // 處理用戶結構
    $users = [];
    if (isset($usersData['users'])) {
        $users = $usersData['users'];
    } elseif (is_array($usersData)) {
        $users = $usersData;
    }

    // 清理過期用戶
    $currentTime = time();
    $activeUsers = array_filter($users, function ($user) use ($currentTime) {
        if (isset($user['last_active'])) {
            $lastActive = strtotime($user['last_active']);
            return ($currentTime - $lastActive) < 60;
        } elseif (isset($user['last_seen'])) {
            return ($currentTime - $user['last_seen']) < 60;
        }
        return false;
    });

    // 獲取代碼數據
    $codeInfo = getLatestCodeFromSync($roomId);

    // 如果沒有sync數據，嘗試從單獨的code文件讀取
    if (empty($codeInfo['code'])) {
        $codeFile = $dataDir . 'code_' . $roomId . '.json';
        $codeData = loadJSONFile($codeFile);

        if ($codeData && is_array($codeData)) {
            $latestCodeRecord = null;
            foreach ($codeData as $record) {
                if (!$latestCodeRecord || $record['timestamp'] > $latestCodeRecord['timestamp']) {
                    $latestCodeRecord = $record;
                }
            }
            if ($latestCodeRecord) {
                $codeInfo['code'] = $latestCodeRecord['code'] ?? '';
                $codeInfo['version'] = $latestCodeRecord['version'] ?? 0;
            }
        }
    }

    // 加載聊天數據
    $chatFile = $dataDir . 'chat_' . $roomId . '.json';
    $chatData = loadJSONFile($chatFile);
    $recentMessages = [];

    if ($chatData && is_array($chatData)) {
        // 獲取最近10條消息
        $recentMessages = array_slice($chatData, -10);
    }

    return [
        'id' => $roomId,
        'name' => ucfirst(str_replace('-', ' ', $roomId)),
        'code' => $codeInfo['code'],
        'version' => $codeInfo['version'],
        'users' => array_values($activeUsers),
        'userCount' => count($activeUsers),
        'created_at' => date('c'),
        'last_activity' => $codeInfo['lastActivity'],
        'recentMessages' => $recentMessages,
        'messageCount' => count($chatData ?? []),
        'codeLength' => strlen($codeInfo['code']),
        'isActive' => count($activeUsers) > 0
    ];
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if ($method === 'GET') {
        // GET /api/teacher.php?action=rooms - 獲取所有房間列表
        if (empty($action) || $action === 'rooms') {
            $data = getAllRooms();
            echo json_encode($data);
            exit();
        }
        // GET /api/teacher.php?action=room&id={roomId} - 獲取特定房間詳情
        elseif ($action === 'room') {
            $roomId = $_GET['id'] ?? '';
            if (empty($roomId)) {
                sendResponse(false, null, '缺少房間ID參數');
            }

            $data = getRoomDetails($roomId);

            if ($data) {
                echo json_encode($data);
                exit();
            } else {
                http_response_code(404);
                echo json_encode(['error' => '房間不存在或無用戶數據']);
                exit();
            }
        } else {
            sendResponse(false, null, '未知的API端點');
        }
    } else {
        sendResponse(false, null, '不支援的請求方法');
    }
} catch (Exception $e) {
    error_log("教師API錯誤: " . $e->getMessage());
    sendResponse(false, null, '服務器內部錯誤: ' . $e->getMessage());
}
