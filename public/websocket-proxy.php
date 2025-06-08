<?php
/**
 * WebSocket 代理 - 將 HTTP WebSocket 升級請求轉發到實際的 WebSocket 服務器
 * 用於 Zeabur 單端口部署環境
 */

// 檢查是否為 WebSocket 升級請求
function isWebSocketUpgrade() {
    return isset($_SERVER['HTTP_UPGRADE']) && 
           strtolower($_SERVER['HTTP_UPGRADE']) === 'websocket' &&
           isset($_SERVER['HTTP_CONNECTION']) &&
           strpos(strtolower($_SERVER['HTTP_CONNECTION']), 'upgrade') !== false &&
           isset($_SERVER['HTTP_SEC_WEBSOCKET_KEY']);
}

// 代理 WebSocket 連接到內部服務器
function proxyWebSocketConnection() {
    $wsHost = '127.0.0.1';
    $wsPort = 8081;
    
    // 檢查內部 WebSocket 服務器是否可用
    $socket = @fsockopen($wsHost, $wsPort, $errno, $errstr, 1);
    if (!$socket) {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'WebSocket 服務器不可用',
            'details' => "無法連接到 {$wsHost}:{$wsPort}",
            'errno' => $errno,
            'errstr' => $errstr
        ]);
        return;
    }
    fclose($socket);
    
    // 構建 WebSocket 握手請求
    $key = $_SERVER['HTTP_SEC_WEBSOCKET_KEY'];
    $host = $_SERVER['HTTP_HOST'];
    
    $request = "GET /ws HTTP/1.1\r\n";
    $request .= "Host: {$host}\r\n";
    $request .= "Upgrade: websocket\r\n";
    $request .= "Connection: Upgrade\r\n";
    $request .= "Sec-WebSocket-Key: {$key}\r\n";
    $request .= "Sec-WebSocket-Version: 13\r\n";
    $request .= "\r\n";
    
    // 連接到內部 WebSocket 服務器
    $wsSocket = fsockopen($wsHost, $wsPort, $errno, $errstr, 5);
    if (!$wsSocket) {
        http_response_code(502);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'WebSocket 代理失敗',
            'details' => "無法建立代理連接: {$errstr}"
        ]);
        return;
    }
    
    // 發送握手請求到內部服務器
    fwrite($wsSocket, $request);
    
    // 讀取握手響應
    $response = '';
    while (($line = fgets($wsSocket)) !== false) {
        $response .= $line;
        if (trim($line) === '') {
            break; // 空行表示頭部結束
        }
    }
    
    // 檢查握手是否成功
    if (strpos($response, '101 Switching Protocols') === false) {
        http_response_code(502);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'WebSocket 握手失敗',
            'response' => substr($response, 0, 500)
        ]);
        fclose($wsSocket);
        return;
    }
    
    // 轉發握手響應到客戶端
    $lines = explode("\r\n", $response);
    foreach ($lines as $line) {
        if (trim($line) !== '') {
            header($line);
        }
    }
    
    // 開始代理數據
    // 注意：這在 PHP 內建服務器中有限制，但我們嘗試基本的代理
    
    // 設置非阻塞模式
    stream_set_blocking($wsSocket, false);
    
    // 簡單的雙向代理（有限制）
    $clientInput = fopen('php://input', 'r');
    
    // 代理客戶端數據到 WebSocket 服務器
    while (!feof($clientInput)) {
        $data = fread($clientInput, 1024);
        if ($data) {
            fwrite($wsSocket, $data);
        }
        
        // 代理 WebSocket 服務器數據到客戶端
        $wsData = fread($wsSocket, 1024);
        if ($wsData) {
            echo $wsData;
            flush();
        }
        
        usleep(10000); // 10ms 延遲
    }
    
    fclose($clientInput);
    fclose($wsSocket);
}

// 主要處理邏輯
if (isWebSocketUpgrade()) {
    // 處理 WebSocket 升級請求
    proxyWebSocketConnection();
} else {
    // 返回 WebSocket 端點信息
    header('Content-Type: application/json');
    echo json_encode([
        'service' => 'WebSocket Proxy',
        'status' => 'ready',
        'endpoint' => '/ws',
        'internal_server' => '127.0.0.1:8081',
        'note' => 'Send WebSocket upgrade request to establish connection',
        'timestamp' => date('c')
    ]);
}
?> 