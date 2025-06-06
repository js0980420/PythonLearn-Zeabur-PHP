<?php

/**
 * WebSocket 服務器主啟動檔案
 * 處理多人即時協作和衝突檢測
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/backend/classes/Database.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// WebSocket 訊息處理類別
class CollaborationServer implements \Ratchet\MessageComponentInterface {
    private $clients;
    private $rooms;
    private $userJoinTimes;
    private $userFirstUpdate;
    private $database;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->userJoinTimes = [];
        $this->userFirstUpdate = [];
        
        // 初始化數據庫
        try {
            $this->database = Database::getInstance();
        } catch (Exception $e) {
            echo "數據庫連接失敗: " . $e->getMessage() . "\n";
            $this->database = null;
        }
        
        echo "WebSocket 服務器啟動中...\n";
    }
    
    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "新連接 ({$conn->resourceId})\n";
    }
    
    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                return;
            }
            
            switch ($data['type']) {
                case 'join_room':
                    $this->handleJoinRoom($from, $data);
                    break;
                case 'leave_room':
                    $this->handleLeaveRoom($from, $data);
                    break;
                case 'code_change':
                    $this->handleCodeChange($from, $data);
                    break;
                case 'chat_message':
                    $this->handleChatMessage($from, $data);
                    break;
                case 'cursor_position':
                    $this->handleCursorPosition($from, $data);
                    break;
                case 'get_initial_data':
                    $this->handleGetInitialData($from, $data);
                    break;
                case 'ai_request':
                    $this->handleAIRequest($from, $data);
                    break;
                case 'save_code':
                    $this->handleSaveCode($from, $data);
                    break;
                case 'load_code':
                    $this->handleLoadCode($from, $data);
                    break;
                case 'ping':
                    $this->handlePing($from, $data);
                    break;
                default:
                    echo "未知訊息類型: {$data['type']}\n";
                    $from->send(json_encode([
                        'type' => 'error',
                        'error' => "未知訊息類型: {$data['type']}",
                        'details' => "支援的消息類型: join_room, leave_room, code_change, save_code, load_code, ping, ai_request"
                    ]));
            }
            
        } catch (Exception $e) {
            echo "處理訊息錯誤: " . $e->getMessage() . "\n";
        }
    }
    
    public function onClose(\Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // 清理用戶數據
        if (isset($conn->roomId) && isset($conn->userId)) {
            $this->handleLeaveRoom($conn, [
                'room_id' => $conn->roomId,
                'user_id' => $conn->userId
            ]);
        }
        
        echo "連接關閉 ({$conn->resourceId})\n";
    }
    
    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "錯誤: {$e->getMessage()}\n";
        $conn->close();
    }
    
    private function handleJoinRoom($conn, $data) {
        $roomId = $data['room_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $username = $data['username'] ?? '訪客';
        
        if (!$roomId || !$userId) {
            return;
        }
        
        // 設置連接屬性
        $conn->roomId = $roomId;
        $conn->userId = $userId;
        $conn->username = $username;
        
        // 記錄加入時間
        $joinKey = "{$roomId}_{$userId}";
        $this->userJoinTimes[$joinKey] = time();
        $this->userFirstUpdate[$joinKey] = true;
        
        // 初始化房間
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [
                'users' => [],
                'current_code' => "# 歡迎使用 Python 協作編程！\nprint('Hello, World!')",
                'last_update' => time()
            ];
        }
        
        // 添加用戶到房間
        $this->rooms[$roomId]['users'][$userId] = [
            'connection' => $conn,
            'username' => $username,
            'join_time' => time()
        ];
        
        echo "用戶 {$username} ({$userId}) 即將加入房間 {$roomId}\n";
        
        // 發送房間狀態
        $conn->send(json_encode([
            'type' => 'room_joined',
            'room_id' => $roomId,
            'user_id' => $userId,
            'current_code' => $this->rooms[$roomId]['current_code'],
            'users' => $this->getUserList($roomId)
        ]));
        
        // 通知其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'users' => $this->getUserList($roomId)
        ], $userId);
    }
    
    private function handleLeaveRoom($conn, $data) {
        $roomId = $data['room_id'] ?? $conn->roomId ?? null;
        $userId = $data['user_id'] ?? $conn->userId ?? null;
        
        if (!$roomId || !$userId) {
            return;
        }
        
        // 從房間移除用戶
        if (isset($this->rooms[$roomId]['users'][$userId])) {
            unset($this->rooms[$roomId]['users'][$userId]);
            
            // 清理時間記錄
            $joinKey = "{$roomId}_{$userId}";
            unset($this->userJoinTimes[$joinKey]);
            unset($this->userFirstUpdate[$joinKey]);
            
            // 通知其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'user_left',
                'user_id' => $userId,
                'users' => $this->getUserList($roomId)
            ]);
            
            // 如果房間為空，清理房間
            if (empty($this->rooms[$roomId]['users'])) {
                unset($this->rooms[$roomId]);
                echo "房間 {$roomId} 已清空\n";
            }
        }
    }
    
    private function handleCodeChange($conn, $data) {
        $roomId = $data['room_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $code = $data['code'] ?? '';
        
        if (!$roomId || !$userId) {
            return;
        }
        
        // 檢查初始化期
        $joinKey = "{$roomId}_{$userId}";
        $joinTime = $this->userJoinTimes[$joinKey] ?? 0;
        $isInInitPeriod = (time() - $joinTime) < 10; // 10秒初始化期
        $isFirstUpdate = $this->userFirstUpdate[$joinKey] ?? false;
        
        // 跳過衝突檢測的條件
        if ($isInInitPeriod || $isFirstUpdate) {
            echo "跳過衝突檢測: 用戶 {$userId} 在初始化期或首次更新\n";
            
            // 標記非首次更新
            $this->userFirstUpdate[$joinKey] = false;
        } else {
            // 進行衝突檢測（簡化版）
            if (isset($this->rooms[$roomId])) {
                $currentCode = $this->rooms[$roomId]['current_code'] ?? '';
                $lastUpdate = $this->rooms[$roomId]['last_update'] ?? 0;
                
                // 檢查是否有其他用戶在同時編輯
                $recentActivity = (time() - $lastUpdate) < 2; // 2秒內有活動
                $hasOtherUsers = count($this->rooms[$roomId]['users']) > 1;
                
                if ($hasOtherUsers && $recentActivity && $code !== $currentCode) {
                    // 檢查其他用戶是否也在初始化期
                    $otherUsersInInit = false;
                    foreach ($this->rooms[$roomId]['users'] as $otherUserId => $userData) {
                        if ($otherUserId !== $userId) {
                            $otherJoinKey = "{$roomId}_{$otherUserId}";
                            $otherJoinTime = $this->userJoinTimes[$otherJoinKey] ?? 0;
                            if ((time() - $otherJoinTime) < 10) {
                                $otherUsersInInit = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$otherUsersInInit) {
                        // 發送衝突警告
                        $this->sendConflictDetection($conn, $roomId, $currentCode, $code);
                        return;
                    }
                }
            }
        }
        
        // 更新房間代碼
        if (isset($this->rooms[$roomId])) {
            $this->rooms[$roomId]['current_code'] = $code;
            $this->rooms[$roomId]['last_update'] = time();
        }
        
        echo "廣播代碼變更: 用戶 {$conn->username} 在房間 {$roomId}\n";
        
        // 廣播給其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'code_sync',
            'code' => $code,
            'user_id' => $userId,
            'username' => $conn->username ?? '未知用戶'
        ], $userId);
    }
    
    private function handleChatMessage($conn, $data) {
        $roomId = $data['room_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $message = $data['message'] ?? '';
        
        if (!$roomId || !$userId || !$message) {
            return;
        }
        
        $chatData = [
            'type' => 'chat_message',
            'room_id' => $roomId,
            'user_id' => $userId,
            'username' => $conn->username ?? '未知用戶',
            'message' => $message,
            'timestamp' => time()
        ];
        
        // 廣播聊天訊息給房間內所有用戶
        $this->broadcastToRoom($roomId, $chatData);
        
        echo "聊天訊息: {$conn->username} 在房間 {$roomId}: {$message}\n";
    }
    
    private function handleCursorPosition($conn, $data) {
        $roomId = $data['room_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        
        if (!$roomId || !$userId) {
            return;
        }
        
        // 廣播游標位置
        $this->broadcastToRoom($roomId, [
            'type' => 'cursor_position',
            'user_id' => $userId,
            'username' => $conn->username ?? '未知用戶',
            'position' => $data['position'] ?? null
        ], $userId);
    }
    
    private function handleGetInitialData($conn, $data) {
        $roomId = $data['room_id'] ?? null;
        
        if (!$roomId || !isset($this->rooms[$roomId])) {
            return;
        }
        
        $conn->send(json_encode([
            'type' => 'initial_data',
            'room_id' => $roomId,
            'current_code' => $this->rooms[$roomId]['current_code'],
            'user_list' => $this->getUserList($roomId)
        ]));
    }
    
    private function sendConflictDetection($conn, $roomId, $currentCode, $newCode) {
        $conflictData = [
            'type' => 'conflict_detected',
            'room_id' => $roomId,
            'current_code' => $currentCode,
            'new_code' => $newCode,
            'conflict_id' => uniqid('conflict_'),
            'timestamp' => time()
        ];
        
        $conn->send(json_encode($conflictData));
        echo "衝突檢測: 房間 {$roomId} 發現代碼衝突\n";
    }
    
    private function broadcastToRoom($roomId, $message, $excludeUserId = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        $messageJson = json_encode($message);
        
        foreach ($this->rooms[$roomId]['users'] as $userId => $userData) {
            if ($excludeUserId && $userId === $excludeUserId) {
                continue;
            }
            
            if (isset($userData['connection'])) {
                $userData['connection']->send($messageJson);
            }
        }
    }
    
    private function handleAIRequest($conn, $data) {
        $action = $data['action'] ?? '';
        $requestId = $data['requestId'] ?? uniqid('ai_');
        $code = $data['data']['code'] ?? '';
        
        if (!$code) {
            $conn->send(json_encode([
                'type' => 'ai_response',
                'requestId' => $requestId,
                'success' => false,
                'error' => '程式碼不能為空'
            ]));
            return;
        }
        
        echo "AI 請求: {$action} (ID: {$requestId})\n";
        
        try {
            // 調用後端 AI API
            $response = $this->callAIAPI($action, $code);
            
            // 發送回應給用戶
            $conn->send(json_encode([
                'type' => 'ai_response',
                'requestId' => $requestId,
                'success' => true,
                'data' => $response
            ]));
            
        } catch (Exception $e) {
            echo "AI 請求錯誤: " . $e->getMessage() . "\n";
            
            $conn->send(json_encode([
                'type' => 'ai_response',
                'requestId' => $requestId,
                'success' => false,
                'error' => 'AI 服務暫時不可用'
            ]));
        }
    }
    
    private function callAIAPI($action, $code) {
        // 調用後端 AI API
        $postData = json_encode([
            'action' => $action,
            'code' => $code,
            'room_id' => 1, // 預設房間ID
            'user_id' => 'websocket_user'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($postData)
                ],
                'content' => $postData,
                'timeout' => 30
            ]
        ]);
        
        // 修正 API 路徑
        $apiUrl = 'http://localhost:8000/api/ai';
        $response = file_get_contents($apiUrl, false, $context);
        
        if ($response === false) {
            throw new Exception('無法連接到 AI API: ' . $apiUrl);
        }
        
        $result = json_decode($response, true);
        
        if (!$result) {
            throw new Exception('AI API 返回無效 JSON: ' . substr($response, 0, 100));
        }
        
        if (!$result['success']) {
            throw new Exception($result['message'] ?? 'AI API 錯誤');
        }
        
        return $result['data'];
    }
    
    private function handleSaveCode($conn, $data) {
        $roomId = $data['room_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $code = $data['code'] ?? '';
        
        if (!$roomId || !$userId) {
            $conn->send(json_encode([
                'type' => 'error',
                'error' => '缺少必要參數',
                'details' => '保存代碼需要 room_id 和 user_id'
            ]));
            return;
        }
        
        // 更新房間代碼
        if (isset($this->rooms[$roomId])) {
            $this->rooms[$roomId]['current_code'] = $code;
            $this->rooms[$roomId]['last_update'] = time();
        }
        
        echo "代碼保存: 用戶 {$userId} 在房間 {$roomId}\n";
        
        // 確認保存成功
        $conn->send(json_encode([
            'type' => 'code_saved',
            'room_id' => $roomId,
            'user_id' => $userId,
            'timestamp' => time()
        ]));
    }
    
    private function handleLoadCode($conn, $data) {
        $roomId = $data['room_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        
        if (!$roomId || !$userId) {
            $conn->send(json_encode([
                'type' => 'error',
                'error' => '缺少必要參數',
                'details' => '載入代碼需要 room_id 和 user_id'
            ]));
            return;
        }
        
        // 獲取房間代碼
        $code = '';
        if (isset($this->rooms[$roomId])) {
            $code = $this->rooms[$roomId]['current_code'];
        }
        
        echo "代碼載入: 用戶 {$userId} 從房間 {$roomId}\n";
        
        // 發送代碼
        $conn->send(json_encode([
            'type' => 'code_loaded',
            'room_id' => $roomId,
            'user_id' => $userId,
            'code' => $code,
            'timestamp' => time()
        ]));
    }
    
    private function handlePing($conn, $data) {
        // 回應心跳檢測
        $conn->send(json_encode([
            'type' => 'pong',
            'timestamp' => time()
        ]));
    }

    private function getUserList($roomId) {
        if (!isset($this->rooms[$roomId])) {
            return [];
        }
        
        $userList = [];
        foreach ($this->rooms[$roomId]['users'] as $userId => $userData) {
            $userList[] = [
                'user_id' => $userId,
                'username' => $userData['username'],
                'join_time' => $userData['join_time']
            ];
        }
        
        return $userList;
    }
}

// 啟動 WebSocket 服務器
try {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new CollaborationServer()
            )
        ),
        8080
    );
    
    echo "✅ WebSocket 服務器啟動成功，監聽 localhost:8080\n";
    echo "🚀 準備接收連接...\n";
    
    $server->run();
    
} catch (Exception $e) {
    echo "❌ WebSocket 服務器啟動失敗: " . $e->getMessage() . "\n";
    echo "💡 請確保端口 8080 未被占用\n";
}

?> 