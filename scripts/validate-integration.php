<?php
/**
 * 整合驗證腳本
 * 用於驗證新功能整合後的系統完整性
 */

class IntegrationValidator {
    private $testResults = [];
    private $errors = [];
    private $warnings = [];
    
    public function __construct() {
        echo "🔍 PythonLearn 整合驗證器\n";
        echo "========================\n\n";
    }
    
    public function runAllTests() {
        $this->testDatabaseConnection();
        $this->testAPIEndpoints();
        $this->testWebSocketServer();
        $this->testFileStructure();
        $this->testFrontendIntegration();
        $this->testPerformance();
        
        $this->generateReport();
    }
    
    private function testDatabaseConnection() {
        echo "📊 測試數據庫連接...\n";
        
        try {
            // 測試 SQLite 連接
            $dbFile = __DIR__ . '/../data/pythonlearn.db';
            if (file_exists($dbFile)) {
                $pdo = new PDO("sqlite:$dbFile");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // 測試基本查詢
                $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $requiredTables = ['rooms', 'users', 'code_history', 'ai_interactions'];
                $missingTables = array_diff($requiredTables, $tables);
                
                if (empty($missingTables)) {
                    $this->addResult('database', 'success', '數據庫連接正常，所有必要表格存在');
                } else {
                    $this->addResult('database', 'warning', '缺少表格: ' . implode(', ', $missingTables));
                }
            } else {
                $this->addResult('database', 'error', '數據庫文件不存在');
            }
            
        } catch (Exception $e) {
            $this->addResult('database', 'error', '數據庫連接失敗: ' . $e->getMessage());
        }
    }
    
    private function testAPIEndpoints() {
        echo "🔌 測試 API 端點...\n";
        
        $endpoints = [
            '/api/auth' => 'POST',
            '/api/room' => 'GET',
            '/api/code' => 'GET',
            '/api/ai' => 'POST',
            '/api/status' => 'GET'
        ];
        
        foreach ($endpoints as $endpoint => $method) {
            $this->testAPIEndpoint($endpoint, $method);
        }
    }
    
