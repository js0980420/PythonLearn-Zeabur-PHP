<?php

/**
 * PythonLearn API 端點
 * 為 Zeabur 環境提供 HTTP 輪詢支持，替代 WebSocket
 * 
 * 📅 創建日期: 2025-01-28
 * 🎯 目標: 解決 Zeabur 單端口限制，提供實時協作功能
 * 🗄️ 資料庫支持: MySQL 作為主要存儲，文件作為備份
 */

// 載入資料庫配置
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 獲取請求參數
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$roomId = $_GET['room_id'] ?? $_POST['room_id'] ?? 'general-room';
$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? '';

// 簡單的會話管理（使用文件系統）
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$roomFile = $dataDir . '/room_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId) . '.json';
$usersFile = $dataDir . '/users_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId) . '.json';
$codeFile = $dataDir . '/code_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId) . '.json';
$syncFile = $dataDir . '/sync_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId) . '.json';

/**
 * 載入房間數據
 */
function loadRoomData($file)
{
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return $content ? json_decode($content, true) : [];
    }
    return [];
}

/**
 * 保存房間數據
 */
function saveRoomData($file, $data)
{
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * 清理過期用戶
 */
function cleanExpiredUsers($users, $timeout = 30)  // 🔥 縮短超時時間到30秒
{
    $now = time();
    $cleanUsers = array_filter($users, function ($user) use ($now, $timeout) {
        return ($now - $user['last_seen']) < $timeout;
    });

    // 🔥 移除重複用戶 - 保留最新的記錄
    $uniqueUsers = [];
    $seenUsers = [];

    // 反向遍歷以保留最新記錄
    foreach (array_reverse($cleanUsers) as $user) {
        $userKey = $user['id'] . '_' . ($user['name'] ?? '');
        if (!isset($seenUsers[$userKey])) {
            $seenUsers[$userKey] = true;
            array_unshift($uniqueUsers, $user); // 添加到開頭保持順序
        }
    }

    return $uniqueUsers;
}

/**
 * 🔥 強制清理特定用戶（用於beforeunload失敗的情況）
 */
function forceRemoveUser($users, $userId, $userName = null)
{
    return array_filter($users, function ($user) use ($userId, $userName) {
        // 按用戶ID移除
        if ($user['id'] === $userId) {
            return false;
        }

        // 如果有用戶名，也按用戶名移除（處理重新整理的情況）
        if ($userName && isset($user['name']) && $user['name'] === $userName) {
            return false;
        }

        return true;
    });
}

/**
 * 更新用戶最後活動時間
 */
function updateUserActivity($users, $userId, $userName = null, $isTeacher = false)
{
    $now = time();
    $found = false;

    // 🔥 老師不應該被加入到房間用戶列表中
    if ($isTeacher) {
        return $users; // 直接返回，不添加老師到用戶列表
    }

    // 🔥 首先移除相同用戶名的舊條目（處理重新整理情況）
    if ($userName) {
        $users = array_filter($users, function ($user) use ($userName, $userId) {
            // 移除同名但ID不同的用戶（舊的瀏覽器會話）
            return !($user['name'] === $userName && $user['id'] !== $userId);
        });
    }

    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user['last_seen'] = $now;
            if ($userName) {
                $user['name'] = $userName;
            }
            $found = true;
            break;
        }
    }

    // 🔥 只有非老師用戶且未找到時才添加
    if (!$found && $userName && !$isTeacher) {
        $users[] = [
            'id' => $userId,
            'name' => $userName,
            'joined_at' => $now,
            'last_seen' => $now,
            'connection_type' => 'http_polling',
            'is_teacher' => false
        ];
    }

    return $users;
}

/**
 * 🧹 部署清理函數 - 清空所有房間和用戶狀態
 */
