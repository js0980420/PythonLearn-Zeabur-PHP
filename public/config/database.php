<?php

/**
 * 🗄️ PythonLearn 平台 MySQL 資料庫配置
 * 
 * 功能：
 * - 環境自動檢測（XAMPP/Zeabur/其他）
 * - 連接配置管理
 * - 自動建表和初始化
 * - 錯誤處理和日誌記錄
 * 
 * 📅 創建日期: 2025-06-10
 * 🎯 目標: 建立穩固的資料庫架構，避免檔案存儲問題
 */

class DatabaseConfig
{
    private static $instance = null;
    private $pdo = null;
    private $config = [];

    // 資料庫配置
    private $defaultConfig = [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'pythonlearn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];

    private function __construct()
    {
        $this->detectEnvironment();
        $this->loadConfig();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 🔍 檢測運行環境
     */
    private function detectEnvironment()
    {
        // 檢測 Zeabur 環境
        if (getenv('ZEABUR')) {
            $this->config = [
                'host' => getenv('MYSQL_HOST') ?: 'mysql',
                'port' => getenv('MYSQL_PORT') ?: 3306,
                'database' => getenv('MYSQL_DATABASE') ?: 'pythonlearn',
                'username' => getenv('MYSQL_USERNAME') ?: 'root',
                'password' => getenv('MYSQL_PASSWORD') ?: '',
                'environment' => 'zeabur'
            ];
        }
        // 檢測 Railway 環境
        elseif (getenv('RAILWAY_ENVIRONMENT')) {
            $this->config = [
                'host' => getenv('MYSQLHOST') ?: 'localhost',
                'port' => getenv('MYSQLPORT') ?: 3306,
                'database' => getenv('MYSQLDATABASE') ?: 'pythonlearn',
                'username' => getenv('MYSQLUSER') ?: 'root',
                'password' => getenv('MYSQLPASSWORD') ?: '',
                'environment' => 'railway'
            ];
        }
        // 檢測本地 XAMPP 環境
        elseif (file_exists('/xampp') || file_exists('C:/xampp')) {
            $this->config = [
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'pythonlearn',
                'username' => 'root',
                'password' => '',
                'environment' => 'xampp'
            ];
        }
        // 預設本地環境
        else {
            $this->config = [
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'pythonlearn',
                'username' => 'root',
                'password' => '',
                'environment' => 'local'
            ];
        }

        // 合併預設配置
        $this->config = array_merge($this->defaultConfig, $this->config);
    }

    /**
     * 📝 載入本地配置檔案（如果存在）
     */
    private function loadConfig()
    {
        $configFile = __DIR__ . '/local-config.php';
        if (file_exists($configFile)) {
            $localConfig = include $configFile;
            if (is_array($localConfig)) {
                $this->config = array_merge($this->config, $localConfig);
            }
        }
    }

    /**
     * 🔗 建立資料庫連接
     */
    public function getConnection()
    {
        if ($this->pdo === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;charset=%s',
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['charset']
                );

                $this->pdo = new PDO(
                    $dsn,
                    $this->config['username'],
                    $this->config['password'],
                    $this->config['options']
                );

                // 檢查並創建資料庫
                $this->ensureDatabase();

                // 使用指定資料庫
                $this->pdo->exec("USE `{$this->config['database']}`");

                // 初始化資料表
                $this->initializeTables();

                $this->log("✅ 資料庫連接成功: {$this->config['host']}:{$this->config['port']}/{$this->config['database']}");
            } catch (PDOException $e) {
                $this->log("❌ 資料庫連接失敗: " . $e->getMessage());
                throw new Exception("資料庫連接失敗: " . $e->getMessage());
            }
        }

        return $this->pdo;
    }

    /**
     * 🏗️ 確保資料庫存在
     */
    private function ensureDatabase()
    {
        try {
            $sql = "CREATE DATABASE IF NOT EXISTS `{$this->config['database']}` 
                    CHARACTER SET utf8mb4 
                    COLLATE utf8mb4_unicode_ci";
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            $this->log("❌ 創建資料庫失敗: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 📊 初始化資料表
     */
    private function initializeTables()
    {
        $sqlFile = __DIR__ . '/../../database_setup.sql';
        if (!file_exists($sqlFile)) {
            $this->log("⚠️ 資料庫初始化檔案不存在: {$sqlFile}");
            return;
        }

        try {
            $sql = file_get_contents($sqlFile);

            // 移除 USE 語句和創建資料庫語句（已經處理過了）
            $sql = preg_replace('/CREATE DATABASE[^;]+;/', '', $sql);
            $sql = preg_replace('/USE[^;]+;/', '', $sql);

            // 分割並執行 SQL 語句
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function ($stmt) {
                    return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
                }
            );

            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $this->pdo->exec($statement);
                }
            }

            $this->log("✅ 資料庫表格檢查/創建完成");
        } catch (PDOException $e) {
            $this->log("❌ 資料庫初始化失敗: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 📊 獲取配置資訊
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 🔍 測試連接
     */
    public function testConnection()
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->query("SELECT 1");
            return [
                'success' => true,
                'message' => '資料庫連接正常',
                'environment' => $this->config['environment'],
                'host' => $this->config['host'],
                'database' => $this->config['database']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'environment' => $this->config['environment']
            ];
        }
    }

    /**
     * 📝 記錄日誌
     */
    private function log($message)
    {
        error_log("[" . date('c') . "] " . $message);

        // 如果是CLI模式，也輸出到控制台
        if (php_sapi_name() === 'cli') {
            echo "[" . date('c') . "] " . $message . "\n";
        }
    }

    /**
     * 🔒 防止複製
     */
    private function __clone() {}

    /**
     * 🔒 防止反序列化
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}

// 📊 資料庫操作輔助函數

/**
 * 🔗 獲取資料庫連接
 */
function getDbConnection()
{
    return DatabaseConfig::getInstance()->getConnection();
}

/**
 * 🔍 測試資料庫連接
 */
function testDbConnection()
{
    return DatabaseConfig::getInstance()->testConnection();
}

/**
 * 📊 獲取資料庫配置
 */
function getDbConfig()
{
    return DatabaseConfig::getInstance()->getConfig();
}

/**
 * 🧹 清理過期會話（資料庫版本）
 */
function cleanExpiredSessions($timeout = 1800) // 30分鐘
{
    try {
        $pdo = getDbConnection();
        $sql = "DELETE FROM user_login_logs 
                WHERE logout_time IS NULL 
                AND login_time < DATE_SUB(NOW(), INTERVAL :timeout SECOND)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['timeout' => $timeout]);
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("清理過期會話失敗: " . $e->getMessage());
        return 0;
    }
}

/**
 * 📊 獲取活躍用戶統計
 */
function getActiveUsersStats()
{
    try {
        $pdo = getDbConnection();
        $sql = "SELECT 
                    COUNT(DISTINCT user_name) as total_users,
                    COUNT(DISTINCT CASE WHEN is_teacher = 1 THEN user_name END) as teachers,
                    COUNT(DISTINCT CASE WHEN is_teacher = 0 THEN user_name END) as students,
                    COUNT(DISTINCT CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN user_name END) as recent_active
                FROM users";
        $stmt = $pdo->query($sql);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("獲取用戶統計失敗: " . $e->getMessage());
        return [
            'total_users' => 0,
            'teachers' => 0,
            'students' => 0,
            'recent_active' => 0
        ];
    }
}
