<?php

// 基本WebSocket服務器實現
class BasicWebSocketServer {
    private $socket;
    private $clients = [];
    private $rooms = [];
    
    public function __construct($host = 'localhost', $port = 8080) {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->socket, $host, $port);
        socket_listen($this->socket);
        
        echo "WebSocket服務器啟動在 ws://$host:$port\n";
    }
    
    public function run() {
        while (true) {
            $read = array_merge([$this->socket], $this->clients);
            $write = null;
            $except = null;
            
            if (socket_select($read, $write, $except, 0, 10000) < 1) {
                continue;
            }
            
            // 處理新連接
            if (in_array($this->socket, $read)) {
                $newSocket = socket_accept($this->socket);
                $this->clients[] = $newSocket;
                $this->performHandshake($newSocket);
                echo "新連接: " . count($this->clients) . " 個客戶端\n";
                
                $key = array_search($this->socket, $read);
                unset($read[$key]);
            }
            
            // 處理客戶端消息
            foreach ($read as $client) {
                $data = socket_read($client, 1024);
                
                if ($data === false) {
                    $this->disconnect($client);
                    continue;
                }
                
                if (empty($data)) {
                    continue;
                }
                
                $message = $this->decode($data);
                if ($message) {
                    $this->handleMessage($client, $message);
                }
            }
        }
    }
    
    private function performHandshake($socket) {
        $request = socket_read($socket, 5000);
        
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
        if (empty($matches[1])) {
            return false;
        }
        
        $key = trim($matches[1]);
        $responseKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: $responseKey\r\n\r\n";
        
        socket_write($socket, $response, strlen($response));
        
        // 發送歡迎消息
        $welcome = json_encode([
            'type' => 'welcome',
            'message' => '歡迎連接到WebSocket服務器！',
            'timestamp' => time()
        ]);
        $this->send($socket, $welcome);
        
        return true;
    }
    
    private function decode($data) {
        $length = ord($data[1]) & 127;
        
        if ($length == 126) {
            $masks = substr($data, 4, 4);
            $data = substr($data, 8);
        } elseif ($length == 127) {
            $masks = substr($data, 10, 4);
            $data = substr($data, 14);
        } else {
            $masks = substr($data, 2, 4);
            $data = substr($data, 6);
        }
        
        $text = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        
        return $text;
    }
    
    private function encode($message) {
        $length = strlen($message);
        
        if ($length <= 125) {
            return pack('CC', 129, $length) . $message;
        } elseif ($length <= 65535) {
            return pack('CCn', 129, 126, $length) . $message;
        } else {
            return pack('CCNN', 129, 127, 0, $length) . $message;
        }
    }
    
    private function send($client, $message) {
        $encoded = $this->encode($message);
        socket_write($client, $encoded, strlen($encoded));
    }
    
    private function broadcast($message, $exclude = null) {
        foreach ($this->clients as $client) {
            if ($client !== $exclude) {
                $this->send($client, $message);
            }
        }
    }
    
    private function handleMessage($client, $message) {
        echo "收到消息: $message\n";
        
        $data = json_decode($message, true);
        if (!$data) {
            return;
        }
        
        switch ($data['type']) {
            case 'join_room':
                $this->handleJoinRoom($client, $data);
                break;
                
            case 'leave_room':
                $this->handleLeaveRoom($client, $data);
                break;
                
            case 'chat_message':
                $this->handleChatMessage($client, $data);
                break;
                
            case 'code_change':
                $this->handleCodeChange($client, $data);
                break;
                
            default:
                echo "未知消息類型: {$data['type']}\n";
        }
    }
    
    private function handleJoinRoom($client, $data) {
        $roomId = $data['room_id'] ?? '';
        $userId = $data['user_id'] ?? 'user_' . uniqid();
        $username = $data['username'] ?? $userId;
        
        if (empty($roomId)) {
            return;
        }
        
        // 設置客戶端信息
        $clientId = array_search($client, $this->clients);
        $this->clients[$clientId] = $client;
        
        // 添加到房間
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [];
        }
        
        $this->rooms[$roomId][$clientId] = [
            'socket' => $client,
            'user_id' => $userId,
            'username' => $username
        ];
        
        echo "用戶 $username 加入房間 $roomId\n";
        
        // 通知房間內所有用戶
        $userList = [];
        foreach ($this->rooms[$roomId] as $user) {
            $userList[] = [
                'user_id' => $user['user_id'],
                'username' => $user['username']
            ];
        }
        
        $joinMessage = json_encode([
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'room_id' => $roomId,
            'users_count' => count($this->rooms[$roomId]),
            'users' => $userList
        ]);
        
        // 廣播給房間內所有用戶
        foreach ($this->rooms[$roomId] as $user) {
            $this->send($user['socket'], $joinMessage);
        }
        
        // 發送成功消息給當前用戶
        $successMessage = json_encode([
            'type' => 'room_joined',
            'room_id' => $roomId,
            'users' => $userList,
            'message' => "成功加入房間 $roomId"
        ]);
        $this->send($client, $successMessage);
    }
    
    private function handleLeaveRoom($client, $data) {
        $clientId = array_search($client, $this->clients);
        
        foreach ($this->rooms as $roomId => &$room) {
            if (isset($room[$clientId])) {
                $user = $room[$clientId];
                unset($room[$clientId]);
                
                echo "用戶 {$user['username']} 離開房間 $roomId\n";
                
                // 通知房間內其他用戶
                $leaveMessage = json_encode([
                    'type' => 'user_left',
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'room_id' => $roomId,
                    'users_count' => count($room)
                ]);
                
                foreach ($room as $otherUser) {
                    $this->send($otherUser['socket'], $leaveMessage);
                }
                
                // 如果房間空了，刪除房間
                if (empty($room)) {
                    unset($this->rooms[$roomId]);
                    echo "房間 $roomId 已清空\n";
                }
                break;
            }
        }
    }
    
    private function handleChatMessage($client, $data) {
        $clientId = array_search($client, $this->clients);
        $message = $data['message'] ?? '';
        
        if (empty($message)) {
            return;
        }
        
        // 找到用戶所在的房間
        foreach ($this->rooms as $roomId => $room) {
            if (isset($room[$clientId])) {
                $user = $room[$clientId];
                
                $chatMessage = json_encode([
                    'type' => 'chat_message',
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'message' => $message,
                    'timestamp' => time()
                ]);
                
                // 廣播給房間內所有用戶
                foreach ($room as $roomUser) {
                    $this->send($roomUser['socket'], $chatMessage);
                }
                break;
            }
        }
    }
    
    private function handleCodeChange($client, $data) {
        $clientId = array_search($client, $this->clients);
        $change = $data['change'] ?? '';
        
        // 找到用戶所在的房間
        foreach ($this->rooms as $roomId => $room) {
            if (isset($room[$clientId])) {
                $user = $room[$clientId];
                
                $codeMessage = json_encode([
                    'type' => 'code_change',
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'change' => $change,
                    'timestamp' => time()
                ]);
                
                // 廣播給房間內其他用戶（不包括發送者）
                foreach ($room as $otherClientId => $roomUser) {
                    if ($otherClientId !== $clientId) {
                        $this->send($roomUser['socket'], $codeMessage);
                    }
                }
                break;
            }
        }
    }
    
    private function disconnect($client) {
        $clientId = array_search($client, $this->clients);
        
        // 從所有房間中移除用戶
        $this->handleLeaveRoom($client, []);
        
        // 移除客戶端
        unset($this->clients[$clientId]);
        socket_close($client);
        
        echo "客戶端斷開連接，剩餘 " . count($this->clients) . " 個客戶端\n";
    }
    
    public function getRoomStats() {
        $stats = [];
        foreach ($this->rooms as $roomId => $room) {
            $stats[$roomId] = [
                'users_count' => count($room),
                'users' => array_map(function($user) {
                    return [
                        'user_id' => $user['user_id'],
                        'username' => $user['username']
                    ];
                }, array_values($room))
            ];
        }
        return $stats;
    }
}

// 啟動服務器
try {
    $server = new BasicWebSocketServer('localhost', 8080);
    echo "按 Ctrl+C 停止服務器\n";
    $server->run();
} catch (Exception $e) {
    echo "服務器錯誤: " . $e->getMessage() . "\n";
}
?> 