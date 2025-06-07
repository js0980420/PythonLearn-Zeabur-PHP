<?php
// 關閉錯誤顯示，避免破壞JSON響應
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Logger.php';

use Database;
use App\Logger;

// 設置CORS頭
APIResponse::setCORSHeaders();

// 初始化
$database = Database::getInstance();
$logger = new Logger('chat.log');

try {
    session_start();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            handleListChatMessages($database, $logger, $input);
            break;
            
        case 'send': // 雖然主要通過WebSocket，但提供HTTP接口作為備用或測試
            handleSendChatMessage($database, $logger, $input);
            break;
            
        default:
            echo APIResponse::error('無效的操作', 'E001');
    }
    
} catch (Exception $e) {
    $logger->error('聊天API錯誤', ['error' => $e->getMessage()]);
    echo APIResponse::error('系統錯誤', 'E010', 500);
}

function handleListChatMessages($database, $logger, $input) {
    $roomId = $_GET['room_id'] ?? $input['room_id'] ?? '';
    $limit = intval($_GET['limit'] ?? $input['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? $input['offset'] ?? 0);

    if (empty($roomId)) {
        echo APIResponse::error('房間ID不能為空', 'E001');
        return;
    }

    // 檢查房間是否存在
    $room = $database->fetch("SELECT id FROM rooms WHERE id = :room_id", ['room_id' => $roomId]);
    if (!$room) {
        echo APIResponse::error('房間不存在', 'E002', 404);
        return;
    }

    // 獲取聊天訊息
    $messages = $database->fetchAll(
        "SELECT cm.*, u.username 
         FROM chat_messages cm 
         JOIN users u ON cm.user_id = u.id 
         WHERE cm.room_id = :room_id 
         ORDER BY cm.created_at ASC 
         LIMIT :limit OFFSET :offset",
        ['room_id' => $roomId, 'limit' => $limit, 'offset' => $offset]
    );

    // 格式化訊息
    $formattedMessages = [];
    foreach ($messages as $message) {
        $formattedMessages[] = [
            'id' => $message['id'],
            'room_id' => $message['room_id'],
            'user_id' => $message['user_id'],
            'username' => $message['username'] ?? '未知用戶',
            'message_content' => $message['message_content'],
            'created_at' => $message['created_at']
        ];
    }
    
    $logger->info('獲取聊天記錄', ['room_id' => $roomId, 'count' => count($formattedMessages)]);
    echo APIResponse::success(['messages' => $formattedMessages], '聊天記錄獲取成功');
}

function handleSendChatMessage($database, $logger, $input) {
    $roomId = $input['room_id'] ?? '';
    $messageContent = $input['message_content'] ?? '';
    $userId = $_SESSION['user_id'] ?? null;

    if (empty($roomId) || empty($messageContent)) {
        echo APIResponse::error('房間ID和訊息內容不能為空', 'E001');
        return;
    }

    if (!$userId) {
        echo APIResponse::error('請先登入，才能發送訊息', 'E003', 401);
        return;
    }

    // 檢查房間是否存在
    $room = $database->fetch("SELECT id FROM rooms WHERE id = :room_id", ['room_id' => $roomId]);
    if (!$room) {
        echo APIResponse::error('房間不存在，無法發送訊息', 'E002', 404);
        return;
    }

    // 插入訊息
    $messageId = $database->insert('chat_messages', [
        'room_id' => $roomId,
        'user_id' => $userId,
        'message_content' => $messageContent
    ]);

    $logger->info('發送聊天訊息 (HTTP)', [
        'room_id' => $roomId, 
        'user_id' => $userId, 
        'message_id' => $messageId
    ]);
    echo APIResponse::success(['message_id' => $messageId], '訊息發送成功');
}

?> 