function deployCleanup()
{
    // 記錄清理時間
    $cleanup_time = date('c');
    $data_dir = 'data';

    // 確保資料目錄存在
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0777, true);
    }

    $cleanup_result = [
        'cleanup_time' => $cleanup_time,
        'files_cleaned' => [],
        'total_users_removed' => 0,
        'total_rooms_removed' => 0
    ];

    try {
        // 清理用戶狀態檔案
        $users_file = "$data_dir/users.json";
        if (file_exists($users_file)) {
            $users = json_decode(file_get_contents($users_file), true) ?: [];
            $cleanup_result['total_users_removed'] = count($users);
            file_put_contents($users_file, json_encode([]));
            $cleanup_result['files_cleaned'][] = 'users.json';
        }

        // 清理房間狀態檔案
        $rooms_file = "$data_dir/rooms.json";
        if (file_exists($rooms_file)) {
            $rooms = json_decode(file_get_contents($rooms_file), true) ?: [];
            $cleanup_result['total_rooms_removed'] = count($rooms);
            file_put_contents($rooms_file, json_encode([]));
            $cleanup_result['files_cleaned'][] = 'rooms.json';
        }

        // 清理同步記錄
        $sync_file = "$data_dir/sync_records.json";
        if (file_exists($sync_file)) {
            file_put_contents($sync_file, json_encode([]));
            $cleanup_result['files_cleaned'][] = 'sync_records.json';
        }

        // 清理聊天記錄
        $chat_file = "$data_dir/chat_messages.json";
        if (file_exists($chat_file)) {
            file_put_contents($chat_file, json_encode([]));
            $cleanup_result['files_cleaned'][] = 'chat_messages.json';
        }

        // 記錄部署時間
        $deploy_time_file = "$data_dir/deploy_time.json";
        file_put_contents($deploy_time_file, json_encode([
            'deploy_time' => $cleanup_time,
            'cleanup_performed' => true
        ]));

        $cleanup_result['success'] = true;
    } catch (Exception $e) {
        $cleanup_result['success'] = false;
        $cleanup_result['error'] = $e->getMessage();
    }

    return $cleanup_result;
}

/**
 * 🔍 檢查是否為新部署
 */
function isNewDeploy()
{
    $deploy_time_file = 'data/deploy_time.json';

    if (!file_exists($deploy_time_file)) {
        return true; // 沒有部署時間記錄，視為新部署
    }

    $deploy_info = json_decode(file_get_contents($deploy_time_file), true);

    if (!$deploy_info || !isset($deploy_info['deploy_time'])) {
        return true; // 部署時間記錄無效，視為新部署
    }

    // 檢查是否超過 5 分鐘 (新部署的容錯時間)
    $deploy_timestamp = strtotime($deploy_info['deploy_time']);
    $current_timestamp = time();
    $time_diff = $current_timestamp - $deploy_timestamp;

    return $time_diff < 300; // 5分鐘內視為新部署
}

