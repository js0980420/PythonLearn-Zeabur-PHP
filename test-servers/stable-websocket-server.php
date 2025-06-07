<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class StableWebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $rooms;
    protected $roomCodeStates;
    protected $teachers; // 新增：教師連接列表
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->roomCodeStates = [];
        $this->teachers = []; // 初始化教師列表
        
        // 創建日誌目錄
        $logDir = __DIR__ . '/../test-logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        echo "🚀 穩定版WebSocket測試服務器啟動\n";
        echo "📡 端口: 8081\n";
        echo "⏰ 啟動時間: " . date('Y-m-d H:i:s') . "\n";
        echo "✅ 基於成功配置 v1.2.2\n";
        echo "🔗 測試地址: ws://localhost:8081\n";
        echo "📝 日誌: test-logs/stable_websocket.log\n";
        echo "👨‍🏫 支援教師監控功能\n";
        echo str_repeat("=", 51) . "\n";
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        
        // 生成測試ID
        $conn->testId = 'stable_' . uniqid();
        $conn->connectedAt = time();
        $conn->isTeacher = false; // 預設不是教師
        
        $this->log("✅ 新連接建立: {$conn->testId}");
        
        // 發送歡迎消息
        $this->sendToConnection($conn, [
            'type' => 'connection_established',
            'message' => '歡迎連接到穩定版WebSocket測試服務器',
            'test_id' => $conn->testId,
            'server_version' => 'v1.2.2-stable',
            'timestamp' => date('c')
        ]);
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $this->log("📨 收到消息 from {$from->testId}");
        
        try {
            $data = json_decode($msg, true);
            if (!$data) {
                $this->sendError($from, '無效的JSON格式');
                return;
            }
            
            $type = $data['type'] ?? '';
            
            switch ($type) {
                case 'teacher_monitor':
                    $this->handleTeacherMonitor($from, $data);
                    break;
                    
                case 'join_room':
                    $this->handleJoinRoom($from, $data);
                    break;
                    
                case 'leave_room':
                    $this->handleLeaveRoom($from);
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
                    
                case 'chat_message':
                    $this->handleChatMessage($from, $data);
                    break;
                    
                case 'teacher_chat':
                    $this->handleTeacherChat($from, $data);
                    break;
                    
                case 'teacher_broadcast':
                    $this->handleTeacherBroadcast($from, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($from);
                    break;
                    
                case 'get_history':
                    $this->handleGetHistory($from);
                    break;
                    
                case 'ai_request':
                    $this->handleAIRequest($from, $data);
                    break;
                    
                default:
                    $this->sendError($from, "未知的消息類型: {$type}");
            }
            
        } catch (Exception $e) {
            $this->sendError($from, '消息處理錯誤: ' . $e->getMessage());
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        $this->log("👋 連接關閉: {$conn->testId}");
        
        // 如果是教師，從教師列表中移除
        if ($conn->isTeacher) {
            $this->removeTeacher($conn);
        }
        
        // 移除用戶
        if (isset($conn->roomId)) {
            $this->removeUserFromRoom($conn, $conn->roomId);
        }
        
        $this->clients->detach($conn);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->log("❌ 連接錯誤: " . $e->getMessage());
        $conn->close();
    }
    
    // 新增：處理教師監控註冊
    private function handleTeacherMonitor(ConnectionInterface $conn, $data) {
        $action = $data['data']['action'] ?? '';
        
        if ($action === 'register') {
            // 註冊為教師
            $conn->isTeacher = true;
            $conn->teacherId = 'teacher_' . uniqid();
            $this->teachers[] = $conn;
            
            $this->log("👨‍🏫 教師註冊: {$conn->teacherId}");
            
            // 發送歡迎消息
            $this->sendToConnection($conn, [
                'type' => 'welcome',
                'message' => '教師監控已啟動',
                'userId' => $conn->teacherId,
                'timestamp' => date('c')
            ]);
            
            // 發送當前統計信息
            $this->sendTeacherStats($conn);
            
            // 發送所有房間信息
            $this->sendAllRoomsToTeacher($conn);
        }
    }
    
    // 新增：處理教師聊天
    private function handleTeacherChat(ConnectionInterface $conn, $data) {
        if (!$conn->isTeacher) {
            $this->sendError($conn, '只有教師可以發送教師聊天消息');
            return;
        }
        
        $targetRoom = $data['data']['targetRoom'] ?? '';
        $message = $data['data']['message'] ?? '';
        $teacherName = $data['data']['teacherName'] ?? '教師';
        
        if (!$message) {
            $this->sendError($conn, '消息內容不能為空');
            return;
        }
        
        $chatData = [
            'type' => 'teacher_message',
            'username' => $teacherName,
            'message' => $message,
            'room' => $targetRoom,
            'timestamp' => date('c'),
            'isTeacher' => true
        ];
        
        if ($targetRoom === 'all') {
            // 廣播到所有房間
            foreach ($this->rooms as $roomId => $connections) {
                $this->broadcastToRoom($roomId, $chatData);
            }
            $this->log("👨‍🏫 教師向所有房間廣播: {$message}");
        } else {
            // 發送到特定房間
            $this->broadcastToRoom($targetRoom, $chatData);
            $this->log("👨‍🏫 教師向房間 {$targetRoom} 發送: {$message}");
        }
    }
    
    // 新增：處理教師廣播
    private function handleTeacherBroadcast(ConnectionInterface $conn, $data) {
        if (!$conn->isTeacher) {
            $this->sendError($conn, '只有教師可以發送廣播消息');
            return;
        }
        
        $targetRoom = $data['data']['targetRoom'] ?? '';
        $message = $data['data']['message'] ?? '';
        $messageType = $data['data']['messageType'] ?? 'info';
        
        if (!$message) {
            $this->sendError($conn, '廣播內容不能為空');
            return;
        }
        
        $broadcastData = [
            'type' => 'teacher_broadcast',
            'message' => $message,
            'messageType' => $messageType,
            'room' => $targetRoom,
            'timestamp' => date('c')
        ];
        
        if ($targetRoom === 'all') {
            // 廣播到所有房間
            foreach ($this->rooms as $roomId => $connections) {
                $this->broadcastToRoom($roomId, $broadcastData);
            }
            $this->log("📢 教師廣播到所有房間: {$message}");
        } else {
            // 廣播到特定房間
            $this->broadcastToRoom($targetRoom, $broadcastData);
            $this->log("📢 教師廣播到房間 {$targetRoom}: {$message}");
        }
    }
    
    // 新增：移除教師
    private function removeTeacher(ConnectionInterface $conn) {
        $this->teachers = array_filter($this->teachers, function($teacher) use ($conn) {
            return $teacher !== $conn;
        });
        $this->log("👨‍🏫 教師離線: {$conn->teacherId}");
    }
    
    // 新增：發送統計信息給教師
    private function sendTeacherStats(ConnectionInterface $teacher) {
        $activeRooms = count($this->rooms);
        $onlineStudents = 0;
        
        foreach ($this->rooms as $connections) {
            $onlineStudents += count($connections);
        }
        
        $this->sendToConnection($teacher, [
            'type' => 'stats_update',
            'data' => [
                'activeRooms' => $activeRooms,
                'onlineStudents' => $onlineStudents,
                'editCount' => 0 // 可以後續實現
            ],
            'timestamp' => date('c')
        ]);
    }
    
    // 新增：發送所有房間信息給教師
    private function sendAllRoomsToTeacher(ConnectionInterface $teacher) {
        foreach ($this->rooms as $roomId => $connections) {
            $users = $this->getRoomUsers($roomId);
            $code = $this->roomCodeStates[$roomId]['current_code'] ?? '';
            
            $this->sendToConnection($teacher, [
                'type' => 'room_update',
                'data' => [
                    'roomName' => $roomId,
                    'users' => $users,
                    'code' => $code,
                    'version' => $this->roomCodeStates[$roomId]['version'] ?? 1,
                    'lastActivity' => $this->roomCodeStates[$roomId]['last_updated'] ?? time(),
                    'lastEditedBy' => $this->roomCodeStates[$roomId]['last_user'] ?? ''
                ],
                'timestamp' => date('c')
            ]);
        }
    }
    
    // 新增：通知所有教師房間更新
    private function notifyTeachersRoomUpdate($roomId, $users, $code, $lastEditedBy = '') {
        $updateData = [
            'type' => 'room_update',
            'data' => [
                'roomName' => $roomId,
                'users' => $users,
                'code' => $code,
                'version' => $this->roomCodeStates[$roomId]['version'] ?? 1,
                'lastActivity' => time(),
                'lastEditedBy' => $lastEditedBy
            ],
            'timestamp' => date('c')
        ];
        
        foreach ($this->teachers as $teacher) {
            $this->sendToConnection($teacher, $updateData);
        }
    }
    
    // 新增：通知所有教師代碼變更
    private function notifyTeachersCodeChange($roomId, $userId, $username, $code) {
        $changeData = [
            'type' => 'code_change',
            'data' => [
                'roomName' => $roomId,
                'userId' => $userId,
                'userName' => $username,
                'code' => $code,
                'timestamp' => date('c')
            ]
        ];
        
        foreach ($this->teachers as $teacher) {
            $this->sendToConnection($teacher, $changeData);
        }
    }
    
    private function handleJoinRoom(ConnectionInterface $conn, $data) {
        $roomId = $data['room_id'] ?? 'default';
        $userId = $data['user_id'] ?? 'user_' . uniqid();
        $username = $data['username'] ?? $userId;
        
        // 設置連接屬性
        $conn->roomId = $roomId;
        $conn->userId = $userId;
        $conn->username = $username;
        
        // 初始化房間
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [];
            $this->roomCodeStates[$roomId] = [
                'current_code' => "# 歡迎使用Python多人協作平台\n# 房間: {$roomId}\n\nprint('Hello, World!')\n",
                'last_updated' => time(),
                'last_user' => $username,
                'version' => 1
            ];
        }
        
        // 添加到房間
        $this->rooms[$roomId][] = $conn;
        
        $currentCode = $this->roomCodeStates[$roomId]['current_code'];
        
        $this->log("👤 用戶 {$username} 成功加入房間 {$roomId}");
        
        // 獲取用戶列表
        $users = $this->getRoomUsers($roomId);
        
        // 發送加入成功消息（包含用戶列表）
        $this->sendToConnection($conn, [
            'type' => 'room_joined',
            'room_id' => $roomId,
            'user_id' => $userId,
            'username' => $username,
            'message' => "成功加入房間 {$roomId}",
            'current_code' => $currentCode,
            'users' => $users,
            'user_count' => count($users),
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
        
        // 廣播更新的用戶列表
        $this->broadcastToRoom($roomId, [
            'type' => 'room_users',
            'users' => $users,
            'user_count' => count($users),
            'timestamp' => date('c')
        ]);
        
        // 通知所有教師房間更新
        $this->notifyTeachersRoomUpdate($roomId, $users, $currentCode, $username);
        
        // 更新教師統計信息
        foreach ($this->teachers as $teacher) {
            $this->sendTeacherStats($teacher);
        }
    }
    
    private function handleLeaveRoom(ConnectionInterface $conn) {
        if (!isset($conn->roomId)) {
            $this->sendError($conn, '您當前不在任何房間中');
            return;
        }
        
        $roomId = $conn->roomId;
        $this->removeUserFromRoom($conn, $roomId);
        
        $this->sendToConnection($conn, [
            'type' => 'room_left',
            'room_id' => $roomId,
            'message' => "已離開房間 {$roomId}",
            'timestamp' => date('c')
        ]);
    }
    
    private function handleCodeChange(ConnectionInterface $conn, $data) {
        if (!isset($conn->roomId)) {
            $this->sendError($conn, '請先加入房間');
            return;
        }
        
        $code = $data['code'] ?? '';
        $roomId = $conn->roomId;
        $username = $conn->username ?? '匿名用戶';
        
        // 更新房間代碼狀態
        $currentVersion = $this->roomCodeStates[$roomId]['version'] ?? 1;
        $this->roomCodeStates[$roomId] = [
            'current_code' => $code,
            'last_updated' => time(),
            'last_user' => $username,
            'version' => $currentVersion + 1
        ];
        
        $this->log("📝 代碼變更 from {$username}: " . strlen($code) . " 字符");
        
        // 廣播代碼變更
        $this->broadcastToRoom($roomId, [
            'type' => 'code_sync',
            'code' => $code,
            'user_id' => $conn->userId,
            'username' => $username,
            'timestamp' => date('c')
        ], $conn);
        
        // 通知所有教師代碼變更
        $this->notifyTeachersCodeChange($roomId, $conn->userId, $username, $code);
        
        // 通知所有教師房間更新（包含最新代碼）
        $users = $this->getRoomUsers($roomId);
        $this->notifyTeachersRoomUpdate($roomId, $users, $code, $username);
    }
    
    private function handleSaveCode(ConnectionInterface $conn, $data) {
        if (!isset($conn->roomId)) {
            $this->sendError($conn, '請先加入房間');
            return;
        }
        
        $this->sendToConnection($conn, [
            'type' => 'save_success',
            'message' => '代碼已保存',
            'timestamp' => date('c')
        ]);
    }
    
    private function handleLoadCode(ConnectionInterface $conn, $data) {
        if (!isset($conn->roomId)) {
            $this->sendError($conn, '請先加入房間');
            return;
        }
        
        $roomId = $conn->roomId;
        $currentCode = $this->roomCodeStates[$roomId]['current_code'] ?? '';
        
        $this->sendToConnection($conn, [
            'type' => 'code_loaded',
            'code' => $currentCode,
            'timestamp' => date('c')
        ]);
    }
    
    private function handleChatMessage(ConnectionInterface $conn, $data) {
        if (!isset($conn->roomId)) {
            $this->sendError($conn, '請先加入房間');
            return;
        }
        
        $message = $data['message'] ?? '';
        $username = $conn->username ?? '匿名用戶';
        $roomId = $conn->roomId;
        
        $this->log("💬 聊天消息 from {$username}: {$message}");
        
        // 廣播聊天消息
        $this->broadcastToRoom($roomId, [
            'type' => 'chat_message',
            'user_id' => $conn->userId,
            'username' => $username,
            'message' => $message,
            'timestamp' => date('c')
        ]);
    }
    
    private function handlePing(ConnectionInterface $conn) {
        $this->sendToConnection($conn, [
            'type' => 'pong',
            'server_time' => date('c'),
            'timestamp' => date('c')
        ]);
    }
    
    private function handleGetHistory(ConnectionInterface $conn) {
        // 模擬歷史記錄
        $this->sendToConnection($conn, [
            'type' => 'history_loaded',
            'history' => [
                [
                    'title' => '測試代碼1',
                    'author' => '測試用戶',
                    'timestamp' => date('c', time() - 3600)
                ],
                [
                    'title' => '測試代碼2', 
                    'author' => '開發者',
                    'timestamp' => date('c', time() - 1800)
                ]
            ],
            'total' => 2,
            'timestamp' => date('c')
        ]);
    }
    
    private function removeUserFromRoom(ConnectionInterface $conn, $roomId) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        $userId = $conn->userId ?? '';
        $username = $conn->username ?? '匿名用戶';
        
        // 從房間移除用戶
        $this->rooms[$roomId] = array_filter($this->rooms[$roomId], function($c) use ($conn) {
            return $c !== $conn;
        });
        
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
        unset($conn->roomId);
        unset($conn->userId);
        unset($conn->username);
    }
    
    private function broadcastToRoom($roomId, $data, $except = null) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        foreach ($this->rooms[$roomId] as $conn) {
            if ($conn !== $except) {
                $this->sendToConnection($conn, $data);
            }
        }
    }
    
    private function sendToConnection(ConnectionInterface $conn, $data) {
        try {
            $conn->send(json_encode($data));
        } catch (Exception $e) {
            $this->log("❌ 發送消息失敗: " . $e->getMessage());
        }
    }
    
    private function sendError(ConnectionInterface $conn, $message) {
        $this->sendToConnection($conn, [
            'type' => 'error',
            'error' => $message,
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
    
    private function handleAIRequest(ConnectionInterface $conn, $data) {
        $action = $data['action'] ?? '';
        $code = $data['data']['code'] ?? '';
        $requestId = $data['requestId'] ?? 'unknown';
        
        if (empty($action) || empty($code)) {
            $this->sendError($conn, '缺少AI請求參數');
            return;
        }
        
        $this->log("🤖 AI請求: {$conn->username} - {$action} (ID: {$requestId})");
        
        // 檢查AI配置
        $aiConfigFile = __DIR__ . '/../ai_config.json';
        if (!file_exists($aiConfigFile)) {
            $this->sendToConnection($conn, [
                'type' => 'ai_response',
                'action' => $action,
                'requestId' => $requestId,
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
                'requestId' => $requestId,
                'success' => true,
                'response' => $response,
                'timestamp' => date('c')
            ]);
            
            $this->log("✅ AI響應成功: {$action}");
            
        } catch (Exception $e) {
            $this->sendToConnection($conn, [
                'type' => 'ai_response',
                'action' => $action,
                'requestId' => $requestId,
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ]);
            
            $this->log("❌ AI請求失敗: " . $e->getMessage());
        }
    }
    
    private function callOpenAI($config, $action, $code) {
        $prompts = [
            'explain' => "請解釋以下Python代碼的功能和邏輯：\n\n{$code}",
            'check_errors' => "請檢查以下Python代碼是否有錯誤：\n\n{$code}",
            'suggest_improvements' => "請為以下Python代碼提供改進建議：\n\n{$code}",
            'analyze' => "請分析以下Python代碼的結構和功能：\n\n{$code}",
            'answer_question' => "關於以下Python代碼，請回答問題：\n\n{$code}"
        ];
        
        $prompt = $prompts[$action] ?? $prompts['explain'];
        
        $data = [
            'model' => $config['model'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '你是一個專業的Python程式設計助教，專門幫助學生學習Python程式設計。請用繁體中文回答所有問題。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
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
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        // 輸出到控制台
        echo $logMessage;
        
        // 寫入日誌文件
        $logFile = __DIR__ . '/../test-logs/stable_websocket.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// 啟動服務器
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new StableWebSocketServer()
        )
    ),
    8081
);

echo "🎯 穩定版WebSocket服務器正在運行...\n";
echo "🔗 連接地址: ws://localhost:8081\n";
echo "⏹️ 按 Ctrl+C 停止服務器\n";

$server->run(); 