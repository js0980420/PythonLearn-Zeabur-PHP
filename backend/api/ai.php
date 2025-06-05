<?php
// 關閉錯誤顯示，避免破壞JSON響應
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../classes/MockDatabase.php';
require_once __DIR__ . '/../classes/AIAssistant.php';
require_once __DIR__ . '/../classes/Logger.php';

use App\MockDatabase as Database;
use App\Logger;
use App\AIAssistant;

// 設置CORS頭
APIResponse::setCORSHeaders();

// 初始化
$database = Database::getInstance();
$database->addTestData(); // 添加測試數據
$logger = new Logger('ai.log');
$aiAssistant = new AIAssistant();

try {
    // 啟動session，但如果失敗則使用測試用戶
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 檢查用戶是否登入，如果沒有則設置測試用戶
    if (!isset($_SESSION['user_id'])) {
        // 設定測試用戶ID以便AI功能正常運作
        $_SESSION['user_id'] = 'test_user_' . time();
        $_SESSION['username'] = '測試用戶';
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'explain':
            handleExplainCode($aiAssistant, $database, $logger, $input);
            break;
            
        case 'check_errors':
            handleCheckErrors($aiAssistant, $database, $logger, $input);
            break;
            
        case 'suggest_improvements':
            handleSuggestImprovements($aiAssistant, $database, $logger, $input);
            break;
            
        case 'conflict':
            handleConflictAnalysis($aiAssistant, $database, $logger, $input);
            break;
            
        case 'question':
            handleQuestion($aiAssistant, $database, $logger, $input);
            break;
            
        case 'history':
            handleAIHistory($database, $logger, $input);
            break;
            
        default:
            echo APIResponse::error('無效的操作', 'E001');
    }
    
} catch (Exception $e) {
    $logger->error('AI API錯誤', ['error' => $e->getMessage()]);
    echo APIResponse::error('系統錯誤', 'E010', 500);
}

