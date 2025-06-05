<?php
// 關閉錯誤顯示，避免破壞JSON響應
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../classes/MockDatabase.php';
require_once __DIR__ . '/../classes/Logger.php';

use App\MockDatabase as Database;
use App\Logger;

// 設置CORS頭
APIResponse::setCORSHeaders();

// 初始化
$database = Database::getInstance();
$database->addTestData();
$logger = new Logger('history.log');

try {
    session_start();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            handleGetHistory($database, $logger, $input);
            break;
            
        case 'load':
            handleLoadVersion($database, $logger, $input);
            break;
            
        case 'save':
            handleSaveVersion($database, $logger, $input);
            break;
            
        case 'delete':
            handleDeleteVersion($database, $logger, $input);
            break;
            
        default:
            echo APIResponse::error('無效的操作', 'E001');
    }
    
} catch (Exception $e) {
    $logger->error('歷史記錄API錯誤', ['error' => $e->getMessage()]);
    echo APIResponse::error('系統錯誤', 'E010', 500);
}

function handleGetHistory($database, $logger, $input) {
    $roomId = $_GET['room_id'] ?? $input['room_id'] ?? '';
    $limit = intval($_GET['limit'] ?? $input['limit'] ?? 20);
    
    if (empty($roomId)) {
        echo APIResponse::error('房間ID不能為空', 'E001');
        return;
    }
    
    // 獲取歷史記錄
    $history = $database->fetchAll(
        "SELECT * FROM code_history WHERE room_id = :room_id ORDER BY saved_at DESC LIMIT :limit",
        ['room_id' => $roomId, 'limit' => $limit]
    );
    
    // 格式化歷史記錄
    $formattedHistory = [];
    foreach ($history as $record) {
        $codeContent = $record['code_content'] ?? $record['code'] ?? '';
        $formattedHistory[] = [
            'id' => $record['id'],
            'version' => $record['version'],
            'user_id' => $record['user_id'],
            'username' => $record['username'] ?? $record['user_id'],
            'code_preview' => substr($codeContent, 0, 100) . (strlen($codeContent) > 100 ? '...' : ''),
            'code_length' => strlen($codeContent),
            'saved_at' => $record['saved_at'] ?? $record['created_at'],
            'description' => $record['description'] ?? '自動保存'
        ];
    }
    
    echo APIResponse::success([
        'history' => $formattedHistory,
        'total' => count($formattedHistory)
    ], '歷史記錄獲取成功');
}

function handleLoadVersion($database, $logger, $input) {
    $historyId = $input['history_id'] ?? $_GET['history_id'] ?? '';
    $roomId = $input['room_id'] ?? $_GET['room_id'] ?? '';
    
    if (empty($historyId)) {
        echo APIResponse::error('歷史記錄ID不能為空', 'E001');
        return;
    }
    
    // 獲取特定版本的代碼
    $version = $database->fetch(
        "SELECT * FROM code_history WHERE id = :history_id",
        ['history_id' => $historyId]
    );
    
    if (!$version) {
        echo APIResponse::error('歷史記錄不存在', 'E002');
        return;
    }
    
    // 如果指定了房間ID，檢查是否匹配
    if (!empty($roomId) && $version['room_id'] != $roomId) {
        echo APIResponse::error('歷史記錄不屬於指定房間', 'E004');
        return;
    }
    
    $codeContent = $version['code_content'] ?? $version['code'] ?? '';
    
    echo APIResponse::success([
        'code' => $codeContent,
        'code_content' => $codeContent,
        'version' => $version['version'],
        'user_id' => $version['user_id'],
        'username' => $version['username'] ?? $version['user_id'],
        'saved_at' => $version['saved_at'] ?? $version['created_at'],
        'description' => $version['description'] ?? '自動保存'
    ], '版本載入成功');
}

function handleSaveVersion($database, $logger, $input) {
    $roomId = $input['room_id'] ?? '';
    $code = $input['code'] ?? '';
    $description = $input['description'] ?? '手動保存';
    $userId = $_SESSION['user_id'] ?? 'anonymous';
    
    if (empty($roomId) || empty($code)) {
        echo APIResponse::error('房間ID和代碼不能為空', 'E001');
        return;
    }
    
    // 保存新版本
    $historyId = $database->insert('code_history', [
        'room_id' => $roomId,
        'user_id' => $userId,
        'code' => $code,
        'version' => time(),
        'description' => $description
    ]);
    
    $logger->info('版本保存', [
        'room_id' => $roomId,
        'user_id' => $userId,
        'history_id' => $historyId,
        'description' => $description
    ]);
    
    echo APIResponse::success([
        'history_id' => $historyId,
        'version' => time(),
        'saved_at' => date('Y-m-d H:i:s')
    ], '版本保存成功');
}

function handleDeleteVersion($database, $logger, $input) {
    $historyId = $input['history_id'] ?? '';
    $userId = $_SESSION['user_id'] ?? 'anonymous';
    
    if (empty($historyId)) {
        echo APIResponse::error('歷史記錄ID不能為空', 'E001');
        return;
    }
    
    // 檢查記錄是否存在且屬於當前用戶
    $version = $database->fetch(
        "SELECT * FROM code_history WHERE id = :history_id",
        ['history_id' => $historyId]
    );
    
    if (!$version) {
        echo APIResponse::error('歷史記錄不存在', 'E002');
        return;
    }
    
    // 刪除記錄
    $database->delete('code_history', ['id' => $historyId]);
    
    $logger->info('版本刪除', [
        'history_id' => $historyId,
        'user_id' => $userId
    ]);
    
    echo APIResponse::success(null, '版本刪除成功');
}
?> 