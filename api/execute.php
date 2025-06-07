<?php
/**
 * Python代碼執行API
 * 提供安全的Python代碼執行服務
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 只允許POST請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => '只允許POST請求',
        'error_type' => 'method_not_allowed'
    ]);
    exit;
}

try {
    // 獲取請求數據
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('無效的JSON數據');
    }
    
    $code = $data['code'] ?? '';
    $userInput = $data['input'] ?? '';
    $roomId = $data['room_id'] ?? '';
    $userId = $data['user_id'] ?? '';
    
    if (empty(trim($code))) {
        echo json_encode([
            'success' => false,
            'error' => '代碼不能為空',
            'error_type' => 'empty_code',
            'output' => '',
            'execution_time' => 0
        ]);
        exit;
    }
    
    // 引入必要的類
    require_once __DIR__ . '/../classes/PythonExecutor.php';
    require_once __DIR__ . '/../classes/Database.php';
    
    // 初始化Python執行器
    $executor = new PythonExecutor([
        'max_execution_time' => 10,
        'max_memory_mb' => 128,
        'temp_dir' => sys_get_temp_dir() . '/pythonlearn_api'
    ]);
    
    // 執行代碼
    $result = $executor->execute($code, $userInput);
    
    // 記錄到數據庫（如果提供了房間和用戶信息）
    if (!empty($roomId) && !empty($userId)) {
        try {
            $database = new Database();
            $insertSql = "INSERT INTO code_executions (room_id, user_id, code, output, error, success, execution_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $database->query($insertSql, [
                $roomId,
                $userId,
                $code,
                $result['output'],
                $result['error'],
                $result['success'] ? 1 : 0,
                $result['execution_time']
            ]);
        } catch (Exception $dbError) {
            // 數據庫錯誤不影響代碼執行結果
            error_log('Database error in execute.php: ' . $dbError->getMessage());
        }
    }
    
    // 返回執行結果
    echo json_encode([
        'success' => $result['success'],
        'output' => $result['output'],
        'error' => $result['error'],
        'error_type' => $result['error_type'],
        'execution_time' => $result['execution_time'],
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '服務器錯誤: ' . $e->getMessage(),
        'error_type' => 'server_error',
        'output' => '',
        'execution_time' => 0
    ]);
} 