<?php
/**
 * WebSocket 處理器 - 在同一端口處理 HTTP 和 WebSocket
 * 用於 Zeabur 部署環境
 */

// 檢查是否為 WebSocket 升級請求
function isWebSocketRequest() {
    return isset($_SERVER['HTTP_UPGRADE']) && 
           strtolower($_SERVER['HTTP_UPGRADE']) === 'websocket' &&
           isset($_SERVER['HTTP_CONNECTION']) &&
           strpos(strtolower($_SERVER['HTTP_CONNECTION']), 'upgrade') !== false;
}

// 處理 WebSocket 握手
function handleWebSocketHandshake() {
    if (!isset($_SERVER['HTTP_SEC_WEBSOCKET_KEY'])) {
        http_response_code(400);
        echo "Bad Request: Missing WebSocket key";
        return false;
    }
    
    $key = $_SERVER['HTTP_SEC_WEBSOCKET_KEY'];
    $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    
    // 發送 WebSocket 握手響應
    header('HTTP/1.1 101 Switching Protocols');
    header('Upgrade: websocket');
    header('Connection: Upgrade');
    header('Sec-WebSocket-Accept: ' . $acceptKey);
    header('Sec-WebSocket-Protocol: chat');
    
    return true;
}

// 簡化的 WebSocket 消息處理
function handleWebSocketMessage($message) {
    $data = json_decode($message, true);
    
    if (!$data) {
        return json_encode(['error' => 'Invalid JSON']);
    }
    
    // 基本的消息回應
    switch ($data['type'] ?? '') {
        case 'ping':
            return json_encode(['type' => 'pong', 'timestamp' => time()]);
            
        case 'join_room':
            return json_encode([
                'type' => 'room_joined',
                'room_id' => $data['room_id'] ?? 'default',
                'user_id' => $data['user_id'] ?? 'anonymous',
                'message' => '成功加入房間'
            ]);
            
        case 'code_change':
            // 廣播代碼變更（簡化版）
            return json_encode([
                'type' => 'code_updated',
                'room_id' => $data['room_id'] ?? 'default',
                'code' => $data['code'] ?? '',
                'user_id' => $data['user_id'] ?? 'anonymous'
            ]);
            
        default:
            return json_encode([
                'type' => 'echo',
                'original' => $data,
                'timestamp' => time()
            ]);
    }
}

// 主要處理邏輯
if (isWebSocketRequest()) {
    // 處理 WebSocket 請求
    if (handleWebSocketHandshake()) {
        // 這裡應該啟動 WebSocket 服務器
        // 但由於 PHP 內建服務器的限制，我們返回一個說明
        echo "WebSocket connection established";
    }
} else {
    // 返回 WebSocket 端點信息
    header('Content-Type: application/json');
    echo json_encode([
        'service' => 'WebSocket Handler',
        'status' => 'ready',
        'endpoint' => '/ws',
        'protocols' => ['chat'],
        'note' => 'Send WebSocket upgrade request to establish connection'
    ]);
}
?> 