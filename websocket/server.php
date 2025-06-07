<?php
/**
 * WebSocket 協作服務器 - 穩定版本 v1.2.2
 * 支援實時代碼協作、聊天、AI助教等功能
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Database.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class CodeCollaborationServer implements MessageComponentInterface {
    protected $clients;
    protected $rooms;
    protected $database;
    protected $roomCodeStates;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->roomCodeStates = [];
            
            // 初始化數據庫
        try {
            $this->database = new Database();
        } catch (Exception $e) {
            $this->database = null;
            }
            
        echo "🚀 WebSocket服務器啟動在 0.0.0.0:8081\n";
        echo "⏹️ 按 Ctrl+C 停止服務器\n\n";
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        
        // 初始化連接屬性
        $conn->roomId = null;
        $conn->userId = null;
        $conn->username = null;
        
        echo "新連接: {$conn->resourceId}\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                $this->sendError($from, '無效的消息格式');
                return;
            }
            
            switch ($data['type']) {
                case 'join_room':
                    $this->handleJoinRoom($from, $data);
                    break;
                    
                case 'leave_room':
                    $this->handleLeaveRoom($from, $data);
                    break;
                    
                case 'chat_message':
                    $this->handleChatMessage($from, $data);
                    break;
                    
                case 'code_change':
                    $this->handleCodeChange($from, $data);
                    break;
                    
                case 'save_code':
                    $this->handleSaveCode($from, $data);
                    break;
                    
                case 'load_code':
                    $this->handleLoadCode($from, $data);
                    break;
                    
                case 'ai_request':
                    $this->handleAIRequest($from, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($from, $data);
                    break;
                    
                case 'get_history':
                    $this->handleGetHistory($from, $data);
                    break;
                    
                default:
                    $this->sendError($from, "未知的消息類型: {$data['type']}");
            }
            
        } catch (Exception $e) {
            echo "處理消息錯誤: {$e->getMessage()}\n";
            $this->sendError($from, "服務器錯誤: " . $e->getMessage());
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        if ($conn->roomId) {
            $this->handleLeaveRoom($conn, ['room_id' => $conn->roomId]);
        }
        
        $this->clients->detach($conn);
        echo "連接關閉: {$conn->resourceId}\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "WebSocket錯誤: {$e->getMessage()}\n";
            $conn->close();
    }
    
    private function handleJoinRoom(ConnectionInterface $conn, $data) {
        $roomId = trim($data['room_id'] ?? '');
        $userId = trim($data['user_id'] ?? '');
        $username = trim($data['username'] ?? '');

        if (empty($roomId) || empty($userId) || empty($username)) {
            $this->sendError($conn, '缺少必要參數');
            return;
        }
        
        // 設置連接屬性
        $conn->roomId = $roomId;
        $conn->userId = $userId;
        $conn->username = $username;

        // 添加到房間
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [];
        }
        $this->rooms[$roomId][$conn->resourceId] = $conn;
        
        // 初始化房間代碼狀態
        if (!isset($this->roomCodeStates[$roomId])) {
            $this->roomCodeStates[$roomId] = [
                'current_code' => '# 歡迎使用 Python 協作學習平台\nprint("Hello, World!")\n\n# 在這裡開始你的 Python 學習之旅！',
                'last_update' => time()
            ];
        }
        
        // 獲取當前代碼
        $currentCode = $this->roomCodeStates[$roomId]['current_code'];
        
        // 如果有數據庫，嘗試載入代碼
        if ($this->database) {
            try {
                $codeResult = $this->database->loadCode($roomId);
                if ($codeResult && isset($codeResult['code'])) {
                    $currentCode = $codeResult['code'];
                }
            } catch (Exception $e) {
                // 忽略數據庫錯誤，使用預設代碼
            }
        }
        
        // 發送加入成功消息（包含用戶列表）
        $users = $this->getRoomUsers($roomId);
        $this->sendToConnection($conn, [
            'type' => 'room_joined',
            'room_id' => $roomId,
            'user_id' => $userId,
            'username' => $username,
            'message' => "成功加入房間 {$roomId}",
            'current_code' => $currentCode,
            'users' => $users,
            'timestamp' => date('c')
        ]);

        // 通知房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'message' => "{$username} 加入了房間",
            'timestamp' => date('c')
        ], $conn);
        
        // 廣播更新的用戶列表給所有房間用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'room_users',
            'users' => $users,
            'user_count' => count($users),
            'timestamp' => date('c')
        ]);
        
        echo "用戶 {$username} 加入房間 {$roomId}\n";
    }
    
    private function handleLeaveRoom(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId ?? $data['room_id'] ?? null;
        
        if (!$roomId || !isset($this->rooms[$roomId])) {
            return;
        }
        
        $userId = $conn->userId;
        $username = $conn->username;
        
        // 從房間移除
            unset($this->rooms[$roomId][$conn->resourceId]);
            
            // 如果房間為空，清理房間
            if (empty($this->rooms[$roomId])) {
                unset($this->rooms[$roomId]);
                    unset($this->roomCodeStates[$roomId]);
            } else {
            // 通知其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_left',
                'user_id' => $userId,
                'username' => $username,
                'message' => "{$username} 離開了房間",
                'timestamp' => date('c')
            ]);
            
            // 廣播更新的用戶列表
            $users = $this->getRoomUsers($roomId);
            $this->broadcastToRoom($roomId, [
                'type' => 'room_users',
                'users' => $users,
                'user_count' => count($users),
                'timestamp' => date('c')
            ]);
        }
        
        // 清理連接屬性
        $conn->roomId = null;
        $conn->userId = null;
        $conn->username = null;
        
        echo "用戶 {$username} 離開房間 {$roomId}\n";
    }
    
    private function handleChatMessage(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        $message = trim($data['message'] ?? '');
        
        if (!$roomId || empty($message)) {
            $this->sendError($conn, '無效的聊天消息');
            return;
        }
        
        // 廣播聊天消息
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'user_id' => $conn->userId,
            'username' => $conn->username,
            'message' => $message,
            'timestamp' => date('c')
        ]);
        
        echo "聊天: {$conn->username}: {$message}\n";
    }
    
    private function handleCodeChange(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        $code = $data['code'] ?? '';
        
        if (!$roomId) {
            $this->sendError($conn, '尚未加入房間');
            return;
        }
        
        // 更新房間代碼狀態
        if (isset($this->roomCodeStates[$roomId])) {
            $this->roomCodeStates[$roomId]['current_code'] = $code;
            $this->roomCodeStates[$roomId]['last_update'] = time();
        }
        
        // 廣播代碼變更
        $this->broadcastToRoom($roomId, [
            'type' => 'code_change',
            'user_id' => $conn->userId,
            'username' => $conn->username,
            'code' => $code,
            'timestamp' => date('c')
        ], $conn);
    }
    
    private function handleSaveCode(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        $code = $data['code'] ?? '';
        $title = $data['title'] ?? '手動保存 ' . date('Y/m/d H:i:s');
        
        if (!$roomId) {
            $this->sendError($conn, '尚未加入房間');
            return;
        }
        
        // 如果有數據庫，保存到數據庫
        if ($this->database) {
            try {
                $result = $this->database->saveCode($roomId, $conn->userId, $code, $title);
                if ($result['success']) {
                    $this->sendToConnection($conn, [
                        'type' => 'save_success',
                        'success' => true,
                        'message' => "代碼已保存: {$title}",
                        'timestamp' => date('c')
                    ]);
                } else {
                    $this->sendError($conn, '保存失敗: ' . $result['error']);
                }
            } catch (Exception $e) {
                $this->sendError($conn, '保存失敗: ' . $e->getMessage());
            }
        } else {
            // 沒有數據庫，只更新內存狀態
            if (isset($this->roomCodeStates[$roomId])) {
                $this->roomCodeStates[$roomId]['current_code'] = $code;
            }
            
        $this->sendToConnection($conn, [
                'type' => 'save_success',
                'success' => true,
                'message' => "代碼已保存到內存: {$title}",
                'timestamp' => date('c')
            ]);
        }
        
        echo "代碼保存: {$conn->username} 在房間 {$roomId}\n";
    }
    
    private function handleLoadCode(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        
        if (!$roomId) {
            $this->sendError($conn, '尚未加入房間');
            return;
        }
        
        $code = '# 歡迎使用 Python 協作學習平台\nprint("Hello, World!")';
        
        // 嘗試從數據庫載入
        if ($this->database) {
            try {
                $result = $this->database->loadCode($roomId);
                if ($result && isset($result['code'])) {
                    $code = $result['code'];
                }
            } catch (Exception $e) {
                // 忽略錯誤，使用預設代碼
            }
        } else if (isset($this->roomCodeStates[$roomId])) {
            // 從內存載入
            $code = $this->roomCodeStates[$roomId]['current_code'];
        }
        
                $this->sendToConnection($conn, [
            'type' => 'code_loaded',
            'success' => true,
            'code' => $code,
            'timestamp' => date('c')
        ]);
        
        echo "代碼載入: {$conn->username} 從房間 {$roomId}\n";
    }
    
    private function handleAIRequest(ConnectionInterface $conn, $data) {
        $action = $data['action'] ?? '';
        $code = $data['code'] ?? '';
        
        if (empty($action) || empty($code)) {
            $this->sendError($conn, '缺少AI請求參數');
            return;
        }
        
        // 檢查AI配置
        $aiConfigFile = __DIR__ . '/../ai_config.json';
        if (!file_exists($aiConfigFile)) {
        $this->sendToConnection($conn, [
                'type' => 'ai_response',
                'action' => $action,
                'success' => false,
                'error' => 'AI助教功能未啟用',
                'timestamp' => date('c')
            ]);
                return;
            }
            
        try {
            $aiConfig = json_decode(file_get_contents($aiConfigFile), true);
            
            if (!$aiConfig['enabled']) {
                throw new Exception('AI功能已停用');
            }
            
            // 調用AI API
            $response = $this->callOpenAI($aiConfig, $action, $code);
            
            $this->sendToConnection($conn, [
                'type' => 'ai_response',
                'action' => $action,
                'success' => true,
                'response' => $response,
                'timestamp' => date('c')
            ]);
            
        } catch (Exception $e) {
            $this->sendToConnection($conn, [
                'type' => 'ai_response',
                'action' => $action,
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ]);
        }
        
        echo "AI請求: {$conn->username} - {$action}\n";
    }
    
    private function callOpenAI($config, $action, $code) {
        $prompts = [
            'explain' => "請解釋以下Python代碼的功能和邏輯：\n\n{$code}",
            'check_errors' => "請檢查以下Python代碼是否有錯誤：\n\n{$code}",
            'suggest_improvements' => "請為以下Python代碼提供改進建議：\n\n{$code}",
            'answer_question' => "關於以下Python代碼，請回答問題：\n\n{$code}"
        ];
        
        $prompt = $prompts[$action] ?? $prompts['explain'];
        
        $data = [
            'model' => $config['model'],
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $config['max_tokens'],
            'temperature' => $config['temperature']
            ];
            
            $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['openai_api_key']
            ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $config['timeout'] / 1000);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
        if ($httpCode !== 200) {
                throw new Exception('AI API請求失敗');
            }
            
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['choices'][0]['message']['content'])) {
            throw new Exception('AI API響應格式錯誤');
        }
        
        return $result['choices'][0]['message']['content'];
    }
    
    private function handleGetHistory(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        
        if (!$roomId) {
            $this->sendError($conn, '尚未加入房間');
            return;
        }
        
        echo "歷史記錄請求: {$conn->username} 從房間 {$roomId}\n";
        
        // 模擬歷史記錄數據
        $history = [
            [
                'id' => 1,
                'title' => '範例代碼 1',
                'author' => '系統',
                'code' => 'print("Hello World")',
                'timestamp' => date('c', time() - 3600)
            ],
            [
                'id' => 2,
                'title' => '範例代碼 2',
                'author' => '系統',
                'code' => 'for i in range(10):\n    print(i)',
                'timestamp' => date('c', time() - 1800)
            ]
        ];
        
        $this->sendToConnection($conn, [
            'type' => 'history_loaded',
            'room_id' => $roomId,
            'history' => $history,
            'total' => count($history),
            'timestamp' => date('c')
        ]);
    }
    
    private function handlePing(ConnectionInterface $conn, $data) {
        $this->sendToConnection($conn, [
            'type' => 'pong',
            'timestamp' => date('c')
        ]);
    }
    
    private function broadcastToRoom($roomId, $message, $excludeConn = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        foreach ($this->rooms[$roomId] as $conn) {
            if ($excludeConn && $conn === $excludeConn) {
                continue;
            }
            $this->sendToConnection($conn, $message);
        }
    }
    
    private function sendToConnection(ConnectionInterface $conn, $message) {
        try {
            $conn->send(json_encode($message));
        } catch (Exception $e) {
            echo "發送消息失敗: {$e->getMessage()}\n";
        }
    }
    
    private function sendError(ConnectionInterface $conn, $message) {
        $this->sendToConnection($conn, [
            'type' => 'error',
            'message' => $message,
                'timestamp' => date('c')
        ]);
    }
    
    private function getRoomUsers($roomId) {
        $users = [];
        if (isset($this->rooms[$roomId])) {
            foreach ($this->rooms[$roomId] as $conn) {
                if ($conn->username) {
                    $users[] = [
                'user_id' => $conn->userId,
                        'username' => $conn->username
                    ];
                }
            }
        }
        return $users;
    }
}

// 啟動服務器
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new CodeCollaborationServer()
        )
    ),
    8081
);

$server->run(); 