<?php

/**
 * PythonLearn AI Assistant API - 簡化版
 * 只使用 Zeabur 環境變數 OPENAI_API_KEY
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 載入 AI 配置
$aiConfig = require_once __DIR__ . '/../config/ai-config.php';

// OpenAI API 配置
$OPENAI_API_URL = $aiConfig['openai']['api_url'];
$OPENAI_API_KEY = $aiConfig['openai']['api_key'];
$OPENAI_MODEL = $aiConfig['openai']['model'];
$OPENAI_MAX_TOKENS = $aiConfig['openai']['max_tokens'];
$OPENAI_TEMPERATURE = $aiConfig['openai']['temperature'];
$OPENAI_TIMEOUT = $aiConfig['openai']['timeout'];

/**
 * 調用 OpenAI API
 */
function callOpenAI($messages, $max_tokens = 1000)
{
    global $OPENAI_API_URL, $OPENAI_API_KEY, $OPENAI_MODEL, $OPENAI_TEMPERATURE, $OPENAI_TIMEOUT;

    $data = [
        'model' => $OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => $max_tokens,
        'temperature' => $OPENAI_TEMPERATURE,
        'top_p' => 1,
        'frequency_penalty' => 0,
        'presence_penalty' => 0
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $OPENAI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $OPENAI_TIMEOUT);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL 錯誤: " . $error);
    }

    if ($httpCode !== 200) {
        throw new Exception("OpenAI API 錯誤 (HTTP $httpCode): " . $response);
    }

    $result = json_decode($response, true);
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        throw new Exception("OpenAI API 回應格式錯誤");
    }

    return $result['choices'][0]['message']['content'];
}

/**
 * 生成系統提示詞
 */
function getSystemPrompt($action)
{
    switch ($action) {
        case 'check_errors':
            return "You are a Python code checker. Check for syntax errors, logic issues, and provide brief suggestions. Use traditional Chinese but keep it simple and direct.";

        case 'suggest':
        case 'improvement_tips':
            return "You are a Python code reviewer. Provide brief improvement suggestions focusing on code quality, efficiency, and best practices. Keep explanations simple and actionable.";

        case 'analyze':
            return "You are a Python programming assistant. Analyze the code structure and functionality briefly. Focus on what the code does and its main components.";

        case 'conflict_analysis':
            return "You are a Python code conflict analyzer. Analyze the code for potential collaboration conflicts, identify problematic areas, and suggest resolution strategies. Focus on code structure, variable conflicts, and collaborative issues.";

        case 'run_code':
            return "You are a Python code simulator. Predict what this code will output when executed. Provide the expected output and brief analysis.";

        case 'explain_code':
        default:
            return "You are a Python programming assistant. Explain the code in a simple and concise manner. Focus on what the code does, not detailed explanations.";
    }
}

/**
 * 處理代碼執行請求
 */
function handleCodeExecution($code)
{
    $systemPrompt = "你是一個 Python 代碼分析師。請分析提供的 Python 代碼並預測其執行結果。如果代碼有語法或邏輯錯誤，請指出問題。如果代碼正常，請描述其輸出結果。請用繁體中文回答。";

    $userPrompt = "請分析以下 Python 代碼並預測執行結果：\n\n```python\n$code\n```";

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt]
    ];

    $analysis = callOpenAI($messages, 800);

    return [
        'success' => true,
        'output' => $analysis,
        'analysis' => $analysis,
        'execution_time' => rand(50, 200) // 模擬執行時間
    ];
}

/**
 * 調用 AI 進行各種分析
 */
function callAIForAction($action, $content, $aiConfig)
{
    $systemPrompt = getSystemPrompt($action);
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $content]
    ];

    try {
        $response = callOpenAI($messages, $aiConfig['openai']['max_tokens']);
        
        return [
            'success' => true,
            'analysis' => $response,
            'timestamp' => date('c')
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('c')
        ];
    }
}

// 主要處理邏輯
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('無效的請求數據');
    }
    
    $action = $input['action'] ?? '';
    $code = $input['code'] ?? '';
    $question = $input['question'] ?? '';
    $context = $input['context'] ?? '';
    
    if (empty($action)) {
        throw new Exception('缺少 action 參數');
    }
    
    $response = null;
    
    switch ($action) {
        case 'explain':
            if (empty($code)) {
                throw new Exception('缺少代碼內容');
            }
            $prompt = "請用繁體中文解釋以下 Python 代碼的功能：\n\n```python\n{$code}\n```";
            $response = callAIForAction('explain_code', $prompt, $aiConfig);
            break;
            
        case 'check_errors':
            if (empty($code)) {
                throw new Exception('缺少代碼內容');
            }
            $prompt = "請檢查以下 Python 代碼是否有錯誤，並提供修正建議：\n\n```python\n{$code}\n```";
            $response = callAIForAction('check_errors', $prompt, $aiConfig);
            break;
            
        case 'suggest':
            if (empty($code)) {
                throw new Exception('缺少代碼內容');
            }
            $prompt = "請為以下 Python 代碼提供改進建議：\n\n```python\n{$code}\n```";
            $response = callAIForAction('suggest', $prompt, $aiConfig);
            break;
            
        case 'analyze':
            if (empty($code)) {
                throw new Exception('缺少代碼內容');
            }
            $prompt = "請分析以下 Python 代碼的結構和功能：\n\n```python\n{$code}\n```";
            $response = callAIForAction('analyze', $prompt, $aiConfig);
            break;
            
        case 'run_code':
            if (empty($code)) {
                throw new Exception('缺少代碼內容');
            }
            $response = handleCodeExecution($code);
            break;
            
        case 'ask_question':
            if (empty($question)) {
                throw new Exception('缺少問題內容');
            }
            $prompt = "問題：{$question}";
            if (!empty($context)) {
                $prompt .= "\n\n相關上下文：\n{$context}";
            }
            $response = callAIForAction('answer_question', $prompt, $aiConfig);
            break;
            
        default:
            throw new Exception('不支援的操作類型');
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
}
