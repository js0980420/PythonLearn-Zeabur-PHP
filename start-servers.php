<?php

/**
 * PHP 服務器啟動腳本
 * 啟動 PHP 內建 Web 服務器和 Ratchet WebSocket 服務器
 */

echo "🚀 Python 教學多人協作平台 - 純 PHP 版本\n";
echo "================================================\n";

// 檢查 PHP 版本
$phpVersion = phpversion();
echo "PHP 版本: {$phpVersion}\n";

if (version_compare($phpVersion, '8.1.0', '<')) {
    echo "❌ 錯誤: 需要 PHP 8.1 或更高版本\n";
    exit(1);
}

// 檢查必要的擴展
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'curl', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "❌ 錯誤: 缺少必要的 PHP 擴展: " . implode(', ', $missingExtensions) . "\n";
    exit(1);
}

// 檢查 Composer 依賴
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ 錯誤: 未找到 vendor/autoload.php\n";
    echo "請運行: composer install\n";
    exit(1);
}

// 檢查必要目錄
$requiredDirs = ['data', 'data/rooms', 'logs'];
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        echo "📁 創建目錄: {$dir}\n";
        mkdir($dir, 0755, true);
    }
}

// 端口配置
$webPort = 80;
$websocketPort = 8080;

// 在開發環境中使用不同的端口
if (php_sapi_name() === 'cli-server' || getenv('APP_ENV') === 'development') {
    $webPort = 8000; // 使用 8000 而不是 3000
}

echo "🌐 Web 服務器端口: {$webPort}\n";
echo "📡 WebSocket 服務器端口: {$websocketPort}\n";
echo "\n";

// 檢查端口是否被占用
function isPortInUse($port) {
    $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 5);
    if ($connection) {
        fclose($connection);
        return true;
    }
    return false;
}

if (isPortInUse($webPort)) {
    echo "⚠️  警告: 端口 {$webPort} 已被占用\n";
}

if (isPortInUse($websocketPort)) {
    echo "⚠️  警告: 端口 {$websocketPort} 已被占用\n";
}

// 環境變數檢查
echo "🔧 環境配置檢查:\n";
$openaiKey = getenv('OPENAI_API_KEY');
if ($openaiKey) {
    echo "   ✅ OpenAI API Key: " . (strlen($openaiKey) > 10 ? substr($openaiKey, 0, 10) . '...' : '短密鑰') . "\n";
} else {
    echo "   ⚠️  OpenAI API Key: 未設置 (AI 功能將被禁用)\n";
}

$mysqlHost = getenv('MYSQL_HOST');
if ($mysqlHost) {
    echo "   ✅ MySQL Host: {$mysqlHost}\n";
} else {
    echo "   ℹ️  MySQL Host: 未設置 (使用模擬數據庫)\n";
}

echo "\n";

// 如果是 CLI 模式，啟動內建服務器
if (php_sapi_name() === 'cli') {
    echo "🚀 啟動服務器...\n";
    echo "\n";

    // 設置環境變數
    putenv("WEBSOCKET_PORT={$websocketPort}");
    
    // 啟動 WebSocket 服務器（後台進程）
    echo "📡 啟動 WebSocket 服務器 (端口 {$websocketPort})...\n";
    $websocketCmd = "php websocket/server.php";
    
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows 後台啟動
        $websocketProcess = popen("start /B {$websocketCmd}", 'r');
    } else {
        // Linux/Mac 後台啟動
        $websocketProcess = popen("{$websocketCmd} > /dev/null 2>&1 &", 'r');
    }
    
    // 等待 WebSocket 服務器啟動
    sleep(2);
    
    if (isPortInUse($websocketPort)) {
        echo "   ✅ WebSocket 服務器啟動成功\n";
    } else {
        echo "   ❌ WebSocket 服務器啟動失敗\n";
    }
    
    // 啟動 Web 服務器
    echo "🌐 啟動 Web 服務器 (端口 {$webPort})...\n";
    echo "\n";
    echo "🌟 服務器已啟動！\n";
    echo "================================\n";
    echo "📱 Web 界面: http://localhost:{$webPort}\n";
    echo "📡 WebSocket: ws://localhost:{$websocketPort}\n";
    echo "💊 健康檢查: http://localhost:{$webPort}/backend/api/health.php\n";
    echo "🎓 教師後台: http://localhost:{$webPort}/teacher-dashboard.html\n";
    echo "\n";
    echo "按 Ctrl+C 停止服務器\n";
    echo "================================\n";
    
    // 啟動內建 Web 服務器
    $webCmd = "php -S 0.0.0.0:{$webPort}";
    system($webCmd);
    
} else {
    echo "ℹ️  在 Web 服務器環境中運行\n";
    echo "🌐 Web 服務: 已由 Web 服務器提供\n";
    echo "📡 WebSocket: 需要手動啟動 - php websocket/server.php\n";
}

?> 