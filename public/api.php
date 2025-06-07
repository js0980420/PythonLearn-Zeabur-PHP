<?php
/**
 * 簡化的API處理文件
 * 包含認證、歷史記錄和AI功能
 */

// 設置 CORS 頭
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// 處理預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// 解析路徑
$path = parse_url($requestUri, PHP_URL_PATH);
$pathSegments = explode('/', trim($path, '/'));

// 模擬認證API
if ($method === 'POST' && end($pathSegments) === 'auth') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 簡單的用戶驗證模擬
    $response = [
        'success' => true,
        'user_id' => 'user_' . uniqid(),
        'username' => $input['username'] ?? 'Anonymous',
        'message' => '登入成功'
    ];
    
    echo json_encode($response);
    exit;
}

// 模擬歷史API
if ($method === 'GET' && in_array('history', $pathSegments)) {
    $roomId = $_GET['room_id'] ?? 'default';
    
    $response = [
        'success' => true,
        'history' => [],
        'room_id' => $roomId,
        'message' => '歷史記錄獲取成功'
    ];
    
    echo json_encode($response);
    exit;
}

// AI API 處理
if ($method === 'POST' && end($pathSegments) === 'ai') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 檢查必要參數
    $action = $input['action'] ?? '';
    
    // 對於衝突分析，參數要求不同
    if ($action === 'conflict_analysis') {
        $conflictData = $input['conflict_data'] ?? null;
        if (!$conflictData) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '缺少衝突數據'
            ]);
            exit;
        }
    } else {
        $code = $input['code'] ?? '';
        if (empty($action) || empty($code)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '缺少必要參數: action 或 code'
            ]);
            exit;
        }
    }
    
    // 檢查AI配置
    $aiConfigFile = __DIR__ . '/../ai_config.json';
    if (!file_exists($aiConfigFile)) {
        echo json_encode([
            'success' => false,
            'error' => 'AI助教功能未啟用：缺少配置文件'
        ]);
        exit;
    }
    
    try {
        $aiConfig = json_decode(file_get_contents($aiConfigFile), true);
        
        if (!$aiConfig || !$aiConfig['enabled']) {
            throw new Exception('AI功能已停用');
        }
        
        // 調用AI API
        if ($action === 'conflict_analysis') {
            $response = callOpenAIForConflict($aiConfig, $conflictData);
        } else {
            $response = callOpenAI($aiConfig, $action, $code);
        }
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'response' => $response,
            'timestamp' => date('c')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'action' => $action,
            'error' => $e->getMessage(),
            'timestamp' => date('c')
        ]);
    }
    
    exit;
}

// 未找到的API
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'API 端點未找到',
    'path' => $path
]);

/**
 * 調用OpenAI API
 */
function callOpenAI($config, $action, $code) {
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
        throw new Exception('AI API請求失敗，HTTP狀態碼: ' . $httpCode);
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        throw new Exception('AI API響應格式錯誤');
    }
    
    return $result['choices'][0]['message']['content'];
}

/**
 * 調用OpenAI API進行衝突分析
 */
function callOpenAIForConflict($config, $conflictData) {
    // 檢查衝突分析是否啟用
    if (!isset($config['conflict_analysis']['enabled']) || !$config['conflict_analysis']['enabled']) {
        throw new Exception('衝突分析功能未啟用');
    }
    
    $conflictType = $conflictData['type'] ?? 'unknown';
    $oldCode = $conflictData['old_code'] ?? '';
    $newCode = $conflictData['new_code'] ?? '';
    $affectedLines = $conflictData['affected_lines'] ?? 0;
    $otherUsers = $conflictData['other_users'] ?? [];
    
    // 使用配置中的衝突分析提示
    $promptTemplate = $config['prompts']['conflict_analysis'] ?? 
        "請分析以下代碼衝突：\n衝突類型：{conflict_type}\n原始代碼：\n{old_code}\n\n修改後代碼：\n{new_code}";
    
    $prompt = str_replace(
        ['{conflict_type}', '{old_code}', '{new_code}'],
        [$conflictType, $oldCode, $newCode],
        $promptTemplate
    );
    
    // 添加協作者信息
    if (!empty($otherUsers)) {
        $userList = implode(', ', array_column($otherUsers, 'username'));
        $prompt .= "\n\n當前協作者：" . $userList;
        $prompt .= "\n影響行數：" . $affectedLines . " 行";
    }
    
    $data = [
        'model' => $config['model'],
        'messages' => [
            [
                'role' => 'system',
                'content' => '你是一個專業的程式碼衝突分析助手，專門幫助開發團隊解決代碼衝突問題。請用繁體中文提供詳細的分析和建議。'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => $config['conflict_analysis']['max_analysis_length'] ?? 1000,
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
        throw new Exception('AI衝突分析請求失敗，HTTP狀態碼: ' . $httpCode);
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        throw new Exception('AI衝突分析響應格式錯誤');
    }
    
    return $result['choices'][0]['message']['content'];
}
?> 