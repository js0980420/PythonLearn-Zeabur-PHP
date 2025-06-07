<?php
/**
 * 原生WebSocket測試服務器
 * 端口：8081
 * 用途：純原生PHP實現的WebSocket服務器，無外部依賴
 */

class NativeTestWebSocketServer {
    private $host;
    private $port;
    private $socket;
    private $clients = [];
    private $rooms = [];
    private $nextClientId = 1;
    
    public function __construct($host = '0.0.0.0', $port = 8081) {
        $this->host = $host;
        $this->port = $port;
    }
    
    public function start() {
        echo "🚀 啟動原生WebSocket測試服務器...\n";
        echo "📡 監聽地址: {$this->host}:{$this->port}\n";
        echo "🌐 連接地址: ws://localhost:{$this->port}\n";
        echo "💾 存儲: 純內存模式 (無數據庫依賴)\n";
        echo "📊 功能: 實時協作、聊天、歷史記錄\n";
        
        // 創建socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            die("❌ 無法創建socket: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        // 設置socket選項
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        
        // 綁定和監聽
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            die("❌ 無法綁定端口 {$this->port}: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        if (!socket_listen($this->socket, 5)) {
            die("❌ 無法監聽: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        echo "✅ 服務器啟動成功，等待連接...\n\n";
        
        // 主循環
        while (true) {
            $read = [$this->socket];
            foreach ($this->clients as $client) {
                $read[] = $client['socket'];
            }
            
            $write = null;
            $except = null;
            
            if (socket_select($read, $write, $except, 0, 100000) > 0) {
                // 處理新連接
                if (in_array($this->socket, $read)) {
                    $this->handleNewConnection();
                    $key = array_search($this->socket, $read);
                    unset($read[$key]);
                }
                
                // 處理客戶端消息
                foreach ($read as $clientSocket) {
                    $this->handleClientMessage($clientSocket);
                }
            }
        }
    }
    
    private function handleNewConnection() {
        $clientSocket = socket_accept($this->socket);
        if (!$clientSocket) {
            return;
        }
        
        $clientId = $this->nextClientId++;
        $clientInfo = [
            'id' => $clientId,
            'socket' => $clientSocket,
            'handshake_done' => false,
            'test_id' => 'test_' . uniqid(),
            'room_id' => null,
            'user_id' => null,
            'username' => null,
            'connected_at' => date('c')
        ];
        
        $this->clients[$clientId] = $clientInfo;
        
        $address = '';
        socket_getpeername($clientSocket, $address);
        echo "🔗 新連接建立: ID={$clientId}, 地址={$address}\n";
    }
    
    private function handleClientMessage($clientSocket) {
        $clientId = $this->getClientIdBySocket($clientSocket);
        if (!$clientId) {
            return;
        }
        
        $client = &$this->clients[$clientId];
        
        $data = socket_read($clientSocket, 2048);
        if ($data === false || $data === '') {
            $this->disconnectClient($clientId);
            return;
        }
        
        if (!$client['handshake_done']) {
            $this->performHandshake($clientId, $data);
        } else {
            $this->processWebSocketFrame($clientId, $data);
        }
    }
    
    private function performHandshake($clientId, $data) {
        $client = &$this->clients[$clientId];
        
        // 解析HTTP請求頭
        $lines = explode("\r\n", $data);
        $headers = [];
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        if (!isset($headers['Sec-WebSocket-Key'])) {
            $this->disconnectClient($clientId);
            return;
        }
        
        // 生成WebSocket接受密鑰
        $key = $headers['Sec-WebSocket-Key'];
        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        
        // 發送握手響應
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
        
        socket_write($client['socket'], $response);
        $client['handshake_done'] = true;
        
        echo "✅ WebSocket握手成功: {$client['test_id']}\n";
        
        // 發送歡迎消息
        $this->sendToClient($clientId, [
            'type' => 'connection_established',
            'test_id' => $client['test_id'],
            'message' => '歡迎連接到原生WebSocket測試服務器',
            'timestamp' => date('c')
        ]);
    }
    
    private function processWebSocketFrame($clientId, $data) {
        $client = &$this->clients[$clientId];
        
        // 解析WebSocket幀
        $frame = $this->decodeFrame($data);
        if (!$frame) {
            return;
        }
        
        if ($frame['opcode'] === 8) { // 關閉幀
            $this->disconnectClient($clientId);
            return;
        }
        
        if ($frame['opcode'] === 1) { // 文本幀
            $this->handleMessage($clientId, $frame['payload']);
        }
    }
    
    private function handleMessage($clientId, $message) {
        $client = &$this->clients[$clientId];
        
        echo "📨 收到消息 from {$client['test_id']}: $message\n";
        
        try {
            $data = json_decode($message, true);
            if (!$data) {
                throw new Exception('無效的JSON格式');
            }
            
            $type = $data['type'] ?? '';
            
            switch ($type) {
                case 'join_room':
                    $this->handleJoinRoom($clientId, $data);
                    break;
                    
                case 'leave_room':
                    $this->handleLeaveRoom($clientId, $data);
                    break;
                    
                case 'code_change':
                    $this->handleCodeChange($clientId, $data);
                    break;
                    
                case 'chat_message':
                    $this->handleChatMessage($clientId, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($clientId, $data);
                    break;
                    
                case 'get_history':
                    $this->handleGetHistory($clientId, $data);
                    break;
                    
                default:
                    $this->sendError($clientId, "未知的消息類型: $type");
            }
            
        } catch (Exception $e) {
            echo "❌ 消息處理錯誤: " . $e->getMessage() . "\n";
            $this->sendError($clientId, '消息處理失敗: ' . $e->getMessage());
        }
    }
    
    private function handleJoinRoom($clientId, $data) {
        $client = &$this->clients[$clientId];
        
        $roomId = $data['room_id'] ?? 'test_room_' . uniqid();
        $userId = $data['user_id'] ?? $client['test_id'];
        $username = $data['username'] ?? "測試用戶_{$client['test_id']}";
        
        // 創建房間如果不存在
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [
                'id' => $roomId,
                'name' => "測試房間 $roomId",
                'current_code' => "# 測試房間代碼\nprint('Hello from test room!')\n\n# 開始你的測試...",
                'clients' => [],
                'users' => [],
                'created_at' => date('c')
            ];
        }
        
        // 添加用戶到房間
        $this->rooms[$roomId]['clients'][$clientId] = $clientId;
        $this->rooms[$roomId]['users'][$userId] = [
            'user_id' => $userId,
            'username' => $username,
            'client_id' => $clientId,
            'joined_at' => date('c')
        ];
        
        $client['room_id'] = $roomId;
        $client['user_id'] = $userId;
        $client['username'] = $username;
        
        // 發送房間信息給新用戶
        $this->sendToClient($clientId, [
            'type' => 'room_joined',
            'room_id' => $roomId,
            'current_code' => $this->rooms[$roomId]['current_code'],
            'users' => array_values($this->rooms[$roomId]['users']),
            'message' => "成功加入測試房間: $roomId"
        ]);
        
        // 通知房間其他用戶有新用戶加入
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'message' => "$username 加入了房間"
        ], $clientId);
        
        // 廣播更新後的用戶列表給所有房間用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'room_users',
            'users' => array_values($this->rooms[$roomId]['users']),
            'user_count' => count($this->rooms[$roomId]['users']),
            'room_id' => $roomId
        ]);
        
        echo "👥 用戶 $username 加入房間 $roomId\n";
    }
    
    private function handleLeaveRoom($clientId, $data) {
        $client = &$this->clients[$clientId];
        $roomId = $client['room_id'] ?? null;
        
        if ($roomId && isset($this->rooms[$roomId])) {
            unset($this->rooms[$roomId]['clients'][$clientId]);
            unset($this->rooms[$roomId]['users'][$client['user_id']]);
            
            // 通知房間其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'user_left',
                'user_id' => $client['user_id'],
                'username' => $client['username'],
                'message' => "{$client['username']} 離開了房間"
            ], $clientId);
            
            // 廣播更新後的用戶列表
            $this->broadcastToRoom($roomId, [
                'type' => 'room_users',
                'users' => array_values($this->rooms[$roomId]['users']),
                'user_count' => count($this->rooms[$roomId]['users']),
                'room_id' => $roomId
            ]);
            
            $this->sendToClient($clientId, [
                'type' => 'room_left',
                'room_id' => $roomId,
                'message' => '已離開房間'
            ]);
            
            echo "👋 用戶 {$client['username']} 離開房間 $roomId\n";
        }
    }
    
    private function handleCodeChange($clientId, $data) {
        $client = &$this->clients[$clientId];
        $roomId = $client['room_id'] ?? null;
        $newCode = $data['code'] ?? '';
        
        if (!$roomId || !isset($this->rooms[$roomId])) {
            return $this->sendError($clientId, '未加入有效房間');
        }
        
        // 更新房間代碼
        $this->rooms[$roomId]['current_code'] = $newCode;
        
        // 廣播代碼變更給房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'code_sync',
            'code' => $newCode,
            'user_id' => $client['user_id'],
            'username' => $client['username'],
            'timestamp' => date('c')
        ], $clientId);
        
        echo "📝 房間 $roomId 代碼更新 by {$client['username']}\n";
    }
    
    private function handleChatMessage($clientId, $data) {
        $client = &$this->clients[$clientId];
        $roomId = $client['room_id'] ?? null;
        $message = $data['message'] ?? '';
        
        if (!$roomId || !isset($this->rooms[$roomId])) {
            return $this->sendError($clientId, '未加入有效房間');
        }
        
        // 廣播聊天消息
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'user_id' => $client['user_id'],
            'username' => $client['username'],
            'message' => $message,
            'timestamp' => date('c')
        ]);
        
        echo "💬 聊天消息 in $roomId from {$client['username']}: $message\n";
    }
    
    private function handlePing($clientId, $data) {
        $this->sendToClient($clientId, [
            'type' => 'pong',
            'timestamp' => date('c'),
            'server_time' => microtime(true)
        ]);
    }
    
    private function handleGetHistory($clientId, $data) {
        $client = &$this->clients[$clientId];
        $roomId = $client['room_id'] ?? null;
        
        if (!$roomId || !isset($this->rooms[$roomId])) {
            return $this->sendError($clientId, '未加入有效房間');
        }
        
        // 模擬歷史記錄數據
        $mockHistory = [
            [
                'id' => 1,
                'slot_id' => 0,
                'slot_name' => '槽位 0',
                'user_id' => 'test_user',
                'username' => '測試用戶',
                'code_preview' => '# 測試代碼\nprint("Hello World")',
                'code_length' => 35,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'saved_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'title' => '自動保存',
                'save_name' => '自動保存',
                'description' => '自動保存',
                'author' => '測試用戶',
                'operation_type' => 'save'
            ],
            [
                'id' => 2,
                'slot_id' => 1,
                'slot_name' => '槽位 1',
                'user_id' => 'test_user_2',
                'username' => '另一個測試用戶',
                'code_preview' => '# 另一個測試\nfor i in range(5):\n    print(i)',
                'code_length' => 45,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
                'saved_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
                'title' => '手動保存',
                'save_name' => '手動保存',
                'description' => '手動保存',
                'author' => '另一個測試用戶',
                'operation_type' => 'save'
            ]
        ];
        
        // 發送歷史記錄回應
        $this->sendToClient($clientId, [
            'type' => 'history_loaded',
            'history' => $mockHistory,
            'total' => count($mockHistory),
            'room_id' => $roomId,
            'message' => '歷史記錄載入成功'
        ]);
        
        echo "📚 房間 $roomId 歷史記錄請求 by {$client['username']}\n";
    }
    
    private function broadcastToRoom($roomId, $message, $excludeClientId = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        foreach ($this->rooms[$roomId]['clients'] as $clientId) {
            if ($clientId !== $excludeClientId) {
                $this->sendToClient($clientId, $message);
            }
        }
    }
    
    private function sendToClient($clientId, $data) {
        if (!isset($this->clients[$clientId])) {
            return;
        }
        
        $client = $this->clients[$clientId];
        if (!$client['handshake_done']) {
            return;
        }
        
        $message = json_encode($data, JSON_UNESCAPED_UNICODE);
        $frame = $this->encodeFrame($message);
        
        if (socket_write($client['socket'], $frame) === false) {
            $this->disconnectClient($clientId);
        }
    }
    
    private function sendError($clientId, $message) {
        $this->sendToClient($clientId, [
            'type' => 'error',
            'error' => $message,
            'timestamp' => date('c')
        ]);
    }
    
    private function disconnectClient($clientId) {
        if (!isset($this->clients[$clientId])) {
            return;
        }
        
        $client = $this->clients[$clientId];
        
        // 從房間中移除用戶
        if ($client['room_id'] && isset($this->rooms[$client['room_id']])) {
            $roomId = $client['room_id'];
            unset($this->rooms[$roomId]['clients'][$clientId]);
            unset($this->rooms[$roomId]['users'][$client['user_id']]);
            
            // 通知房間其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'user_left',
                'user_id' => $client['user_id'],
                'username' => $client['username'],
                'message' => "{$client['username']} 離開了房間"
            ], $clientId);
        }
        
        socket_close($client['socket']);
        unset($this->clients[$clientId]);
        
        echo "🔌 連接斷開: {$client['test_id']}\n";
    }
    
    private function getClientIdBySocket($socket) {
        foreach ($this->clients as $clientId => $client) {
            if ($client['socket'] === $socket) {
                return $clientId;
            }
        }
        return null;
    }
    
    private function decodeFrame($data) {
        if (strlen($data) < 2) {
            return false;
        }
        
        $firstByte = ord($data[0]);
        $secondByte = ord($data[1]);
        
        $opcode = $firstByte & 0x0F;
        $masked = ($secondByte & 0x80) === 0x80;
        $payloadLength = $secondByte & 0x7F;
        
        $offset = 2;
        
        if ($payloadLength === 126) {
            if (strlen($data) < $offset + 2) return false;
            $payloadLength = unpack('n', substr($data, $offset, 2))[1];
            $offset += 2;
        } elseif ($payloadLength === 127) {
            if (strlen($data) < $offset + 8) return false;
            $payloadLength = unpack('J', substr($data, $offset, 8))[1];
            $offset += 8;
        }
        
        if ($masked) {
            if (strlen($data) < $offset + 4) return false;
            $maskingKey = substr($data, $offset, 4);
            $offset += 4;
        }
        
        if (strlen($data) < $offset + $payloadLength) {
            return false;
        }
        
        $payload = substr($data, $offset, $payloadLength);
        
        if ($masked) {
            for ($i = 0; $i < $payloadLength; $i++) {
                $payload[$i] = $payload[$i] ^ $maskingKey[$i % 4];
            }
        }
        
        return [
            'opcode' => $opcode,
            'payload' => $payload
        ];
    }
    
    private function encodeFrame($payload, $opcode = 1) {
        $payloadLength = strlen($payload);
        
        $frame = chr(0x80 | $opcode);
        
        if ($payloadLength < 126) {
            $frame .= chr($payloadLength);
        } elseif ($payloadLength < 65536) {
            $frame .= chr(126) . pack('n', $payloadLength);
        } else {
            $frame .= chr(127) . pack('J', $payloadLength);
        }
        
        $frame .= $payload;
        
        return $frame;
    }
}

// 啟動服務器
$server = new NativeTestWebSocketServer('0.0.0.0', 8081);
$server->start();
?> 