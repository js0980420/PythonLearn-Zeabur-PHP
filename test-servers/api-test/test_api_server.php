<?php
/**
 * 獨立API測試服務器
 * 端口：9081
 * 用途：測試API功能而不影響主服務器
 */

// 設置錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../test-logs/api_test_errors.log');

// CORS設置
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 測試配置
define('TEST_MODE', true);
define('TEST_DB_FILE', __DIR__ . '/../../test-logs/test_database.json');

class TestAPIServer {
    private $testData = [];
    
    public function __construct() {
        $this->loadTestData();
        $this->logRequest();
    }
    
    private function loadTestData() {
        if (file_exists(TEST_DB_FILE)) {
            $this->testData = json_decode(file_get_contents(TEST_DB_FILE), true) ?: [];
        } else {
            $this->testData = [
                'users' => [],
                'rooms' => [],
                'code_history' => []
            ];
        }
    }
    
    private function saveTestData() {
        file_put_contents(TEST_DB_FILE, json_encode($this->testData, JSON_PRETTY_PRINT));
    }
    
    private function logRequest() {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'headers' => getallheaders(),
            'body' => file_get_contents('php://input')
        ];
        
        $logFile = __DIR__ . '/../../test-logs/api_test_requests.log';
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND);
    }
    
    public function handleRequest() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($path) {
                case '/api/auth':
                    return $this->handleAuth();
                case '/api/room':
                    return $this->handleRoom();
                case '/api/code':
                    return $this->handleCode();
                case '/api/ai':
                    return $this->handleAI();
                case '/api/status':
                    return $this->handleStatus();
                default:
                    return $this->sendError('API端點不存在', 404);
            }
        } catch (Exception $e) {
            return $this->sendError('服務器錯誤: ' . $e->getMessage(), 500);
        }
    }
    
    private function handleAuth() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $input['username'] ?? '';
            $userType = $input['user_type'] ?? 'student';
            
            if (empty($username)) {
                return $this->sendError('用戶名不能為空');
            }
            
            $userId = 'test_' . uniqid();
            $user = [
                'user_id' => $userId,
                'username' => $username,
                'user_type' => $userType,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->testData['users'][$userId] = $user;
            $this->saveTestData();
            
            return $this->sendSuccess($user, '登入成功');
        }
        
        return $this->sendError('不支援的請求方法');
    }
    
    private function handleRoom() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'create':
                    $roomName = $input['room_name'] ?? '測試房間';
                    $roomId = 'test_room_' . uniqid();
                    
                    $room = [
                        'room_id' => $roomId,
                        'room_name' => $roomName,
                        'current_code' => '# 測試房間預設代碼\nprint("Hello, Test World!")',
                        'created_at' => date('Y-m-d H:i:s'),
                        'users' => []
                    ];
                    
                    $this->testData['rooms'][$roomId] = $room;
                    $this->saveTestData();
                    
                    return $this->sendSuccess($room, '房間創建成功');
                    
                case 'join':
                    $roomId = $input['room_id'] ?? '';
                    $userId = $input['user_id'] ?? '';
                    
                    if (!isset($this->testData['rooms'][$roomId])) {
                        return $this->sendError('房間不存在');
                    }
                    
                    $this->testData['rooms'][$roomId]['users'][] = $userId;
                    $this->saveTestData();
                    
                    return $this->sendSuccess($this->testData['rooms'][$roomId], '加入房間成功');
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->sendSuccess(array_values($this->testData['rooms']), '房間列表');
        }
        
        return $this->sendError('不支援的請求');
    }
    
    private function handleCode() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'save':
                    $roomId = $input['room_id'] ?? '';
                    $code = $input['code'] ?? '';
                    $userId = $input['user_id'] ?? '';
                    
                    if (empty($roomId) || empty($code)) {
                        return $this->sendError('房間ID和代碼不能為空');
                    }
                    
                    $historyId = uniqid();
                    $history = [
                        'id' => $historyId,
                        'room_id' => $roomId,
                        'user_id' => $userId,
                        'code' => $code,
                        'version' => count($this->testData['code_history']) + 1,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $this->testData['code_history'][$historyId] = $history;
                    
                    // 更新房間當前代碼
                    if (isset($this->testData['rooms'][$roomId])) {
                        $this->testData['rooms'][$roomId]['current_code'] = $code;
                    }
                    
                    $this->saveTestData();
                    
                    return $this->sendSuccess($history, '代碼保存成功');
                    
                case 'execute':
                    $code = $input['code'] ?? '';
                    
                    // 模擬代碼執行
                    $output = "測試執行結果:\n";
                    if (strpos($code, 'print') !== false) {
                        $output .= "Hello, Test World!\n";
                    }
                    $output .= "代碼執行完成 (模擬)";
                    
                    return $this->sendSuccess([
                        'output' => $output,
                        'execution_time' => 0.001,
                        'status' => 'success'
                    ], '代碼執行成功');
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $roomId = $_GET['room_id'] ?? '';
            
            if (isset($this->testData['rooms'][$roomId])) {
                return $this->sendSuccess([
                    'code' => $this->testData['rooms'][$roomId]['current_code'],
                    'room_id' => $roomId
                ], '代碼載入成功');
            }
            
            return $this->sendError('房間不存在');
        }
        
        return $this->sendError('不支援的請求');
    }
    
    private function handleAI() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $input['action'] ?? '';
            $code = $input['code'] ?? '';
            $question = $input['question'] ?? '';
            
            // 模擬AI助教功能
            switch ($action) {
                case 'explain':
                    if (empty($code)) {
                        return $this->sendError('代碼不能為空');
                    }
                    
                    $mockResponse = [
                        'explanation' => "這是一個測試AI解釋：\n\n您的代碼包含以下功能：\n1. 定義了一個函數\n2. 使用了基本的Python語法\n3. 包含輸出語句\n\n這是一個很好的Python代碼範例！",
                        'complexity' => 'simple',
                        'suggestions' => ['添加註釋', '考慮錯誤處理'],
                        'token_usage' => 150
                    ];
                    
                    return $this->sendSuccess($mockResponse, 'AI解釋完成');
                    
                case 'check_errors':
                    if (empty($code)) {
                        return $this->sendError('代碼不能為空');
                    }
                    
                    $mockResponse = [
                        'errors' => [
                            [
                                'type' => 'syntax',
                                'line' => 3,
                                'message' => '建議添加註釋說明函數用途',
                                'severity' => 'low'
                            ]
                        ],
                        'warnings' => [
                            [
                                'type' => 'style',
                                'line' => 1,
                                'message' => '函數名稱建議使用snake_case格式',
                                'severity' => 'medium'
                            ]
                        ],
                        'suggestions' => ['代碼結構良好', '建議添加文檔字符串'],
                        'token_usage' => 120
                    ];
                    
                    return $this->sendSuccess($mockResponse, 'AI檢查完成');
                    
                case 'suggest_improvements':
                    if (empty($code)) {
                        return $this->sendError('代碼不能為空');
                    }
                    
                    $mockResponse = [
                        'improvements' => [
                            [
                                'category' => 'performance',
                                'suggestion' => '可以使用記憶化來優化遞歸函數',
                                'example' => 'from functools import lru_cache\n@lru_cache(maxsize=None)\ndef fibonacci(n):'
                            ],
                            [
                                'category' => 'readability',
                                'suggestion' => '添加類型提示',
                                'example' => 'def fibonacci(n: int) -> int:'
                            ]
                        ],
                        'overall_score' => 7.5,
                        'token_usage' => 180
                    ];
                    
                    return $this->sendSuccess($mockResponse, 'AI改進建議完成');
                    
                case 'question':
                case 'ask':
                    if (empty($question)) {
                        return $this->sendError('問題不能為空');
                    }
                    
                    $mockResponse = [
                        'answer' => "這是AI助教的測試回答：\n\n關於您的問題「{$question}」，我的建議是：\n\n1. 首先理解基本概念\n2. 通過實際練習加深理解\n3. 查閱相關文檔和資料\n4. 多寫代碼實踐\n\n希望這個回答對您有幫助！如果還有其他問題，請隨時詢問。",
                        'confidence' => 0.85,
                        'related_topics' => ['Python基礎', '函數定義', '程式設計'],
                        'token_usage' => 200
                    ];
                    
                    return $this->sendSuccess($mockResponse, 'AI回答完成');
                    
                case 'conflict':
                    $originalCode = $input['original_code'] ?? '';
                    $conflictedCode = $input['conflicted_code'] ?? '';
                    
                    if (empty($originalCode) || empty($conflictedCode)) {
                        return $this->sendError('原始代碼和衝突代碼都不能為空');
                    }
                    
                    $mockResponse = [
                        'analysis' => '檢測到代碼衝突，建議採用以下解決方案：',
                        'resolution' => 'merge',
                        'merged_code' => $conflictedCode, // 簡單返回衝突代碼
                        'explanation' => '這個衝突可以通過合併兩個版本來解決',
                        'token_usage' => 160
                    ];
                    
                    return $this->sendSuccess($mockResponse, 'AI衝突分析完成');
                    
                default:
                    return $this->sendError('不支援的AI操作');
            }
        }
        
        return $this->sendError('不支援的請求方法');
    }
    
    private function handleStatus() {
        $status = [
            'status' => 'healthy',
            'server' => 'test-api-server',
            'port' => 9081,
            'timestamp' => date('c'),
            'test_data' => [
                'users_count' => count($this->testData['users']),
                'rooms_count' => count($this->testData['rooms']),
                'history_count' => count($this->testData['code_history'])
            ]
        ];
        
        return $this->sendSuccess($status, '測試服務器運行正常');
    }
    
    private function sendSuccess($data = null, $message = '操作成功') {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
            'server' => 'test-api-server'
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return true;
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => date('c'),
            'server' => 'test-api-server'
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return false;
    }
}

// 啟動測試服務器
$server = new TestAPIServer();
$server->handleRequest();
?> 