try {
    switch ($action) {
        case 'poll':
            // 高頻 HTTP 輪詢 - 獲取房間狀態、用戶列表和代碼同步
            $users = loadRoomData($usersFile);
            $users = cleanExpiredUsers($users);
            $syncData = loadRoomData($syncFile);

            // 🔥 檢查當前用戶是否為老師（通過用戶名判斷）
            $isTeacher = strpos($userId, '老師') !== false ||
                strpos($userId, 'teacher') !== false ||
                strpos($userId, 'Teacher') !== false ||
                strpos($userName ?? '', '老師') !== false ||
                strpos($userName ?? '', 'teacher') !== false ||
                strpos($userName ?? '', 'Teacher') !== false ||
                (isset($_GET['is_teacher']) && $_GET['is_teacher']);

            // 🔥 調試信息
            if ($isTeacher) {
                error_log("Teacher detected in poll: userId=$userId, userName=" . ($userName ?? 'null'));
            }

            // 更新當前用戶活動時間（老師不會被添加到用戶列表）
            if ($userId) {
                $users = updateUserActivity($users, $userId, $userName ?? null, $isTeacher);
                saveRoomData($usersFile, $users);
            }

            // 獲取客戶端時間戳，只返回新的同步數據
            $clientTimestamp = (int)($_GET['timestamp'] ?? 0);
            $newSyncData = [];

            if (!empty($syncData)) {
                // 🔥 修復時間戳比較 - 統一使用毫秒級時間戳
                $newSyncData = array_filter($syncData, function ($sync) use ($clientTimestamp) {
                    // 如果客戶端時間戳為0，返回最近的5條記錄（初次連接）
                    if ($clientTimestamp === 0) {
                        return true; // 初次輪詢，返回所有同步數據
                    }

                    // 比較毫秒級時間戳
                    $syncTimestamp = isset($sync['timestamp']) ? (int)$sync['timestamp'] : 0;
                    return $syncTimestamp > $clientTimestamp;
                });

                // 🔥 如果是初次連接，只返回最近的1條記錄（避免大量舊數據）
                if ($clientTimestamp === 0 && count($newSyncData) > 1) {
                    $newSyncData = array_slice($newSyncData, -1);
                }
            }

            // 獲取聊天記錄
            $chatFile = "data/chat_{$roomId}.json";
            $chatHistory = [];
            if (file_exists($chatFile)) {
                $content = file_get_contents($chatFile);
                if ($content) {
                    $chatHistory = json_decode($content, true) ?: [];
                }
            }

            // 只返回新的聊天消息（基於時間戳）
            $newChatMessages = [];
            if (!empty($chatHistory)) {
                $newChatMessages = array_filter($chatHistory, function ($chatMsg) use ($clientTimestamp) {
                    // 如果客戶端時間戳為0，返回最近的10條記錄（初次連接）
                    if ($clientTimestamp === 0) {
                        return true;
                    }

                    // 🔥 修復時間戳比較邏輯
                    $msgTimestamp = isset($chatMsg['timestamp']) ? (int)$chatMsg['timestamp'] : 0;
                    $clientTimestampSeconds = (int)($clientTimestamp / 1000); // 轉換為秒級時間戳

                    // 🔥 使用更寬鬆的時間比較，確保新消息能被返回
                    return $msgTimestamp >= $clientTimestampSeconds;
                });

                // 如果是初次連接，只返回最近的10條記錄
                if ($clientTimestamp === 0 && count($newChatMessages) > 10) {
                    $newChatMessages = array_slice($newChatMessages, -10);
                }
            }

            // 🔥 添加調試日誌 
            if (count($newChatMessages) > 0) {
                error_log("💬 輪詢返回 " . count($newChatMessages) . " 條聊天消息給房間: $roomId");
            }

            echo json_encode([
                'success' => true,
                'action' => 'poll',
                'timestamp' => time(), // 🔥 保持秒級時間戳
                'server_timestamp_ms' => time() * 1000, // 🔥 添加毫秒級時間戳給客戶端參考
                'room_id' => $roomId,
                'users' => array_values($users),
                'online_count' => count($users),
                'messages' => array_values($newChatMessages), // 🗨️ 返回聊天消息
                'chat_messages' => array_values($newChatMessages), // 🔥 添加專用聊天消息字段
                'code_changes' => array_values($newSyncData),
                'sync_data_count' => count($newSyncData), // 🔥 添加同步數據計數
                'client_timestamp' => $clientTimestamp, // 🔥 回傳客戶端時間戳用於調試
                'debug_info' => [ // 🔥 添加調試信息
                    'current_user_id' => $userId,
                    'is_teacher' => $isTeacher,
                    'user_names' => array_column($users, 'name'),
                    'total_users_before_cleanup' => count(loadRoomData($usersFile)),
                    'chat_messages_count' => count($newChatMessages)
                ],
                'room_info' => [
                    'id' => $roomId,
                    'connection_mode' => 'http_polling',
                    'platform' => 'zeabur',
                    'sync_frequency' => 'adaptive_500ms'
                ]
            ]);
            break;

        case 'join':
            // 用戶加入房間
            $userName = $_POST['user_name'] ?? $_GET['user_name'] ?? $userId;
            $isTeacher = (bool)($_POST['is_teacher'] ?? $_GET['is_teacher'] ?? false);

            if (!$userId) {
                throw new Exception('缺少 user_id 參數');
            }

            // 🗄️ 保存用戶到資料庫
            try {
                if ($userName && $userName !== $userId) {
                    $pdo = getDbConnection();

                    // 檢查用戶是否存在
                    $checkSql = "SELECT id FROM users WHERE user_name = :user_name";
                    $checkStmt = $pdo->prepare($checkSql);
                    $checkStmt->bindValue(':user_name', $userName);
                    $checkStmt->execute();
                    $existingUser = $checkStmt->fetch();

                    if ($existingUser) {
                        // 更新現有用戶
                        $updateSql = "UPDATE users 
                                      SET last_login_at = CURRENT_TIMESTAMP,
                                          is_teacher = :is_teacher,
                                          last_room_id = :room_id
                                      WHERE user_name = :user_name";
                        $updateStmt = $pdo->prepare($updateSql);
                        $updateStmt->bindValue(':user_name', $userName);
                        $updateStmt->bindValue(':is_teacher', $isTeacher);
                        $updateStmt->bindValue(':room_id', $roomId);
                        $updateStmt->execute();
                    } else {
                        // 創建新用戶
                        $insertSql = "INSERT INTO users (user_name, is_teacher, last_login_at, last_room_id)
                                      VALUES (:user_name, :is_teacher, CURRENT_TIMESTAMP, :room_id)";
                        $insertStmt = $pdo->prepare($insertSql);
                        $insertStmt->bindValue(':user_name', $userName);
                        $insertStmt->bindValue(':is_teacher', $isTeacher);
                        $insertStmt->bindValue(':room_id', $roomId);
                        $insertStmt->execute();
                    }

                    // 記錄登入日誌
                    $logSql = "INSERT INTO user_login_logs (user_name, user_id, room_id, is_teacher, login_time)
                               VALUES (:user_name, :user_id, :room_id, :is_teacher, CURRENT_TIMESTAMP)";
                    $logStmt = $pdo->prepare($logSql);
                    $logStmt->bindValue(':user_name', $userName);
                    $logStmt->bindValue(':user_id', $userId);
                    $logStmt->bindValue(':room_id', $roomId);
                    $logStmt->bindValue(':is_teacher', $isTeacher);
                    $logStmt->execute();
                }
            } catch (Exception $e) {
                // 資料庫錯誤不影響房間加入功能，記錄錯誤但繼續
                error_log("Database error in join: " . $e->getMessage());
            }

            // 📁 文件系統處理（保留作為備份）
            $users = loadRoomData($usersFile);
            $users = cleanExpiredUsers($users);

            // 🔥 重新整理檢測：主動清理同名用戶的舊會話
            if ($userName && !$isTeacher) {
                $oldUserCount = count($users);
                $users = forceRemoveUser($users, '', $userName); // 清理同名的舊用戶
                $removedCount = $oldUserCount - count($users);

                if ($removedCount > 0) {
                    error_log("🔄 檢測到重新整理：移除了 $removedCount 個同名用戶($userName)的舊會話");
                }
            }

            $users = updateUserActivity($users, $userId, $userName, $isTeacher);
            saveRoomData($usersFile, $users);

            // 🔄 獲取房間最新代碼
            $roomCode = '';
            $roomCodeFile = $dataDir . '/code_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId) . '.txt';
            if (file_exists($roomCodeFile)) {
                $roomCode = file_get_contents($roomCodeFile);
            }

            // 獲取代碼歷史（最近的同步記錄）
            $syncData = loadRoomData($syncFile);
            $latestSync = null;
            if (!empty($syncData)) {
                // 獲取最新的代碼同步記錄
                $latestSync = end($syncData);
                if ($latestSync && !empty($latestSync['code'])) {
                    $roomCode = $latestSync['code'];
                }
            }

            // 獲取聊天歷史
            $chatFile = "data/chat_{$roomId}.json";
            $chatHistory = [];
            if (file_exists($chatFile)) {
                $content = file_get_contents($chatFile);
                if ($content) {
                    $chatHistory = json_decode($content, true) ?: [];
                    // 只返回最近的10條聊天記錄
                    if (count($chatHistory) > 10) {
                        $chatHistory = array_slice($chatHistory, -10);
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'action' => 'join',
                'message' => '成功加入房間',
                'room_id' => $roomId,
                'user_id' => $userId,
                'user_name' => $userName,
                'is_teacher' => $isTeacher,
                'users' => array_values($users),
                'online_count' => count($users),
                'room_code' => $roomCode,
                'latest_sync' => $latestSync,
                'code_loaded' => !empty($roomCode),
                'refresh_detected' => isset($removedCount) ? $removedCount > 0 : false, // 🔥 返回是否檢測到重新整理
                'chat_history' => array_values($chatHistory) // 🗨️ 返回聊天歷史
            ]);
            break;

        case 'leave':
            // 用戶離開房間
            if (!$userId) {
                throw new Exception('缺少 user_id 參數');
            }

            // 🔥 詳細調試日誌
            error_log("=== LEAVE DEBUG ===");
            error_log("User ID: " . $userId);
            error_log("Room ID: " . $roomId);
            error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
            error_log("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'));
            error_log("Time: " . date('Y-m-d H:i:s'));

            $users = loadRoomData($usersFile);

            // 記錄離開前的用戶列表
            error_log("Users before leave: " . json_encode($users));

            $removedUsers = array_filter($users, function ($user) use ($userId) {
                return $user['id'] === $userId;
            });

            $users = array_filter($users, function ($user) use ($userId) {
                return $user['id'] !== $userId;
            });

            // 記錄離開後的用戶列表
            error_log("Removed users: " . json_encode($removedUsers));
            error_log("Users after leave: " . json_encode($users));

            saveRoomData($usersFile, $users);

            $response = [
                'success' => true,
                'action' => 'leave',
                'message' => '已離開房間',
                'room_id' => $roomId,
                'user_id' => $userId,
                'users' => array_values($users),
                'online_count' => count($users),
                'removed_count' => count($removedUsers),
                'timestamp' => time()
            ];

            error_log("Leave response: " . json_encode($response));
            error_log("=== END LEAVE DEBUG ===");

            echo json_encode($response);
            break;

        case 'sync_code':
            // 代碼同步 - 接收並廣播代碼變更
            $codeContent = $_POST['code'] ?? '';
            $changeType = $_POST['change_type'] ?? 'update';
            $userInfo = $_POST['user_info'] ?? [];
            $userName = $_POST['username'] ?? $_POST['user_name'] ?? $userId;

            if (!$userId) {
                throw new Exception('缺少 user_id 參數');
            }

            $syncData = loadRoomData($syncFile);

            // 🔥 獲取當前在線用戶，確保只在多用戶環境下同步
            $users = loadRoomData($usersFile);
            $users = cleanExpiredUsers($users);
            $onlineUserCount = count($users);

            // 添加新的同步記錄
            $syncRecord = [
                'id' => uniqid(),
                'timestamp' => time() * 1000, // 毫秒時間戳
                'user_id' => $userId,
                'username' => $userName,
                'change_type' => $changeType,
                'code' => $codeContent,
                'user_info' => $userInfo,
                'online_users' => $onlineUserCount,
                'room_id' => $roomId
            ];

            $syncData[] = $syncRecord;

            // 只保留最近 100 條記錄
            if (count($syncData) > 100) {
                $syncData = array_slice($syncData, -100);
            }

            saveRoomData($syncFile, $syncData);

            // 💾 保存房間最新代碼到專用文件
            $roomCodeFile = $dataDir . '/code_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId) . '.txt';
            file_put_contents($roomCodeFile, $codeContent);

            // 🔄 更新用戶活動狀態
            $users = updateUserActivity($users, $userId, $userName);
            saveRoomData($usersFile, $users);

            echo json_encode([
                'success' => true,
                'action' => 'sync_code',
                'message' => '代碼同步成功',
                'sync_id' => $syncRecord['id'],
                'timestamp' => $syncRecord['timestamp'],
                'online_users' => $onlineUserCount,
                'broadcast_to_users' => array_values($users)
            ]);
            break;

        case 'save_room_code':
            // 保存房間代碼
            $codeContent = $_POST['code'] ?? '';
            $saveType = $_POST['save_type'] ?? 'manual'; // manual, auto

            if (!$userId) {
                throw new Exception('缺少 user_id 參數');
            }

            if (empty($codeContent)) {
                throw new Exception('代碼內容不能為空');
            }

            // 保存到房間代碼文件
            $roomCodeFile = $dataDir . '/code_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId) . '.txt';
            $result = file_put_contents($roomCodeFile, $codeContent);

            if ($result === false) {
                throw new Exception('保存房間代碼失敗');
            }

            // 記錄保存歷史
            $roomHistoryFile = $dataDir . '/history_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId) . '.json';
            $historyData = loadRoomData($roomHistoryFile);

            $historyRecord = [
                'id' => uniqid(),
                'timestamp' => time() * 1000,
                'user_id' => $userId,
                'save_type' => $saveType,
                'code_length' => strlen($codeContent),
                'code_hash' => md5($codeContent)
            ];

            $historyData[] = $historyRecord;

            // 只保留最近 50 條歷史記錄
            if (count($historyData) > 50) {
                $historyData = array_slice($historyData, -50);
            }

            saveRoomData($roomHistoryFile, $historyData);

            echo json_encode([
                'success' => true,
                'action' => 'save_room_code',
                'message' => '房間代碼保存成功',
                'save_id' => $historyRecord['id'],
                'timestamp' => $historyRecord['timestamp'],
                'code_length' => strlen($codeContent)
            ]);
            break;

        case 'load_room_code':
            // 載入房間代碼
            $roomCodeFile = $dataDir . '/code_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId) . '.txt';

            if (file_exists($roomCodeFile)) {
                $roomCode = file_get_contents($roomCodeFile);
                $codeInfo = [
                    'code' => $roomCode,
                    'length' => strlen($roomCode),
                    'last_modified' => filemtime($roomCodeFile)
                ];
            } else {
                $codeInfo = [
                    'code' => '',
                    'length' => 0,
                    'last_modified' => null
                ];
            }

            echo json_encode([
                'success' => true,
                'action' => 'load_room_code',
                'room_id' => $roomId,
                'code_info' => $codeInfo
            ]);
            break;

        case 'conflict_notification':
            // 衝突通知處理
            $targetUser = $_POST['targetUser'] ?? '';
            $conflictWith = $_POST['conflictWith'] ?? '';
            $message = $_POST['message'] ?? '';
            $conflictData = $_POST['conflictData'] ?? '';

            if (!$userId || !$targetUser) {
                throw new Exception('缺少必要的用戶信息');
            }

            // 記錄衝突日誌（可選）
            $conflictLogFile = $dataDir . '/conflicts_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $roomId) . '.json';
            $conflictLogs = loadRoomData($conflictLogFile);

            $conflictRecord = [
                'id' => uniqid(),
                'timestamp' => time() * 1000,
                'room_id' => $roomId,
                'initiator' => $userId,
                'target_user' => $targetUser,
                'conflict_with' => $conflictWith,
                'message' => $message,
                'conflict_data' => $conflictData
            ];

            $conflictLogs[] = $conflictRecord;

            // 只保留最近 20 條衝突記錄
            if (count($conflictLogs) > 20) {
                $conflictLogs = array_slice($conflictLogs, -20);
            }

            saveRoomData($conflictLogFile, $conflictLogs);

            echo json_encode([
                'success' => true,
                'action' => 'conflict_notification',
                'message' => '衝突通知已記錄',
                'conflict_id' => $conflictRecord['id'],
                'timestamp' => $conflictRecord['timestamp']
            ]);
            break;

        case 'status':
            // 檢查系統狀態
            echo json_encode([
                'success' => true,
                'action' => 'status',
                'platform' => $_ENV['PLATFORM'] ?? 'zeabur',
                'websocket_available' => false,
                'connection_mode' => 'http_polling',
                'api_version' => '2.0.0',
                'timestamp' => time(),
                'room_count' => count(glob($dataDir . '/room_*.json')),
                'features' => [
                    'realtime_sync' => true,
                    'adaptive_polling' => true,
                    'code_collaboration' => true,
                    'user_presence' => true
                ],
                'message' => 'API 服務正常運行 - 即時同步已啟用'
            ]);
            break;

        case 'deploy_cleanup':
            $cleanup_result = deployCleanup();
            echo json_encode([
                'success' => true,
                'message' => 'Deploy cleanup completed',
                'data' => $cleanup_result,
                'timestamp' => date('c')
            ]);
            break;

        case 'deploy_version':
            $deploy_time = file_exists('data/deploy_time.json') ?
                json_decode(file_get_contents('data/deploy_time.json'), true) :
                null;

            echo json_encode([
                'success' => true,
                'deploy_time' => $deploy_time,
                'current_time' => date('c'),
                'is_new_deploy' => isNewDeploy(),
                'timestamp' => date('c')
            ]);
            break;

        case 'get_recent_users':
            // 🗄️ 從資料庫獲取最近的用戶列表
            try {
                $limit = min((int)($_GET['limit'] ?? 10), 50); // 最多50個
                $pdo = getDbConnection();

                $sql = "SELECT user_name, is_teacher, last_login_at, created_at 
                        FROM users 
                        WHERE user_name IS NOT NULL AND user_name != ''
                        ORDER BY last_login_at DESC 
                        LIMIT :limit";

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $users = $stmt->fetchAll();

                echo json_encode([
                    'success' => true,
                    'action' => 'get_recent_users',
                    'users' => $users,
                    'count' => count($users),
                    'limit' => $limit,
                    'timestamp' => time()
                ]);
            } catch (Exception $e) {
                throw new Exception('獲取用戶列表失敗: ' . $e->getMessage());
            }
            break;

        case 'save_user_to_db':
            // 🗄️ 保存用戶到資料庫
            $userName = $_POST['user_name'] ?? $_GET['user_name'] ?? '';
            $isTeacher = (bool)($_POST['is_teacher'] ?? $_GET['is_teacher'] ?? false);

            if (!$userName) {
                throw new Exception('缺少 user_name 參數');
            }

            try {
                $pdo = getDbConnection();

                // 檢查用戶是否存在
                $checkSql = "SELECT id FROM users WHERE user_name = :user_name";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->bindValue(':user_name', $userName);
                $checkStmt->execute();
                $existingUser = $checkStmt->fetch();

                if ($existingUser) {
                    // 更新現有用戶
                    $updateSql = "UPDATE users 
                                  SET last_login_at = CURRENT_TIMESTAMP,
                                      is_teacher = :is_teacher,
                                      last_room_id = :room_id
                                  WHERE user_name = :user_name";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->bindValue(':user_name', $userName);
                    $updateStmt->bindValue(':is_teacher', $isTeacher);
                    $updateStmt->bindValue(':room_id', $roomId);
                    $updateStmt->execute();

                    $message = '用戶信息已更新';
                } else {
                    // 創建新用戶
                    $insertSql = "INSERT INTO users (user_name, is_teacher, last_login_at, last_room_id)
                                  VALUES (:user_name, :is_teacher, CURRENT_TIMESTAMP, :room_id)";
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->bindValue(':user_name', $userName);
                    $insertStmt->bindValue(':is_teacher', $isTeacher);
                    $insertStmt->bindValue(':room_id', $roomId);
                    $insertStmt->execute();

                    $message = '新用戶已創建';
                }

                // 記錄登入日誌
                $logSql = "INSERT INTO user_login_logs (user_name, user_id, room_id, is_teacher, login_time)
                           VALUES (:user_name, :user_id, :room_id, :is_teacher, CURRENT_TIMESTAMP)";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->bindValue(':user_name', $userName);
                $logStmt->bindValue(':user_id', $userId ?: uniqid('user_'));
                $logStmt->bindValue(':room_id', $roomId);
                $logStmt->bindValue(':is_teacher', $isTeacher);
                $logStmt->execute();

                echo json_encode([
                    'success' => true,
                    'action' => 'save_user_to_db',
                    'message' => $message,
                    'user_name' => $userName,
                    'is_teacher' => $isTeacher,
                    'room_id' => $roomId,
                    'timestamp' => time()
                ]);
            } catch (Exception $e) {
                throw new Exception('保存用戶失敗: ' . $e->getMessage());
            }
            break;

        case 'get_online_users':
            // 🗄️ 獲取在線用戶（結合文件系統和資料庫）
            try {
                // 從文件系統獲取在線用戶
                $users = loadRoomData($usersFile);
                $users = cleanExpiredUsers($users);

                // 從資料庫補充用戶信息
                if (!empty($users)) {
                    $pdo = getDbConnection();
                    $userNames = array_column($users, 'name');
                    $placeholders = str_repeat('?,', count($userNames) - 1) . '?';

                    $sql = "SELECT user_name, is_teacher FROM users WHERE user_name IN ($placeholders)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($userNames);
                    $dbUsers = $stmt->fetchAll();

                    // 合併資料庫信息
                    $dbUserMap = [];
                    foreach ($dbUsers as $dbUser) {
                        $dbUserMap[$dbUser['user_name']] = $dbUser;
                    }

                    foreach ($users as &$user) {
                        if (isset($dbUserMap[$user['name']])) {
                            $user['is_teacher'] = (bool)$dbUserMap[$user['name']]['is_teacher'];
                        }
                    }
                }

                echo json_encode([
                    'success' => true,
                    'action' => 'get_online_users',
                    'users' => array_values($users),
                    'online_count' => count($users),
                    'room_id' => $roomId,
                    'timestamp' => time()
                ]);
            } catch (Exception $e) {
                throw new Exception('獲取在線用戶失敗: ' . $e->getMessage());
            }
            break;

        case 'cleanup_duplicates':
            // 🧹 清理重複用戶
            $targetUserName = $_POST['user_name'] ?? $_GET['user_name'] ?? '';

            if (!$targetUserName) {
                throw new Exception('缺少 user_name 參數');
            }

            $users = loadRoomData($usersFile);
            $originalCount = count($users);

            // 清理同名用戶（保留最新的）
            $users = forceRemoveUser($users, '', $targetUserName);
            $removedCount = $originalCount - count($users);

            saveRoomData($usersFile, $users);

            echo json_encode([
                'success' => true,
                'action' => 'cleanup_duplicates',
                'message' => "清理了 $removedCount 個重複用戶",
                'user_name' => $targetUserName,
                'removed_count' => $removedCount,
                'remaining_users' => array_values($users),
                'online_count' => count($users),
                'timestamp' => time()
            ]);
            break;

        case 'get_client_info':
            // 獲取客戶端網路信息
            $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            // 嘗試獲取真實IP（如果有代理）
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $clientIP = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                $clientIP = $_SERVER['HTTP_X_REAL_IP'];
            }

            echo json_encode([
                'success' => true,
                'action' => 'get_client_info',
                'ip' => $clientIP,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'server_info' => [
                    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
                    'server_port' => $_SERVER['SERVER_PORT'] ?? 'unknown',
                    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown'
                ],
                'timestamp' => time()
            ]);
            break;

        case 'send_chat':
            // 🗨️ 發送聊天消息
            $message = $_POST['message'] ?? '';
            $senderName = $_POST['user_id'] ?? '';

            if (empty($message)) {
                throw new Exception('消息內容不能為空');
            }

            if (empty($senderName)) {
                throw new Exception('發送者名稱不能為空');
            }

            // 清理和驗證消息
            $message = trim($message);
            if (strlen($message) > 1000) {
                throw new Exception('消息內容過長（最多1000字符）');
            }

            // 從資料庫獲取發送者信息，判斷是否為教師
            $isTeacher = false;
            try {
                $pdo = getDbConnection();
                $stmt = $pdo->prepare("SELECT is_teacher FROM users WHERE user_name = :user_name");
                $stmt->bindValue(':user_name', $senderName);
                $stmt->execute();
                $userInfo = $stmt->fetch();
                if ($userInfo) {
                    $isTeacher = (bool)$userInfo['is_teacher'];
                }
            } catch (Exception $e) {
                // 如果查詢失敗，默認為學生
                error_log("查詢用戶教師狀態失敗: " . $e->getMessage());
            }

            // 保存聊天消息到文件
            $chatMessage = [
                'id' => uniqid('msg_'),
                'userName' => $senderName,
                'message' => $message,
                'timestamp' => time(),
                'isTeacher' => $isTeacher,
                'type' => 'user'
            ];

            // 讀取現有聊天記錄
            $chatFile = "data/chat_{$roomId}.json";
            $chatHistory = [];
            if (file_exists($chatFile)) {
                $content = file_get_contents($chatFile);
                if ($content) {
                    $chatHistory = json_decode($content, true) ?: [];
                }
            }

            // 添加新消息
            $chatHistory[] = $chatMessage;

            // 限制聊天記錄數量（保留最新的100條）
            if (count($chatHistory) > 100) {
                $chatHistory = array_slice($chatHistory, -100);
            }

            // 保存聊天記錄
            if (!file_exists(dirname($chatFile))) {
                mkdir(dirname($chatFile), 0755, true);
            }
            file_put_contents($chatFile, json_encode($chatHistory, JSON_UNESCAPED_UNICODE));

            // 🔥 添加調試日誌
            error_log("💬 聊天消息已保存: room={$roomId}, user={$senderName}, message=" . substr($message, 0, 50));

            echo json_encode([
                'success' => true,
                'action' => 'send_chat',
                'message' => '消息發送成功',
                'chat_message' => $chatMessage,
                'room_id' => $roomId,
                'timestamp' => time()
            ]);
            break;

        case 'ping':
            // 簡單的ping測試
            echo json_encode([
                'success' => true,
                'action' => 'ping',
                'message' => 'pong',
                'timestamp' => time(),
                'response_time' => microtime(true)
            ]);
            break;

        case '':
        case null:
            // 沒有指定 action，返回基本狀態信息
            echo json_encode([
                'success' => true,
                'action' => 'default',
                'message' => '請指定操作類型 (action)',
                'available_actions' => ['poll', 'join', 'leave', 'sync_code', 'status', 'deploy_cleanup', 'deploy_version', 'get_client_info', 'ping'],
                'platform' => $_ENV['PLATFORM'] ?? 'zeabur',
                'connection_mode' => 'http_polling',
                'timestamp' => time()
            ]);
            break;

        default:
            throw new Exception('不支援的操作: ' . $action);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'action' => $action,
        'timestamp' => time()
    ]);
}