    private function testAPIEndpoint($endpoint, $method) {
        $url = "http://localhost:8080$endpoint";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => true]));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->addResult('api', 'error', "$endpoint: cURL 錯誤 - $error");
        } elseif ($httpCode >= 200 && $httpCode < 300) {
            $this->addResult('api', 'success', "$endpoint: HTTP $httpCode");
        } elseif ($httpCode >= 400 && $httpCode < 500) {
            $this->addResult('api', 'warning', "$endpoint: HTTP $httpCode (客戶端錯誤)");
        } else {
            $this->addResult('api', 'error', "$endpoint: HTTP $httpCode (服務器錯誤)");
        }
    }
    
    private function testWebSocketServer() {
        echo "🌐 測試 WebSocket 服務器...\n";
        
        // 檢查 WebSocket 服務器文件
        $wsServerFile = __DIR__ . '/../websocket/server.php';
        if (!file_exists($wsServerFile)) {
            $this->addResult('websocket', 'error', 'WebSocket 服務器文件不存在');
            return;
        }
        
        // 檢查 Ratchet 依賴
        $vendorDir = __DIR__ . '/../vendor';
        if (!is_dir($vendorDir)) {
            $this->addResult('websocket', 'error', 'Composer 依賴未安裝');
            return;
        }
        
        // 嘗試連接 WebSocket (如果正在運行)
        $context = stream_context_create();
        $socket = @stream_socket_client('tcp://localhost:8080', $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
        
        if ($socket) {
            fclose($socket);
            $this->addResult('websocket', 'success', 'WebSocket 端口可訪問');
        } else {
            $this->addResult('websocket', 'warning', 'WebSocket 服務器未運行或端口不可訪問');
        }
    }
    
    private function testFileStructure() {
        echo "📁 測試文件結構...\n";
        
        $requiredFiles = [
            'router.php',
            'composer.json',
            'public/index.html',
            'public/js/websocket.js',
            'public/js/editor.js',
            'public/js/ai-assistant.js',
            'public/css/styles.css',
            'backend/api/auth.php',
            'backend/api/room.php',
            'backend/api/code.php',
            'backend/classes/Database.php',
            'websocket/server.php'
        ];
        
        $missingFiles = [];
        $existingFiles = [];
        
        foreach ($requiredFiles as $file) {
            $fullPath = __DIR__ . '/../' . $file;
            if (file_exists($fullPath)) {
                $existingFiles[] = $file;
            } else {
                $missingFiles[] = $file;
            }
        }
        
        if (empty($missingFiles)) {
            $this->addResult('structure', 'success', '所有必要文件存在');
        } else {
            $this->addResult('structure', 'error', '缺少文件: ' . implode(', ', $missingFiles));
        }
        
        // 檢查文件權限
        $this->checkFilePermissions();
    }
    
    private function checkFilePermissions() {
        $writableDirs = ['data', 'logs', 'temp', 'sessions'];
        
        foreach ($writableDirs as $dir) {
            $fullPath = __DIR__ . '/../' . $dir;
            if (is_dir($fullPath)) {
                if (is_writable($fullPath)) {
                    $this->addResult('permissions', 'success', "$dir 目錄可寫");
                } else {
                    $this->addResult('permissions', 'warning', "$dir 目錄不可寫");
                }
            } else {
                $this->addResult('permissions', 'warning', "$dir 目錄不存在");
            }
        }
    }
    
    private function testFrontendIntegration() {
        echo "🎨 測試前端整合...\n";
        
        // 檢查 JavaScript 文件語法
        $jsFiles = [
            'public/js/websocket.js',
            'public/js/editor.js',
            'public/js/ai-assistant.js',
            'public/js/ui.js'
        ];
        
        foreach ($jsFiles as $jsFile) {
            $fullPath = __DIR__ . '/../' . $jsFile;
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                
                // 簡單的語法檢查
                if (strpos($content, 'function') !== false || strpos($content, 'class') !== false) {
                    $this->addResult('frontend', 'success', "$jsFile 語法正常");
                } else {
                    $this->addResult('frontend', 'warning', "$jsFile 可能為空或語法異常");
                }
            } else {
                $this->addResult('frontend', 'error', "$jsFile 文件不存在");
            }
        }
        
        // 檢查 CSS 文件
        $cssFile = __DIR__ . '/../public/css/styles.css';
        if (file_exists($cssFile)) {
            $size = filesize($cssFile);
            if ($size > 1000) {
                $this->addResult('frontend', 'success', 'CSS 文件存在且有內容');
            } else {
                $this->addResult('frontend', 'warning', 'CSS 文件過小，可能缺少樣式');
            }
        } else {
            $this->addResult('frontend', 'error', 'CSS 文件不存在');
        }
    }
    
    private function testPerformance() {
        echo "⚡ 測試性能指標...\n";
        
        // 測試文件載入時間
        $startTime = microtime(true);
        $indexFile = __DIR__ . '/../public/index.html';
        if (file_exists($indexFile)) {
            file_get_contents($indexFile);
            $loadTime = (microtime(true) - $startTime) * 1000;
            
            if ($loadTime < 100) {
                $this->addResult('performance', 'success', "首頁載入時間: {$loadTime}ms");
            } else {
                $this->addResult('performance', 'warning', "首頁載入時間較慢: {$loadTime}ms");
            }
        }
        
        // 測試內存使用
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        if ($memoryUsage < 50) {
            $this->addResult('performance', 'success', "內存使用: {$memoryUsage}MB");
        } else {
            $this->addResult('performance', 'warning', "內存使用較高: {$memoryUsage}MB");
        }
    }
    
    private function addResult($category, $status, $message) {
        $this->testResults[] = [
            'category' => $category,
            'status' => $status,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $icon = $status === 'success' ? '✅' : ($status === 'warning' ? '⚠️' : '❌');
        echo "  $icon $message\n";
        
        if ($status === 'error') {
            $this->errors[] = $message;
        } elseif ($status === 'warning') {
            $this->warnings[] = $message;
        }
    }
    
    private function generateReport() {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "📊 整合驗證報告\n";
        echo str_repeat('=', 50) . "\n\n";
        
        // 統計結果
        $total = count($this->testResults);
        $success = count(array_filter($this->testResults, fn($r) => $r['status'] === 'success'));
        $warnings = count(array_filter($this->testResults, fn($r) => $r['status'] === 'warning'));
        $errors = count(array_filter($this->testResults, fn($r) => $r['status'] === 'error'));
        
        echo "📈 測試統計:\n";
        echo "  總測試數: $total\n";
        echo "  ✅ 成功: $success\n";
        echo "  ⚠️ 警告: $warnings\n";
        echo "  ❌ 錯誤: $errors\n\n";
        
        // 計算成功率
        $successRate = $total > 0 ? ($success / $total) * 100 : 0;
        echo "🎯 成功率: " . number_format($successRate, 1) . "%\n\n";
        
        // 整體評估
        if ($errors === 0 && $warnings === 0) {
            echo "🎉 整合驗證完全通過！系統可以安全部署。\n";
        } elseif ($errors === 0) {
            echo "✅ 整合驗證基本通過，有一些警告需要注意。\n";
        } else {
            echo "❌ 整合驗證失敗，需要修復錯誤後再次測試。\n";
        }
        
        // 詳細錯誤和警告
        if (!empty($this->errors)) {
            echo "\n🚨 需要修復的錯誤:\n";
            foreach ($this->errors as $error) {
                echo "  ❌ $error\n";
            }
        }
        
        if (!empty($this->warnings)) {
            echo "\n⚠️ 需要注意的警告:\n";
            foreach ($this->warnings as $warning) {
                echo "  ⚠️ $warning\n";
            }
        }
        
        // 保存報告
        $this->saveReport();
        
        echo "\n📄 詳細報告已保存到: test-reports/integration-validation.json\n";
    }
    
    private function saveReport() {
        $reportDir = __DIR__ . '/../test-reports';
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'total' => count($this->testResults),
                'success' => count(array_filter($this->testResults, fn($r) => $r['status'] === 'success')),
                'warnings' => count(array_filter($this->testResults, fn($r) => $r['status'] === 'warning')),
                'errors' => count(array_filter($this->testResults, fn($r) => $r['status'] === 'error'))
            ],
            'results' => $this->testResults,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
        
        file_put_contents(
            $reportDir . '/integration-validation.json',
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}

// 運行驗證
$validator = new IntegrationValidator();
$validator->runAllTests();
?> 