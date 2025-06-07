<?php
/**
 * 獨立WebSocket測試服務器
 * 端口：9082
 * 用途：測試WebSocket功能而不影響主服務器
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class TestWebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $rooms;
    protected $userConnections;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->userConnections = [];
        
        echo "🧪 WebSocket測試服務器啟動 (端口: 9082)\n";
        echo "📝 測試日誌將保存到: test-logs/websocket_test.log\n";
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->testId = uniqid('test_');
        
        $this->log("新連接: {$conn->testId}");
        
        // 發送歡迎消息
        $this->sendToConnection($conn, [
            'type' => 'connection_established',
            'test_id' => $conn->testId,
            'message' => '歡迎連接到WebSocket測試服務器',
            'timestamp' => date('c')
        ]);
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $this->log("收到消息 from {$from->testId}: $msg");
        
        try {
            $data = json_decode($msg, true);
            if (!$data) {
                throw new Exception('無效的JSON格式');
            }
            
            $this->handleMessage($from, $data);
            
        } catch (Exception $e) {
            $this->log("消息處理錯誤: " . $e->getMessage());
            $this->sendError($from, '消息處理失敗: ' . $e->getMessage());
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // 從房間中移除用戶
        foreach ($this->rooms as $roomId => &$room) {
            if (isset($room['connections'][$conn->testId])) {
                unset($room['connections'][$conn->testId]);
                
                // 通知房間其他用戶
                $this->broadcastToRoom($roomId, [
                    'type' => 'user_left',
                    'user_id' => $conn->testId,
                    'message' => '用戶離開了房間'
                ], $conn);
            }
        }
        
        $this->log("連接關閉: {$conn->testId}");
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->log("連接錯誤 {$conn->testId}: " . $e->getMessage());
        $conn->close();
    }
    
    private function handleMessage(ConnectionInterface $conn, $data) {
        $type = $data['type'] ?? '';
        
        switch ($type) {
            case 'join_room':
                $this->handleJoinRoom($conn, $data);
                break;
                
            case 'leave_room':
                $this->handleLeaveRoom($conn, $data);
                break;
                
            case 'code_change':
                $this->handleCodeChange($conn, $data);
                break;
                
            case 'chat_message':
                $this->handleChatMessage($conn, $data);
                break;
                
            case 'ping':
                $this->handlePing($conn, $data);
                break;
                
            default:
                $this->sendError($conn, "未知的消息類型: $type");
        }
    }
    
    private function handleJoinRoom(ConnectionInterface $conn, $data) {
        $roomId = $data['room_id'] ?? 'test_room_' . uniqid();
        $userId = $data['user_id'] ?? $conn->testId;
        $username = $data['username'] ?? "測試用戶_{$conn->testId}";
        
        // 創建房間如果不存在
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [
                'id' => $roomId,
                'name' => "測試房間 $roomId",
                'current_code' => "# 測試房間代碼\nprint('Hello from test room!')\n\n# 開始你的測試...",
                'connections' => [],
                'users' => [],
                'created_at' => date('c')
            ];
        }
        
        // 添加用戶到房間
        $this->rooms[$roomId]['connections'][$conn->testId] = $conn;
        $this->rooms[$roomId]['users'][$userId] = [
            'user_id' => $userId,
            'username' => $username,
            'connection_id' => $conn->testId,
            'joined_at' => date('c')
        ];
        
        $conn->roomId = $roomId;
        $conn->userId = $userId;
        $conn->username = $username;
        
        // 發送房間信息給新用戶
        $this->sendToConnection($conn, [
            'type' => 'room_joined',
            'room_id' => $roomId,
            'current_code' => $this->rooms[$roomId]['current_code'],
            'users' => array_values($this->rooms[$roomId]['users']),
            'message' => "成功加入測試房間: $roomId"
        ]);
        
        // 通知房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'message' => "$username 加入了房間"
        ], $conn);
        
        $this->log("用戶 $username 加入房間 $roomId");
    }
    
    private function handleLeaveRoom(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId ?? null;
        
        if ($roomId && isset($this->rooms[$roomId])) {
            unset($this->rooms[$roomId]['connections'][$conn->testId]);
            unset($this->rooms[$roomId]['users'][$conn->userId]);
            
            // 通知房間其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'user_left',
                'user_id' => $conn->userId,
                'username' => $conn->username,
                'message' => "{$conn->username} 離開了房間"
            ], $conn);
            
            $this->sendToConnection($conn, [
                'type' => 'room_left',
                'room_id' => $roomId,
                'message' => '已離開房間'
            ]);
            
            $this->log("用戶 {$conn->username} 離開房間 $roomId");
        }
    }
    
    private function handleCodeChange(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId ?? null;
        $newCode = $data['code'] ?? '';
        
        if (!$roomId || !isset($this->rooms[$roomId])) {
            return $this->sendError($conn, '未加入有效房間');
        }
        
        // 更新房間代碼
        $this->rooms[$roomId]['current_code'] = $newCode;
        
        // 廣播代碼變更給房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'code_sync',
            'code' => $newCode,
            'user_id' => $conn->userId,
            'username' => $conn->username,
            'timestamp' => date('c')
        ], $conn);
        
        $this->log("房間 $roomId 代碼更新 by {$conn->username}");
    }
    
    private function handleChatMessage(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId ?? null;
        $message = $data['message'] ?? '';
        
        if (!$roomId || !isset($this->rooms[$roomId])) {
            return $this->sendError($conn, '未加入有效房間');
        }
        
        // 廣播聊天消息
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'user_id' => $conn->userId,
            'username' => $conn->username,
            'message' => $message,
            'timestamp' => date('c')
        ]);
        
        $this->log("聊天消息 in $roomId from {$conn->username}: $message");
    }
    
    private function handlePing(ConnectionInterface $conn, $data) {
        $this->sendToConnection($conn, [
            'type' => 'pong',
            'timestamp' => date('c'),
            'server_time' => microtime(true)
        ]);
    }
    
    private function broadcastToRoom($roomId, $message, $excludeConn = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        foreach ($this->rooms[$roomId]['connections'] as $conn) {
            if ($conn !== $excludeConn) {
                $this->sendToConnection($conn, $message);
            }
        }
    }
    
    private function sendToConnection(ConnectionInterface $conn, $data) {
        try {
            $conn->send(json_encode($data, JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            $this->log("發送消息失敗: " . $e->getMessage());
        }
    }
    
    private function sendError(ConnectionInterface $conn, $message) {
        $this->sendToConnection($conn, [
            'type' => 'error',
            'error' => $message,
            'timestamp' => date('c')
        ]);
    }
    
    private function log($message) {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] $message\n";
        echo $logMessage;
        
        $logFile = __DIR__ . '/../../test-logs/websocket_test.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// 啟動WebSocket測試服務器
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new TestWebSocketServer()
        )
    ),
    9082
);

echo "🚀 WebSocket測試服務器運行在 ws://localhost:9082\n";
echo "📊 測試狀態: http://localhost:9082/status\n";
echo "⏹️ 按 Ctrl+C 停止服務器\n\n";

$server->run();
?> 