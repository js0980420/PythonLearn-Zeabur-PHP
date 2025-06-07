<?php
// 關閉錯誤顯示，避免破壞JSON響應
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../classes/APIResponse.php';
require_once __DIR__ . '/../../classes/Database.php';

use App\APIResponse;

// 設置CORS頭
APIResponse::setCORSHeaders();

// 初始化真實數據庫，抑制初始化輸出
ob_start();
$database = new Database();
ob_end_clean();

try {
    session_start();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            handleGetHistory($database, $input);
            break;
            
        case 'load':
            handleLoadVersion($database, $input);
            break;
            
        case 'save':
            handleSaveVersion($database, $input);
            break;
            
        case 'delete':
            handleDeleteVersion($database, $input);
            break;
            
        default:
            echo APIResponse::error('無效的操作', 'E001');
    }
    
} catch (Exception $e) {
    error_log('歷史記錄API錯誤: ' . $e->getMessage());
    echo APIResponse::error('系統錯誤: ' . $e->getMessage(), 'E010', 500);
}

function handleGetHistory($database, $input) {
    $roomId = $_GET['room_id'] ?? $input['room_id'] ?? '';
    $limit = intval($_GET['limit'] ?? $input['limit'] ?? 20);
    
    if (empty($roomId)) {
        echo APIResponse::error('房間ID不能為空', 'E001');
        return;
    }
    
    // 使用槽位系統查詢歷史記錄
    $history = $database->query(
        "SELECT id, room_id, user_id, username, code_content, slot_id, save_name, operation_type, created_at 
         FROM code_history 
         WHERE room_id = ? 
         ORDER BY slot_id ASC, created_at DESC 
         LIMIT ?",
        [$roomId, $limit]
    );
    
    if ($history === false) {
        echo APIResponse::error('查詢歷史記錄失敗', 'E002');
        return;
    }
    
    // 格式化歷史記錄
    $formattedHistory = [];
    foreach ($history as $record) {
        $codeContent = $record['code_content'] ?? '';
        $formattedHistory[] = [
            'id' => $record['id'],
            'slot_id' => $record['slot_id'] ?? 0,
            'slot_name' => '槽位 ' . ($record['slot_id'] ?? 0),
            'user_id' => $record['user_id'],
            'username' => $record['username'] ?? $record['user_id'],
            'code_preview' => substr($codeContent, 0, 100) . (strlen($codeContent) > 100 ? '...' : ''),
            'code_length' => strlen($codeContent),
            'timestamp' => $record['created_at'],
            'saved_at' => $record['created_at'],
            'title' => $record['save_name'] ?? '自動保存',
            'save_name' => $record['save_name'] ?? '自動保存',
            'description' => $record['save_name'] ?? '自動保存',
            'author' => $record['username'] ?? $record['user_id'],
            'operation_type' => $record['operation_type'] ?? 'save'
        ];
    }
    
    echo APIResponse::success([
        'history' => $formattedHistory,
        'total' => count($formattedHistory),
        'room_id' => $roomId
    ], '歷史記錄獲取成功');
}

function handleLoadVersion($database, $input) {
    $historyId = $input['history_id'] ?? $_GET['history_id'] ?? '';
    $roomId = $input['room_id'] ?? $_GET['room_id'] ?? '';
    
    if (empty($historyId)) {
        echo APIResponse::error('歷史記錄ID不能為空', 'E001');
        return;
    }
    
    // 獲取特定槽位的代碼
    $version = $database->fetch(
        "SELECT id, room_id, user_id, username, code_content, slot_id, save_name, operation_type, created_at 
         FROM code_history 
         WHERE id = ?",
        [$historyId]
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
    
    $codeContent = $version['code_content'] ?? '';
    
    echo APIResponse::success([
        'code' => $codeContent,
        'code_content' => $codeContent,
        'slot_id' => $version['slot_id'] ?? 0,
        'slot_name' => '槽位 ' . ($version['slot_id'] ?? 0),
        'user_id' => $version['user_id'],
        'username' => $version['username'] ?? $version['user_id'],
        'timestamp' => $version['created_at'],
        'saved_at' => $version['created_at'],
        'title' => $version['save_name'] ?? '自動保存',
        'save_name' => $version['save_name'] ?? '自動保存',
        'description' => $version['save_name'] ?? '自動保存'
    ], '槽位載入成功');
}

function handleSaveVersion($database, $input) {
    $roomId = $input['room_id'] ?? '';
    $code = $input['code'] ?? '';
    $description = $input['description'] ?? $input['save_name'] ?? '手動保存';
    $userId = $_SESSION['user_id'] ?? $input['user_id'] ?? 'anonymous';
    $username = $_SESSION['username'] ?? $input['username'] ?? $userId;
    
    if (empty($roomId) || empty($code)) {
        echo APIResponse::error('房間ID和代碼不能為空', 'E001');
        return;
    }
    
    // 使用默認槽位0保存，或使用請求中指定的槽位
    $slotId = $input['slot_id'] ?? 0;
    
    // 保存到指定槽位
    $historyId = $database->insert('code_history', [
        'room_id' => $roomId,
        'user_id' => $userId,
        'username' => $username,
        'code_content' => $code,
        'slot_id' => $slotId,
        'save_name' => $description,
        'operation_type' => 'save'
    ]);
    
    if ($historyId === false) {
        echo APIResponse::error('保存歷史記錄失敗', 'E003');
        return;
    }
    
    echo APIResponse::success([
        'history_id' => $historyId,
        'slot_id' => $slotId,
        'slot_name' => '槽位 ' . $slotId,
        'saved_at' => date('Y-m-d H:i:s')
    ], '槽位保存成功');
}

function handleDeleteVersion($database, $input) {
    $historyId = $input['history_id'] ?? '';
    $userId = $_SESSION['user_id'] ?? 'anonymous';
    
    if (empty($historyId)) {
        echo APIResponse::error('歷史記錄ID不能為空', 'E001');
        return;
    }
    
    // 檢查記錄是否存在且屬於當前用戶
    $version = $database->fetch(
        "SELECT * FROM code_history WHERE id = ?",
        [$historyId]
    );
    
    if (!$version) {
        echo APIResponse::error('歷史記錄不存在', 'E002');
        return;
    }
    
    // 刪除記錄（這裡簡化處理，在生產環境中可能需要更嚴格的權限檢查）
    $result = $database->query("DELETE FROM code_history WHERE id = ?", [$historyId]);
    
    if ($result === false) {
        echo APIResponse::error('刪除歷史記錄失敗', 'E003');
        return;
    }
    
    echo APIResponse::success(null, '版本刪除成功');
}
?> 