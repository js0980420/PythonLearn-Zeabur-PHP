<?php
// 啟用錯誤顯示來調試
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../classes/APIResponse.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/AIAssistant.php';

use App\APIResponse;
use App\Database;
use App\AIAssistant;

// 設置CORS頭
APIResponse::setCORSHeaders();

// 初始化數據庫（靜默模式）
ob_start(); // 捕獲輸出
$database = Database::getInstance();
ob_end_clean(); // 清除輸出

// 安全初始化 AIAssistant
try {
$aiAssistant = new AIAssistant();
} catch (Exception $e) {
    echo APIResponse::error('AI服務初始化失敗: ' . $e->getMessage(), 'E011', 503);
    exit;
}

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
    
    // 支持測試模式 - 從全域變數讀取輸入
    if (isset($GLOBALS['test_input'])) {
        $input = json_decode($GLOBALS['test_input'], true) ?? [];
    } else {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    }
    
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'explain':
        case 'explain_code':  // 支援前端發送的action
            handleExplainCode($aiAssistant, $database, $input);
            break;
            
        case 'check_errors':
            handleCheckErrors($aiAssistant, $database, $input);
            break;
            
        case 'suggest':
        case 'suggest_improvements':  // 支援兩種格式
            handleSuggestImprovements($aiAssistant, $database, $input);
            break;
            
        case 'analyze':  // 添加analyze操作支援
            handleAnalyzeCode($aiAssistant, $database, $input);
            break;
            
        case 'conflict':
            handleConflictAnalysis($aiAssistant, $database, $input);
            break;
            
        case 'question':
        case 'ask':  // 添加ask操作支援
            handleQuestion($aiAssistant, $database, $input);
            break;
            
        case 'history':
            handleAIHistory($database, $input);
            break;
            
        default:
            echo APIResponse::error('無效的操作: ' . $action, 'E001');
    }
    
} catch (Exception $e) {
    echo APIResponse::error('系統錯誤: ' . $e->getMessage(), 'E010', 500);
}

