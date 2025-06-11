<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class AIAssistant {
    private $config;
    private $client;
    private $database;
    private $logger;
    
    public function __construct() {
        // 使用簡化的配置系統，只支援Zeabur環境變數
        $this->config = require __DIR__ . '/../config/openai.php';
        $this->client = new Client([
            'timeout' => $this->config['timeout'] / 1000, // 轉換為秒
            'connect_timeout' => 10 // 連接超時10秒
        ]);
        
        // 使用現有的Database類
        require_once __DIR__ . '/Database.php';
        $this->database = \App\Database::getInstance();
        
        // 創建簡單的Logger實現
        $this->logger = new class {
            public function info($message, $context = []) {
                $this->log('INFO', $message, $context);
            }
            
            public function error($message, $context = []) {
                $this->log('ERROR', $message, $context);
            }
            
            private function log($level, $message, $context = []) {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                echo "[{$timestamp}] AI {$level}: {$message}{$contextStr}\n";
            }
        };
    }

    /**
     * 1. 解釋程式碼
     */
    public function explainCode($code, $userId, $detailLevel = 'basic') {
        $prompt = $this->buildExplainPrompt($code, $detailLevel);
        return $this->makeRequest('explain', $prompt, $userId);
    }
    
    /**
     * 2. 檢查錯誤
     */
    public function checkErrors($code, $userId, $errorTypes = ['syntax', 'logic', 'performance', 'security']) {
        $prompt = $this->buildErrorCheckPrompt($code, $errorTypes);
        return $this->makeRequest('check_errors', $prompt, $userId);
    }
    
    /**
     * 3. 改進建議
     */
    public function suggestImprovements($code, $userId, $focusAreas = ['performance', 'readability', 'best_practices']) {
        $prompt = $this->buildImprovementPrompt($code, $focusAreas);
        return $this->makeRequest('suggest_improvements', $prompt, $userId);
    }
    
    /**
     * 4. 衝突分析
     */
    public function analyzeConflict($originalCode, $conflictedCode, $userId, $conflictType = 'merge') {
        $prompt = $this->buildConflictAnalysisPrompt($originalCode, $conflictedCode, $conflictType);
        return $this->makeRequest('analyze_conflict', $prompt, $userId);
    }
    
    /**
     * 5. 回答問題
     */
    public function answerQuestion($question, $userId, $context = '', $category = 'general') {
        $prompt = $this->buildQuestionPrompt($question, $context, $category);
        return $this->makeRequest('answer_question', $prompt, $userId);
    }
    
    private function buildExplainPrompt($code, $detailLevel) {
        $levelInstructions = [
            'basic' => '請用簡單易懂的語言解釋，適合初學者',
            'detailed' => '請提供詳細的解釋，包含技術細節',
            'expert' => '請提供專業級的深度分析'
        ];
        
        return "請用繁體中文解釋以下Python代碼：

```python
{$code}
```

{$levelInstructions[$detailLevel]}

請包含：
1. 代碼的主要功能
2. 每個函數的作用
3. 重要變數的用途
4. 執行流程說明
5. 適合初學者的解釋";
    }
    
    private function buildErrorCheckPrompt($code, $errorTypes) {
        $typeDescriptions = [
            'syntax' => '語法錯誤',
            'logic' => '邏輯錯誤',
            'performance' => '性能問題',
            'security' => '安全隱患'
        ];
        
        $checkTypes = array_map(function($type) use ($typeDescriptions) {
            return $typeDescriptions[$type] ?? $type;
        }, $errorTypes);
        
        return "請檢查以下Python代碼的錯誤：

```python
{$code}
```

請檢查：
" . implode("\n", array_map(function($i, $type) { return ($i+1) . ". {$type}"; }, array_keys($checkTypes), $checkTypes)) . "

請用繁體中文回答，並提供修正建議。如果沒有發現問題，請說明代碼的優點。";
    }
    
    private function buildImprovementPrompt($code, $focusAreas) {
        $areaDescriptions = [
            'performance' => '性能優化',
            'readability' => '代碼可讀性',
            'best_practices' => 'Python最佳實踐'
        ];
        
        $focusDescriptions = array_map(function($area) use ($areaDescriptions) {
            return $areaDescriptions[$area] ?? $area;
        }, $focusAreas);
        
        return "請為以下Python代碼提供改進建議：

```python
{$code}
```

請從以下角度分析：
" . implode("\n", array_map(function($i, $area) { return ($i+1) . ". {$area}"; }, array_keys($focusDescriptions), $focusDescriptions)) . "
4. 代碼結構改進
5. 錯誤處理改善

請用繁體中文回答，並提供具體的改進代碼範例。";
    }
    
    private function buildConflictAnalysisPrompt($originalCode, $conflictedCode, $conflictType) {
        return "以下是發生衝突的兩段Python代碼：

原始代碼：
```python
{$originalCode}
```

衝突代碼：
```python
{$conflictedCode}
```

衝突類型：{$conflictType}

請分析：
1. 衝突的具體原因
2. 兩段代碼的差異
3. 合併建議
4. 最佳解決方案
5. 潛在風險評估

請用繁體中文回答，並提供合併後的代碼建議。";
    }
    
    private function buildQuestionPrompt($question, $context, $category) {
        $categoryInstructions = [
            'web_operation' => '這是關於網頁操作的問題',
            'python_programming' => '這是關於Python程式設計的問題',
            'general' => '這是一般性問題'
        ];
        
        $contextSection = !empty($context) ? "\n相關上下文：\n{$context}\n" : '';
        
        return "用戶問題：{$question}
{$contextSection}
問題類別：{$categoryInstructions[$category]}

請用繁體中文回答，並：
1. 提供清楚的解答
2. 如果是程式問題，提供代碼範例
3. 如果是操作問題，提供步驟說明
4. 給出相關的學習建議
5. 提醒注意事項

請確保答案適合Python初學者理解。";
    }
    
    private function makeRequest($requestType, $prompt, $userId) {
        $startTime = microtime(true);
        
        try {
            $response = $this->client->post($this->config['base_url'] . '/chat/completions', [
                'headers' => array_merge($this->config['headers'], [
                    'Authorization' => 'Bearer ' . $this->config['api_key']
                ]),
                'json' => [
                    'model' => $this->config['model'],
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
                    'max_tokens' => $this->config['max_tokens'],
                    'temperature' => $this->config['temperature']
                ]
            ]);
            
            $executionTime = microtime(true) - $startTime;
            $responseData = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($responseData['choices'][0]['message']['content'])) {
                throw new Exception('AI響應格式錯誤');
            }
            
            $content = $responseData['choices'][0]['message']['content'];
            $tokenUsage = $responseData['usage']['total_tokens'] ?? 0;
            
            // 記錄請求
            $this->logRequest($userId, $requestType, $prompt, $content, $executionTime, $tokenUsage, true);
            
            return [
                'success' => true,
                'analysis' => $content,
                'token_usage' => $tokenUsage,
                'execution_time' => $executionTime
            ];
            
        } catch (RequestException $e) {
            $executionTime = microtime(true) - $startTime;
            $errorMessage = $e->getMessage();
            
            $this->logger->error('AI API請求失敗', [
                'user_id' => $userId,
                'request_type' => $requestType,
                'error' => $errorMessage,
                'execution_time' => $executionTime
            ]);
            
            // 記錄失敗的請求
            $this->logRequest($userId, $requestType, $prompt, null, $executionTime, 0, false, $errorMessage);
            
            return [
                'success' => false,
                'error' => 'AI服務暫時不可用，請稍後再試',
                'execution_time' => $executionTime
            ];
            
        } catch (Exception $e) {
            $executionTime = microtime(true) - $startTime;
            $errorMessage = $e->getMessage();
            
            $this->logger->error('AI處理錯誤', [
                'user_id' => $userId,
                'request_type' => $requestType,
                'error' => $errorMessage
            ]);
            
            $this->logRequest($userId, $requestType, $prompt, null, $executionTime, 0, false, $errorMessage);
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'execution_time' => $executionTime
            ];
        }
    }
    
    private function logRequest($userId, $requestType, $prompt, $response, $executionTime, $tokenUsage, $success, $errorMessage = null) {
        try {
            // 使用默認房間ID 0 來避免 null 約束問題
            $this->database->insert('ai_requests', [
                'room_id' => 0, // 使用 0 作為默認值而不是 null
                'user_id' => $userId,
                'request_type' => $requestType,
                'prompt' => substr($prompt, 0, 1000), // 限制長度
                'response' => $response ? substr($response, 0, 2000) : null,
                'execution_time' => $executionTime,
                'token_usage' => $tokenUsage,
                'success' => $success,
                'error_message' => $errorMessage
            ]);
        } catch (Exception $e) {
            $this->logger->error('AI請求日誌記錄失敗', ['error' => $e->getMessage()]);
        }
    }
    
    public function getUsageStats($userId, $timeRange = '1h') {
        $timeCondition = match($timeRange) {
            '1h' => 'created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)',
            '1d' => 'created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)',
            '1w' => 'created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)',
            default => 'created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        };
        
        $sql = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(token_usage) as total_tokens,
                    AVG(execution_time) as avg_response_time,
                    SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_requests
                FROM ai_requests 
                WHERE user_id = :user_id AND {$timeCondition}";
        
        return $this->database->fetch($sql, ['user_id' => $userId]);
    }
    
    public function checkRateLimit($userId) {
        $config = require __DIR__ . '/../config/app.php';
        $limit = $config['limits']['ai_requests_per_minute'];
        
        $sql = "SELECT COUNT(*) as count 
                FROM ai_requests 
                WHERE user_id = :user_id 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        
        $result = $this->database->fetch($sql, ['user_id' => $userId]);
        
        return $result['count'] < $limit;
    }
} 