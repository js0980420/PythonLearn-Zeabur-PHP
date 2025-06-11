<?php

/**
 * 🎛️ PythonLearn 環境變數配置
 * 純 HTTP 輪詢模式 - 專為 Zeabur 單端口環境設計
 * 所有配置都從 Zeabur Config Editor 讀取
 * 無需在代碼倉庫中儲存敏感資料
 */

// ===============================
// 🌐 基礎應用配置
// ===============================
define('APP_ENV', $_ENV['NODE_ENV'] ?? 'development');
define('APP_PORT', (int)($_ENV['PORT'] ?? 8080));
define('HEALTH_CHECK_PATH', $_ENV['HEALTH_CHECK_PATH'] ?? '/health');

// ===============================
// 🤖 AI 助教配置
// ===============================
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? '');
define('OPENAI_MODEL', $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo');
define('OPENAI_MAX_TOKENS', (int)($_ENV['OPENAI_MAX_TOKENS'] ?? 1000));
define('OPENAI_TEMPERATURE', (float)($_ENV['OPENAI_TEMPERATURE'] ?? 0.3));
define('OPENAI_TIMEOUT', (int)($_ENV['OPENAI_TIMEOUT'] ?? 30000));

// ===============================
// 🗄️ MySQL 資料庫配置
// ===============================
define('MYSQL_HOST', $_ENV['MYSQL_HOST'] ?? 'localhost');
define('MYSQL_USER', $_ENV['MYSQL_USER'] ?? 'root');
define('MYSQL_PASSWORD', $_ENV['MYSQL_PASSWORD'] ?? '');
define('MYSQL_DATABASE', $_ENV['MYSQL_DATABASE'] ?? 'pythonlearn');
define('MYSQL_PORT', (int)($_ENV['MYSQL_PORT'] ?? 3306));

// ===============================
// 🔄 HTTP 輪詢配置 (替代 WebSocket)
// ===============================
define('POLLING_ENABLED', true);
define('POLLING_INTERVAL', (int)($_ENV['POLLING_INTERVAL'] ?? 1000)); // 1秒
define('POLLING_TIMEOUT', (int)($_ENV['POLLING_TIMEOUT'] ?? 30000)); // 30秒
define('LONG_POLLING_ENABLED', filter_var($_ENV['LONG_POLLING_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));

// ===============================
// 🔒 安全配置
// ===============================
define('SESSION_SECRET', $_ENV['SESSION_SECRET'] ?? 'default-secret-key');
define('CORS_ORIGIN', $_ENV['CORS_ORIGIN'] ?? '*');
define('RATE_LIMIT_WINDOW_MS', (int)($_ENV['RATE_LIMIT_WINDOW_MS'] ?? 900000));
define('RATE_LIMIT_MAX_REQUESTS', (int)($_ENV['RATE_LIMIT_MAX_REQUESTS'] ?? 100));

// ===============================
// 📊 性能配置
// ===============================
define('MAX_CONCURRENT_USERS', (int)($_ENV['MAX_CONCURRENT_USERS'] ?? 100));
define('MAX_ROOMS', (int)($_ENV['MAX_ROOMS'] ?? 50));
define('MAX_USERS_PER_ROOM', (int)($_ENV['MAX_USERS_PER_ROOM'] ?? 8));
define('DATABASE_POOL_SIZE', (int)($_ENV['DATABASE_POOL_SIZE'] ?? 10));
define('API_TIMEOUT', (int)($_ENV['API_TIMEOUT'] ?? 30000));

// ===============================
// 🔍 監控配置
// ===============================
define('ENABLE_METRICS', filter_var($_ENV['ENABLE_METRICS'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'info');
define('DEBUG_MODE', filter_var($_ENV['DEBUG_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN));

// ===============================
// 🛡️ 配置驗證
// ===============================

/**
 * 驗證必要的環境變數是否設置
 * @return array 缺失的配置項目
 */
function validateConfig(): array
{
    $missingConfig = [];

    // 檢查必要的配置
    $requiredConfigs = [
        'MYSQL_HOST' => MYSQL_HOST,
        'MYSQL_USER' => MYSQL_USER,
        'MYSQL_DATABASE' => MYSQL_DATABASE
    ];

    foreach ($requiredConfigs as $key => $value) {
        if (empty($value)) {
            $missingConfig[] = $key;
        }
    }

    return $missingConfig;
}

/**
 * 獲取配置總覽
 * @return array 配置資訊（隱藏敏感資料）
 */
function getConfigOverview(): array
{
    return [
        'app' => [
            'environment' => APP_ENV,
            'port' => APP_PORT,
            'health_check' => HEALTH_CHECK_PATH
        ],
        'ai' => [
            'model' => OPENAI_MODEL,
            'max_tokens' => OPENAI_MAX_TOKENS,
            'temperature' => OPENAI_TEMPERATURE,
            'api_key_set' => !empty(OPENAI_API_KEY)
        ],
        'database' => [
            'host' => MYSQL_HOST,
            'user' => MYSQL_USER,
            'database' => MYSQL_DATABASE,
            'port' => MYSQL_PORT,
            'password_set' => !empty(MYSQL_PASSWORD)
        ],
        'polling' => [
            'enabled' => POLLING_ENABLED,
            'interval' => POLLING_INTERVAL,
            'long_polling' => LONG_POLLING_ENABLED,
            'timeout' => POLLING_TIMEOUT
        ],
        'performance' => [
            'max_users' => MAX_CONCURRENT_USERS,
            'max_rooms' => MAX_ROOMS,
            'users_per_room' => MAX_USERS_PER_ROOM
        ]
    ];
}

// ===============================
// 🚨 開發環境警告
// ===============================
if (APP_ENV === 'development' && DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    echo "<!-- 🚨 開發模式啟用，請確保生產環境關閉 DEBUG_MODE -->\n";
}

// 記錄配置載入
if (LOG_LEVEL === 'debug' || DEBUG_MODE) {
    error_log("✅ PythonLearn 配置已載入 - 環境: " . APP_ENV . " (HTTP 輪詢模式)");
}
