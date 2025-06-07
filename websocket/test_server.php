<?php
/**
 * 極簡 WebSocket 測試服務器
 * 用於快速驗證連接功能
 */

echo "🚀 啟動極簡 WebSocket 測試服務器...\n";
echo "📍 地址: 127.0.0.1:8081\n";
echo "📅 時間: " . date('Y-m-d H:i:s') . "\n";
echo "🔧 PHP版本: " . PHP_VERSION . "\n";
echo "\n";

// 檢查 socket 擴展
if (!extension_loaded('sockets')) {
    die("❌ PHP sockets 擴展未安裝\n");
}

// 創建 socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$socket) {
    die("❌ 無法創建 socket: " . socket_strerror(socket_last_error()) . "\n");
}

// 設置 socket 選項
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

// 綁定地址和端口
if (!socket_bind($socket, '127.0.0.1', 8081)) {
    die("❌ 無法綁定地址: " . socket_strerror(socket_last_error()) . "\n");
}

// 開始監聽
if (!socket_listen($socket, 5)) {
    die("❌ 無法監聽: " . socket_strerror(socket_last_error()) . "\n");
}

echo "✅ WebSocket 服務器已啟動，等待連接...\n";
echo "🔗 測試地址: ws://127.0.0.1:8081\n";
echo "⏹️  按 Ctrl+C 停止服務器\n";
echo "\n";

$clients = [];
$rooms = [];

// 主循環
while (true) {
    $read = array_merge([$socket], $clients);
    $write = null;
    $except = null;
    
    if (socket_select($read, $write, $except, 0, 100000) < 1) {
        continue;
    }
    
    // 處理新連接
    if (in_array($socket, $read)) {
        $newClient = socket_accept($socket);
        if ($newClient) {
            echo "🔌 新連接建立 - " . date('H:i:s') . "\n";
            
            // 讀取 HTTP 握手請求
            $request = socket_read($newClient, 1024);
            if ($request && performHandshake($newClient, $request)) {
                $clients[] = $newClient;
                $clientId = array_search($newClient, $clients);
                echo "✅ 客戶端 {$clientId} 連接成功\n";
            } else {
                socket_close($newClient);
                echo "❌ 握手失敗\n";
            }
        }
        $key = array_search($socket, $read);
        unset($read[$key]);
    }
    
    // 處理客戶端消息
    foreach ($read as $client) {
        $data = socket_read($client, 1024);
        if (!$data) {
            removeClient($client, $clients, $rooms);
            continue;
        }
        
        $message = decodeFrame($data);
        if ($message !== false) {
            echo "📨 收到消息: {$message}\n";
            
            try {
                $messageData = json_decode($message, true);
                if ($messageData) {
                    processMessage($client, $messageData, $clients, $rooms);
                }
            } catch (Exception $e) {
                echo "❌ 處理消息錯誤: {$e->getMessage()}\n";
            }
        }
    }
}

function performHandshake($client, $request) {
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

function processMessage($client, $data, &$clients, &$rooms) {
    $type = $data['type'] ?? 'unknown';
    
    switch ($type) {
        case 'join_room':
            handleJoinRoom($client, $data, $clients, $rooms);
            break;
            
        case 'ping':
            sendToClient($client, ['type' => 'pong', 'timestamp' => time()]);
            break;
            
        default:
            echo "⚠️ 未知消息類型: {$type}\n";
    }
}

function handleJoinRoom($client, $data, &$clients, &$rooms) {
    $roomId = $data['room_id'] ?? '';
    $userId = $data['user_id'] ?? '';
    $username = $data['username'] ?? '';
    
    if (empty($roomId) || empty($userId) || empty($username)) {
        sendToClient($client, [
            'type' => 'error',
            'message' => '缺少必要參數'
        ]);
        return;
    }
    
    echo "👤 用戶 {$username} 加入房間 {$roomId}\n";
    
    // 發送成功響應
    sendToClient($client, [
        'type' => 'room_joined',
        'room_id' => $roomId,
        'user_id' => $userId,
        'username' => $username,
        'message' => "成功加入房間 {$roomId}",
        'timestamp' => date('c')
    ]);
    
    // 發送用戶列表
    sendToClient($client, [
        'type' => 'user_list_update',
        'users' => [
            [
                'user_id' => $userId,
                'username' => $username,
                'status' => 'active'
            ]
        ],
        'total_users' => 1,
        'timestamp' => date('c')
    ]);
}

function sendToClient($client, $message) {
    $frame = encodeFrame(json_encode($message));
    socket_write($client, $frame);
}

function removeClient($client, &$clients, &$rooms) {
    $clientId = array_search($client, $clients);
    if ($clientId !== false) {
        echo "🔌 客戶端 {$clientId} 斷開連接\n";
        unset($clients[$clientId]);
    }
    socket_close($client);
}

function decodeFrame($data) {
    if (strlen($data) < 2) return false;
    
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

function encodeFrame($message) {
    $length = strlen($message);
    
    if ($length <= 125) {
        return chr(129) . chr($length) . $message;
    } elseif ($length <= 65535) {
        return chr(129) . chr(126) . pack('n', $length) . $message;
    } else {
        return chr(129) . chr(127) . pack('J', $length) . $message;
    }
}
?> 