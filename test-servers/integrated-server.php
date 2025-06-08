<?php
/**
 * 整合服務器 - 在同一進程中處理 HTTP 和 WebSocket 請求
 * 專為 Zeabur 單端口部署設計
 */

class IntegratedServer {
    private $host = '0.0.0.0';
    private $port = 8080;
    private $socket;
    private $clients = [];
    private $rooms = [];
    
    public function __construct() {
        echo "🚀 啟動整合服務器 (HTTP + WebSocket)\n";
        echo "📡 監聽地址: {$this->host}:{$this->port}\n";
        echo "🌐 HTTP 服務: http://{$this->host}:{$this->port}\n";
        echo "🔌 WebSocket 服務: ws://{$this->host}:{$this->port}/ws\n";
        echo str_repeat("=", 50) . "\n";
        
        $this->createSocket();
        $this->run();
    }
    
    private function createSocket() {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        if (!$this->socket) {
            die("❌ 無法創建 socket\n");
        }
        
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            die("❌ 無法綁定到 {$this->host}:{$this->port}\n");
        }
        
        if (!socket_listen($this->socket, 5)) {
            die("❌ 無法監聽端口 {$this->port}\n");
        }
        
        echo "✅ 服務器已啟動並監聽 {$this->host}:{$this->port}\n";
    }
    
    private function run() {
        while (true) {
            $read = [$this->socket];
            $write = null;
            $except = null;
            
            // 添加所有客戶端連接到讀取列表
            foreach ($this->clients as $client) {
                $read[] = $client['socket'];
            }
            
            $ready = socket_select($read, $write, $except, 1);
            
            if ($ready === false) {
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
            return;
        }
        
        $clientId = uniqid();
        $this->clients[$clientId] = [
            'socket' => $clientSocket,
            'handshake' => false,
            'type' => 'unknown',
            'buffer' => '',
            'user_id' => null,
            'room_id' => null
        ];
        
        echo "🔗 新連接: {$clientId}\n";
    }
    
    private function handleClientMessage($clientSocket) {
        $clientId = $this->findClientId($clientSocket);
        if (!$clientId) {
            return;
        }
        
        $client = &$this->clients[$clientId];
        $data = socket_read($clientSocket, 2048);
        
        if ($data === false || $data === '') {
            $this->removeClient($clientId);
            return;
        }
        
        $client['buffer'] .= $data;
        
        if (!$client['handshake']) {
            $this->handleHandshake($clientId);
        } else if ($client['type'] === 'websocket') {
            $this->handleWebSocketMessage($clientId);
        }
    }
    
    private function handleHandshake($clientId) {
        $client = &$this->clients[$clientId];
        
        if (strpos($client['buffer'], "\r\n\r\n") === false) {
            return; // 等待完整的 HTTP 頭
        }
        
        $lines = explode("\r\n", $client['buffer']);
        $requestLine = $lines[0];
        
        // 解析請求
        if (preg_match('/^GET\s+(\/\S*)\s+HTTP\/1\.1/', $requestLine, $matches)) {
            $path = $matches[1];
            
            // 檢查是否為 WebSocket 升級請求
            $isWebSocket = false;
            $wsKey = null;
            
            foreach ($lines as $line) {
                if (stripos($line, 'Upgrade: websocket') !== false) {
                    $isWebSocket = true;
                } elseif (preg_match('/Sec-WebSocket-Key:\s*(.+)/', $line, $keyMatches)) {
                    $wsKey = trim($keyMatches[1]);
                }
            }
            
            if ($isWebSocket && $wsKey && $path === '/ws') {
                $this->handleWebSocketHandshake($clientId, $wsKey);
            } else {
                $this->handleHttpRequest($clientId, $path);
            }
        }
        
        $client['handshake'] = true;
        $client['buffer'] = '';
    }
    
    private function handleWebSocketHandshake($clientId, $wsKey) {
        $client = &$this->clients[$clientId];
        $client['type'] = 'websocket';
        
        $acceptKey = base64_encode(sha1($wsKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        
        $response = "HTTP/1.1 101 Switching Protocols\r\n";
        $response .= "Upgrade: websocket\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Sec-WebSocket-Accept: {$acceptKey}\r\n";
        $response .= "\r\n";
        
        socket_write($client['socket'], $response);
        
        echo "🔌 WebSocket 連接已建立: {$clientId}\n";
        
        // 發送歡迎消息
        $this->sendWebSocketMessage($clientId, [
            'type' => 'connection_established',
            'message' => '歡迎連接到 Python 協作學習平台',
            'client_id' => $clientId,
            'timestamp' => date('c')
        ]);
    }
    
    private function handleHttpRequest($clientId, $path) {
        $client = &$this->clients[$clientId];
        $client['type'] = 'http';
        
        echo "📄 HTTP 請求: {$path}\n";
        
        // 處理根路徑
        if ($path === '/' || $path === '') {
            $path = '/index.html';
        }
        
        // 構建文件路徑
        $filePath = __DIR__ . '/../public' . $path;
        
        // 檢查文件是否存在
        if (file_exists($filePath) && is_file($filePath)) {
            // 獲取文件內容
            $content = file_get_contents($filePath);
            $fileSize = strlen($content);
            
            // 確定 MIME 類型
            $mimeType = $this->getMimeType($path);
            
            // 構建 HTTP 響應
            $response = "HTTP/1.1 200 OK\r\n";
            $response .= "Content-Type: {$mimeType}\r\n";
            $response .= "Content-Length: {$fileSize}\r\n";
            $response .= "Access-Control-Allow-Origin: *\r\n";
            $response .= "Cache-Control: no-cache\r\n";
            $response .= "\r\n";
            $response .= $content;
            
            socket_write($client['socket'], $response);
            echo "✅ 服務文件: {$path} ({$fileSize} bytes, {$mimeType})\n";
        } else {
            // 文件不存在，返回 API 響應或 404
            if (strpos($path, '/api') === 0) {
                // API 請求
                $response = "HTTP/1.1 200 OK\r\n";
                $response .= "Content-Type: application/json\r\n";
                $response .= "Access-Control-Allow-Origin: *\r\n";
                $response .= "\r\n";
                
                $data = [
                    'service' => 'Python 協作學習平台',
                    'path' => $path,
                    'websocket_endpoint' => '/ws',
                    'status' => 'running',
                    'timestamp' => date('c')
                ];
                
                $response .= json_encode($data, JSON_UNESCAPED_UNICODE);
                socket_write($client['socket'], $response);
                echo "📡 API 響應: {$path}\n";
            } else {
                // 404 錯誤
                $response = "HTTP/1.1 404 Not Found\r\n";
                $response .= "Content-Type: text/html\r\n";
                $response .= "Access-Control-Allow-Origin: *\r\n";
                $response .= "\r\n";
                $response .= "<h1>404 - 文件未找到</h1><p>請求的文件 {$path} 不存在</p>";
                
                socket_write($client['socket'], $response);
                echo "❌ 404: {$path}\n";
            }
        }
        
        $this->removeClient($clientId);
    }
    
    private function getMimeType($path) {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'html' => 'text/html; charset=utf-8',
            'htm' => 'text/html; charset=utf-8',
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
            'php' => 'application/x-httpd-php'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    private function handleWebSocketMessage($clientId) {
        $client = &$this->clients[$clientId];
        
        // 簡化的 WebSocket 幀解析
        if (strlen($client['buffer']) < 2) {
            return;
        }
        
        $firstByte = ord($client['buffer'][0]);
        $secondByte = ord($client['buffer'][1]);
        
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
            return; // 等待完整的幀
        }
        
        $payload = substr($client['buffer'], $headerLength, $payloadLength);
        
        if ($masked) {
            $mask = substr($client['buffer'], $headerLength - 4, 4);
            for ($i = 0; $i < $payloadLength; $i++) {
                $payload[$i] = $payload[$i] ^ $mask[$i % 4];
            }
        }
        
        // 處理消息
        if ($opcode === 0x1) { // 文本幀
            $this->processWebSocketMessage($clientId, $payload);
        } elseif ($opcode === 0x8) { // 關閉幀
            $this->removeClient($clientId);
        }
        
        // 移除已處理的數據
        $client['buffer'] = substr($client['buffer'], $headerLength + $payloadLength);
    }
    
    private function processWebSocketMessage($clientId, $payload) {
        $data = json_decode($payload, true);
        
        if (!$data) {
            echo "⚠️ 無效的 JSON 消息: {$payload}\n";
            return;
        }
        
        echo "📨 收到消息: " . ($data['type'] ?? 'unknown') . "\n";
        
        $client = &$this->clients[$clientId];
        
        switch ($data['type'] ?? '') {
            case 'join_room':
                $this->handleJoinRoom($clientId, $data);
                break;
                
            case 'code_change':
                $this->handleCodeChange($clientId, $data);
                break;
                
            case 'chat_message':
                $this->handleChatMessage($clientId, $data);
                break;
                
            case 'ping':
                $this->sendWebSocketMessage($clientId, ['type' => 'pong', 'timestamp' => time()]);
                break;
                
            default:
                $this->sendWebSocketMessage($clientId, [
                    'type' => 'echo',
                    'original' => $data,
                    'timestamp' => time()
                ]);
        }
    }
    
    private function handleJoinRoom($clientId, $data) {
        $client = &$this->clients[$clientId];
        $roomId = $data['room_id'] ?? 'default';
        $userId = $data['user_id'] ?? 'anonymous';
        
        $client['room_id'] = $roomId;
        $client['user_id'] = $userId;
        
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [];
        }
        
        $this->rooms[$roomId][$clientId] = $userId;
        
        $this->sendWebSocketMessage($clientId, [
            'type' => 'room_joined',
            'room_id' => $roomId,
            'user_id' => $userId,
            'message' => '成功加入房間'
        ]);
        
        // 通知房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'room_id' => $roomId,
            'user_id' => $userId,
            'message' => "{$userId} 加入了房間"
        ], $clientId);
        
        echo "👤 用戶 {$userId} 加入房間 {$roomId}\n";
    }
    
    private function handleCodeChange($clientId, $data) {
        $client = $this->clients[$clientId];
        $roomId = $client['room_id'] ?? null;
        
        if (!$roomId) {
            return;
        }
        
        // 廣播代碼變更到房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'code_change',
            'room_id' => $roomId,
            'user_id' => $client['user_id'],
            'code' => $data['code'] ?? '',
            'change' => $data['change'] ?? null,
            'timestamp' => time()
        ], $clientId);
    }
    
    private function handleChatMessage($clientId, $data) {
        $client = $this->clients[$clientId];
        $roomId = $client['room_id'] ?? null;
        
        if (!$roomId) {
            return;
        }
        
        // 廣播聊天消息到房間所有用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'room_id' => $roomId,
            'user_id' => $client['user_id'],
            'username' => $client['user_id'],
            'message' => $data['message'] ?? '',
            'timestamp' => time()
        ]);
    }
    
    private function sendWebSocketMessage($clientId, $data) {
        if (!isset($this->clients[$clientId])) {
            return;
        }
        
        $client = $this->clients[$clientId];
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
        $frame = $this->createWebSocketFrame($payload);
        
        socket_write($client['socket'], $frame);
    }
    
    private function broadcastToRoom($roomId, $data, $excludeClientId = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        foreach ($this->rooms[$roomId] as $clientId => $userId) {
            if ($clientId !== $excludeClientId) {
                $this->sendWebSocketMessage($clientId, $data);
            }
        }
    }
    
    private function createWebSocketFrame($payload) {
        $length = strlen($payload);
        $frame = chr(0x81); // FIN + 文本幀
        
        if ($length < 126) {
            $frame .= chr($length);
        } elseif ($length < 65536) {
            $frame .= chr(126) . pack('n', $length);
        } else {
            $frame .= chr(127) . pack('J', $length);
        }
        
        $frame .= $payload;
        return $frame;
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
        
        // 從房間中移除
        if ($client['room_id'] && isset($this->rooms[$client['room_id']][$clientId])) {
            unset($this->rooms[$client['room_id']][$clientId]);
            
            // 通知房間其他用戶
            $this->broadcastToRoom($client['room_id'], [
                'type' => 'user_left',
                'room_id' => $client['room_id'],
                'user_id' => $client['user_id'],
                'message' => "{$client['user_id']} 離開了房間"
            ]);
        }
        
        socket_close($client['socket']);
        unset($this->clients[$clientId]);
        
        echo "🔌 連接已關閉: {$clientId}\n";
    }
    
    private function cleanupConnections() {
        foreach ($this->clients as $clientId => $client) {
            if (!is_resource($client['socket'])) {
                $this->removeClient($clientId);
            }
        }
    }
}

// 啟動整合服務器
new IntegratedServer();
?> 