function handleExplainCode($aiAssistant, $database, $input) {
    $code = $input['code'] ?? '';
    $roomId = $input['room_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? 'anonymous_user';
    $detailLevel = $input['detail_level'] ?? 'basic';
    
    if (empty($code)) {
        echo APIResponse::error('代碼不能為空', 'E001');
        return;
    }
    
    try {
        $result = $aiAssistant->explainCode($code, $userId, $detailLevel);
        
        // 記錄AI請求
        $insertData = [
            'user_id' => $userId,
            'request_type' => 'explain_code',
            'request_data' => json_encode(['code' => $code, 'detail_level' => $detailLevel]),
            'response_data' => json_encode($result)
        ];
        
        if (!empty($roomId)) {
            $insertData['room_id'] = $roomId;
        }
        
        $database->insert('ai_requests', $insertData);
        
        echo APIResponse::success($result, 'AI解釋完成');
        
    } catch (Exception $e) {
        echo APIResponse::error('AI解釋失敗: ' . $e->getMessage(), 'E020');
    }
}

function handleCheckErrors($aiAssistant, $database, $input) {
    $code = $input['code'] ?? '';
    $roomId = $input['room_id'] ?? 'default_room';
    $userId = $_SESSION['user_id'] ?? 'anonymous_user';
    $username = $_SESSION['username'] ?? 'Anonymous';
    $errorTypes = $input['error_types'] ?? ['syntax', 'logic', 'performance', 'security'];

    if (empty($code)) {
        echo APIResponse::error('代碼不能為空', 'E001');
        return;
    }
    
    try {
        $startTime = microtime(true);
        $result = $aiAssistant->checkErrors($code, $userId, $errorTypes);
        $responseTime = round((microtime(true) - $startTime) * 1000); // 轉換為毫秒
        
        // 記錄AI互動
        $insertData = [
            'room_id' => $roomId,
            'user_id' => $userId,
            'username' => $username,
            'interaction_type' => 'check_errors',
            'user_input' => $code,
            'ai_response' => json_encode($result),
            'response_time_ms' => $responseTime,
            'tokens_used' => $result['token_usage'] ?? null
        ];
        
        $database->insert('ai_interactions', $insertData);
        
        echo APIResponse::success($result, 'AI檢查完成');
        
    } catch (Exception $e) {
        echo APIResponse::error('AI檢查失敗: ' . $e->getMessage(), 'E020');
    }
}

function handleSuggestImprovements($aiAssistant, $database, $input) {
    $code = $input['code'] ?? '';
    $roomId = $input['room_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? 'anonymous_user';
    $focus = $input['focus'] ?? 'general';

    if (empty($code)) {
        echo APIResponse::error('代碼不能為空', 'E001');
        return;
    }
    
    try {
        $result = $aiAssistant->suggestImprovements($code, $userId, $focus);
        
        // 記錄AI請求
        $insertData = [
            'user_id' => $userId,
            'request_type' => 'suggest_improvements',
            'request_data' => json_encode(['code' => $code, 'focus' => $focus]),
            'response_data' => json_encode($result)
        ];
        
        if (!empty($roomId)) {
            $insertData['room_id'] = $roomId;
        }
        
        $database->insert('ai_requests', $insertData);
        
        echo APIResponse::success($result, 'AI改進建議完成');
        
    } catch (Exception $e) {
        echo APIResponse::error('AI改進建議失敗: ' . $e->getMessage(), 'E020');
    }
}

function handleAnalyzeCode($aiAssistant, $database, $input) {
    $code = $input['code'] ?? '';
    $roomId = $input['room_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? 'anonymous_user';
    $analysisType = $input['analysis_type'] ?? 'general';

    if (empty($code)) {
        echo APIResponse::error('代碼不能為空', 'E001');
        return;
    }
    
    try {
        // 使用explainCode方法進行代碼分析
        $result = $aiAssistant->explainCode($code, $userId, 'detailed');
        
        // 記錄AI請求
        $insertData = [
            'user_id' => $userId,
            'request_type' => 'analyze_code',
            'request_data' => json_encode(['code' => $code, 'analysis_type' => $analysisType]),
            'response_data' => json_encode($result)
        ];
        
        if (!empty($roomId)) {
            $insertData['room_id'] = $roomId;
        }
        
        $database->insert('ai_requests', $insertData);
        
        echo APIResponse::success($result, 'AI代碼分析完成');
        
    } catch (Exception $e) {
        echo APIResponse::error('AI代碼分析失敗: ' . $e->getMessage(), 'E020');
    }
}

function handleAnalyzeConflict($aiAssistant, $database, $input) {
    $originalCode = $input['original_code'] ?? '';
    $conflictedCode = $input['conflicted_code'] ?? '';
    $roomId = $input['room_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? 'anonymous_user';

    if (empty($originalCode) || empty($conflictedCode)) {
        echo APIResponse::error('原始代碼和衝突代碼都不能為空', 'E001');
        return;
    }
    
    try {
        $result = $aiAssistant->analyzeConflict($originalCode, $conflictedCode, $userId);
        
        // 記錄AI請求
        $insertData = [
            'user_id' => $userId,
            'request_type' => 'analyze_conflict',
            'request_data' => json_encode(['original_code' => $originalCode, 'conflicted_code' => $conflictedCode]),
            'response_data' => json_encode($result)
        ];
        
        if (!empty($roomId)) {
            $insertData['room_id'] = $roomId;
        }
        
        $database->insert('ai_requests', $insertData);
        
        echo APIResponse::success($result, 'AI衝突分析完成');
        
    } catch (Exception $e) {
        echo APIResponse::error('AI衝突分析失敗: ' . $e->getMessage(), 'E020');
    }
}

function handleQuestion($aiAssistant, $database, $input) {
    $question = $input['question'] ?? '';
    $context = $input['context'] ?? '';
    $roomId = $input['room_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? 'anonymous_user';

    if (empty($question)) {
        echo APIResponse::error('問題不能為空', 'E001');
        return;
    }
    
    try {
        $result = $aiAssistant->answerQuestion($question, $userId, $context);
        
        // 記錄AI請求
        $insertData = [
            'user_id' => $userId,
            'request_type' => 'answer_question',
            'request_data' => json_encode(['question' => $question, 'context' => $context]),
            'response_data' => json_encode($result)
        ];
        
        if (!empty($roomId)) {
            $insertData['room_id'] = $roomId;
        }
        
        $database->insert('ai_requests', $insertData);
        
        echo APIResponse::success($result, 'AI問答完成');
        
    } catch (Exception $e) {
        echo APIResponse::error('AI問答失敗: ' . $e->getMessage(), 'E020');
    }
}

function handleAIHistory($database, $input) {
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