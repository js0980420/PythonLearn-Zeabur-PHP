<?php
/**
 * 調試版本的 WebSocket 服務器
 * 用於診斷握手問題
 */

class DebugWebSocketServer {
    private $socket;
    private $clients = [];
    private $host = '0.0.0.0';
    private $port = 8081;
    
    public function __construct() {
        echo "🔧 啟動調試 WebSocket 服務器...\n";
        echo "📡 監聽地址: {$this->host}:{$this->port}\n";
        echo "🌐 連接地址: ws://localhost:{$this->port}\n";
        echo "🔍 調試模式: 詳細日誌\n\n";
        
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
                    $this->handleClientData($clientSocket);
                }
            }
            
            // 清理斷開的連接
            $this->cleanupClients();
        }
    }
    
    private function acceptNewConnection() {
        $clientSocket = @socket_accept($this->socket);
        
        if ($clientSocket === false) {
            $error = socket_last_error($this->socket);
            if ($error !== SOCKET_EWOULDBLOCK && $error !== 0) {
                echo "❌ 接受連接失敗: " . socket_strerror($error) . "\n";
            }
            return;
        }
        
        socket_set_nonblock($clientSocket);
        
        $clientId = uniqid('client_');
        $remoteAddress = '';
        @socket_getpeername($clientSocket, $remoteAddress);
        
        $this->clients[$clientId] = [
            'socket' => $clientSocket,
            'id' => $clientId,
            'address' => $remoteAddress,
            'buffer' => '',
            'handshake' => false,
            'connected_at' => time()
        ];
        
        echo "🔗 新連接: {$clientId} 來自 {$remoteAddress}\n";
    }
    
    private function handleClientData($clientSocket) {
        $client = $this->getClientBySocket($clientSocket);
        
        if (!$client) {
            echo "⚠️ 找不到對應的客戶端\n";
            return;
        }
        
        // 讀取數據
        $data = @socket_read($clientSocket, 4096, PHP_BINARY_READ);
        
        if ($data === false) {
            $error = socket_last_error($clientSocket);
            if ($error !== SOCKET_EWOULDBLOCK && $error !== 0) {
                echo "❌ 讀取數據失敗 ({$client['id']}): " . socket_strerror($error) . "\n";
                $this->closeClient($client['id']);
            }
            return;
        }
        
        if ($data === '') {
            echo "🔌 客戶端主動斷開: {$client['id']}\n";
            $this->closeClient($client['id']);
            return;
        }
        
        // 添加到緩衝區
        $this->clients[$client['id']]['buffer'] .= $data;
        
        echo "📨 收到數據 ({$client['id']}): " . strlen($data) . " 字節\n";
        echo "📋 數據內容: " . $this->formatData($data) . "\n";
        
        if (!$client['handshake']) {
            echo "🤝 嘗試 WebSocket 握手 ({$client['id']})\n";
            $this->performHandshake($client['id']);
        } else {
            echo "📦 處理 WebSocket 消息 ({$client['id']})\n";
            $this->processWebSocketMessage($client['id']);
        }
    }
    
    private function performHandshake($clientId) {
        $client = &$this->clients[$clientId];
        
        // 檢查是否有完整的 HTTP 請求
        if (strpos($client['buffer'], "\r\n\r\n") === false) {
            echo "⏳ 等待更多握手數據 ({$clientId})\n";
            return;
        }
        
        echo "🔍 開始解析握手請求 ({$clientId})\n";
        
        $request = $client['buffer'];
        $lines = explode("\r\n", $request);
        
        echo "📄 HTTP 請求行數: " . count($lines) . "\n";
        echo "📄 第一行: " . ($lines[0] ?? 'N/A') . "\n";
        
        // 解析請求頭
        $headers = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        echo "📋 解析到的請求頭:\n";
        foreach ($headers as $key => $value) {
            echo "   {$key}: {$value}\n";
        }
        
        // 驗證 WebSocket 握手
        $required = ['Sec-WebSocket-Key', 'Upgrade', 'Connection'];
        $missing = [];
        
        foreach ($required as $header) {
            if (!isset($headers[$header])) {
                $missing[] = $header;
            }
        }
        
        if (!empty($missing)) {
            echo "❌ 缺少必要的請求頭: " . implode(', ', $missing) . "\n";
            $this->closeClient($clientId);
            return;
        }
        
        // 檢查 Upgrade 頭
        if (strtolower($headers['Upgrade']) !== 'websocket') {
            echo "❌ 錯誤的 Upgrade 頭: {$headers['Upgrade']}\n";
            $this->closeClient($clientId);
            return;
        }
        
        // 檢查 Connection 頭
        if (strpos(strtolower($headers['Connection']), 'upgrade') === false) {
            echo "❌ 錯誤的 Connection 頭: {$headers['Connection']}\n";
            $this->closeClient($clientId);
            return;
        }
        
        // 生成響應
        $key = $headers['Sec-WebSocket-Key'];
        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";
        
        echo "📤 發送握手響應 ({$clientId}):\n";
        echo $this->formatData($response) . "\n";
        
        $result = @socket_write($client['socket'], $response);
        
        if ($result === false) {
            echo "❌ 握手響應發送失敗 ({$clientId}): " . socket_strerror(socket_last_error($client['socket'])) . "\n";
            $this->closeClient($clientId);
            return;
        }
        
        echo "✅ WebSocket 握手成功 ({$clientId})\n";
        $client['handshake'] = true;
        $client['buffer'] = '';
        
        // 發送歡迎消息
        $welcomeMessage = json_encode([
            'type' => 'welcome',
            'message' => 'WebSocket 連接成功！',
            'client_id' => $clientId,
            'timestamp' => time()
        ]);
        
        $this->sendWebSocketMessage($clientId, $welcomeMessage);
    }
    
    private function processWebSocketMessage($clientId) {
        $client = &$this->clients[$clientId];
        
        while (strlen($client['buffer']) >= 2) {
            $frame = $this->parseWebSocketFrame($client['buffer']);
            
            if ($frame === null) {
                echo "⏳ 需要更多數據來完成幀解析 ({$clientId})\n";
                break;
            }
            
            if ($frame === false) {
                echo "❌ WebSocket 幀解析失敗 ({$clientId})\n";
                $this->closeClient($clientId);
                return;
            }
            
            // 移除已處理的數據
            $client['buffer'] = substr($client['buffer'], $frame['frame_size']);
            
            echo "📦 收到 WebSocket 幀 ({$clientId}): opcode={$frame['opcode']}, payload=" . strlen($frame['payload']) . " 字節\n";
            
            if ($frame['opcode'] === 0x8) { // Close frame
                echo "🔌 收到關閉幀 ({$clientId})\n";
                $this->closeClient($clientId);
                return;
            } elseif ($frame['opcode'] === 0x1) { // Text frame
                echo "💬 收到文本消息 ({$clientId}): {$frame['payload']}\n";
                
                // 回應消息
                $response = json_encode([
                    'type' => 'echo',
                    'original_message' => $frame['payload'],
                    'timestamp' => time()
                ]);
                
                $this->sendWebSocketMessage($clientId, $response);
            }
        }
    }
    
    private function parseWebSocketFrame(&$buffer) {
        $bufferLength = strlen($buffer);
        
        if ($bufferLength < 2) {
            return null;
        }
        
        $firstByte = ord($buffer[0]);
        $secondByte = ord($buffer[1]);
        
        $fin = ($firstByte >> 7) & 1;
        $opcode = $firstByte & 0xf;
        $masked = ($secondByte >> 7) & 1;
        $payloadLength = $secondByte & 0x7f;
        
        $headerLength = 2;
        
        if ($payloadLength === 126) {
            if ($bufferLength < 4) return null;
            $payloadLength = unpack('n', substr($buffer, 2, 2))[1];
            $headerLength = 4;
        } elseif ($payloadLength === 127) {
            if ($bufferLength < 10) return null;
            $payloadLength = unpack('J', substr($buffer, 2, 8))[1];
            $headerLength = 10;
        }
        
        if ($masked) {
            $headerLength += 4;
        }
        
        if ($bufferLength < $headerLength + $payloadLength) {
            return null;
        }
        
        $payload = '';
        if ($payloadLength > 0) {
            $payload = substr($buffer, $headerLength, $payloadLength);
            
            if ($masked) {
                $maskingKey = substr($buffer, $headerLength - 4, 4);
                for ($i = 0; $i < $payloadLength; $i++) {
                    $payload[$i] = $payload[$i] ^ $maskingKey[$i % 4];
                }
            }
        }
        
        return [
            'fin' => $fin,
            'opcode' => $opcode,
            'payload' => $payload,
            'frame_size' => $headerLength + $payloadLength
        ];
    }
    
    private function sendWebSocketMessage($clientId, $message) {
        if (!isset($this->clients[$clientId])) {
            echo "⚠️ 客戶端不存在: {$clientId}\n";
            return false;
        }
        
        $client = $this->clients[$clientId];
        
        if (!$client['handshake']) {
            echo "⚠️ 客戶端尚未完成握手: {$clientId}\n";
            return false;
        }
        
        $frame = $this->createWebSocketFrame($message);
        $result = @socket_write($client['socket'], $frame);
        
        if ($result === false) {
            echo "❌ 發送消息失敗 ({$clientId}): " . socket_strerror(socket_last_error($client['socket'])) . "\n";
            $this->closeClient($clientId);
            return false;
        }
        
        echo "📤 發送消息 ({$clientId}): {$message}\n";
        return true;
    }
    
    private function createWebSocketFrame($data) {
        $length = strlen($data);
        $frame = '';
        
        // FIN (1) + RSV (3) + Opcode (4) = 0x81 for text frame
        $frame .= chr(0x81);
        
        if ($length < 126) {
            $frame .= chr($length);
        } elseif ($length < 65536) {
            $frame .= chr(126) . pack('n', $length);
        } else {
            $frame .= chr(127) . pack('J', $length);
        }
        
        $frame .= $data;
        return $frame;
    }
    
    private function getClientBySocket($socket) {
        foreach ($this->clients as $client) {
            if ($client['socket'] === $socket) {
                return $client;
            }
        }
        return null;
    }
    
    private function closeClient($clientId) {
        if (isset($this->clients[$clientId])) {
            $client = $this->clients[$clientId];
            
            if (is_resource($client['socket'])) {
                @socket_close($client['socket']);
            }
            
            unset($this->clients[$clientId]);
            echo "🔌 客戶端已斷開: {$clientId}\n";
        }
    }
    
    private function cleanupClients() {
        foreach ($this->clients as $clientId => $client) {
            if (!is_resource($client['socket'])) {
                echo "🧹 清理無效連接: {$clientId}\n";
                unset($this->clients[$clientId]);
            }
        }
    }
    
    private function formatData($data) {
        // 將不可見字符轉換為可見格式
        $formatted = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            $ord = ord($char);
            
            if ($ord >= 32 && $ord <= 126) {
                $formatted .= $char;
            } else {
                switch ($char) {
                    case "\r":
                        $formatted .= '\\r';
                        break;
                    case "\n":
                        $formatted .= '\\n';
                        break;
                    case "\t":
                        $formatted .= '\\t';
                        break;
                    default:
                        $formatted .= '\\x' . sprintf('%02X', $ord);
                }
            }
        }
        
        return $formatted;
    }
}

// 啟動調試服務器
try {
    $server = new DebugWebSocketServer();
    $server->run();
} catch (Exception $e) {
    echo "❌ 服務器啟動失敗: " . $e->getMessage() . "\n";
} 