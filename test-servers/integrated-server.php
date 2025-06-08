<?php
/**
 * 整合服務器 - 在同一進程中處理 HTTP 和 WebSocket 請求
 * 專為 Zeabur 單端口部署設計
 * 版本: v2.0 - 純 PHP 實現
 */

class IntegratedServer {
    private $host = '0.0.0.0';
    private $port = 8080;
    private $socket;
    private $clients = [];
    private $rooms = [];
    
    public function __construct() {
        echo "🚀 啟動整合服務器 v2.0 (純 PHP 實現)\n";
        echo "📡 監聽地址: {$this->host}:{$this->port}\n";
        echo "🌐 HTTP 服務: http://{$this->host}:{$this->port}\n";
        echo "🔌 WebSocket 服務: ws://{$this->host}:{$this->port}/ws\n";
        echo "💾 存儲模式: 純內存 (無數據庫依賴)\n";
        echo "🔧 PHP版本: " . PHP_VERSION . "\n";
        echo str_repeat("=", 50) . "\n";
        
        $this->createSocket();
        $this->run();
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
        
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            die("❌ 無法綁定到 {$this->host}:{$this->port}: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        if (!socket_listen($this->socket, 10)) {
            die("❌ 無法監聽端口 {$this->port}: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        echo "✅ 服務器已啟動並監聽 {$this->host}:{$this->port}\n";
    }
    
    private function run() {
        echo "🔄 開始主循環，等待連接...\n\n";
        
        while (true) {
            $read = [$this->socket];
            $write = null;
            $except = null;
            
            // 添加所有客戶端連接到讀取列表
            foreach ($this->clients as $client) {
                if (is_resource($client['socket'])) {
                    $read[] = $client['socket'];
                }
            }
            
            $ready = socket_select($read, $write, $except, 1);
            
            if ($ready === false) {
                echo "❌ socket_select 失敗\n";
                break;
            }
            
            if ($ready > 0) {
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
            $this->cleanupConnections();
        }
    }
    
    private function acceptNewConnection() {
        $clientSocket = socket_accept($this->socket);
        
        if ($clientSocket === false) {
            echo "⚠️ 接受連接失敗\n";
            return;
        }
        
        $clientId = uniqid('client_');
        $this->clients[$clientId] = [
            'socket' => $clientSocket,
            'handshake' => false,
            'type' => 'unknown',
            'buffer' => '',
            'user_id' => null,
            'room_id' => null,
            'username' => null,
            'last_activity' => time()
        ];
        
        echo "🔗 新連接: {$clientId}\n";
    }
    
    private function handleClientMessage($clientSocket) {
        $clientId = $this->findClientId($clientSocket);
        if (!$clientId) {
            return;
        }
        
        $client = &$this->clients[$clientId];
        $client['last_activity'] = time();
        
        $data = socket_read($clientSocket, 4096);
        
        if ($data === false || $data === '') {
            echo "🔌 客戶端 {$clientId} 斷開連接\n";
            $this->removeClient($clientId);
            return;
        }
        
        $client['buffer'] .= $data;
        
        if (!$client['handshake']) {
            $this->handleHandshake($clientId);
        } else {
            $this->handleWebSocketMessage($clientId);
        }
    }
    
    private function handleHandshake($clientId) {
        $client = &$this->clients[$clientId];
        
        // 檢查是否有完整的 HTTP 請求
        if (strpos($client['buffer'], "\r\n\r\n") === false) {
            return; // 等待更多數據
        }
        
        $request = substr($client['buffer'], 0, strpos($client['buffer'], "\r\n\r\n"));
        $client['buffer'] = substr($client['buffer'], strpos($client['buffer'], "\r\n\r\n") + 4);
        
        $lines = explode("\r\n", $request);
        $requestLine = $lines[0];
        
        // 解析請求行
        if (preg_match('/^(GET|POST)\s+([^\s]+)\s+HTTP\/1\.[01]$/', $requestLine, $matches)) {
            $method = $matches[1];
            $path = $matches[2];
            
            echo "📥 {$method} {$path} from {$clientId}\n";
            
            // 解析請求頭
            $headers = [];
            for ($i = 1; $i < count($lines); $i++) {
                if (strpos($lines[$i], ':') !== false) {
                    list($key, $value) = explode(':', $lines[$i], 2);
                    $headers[strtolower(trim($key))] = trim($value);
                }
            }
            
            // 檢查是否為 WebSocket 升級請求
            if (isset($headers['upgrade']) && strtolower($headers['upgrade']) === 'websocket') {
                $this->handleWebSocketHandshake($clientId, $headers);
            } else {
                $this->handleHttpRequest($clientId, $method, $path, $headers);
            }
        } else {
            echo "⚠️ 無效的請求行: {$requestLine}\n";
            $this->removeClient($clientId);
        }
    }
    
    private function handleWebSocketHandshake($clientId, $headers) {
        $client = &$this->clients[$clientId];
        
        if (!isset($headers['sec-websocket-key'])) {
            echo "❌ 缺少 WebSocket 密鑰\n";
            $this->removeClient($clientId);
            return;
        }
        
        $key = $headers['sec-websocket-key'];
        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        
        $response = "HTTP/1.1 101 Switching Protocols\r\n";
        $response .= "Upgrade: websocket\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Sec-WebSocket-Accept: {$acceptKey}\r\n";
        $response .= "\r\n";
        
        socket_write($client['socket'], $response);
        
        $client['handshake'] = true;
        $client['type'] = 'websocket';
        
        echo "✅ WebSocket 握手完成: {$clientId}\n";
        
        // 發送歡迎消息
        $this->sendWebSocketMessage($clientId, [
            'type' => 'connected',
            'message' => '歡迎連接到 Python 協作學習平台',
            'timestamp' => date('c')
        ]);
    }
    
    private function handleHttpRequest($clientId, $method, $path, $headers) {
        $client = &$this->clients[$clientId];
        
        // 處理靜態文件請求
        if ($method === 'GET') {
            $this->serveStaticFile($clientId, $path);
        } else {
            // 處理 API 請求
            $this->handleApiRequest($clientId, $method, $path, $headers);
        }
        
        // HTTP 請求處理完畢後關閉連接
        $this->removeClient($clientId);
    }
    
    private function serveStaticFile($clientId, $path) {
        $client = &$this->clients[$clientId];
        
        // 安全檢查：防止目錄遍歷攻擊
        $path = parse_url($path, PHP_URL_PATH);
        $path = ltrim($path, '/');
        
        if (empty($path) || $path === '/') {
            $path = 'index.html';
        }
        
        $filePath = __DIR__ . '/../public/' . $path;
        $realPath = realpath($filePath);
        $publicDir = realpath(__DIR__ . '/../public');
        
        // 確保文件在 public 目錄內
        if (!$realPath || strpos($realPath, $publicDir) !== 0) {
            $this->send404($clientId);
            return;
        }
        
        if (!file_exists($realPath) || !is_file($realPath)) {
            $this->send404($clientId);
            return;
        }
        
        $mimeType = $this->getMimeType($realPath);
        $content = file_get_contents($realPath);
        
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Content-Type: {$mimeType}\r\n";
        $response .= "Content-Length: " . strlen($content) . "\r\n";
        $response .= "Cache-Control: no-cache\r\n";
        $response .= "\r\n";
        $response .= $content;
        
        socket_write($client['socket'], $response);
        
        echo "📄 提供文件: {$path} ({$mimeType})\n";
    }
    
    private function send404($clientId) {
        $client = &$this->clients[$clientId];
        
        $content = "<!DOCTYPE html><html><head><title>404 Not Found</title></head>";
        $content .= "<body><h1>404 Not Found</h1><p>The requested file was not found.</p></body></html>";
        
        $response = "HTTP/1.1 404 Not Found\r\n";
        $response .= "Content-Type: text/html\r\n";
        $response .= "Content-Length: " . strlen($content) . "\r\n";
        $response .= "\r\n";
        $response .= $content;
        
        socket_write($client['socket'], $response);
        
        echo "❌ 404: 文件未找到\n";
    }
    
    private function getMimeType($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'html' => 'text/html',
            'htm' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'txt' => 'text/plain',
            'php' => 'text/html'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    private function handleApiRequest($clientId, $method, $path, $headers) {
        // 簡單的 API 處理
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Content-Type: application/json\r\n";
        $response .= "\r\n";
        $response .= json_encode(['status' => 'ok', 'message' => 'API endpoint']);
        
        socket_write($this->clients[$clientId]['socket'], $response);
    }
    
    private function handleWebSocketMessage($clientId) {
        $client = &$this->clients[$clientId];
        
        // 簡單的 WebSocket 幀解析
        while (strlen($client['buffer']) >= 2) {
            $firstByte = ord($client['buffer'][0]);
            $secondByte = ord($client['buffer'][1]);
            
            $fin = ($firstByte & 0x80) === 0x80;
            $opcode = $firstByte & 0x0F;
            $masked = ($secondByte & 0x80) === 0x80;
            $payloadLength = $secondByte & 0x7F;
            
            $headerLength = 2;
            
            if ($payloadLength === 126) {
                if (strlen($client['buffer']) < 4) return;
                $payloadLength = unpack('n', substr($client['buffer'], 2, 2))[1];
                $headerLength = 4;
            } elseif ($payloadLength === 127) {
                if (strlen($client['buffer']) < 10) return;
                $payloadLength = unpack('J', substr($client['buffer'], 2, 8))[1];
                $headerLength = 10;
            }
            
            if ($masked) {
                $headerLength += 4;
            }
            
            if (strlen($client['buffer']) < $headerLength + $payloadLength) {
                return; // 等待更多數據
            }
            
            $payload = substr($client['buffer'], $headerLength, $payloadLength);
            
            if ($masked) {
                $mask = substr($client['buffer'], $headerLength - 4, 4);
                for ($i = 0; $i < $payloadLength; $i++) {
                    $payload[$i] = $payload[$i] ^ $mask[$i % 4];
                }
            }
            
            $client['buffer'] = substr($client['buffer'], $headerLength + $payloadLength);
            
            if ($opcode === 0x8) { // 關閉幀
                $this->removeClient($clientId);
                return;
            } elseif ($opcode === 0x9) { // Ping 幀
                $this->sendWebSocketPong($clientId, $payload);
            } elseif ($opcode === 0x1) { // 文本幀
                $this->processWebSocketMessage($clientId, $payload);
            }
        }
    }
    
    private function processWebSocketMessage($clientId, $payload) {
        try {
            $message = json_decode($payload, true);
            
            if (!$message) {
                echo "⚠️ 無效的 JSON 消息 from {$clientId}\n";
                return;
            }
            
            echo "📥 WebSocket 消息: {$message['type']} from {$clientId}\n";
            
            switch ($message['type']) {
                case 'join_room':
                    $this->handleJoinRoom($clientId, $message);
                    break;
                case 'leave_room':
                    $this->handleLeaveRoom($clientId, $message);
                    break;
                case 'code_change':
                    $this->handleCodeChange($clientId, $message);
                    break;
                case 'chat_message':
                    $this->handleChatMessage($clientId, $message);
                    break;
                default:
                    echo "⚠️ 未知消息類型: {$message['type']}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ 處理 WebSocket 消息錯誤: " . $e->getMessage() . "\n";
        }
    }
    
    private function handleJoinRoom($clientId, $message) {
        $roomId = $message['room_id'] ?? null;
        $userId = $message['user_id'] ?? null;
        $username = $message['username'] ?? "用戶_{$clientId}";
        
        if (!$roomId || !$userId) {
            $this->sendWebSocketMessage($clientId, [
                'type' => 'error',
                'message' => '缺少房間ID或用戶ID'
            ]);
            return;
        }
        
        // 初始化房間
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [
                'users' => [],
                'code' => "# 歡迎來到 Python 協作學習平台\n# 開始編寫你的代碼吧！\n\nprint('Hello, World!')",
                'created_at' => time()
            ];
        }
        
        // 更新客戶端信息
        $this->clients[$clientId]['user_id'] = $userId;
        $this->clients[$clientId]['room_id'] = $roomId;
        $this->clients[$clientId]['username'] = $username;
        
        // 添加用戶到房間
        $this->rooms[$roomId]['users'][$userId] = [
            'client_id' => $clientId,
            'username' => $username,
            'joined_at' => time()
        ];
        
        echo "👤 用戶 {$username} 加入房間 {$roomId}\n";
        
        // 發送成功響應
        $this->sendWebSocketMessage($clientId, [
            'type' => 'room_joined',
            'room_id' => $roomId,
            'user_id' => $userId,
            'username' => $username,
            'code' => $this->rooms[$roomId]['code'],
            'users' => array_values($this->rooms[$roomId]['users'])
        ]);
        
        // 通知房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'users' => array_values($this->rooms[$roomId]['users'])
        ], $clientId);
    }
    
    private function handleLeaveRoom($clientId, $message) {
        $client = $this->clients[$clientId];
        $roomId = $client['room_id'];
        $userId = $client['user_id'];
        
        if ($roomId && isset($this->rooms[$roomId]['users'][$userId])) {
            unset($this->rooms[$roomId]['users'][$userId]);
            
            echo "👋 用戶 {$userId} 離開房間 {$roomId}\n";
            
            // 通知房間其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'user_left',
                'user_id' => $userId,
                'users' => array_values($this->rooms[$roomId]['users'])
            ]);
            
            // 如果房間空了，清理房間
            if (empty($this->rooms[$roomId]['users'])) {
                unset($this->rooms[$roomId]);
                echo "🗑️ 清理空房間: {$roomId}\n";
            }
        }
    }
    
