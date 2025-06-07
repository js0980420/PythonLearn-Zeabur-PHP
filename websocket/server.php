<?php
/**
 * WebSocket 協作服務器
 * 支援實時代碼編輯、衝突檢測、聊天等功能
 */

// 簡單的Logger類
class Logger {
    private $logFile;
    
    public function __construct($logFile = 'websocket.log') {
        $this->logFile = $logFile;
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    private function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
        
        // 輸出到控制台
        echo $logEntry;
        
        // 寫入日誌文件（可選）
        @file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// 簡單的ConflictDetector類
class ConflictDetector {
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
    }
    
    public function detectConflict($originalCode, $userCode1, $userCode2, $userId1, $userId2) {
        // 簡化的衝突檢測
        if ($userCode1 !== $userCode2) {
            return [
                'has_conflict' => true,
                'type' => 'code_difference',
                'users' => [$userId1, $userId2],
                'description' => '代碼版本不同步',
                'details' => '用戶間的代碼存在差異'
            ];
        }
        
        return ['has_conflict' => false];
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
// 載入增強的 Database 類，支援 XAMPP MySQL 和完整功能
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
    protected $logger;
    protected $conflictDetector;
    protected $roomCodeStates; // 存儲房間的代碼狀態
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->roomCodeStates = [];
        
        // 初始化增強的 Database 類
        try {
            $this->database = new Database();
            
            // 檢查數據庫狀態
            if (method_exists($this->database, 'isConnected')) {
                $isConnected = $this->database->isConnected();
                $connectionStatus = $isConnected ? '✅ 已連接' : '❌ 未連接';
                
                echo "🔧 WebSocket 服務器啟動中...\n";
                echo "   數據庫類型: MySQL\n";
                echo "   連接狀態: {$connectionStatus}\n";
                echo "   數據表數量: 10\n";
            } else {
                echo "🔧 WebSocket 服務器啟動中...\n";
                echo "   數據庫類型: Unknown\n";
                echo "   連接狀態: ✅ 已初始化\n";
            }
        } catch (Exception $e) {
            echo "❌ 數據庫初始化失敗: " . $e->getMessage() . "\n";
            echo "   將使用內存模式運行\n";
            $this->database = null;
        }
        
        $this->logger = new Logger('websocket.log');
        $this->conflictDetector = new ConflictDetector($this->logger);
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->roomId = null;
        $conn->userId = null;
        $conn->username = null;
        
        echo "新連接 ({$conn->resourceId})\n";
        $this->logger->info('WebSocket新連接', ['resource_id' => $conn->resourceId]);
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                $this->sendError($from, '無效的消息格式');
                return;
            }
            
            $this->logger->debug('收到消息', [
                'resource_id' => $from->resourceId,
                'type' => $data['type'],
                'room_id' => $from->roomId ?? null
            ]);
            
            switch ($data['type']) {
                case 'join_room':
                    $this->handleJoinRoom($from, $data);
                    break;
                    
                case 'leave_room':
                    $this->handleLeaveRoom($from, $data);
                    break;
                    
                case 'code_change':
                    $this->handleCodeChange($from, $data);
                    break;
                    
                case 'cursor_position':
                    $this->handleCursorPosition($from, $data);
                    break;
                    
                case 'conflict_resolution':
                    $this->handleConflictResolution($from, $data);
                    break;
                    
                case 'chat_message':
                    $this->handleChatMessage($from, $data);
                    break;
                    
                case 'heartbeat':
                    $this->handleHeartbeat($from, $data);
                    break;
                    
                case 'ai_request':
                    $this->handleAIRequest($from, $data);
                    break;
                    
                case 'save_code':
                    $this->handleSaveCode($from, $data);
                    break;
                    
                case 'load_code':
                    $this->handleLoadCode($from, $data);
                    break;
                    
                case 'run_code':
                    $this->handleRunCode($from, $data);
                    break;
                    
                case 'get_history':
                    $this->handleGetHistory($from, $data);
                    break;
                    
                case 'delete_slot':
                    $this->handleDeleteSlot($from, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($from, $data);
                    break;
                    
                default:
                    $this->sendError($from, '未知的消息類型: ' . $data['type']);
            }
            
        } catch (Exception $e) {
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                // 'trace' => $e->getTraceAsString() // Full trace can be verbose
            ];
            $this->logger->error('處理消息錯誤', [
                'error' => $errorDetails,
                'resource_id' => $from->resourceId,
                'message_data' => $msg // Log the original message
            ]);
            $clientErrorMessage = "服務器錯誤: " . $e->getMessage() . " in " . basename($e->getFile()) . " on line " . $e->getLine();
            $this->sendError($from, $clientErrorMessage);
            echo "處理消息時發生錯誤: {$clientErrorMessage}\n"; // Also echo to server console
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        if ($conn->roomId) {
            $this->handleLeaveRoom($conn, ['room_id' => $conn->roomId]);
        }
        
        $this->clients->detach($conn);
        
        echo "連接關閉 ({$conn->resourceId})\n";
        $this->logger->info('WebSocket連接關閉', ['resource_id' => $conn->resourceId]);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $errorDetails = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            // 'trace' => $e->getTraceAsString()
        ];
        $errorMessage = $e->getMessage() . " in " . basename($e->getFile()) . " on line " . $e->getLine();
        echo "WebSocket連接錯誤: {$errorMessage}\n"; 

        $this->logger->error('WebSocket錯誤', [
            'error' => $errorDetails,
            'resource_id' => $conn->resourceId
        ]);
        
