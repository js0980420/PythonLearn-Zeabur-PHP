<?php
/**
 * 簡化的 WebSocket 測試服務器
 * 用於快速測試和開發，不依賴複雜的依賴
 */

// 簡單的 WebSocket 服務器實現
class SimpleWebSocketServer {
    private $host;
    private $port;
    private $socket;
    private $clients = [];
    private $rooms = [];
    
    public function __construct($host = '127.0.0.1', $port = 8081) {
        $this->host = $host;
        $this->port = $port;
    }
    
    public function start() {
        echo "🚀 啟動簡化 WebSocket 服務器...\n";
        echo "📍 地址: {$this->host}:{$this->port}\n";
        
        // 創建 socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            die("❌ 無法創建 socket: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        // 設置 socket 選項
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        
        // 綁定地址和端口
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            die("❌ 無法綁定地址: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        // 開始監聽
        if (!socket_listen($this->socket, 5)) {
            die("❌ 無法監聽: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        echo "✅ WebSocket 服務器已啟動，等待連接...\n";
        
        // 主循環
        while (true) {
            $read = array_merge([$this->socket], $this->clients);
            $write = null;
            $except = null;
            
            if (socket_select($read, $write, $except, 0, 10000) < 1) {
                continue;
            }
            
            // 處理新連接
            if (in_array($this->socket, $read)) {
                $newClient = socket_accept($this->socket);
                if ($newClient) {
                    $this->handleNewConnection($newClient);
                }
                $key = array_search($this->socket, $read);
                unset($read[$key]);
            }
            
            // 處理客戶端消息
            foreach ($read as $client) {
                $this->handleClientMessage($client);
            }
        }
    }
    
    private function handleNewConnection($client) {
        echo "🔌 新連接建立\n";
        
        // 讀取 HTTP 握手請求
        $request = socket_read($client, 1024);
        if (!$request) {
            socket_close($client);
            return;
        }
        
        // 執行 WebSocket 握手
        if ($this->performHandshake($client, $request)) {
            $this->clients[] = $client;
            $clientId = array_search($client, $this->clients);
            echo "✅ 客戶端 {$clientId} 連接成功\n";
        } else {
            socket_close($client);
        }
    }
    
    private function performHandshake($client, $request) {
        $lines = explode("\n", $request);
        $headers = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        if (!isset($headers['Sec-WebSocket-Key'])) {
            return false;
        }
        
        $key = $headers['Sec-WebSocket-Key'];
        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        
        $response = "HTTP/1.1 101 Switching Protocols\r\n";
        $response .= "Upgrade: websocket\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Sec-WebSocket-Accept: {$acceptKey}\r\n";
        $response .= "\r\n";
        
        socket_write($client, $response);
        return true;
    }
    
    private function handleClientMessage($client) {
        $data = socket_read($client, 1024);
        if (!$data) {
            $this->removeClient($client);
            return;
        }
        
        $message = $this->decodeFrame($data);
        if ($message === false) {
            return;
        }
        
        echo "📨 收到消息: {$message}\n";
        
        try {
            $messageData = json_decode($message, true);
            if ($messageData) {
                $this->processMessage($client, $messageData);
            }
        } catch (Exception $e) {
            echo "❌ 處理消息錯誤: {$e->getMessage()}\n";
        }
    }
    
    private function processMessage($client, $data) {
        $type = $data['type'] ?? 'unknown';
        
        switch ($type) {
            case 'join_room':
                $this->handleJoinRoom($client, $data);
                break;
                
            case 'ping':
                $this->sendToClient($client, ['type' => 'pong', 'timestamp' => time()]);
                break;
                
            case 'chat_message':
                $this->handleChatMessage($client, $data);
                break;
                
            default:
                echo "⚠️ 未知消息類型: {$type}\n";
        }
    }
    
    private function handleJoinRoom($client, $data) {
        $roomId = $data['room_id'] ?? '';
        $userId = $data['user_id'] ?? '';
        $username = $data['username'] ?? '';
        
        if (empty($roomId) || empty($userId) || empty($username)) {
            $this->sendToClient($client, [
                'type' => 'error',
                'message' => '缺少必要參數'
            ]);
            return;
        }
        
        // 設置客戶端信息
        $clientId = array_search($client, $this->clients);
        $this->clients[$clientId] = [
            'socket' => $client,
            'room_id' => $roomId,
            'user_id' => $userId,
            'username' => $username
        ];
        
        // 添加到房間
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [];
        }
        
        // 檢查並移除重複用戶
        foreach ($this->rooms[$roomId] as $key => $existingClient) {
            if ($existingClient['user_id'] === $userId) {
                echo "🔄 移除重複用戶: {$username}\n";
                unset($this->rooms[$roomId][$key]);
            }
        }
        
        $this->rooms[$roomId][$clientId] = $this->clients[$clientId];
        
        echo "👤 用戶 {$username} 加入房間 {$roomId}\n";
        
        // 發送成功響應
        $this->sendToClient($client, [
            'type' => 'room_joined',
            'room_id' => $roomId,
            'user_id' => $userId,
            'username' => $username,
            'message' => "成功加入房間 {$roomId}",
            'timestamp' => date('c')
        ]);
        
        // 廣播用戶列表
        $this->broadcastUserList($roomId);
        
        // 通知其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'message' => "{$username} 加入了房間"
        ], $clientId);
    }
    
