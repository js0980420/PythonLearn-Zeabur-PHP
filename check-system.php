<?php
/**
 * 系統狀態檢查腳本
 * 用於快速診斷 Python 多人協作教學平台的運行狀態
 */

echo "🔍 Python 多人協作教學平台 - 系統狀態檢查\n";
echo "================================================\n\n";

// 檢查 PHP 版本
echo "📋 基本環境檢查:\n";
echo "   PHP 版本: " . PHP_VERSION . "\n";
echo "   內存限制: " . ini_get('memory_limit') . "\n";
echo "   當前內存使用: " . round(memory_get_usage(true) / 1024 / 1024, 2) . "MB\n\n";

// 檢查必要文件
echo "📁 關鍵文件檢查:\n";
$requiredFiles = [
    'index.html' => '主頁面',
    'router.php' => '路由器',
    'websocket/server.php' => 'WebSocket 服務器',
    'backend/api/ai.php' => 'AI API',
    'backend/classes/Database.php' => '數據庫類',
    'js/websocket.js' => 'WebSocket 客戶端',
    'js/ai-assistant.js' => 'AI 助教客戶端'
];

$missingFiles = [];
foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ {$description}: {$file}\n";
    } else {
        echo "   ❌ {$description}: {$file} (缺失)\n";
        $missingFiles[] = $file;
    }
}

if (!empty($missingFiles)) {
    echo "\n⚠️  警告: 發現 " . count($missingFiles) . " 個缺失文件\n";
}

echo "\n";

// 檢查端口狀態
echo "🔌 端口狀態檢查:\n";
$webPort = 8080;
$wsPort = 8081;

// 檢查Web服務器端口
$webConnection = @fsockopen('localhost', $webPort, $errno, $errstr, 1);
if ($webConnection) {
    echo "   ✅ 端口 {$webPort}: Web服務器 (運行中)\n";
    fclose($webConnection);
} else {
    echo "   ❌ 端口 {$webPort}: Web服務器 (未運行)\n";
}

// 檢查WebSocket服務器端口
$wsConnection = @fsockopen('localhost', $wsPort, $errno, $errstr, 1);
if ($wsConnection) {
    echo "   ✅ 端口 {$wsPort}: WebSocket服務器 (運行中)\n";
    fclose($wsConnection);
} else {
    echo "   ❌ 端口 {$wsPort}: WebSocket服務器 (未運行)\n";
}

echo "\n";

// 檢查數據庫
echo "🗄️  數據庫狀態檢查:\n";
try {
    if (file_exists('backend/classes/Database.php')) {
        require_once 'backend/classes/Database.php';
        $database = Database::getInstance();
        $status = $database->getStatus();
        
        if ($status['connected']) {
            echo "   ✅ 數據庫: {$status['type']} (已連接)\n";
            echo "   📊 表數量: {$status['tables_count']}\n";
        } else {
            echo "   ❌ 數據庫: 連接失敗\n";
        }
    } else {
        echo "   ❌ 數據庫類文件不存在\n";
    }
} catch (Exception $e) {
    echo "   ❌ 數據庫錯誤: " . $e->getMessage() . "\n";
}

echo "\n";

// 檢查 AI 配置
echo "🤖 AI 助教配置檢查:\n";
$openaiKey = $_ENV['OPENAI_API_KEY'] ?? null;

// 檢查 ai_config.json
if (!$openaiKey && file_exists('ai_config.json')) {
    $aiConfig = json_decode(file_get_contents('ai_config.json'), true);
    $openaiKey = $aiConfig['openai_api_key'] ?? null;
}

if ($openaiKey) {
    if (strlen($openaiKey) > 10) {
        echo "   ✅ OpenAI API Key: 已配置 (長度: " . strlen($openaiKey) . ")\n";
    } else {
        echo "   ⚠️  OpenAI API Key: 配置但可能無效 (長度: " . strlen($openaiKey) . ")\n";
    }
} else {
    echo "   ❌ OpenAI API Key: 未配置\n";
    echo "      請設置環境變數 OPENAI_API_KEY 或創建 ai_config.json\n";
}

echo "\n";

// 檢查 Composer 依賴
echo "📦 依賴檢查:\n";
if (file_exists('vendor/autoload.php')) {
    echo "   ✅ Composer 依賴: 已安裝\n";
    
    // 檢查關鍵依賴
    $requiredPackages = [
        'ratchet/pawl' => 'Ratchet WebSocket',
        'ratchet/rfc6455' => 'WebSocket RFC6455'
    ];
    
    if (file_exists('composer.lock')) {
        $composerLock = json_decode(file_get_contents('composer.lock'), true);
        $installedPackages = [];
        
        foreach ($composerLock['packages'] as $package) {
            $installedPackages[$package['name']] = $package['version'];
        }
        
        foreach ($requiredPackages as $packageName => $description) {
            if (isset($installedPackages[$packageName])) {
                echo "   ✅ {$description}: {$installedPackages[$packageName]}\n";
            } else {
                echo "   ❌ {$description}: 未安裝\n";
            }
        }
    }
} else {
    echo "   ❌ Composer 依賴: 未安裝\n";
    echo "      請運行: composer install\n";
}

echo "\n";

// 測試健康檢查端點
echo "🏥 健康檢查端點測試:\n";
$healthUrls = [
    'http://localhost:8080/health',
    'http://localhost:8080/api/health'
];

foreach ($healthUrls as $url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    $httpCode = 0;
    
    if (isset($http_response_header)) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $httpCode = intval($matches[1] ?? 0);
    }
    
    if ($response && $httpCode === 200) {
        echo "   ✅ {$url}: 正常 (HTTP {$httpCode})\n";
        
        $healthData = json_decode($response, true);
        if ($healthData && isset($healthData['status'])) {
            echo "      狀態: {$healthData['status']}\n";
        }
    } else {
        echo "   ❌ {$url}: 失敗 (HTTP {$httpCode})\n";
    }
}

echo "\n";

// 系統建議
echo "💡 系統建議:\n";

$suggestions = [];

if (!empty($missingFiles)) {
    $suggestions[] = "修復缺失的文件: " . implode(', ', $missingFiles);
}

if (!$openaiKey) {
    $suggestions[] = "配置 OpenAI API Key 以啟用 AI 助教功能";
}

if (!file_exists('vendor/autoload.php')) {
    $suggestions[] = "運行 'composer install' 安裝依賴";
}

// 檢查是否有服務器在運行
$webServerRunning = @fsockopen('localhost', 8080, $errno, $errstr, 1);
if (!$webServerRunning) {
    $suggestions[] = "啟動 Web 服務器: php -S localhost:8080 router.php";
    $suggestions[] = "或使用快速啟動: .\\start.bat";
}

if (empty($suggestions)) {
    echo "   ✅ 系統狀態良好，無需特別操作\n";
} else {
    foreach ($suggestions as $i => $suggestion) {
        echo "   " . ($i + 1) . ". {$suggestion}\n";
    }
}

echo "\n";

// 快速啟動指令
echo "🚀 快速啟動指令:\n";
echo "   Windows: .\\start.bat\n";
echo "   手動啟動 Web: php -S localhost:8080 router.php\n";
echo "   手動啟動 WebSocket: php websocket/server.php\n";
echo "   健康檢查: curl http://localhost:8080/health\n";

echo "\n================================================\n";
echo "檢查完成！\n";
?> 