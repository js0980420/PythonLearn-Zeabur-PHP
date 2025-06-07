<?php
// 禁用錯誤顯示
error_reporting(0);
ini_set('display_errors', 0);

require_once '../classes/APIResponse.php';
require_once '../classes/Database.php';
require_once '../classes/PythonExecutor.php';

use App\APIResponse;
use App\Database;
use App\PythonExecutor;

// 設置CORS頭
APIResponse::setCORSHeaders();

// 初始化
$database = Database::getInstance();

try {
    session_start();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'save':
            handleSaveCode($database, $logger, $input);
            break;
            
        case 'load':
            handleLoadCode($database, $logger, $input);
            break;
            
        case 'execute':
            handleExecuteCode($database, $logger, $input);
            break;
            
        case 'export':
            handleExportCode($database, $logger, $input);
            break;
            
        default:
            echo APIResponse::error('無效的操作', 'E001');
    }
    
} catch (Exception $e) {
    $logger->error('代碼API錯誤', ['error' => $e->getMessage()]);
    echo APIResponse::error('系統錯誤', 'E010', 500);
}

function handleSaveCode($database, $logger, $input) {
    $roomId = $input['room_id'] ?? '';
    $code = $input['code'] ?? '';
    $userId = $input['user_id'] ?? $_SESSION['user_id'] ?? 'anonymous';
    $username = $input['username'] ?? $_SESSION['username'] ?? 'Anonymous';
    $saveName = $input['saveName'] ?? $input['save_name'] ?? null;
    
    if (empty($roomId)) {
        echo APIResponse::error('房間ID不能為空', 'E001');
        return;
    }
    
    // 如果沒有提供保存名稱，生成默認名稱
    if (empty($saveName)) {
        $saveName = '保存 ' . date('Y-m-d H:i:s');
    }
    
    // 保存代碼到歷史記錄
    $historyId = $database->insert('code_history', [
        'room_id' => $roomId,
        'user_id' => $userId,
        'username' => $username,
        'code_content' => $code,
        'save_name' => $saveName,
        'saved_at' => date('Y-m-d H:i:s'),
        'version' => time()
    ]);
    
    // 更新房間的當前代碼
    $existingRoom = $database->fetch(
        "SELECT id FROM rooms WHERE id = :room_id",
        ['room_id' => $roomId]
    );
    
    if ($existingRoom) {
        $database->update(
            'rooms',
            ['current_code' => $code, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $roomId]
        );
    } else {
        // 如果房間不存在，創建新房間
        $database->insert('rooms', [
            'id' => $roomId,
            'room_name' => 'Room ' . $roomId,
            'created_by' => $userId,
            'current_code' => $code,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    $logger->info('代碼保存', [
        'room_id' => $roomId,
        'user_id' => $userId,
        'username' => $username,
        'code_length' => strlen($code),
        'history_id' => $historyId
    ]);
    
    echo APIResponse::success([
        'history_id' => $historyId,
        'saved_at' => date('Y-m-d H:i:s'),
        'room_id' => $roomId
    ], '代碼保存成功');
}

function handleLoadCode($database, $logger, $input) {
    // 對於GET請求，優先從$_GET獲取參數
    $roomId = $_GET['room_id'] ?? $input['room_id'] ?? '';
    
    if (empty($roomId)) {
        echo APIResponse::error('房間ID不能為空', 'E001');
        return;
    }
    
    // 獲取房間的當前代碼
    $room = $database->fetch(
        "SELECT current_code FROM rooms WHERE id = :room_id",
        ['room_id' => $roomId]
    );
    
    if (!$room) {
        // 如果房間不存在，創建一個默認房間
        $database->insert('rooms', [
            'id' => $roomId,
            'room_name' => 'Room ' . $roomId,
            'created_by' => $_SESSION['user_id'] ?? 'anonymous',
            'current_code' => 'print("Hello, World!")'
        ]);
        
        $logger->info('自動創建房間', ['room_id' => $roomId]);
        
        echo APIResponse::success([
            'code' => 'print("Hello, World!")',
            'room_id' => $roomId
        ], '代碼載入成功（新房間）');
        return;
    }
    
    echo APIResponse::success([
        'code' => $room['current_code'] ?? 'print("Hello, World!")',
        'room_id' => $roomId
    ], '代碼載入成功');
}

function handleExecuteCode($database, $logger, $input) {
    $code = $input['code'] ?? '';
    $timeout = intval($input['timeout'] ?? 10);
    
    if (empty($code)) {
        echo APIResponse::error('代碼不能為空', 'E001');
        return;
    }
    
    // 使用PythonExecutor執行代碼
    $executor = new PythonExecutor();
    $output = $executor->execute($code, $timeout);
    
    $logger->info('代碼執行', [
        'code_length' => strlen($code),
        'output_length' => strlen($output)
    ]);
    
    echo APIResponse::success([
        'output' => $output,
        'execution_time' => $executor->getExecutionTime(),
        'memory_usage' => $executor->getMemoryUsage()
    ], '代碼執行完成');
}

function handleExportCode($database, $logger, $input) {
    // 對於GET請求，優先從$_GET獲取參數
    $roomId = $_GET['room_id'] ?? $input['room_id'] ?? '';
    $format = $_GET['format'] ?? $input['format'] ?? 'py';
    
    if (empty($roomId)) {
        echo APIResponse::error('房間ID不能為空', 'E001');
        return;
    }
    
    $room = $database->fetch(
        "SELECT current_code, room_name FROM rooms WHERE id = :room_id",
        ['room_id' => $roomId]
    );
    
    if (!$room) {
        echo APIResponse::error('房間不存在', 'E002');
        return;
    }
    
    $filename = ($room['room_name'] ?? 'code') . '.' . $format;
    
    echo APIResponse::success([
        'code' => $room['current_code'] ?? '',
        'filename' => $filename,
        'format' => $format
    ], '代碼導出成功');
}
?> 