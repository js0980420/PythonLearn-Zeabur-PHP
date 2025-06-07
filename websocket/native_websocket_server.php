<?php
/**
 * 原生 WebSocket 服務器
 * 完全使用 PHP 原生 socket 實現，無外部依賴
 * 支持完整的協作編程功能
 */

class NativeWebSocketServer {
    private $socket;
    private $clients = [];
    private $rooms = [];

    public function __construct($host = '0.0.0.0', $port = 8081) {
        $this->host = $host;
        $this->port = $port;
        $this->createSocket();
    }

    private function createSocket() {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        if (!$this->socket) {
            throw new Exception('無法創建 socket: ' . socket_strerror(socket_last_error()));
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_nonblock($this->socket);

        if (!socket_bind($this->socket, $this->host, $this->port)) {
            throw new Exception('無法綁定端口: ' . socket_strerror(socket_last_error()));
        }

        if (!socket_listen($this->socket, 5)) {
            throw new Exception('無法監聽端口: ' . socket_strerror(socket_last_error()));
        }

        echo "🚀 啟動原生 WebSocket 服務器...\n";
        echo "📡 監聽地址: {$this->host}:{$this->port}\n";
        echo "🌐 連接地址: ws://localhost:{$this->port}\n";
        echo "✅ 服務器啟動成功，等待連接...\n";
    }

    public function run() {
        while (true) {
            $read = [$this->socket];
            
            // 添加所有客戶端 socket 到讀取列表
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
            $this->cleanupDisconnectedClients();
            
            // 檢查超時連接
            $this->checkTimeouts();
        }
    }

    private function acceptNewConnection() {
        $newSocket = socket_accept($this->socket);
        
        if (!$newSocket) {
            $error = socket_last_error();
            if ($error !== SOCKET_EWOULDBLOCK) {
                echo "❌ 接受連接失敗: " . socket_strerror($error) . "\n";
            }
            return;
        }
        
        // 設置為非阻塞模式
        socket_set_nonblock($newSocket);
        
        $clientId = uniqid('client_');
        
        $this->clients[$clientId] = [
            'id' => $clientId,
            'socket' => $newSocket,
            'handshake' => false,
            'room_id' => null,
            'user_id' => null,
            'username' => null,
            'last_ping' => time(),
            'buffer' => '',
            'connected_at' => time()
        ];
        
        echo "🔗 新連接: {$clientId}\n";
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
                echo "❌ 讀取數據錯誤 ({$clientId}): " . socket_strerror($error) . "\n";
                $this->disconnectClient($clientId);
            }
            return;
        }
        
        if ($data === '') {
            echo "🔌 客戶端主動斷開: {$clientId}\n";
            $this->disconnectClient($clientId);
            return;
        }
        
        // 將數據添加到緩衝區
        $client['buffer'] .= $data;
        
