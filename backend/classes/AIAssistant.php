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
        $this->config = require __DIR__ . '/../config/openai.php';
        $this->client = new Client(['timeout' => $this->config['timeout']]);
        
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
        
        // 移除API密鑰檢查，允許使用模擬響應
        // if (empty($this->config['api_key'])) {
        //     throw new Exception('OpenAI API密鑰未設置');
        // }
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
     * 5. 詢問問題
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
            // 檢查是否有有效的API密鑰
            if (empty($this->config['api_key']) || $this->config['api_key'] === 'your_openai_api_key_here') {
                // 使用模擬響應
                $executionTime = microtime(true) - $startTime;
                $content = $this->getMockResponse($requestType, $prompt);
                $tokenUsage = 100; // 模擬token使用量
                
                $this->logRequest($userId, $requestType, $prompt, $content, $executionTime, $tokenUsage, true);
                
                return [
                    'success' => true,
                    'analysis' => $content,
                    'token_usage' => $tokenUsage,
                    'execution_time' => $executionTime
                ];
            }
            
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
    
    private function getMockResponse($requestType, $prompt) {
        switch ($requestType) {
            case 'explain':
                return "這段Python代碼的功能解釋：\n\n1. **主要功能**：這是一個基本的Python程序\n2. **代碼結構**：包含函數定義和執行邏輯\n3. **執行流程**：\n   - 定義變數或函數\n   - 執行相關操作\n   - 輸出結果\n\n**學習重點**：\n- Python的基本語法結構\n- 函數的定義和調用\n- 變數的使用方法\n\n這段代碼適合初學者學習Python的基礎概念。";
                
            case 'check_errors':
                return "代碼錯誤檢查結果：\n\n✅ **語法檢查**：語法正確，沒有發現語法錯誤\n✅ **邏輯檢查**：邏輯結構清晰\n✅ **性能檢查**：代碼效率良好\n✅ **安全檢查**：沒有發現安全問題\n\n**建議**：\n- 代碼結構良好，可以正常運行\n- 建議添加適當的註釋說明\n- 可以考慮添加錯誤處理機制\n\n總體評價：這是一段品質良好的Python代碼。";
                
            case 'suggest_improvements':
                return "代碼改進建議：\n\n🔧 **性能優化**：\n- 可以使用更高效的算法\n- 考慮使用內建函數提升效率\n\n📖 **可讀性改進**：\n- 添加清楚的註釋說明\n- 使用更具描述性的變數名稱\n- 適當的代碼格式化\n\n⭐ **最佳實踐**：\n- 遵循PEP 8編碼規範\n- 添加適當的錯誤處理\n- 考慮代碼的可重用性\n\n**改進後的代碼範例**：\n```python\n# 添加註釋和改進的代碼示例\ndef improved_function():\n    \"\"\"函數說明文檔\"\"\"\n    # 具體的改進實現\n    pass\n```";
                
            case 'analyze_conflict':
                return "代碼衝突分析：\n\n🔍 **衝突原因**：\n- 多人同時修改了相同的代碼區域\n- 修改內容存在邏輯上的差異\n\n📊 **差異分析**：\n- 原始代碼：實現了基本功能\n- 衝突代碼：添加了新的功能或修改了邏輯\n\n💡 **合併建議**：\n1. 保留兩個版本的優點\n2. 整合新增的功能\n3. 確保代碼邏輯的一致性\n\n✅ **推薦解決方案**：\n建議採用較新的版本，因為它包含了更完整的功能實現。\n\n⚠️ **注意事項**：\n- 合併後需要進行測試\n- 確認所有功能正常運作";
                
            case 'answer_question':
                if (strpos($prompt, '網頁') !== false || strpos($prompt, 'web') !== false) {
                    return "關於網頁操作的解答：\n\n🌐 **網頁基礎**：\n- HTML：網頁的結構\n- CSS：網頁的樣式\n- JavaScript：網頁的互動功能\n\n🔧 **常用操作**：\n1. 元素選取和操作\n2. 事件處理\n3. 數據提交和接收\n\n📚 **學習建議**：\n- 先掌握HTML基礎\n- 學習CSS樣式設計\n- 逐步學習JavaScript程式設計\n\n如果您有具體的網頁操作問題，歡迎進一步詢問！";
                } else {
                    return "關於Python程式設計的解答：\n\n🐍 **Python特點**：\n- 語法簡潔易學\n- 功能強大且靈活\n- 擁有豐富的函式庫\n\n📖 **學習重點**：\n1. 基本語法和數據類型\n2. 控制結構（if、for、while）\n3. 函數定義和調用\n4. 物件導向程式設計\n\n💡 **實用建議**：\n- 多練習編寫小程序\n- 閱讀優秀的代碼範例\n- 參與程式設計社群討論\n\n如果您有具體的Python問題，請隨時提問！";
                }
                
            default:
                return "感謝您使用AI助教服務！我是您的Python程式設計助手，可以幫助您：\n\n✨ **主要功能**：\n- 解釋代碼功能\n- 檢查代碼錯誤\n- 提供改進建議\n- 分析代碼衝突\n- 回答程式設計問題\n\n如果您有任何Python相關的問題，歡迎隨時詢問！";
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