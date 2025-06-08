<?php
/**
 * 純原生 PHP WebSocket 服務器 - 零依賴版本
 * 用於 Zeabur 部署環境，確保 WebSocket 功能可用
 * 端口: 8081
 */

class NativeWebSocketServer {
    private $socket;
    private $clients = [];
    private $rooms = [];
    private $host = '0.0.0.0';
    private $port = 8081;
    
    public function __construct() {
        echo "🚀 啟動原生 WebSocket 服務器 (零依賴版本)\n";
        echo "📡 監聽地址: {$this->host}:{$this->port}\n";
        echo "🌐 連接地址: ws://localhost:{$this->port}\n";
        echo "💾 存儲: 純內存模式\n";
        echo "📊 功能: 實時協作、聊天、基礎 AI\n";
        echo "🔧 PHP版本: " . PHP_VERSION . "\n";
        echo str_repeat("=", 50) . "\n";
        
        $this->createSocket();
    }
    
    private function createSocket() {
        // 檢查 sockets 擴展
        if (!extension_loaded('sockets')) {
            die("❌ PHP sockets 擴展未安裝\n");
        }
        
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            die("❌ 無法創建 socket: " . socket_strerror(socket_last_error()) . "\n");
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_nonblock($this->socket);

        if (!socket_bind($this->socket, $this->host, $this->port)) {
            die("❌ 無法綁定端口 {$this->port}: " . socket_strerror(socket_last_error()) . "\n");
        }

        if (!socket_listen($this->socket, 5)) {
            die("❌ 無法監聽: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        echo "✅ Socket 創建成功\n";
    }
    
    public function run() {
        echo "🚀 服務器開始運行...\n\n";
        
        while (true) {
            $read = [$this->socket];
            
            // 添加所有客戶端 socket
            foreach ($this->clients as $client) {
                if (is_resource($client['socket'])) {
                    $read[] = $client['socket'];
                }
            }
            
            $write = null;
            $except = null;
            
            $result = socket_select($read, $write, $except, 1);
            
            if ($result === false) {
                echo "❌ socket_select 錯誤: " . socket_strerror(socket_last_error()) . "\n";
                continue;
            }
            
            if ($result > 0) {
                // 檢查新連接
                if (in_array($this->socket, $read)) {
                    $this->acceptNewConnection();
                    $key = array_search($this->socket, $read);
                    unset($read[$key]);
                }
                
                // 處理客戶端消息
                foreach ($read as $clientSocket) {
                    $this->handleClientMessage($clientSocket);
                }
            }
            
            // 清理斷開的連接
            $this->cleanupClients();
        }
    }
    
    private function acceptNewConnection() {
        $clientSocket = socket_accept($this->socket);
        if (!$clientSocket) {
            return;
        }
        
        $clientId = uniqid('client_');
        $clientInfo = [
            'id' => $clientId,
            'socket' => $clientSocket,
            'handshake_done' => false,
            'buffer' => '',
            'room_id' => null,
            'user_id' => null,
            'username' => null,
            'connected_at' => date('c')
        ];
        
        $this->clients[$clientId] = $clientInfo;
        
        echo "🔗 新連接建立: {$clientId}\n";
    }
    
    private function handleClientMessage($clientSocket) {
        $clientId = $this->getClientIdBySocket($clientSocket);
        if (!$clientId) {
            return;
        }
        
        $client = &$this->clients[$clientId];
        
        // 讀取數據
        $data = @socket_read($clientSocket, 4096, PHP_BINARY_READ);
        
        if ($data === false) {
            $error = socket_last_error($clientSocket);
            if ($error !== SOCKET_EWOULDBLOCK && $error !== 0) {
                $this->disconnectClient($clientId);
            }
            return;
        }
        
        if ($data === '') {
            echo "🔌 客戶端主動斷開: {$clientId}\n";
            $this->disconnectClient($clientId);
            return;
        }
        
        $client['buffer'] .= $data;
        
        if (!$client['handshake_done']) {
            $this->performHandshake($clientId);
        } else {
            $this->processWebSocketFrames($clientId);
        }
    }
    
    private function performHandshake($clientId) {
        $client = &$this->clients[$clientId];
        
        // 檢查是否有完整的 HTTP 請求
        if (strpos($client['buffer'], "\r\n\r\n") === false) {
            return;
        }
        
        $data = $client['buffer'];
        $lines = explode("\r\n", $data);
        $headers = [];
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        if (!isset($headers['Sec-WebSocket-Key'])) {
            echo "❌ 缺少 Sec-WebSocket-Key 頭部 ({$clientId})\n";
            $this->disconnectClient($clientId);
            return;
        }
        
        $key = $headers['Sec-WebSocket-Key'];
        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";
        
        $result = @socket_write($client['socket'], $response);
        
        if ($result === false) {
            echo "❌ 發送握手響應失敗 ({$clientId})\n";
            $this->disconnectClient($clientId);
            return;
        }
        
        $client['handshake_done'] = true;
        $client['buffer'] = '';
        
        echo "✅ WebSocket 握手成功: {$clientId}\n";
        
        // 發送歡迎消息
        $this->sendToClient($clientId, [
            'type' => 'connection_established',
            'message' => '歡迎連接到原生 WebSocket 服務器',
            'client_id' => $clientId,
            'server_version' => 'native-v1.0',
            'timestamp' => date('c')
        ]);
    }
    
    private function processWebSocketFrames($clientId) {
        $client = &$this->clients[$clientId];
        
        while (strlen($client['buffer']) >= 2) {
            $frame = $this->decodeFrame($client['buffer']);
            
            if ($frame === false) {
                break; // 需要更多數據
            }
            
            if ($frame === null) {
                // 解碼錯誤
                $this->disconnectClient($clientId);
                return;
            }
            
            // 移除已處理的數據
            $client['buffer'] = substr($client['buffer'], $frame['frame_size']);
            
            // 處理消息
            if ($frame['opcode'] === 1) { // 文本消息
                $this->handleMessage($clientId, $frame['payload']);
            } elseif ($frame['opcode'] === 8) { // 關閉連接
                $this->disconnectClient($clientId);
                return;
            }
        }
    }
    
    private function decodeFrame($data) {
        if (strlen($data) < 2) {
            return false;
        }
        
        $firstByte = ord($data[0]);
        $secondByte = ord($data[1]);
        
        $fin = ($firstByte >> 7) & 1;
        $opcode = $firstByte & 15;
        $masked = ($secondByte >> 7) & 1;
        $payloadLength = $secondByte & 127;
        
        $offset = 2;
        
        if ($payloadLength === 126) {
            if (strlen($data) < $offset + 2) {
                return false;
            }
            $payloadLength = unpack('n', substr($data, $offset, 2))[1];
            $offset += 2;
        } elseif ($payloadLength === 127) {
            if (strlen($data) < $offset + 8) {
                return false;
            }
            $payloadLength = unpack('J', substr($data, $offset, 8))[1];
            $offset += 8;
        }
        
        if ($masked) {
            if (strlen($data) < $offset + 4) {
                return false;
            }
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
            'fin' => $fin,
            'opcode' => $opcode,
            'payload' => $payload,
            'frame_size' => $offset + $payloadLength
        ];
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
    
    private function handleMessage($clientId, $message) {
        echo "📨 收到消息 from {$clientId}: " . substr($message, 0, 100) . "\n";
        
        try {
            $data = json_decode($message, true);
            if (!$data) {
                $this->sendError($clientId, '無效的JSON格式');
                return;
            }
            
            $type = $data['type'] ?? '';
            
            switch ($type) {
                case 'join_room':
                    $this->handleJoinRoom($clientId, $data);
                    break;
                    
                case 'leave_room':
                    $this->handleLeaveRoom($clientId);
                    break;
                    
                case 'code_change':
                    $this->handleCodeChange($clientId, $data);
                    break;
                    
                case 'chat_message':
                    $this->handleChatMessage($clientId, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($clientId);
                    break;
                    
                default:
                    $this->sendError($clientId, "未知的消息類型: {$type}");
            }
            
        } catch (Exception $e) {
            $this->sendError($clientId, '消息處理錯誤: ' . $e->getMessage());
        }
    }
    
    private function handleJoinRoom($clientId, $data) {
        $roomId = $data['room_id'] ?? '';
        $userId = $data['user_id'] ?? '';
        $username = $data['username'] ?? '';
        
        if (!$roomId || !$userId || !$username) {
            $this->sendError($clientId, '缺少必要參數');
            return;
        }
        
        $client = &$this->clients[$clientId];
        $client['room_id'] = $roomId;
        $client['user_id'] = $userId;
        $client['username'] = $username;
        
        // 初始化房間
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [
                'users' => [],
                'code' => '',
                'created_at' => date('c')
            ];
        }
        
        $this->rooms[$roomId]['users'][$userId] = [
            'client_id' => $clientId,
            'username' => $username,
            'joined_at' => date('c')
        ];
        
        echo "👤 用戶 {$username} 加入房間 {$roomId}\n";
        
        // 發送成功響應
        $this->sendToClient($clientId, [
            'type' => 'room_joined',
            'room_id' => $roomId,
            'user_id' => $userId,
            'username' => $username,
            'current_code' => $this->rooms[$roomId]['code'],
            'users' => array_values($this->rooms[$roomId]['users']),
            'timestamp' => date('c')
        ]);
        
        // 通知房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'timestamp' => date('c')
        ], $clientId);
    }
    
    private function handleLeaveRoom($clientId) {
        $client = $this->clients[$clientId] ?? null;
        if (!$client || !$client['room_id']) {
            return;
        }
        
        $roomId = $client['room_id'];
        $userId = $client['user_id'];
        $username = $client['username'];
        
        // 從房間移除用戶
        if (isset($this->rooms[$roomId]['users'][$userId])) {
            unset($this->rooms[$roomId]['users'][$userId]);
            
            echo "👋 用戶 {$username} 離開房間 {$roomId}\n";
            
            // 通知房間其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'user_left',
                'user_id' => $userId,
                'username' => $username,
                'timestamp' => date('c')
            ]);
            
            // 如果房間空了，清理房間
            if (empty($this->rooms[$roomId]['users'])) {
                unset($this->rooms[$roomId]);
                echo "🗑️ 房間 {$roomId} 已清理\n";
            }
        }
        