        if (!$client['handshake']) {
            $this->performHandshake($clientId);
        } else {
            $this->processWebSocketFrames($clientId);
        }
    }

    private function performHandshake($clientId) {
        $client = &$this->clients[$clientId];
        
        // 檢查是否有完整的 HTTP 請求
        if (strpos($client['buffer'], "\r\n\r\n") === false) {
            return; // 等待更多數據
        }
        
        $data = $client['buffer'];
        
        // 解析 HTTP 請求頭
        $lines = explode("\r\n", $data);
        $headers = [];
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        // 驗證 WebSocket 握手
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
            echo "❌ 發送握手響應失敗 ({$clientId}): " . socket_strerror(socket_last_error($client['socket'])) . "\n";
            $this->disconnectClient($clientId);
            return;
        }
        
        $client['handshake'] = true;
        $client['buffer'] = ''; // 清空緩衝區
        
        echo "✅ WebSocket 握手成功: {$clientId}\n";
    }

    private function processWebSocketFrames($clientId) {
        $client = &$this->clients[$clientId];
        
        while (strlen($client['buffer']) >= 2) {
            $frame = $this->parseWebSocketFrame($client['buffer']);
            
            if ($frame === null) {
                break; // 需要更多數據
            }
            
            if ($frame === false) {
                echo "❌ 無效的 WebSocket 幀 ({$clientId})\n";
                $this->disconnectClient($clientId);
                return;
            }
            
            // 從緩衝區移除已處理的數據
            $client['buffer'] = substr($client['buffer'], $frame['frame_length']);
            
            // 處理幀
            $this->handleWebSocketFrame($clientId, $frame);
        }
    }

    private function parseWebSocketFrame($data) {
        if (strlen($data) < 2) {
            return null; // 需要更多數據
        }
        
        $firstByte = ord($data[0]);
        $secondByte = ord($data[1]);
        
        $fin = ($firstByte & 0x80) === 0x80;
        $opcode = $firstByte & 0x0F;
        $masked = ($secondByte & 0x80) === 0x80;
        $payloadLength = $secondByte & 0x7F;
        
        $offset = 2;
        
        if ($payloadLength === 126) {
            if (strlen($data) < $offset + 2) {
                return null; // 需要更多數據
            }
            $payloadLength = unpack('n', substr($data, $offset, 2))[1];
            $offset += 2;
        } elseif ($payloadLength === 127) {
            if (strlen($data) < $offset + 8) {
                return null; // 需要更多數據
            }
            $payloadLength = unpack('J', substr($data, $offset, 8))[1];
            $offset += 8;
        }
        
        if ($masked) {
            if (strlen($data) < $offset + 4) {
                return null; // 需要更多數據
            }
            $maskingKey = substr($data, $offset, 4);
            $offset += 4;
        }
        
        if (strlen($data) < $offset + $payloadLength) {
            return null; // 需要更多數據
        }
        
        $payload = substr($data, $offset, $payloadLength);
        
        if ($masked) {
            for ($i = 0; $i < strlen($payload); $i++) {
                $payload[$i] = chr(ord($payload[$i]) ^ ord($maskingKey[$i % 4]));
            }
        }
        
        return [
            'fin' => $fin,
            'opcode' => $opcode,
            'payload' => $payload,
            'frame_length' => $offset + $payloadLength
        ];
    }

    private function handleWebSocketFrame($clientId, $frame) {
        $client = &$this->clients[$clientId];
        
        switch ($frame['opcode']) {
            case 0x1: // 文本幀
                $this->handleTextMessage($clientId, $frame['payload']);
                break;
            case 0x8: // 關閉幀
                echo "📪 收到關閉幀: {$clientId}\n";
                $this->disconnectClient($clientId);
                break;
            case 0x9: // Ping 幀
                $this->sendPong($clientId, $frame['payload']);
                break;
            case 0xA: // Pong 幀
                $client['last_ping'] = time();
                break;
            default:
                echo "⚠️ 未知的 opcode: {$frame['opcode']} ({$clientId})\n";
        }
    }

    private function handleTextMessage($clientId, $message) {
        echo "📨 收到消息 from {$clientId}: {$message}\n";
        
        try {
            $data = json_decode($message, true);
            
            if (!$data) {
                throw new Exception('無效的 JSON 格式');
            }
            
            $this->processMessage($clientId, $data);
            
        } catch (Exception $e) {
            echo "❌ 消息處理錯誤 ({$clientId}): " . $e->getMessage() . "\n";
            $this->sendError($clientId, '消息處理失敗: ' . $e->getMessage());
        }
    }

    private function processMessage($clientId, $data) {
        $client = &$this->clients[$clientId];
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
                echo "⚠️ 未知的消息類型: {$type} ({$clientId})\n";
                $this->sendError($clientId, "未知的消息類型: {$type}");
        }
    }

    private function handleJoinRoom($clientId, $data) {
        $client = &$this->clients[$clientId];
        $roomId = $data['room_id'] ?? '';
        $userId = $data['user_id'] ?? '';
        $username = $data['username'] ?? '';
        
        if (!$roomId || !$userId || !$username) {
            $this->sendError($clientId, '缺少必要的房間信息');
            return;
        }
        
        // 如果用戶已經在其他房間，先離開
        if ($client['room_id']) {
            $this->removeUserFromRoom($clientId, $client['room_id']);
        }
        
        // 加入新房間
        $client['room_id'] = $roomId;
        $client['user_id'] = $userId;
        $client['username'] = $username;
        
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [
                'users' => [],
                'code' => '',
                'version' => 0
            ];
        }
        
        $this->rooms[$roomId]['users'][$clientId] = [
            'user_id' => $userId,
            'username' => $username,
            'joined_at' => time()
        ];
        
        echo "👥 用戶 {$username} ({$userId}) 加入房間 {$roomId}\n";
        
        // 發送房間加入成功響應
        $this->sendToClient($clientId, [
            'type' => 'room_joined',
            'room_id' => $roomId,
            'user_id' => $userId,
            'username' => $username,
            'code' => $this->rooms[$roomId]['code'],
            'version' => $this->rooms[$roomId]['version'],
            'users' => array_values($this->rooms[$roomId]['users']),
            'timestamp' => date('c')
        ]);
        
        // 通知房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'users' => array_values($this->rooms[$roomId]['users']),
            'timestamp' => date('c')
        ], $clientId);
    }

    private function handleLeaveRoom($clientId, $data) {
        $client = &$this->clients[$clientId];
        
        if ($client['room_id']) {
            $this->removeUserFromRoom($clientId, $client['room_id']);
        }
    }

    private function handleCodeChange($clientId, $data) {
        $client = &$this->clients[$clientId];
        $roomId = $client['room_id'];
        
        if (!$roomId) {
            $this->sendError($clientId, '用戶未加入任何房間');
            return;
        }
        
        $code = $data['code'] ?? '';
        $version = $data['version'] ?? 0;
        
        // 更新房間代碼
        $this->rooms[$roomId]['code'] = $code;
        $this->rooms[$roomId]['version'] = $version + 1;
        
        echo "📝 代碼更新 in {$roomId} by {$client['username']}: " . strlen($code) . " 字符\n";
        
        // 廣播代碼變更到房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'code_change',
            'code' => $code,
            'version' => $this->rooms[$roomId]['version'],
            'user_id' => $client['user_id'],
            'username' => $client['username'],
            'timestamp' => date('c')
        ], $clientId);
    }

    private function handleChatMessage($clientId, $data) {
        $client = &$this->clients[$clientId];
        $roomId = $client['room_id'];
        
        if (!$roomId) {
            $this->sendError($clientId, '用戶未加入任何房間');
            return;
        }
        
        $message = $data['message'] ?? '';
        
        if (!$message) {
            $this->sendError($clientId, '聊天消息不能為空');
            return;
        }
        
        echo "💬 聊天消息 in {$roomId} from {$client['username']}: {$message}\n";
        
        // 廣播聊天消息到房間內所有用戶（包括發送者）
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'message' => $message,
            'user_id' => $client['user_id'],
            'username' => $client['username'],
            'timestamp' => date('c')
        ]);
    }

    private function handlePing($clientId, $data) {
        $client = &$this->clients[$clientId];
        $client['last_ping'] = time();
        
        // 發送 pong 響應
        $this->sendToClient($clientId, [
            'type' => 'pong',
            'timestamp' => date('c')
        ]);
    }

    private function handleGetHistory($clientId, $data) {
        $roomId = $data['room_id'] ?? '';
        
        if (!$roomId) {
            $this->sendError($clientId, '缺少房間 ID');
            return;
        }
        
        echo "📜 歷史記錄請求 for {$roomId} from {$clientId}\n";
        
        // 模擬歷史記錄數據
        $history = [
            [
                'id' => 1,
                'name' => '範例代碼 1',
                'code' => 'print("Hello World")',
                'created_at' => date('c', time() - 3600),
                'user_id' => 'system',
                'username' => '系統'
            ],
            [
                'id' => 2,
                'name' => '範例代碼 2',
                'code' => 'for i in range(10):\n    print(i)',
                'created_at' => date('c', time() - 1800),
                'user_id' => 'system',
                'username' => '系統'
            ]
        ];
        
        $this->sendToClient($clientId, [
            'type' => 'history_response',
            'room_id' => $roomId,
            'history' => $history,
            'timestamp' => date('c')
        ]);
    }

    private function removeUserFromRoom($clientId, $roomId) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        $client = &$this->clients[$clientId];
        $username = $client['username'] ?? 'Unknown';
        $userId = $client['user_id'] ?? 'Unknown';
        
        unset($this->rooms[$roomId]['users'][$clientId]);
        
        echo "👋 用戶 {$username} ({$userId}) 離開房間 {$roomId}\n";
        
        // 通知房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_left',
            'user_id' => $userId,
            'username' => $username,
            'users' => array_values($this->rooms[$roomId]['users']),
            'timestamp' => date('c')
        ]);
        
        // 如果房間沒有用戶了，清理房間
        if (empty($this->rooms[$roomId]['users'])) {
            unset($this->rooms[$roomId]);
            echo "🗑️ 清理空房間: {$roomId}\n";
        }
        
        $client['room_id'] = null;
        $client['user_id'] = null;
        $client['username'] = null;
    }

    private function broadcastToRoom($roomId, $message, $excludeClientId = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        foreach ($this->rooms[$roomId]['users'] as $clientId => $user) {
            if ($clientId !== $excludeClientId) {
                $this->sendToClient($clientId, $message);
            }
        }
    }

    private function sendToClient($clientId, $data) {
        if (!isset($this->clients[$clientId])) {
            return false;
        }
        
        $message = json_encode($data);
        return $this->sendWebSocketFrame($this->clients[$clientId]['socket'], $message);
    }

    private function sendError($clientId, $message) {
        $this->sendToClient($clientId, [
            'type' => 'error',
            'message' => $message,
            'timestamp' => date('c')
        ]);
    }

    private function sendPong($clientId, $payload = '') {
        if (!isset($this->clients[$clientId])) {
            return;
        }
        
        $socket = $this->clients[$clientId]['socket'];
        
        // 構建 Pong 幀 (opcode 0xA)
        $frame = chr(0x8A) . chr(strlen($payload)) . $payload;
        
        @socket_write($socket, $frame);
    }

    private function sendWebSocketFrame($socket, $message) {
        $length = strlen($message);
        
        if ($length < 126) {
            $frame = chr(0x81) . chr($length) . $message;
        } elseif ($length < 65536) {
            $frame = chr(0x81) . chr(126) . pack('n', $length) . $message;
        } else {
            $frame = chr(0x81) . chr(127) . pack('J', $length) . $message;
        }
        
        $result = @socket_write($socket, $frame);
        
        if ($result === false) {
            echo "❌ 發送消息失敗: " . socket_strerror(socket_last_error($socket)) . "\n";
            return false;
        }
        
        return true;
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
        
        // 從房間中移除用戶
        if ($client['room_id']) {
            $this->removeUserFromRoom($clientId, $client['room_id']);
        }
        
        // 關閉 socket
        if (is_resource($client['socket'])) {
            @socket_close($client['socket']);
        }
        
        unset($this->clients[$clientId]);
        
        echo "🔌 連接斷開: {$clientId}\n";
    }

    private function cleanupDisconnectedClients() {
        foreach ($this->clients as $clientId => $client) {
            if (!is_resource($client['socket'])) {
                $this->disconnectClient($clientId);
            }
        }
    }

    private function checkTimeouts() {
        $now = time();
        
        foreach ($this->clients as $clientId => $client) {
            // 5分鐘超時
            if ($now - $client['last_ping'] > 300) {
                echo "⏰ 客戶端超時: {$clientId}\n";
                $this->disconnectClient($clientId);
            }
        }
    }

    public function stop() {
        echo "🛑 停止 WebSocket 服務器...\n";
        
        // 關閉所有客戶端連接
        foreach ($this->clients as $clientId => $client) {
            $this->disconnectClient($clientId);
        }
        
        // 關閉服務器 socket
        if (is_resource($this->socket)) {
            socket_close($this->socket);
        }
        
        echo "✅ 服務器已停止\n";
    }

    public function __destruct() {
        $this->stop();
    }
}

// 啟動服務器
try {
    $server = new NativeWebSocketServer('0.0.0.0', 8081);
    $server->run();
} catch (Exception $e) {
    echo "❌ 服務器錯誤: " . $e->getMessage() . "\n";
}