function handleExplainCode($aiAssistant, $database, $logger, $input) {
    $code = $input['code'] ?? '';
    $roomId = intval($input['room_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    if (empty($code)) {
        echo APIResponse::error('代碼不能為空', 'E001');
        return;
    }
    
    try {
        $result = $aiAssistant->explainCode($code, $userId);
        
        // 記錄AI請求
        $database->insert('ai_requests', [
            'user_id' => $userId,
            'room_id' => $roomId ?: null,
            'request_type' => 'explain',
            'request_data' => json_encode(['code' => $code]),
            'response_data' => json_encode($result)
        ]);
        
        $logger->info('AI解釋代碼', [
            'user_id' => $userId,
            'room_id' => $roomId,
            'code_length' => strlen($code)
        ]);
        
        echo APIResponse::success($result, 'AI解釋完成');
        
    } catch (Exception $e) {
        $logger->error('AI解釋代碼失敗', ['error' => $e->getMessage()]);
        echo APIResponse::error('AI服務暫時不可用', 'E020', 503);
    }
}

function handleCheckErrors($aiAssistant, $database, $logger, $input) {
    $code = $input['code'] ?? '';
    $roomId = intval($input['room_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    $errorTypes = $input['error_types'] ?? ['syntax', 'logic', 'performance', 'security'];

    if (empty($code)) {
        echo APIResponse::error('代碼不能為空', 'E001');
        return;
    }
    
    try {
        $result = $aiAssistant->checkErrors($code, $userId, $errorTypes);
        
        // 記錄AI請求
        $database->insert('ai_requests', [
            'user_id' => $userId,
            'room_id' => $roomId ?: null,
            'request_type' => 'check_errors',
            'request_data' => json_encode(['code' => $code, 'error_types' => $errorTypes]),
            'response_data' => json_encode($result)
        ]);
        
        $logger->info('AI檢查錯誤', [
            'user_id' => $userId,
            'room_id' => $roomId,
        ]);
        
        echo APIResponse::success($result, 'AI檢查完成');
        
    } catch (Exception $e) {
        $logger->error('AI檢查錯誤失敗', ['error' => $e->getMessage()]);
        echo APIResponse::error('AI服務暫時不可用', 'E020', 503);
    }
}

function handleSuggestImprovements($aiAssistant, $database, $logger, $input) {
    $code = $input['code'] ?? '';
    $roomId = intval($input['room_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    $focusAreas = $input['focus_areas'] ?? ['performance', 'readability', 'best_practices'];

    if (empty($code)) {
        echo APIResponse::error('代碼不能為空', 'E001');
        return;
    }
    
    try {
        $result = $aiAssistant->suggestImprovements($code, $userId, $focusAreas);
        
        // 記錄AI請求
        $database->insert('ai_requests', [
            'user_id' => $userId,
            'room_id' => $roomId ?: null,
            'request_type' => 'suggest_improvements',
            'request_data' => json_encode(['code' => $code, 'focus_areas' => $focusAreas]),
            'response_data' => json_encode($result)
        ]);
        
        $logger->info('AI改進代碼', [
            'user_id' => $userId,
            'room_id' => $roomId,
        ]);
        
        echo APIResponse::success($result, 'AI改進完成');
        
    } catch (Exception $e) {
        $logger->error('AI改進代碼失敗', ['error' => $e->getMessage()]);
        echo APIResponse::error('AI服務暫時不可用', 'E020', 503);
    }
}

function handleConflictAnalysis($aiAssistant, $database, $logger, $input) {
    $originalCode = $input['original_code'] ?? '';
    $conflictCode = $input['conflict_code'] ?? '';
    $roomId = intval($input['room_id'] ?? 0);
    $userId = $_SESSION['user_id'];

    if (empty($originalCode) || empty($conflictCode)) {
        echo APIResponse::error('原始代碼和衝突代碼都不能為空', 'E001');
        return;
    }
    
    try {
        $result = $aiAssistant->analyzeConflict($originalCode, $conflictCode, $userId);
        
        // 記錄AI請求
        $database->insert('ai_requests', [
            'user_id' => $userId,
            'room_id' => $roomId ?: null,
            'request_type' => 'conflict',
            'request_data' => json_encode([
                'original_code' => $originalCode,
                'conflict_code' => $conflictCode
            ]),
            'response_data' => json_encode($result)
        ]);
        
        $logger->info('AI衝突分析', [
            'user_id' => $userId,
            'room_id' => $roomId
        ]);
        
        echo APIResponse::success($result, 'AI衝突分析完成');
        
    } catch (Exception $e) {
        $logger->error('AI衝突分析失敗', ['error' => $e->getMessage()]);
        echo APIResponse::error('AI服務暫時不可用', 'E020', 503);
    }
}

function handleQuestion($aiAssistant, $database, $logger, $input) {
    $question = $input['question'] ?? '';
    $context = $input['context'] ?? '';
    $roomId = intval($input['room_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    $category = $input['category'] ?? 'general';

    if (empty($question)) {
        echo APIResponse::error('問題不能為空', 'E001');
        return;
    }
    
    try {
        $result = $aiAssistant->answerQuestion($question, $userId, $context, $category);
        
        // 記錄AI請求
        $database->insert('ai_requests', [
            'user_id' => $userId,
            'room_id' => $roomId ?: null,
            'request_type' => 'question',
            'request_data' => json_encode(['question' => $question, 'context' => $context]),
            'response_data' => json_encode($result)
        ]);
        
        $logger->info('AI回答問題', [
            'user_id' => $userId,
            'room_id' => $roomId,
            'question_length' => strlen($question)
        ]);
        
        echo APIResponse::success($result, 'AI回答完成');
        
    } catch (Exception $e) {
        $logger->error('AI回答問題失敗', ['error' => $e->getMessage()]);
        echo APIResponse::error('AI服務暫時不可用', 'E020', 503);
    }
}

function handleAIHistory($database, $logger, $input) {
    $roomId = intval($input['room_id'] ?? $_GET['room_id'] ?? 0);
    $limit = intval($input['limit'] ?? $_GET['limit'] ?? 20);
    $offset = intval($input['offset'] ?? $_GET['offset'] ?? 0);
    
    $whereClause = "user_id = :user_id";
    $params = ['user_id' => $_SESSION['user_id']];
    
    if ($roomId) {
        $whereClause .= " AND room_id = :room_id";
        $params['room_id'] = $roomId;
    }
    
    $history = $database->fetchAll(
        "SELECT ar.*, u.username 
         FROM ai_requests ar
         JOIN users u ON ar.user_id = u.id
         WHERE $whereClause
         ORDER BY ar.created_at DESC
         LIMIT :limit OFFSET :offset",
        array_merge($params, ['limit' => $limit, 'offset' => $offset])
    );
    
    // 解析JSON數據
    foreach ($history as &$record) {
        $record['request_data'] = json_decode($record['request_data'], true);
        $record['response_data'] = json_decode($record['response_data'], true);
    }
    
    echo APIResponse::success($history);
} 