    private function handleChatMessage($client, $data) {
        $clientId = array_search($client, $this->clients);
        $clientInfo = $this->clients[$clientId];
        
        if (!is_array($clientInfo) || !isset($clientInfo['room_id'])) {
            return;
        }
        
        $roomId = $clientInfo['room_id'];
        $message = $data['message'] ?? '';
        
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'user_id' => $clientInfo['user_id'],
            'username' => $clientInfo['username'],
            'message' => $message,
            'timestamp' => date('c')
        ]);
    }
    
    private function broadcastUserList($roomId) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        $users = [];
        foreach ($this->rooms[$roomId] as $client) {
            $users[] = [
                'user_id' => $client['user_id'],
                'username' => $client['username'],
                'status' => 'active'
            ];
        }
        
        echo "📋 房間 {$roomId} 用戶列表: " . count($users) . " 個用戶\n";
        
        $this->broadcastToRoom($roomId, [
            'type' => 'user_list_update',
            'users' => $users,
            'total_users' => count($users),
            'timestamp' => date('c')
        ]);
    }
    
    private function broadcastToRoom($roomId, $message, $excludeClientId = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        foreach ($this->rooms[$roomId] as $clientId => $client) {
            if ($excludeClientId !== null && $clientId === $excludeClientId) {
                continue;
            }
            $this->sendToClient($client['socket'], $message);
        }
    }
    
    private function sendToClient($client, $message) {
        $frame = $this->encodeFrame(json_encode($message));
        socket_write($client, $frame);
    }
    
    private function removeClient($client) {
        $clientId = array_search($client, $this->clients);
        if ($clientId !== false) {
            echo "🔌 客戶端 {$clientId} 斷開連接\n";
            
            // 從房間中移除
            foreach ($this->rooms as $roomId => &$room) {
                if (isset($room[$clientId])) {
                    $clientInfo = $room[$clientId];
                    unset($room[$clientId]);
                    
                    // 通知其他用戶
                    $this->broadcastToRoom($roomId, [
                        'type' => 'user_left',
                        'user_id' => $clientInfo['user_id'],
                        'username' => $clientInfo['username']
                    ]);
                    
                    // 更新用戶列表
                    $this->broadcastUserList($roomId);
                    break;
                }
            }
            
            unset($this->clients[$clientId]);
        }
        socket_close($client);
    }
    
    private function decodeFrame($data) {
        $length = ord($data[1]) & 127;
        
        if ($length == 126) {
            $masks = substr($data, 4, 4);
            $payload = substr($data, 8);
        } elseif ($length == 127) {
            $masks = substr($data, 10, 4);
            $payload = substr($data, 14);
        } else {
            $masks = substr($data, 2, 4);
            $payload = substr($data, 6);
        }
        
        $text = '';
        for ($i = 0; $i < strlen($payload); ++$i) {
            $text .= $payload[$i] ^ $masks[$i % 4];
        }
        
        return $text;
    }
    
    private function encodeFrame($message) {
        $length = strlen($message);
        
        if ($length <= 125) {
            return chr(129) . chr($length) . $message;
        } elseif ($length <= 65535) {
            return chr(129) . chr(126) . pack('n', $length) . $message;
        } else {
            return chr(129) . chr(127) . pack('J', $length) . $message;
        }
    }
}

// 啟動服務器
try {
    $server = new SimpleWebSocketServer('127.0.0.1', 8081);
    $server->start();
} catch (Exception $e) {
    echo "❌ 服務器啟動失敗: {$e->getMessage()}\n";
}
?> 