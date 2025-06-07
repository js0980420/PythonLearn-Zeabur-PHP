<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 簡單的WebSocket服務器實現
class SimpleWebSocketServer {
    private $socket;
    private $clients = [];
    private $rooms = [];
    
    public function __construct($host = 'localhost', $port = 8080) {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->socket, $host, $port);
        socket_listen($this->socket);
        
        echo "WebSocket服務器啟動在 ws://{$host}:{$port}\n";
    }
    
    public function run() {
        while (true) {
            $read = [$this->socket];
            foreach ($this->clients as $client) {
                $read[] = $client['socket'];
            }
            
            $write = null;
            $except = null;
            
            if (socket_select($read, $write, $except, 0, 10000) < 1) {
                continue;
            }
            
            if (in_array($this->socket, $read)) {
                $newSocket = socket_accept($this->socket);
                $this->handleNewConnection($newSocket);
                $key = array_search($this->socket, $read);
                unset($read[$key]);
            }
            
            foreach ($read as $socket) {
                $this->handleMessage($socket);
            }
        }
    }
    
    private function handleNewConnection($socket) {
        $header = socket_read($socket, 1024);
        $this->performHandshake($header, $socket);
        
        $clientId = uniqid();
        $this->clients[$clientId] = [
            'socket' => $socket,
            'id' => $clientId,
            'room_id' => null,
            'user_id' => null,
            'username' => null
        ];
        
        echo "新客戶端連接: {$clientId}\n";
    }
    
    private function performHandshake($header, $socket) {
        $headers = [];
        $lines = preg_split("/\r\n/", $header);
        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }
        
        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: {$secAccept}\r\n\r\n";
        
        socket_write($socket, $response, strlen($response));
    }
    
    private function handleMessage($socket) {
        $data = socket_read($socket, 1024);
        
        if ($data === false) {
            $this->removeClient($socket);
            return;
        }
        
        $message = $this->unmask($data);
        if (empty($message)) {
            return;
        }
        
        $data = json_decode($message, true);
        if (!$data) {
            return;
        }
        
        $clientId = $this->getClientIdBySocket($socket);
        if (!$clientId) {
            return;
        }
        
        echo "收到消息: " . $message . "\n";
        
        switch ($data['type']) {
            case 'join_room':
                $this->handleJoinRoom($clientId, $data);
                break;
            case 'code_change':
                $this->handleCodeChange($clientId, $data);
                break;
            case 'cursor_position':
                $this->handleCursorPosition($clientId, $data);
                break;
            case 'chat_message':
                $this->handleChatMessage($clientId, $data);
                break;
        }
    }
    
    private function handleJoinRoom($clientId, $data) {
        $roomId = $data['room_id'] ?? '';
        $userId = $data['user_id'] ?? '';
        $username = $data['username'] ?? '';
        
        if (empty($roomId) || empty($userId)) {
            return;
        }
        
        // 更新客戶端信息
        $this->clients[$clientId]['room_id'] = $roomId;
        $this->clients[$clientId]['user_id'] = $userId;
        $this->clients[$clientId]['username'] = $username;
        
        // 添加到房間
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [];
        }
        $this->rooms[$roomId][$clientId] = $this->clients[$clientId];
        
        echo "用戶 {$username} 加入房間 {$roomId}\n";
        
        // 通知房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'room_id' => $roomId
        ], $clientId);
        
        // 發送房間用戶列表給新用戶
        $users = [];
        foreach ($this->rooms[$roomId] as $client) {
            if ($client['user_id']) {
                $users[] = [
                    'user_id' => $client['user_id'],
                    'username' => $client['username']
                ];
            }
        }
        
        $this->sendToClient($clientId, [
            'type' => 'room_users',
            'users' => $users,
            'room_id' => $roomId
        ]);
    }
    
    private function handleCodeChange($clientId, $data) {
        $roomId = $this->clients[$clientId]['room_id'] ?? '';
        if (empty($roomId)) {
            return;
        }
        
        echo "代碼變更在房間 {$roomId}\n";
        
        // 廣播代碼變更給房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'code_change',
            'user_id' => $this->clients[$clientId]['user_id'],
            'username' => $this->clients[$clientId]['username'],
            'change' => $data['change'] ?? '',
            'code' => $data['code'] ?? '',
            'room_id' => $roomId,
            'timestamp' => time()
        ], $clientId);
    }
    
    private function handleCursorPosition($clientId, $data) {
        $roomId = $this->clients[$clientId]['room_id'] ?? '';
        if (empty($roomId)) {
            return;
        }
        
        // 廣播游標位置給房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'cursor_position',
            'user_id' => $this->clients[$clientId]['user_id'],
            'username' => $this->clients[$clientId]['username'],
            'position' => $data['position'] ?? '',
            'room_id' => $roomId
        ], $clientId);
    }
    
    private function handleChatMessage($clientId, $data) {
        $roomId = $this->clients[$clientId]['room_id'] ?? '';
        if (empty($roomId)) {
            return;
        }
        
        echo "聊天消息在房間 {$roomId}: " . ($data['message'] ?? '') . "\n";
        
        // 廣播聊天消息給房間內所有用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'user_id' => $this->clients[$clientId]['user_id'],
            'username' => $this->clients[$clientId]['username'],
            'message' => $data['message'] ?? '',
            'room_id' => $roomId,
            'timestamp' => time()
        ]);
    }
    
    private function broadcastToRoom($roomId, $message, $excludeClientId = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        foreach ($this->rooms[$roomId] as $clientId => $client) {
            if ($excludeClientId && $clientId === $excludeClientId) {
                continue;
            }
            $this->sendToClient($clientId, $message);
        }
    }
    
    private function sendToClient($clientId, $message) {
        if (!isset($this->clients[$clientId])) {
            return;
        }
        
        $socket = $this->clients[$clientId]['socket'];
        $data = json_encode($message);
        $frame = $this->mask($data);
        
        @socket_write($socket, $frame, strlen($frame));
    }
    
    private function removeClient($socket) {
        $clientId = $this->getClientIdBySocket($socket);
        if (!$clientId) {
            return;
        }
        
        $client = $this->clients[$clientId];
        $roomId = $client['room_id'] ?? '';
        
        // 從房間中移除
        if ($roomId && isset($this->rooms[$roomId][$clientId])) {
            unset($this->rooms[$roomId][$clientId]);
            
            // 通知房間內其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'user_left',
                'user_id' => $client['user_id'],
                'username' => $client['username'],
                'room_id' => $roomId
            ]);
            
            // 如果房間為空，清理房間
            if (empty($this->rooms[$roomId])) {
                unset($this->rooms[$roomId]);
            }
        }
        
        // 移除客戶端
        unset($this->clients[$clientId]);
        socket_close($socket);
        
        echo "客戶端斷開連接: {$clientId}\n";
    }
    
    private function getClientIdBySocket($socket) {
        foreach ($this->clients as $clientId => $client) {
            if ($client['socket'] === $socket) {
                return $clientId;
            }
        }
        return null;
    }
    
    private function unmask($text) {
        $length = ord($text[1]) & 127;
        
        if ($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        } elseif ($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        } else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        
        return $text;
    }
    
    private function mask($text) {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);
        
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length > 125 && $length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } elseif ($length >= 65536) {
            $header = pack('CCNN', $b1, 127, $length);
        }
        
        return $header . $text;
    }
}

// 啟動WebSocket服務器
$server = new SimpleWebSocketServer('localhost', 8080);
$server->run();
?> 