        // Attempt to send error to client before closing, if connection is not null
        if ($conn) {
            try {
                $this->sendError($conn, "WebSocket底層錯誤: " . $e->getMessage());
            } catch (Exception $sendEx) {
                // Ignore if sending also fails
                echo "發送onError消息失敗: {$sendEx->getMessage()}\n";
            }
            $conn->close();
        }
    }
    
    private function handleJoinRoom(ConnectionInterface $conn, $data) {
        $roomId = trim($data['room_id'] ?? '');
        $userId = trim($data['user_id'] ?? '');
        $username = trim($data['username'] ?? '');
        
        $this->logger->info('handleJoinRoom - 收到參數', [
            'data_room_id' => $data['room_id'] ?? '未提供',
            'data_user_id' => $data['user_id'] ?? '未提供',
            'data_username' => $data['username'] ?? '未提供',
            'parsed_room_id' => $roomId,
            'parsed_user_id' => $userId,
            'parsed_username' => $username,
            'resource_id' => $conn->resourceId
        ]);

        if (empty($roomId) || empty($userId) || empty($username)) {
            $this->sendError($conn, '缺少必要參數 (room_id, user_id, username).');
            $this->logger->warning('加入房間失敗: 缺少參數', array_merge($data, ['parsed_room_id' => $roomId, 'parsed_user_id' => $userId, 'parsed_username' => $username]));
            return;
        }
        
        echo "用戶 {$username} ({$userId}) 即將加入房間 {$roomId}\n";
        
        // 🆕 使用數據庫記錄用戶加入房間
        if ($this->database) {
            $joinResult = $this->database->joinRoom($roomId, $userId, $username, 'student');
            if (!$joinResult['success']) {
                $this->sendError($conn, $joinResult['error']);
                return;
            }
            echo "✅ 數據庫記錄用戶加入: {$username} 加入房間 {$roomId}\n";
        }
        
        // 設置連接屬性
        $conn->roomId = $roomId;
        $conn->userId = $userId;
        $conn->username = $username;
        
        $this->logger->info('連接屬性設置完畢', [
            'conn_resourceId' => $conn->resourceId,
            'conn_roomId' => $conn->roomId,
            'conn_userId' => $conn->userId,
            'conn_username' => $conn->username
        ]);

        // 添加到房間
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [];
        }
        $this->rooms[$roomId][$conn->resourceId] = $conn;
        
        // 🆕 初始化或更新房間代碼狀態，記錄用戶加入時間
        if (!isset($this->roomCodeStates[$roomId])) {
            $this->roomCodeStates[$roomId] = [
                'current_code' => '',
                'user_versions' => [],
                'user_join_times' => [],
                'last_update' => time()
            ];
        }
        
        // 🆕 記錄用戶加入時間
        $this->roomCodeStates[$roomId]['user_join_times'][$userId] = time();
        echo "記錄用戶加入時間: {$username} 於 " . date('H:i:s') . " 加入房間 {$roomId}\n";
        
        // 獲取房間當前代碼
        $currentCode = '';
        if ($this->database) {
            $codeResult = $this->database->loadCode($roomId);
            $currentCode = $codeResult ? $codeResult['code'] : '';
        }
        
        // 發送加入成功消息（包含數據庫信息）
        $responseData = [
            'type' => 'room_joined',
            'room_id' => $roomId,
            'user_id' => $userId,
            'username' => $username,
            'message' => "成功加入房間 {$roomId}",
            'current_code' => $currentCode,
            'timestamp' => date('c')
        ];
        
        // 添加房間信息（如果數據庫可用）
        if ($this->database && isset($joinResult['room_info'])) {
            $responseData['room_info'] = [
                'user_count' => $joinResult['user_count'],
                'max_users' => $joinResult['room_info']['max_users'] ?? 10
            ];
        }
        
        $this->sendToConnection($conn, $responseData);
        
        // 獲取並發送用戶列表
        $this->broadcastUserList($roomId);

        // 通知房間內其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'username' => $username,
            'message' => "{$username} 加入了房間",
            'timestamp' => date('c')
        ], $conn);
        
        $this->logger->info('用戶加入房間', [
            'user_id' => $userId,
            'room_id' => $roomId,
            'resource_id' => $conn->resourceId,
            'join_time' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function handleLeaveRoom(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        
        if (!$roomId) {
            return;
        }
        
        // 🆕 使用數據庫記錄用戶離開房間
        if ($this->database && $conn->userId) {
            $leaveResult = $this->database->leaveRoom($roomId, $conn->userId);
            if ($leaveResult['success']) {
                echo "✅ 數據庫記錄用戶離開: {$conn->username} 離開房間 {$roomId}\n";
            }
        }
        
        // 從房間移除
        if (isset($this->rooms[$roomId][$conn->resourceId])) {
            unset($this->rooms[$roomId][$conn->resourceId]);
            
            // 如果房間為空，清理房間
            if (empty($this->rooms[$roomId])) {
                unset($this->rooms[$roomId]);
                // 🆕 同時清理房間的代碼狀態
                if (isset($this->roomCodeStates[$roomId])) {
                    unset($this->roomCodeStates[$roomId]);
                    echo "清理空房間狀態: {$roomId}\n";
                }
            } else {
                // 🆕 房間不為空時，只清理該用戶的相關記錄
                if (isset($this->roomCodeStates[$roomId]['user_join_times'][$conn->userId])) {
                    unset($this->roomCodeStates[$roomId]['user_join_times'][$conn->userId]);
                    echo "清理用戶加入時間記錄: {$conn->username} 離開房間 {$roomId}\n";
                }
                if (isset($this->roomCodeStates[$roomId]['user_versions'][$conn->userId])) {
                    unset($this->roomCodeStates[$roomId]['user_versions'][$conn->userId]);
                    echo "清理用戶代碼版本記錄: {$conn->username} 離開房間 {$roomId}\n";
                }
            }
        }
        
        // 通知房間其他用戶
        $this->broadcastToRoom($roomId, [
            'type' => 'user_left',
            'user_id' => $conn->userId,
            'username' => $conn->username
        ], $conn);
        
        // 更新用戶列表
        $this->broadcastUserList($roomId);
        
        $this->logger->info('用戶離開房間', [
            'user_id' => $conn->userId,
            'room_id' => $roomId,
            'resource_id' => $conn->resourceId
        ]);
        
        // 清理連接屬性
        $conn->roomId = null;
        $conn->userId = null;
        $conn->username = null;
    }
    
    private function handleCodeChange(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        $code = $data['code'] ?? '';
        $changeType = $data['change_type'] ?? 'edit';
        $position = $data['position'] ?? [];
        
        if (!$roomId) {
            $this->sendError($conn, '您未加入任何房間');
            return;
        }
        
        // 🔒 檢查房間是否處於衝突等待狀態
        if (isset($this->roomCodeStates[$roomId]['sync_paused']) && 
            $this->roomCodeStates[$roomId]['sync_paused']) {
            
            $mainChanger = $this->roomCodeStates[$roomId]['main_changer'] ?? null;
            
            // 如果當前用戶不是主改方，禁止修改
            if ($mainChanger && $mainChanger !== $conn->userId) {
                $mainChangerName = $this->getUsernameById($roomId, $mainChanger);
                $conflictData = $this->roomCodeStates[$roomId]['conflict_data'] ?? [];
                $mainChangeType = '';
                
                // 獲取主改方的操作類型
                if (isset($this->roomCodeStates[$roomId]['user_versions'][$mainChanger])) {
                    $mainChangeType = $this->roomCodeStates[$roomId]['user_versions'][$mainChanger]['change_type'] ?? 'edit';
                }
                
                $this->sendToConnection($conn, [
                    'type' => 'edit_blocked_waiting_decision',
                    'main_changer_name' => $mainChangerName,
                    'main_change_type' => $mainChangeType,
                    'conflict_type' => $conflictData['type'] ?? 'unknown',
                    'message' => "⏳ {$mainChangerName} 正在處理衝突 ({$mainChangeType})，請等待決定..."
                ]);
                return;
            }
        }
        
        // 獲取房間當前狀態
        if (!isset($this->roomCodeStates[$roomId])) {
            // 初始化房間狀態
            $this->roomCodeStates[$roomId] = [
                'current_code' => '',
                'user_versions' => [],
                'user_join_times' => [], // 🆕 記錄用戶加入時間
                'last_update' => time()
            ];
        }
        
        $currentState = &$this->roomCodeStates[$roomId];
        
        // 🆕 檢查用戶是否在初始化期（加入房間後 10 秒內）
        $userJoinTime = $currentState['user_join_times'][$conn->userId] ?? 0;
        $isInInitializationPeriod = (time() - $userJoinTime) < 10; // 10秒緩衝期
        
        // 🆕 檢查是否為首次代碼更新（用戶版本記錄為空）
        $isFirstCodeUpdate = !isset($currentState['user_versions'][$conn->userId]);
        
        // 🆕 跳過衝突檢測的條件
        $shouldSkipConflictDetection = $isInInitializationPeriod || $isFirstCodeUpdate;
        
        if ($shouldSkipConflictDetection) {
            echo "跳過衝突檢測: 用戶 {$conn->username} 在初始化期 (加入時間: " . ($userJoinTime ? date('H:i:s', $userJoinTime) : '未知') . ", 首次更新: " . ($isFirstCodeUpdate ? '是' : '否') . ")\n";
        }
        
        // 🚨 核心衝突檢測：只有在非初始化期且有多個用戶時才進行檢測
        $roomUsers = $this->getRoomUsers($roomId);
        if (!$shouldSkipConflictDetection && count($roomUsers) >= 2) {
            
            // 檢測與其他在線用戶的衝突（移除時間窗口限制）
            foreach ($currentState['user_versions'] as $otherUserId => $otherUserData) {
                if ($otherUserId !== $conn->userId) {
                    
                    // 🆕 檢查對方用戶是否也在初始化期
                    $otherUserJoinTime = $currentState['user_join_times'][$otherUserId] ?? 0;
                    $otherUserInInitPeriod = (time() - $otherUserJoinTime) < 10;
                    
                    // 如果對方也在初始化期，跳過與該用戶的衝突檢測
                    if ($otherUserInInitPeriod) {
                        echo "跳過與用戶 {$otherUserId} 的衝突檢測: 對方在初始化期\n";
                        continue;
                    }
                    
                    // 🔥 最優先：檢測同一行不同修改衝突
                    $lineConflict = $this->detectSameLineConflict(
                        $currentState['current_code'], 
                        $otherUserData['code'], 
                        $code,
                        $otherUserId,
                        $conn->userId
                    );
                    
                    if ($lineConflict) {
                        echo "檢測到同行衝突: 用戶 {$conn->username} vs 用戶 {$otherUserId}\n";
                        $this->handleConflictDetected($conn, $roomId, $lineConflict, $otherUserId, $otherUserData['code'], $code);
                        return; // 立即停止，等待衝突解決
                    }
                    
                    // 🔥 第二優先：檢測代碼移除衝突
                    $removalConflict = $this->detectCodeRemovalConflict(
                        $currentState['current_code'],
                        $otherUserData['code'],
                        $code,
                        $otherUserId,
                        $conn->userId,
                        $changeType
                    );
                    
                    if ($removalConflict) {
                        echo "檢測到移除衝突: 用戶 {$conn->username} vs 用戶 {$otherUserId}\n";
                        $this->handleConflictDetected($conn, $roomId, $removalConflict, $otherUserId, $otherUserData['code'], $code);
                        return; // 立即停止，等待衝突解決
                    }
                }
            }
        }
        
        // 更新房間狀態 - 記錄此用戶的版本
        $currentState['user_versions'][$conn->userId] = [
            'code' => $code,
            'timestamp' => time(),
            'change_type' => $changeType,
            'username' => $conn->username
        ];
        
        // 更新房間的當前代碼版本
        $currentState['current_code'] = $code;
        $currentState['last_update'] = time();
        
        // 保存代碼變更到資料庫
        if ($this->database) {
            try {
                // 🔍 調試信息：檢查 database 對象狀態
                echo "🔍 Database 對象檢查:\n";
                echo "   類型: " . get_class($this->database) . "\n";
                echo "   是否有 insert 方法: " . (method_exists($this->database, 'insert') ? '✅' : '❌') . "\n";
                
                // 確保changeType在枚舉範圍內
                $validChangeTypes = ['insert', 'delete', 'replace', 'paste', 'load', 'import', 'edit'];
                $dbChangeType = in_array($changeType, $validChangeTypes) ? $changeType : 'edit';
                
                // 使用Database類的query方法直接插入，避免insert方法問題
                $insertSql = "INSERT INTO code_changes (room_id, user_id, change_type, code_content, position_data, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                $result = $this->database->query($insertSql, [
                    $roomId,
                    $conn->userId,
                    $dbChangeType,
                    $code,
                    json_encode($position)
                ]);
                
                if ($result !== false) {
                    echo "✅ 代碼變更記錄成功\n";
                } else {
                    echo "❌ 代碼變更記錄失敗\n";
                }
            } catch (Exception $e) {
                echo "Database insert error: " . $e->getMessage() . "\n";
                $this->logger->error('Database insert failed', [
                    'error' => $e->getMessage(),
                    'room_id' => $roomId,
                    'user_id' => $conn->userId
                ]);
            }
        }
        
        // 如果沒有衝突，廣播代碼變更
        $broadcastMessage = [
            'type' => 'code_changed',
            'user_id' => $conn->userId,
            'username' => $conn->username,
            'code' => $code,
            'change_type' => $changeType,
            'position' => $position,
            'timestamp' => time()
        ];
        
        echo "廣播代碼變更: 用戶 {$conn->username} 在房間 {$roomId}\n";
        $this->broadcastToRoom($roomId, $broadcastMessage, $conn);
        
        $this->logger->info('代碼變更', [
            'user_id' => $conn->userId,
            'room_id' => $roomId,
            'change_type' => $changeType,
            'code_length' => strlen($code),
            'room_users' => count($roomUsers),
            'skipped_conflict_detection' => $shouldSkipConflictDetection
        ]);
    }
    
    /**
     * 🔥 最優先：檢測同一行不同修改衝突
     */
    private function detectSameLineConflict($originalCode, $otherUserCode, $currentUserCode, $otherUserId, $currentUserId) {
        $originalLines = explode("\n", $originalCode);
        $otherLines = explode("\n", $otherUserCode);
        $currentLines = explode("\n", $currentUserCode);
        
        $maxLines = max(count($originalLines), count($otherLines), count($currentLines));
        $conflictLines = [];
        
        for ($i = 0; $i < $maxLines; $i++) {
            $originalLine = trim($originalLines[$i] ?? '');
            $otherLine = trim($otherLines[$i] ?? '');
            $currentLine = trim($currentLines[$i] ?? '');
            
            // 🚨 加強檢測：同一行被兩人同時修改
            $bothModified = ($otherLine !== $originalLine) && ($currentLine !== $originalLine);
            $differentContent = ($otherLine !== $currentLine);
            
            // 🔥 新增：即使一方是空行，只要另一方有內容且不同就算衝突
            $hasContentConflict = 
                ($bothModified && $differentContent) ||
                (empty($originalLine) && !empty($otherLine) && !empty($currentLine) && $otherLine !== $currentLine) ||
                (!empty($originalLine) && (empty($otherLine) !== empty($currentLine)));
            
            if ($hasContentConflict) {
                $conflictLines[] = [
                    'line_number' => $i + 1,
                    'original' => $originalLine,
                    'other_user' => $otherLine,
                    'current_user' => $currentLine,
                    'conflict_type' => $this->getLineConflictType($originalLine, $otherLine, $currentLine)
                ];
            }
        }
        
        if (!empty($conflictLines)) {
            return [
                'type' => 'same_line_conflict',
                'conflict_id' => 'conflict_' . time() . '_' . mt_rand(1000, 9999),
                'conflict_lines' => $conflictLines,
                'total_conflicts' => count($conflictLines),
                'description' => "檢測到 " . count($conflictLines) . " 行代碼被兩人同時修改",
                'severity' => count($conflictLines) > 3 ? 'critical' : 'high',
                'users' => [$otherUserId, $currentUserId]
            ];
        }
        
        return null;
    }

    /**
     * 🔥 第二優先：檢測代碼移除衝突（載入/貼上/剪下/導入造成大量變更）
     */
    private function detectCodeRemovalConflict($originalCode, $otherUserCode, $currentUserCode, $otherUserId, $currentUserId, $changeType) {
        $originalLength = strlen(trim($originalCode));
        $otherLength = strlen(trim($otherUserCode));
        $currentLength = strlen(trim($currentUserCode));
        
        $originalLineCount = count(explode("\n", $originalCode));
        $otherLineCount = count(explode("\n", $otherUserCode));
        $currentLineCount = count(explode("\n", $currentUserCode));
        
        // 🔥 檢測大量代碼變更操作
        $isMassiveChange = 
            // 1. 明確的大量操作類型
            in_array($changeType, ['import', 'paste', 'load', 'cut', 'replace']) ||
            // 2. 字符數變化超過50%
            abs($currentLength - $otherLength) > max($otherLength * 0.5, 100) ||
            // 3. 行數變化超過30%
            abs($currentLineCount - $otherLineCount) > max($otherLineCount * 0.3, 5) ||
            // 4. 整個編輯器內容被替換（與原始代碼相比差異巨大）
            ($originalLength > 50 && abs($currentLength - $originalLength) > $originalLength * 0.8);
        
        if ($isMassiveChange) {
            $originalLines = explode("\n", $originalCode);
            $otherLines = explode("\n", $otherUserCode);
            $currentLines = explode("\n", $currentUserCode);
            
            // 🔥 檢查代碼行被移除或大幅修改
            $affectedLines = [];
            $removedLines = [];
            $addedLines = [];
            
            // 檢查其他用戶的代碼行是否在當前版本中消失
            foreach ($otherLines as $lineNum => $otherLine) {
                $otherLine = trim($otherLine);
                if (!empty($otherLine)) {
                    // 檢查這行是否在當前代碼中存在
                    $foundInCurrent = false;
                    foreach ($currentLines as $currentLine) {
                        if (trim($currentLine) === $otherLine) {
                            $foundInCurrent = true;
                            break;
                        }
                    }
                    
                    if (!$foundInCurrent) {
                        $removedLines[] = [
                            'line_number' => $lineNum + 1,
                            'content' => $otherLine,
                            'reason' => '其他用戶的代碼行在新版本中消失'
                        ];
                    }
                }
            }
            
            // 檢查當前版本新增的大量代碼
            foreach ($currentLines as $lineNum => $currentLine) {
                $currentLine = trim($currentLine);
                if (!empty($currentLine)) {
                    $foundInOther = false;
                    foreach ($otherLines as $otherLine) {
                        if (trim($otherLine) === $currentLine) {
                            $foundInOther = true;
                            break;
                        }
                    }
                    
                    if (!$foundInOther) {
                        $addedLines[] = [
                            'line_number' => $lineNum + 1,
                            'content' => $currentLine,
                            'reason' => '新版本新增的代碼行'
                        ];
                    }
                }
            }
            
            // 🚨 如果有顯著的代碼變更，觸發衝突
            if (!empty($removedLines) || !empty($addedLines) || 
                abs($currentLineCount - $otherLineCount) > 3) {
                
                $changeDescription = $this->generateMassiveChangeDescription($changeType, $removedLines, $addedLines, $otherLineCount, $currentLineCount);
                
                return [
                    'type' => 'massive_code_change',
                    'conflict_id' => 'conflict_' . time() . '_' . mt_rand(1000, 9999),
                    'change_type' => $changeType,
                    'removed_lines' => $removedLines,
                    'added_lines' => $addedLines,
                    'other_line_count' => $otherLineCount,
                    'current_line_count' => $currentLineCount,
                    'other_char_count' => $otherLength,
                    'current_char_count' => $currentLength,
                    'description' => $changeDescription,
                    'severity' => 'critical',
                    'users' => [$otherUserId, $currentUserId],
                    'change_magnitude' => $this->calculateChangeMagnitude($otherLength, $currentLength, $otherLineCount, $currentLineCount)
                ];
            }
        }
        
        return null;
    }

    /**
     * 🆕 獲取行衝突類型
     */
    private function getLineConflictType($originalLine, $otherLine, $currentLine) {
        if (empty($originalLine)) {
            return 'both_added_different'; // 兩人都在空行添加不同內容
        } elseif (empty($otherLine) && empty($currentLine)) {
            return 'both_deleted'; // 兩人都刪除了同一行
        } elseif (empty($otherLine) || empty($currentLine)) {
            return 'one_deleted_one_modified'; // 一人刪除一人修改
        } else {
            return 'both_modified_different'; // 兩人修改成不同內容
        }
    }

    /**
     * 🆕 生成大量變更描述
     */
    private function generateMassiveChangeDescription($changeType, $removedLines, $addedLines, $otherLineCount, $currentLineCount) {
        $descriptions = [];
        
        // 根據變更類型給出描述
        $typeDescriptions = [
            'import' => '導入新檔案',
            'paste' => '大量貼上操作',
            'load' => '載入歷史版本',
            'cut' => '大量剪下操作',
            'replace' => '整個編輯器內容替換',
            'edit' => '大量編輯操作'
        ];
        
        $typeDesc = $typeDescriptions[$changeType] ?? '大量代碼變更';
        $descriptions[] = $typeDesc;
        
        if (!empty($removedLines)) {
            $descriptions[] = "移除了 " . count($removedLines) . " 行其他同學的代碼";
        }
        
        if (!empty($addedLines)) {
            $descriptions[] = "新增了 " . count($addedLines) . " 行新代碼";
        }
        
        $lineDiff = $currentLineCount - $otherLineCount;
        if ($lineDiff > 0) {
            $descriptions[] = "總行數增加 {$lineDiff} 行";
        } elseif ($lineDiff < 0) {
            $descriptions[] = "總行數減少 " . abs($lineDiff) . " 行";
        }
        
        return implode('，', $descriptions) . "，可能影響其他同學的工作";
    }

    /**
     * 🆕 計算變更幅度
     */
    private function calculateChangeMagnitude($otherLength, $currentLength, $otherLineCount, $currentLineCount) {
        $charChangeRatio = $otherLength > 0 ? abs($currentLength - $otherLength) / $otherLength : 1;
        $lineChangeRatio = $otherLineCount > 0 ? abs($currentLineCount - $otherLineCount) / $otherLineCount : 1;
        
        $magnitude = max($charChangeRatio, $lineChangeRatio);
        
        if ($magnitude > 0.8) return 'extreme';
        if ($magnitude > 0.5) return 'major';
        if ($magnitude > 0.3) return 'moderate';
        return 'minor';
    }

    /**
     * 處理檢測到的衝突
     */
    private function handleConflictDetected($conn, $roomId, $conflict, $otherUserId, $otherUserCode, $currentUserCode) {
        // 🔄 新模式：主改方決定衝突解決方案
        
        // 暫停此房間的代碼同步
        $this->roomCodeStates[$roomId]['sync_paused'] = true;
        $this->roomCodeStates[$roomId]['conflict_data'] = $conflict;
        $this->roomCodeStates[$roomId]['main_changer'] = $conn->userId; // 主改方（當前發起修改的用戶）
        $this->roomCodeStates[$roomId]['other_changer'] = $otherUserId; // 非主改方
        
        // 獲取用戶的變更類型資訊
        $mainChangerData = $this->roomCodeStates[$roomId]['user_versions'][$conn->userId] ?? [];
        $changeType = $mainChangerData['change_type'] ?? 'edit';
        
        // 🎯 發送主改方決定界面給當前用戶（主改方）
        $this->sendToConnection($conn, [
            'type' => 'conflict_main_changer_decision',
            'conflict_id' => $conflict['conflict_id'],
            'conflict_type' => $conflict['type'],
            'conflict_data' => $conflict,
            'other_user_id' => $otherUserId,
            'other_username' => $this->getUsernameById($roomId, $otherUserId),
            'your_code' => $currentUserCode,
            'other_code' => $otherUserCode,
            'your_change_type' => $changeType,
            'room_id' => $roomId,
            'is_main_changer' => true,
            'message' => '您是主改方，請選擇如何處理衝突'
        ]);
        
        // 🔒 發送等待界面給其他用戶（非主改方）
        foreach ($this->rooms[$roomId] as $otherConn) {
            if ($otherConn->userId !== $conn->userId) {
                $this->sendToConnection($otherConn, [
                    'type' => 'conflict_waiting_decision',
                    'conflict_id' => $conflict['conflict_id'],
                    'conflict_type' => $conflict['type'],
                    'conflict_data' => $conflict,
                    'main_changer_id' => $conn->userId,
                    'main_changer_name' => $conn->username,
                    'main_change_type' => $changeType,
                    'your_code' => $otherConn->userId === $otherUserId ? $otherUserCode : '',
                    'main_changer_code' => $currentUserCode,
                    'room_id' => $roomId,
                    'is_main_changer' => false,
                    'message' => $conn->username . ' 正在處理代碼衝突，請等待...'
                ]);
            }
        }
        
        $this->logger->warning('檢測到代碼衝突 (主改方決定模式)', [
            'room_id' => $roomId,
            'conflict_id' => $conflict['conflict_id'],
            'conflict_type' => $conflict['type'],
            'main_changer' => $conn->userId,
            'other_changer' => $otherUserId,
            'change_type' => $changeType,
            'description' => $conflict['description']
        ]);
        
        echo "🚨 衝突檢測 (主改方模式): {$conflict['type']} 在房間 {$roomId}\n";
        echo "   📝 主改方: {$conn->username} ({$changeType})\n";
        echo "   ⏳ 等待中: " . $this->getUsernameById($roomId, $otherUserId) . "\n";
    }
    
    private function handleCursorPosition(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        $position = $data['position'] ?? [];
        
        if (!$roomId) {
            return;
        }
        
        // 廣播游標位置
        $this->broadcastToRoom($roomId, [
            'type' => 'cursor_moved',
            'user_id' => $conn->userId,
            'username' => $conn->username,
            'position' => $position
        ], $conn);
    }
    
    private function handleConflictResolution(ConnectionInterface $conn, $data) {
        $conflictId = $data['conflict_id'] ?? '';
        $resolution = $data['resolution'] ?? '';
        $resolvedCode = $data['resolved_code'] ?? '';
        $roomId = $conn->roomId;
        
        if (!$conflictId || !$resolution || !$roomId) {
            $this->sendError($conn, '缺少必要參數');
            return;
        }
        
        // 檢查房間是否處於衝突狀態
        if (!isset($this->roomCodeStates[$roomId]['sync_paused']) || 
            !$this->roomCodeStates[$roomId]['sync_paused']) {
            $this->sendError($conn, '房間當前沒有衝突需要解決');
            return;
        }
        
        // 🔐 權限檢查：只有主改方可以做決定
        $mainChanger = $this->roomCodeStates[$roomId]['main_changer'] ?? null;
        if ($mainChanger !== $conn->userId) {
            $mainChangerName = $this->getUsernameById($roomId, $mainChanger);
            $this->sendError($conn, "只有主改方 ({$mainChangerName}) 可以決定如何處理衝突，請等待...");
            return;
        }
        
        // 獲取衝突數據
        $conflictData = $this->roomCodeStates[$roomId]['conflict_data'] ?? null;
        if (!$conflictData || $conflictData['conflict_id'] !== $conflictId) {
            $this->sendError($conn, '衝突ID不匹配或衝突已過期');
            return;
        }
        
        switch ($resolution) {
            case 'accept':
                // 接受對方的修改
                $finalCode = $data['other_code'] ?? $resolvedCode;
                
                // 更新房間代碼狀態
                $this->roomCodeStates[$roomId]['current_code'] = $finalCode;
                $this->roomCodeStates[$roomId]['sync_paused'] = false;
                unset($this->roomCodeStates[$roomId]['conflict_data']);
                unset($this->roomCodeStates[$roomId]['main_changer']);
                unset($this->roomCodeStates[$roomId]['other_changer']);
                
                // 通知所有用戶衝突已解決
                $this->broadcastToRoom($roomId, [
                    'type' => 'conflict_resolved',
                    'conflict_id' => $conflictId,
                    'resolution' => 'accept',
                    'final_code' => $finalCode,
                    'resolved_by' => $conn->userId,
                    'resolver_name' => $conn->username,
                    'message' => $conn->username . ' 接受了對方的修改',
                    'conflict_type' => $conflictData['type']
                ]);
                
                echo "✅ 衝突解決: {$conn->username} 接受修改 (房間 {$roomId})\n";
                break;
                
            case 'reject':
                // 拒絕對方的修改，保持自己的版本
                $finalCode = $data['your_code'] ?? $resolvedCode;
                
                // 更新房間代碼狀態
                $this->roomCodeStates[$roomId]['current_code'] = $finalCode;
                $this->roomCodeStates[$roomId]['sync_paused'] = false;
                unset($this->roomCodeStates[$roomId]['conflict_data']);
                unset($this->roomCodeStates[$roomId]['main_changer']);
                unset($this->roomCodeStates[$roomId]['other_changer']);
                
                // 通知所有用戶衝突已解決
                $this->broadcastToRoom($roomId, [
                    'type' => 'conflict_resolved',
                    'conflict_id' => $conflictId,
                    'resolution' => 'reject',
                    'final_code' => $finalCode,
                    'resolved_by' => $conn->userId,
                    'resolver_name' => $conn->username,
                    'message' => $conn->username . ' 保持了自己的版本',
                    'conflict_type' => $conflictData['type']
                ]);
                
                echo "✅ 衝突解決: {$conn->username} 拒絕修改 (房間 {$roomId})\n";
                break;
                
            case 'share_to_chat':
                // 分享到聊天室討論
                $conflictDescription = $this->generateConflictDescription($conflictData);
                
                $this->broadcastToRoom($roomId, [
                    'type' => 'chat_message',
                    'user_id' => 'system',
                    'username' => '🤖 系統助手',
                    'message' => "📋 {$conn->username} 分享了一個代碼衝突需要討論:\n\n{$conflictDescription}\n\n請大家討論最佳解決方案！",
                    'conflict_data' => [
                        'conflict_id' => $conflictId,
                        'type' => $conflictData['type'],
                        'description' => $conflictData['description']
                    ],
                    'timestamp' => date('c'),
                    'is_system_message' => true
                ]);
                
                // 發送衝突分享確認
                $this->sendToConnection($conn, [
                    'type' => 'conflict_shared',
                    'conflict_id' => $conflictId,
                    'shared_by' => $conn->userId,
                    'message' => '衝突已分享到聊天室，房間同步仍暫停直到解決'
                ]);
                
                echo "💬 衝突分享: {$conn->username} 分享到聊天室 (房間 {$roomId})\n";
                // 注意：不恢復同步，等待進一步討論
                break;
                
            case 'ai_analyze':
                // 請求AI分析
                $this->handleConflictAnalysisRequest($conn, [
                    'conflict_id' => $conflictId,
                    'your_code' => $data['your_code'] ?? '',
                    'other_code' => $data['other_code'] ?? '',
                    'conflict_data' => $conflictData
                ]);
                
                echo "🤖 AI分析: {$conn->username} 請求AI協助 (房間 {$roomId})\n";
                // 注意：不立即恢復同步，等待AI分析結果
                break;
                
            default:
                $this->sendError($conn, '未知的解決方案類型: ' . $resolution);
                return;
        }
        
        // 記錄衝突解決
        $this->logger->info('衝突解決', [
            'room_id' => $roomId,
            'conflict_id' => $conflictId,
            'resolution' => $resolution,
            'resolved_by' => $conn->userId,
            'conflict_type' => $conflictData['type']
        ]);
    }

    /**
     * 生成衝突描述文字
     */
    private function generateConflictDescription($conflictData) {
        switch ($conflictData['type']) {
            case 'same_line_conflict':
                return "🔴 同行修改衝突：第 {$conflictData['line_number']} 行被兩人修改成不同內容\n" .
                       "原始：{$conflictData['original_line']}\n" .
                       "版本A：{$conflictData['other_user_line']}\n" .
                       "版本B：{$conflictData['current_user_line']}";
                
            case 'code_removal_conflict':
                $removedCount = count($conflictData['removed_lines']);
                return "⚠️ 代碼移除衝突：檢測到 {$removedCount} 行代碼被移除\n" .
                       "可能是導入新檔案或大量貼上造成的，請確認是否影響其他同學的工作";
                
            default:
                return "❓ 未知類型衝突：{$conflictData['description']}";
        }
    }
    
    private function handleChatMessage(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        $message = trim($data['message'] ?? '');
        
        if (!$roomId || !$message) {
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
    }
    
    private function handleHeartbeat(ConnectionInterface $conn, $data) {
        $this->sendToConnection($conn, [
            'type' => 'heartbeat_response',
            'timestamp' => time()
        ]);
    }

    private function handleAIRequest(ConnectionInterface $conn, $data) {
        try {
            $this->logger->info('收到AI請求', $data);
            
            if (!isset($data['action'])) {
                $this->sendError($conn, '無效的AI請求格式');
                return;
            }
            
            $action = $data['action'];
            $requestData = $data['data'] ?? [];
            
            // 支持的AI請求類型
            $supportedActions = [
                'conflict_analysis',
                'analyze',
                'check_errors', 
                'suggest',
                'explain_code'
            ];
            
            if ($action === 'conflict_analysis') {
                $this->handleConflictAnalysisRequest($conn, $requestData);
            } elseif (in_array($action, $supportedActions)) {
                $this->handleGeneralAIRequest($conn, $data);
            } else {
                $this->sendError($conn, '未知的AI請求類型: ' . $action);
            }
            
        } catch (Exception $e) {
            $this->logger->error('AI請求處理失敗', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            $this->sendError($conn, 'AI請求處理失敗: ' . $e->getMessage());
        }
    }

    private function handleGeneralAIRequest(ConnectionInterface $conn, $data) {
        try {
            $action = $data['action'];
            $requestId = $data['requestId'] ?? 'unknown';
            $code = $data['data']['code'] ?? '';
            $userId = $data['user_id'] ?? $conn->userId;
            $username = $data['username'] ?? $conn->username;
            $roomId = $data['room_id'] ?? $conn->roomId;
            
            $this->logger->info('處理一般AI請求', [
                'action' => $action,
                'requestId' => $requestId,
                'userId' => $userId,
                'codeLength' => strlen($code)
            ]);
            
            // 準備發送到AI API的數據
            $postData = [
                'action' => $action,
                'code' => $code,
                'user_id' => $userId,
                'username' => $username,
                'room_id' => $roomId
            ];
            
            // 發送POST請求到AI API (使用不同端口避免衝突)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost:8081/api/ai');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Cookie: PHPSESSID=' . session_id()
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($response === false) {
                throw new Exception('AI API請求失敗: ' . $curlError);
            }
            
            if ($httpCode !== 200) {
                throw new Exception('AI API返回錯誤狀態碼: ' . $httpCode);
            }
            
            $aiResult = json_decode($response, true);
            
            if (!$aiResult) {
                throw new Exception('AI API返回無效JSON');
            }
            
            // 回傳AI分析結果給用戶
            $this->sendToConnection($conn, [
                'type' => 'ai_response',
                'requestId' => $requestId,
                'action' => $action,
                'success' => $aiResult['success'] ?? false,
                'response' => $aiResult['data']['analysis'] ?? $aiResult['data'] ?? null,
                'error' => $aiResult['message'] ?? null,
                'timestamp' => date('c')
            ]);
            
            $this->logger->info('AI請求完成', [
                'action' => $action,
                'requestId' => $requestId,
                'success' => $aiResult['success'] ?? false
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('AI請求失敗', [
                'action' => $data['action'] ?? 'unknown',
                'requestId' => $data['requestId'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            $this->sendToConnection($conn, [
                'type' => 'ai_response',
                'requestId' => $data['requestId'] ?? 'unknown',
                'action' => $data['action'] ?? 'unknown',
                'success' => false,
                'error' => 'AI分析服務暫時不可用: ' . $e->getMessage(),
                'timestamp' => date('c')
            ]);
        }
    }

    private function handleConflictAnalysisRequest(ConnectionInterface $conn, $data) {
        try {
            // 發送POST請求到AI API
            $postData = [
                'action' => 'conflict',
                'user_code' => $data['userCode'] ?? '',
                'conflict_code' => $data['conflictCode'] ?? '',
                'user_id' => $data['userName'] ?? 'unknown'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost:8081/api/ai');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($response === false || $httpCode !== 200) {
                throw new Exception('AI API請求失敗');
            }
            
            $aiResult = json_decode($response, true);
            
            // 回傳AI分析結果給用戶
            $this->sendToConnection($conn, [
                'type' => 'ai_analysis_result',
                'success' => $aiResult['success'] ?? false,
                'response' => $aiResult['data']['analysis'] ?? null,
                'error' => $aiResult['message'] ?? null
            ]);
            
            $this->logger->info('AI衝突分析完成', [
                'success' => $aiResult['success'] ?? false
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('AI衝突分析失敗', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            $this->sendToConnection($conn, [
                'type' => 'ai_analysis_result',
                'success' => false,
                'error' => 'AI分析服務暫時不可用: ' . $e->getMessage()
            ]);
        }
    }
    
    private function getRoomUsers($roomId) {
        if (!isset($this->rooms[$roomId])) {
            return [];
        }
        
        $users = [];
        foreach ($this->rooms[$roomId] as $conn) {
            if (isset($conn->userId) && isset($conn->username)) {
                $users[] = [
                    'user_id' => $conn->userId,
                    'username' => $conn->username,
                    'connected_at' => $conn->connectedAt ?? time()
                ];
            }
        }
        
        return $users;
    }
    
    private function getUsernameById($roomId, $userId) {
        if (!isset($this->rooms[$roomId])) {
            return '未知用戶';
        }
        
        foreach ($this->rooms[$roomId] as $conn) {
            if (isset($conn->userId) && $conn->userId === $userId) {
                return $conn->username ?? '未知用戶';
            }
        }
        
        return '未知用戶';
    }
    
    private function broadcastUserList($roomId) {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        $userList = [];
        foreach ($this->rooms[$roomId] as $conn) {
            $userList[] = [
                'user_id' => $conn->userId,
                'username' => $conn->username,
                'resource_id' => $conn->resourceId,
                'status' => 'active'
            ];
        }
        
        $this->broadcastToRoom($roomId, [
            'type' => 'user_list_update',
            'users' => $userList,
            'total_users' => count($userList),
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
            $this->logger->error('發送消息失敗', [
                'error' => $e->getMessage(),
                'resource_id' => $conn->resourceId
            ]);
        }
    }
    
    private function sendError(ConnectionInterface $conn, $message) {
        $this->sendToConnection($conn, [
            'type' => 'error',
            'message' => $message
        ]);
    }
    
    private function handleSaveCode(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        $userId = $conn->userId;
        $username = $conn->username;
        $code = $data['code'] ?? '';
        $saveName = $data['save_name'] ?? $data['title'] ?? null;
        $slotId = $data['slot_id'] ?? null;
        
        if (!$roomId || !$userId) {
            $this->sendError($conn, '無效的房間或用戶信息');
            return;
        }

        if (!$this->database) {
            $this->sendError($conn, '數據庫服務不可用');
            return;
        }

        try {
            // 使用 Database 類的 saveCode 方法 (支援槽位系統)
            $result = $this->database->saveCode($roomId, $userId, $code, $saveName, $slotId, $username);
            
            if (!$result['success']) {
                throw new \Exception($result['error'] ?? '保存失敗');
            }

            // 發送保存成功響應
            $this->sendToConnection($conn, [
                'type' => 'save_success',
                'success' => true,
                'message' => "代碼已保存到槽位 {$result['slot_id']}: {$result['save_name']}",
                'history_id' => $result['history_id'],
                'slot_id' => $result['slot_id'],
                'save_name' => $result['save_name'],
                'timestamp' => $result['timestamp'],
                'is_update' => $result['is_update']
            ]);
            
            // 通知房間其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'code_saved_notification',
                'user_id' => $userId,
                'username' => $username,
                'save_name' => $result['save_name'],
                'slot_id' => $result['slot_id'],
                'is_update' => $result['is_update'],
                'timestamp' => date('c')
            ], $conn);

            echo "✅ 代碼保存成功: 用戶 {$username} 在房間 {$roomId} 保存到槽位 {$result['slot_id']}\n";

        } catch (\Exception $e) {
            $this->logger->error('代碼保存失敗', [
                'error' => $e->getMessage(),
                'room_id' => $roomId,
                'user_id' => $userId
            ]);
            
            echo "❌ 代碼保存失敗: {$e->getMessage()}\n";
            $this->sendError($conn, '代碼保存失敗: ' . $e->getMessage());
        }
    }
    
    private function handleLoadCode(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        $slotId = $data['slot_id'] ?? $data['history_id'] ?? null;
        $loadLatest = $data['loadLatest'] ?? false;

        if (!$roomId) {
            $this->sendError($conn, '尚未加入任何房間');
            return;
        }

        if (!$this->database) {
            $this->sendError($conn, '數據庫服務不可用');
            return;
        }

        try {
            $result = null;

            if ($slotId !== null) {
                // 載入特定槽位
                $result = $this->database->loadCode($roomId, intval($slotId));
            } else {
                // 載入最新版本或房間當前代碼
                $result = $this->database->loadCode($roomId);
            }

            if (!$result || !isset($result['code'])) {
                // 如果沒有任何代碼，發送預設代碼
                 $this->sendToConnection($conn, [
                    'type' => 'code_loaded',
                    'success' => true,
                    'code' => "# 歡迎使用Python協作平台\nprint(\"Hello, World!\")",
                    'slot_id' => 0,
                    'save_name' => '預設代碼',
                    'last_saved_by' => '系統',
                    'last_saved_at' => date('c'),
                    'timestamp' => date('c')
                ]);
                echo "📂 載入預設代碼給用戶 {$conn->username} (房間: {$roomId})\n";
                return;
            }
            
            $this->sendToConnection($conn, [
                'type' => 'code_loaded',
                'success' => true,
                'code' => $result['code'],
                'slot_id' => $result['slot_id'] ?? 0,
                'save_name' => $result['save_name'] ?? '代碼載入',
                'last_saved_by' => $result['username'] ?? '未知',
                'last_saved_at' => $result['timestamp'] ?? date('c'),
                'timestamp' => date('c')
            ]);

            // 通知房間其他用戶
            $this->broadcastToRoom($roomId, [
                'type' => 'code_loaded_notification',
                'user_id' => $conn->userId,
                'username' => $conn->username,
                'save_name' => $result['save_name'] ?? '代碼載入',
                'slot_id' => $result['slot_id'] ?? 0,
                'timestamp' => date('c')
            ], $conn);

            echo "✅ 代碼載入成功: 用戶 {$conn->username} 載入槽位 " . ($result['slot_id'] ?? 0) . " (房間: {$roomId})\n";

        } catch (\Exception $e) {
             $this->logger->error('代碼載入失敗', [
                'error' => $e->getMessage(),
                'room_id' => $roomId,
                'slot_id' => $slotId
            ]);
            
            echo "❌ 代碼載入失敗: {$e->getMessage()}\n";
            $this->sendError($conn, '代碼載入失敗: ' . $e->getMessage());
        }
    }
    
    private function handleRunCode(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        $code = $data['code'] ?? '';
        $input = $data['input'] ?? '';
        
        if (!$roomId) {
            $this->sendError($conn, '您未加入任何房間');
            return;
        }
        
        if (empty(trim($code))) {
            $this->sendToConnection($conn, [
                'type' => 'code_execution_result',
                'success' => false,
                'error' => '代碼為空，請輸入要執行的Python代碼',
                'error_type' => 'empty_code',
                'output' => '',
                'execution_time' => 0,
                'timestamp' => date('c')
            ]);
            return;
        }
        
        try {
            echo "🚀 開始執行Python代碼: 用戶 {$conn->username} 在房間 {$roomId}\n";
            
            // 初始化Python執行器
            require_once __DIR__ . '/../classes/PythonExecutor.php';
            $executor = new PythonExecutor([
                'max_execution_time' => 10,
                'max_memory_mb' => 128
            ]);
            
            // 執行代碼
            $result = $executor->execute($code, $input);
            
            // 記錄執行請求到數據庫
            if ($this->database) {
                try {
                    $insertSql = "INSERT INTO code_executions (room_id, user_id, code, output, error, success, execution_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    $this->database->query($insertSql, [
                        $roomId,
                        $conn->userId,
                        $code,
                        $result['output'],
                        $result['error'],
                        $result['success'] ? 1 : 0,
                        $result['execution_time']
                    ]);
                    echo "✅ 代碼執行記錄已保存到數據庫\n";
                } catch (Exception $dbError) {
                    echo "⚠️ 數據庫記錄失敗: " . $dbError->getMessage() . "\n";
                }
            }
            
            // 發送執行結果給用戶
            $this->sendToConnection($conn, [
                'type' => 'code_execution_result',
                'success' => $result['success'],
                'output' => $result['output'],
                'error' => $result['error'],
                'error_type' => $result['error_type'],
                'execution_time' => $result['execution_time'],
                'timestamp' => date('c')
            ]);
            
            // 通知房間其他用戶有人執行了代碼
            $this->broadcastToRoom($roomId, [
                'type' => 'user_executed_code',
                'user_id' => $conn->userId,
                'username' => $conn->username,
                'success' => $result['success'],
                'execution_time' => $result['execution_time'],
                'timestamp' => date('c')
            ], $conn);
            
            $statusIcon = $result['success'] ? '✅' : '❌';
            echo "{$statusIcon} 代碼執行完成: 用戶 {$conn->username}, 耗時 {$result['execution_time']}ms\n";
            
            $this->logger->info('代碼執行完成', [
                'room_id' => $roomId,
                'user_id' => $conn->userId,
                'success' => $result['success'],
                'execution_time' => $result['execution_time'],
                'code_length' => strlen($code),
                'error_type' => $result['error_type']
            ]);
            
        } catch (Exception $e) {
            echo "❌ 代碼執行器錯誤: " . $e->getMessage() . "\n";
            
            $this->logger->error('代碼執行失敗', [
                'error' => $e->getMessage(),
                'room_id' => $roomId,
                'user_id' => $conn->userId
            ]);
            
            $this->sendToConnection($conn, [
                'type' => 'code_execution_result',
                'success' => false,
                'error' => '代碼執行失敗: ' . $e->getMessage(),
                'error_type' => 'executor_error',
                'output' => '',
                'execution_time' => 0,
                'timestamp' => date('c')
            ]);
        }
    }

    private function handleGetHistory(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;

        if (!$roomId) {
            $this->sendError($conn, '尚未加入任何房間');
            return;
        }

        if (!$this->database) {
            $this->sendError($conn, '數據庫服務不可用');
            return;
        }

        try {
            // 使用 Database 類的 getCodeHistory 方法
            $historyResult = $this->database->getCodeHistory($roomId, 5);

            $formattedHistory = [];
            if ($historyResult && $historyResult['success'] && !empty($historyResult['history'])) {
                // 格式化歷史數據以匹配前端期望
                $formattedHistory = array_map(function($item) {
                    return [
                        'slot_id' => $item['slot_id'],
                        'id' => $item['id'],
                        'save_name' => $item['save_name'],
                        'user_id' => $item['user_id'],
                        'username' => $item['username'],
                        'code_content' => $item['code_content'],
                        'created_at' => $item['created_at'],
                        'is_empty' => $item['is_empty'],
                        // 向後兼容
                        'title' => $item['save_name'],
                        'author' => $item['username'],
                        'timestamp' => $item['created_at'],
                        'code' => $item['code_content']
                    ];
                }, $historyResult['history']);
            }

            $this->sendToConnection($conn, [
                'type' => 'history_data',
                'success' => true,
                'history' => $formattedHistory,
                'count' => count($formattedHistory)
            ]);

            echo "📜 歷史記錄查詢成功: 用戶 {$conn->username} 獲取房間 {$roomId} 的 5 槽位記錄\n";

        } catch (\Exception $e) {
            $this->logger->error('獲取歷史紀錄失敗', [
                'error' => $e->getMessage(),
                'room_id' => $roomId
            ]);
            
            echo "❌ 歷史記錄查詢失敗: {$e->getMessage()}\n";
            $this->sendError($conn, '獲取歷史紀錄失敗: ' . $e->getMessage());
        }
    }
    
    private function handleDeleteSlot(ConnectionInterface $conn, $data) {
        $roomId = $conn->roomId;
        $slotId = $data['slot_id'] ?? null;
        
        if (!$roomId) {
            $this->sendError($conn, '尚未加入任何房間');
            return;
        }
        
        if ($slotId === null || $slotId < 1 || $slotId > 4) {
            $this->sendError($conn, '無效的槽位ID，只能刪除槽位1-4');
            return;
        }
        
        if (!$this->database) {
            $this->sendError($conn, '數據庫服務不可用');
            return;
        }
        
        try {
            $result = $this->database->deleteCodeSlot($roomId, $slotId);
            
            if ($result['success']) {
                // 發送刪除成功響應
                $this->sendToConnection($conn, [
                    'type' => 'slot_deleted',
                    'success' => true,
                    'slot_id' => $slotId,
                    'message' => "槽位 {$slotId} 已成功刪除"
                ]);
                
                // 廣播給房間其他用戶
                $this->broadcastToRoom($roomId, [
                    'type' => 'slot_deleted_notification',
                    'user_id' => $conn->userId,
                    'username' => $conn->username,
                    'slot_id' => $slotId,
                    'timestamp' => date('c')
                ], $conn);
                
                echo "🗑️ 槽位刪除成功: 用戶 {$conn->username} 刪除了房間 {$roomId} 的槽位 {$slotId}\n";
                
            } else {
                $this->sendError($conn, $result['error'] ?? '刪除槽位失敗');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('刪除槽位失敗', [
                'error' => $e->getMessage(),
                'room_id' => $roomId,
                'slot_id' => $slotId
            ]);
            
            echo "❌ 刪除槽位失敗: {$e->getMessage()}\n";
            $this->sendError($conn, '刪除槽位失敗: ' . $e->getMessage());
        }
    }
    
    private function handlePing(ConnectionInterface $conn, $data) {
        // 響應心跳包
        $this->sendToConnection($conn, [
            'type' => 'pong',
            'timestamp' => date('c')
        ]);
        
        // 可選：記錄心跳日誌（通常不需要）
        // $this->logger->debug('收到心跳', ['resource_id' => $conn->resourceId]);
    }
}

// 獲取環境變數配置
$host = $_ENV['WEBSOCKET_HOST'] ?? '0.0.0.0';
$port = $_ENV['WEBSOCKET_PORT'] ?? 8081;

// 啟動WebSocket服務器
echo "WebSocket服務器啟動在 {$host}:{$port}\n";
echo "環境: " . (isset($_ENV['ZEABUR_DOMAIN']) ? '雲端' : '本地') . "\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new CodeCollaborationServer()
        )
    ),
    intval($port),
    $host
);

$server->run(); 