        // 清理客戶端房間信息
        $this->clients[$clientId]['room_id'] = null;
        $this->clients[$clientId]['user_id'] = null;
        $this->clients[$clientId]['username'] = null;
    }
    
    private function handleCodeChange($clientId, $data) {
        $client = $this->clients[$clientId] ?? null;
        if (!$client || !$client['room_id']) {
            $this->sendError($clientId, '未加入房間');
            return;
        }
        
        $roomId = $client['room_id'];
        $newCode = $data['code'] ?? '';
        
        // 更新房間代碼
        $this->rooms[$roomId]['code'] = $newCode;
        
        // 廣播代碼變更
        $this->broadcastToRoom($roomId, [
            'type' => 'code_updated',
            'code' => $newCode,
            'user_id' => $client['user_id'],
            'username' => $client['username'],
            'timestamp' => date('c')
        ], $clientId);
    }
    
    private function handleChatMessage($clientId, $data) {
        $client = $this->clients[$clientId] ?? null;
        if (!$client || !$client['room_id']) {
            $this->sendError($clientId, '未加入房間');
            return;
        }
        
        $message = $data['message'] ?? '';
        if (!$message) {
            $this->sendError($clientId, '消息不能為空');
            return;
        }
        
        $roomId = $client['room_id'];
        
        // 廣播聊天消息
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'user_id' => $client['user_id'],
            'username' => $client['username'],
            'message' => $message,
            'timestamp' => date('c')
        ]);
    }
    
    private function handlePing($clientId) {
        $this->sendToClient($clientId, [
            'type' => 'pong',
            'timestamp' => date('c')
        ]);
    }
    
    private function broadcastToRoom($roomId, $data, $exceptClientId = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        foreach ($this->rooms[$roomId]['users'] as $user) {
            if ($user['client_id'] !== $exceptClientId) {
                $this->sendToClient($user['client_id'], $data);
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
        
        $message = json_encode($data);
        $frame = $this->encodeFrame($message);
        
        $result = @socket_write($client['socket'], $frame);
        if ($result === false) {
            echo "❌ 發送消息失敗: {$clientId}\n";
            $this->disconnectClient($clientId);
        }
    }
    
    private function sendError($clientId, $message) {
        $this->sendToClient($clientId, [
            'type' => 'error',
            'message' => $message,
            'timestamp' => date('c')
        ]);
    }
    
    private function getClientIdBySocket($socket) {
        foreach ($this->clients as $clientId => $client) {
            if ($client['socket'] === $socket) {
                return $clientId;
            }
        }
        return null;
    }
    
    private function disconnectClient($clientId) {
        if (!isset($this->clients[$clientId])) {
            return;
        }
        
        $client = $this->clients[$clientId];
        
        // 離開房間
        if ($client['room_id']) {
            $this->handleLeaveRoom($clientId);
        }
        
        // 關閉 socket
        @socket_close($client['socket']);
        
        // 移除客戶端
        unset($this->clients[$clientId]);
        
        echo "🗑️ 客戶端已斷開: {$clientId}\n";
    }
    
    private function cleanupClients() {
        foreach ($this->clients as $clientId => $client) {
            if (!is_resource($client['socket'])) {
                unset($this->clients[$clientId]);
                echo "🧹 清理無效客戶端: {$clientId}\n";
            }
        }
    }
}

// 啟動服務器
try {
    $server = new NativeWebSocketServer();
    $server->run();
} catch (Exception $e) {
    echo "❌ 服務器啟動失敗: {$e->getMessage()}\n";
    exit(1);
}
?> 