    private function handleCodeChange($clientId, $message) {
        $client = $this->clients[$clientId];
        $roomId = $client['room_id'];
        
        if (!$roomId || !isset($this->rooms[$roomId])) {
            return;
        }
        
        $newCode = $message['code'] ?? '';
        $this->rooms[$roomId]['code'] = $newCode;
        
        // 廣播代碼變更到房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'code_updated',
            'code' => $newCode,
            'user_id' => $client['user_id'],
            'timestamp' => date('c')
        ], $clientId);
    }
    
    private function handleChatMessage($clientId, $message) {
        $client = $this->clients[$clientId];
        $roomId = $client['room_id'];
        
        if (!$roomId) {
            return;
        }
        
        $chatMessage = [
            'type' => 'chat_message',
            'user_id' => $client['user_id'],
            'username' => $client['username'],
            'message' => $message['message'] ?? '',
            'timestamp' => date('c')
        ];
        
        // 廣播聊天消息到房間所有用戶
        $this->broadcastToRoom($roomId, $chatMessage);
    }
    
    private function sendWebSocketMessage($clientId, $message) {
        if (!isset($this->clients[$clientId])) {
            return;
        }
        
        $client = $this->clients[$clientId];
        
        if ($client['type'] !== 'websocket') {
            return;
        }
        
        $payload = json_encode($message);
        $frame = $this->createWebSocketFrame($payload);
        
        socket_write($client['socket'], $frame);
    }
    
    private function sendWebSocketPong($clientId, $payload) {
        if (!isset($this->clients[$clientId])) {
            return;
        }
        
        $frame = $this->createWebSocketFrame($payload, 0xA); // Pong 幀
        socket_write($this->clients[$clientId]['socket'], $frame);
    }
    
    private function createWebSocketFrame($payload, $opcode = 0x1) {
        $payloadLength = strlen($payload);
        
        $frame = chr(0x80 | $opcode); // FIN = 1, opcode
        
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
    
    private function broadcastToRoom($roomId, $message, $excludeClientId = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        foreach ($this->rooms[$roomId]['users'] as $userId => $user) {
            $clientId = $user['client_id'];
            
            if ($clientId !== $excludeClientId && isset($this->clients[$clientId])) {
                $this->sendWebSocketMessage($clientId, $message);
            }
        }
    }
    
    private function findClientId($socket) {
        foreach ($this->clients as $clientId => $client) {
            if ($client['socket'] === $socket) {
                return $clientId;
            }
        }
        return null;
    }
    
    private function removeClient($clientId) {
        if (!isset($this->clients[$clientId])) {
            return;
        }
        
        $client = $this->clients[$clientId];
        
        // 如果用戶在房間中，處理離開房間
        if ($client['room_id']) {
            $this->handleLeaveRoom($clientId, []);
        }
        
        // 關閉 socket
        if (is_resource($client['socket'])) {
            socket_close($client['socket']);
        }
        
        unset($this->clients[$clientId]);
        
        echo "🗑️ 移除客戶端: {$clientId}\n";
    }
    
    private function cleanupConnections() {
        $currentTime = time();
        
        foreach ($this->clients as $clientId => $client) {
            // 清理超時連接 (5分鐘無活動)
            if ($currentTime - $client['last_activity'] > 300) {
                echo "⏰ 清理超時連接: {$clientId}\n";
                $this->removeClient($clientId);
            }
        }
    }
}

// 啟動整合服務器
new IntegratedServer();
?> 