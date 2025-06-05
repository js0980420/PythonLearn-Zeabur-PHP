<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;
use Ratchet\RFC6455\Messaging\MessageInterface;

class SimpleWebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $rooms;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        echo "WebSocket服務器啟動成功！\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->roomId = null;
        $conn->userId = null;
        
        echo "新連接: {$conn->resourceId}\n";
        
        // 發送歡迎消息
        $conn->send(json_encode([
            'type' => 'welcome',
            'message' => '歡迎連接到WebSocket服務器！',
            'connection_id' => $conn->resourceId
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "收到消息: $msg\n";
        
        $data = json_decode($msg, true);
        if (!$data) {
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
                
            case 'cursor_position':
                $this->handleCursorPosition($from, $data);
                break;
                
            case 'chat_message':
                $this->handleChatMessage($from, $data);
                break;
                
            default:
                echo "未知消息類型: {$data['type']}\n";
        }
    }

    private function handleJoinRoom(ConnectionInterface $conn, $data) {
        $roomId = $data['room_id'] ?? '';
        $userId = $data['user_id'] ?? 'user_' . $conn->resourceId;
        $username = $data['username'] ?? $userId;
        
        if (empty($roomId)) {
            return;
        }
        
        // 離開之前的房間
        if ($conn->roomId) {
            $this->handleLeaveRoom($conn, ['room_id' => $conn->roomId]);
        }
        
        // 加入新房間
        $conn->roomId = $roomId;
        $conn->userId = $userId;
        $conn->username = $username;
        
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [];
        }
        
        $this->rooms[$roomId][$conn->resourceId] = $conn;
        
        echo "用戶 $username 加入房間 $roomId\n";
        
        // 通知房間內所有用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'room_id' => $roomId,
            'users_count' => count($this->rooms[$roomId])
        ]);
        
        // 發送房間用戶列表給新用戶
        $userList = [];
        foreach ($this->rooms[$roomId] as $client) {
            $userList[] = [
                'user_id' => $client->userId,
                'username' => $client->username,
                'connection_id' => $client->resourceId
            ];
        }
        
        $conn->send(json_encode([
            'type' => 'room_joined',
            'room_id' => $roomId,
            'users' => $userList,
            'message' => "成功加入房間 $roomId"
        ]));
    }

    private function handleLeaveRoom(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        
        if (!$roomId || !isset($this->rooms[$roomId])) {
            return;
        }
        
        unset($this->rooms[$roomId][$conn->resourceId]);
        
        echo "用戶 {$conn->username} 離開房間 $roomId\n";
        
        // 通知房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_left',
            'user_id' => $conn->userId,
            'username' => $conn->username,
            'room_id' => $roomId,
            'users_count' => count($this->rooms[$roomId])
        ], $conn);
        
        // 如果房間空了，刪除房間
        if (empty($this->rooms[$roomId])) {
            unset($this->rooms[$roomId]);
            echo "房間 $roomId 已清空\n";
        }
        
        $conn->roomId = null;
        $conn->userId = null;
        $conn->username = null;
    }

    private function handleCodeChange(ConnectionInterface $from, $data) {
        $roomId = $from->roomId;
        
        if (!$roomId) {
            return;
        }
        
        // 廣播代碼變更給房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'code_change',
            'user_id' => $from->userId,
            'username' => $from->username,
            'change' => $data['change'] ?? '',
            'timestamp' => time()
        ], $from);
    }

    private function handleCursorPosition(ConnectionInterface $from, $data) {
        $roomId = $from->roomId;
        
        if (!$roomId) {
            return;
        }
        
        // 廣播游標位置給房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'cursor_position',
            'user_id' => $from->userId,
            'username' => $from->username,
            'position' => $data['position'] ?? null
        ], $from);
    }

    private function handleChatMessage(ConnectionInterface $from, $data) {
        $roomId = $from->roomId;
        
        if (!$roomId) {
            return;
        }
        
        $message = $data['message'] ?? '';
        if (empty($message)) {
            return;
        }
        
        // 廣播聊天消息給房間內所有用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'user_id' => $from->userId,
            'username' => $from->username,
            'message' => $message,
            'timestamp' => time()
        ]);
    }

    private function broadcastToRoom($roomId, $data, $exclude = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        $message = json_encode($data);
        
        foreach ($this->rooms[$roomId] as $client) {
            if ($exclude && $client === $exclude) {
                continue;
            }
            
            try {
                $client->send($message);
            } catch (Exception $e) {
                echo "發送消息失敗: " . $e->getMessage() . "\n";
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // 用戶離開房間
        if ($conn->roomId) {
            $this->handleLeaveRoom($conn, []);
        }
        
        $this->clients->detach($conn);
        echo "連接關閉: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "WebSocket錯誤: " . $e->getMessage() . "\n";
        $conn->close();
    }
    
    public function getRoomStats() {
        $stats = [];
        foreach ($this->rooms as $roomId => $clients) {
            $stats[$roomId] = [
                'users_count' => count($clients),
                'users' => array_map(function($client) {
                    return [
                        'user_id' => $client->userId,
                        'username' => $client->username
                    ];
                }, array_values($clients))
            ];
        }
        return $stats;
    }
}

// 啟動WebSocket服務器
use React\EventLoop\Loop;
use Ratchet\RFC6455\Handshake\ServerNegotiator;
use Ratchet\RFC6455\Messaging\CloseFrameChecker;
use Ratchet\RFC6455\Messaging\MessageBuffer;

$loop = Loop::get();
$socket = new SocketServer('0.0.0.0:8080', $loop);

$app = new SimpleWebSocketServer();

$socket->on('connection', function ($conn) use ($app) {
    $app->onOpen($conn);
    
    $conn->on('data', function ($data) use ($app, $conn) {
        $app->onMessage($conn, $data);
    });
    
    $conn->on('close', function () use ($app, $conn) {
        $app->onClose($conn);
    });
    
    $conn->on('error', function ($error) use ($app, $conn) {
        $app->onError($conn, $error);
    });
});

echo "WebSocket服務器運行在 ws://localhost:8080\n";
echo "按 Ctrl+C 停止服務器\n";

$loop->run();
?> 