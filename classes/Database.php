<?php
/**
 * 增強型資料庫管理類 - 支援 MySQL 與 SQLite 雙模式
 * 自動偵測 XAMPP 環境，優雅降級到 SQLite
 */
class Database {
    private $pdo;
    private $isMySQL = false;
    private $dbConfig = [];
    
    public function __construct() {
        $this->loadConfig();
        $this->initializeConnection();
    }
    
    /**
     * 載入資料庫配置
     */
    private function loadConfig() {
        $this->dbConfig = [
            'mysql' => [
                'host' => $_ENV['MYSQL_HOST'] ?? $this->detectXAMPPHost(),
                'port' => $_ENV['MYSQL_PORT'] ?? $this->detectXAMPPPort(),
                'dbname' => $_ENV['MYSQL_DATABASE'] ?? 'pythonlearn_collaboration',
                'username' => $_ENV['MYSQL_USER'] ?? $this->detectXAMPPUser(),
                'password' => $_ENV['MYSQL_PASSWORD'] ?? $this->detectXAMPPPassword(),
                'charset' => 'utf8mb4',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 10,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            ],
            'sqlite' => [
                'path' => __DIR__ . '/../data/database.db'
            ]
        ];
    }
    
    /**
     * 偵測 XAMPP MySQL 主機（優先級: XAMPP > 系統 > 預設）
     */
    private function detectXAMPPHost() {
        // 強制優先使用 XAMPP MySQL
        $xamppPaths = [
            'C:/xampp/mysql/bin/mysql.exe',  // Windows XAMPP
            'C:/XAMPP/mysql/bin/mysql.exe',  // Windows XAMPP (大寫)
            'D:/xampp/mysql/bin/mysql.exe',  // D槽 XAMPP
            '/Applications/XAMPP/xamppfiles/bin/mysql', // macOS XAMPP
            '/opt/lampp/bin/mysql'  // Linux XAMPP
        ];
        
        foreach ($xamppPaths as $path) {
            if (file_exists($path)) {
                echo "   ✅ 偵測到 XAMPP 安裝: $path\n";
                return 'localhost'; // XAMPP 固定使用 localhost
            }
        }
        
        echo "   ⚠️ 未偵測到 XAMPP，使用預設 localhost\n";
        return 'localhost'; // 預設值
    }
    
    /**
     * 偵測 XAMPP MySQL 端口（智能衝突避免策略）
     */
    private function detectXAMPPPort() {
        // 智能端口檢測：優先避免與系統MySQL衝突
        $xamppPorts = [3307, 3308, 3309, 3306]; // 3306最後嘗試
        
        echo "   🔍 智能掃描 MySQL 端口（避免系統衝突）...\n";
        foreach ($xamppPorts as $port) {
            if ($this->testMySQLPort('localhost', $port)) {
                echo "   ✅ 發現可用的 MySQL 端口: $port\n";
                if ($port == 3306) {
                    echo "   ⚠️ 使用標準端口 3306 (可能是系統MySQL)\n";
                } else {
                    echo "   🎯 使用 XAMPP 專用端口: $port (避免衝突)\n";
                }
                return $port;
            } else {
                echo "   ❌ 端口 $port 無響應\n";
            }
        }
        
        echo "   ⚠️ 所有端口掃描失敗，使用預設 3307 (XAMPP推薦)\n";
        return 3307; // 預設使用3307，這是常見的XAMPP替代端口
    }
    
    /**
     * 偵測 XAMPP MySQL 用戶名
     */
    private function detectXAMPPUser() {
        // 常見的 XAMPP MySQL 用戶名
        return 'root';
    }
    
    /**
     * 偵測 MySQL 密碼（支援XAMPP和系統MySQL）
     */
    private function detectXAMPPPassword() {
        // 使用已經檢測到的端口，如果沒有則檢測
        $port = $this->detectXAMPPPort();
        $host = 'localhost';
        
        // 根據端口調整密碼嘗試策略
        if ($port == 3306) {
            // 端口3306可能是系統MySQL，增加系統MySQL常見密碼
            $commonPasswords = [
                '',              // XAMPP/系統 預設空密碼
                'root',          // 最常見的root密碼
                'mysql',         // MySQL預設密碼
                'password',      // 通用密碼
                '123456',        // 簡單數字密碼
                'admin',         // 管理員密碼
                'xampp',         // XAMPP專用
                'localhost',     // 一些系統使用
                '1234',          // 更簡單的密碼
                'qwerty'         // 鍵盤序列密碼
            ];
        } else {
            // 非標準端口，更可能是XAMPP
            $commonPasswords = [
                '',          // XAMPP 預設空密碼
                'xampp',     // XAMPP 專用密碼
                'root',      // 常見 root 密碼
                'password',  // 通用密碼
                'mysql',     // MySQL 預設
                '123456',    // 簡單密碼
                'admin'      // 管理員密碼
            ];
        }
        
        echo "   🔐 嘗試常見密碼組合...\n";
        foreach ($commonPasswords as $password) {
            $passwordDisplay = empty($password) ? '(空密碼)' : $password;
            echo "   🔑 嘗試密碼: $passwordDisplay\n";
            
            if ($this->testMySQLCredentials($host, $port, 'root', $password)) {
                echo "   ✅ 認證成功: $passwordDisplay\n";
                return $password;
            }
        }
        
        echo "   ❌ 所有密碼嘗試失敗\n";
        return ''; // 預設空密碼
    }
    
    /**
     * 測試 MySQL 端口連通性
     */
    private function testMySQLPort($host, $port) {
        $connection = @fsockopen($host, $port, $errno, $errstr, 1);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }
    
    /**
     * 測試 MySQL 認證資訊
     */
    private function testMySQLCredentials($host, $port, $username, $password) {
        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $testPdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2
            ]);
            $testPdo = null;
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * 初始化資料庫連接
     */
    private function initializeConnection() {
        echo "🔍 正在初始化資料庫連接...\n";
        echo "================================\n";
        
        // 優先嘗試 MySQL 連接
        echo "📊 MySQL 連接嘗試:\n";
        echo "   主機: {$this->dbConfig['mysql']['host']}:{$this->dbConfig['mysql']['port']}\n";
        echo "   資料庫: {$this->dbConfig['mysql']['dbname']}\n";
        echo "   用戶: {$this->dbConfig['mysql']['username']}\n";
        echo "   密碼: " . (empty($this->dbConfig['mysql']['password']) ? '(空密碼)' : '(已設定)') . "\n";
        
        // 執行多階段 MySQL 連接測試
        if ($this->attemptMySQLConnection()) {
            $this->isMySQL = true;
            $this->initializeMySQLTables();
            echo "✅ MySQL 模式已啟用\n";
            echo "================================\n";
        } else {
            echo "⚠️ MySQL 連接失敗，啟用 SQLite 降級模式\n";
            echo "================================\n";
            $this->connectToSQLite();
            $this->initializeSQLiteTables();
            echo "✅ SQLite 模式已啟用\n";
            echo "================================\n";
        }
    }
    
    /**
     * 嘗試 MySQL 連接（多階段測試）
     */
    private function attemptMySQLConnection() {
        // 階段 1: 測試基礎連接
        echo "   🔸 階段 1: 測試 MySQL 服務連通性...\n";
        if (!$this->testMySQLPort($this->dbConfig['mysql']['host'], $this->dbConfig['mysql']['port'])) {
            echo "      ❌ MySQL 服務未響應\n";
            return false;
        }
        echo "      ✅ MySQL 服務響應正常\n";
        
        // 階段 2: 測試認證
        echo "   🔸 階段 2: 測試 MySQL 認證...\n";
        if (!$this->testMySQLCredentials(
            $this->dbConfig['mysql']['host'], 
            $this->dbConfig['mysql']['port'],
            $this->dbConfig['mysql']['username'], 
            $this->dbConfig['mysql']['password']
        )) {
            echo "      ❌ MySQL 認證失敗\n";
            return false;
        }
        echo "      ✅ MySQL 認證成功\n";
        
        // 階段 3: 創建/連接資料庫
        echo "   🔸 階段 3: 準備專案資料庫...\n";
        if (!$this->connectToMySQL()) {
            echo "      ❌ 資料庫連接失敗\n";
            return false;
        }
        echo "      ✅ 專案資料庫連接成功\n";
        
        return true;
    }
    
    /**
     * 連接到 MySQL
     */
    private function connectToMySQL() {
        try {
            // 首先嘗試創建資料庫
            $this->createMySQLDatabase();
            
            // 連接到指定資料庫
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $this->dbConfig['mysql']['host'],
                $this->dbConfig['mysql']['port'],
                $this->dbConfig['mysql']['dbname'],
                $this->dbConfig['mysql']['charset']
            );
            
            $options = $this->dbConfig['mysql']['options'];
            // 添加重連和保持連接活躍的選項
            $options[PDO::ATTR_PERSISTENT] = false; // 不使用持久連接避免"gone away"
            $options[PDO::ATTR_TIMEOUT] = 30;
            // 注意：PDO::MYSQL_ATTR_RECONNECT 在某些 PHP 版本中不可用
            
            $this->pdo = new PDO(
                $dsn,
                $this->dbConfig['mysql']['username'],
                $this->dbConfig['mysql']['password'],
                $options
            );
            
            return true;
            
        } catch (PDOException $e) {
            echo "      MySQL 連接錯誤: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * 創建 MySQL 資料庫（如果不存在）
     */
    private function createMySQLDatabase() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;charset=%s",
                $this->dbConfig['mysql']['host'],
                $this->dbConfig['mysql']['port'],
                $this->dbConfig['mysql']['charset']
            );
            
            $tempPdo = new PDO(
                $dsn,
                $this->dbConfig['mysql']['username'],
                $this->dbConfig['mysql']['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
            );
            
            $dbName = $this->dbConfig['mysql']['dbname'];
            $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $tempPdo->exec($sql);
            
            $tempPdo = null;
            echo "   資料庫 {$dbName} 準備就緒\n";
            
        } catch (PDOException $e) {
            // 靜默失敗，可能是權限問題
        }
    }
    
    /**
     * 連接到 SQLite
     */
    private function connectToSQLite() {
        try {
            // 確保資料目錄存在
            $dataDir = dirname($this->dbConfig['sqlite']['path']);
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            
            $this->pdo = new PDO(
                'sqlite:' . $this->dbConfig['sqlite']['path'],
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            // 啟用外鍵約束
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            
        } catch (PDOException $e) {
            throw new Exception("SQLite 初始化失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 初始化 MySQL 表結構
     */
    private function initializeMySQLTables() {
        $tables = [
            'rooms' => "
                CREATE TABLE IF NOT EXISTS rooms (
                    id VARCHAR(50) PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    current_code LONGTEXT,
                    user_count INT DEFAULT 0,
                    max_users INT DEFAULT 10,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'room_users' => "
                CREATE TABLE IF NOT EXISTS room_users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(50) NOT NULL,
                    user_id VARCHAR(50) NOT NULL,
                    username VARCHAR(50) NOT NULL,
                    join_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    is_online BOOLEAN DEFAULT TRUE,
                    user_role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
                    UNIQUE KEY unique_room_user (room_id, user_id),
                    INDEX idx_room_online (room_id, is_online)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'code_history' => "
                CREATE TABLE IF NOT EXISTS code_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(50) NOT NULL,
                    user_id VARCHAR(50) NOT NULL,
                    username VARCHAR(50) NOT NULL,
                    code_content LONGTEXT,
                    slot_id INT NOT NULL DEFAULT 0,
                    save_name VARCHAR(200) DEFAULT NULL,
                    operation_type ENUM('save', 'auto_save', 'load', 'import') DEFAULT 'save',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_room_slot (room_id, slot_id),
                    INDEX idx_room_slot (room_id, slot_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'chat_messages' => "
                CREATE TABLE IF NOT EXISTS chat_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(50) NOT NULL,
                    user_id VARCHAR(50) NOT NULL,
                    username VARCHAR(50) NOT NULL,
                    message TEXT NOT NULL,
                    message_type ENUM('user', 'system', 'ai', 'teacher') DEFAULT 'user',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_room_created (room_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'ai_interactions' => "
                CREATE TABLE IF NOT EXISTS ai_interactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(50) NOT NULL,
                    user_id VARCHAR(50) NOT NULL,
                    username VARCHAR(50) NOT NULL,
                    interaction_type ENUM('explain', 'check_errors', 'suggest_improvements', 'analyze_conflict', 'answer_question') NOT NULL,
                    user_input TEXT NOT NULL,
                    ai_response TEXT NOT NULL,
                    response_time_ms INT DEFAULT NULL,
                    tokens_used INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_room_created (room_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",

            'code_changes' => "
                CREATE TABLE IF NOT EXISTS code_changes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(50) NOT NULL,
                    user_id VARCHAR(50) NOT NULL,
                    change_type ENUM('insert', 'delete', 'replace', 'paste', 'load', 'import', 'edit') DEFAULT 'edit',
                    code_content LONGTEXT,
                    position_data JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_room_created (room_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",

            'code_executions' => "
                CREATE TABLE IF NOT EXISTS code_executions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(50) NOT NULL,
                    user_id VARCHAR(50) NOT NULL,
                    code LONGTEXT,
                    output TEXT,
                    error TEXT,
                    success BOOLEAN DEFAULT FALSE,
                    execution_time DECIMAL(10,2) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_room_executions (room_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->pdo->exec($sql);
                echo "✅ MySQL 表 {$tableName} 創建成功\n";
            } catch (PDOException $e) {
                echo "❌ MySQL 表 {$tableName} 創建失敗: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * 初始化 SQLite 表結構
     */
    private function initializeSQLiteTables() {
        $tables = [
            'rooms' => "
                CREATE TABLE IF NOT EXISTS rooms (
                    id TEXT PRIMARY KEY,
                    name TEXT NOT NULL,
                    current_code TEXT,
                    user_count INTEGER DEFAULT 0,
                    max_users INTEGER DEFAULT 10,
                    is_active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",
            
            'room_users' => "
                CREATE TABLE IF NOT EXISTS room_users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    room_id TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    username TEXT NOT NULL,
                    join_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
                    is_online INTEGER DEFAULT 1,
                    user_role TEXT DEFAULT 'student',
                    UNIQUE(room_id, user_id)
                )
            ",
            
            'code_history' => "
                CREATE TABLE IF NOT EXISTS code_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    room_id TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    username TEXT NOT NULL,
                    code_content TEXT,
                    slot_id INTEGER NOT NULL DEFAULT 0,
                    save_name TEXT,
                    operation_type TEXT DEFAULT 'save',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(room_id, slot_id)
                )
            ",
            
            'chat_messages' => "
                CREATE TABLE IF NOT EXISTS chat_messages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    room_id TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    username TEXT NOT NULL,
                    message TEXT NOT NULL,
                    message_type TEXT DEFAULT 'user',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",
            
            'ai_interactions' => "
                CREATE TABLE IF NOT EXISTS ai_interactions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    room_id TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    username TEXT NOT NULL,
                    interaction_type TEXT NOT NULL,
                    user_input TEXT NOT NULL,
                    ai_response TEXT NOT NULL,
                    response_time_ms INTEGER,
                    tokens_used INTEGER,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",

            'code_changes' => "
                CREATE TABLE IF NOT EXISTS code_changes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    room_id TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    change_type TEXT DEFAULT 'edit',
                    code_content TEXT,
                    position_data TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",

            'code_executions' => "
                CREATE TABLE IF NOT EXISTS code_executions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    room_id TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    code TEXT,
                    output TEXT,
                    error TEXT,
                    success INTEGER DEFAULT 0,
                    execution_time REAL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            "
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->pdo->exec($sql);
                echo "✅ SQLite 表 {$tableName} 創建成功\n";
            } catch (PDOException $e) {
                echo "❌ SQLite 表 {$tableName} 創建失敗: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // ============================================
    // 代碼管理核心功能
    // ============================================
    
    /**
     * 保存代碼 - 支援5槽位系統
     * @param string $roomId 房間ID
     * @param string $userId 用戶ID
     * @param string $code 代碼內容
     * @param string|null $saveName 保存名稱
     * @param int|null $slotId 槽位ID (0=最新, 1-4=命名槽位, null=自動選擇)
     * @return array 保存結果
     */
    public function saveCode($roomId, $userId, $code, $saveName = null, $slotId = null) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. 更新房間當前代碼
            if ($this->isMySQL) {
                $updateRoomSql = "
                    INSERT INTO rooms (id, name, current_code, updated_at) 
                    VALUES (?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    current_code = VALUES(current_code), updated_at = NOW()
                ";
            } else {
                $updateRoomSql = "
                    INSERT INTO rooms (id, name, current_code, updated_at) 
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP) 
                    ON CONFLICT(id) DO UPDATE SET 
                    current_code = excluded.current_code, updated_at = CURRENT_TIMESTAMP
                ";
            }
                     
            $stmt = $this->pdo->prepare($updateRoomSql);
            $stmt->execute([$roomId, $roomId, $code]);
            
            // 2. 確定槽位ID
            if ($slotId === null) {
                // 如果沒有指定槽位，默認保存到槽位0（最新）
                $slotId = 0;
            }
            
            // 驗證槽位ID範圍 (0-4)
            if ($slotId < 0 || $slotId > 4) {
                throw new Exception("無效的槽位ID: {$slotId}，槽位範圍為0-4");
            }
            
            // 3. 準備保存名稱
            if ($slotId === 0) {
                // 槽位0始終是"最新"
                $finalSaveName = '最新';
            } else {
                // 槽位1-4使用用戶提供的名稱或默認名稱
                $finalSaveName = $saveName ?: "記錄 {$slotId}";
            }
            
            // 4. 檢查當前槽位是否已有記錄
            $checkSql = "SELECT id FROM code_history WHERE room_id = ? AND slot_id = ?";
            $stmt = $this->pdo->prepare($checkSql);
            $stmt->execute([$roomId, $slotId]);
            $existingRecord = $stmt->fetch();
            
            if ($existingRecord) {
                // 更新現有記錄
                if ($this->isMySQL) {
                    $updateSql = "
                        UPDATE code_history 
                        SET user_id = ?, username = ?, code_content = ?, save_name = ?, created_at = NOW()
                        WHERE room_id = ? AND slot_id = ?
                    ";
                } else {
                    $updateSql = "
                        UPDATE code_history 
                        SET user_id = ?, username = ?, code_content = ?, save_name = ?, created_at = CURRENT_TIMESTAMP
                        WHERE room_id = ? AND slot_id = ?
                    ";
                }
                
                $stmt = $this->pdo->prepare($updateSql);
                $stmt->execute([$userId, $userId, $code, $finalSaveName, $roomId, $slotId]);
                $historyId = $existingRecord['id'];
            } else {
                // 創建新記錄
                if ($this->isMySQL) {
                    $insertSql = "
                        INSERT INTO code_history (room_id, user_id, username, code_content, save_name, slot_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ";
                } else {
                    $insertSql = "
                        INSERT INTO code_history (room_id, user_id, username, code_content, save_name, slot_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                    ";
                }
                
                $stmt = $this->pdo->prepare($insertSql);
                $stmt->execute([$roomId, $userId, $userId, $code, $finalSaveName, $slotId]);
                $historyId = $this->pdo->lastInsertId();
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'slot_id' => $slotId,
                'history_id' => $historyId,
                'save_name' => $finalSaveName,
                'timestamp' => date('c'),
                'is_update' => isset($existingRecord)
            ];
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("保存代碼失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 載入代碼 - 支援槽位系統
     * @param string $roomId 房間ID
     * @param int|string|null $version 版本號或槽位ID，null表示最新版本
     * @return array 代碼資料
     */
    public function loadCode($roomId, $version = null) {
        try {
            if ($version === null || $version === 'latest') {
                // 載入房間當前代碼或槽位0
                $sql = "SELECT current_code as code_content, 'latest' as slot_id FROM rooms WHERE id = ?";
                $params = [$roomId];
            } elseif (is_numeric($version) && $version >= 0 && $version <= 4) {
                // 載入特定槽位
                $sql = "SELECT code_content, slot_id, save_name, created_at FROM code_history WHERE room_id = ? AND slot_id = ?";
                $params = [$roomId, (int)$version];
            } else {
                // 兼容舊的版本號系統（如果需要）
                $sql = "SELECT code_content, slot_id, save_name, created_at FROM code_history WHERE room_id = ? AND id = ?";
                $params = [$roomId, $version];
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'success' => true,
                    'code' => $result['code_content'] ?? '',
                    'slot_id' => $result['slot_id'] ?? null,
                    'save_name' => $result['save_name'] ?? null,
                    'timestamp' => $result['created_at'] ?? date('c')
                ];
            }
            
            // 如果沒有找到代碼，返回預設代碼
            return [
                'success' => true,
                'code' => '# 歡迎使用 Python 協作學習平台\nprint("Hello, World!")\n\n# 在這裡開始你的 Python 學習之旅！',
                'slot_id' => 0,
                'save_name' => '預設代碼',
                'timestamp' => date('c')
            ];
            
        } catch (PDOException $e) {
            throw new Exception("載入代碼失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 獲取代碼歷史 - 5槽位系統
     * @param string $roomId 房間ID
     * @param int $limit 限制數量（保留兼容性，但實際固定為5）
     * @return array 歷史記錄結果
     */
    public function getCodeHistory($roomId, $limit = 5) {
        try {
            // 獲取5個槽位的記錄（slot_id 0-4）
            $sql = "
                SELECT id, room_id, user_id, username, code_content, slot_id, save_name, created_at
                FROM code_history 
                WHERE room_id = ? AND slot_id IN (0, 1, 2, 3, 4)
                ORDER BY slot_id ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId]);
            $records = $stmt->fetchAll();
            
            // 創建完整的5槽位結構
            $slots = [];
            for ($i = 0; $i < 5; $i++) {
                $slots[$i] = [
                    'slot_id' => $i,
                    'id' => null,
                    'save_name' => $i === 0 ? '最新' : "記錄 " . $i,
                    'user_id' => null,
                    'username' => null,
                    'code_content' => '',
                    'created_at' => null,
                    'is_empty' => true
                ];
            }
            
            // 填入實際的記錄
            foreach ($records as $record) {
                $slotId = (int)$record['slot_id'];
                if ($slotId >= 0 && $slotId < 5) {
                    $slots[$slotId] = [
                        'slot_id' => $slotId,
                        'id' => $record['id'],
                        'save_name' => $record['save_name'] ?: ($slotId === 0 ? '最新' : "記錄 " . $slotId),
                        'user_id' => $record['user_id'],
                        'username' => $record['username'],
                        'code_content' => $record['code_content'],
                        'created_at' => $record['created_at'],
                        'is_empty' => false
                    ];
                }
            }
            
            return [
                'success' => true,
                'history' => array_values($slots),
                'total' => count($records)
            ];
            
        } catch (PDOException $e) {
            echo "獲取代碼歷史錯誤: " . $e->getMessage() . "\n";
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'history' => []
            ];
        }
    }
    
    /**
     * 刪除特定槽位的記錄
     * @param string $roomId 房間ID
     * @param int $slotId 槽位ID (1-4，槽位0不可刪除)
     * @return array 刪除結果
     */
    public function deleteCodeSlot($roomId, $slotId) {
        try {
            // 驗證槽位ID（槽位0不可刪除）
            if ($slotId < 1 || $slotId > 4) {
                throw new Exception("無效的槽位ID: {$slotId}，只能刪除槽位1-4");
            }
            
            $sql = "DELETE FROM code_history WHERE room_id = ? AND slot_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$roomId, $slotId]);
            
            return [
                'success' => $result,
                'deleted_slot' => $slotId,
                'affected_rows' => $stmt->rowCount()
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => "刪除槽位失敗: " . $e->getMessage()
            ];
        }
    }
    
    // ============================================
    // 房間管理功能
    // ============================================
    
    /**
     * 檢查並修復MySQL連接
     */
    private function ensureMySQLConnection() {
        if (!$this->isMySQL || !$this->pdo) {
            return false;
        }
        
        try {
            // 測試連接是否仍然有效
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'server has gone away') !== false) {
                echo "⚠️ MySQL 連接中斷，嘗試重新連接...\n";
                // 重新建立連接
                return $this->connectToMySQL();
            }
            return false;
        }
    }
    
    /**
     * 用戶加入房間
     * @param string $roomId 房間ID
     * @param string $userId 用戶ID
     * @param string $username 用戶名稱
     * @param string $userRole 用戶角色
     * @return array 結果
     */
    public function joinRoom($roomId, $userId, $username, $userRole = 'student') {
        // 檢查MySQL連接
        if ($this->isMySQL && !$this->ensureMySQLConnection()) {
            return ['success' => false, 'error' => '資料庫連接已中斷'];
        }
        
        try {
            // 確保房間存在
            $this->createRoomIfNotExists($roomId);
            
            // 加入用戶到房間
            if ($this->isMySQL) {
                $sql = "
                    INSERT INTO room_users (room_id, user_id, username, user_role, join_time, last_activity, is_online) 
                    VALUES (?, ?, ?, ?, NOW(), NOW(), 1) 
                    ON DUPLICATE KEY UPDATE 
                    is_online = 1, last_activity = NOW()
                ";
            } else {
                $sql = "
                    INSERT INTO room_users (room_id, user_id, username, user_role, join_time, last_activity, is_online) 
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1) 
                    ON CONFLICT(room_id, user_id) DO UPDATE SET 
                    is_online = 1, last_activity = CURRENT_TIMESTAMP
                ";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId, $userId, $username, $userRole]);
            
            $this->updateRoomUserCount($roomId);
            
            return ['success' => true, 'message' => '成功加入房間'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => '加入房間失敗: ' . $e->getMessage()];
        }
    }
    
    /**
     * 用戶離開房間
     * @param string $roomId 房間ID
     * @param string $userId 用戶ID
     * @return array 結果
     */
    public function leaveRoom($roomId, $userId) {
        try {
            $sql = "UPDATE room_users SET is_online = 0, last_activity = " . 
                   ($this->isMySQL ? "NOW()" : "CURRENT_TIMESTAMP") . 
                   " WHERE room_id = ? AND user_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId, $userId]);
            
            $this->updateRoomUserCount($roomId);
            
            return ['success' => true, 'message' => '成功離開房間'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => '離開房間失敗: ' . $e->getMessage()];
        }
    }
    
    /**
     * 創建房間（如果不存在）
     * @param string $roomId 房間ID
     */
    private function createRoomIfNotExists($roomId) {
        try {
            if ($this->isMySQL) {
                $sql = "
                    INSERT INTO rooms (id, name, is_active, created_at)
                    VALUES (?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE updated_at = NOW()
                ";
            } else {
            $sql = "
                    INSERT INTO rooms (id, name, is_active, created_at)
                    VALUES (?, ?, 1, CURRENT_TIMESTAMP)
                    ON CONFLICT(id) DO UPDATE SET updated_at = CURRENT_TIMESTAMP
                ";
            }
                     
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId, $roomId]);
            
        } catch (PDOException $e) {
            // 靜默失敗
        }
    }
    
    /**
     * 獲取房間用戶列表
     * @param string $roomId 房間ID
     * @return array 用戶列表
     */
    public function getRoomUsers($roomId) {
        try {
            $sql = "
                SELECT user_id, username, join_time, last_activity, user_role 
                FROM room_users 
                WHERE room_id = ? AND is_online = 1
                ORDER BY join_time ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * 更新房間用戶數
     * @param string $roomId 房間ID
     */
    private function updateRoomUserCount($roomId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM room_users WHERE room_id = ? AND is_online = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId]);
            $userCount = $stmt->fetchColumn();
            
            $updateSql = "UPDATE rooms SET user_count = ?, updated_at = " . 
                        ($this->isMySQL ? "NOW()" : "CURRENT_TIMESTAMP") . 
                        " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($updateSql);
            $stmt->execute([$userCount, $roomId]);
            
        } catch (PDOException $e) {
            // 靜默失敗
        }
    }
    
    // ============================================
    // 聊天功能
    // ============================================
    
    /**
     * 保存聊天訊息
     * @param string $roomId 房間ID
     * @param string $userId 用戶ID
     * @param string $username 用戶名稱
     * @param string $message 訊息內容
     * @param string $messageType 訊息類型
     * @return array 結果
     */
    public function saveChatMessage($roomId, $userId, $username, $message, $messageType = 'user') {
        try {
            $sql = "
                INSERT INTO chat_messages (room_id, user_id, username, message, message_type, created_at) 
                VALUES (?, ?, ?, ?, ?, " . ($this->isMySQL ? "NOW()" : "CURRENT_TIMESTAMP") . ")
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId, $userId, $username, $message, $messageType]);
            
            return [
                'success' => true,
                'message_id' => $this->pdo->lastInsertId(),
                'timestamp' => date('c')
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => '保存訊息失敗: ' . $e->getMessage()];
        }
    }
    
    /**
     * 獲取聊天歷史
     * @param string $roomId 房間ID
     * @param int $limit 限制數量
     * @return array 聊天記錄
     */
    public function getChatHistory($roomId, $limit = 50) {
        try {
            $sql = "
                SELECT username, message, message_type, created_at 
                FROM chat_messages 
                WHERE room_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId, $limit]);
            
            $messages = $stmt->fetchAll();
            return array_reverse($messages);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // ============================================
    // AI 互動記錄
    // ============================================
    
    /**
     * 記錄 AI 互動
     * @param string $roomId 房間ID
     * @param string $userId 用戶ID
     * @param string $username 用戶名稱
     * @param string $interactionType 互動類型
     * @param string $userInput 用戶輸入
     * @param string $aiResponse AI 回應
     * @param int|null $responseTime 回應時間
     * @param int|null $tokensUsed 使用的token數量
     * @return array 結果
     */
    public function recordAIInteraction($roomId, $userId, $username, $interactionType, $userInput, $aiResponse, $responseTime = null, $tokensUsed = null) {
        try {
            $sql = "
                INSERT INTO ai_interactions 
                (room_id, user_id, username, interaction_type, user_input, ai_response, response_time_ms, tokens_used, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, " . ($this->isMySQL ? "NOW()" : "CURRENT_TIMESTAMP") . ")
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $roomId, $userId, $username, $interactionType,
                $userInput, $aiResponse, $responseTime, $tokensUsed
            ]);
            
            return ['success' => true, 'interaction_id' => $this->pdo->lastInsertId()];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => '記錄 AI 互動失敗: ' . $e->getMessage()];
        }
    }
    
    // ============================================
    // 系統管理
    // ============================================
    
    /**
     * 清理不活躍用戶
     * @param int $minutesInactive 不活躍分鐘數
     * @return int 清理數量
     */
    public function cleanupInactiveUsers($minutesInactive = 10) {
        try {
            $sql = "
                UPDATE room_users 
                SET is_online = 0 
                WHERE is_online = 1 
                AND last_activity < " . 
                ($this->isMySQL ? "DATE_SUB(NOW(), INTERVAL ? MINUTE)" : "datetime('now', '-' || ? || ' minutes')");
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$minutesInactive]);
            
            $cleanedCount = $stmt->rowCount();
            
            if ($cleanedCount > 0) {
                // 更新所有房間用戶數
                $this->updateAllRoomUserCounts();
            }
            
            return $cleanedCount;
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * 更新所有房間用戶數
     */
    private function updateAllRoomUserCounts() {
        try {
            if ($this->isMySQL) {
                $sql = "
                    UPDATE rooms r 
                    SET user_count = (
                        SELECT COUNT(*) 
                        FROM room_users ru 
                        WHERE ru.room_id = r.id AND ru.is_online = 1
                    )
                ";
            } else {
                $sql = "
                    UPDATE rooms 
                    SET user_count = (
                        SELECT COUNT(*) 
                        FROM room_users 
                        WHERE room_id = rooms.id AND is_online = 1
                    )
                ";
            }
            
            $this->pdo->exec($sql);
            
        } catch (PDOException $e) {
            // 靜默失敗
        }
    }
    
    /**
     * 獲取系統狀態
     * @return array 狀態資訊
     */
    public function getStatus() {
        try {
            // 計算表數量
            $tablesCount = 0;
            if ($this->pdo) {
                try {
                    if ($this->isMySQL) {
                        $stmt = $this->pdo->query("SHOW TABLES");
                        $tablesCount = $stmt->rowCount();
                    } else {
                        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
                        $tablesCount = $stmt->rowCount();
                    }
                } catch (Exception $e) {
                    $tablesCount = 0;
                }
            }
            
            $stats = [
            'connected' => $this->pdo !== null,
            'type' => $this->isMySQL ? 'MySQL' : 'SQLite',
                'tables_count' => $tablesCount,
                'config' => $this->isMySQL ? $this->dbConfig['mysql'] : $this->dbConfig['sqlite']
            ];
            
            // 統計資訊
            $queries = [
                'active_rooms' => "SELECT COUNT(*) FROM rooms WHERE is_active = 1",
                'online_users' => "SELECT COUNT(*) FROM room_users WHERE is_online = 1",
                'total_saves' => "SELECT COUNT(*) FROM code_history",
                'total_chats' => "SELECT COUNT(*) FROM chat_messages",
                'ai_interactions' => "SELECT COUNT(*) FROM ai_interactions"
            ];
            
            foreach ($queries as $key => $sql) {
                try {
                    $stmt = $this->pdo->query($sql);
                    $stats[$key] = $stmt->fetchColumn();
                } catch (PDOException $e) {
                    $stats[$key] = 0;
                }
            }
            
            return $stats;
            
        } catch (Exception $e) {
            return [
                'connected' => false,
                'type' => 'Unknown',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 更新用戶活動時間
     * @param string $userId 用戶ID
     * @param string $roomId 房間ID
     * @param string $username 用戶名稱
     */
    public function updateUserActivity($userId, $roomId, $username) {
        try {
            if ($this->isMySQL) {
                $sql = "
                    INSERT INTO room_users (room_id, user_id, username, last_activity) 
                    VALUES (?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE last_activity = NOW()
                ";
            } else {
                $sql = "
                    INSERT INTO room_users (room_id, user_id, username, last_activity) 
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP) 
                    ON CONFLICT(room_id, user_id) DO UPDATE SET last_activity = CURRENT_TIMESTAMP
                ";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId, $userId, $username]);
            
        } catch (PDOException $e) {
            // 靜默失敗
        }
    }

    /**
     * 通用插入方法 - 用於WebSocket服務器
     * @param string $table 表名
     * @param array $data 數據陣列
     * @return int|bool 插入ID或false
     */
    public function insert($table, $data) {
        try {
            if (empty($data)) {
                return false;
            }

            // 自動添加創建時間
            if (!isset($data['created_at'])) {
                $data['created_at'] = $this->isMySQL ? null : date('Y-m-d H:i:s');
            }

            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');
            
            $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            // 如果是MySQL且有created_at字段為null，使用NOW()
            if ($this->isMySQL && isset($data['created_at']) && $data['created_at'] === null) {
                $nowIndex = array_search('created_at', $columns);
                $placeholders[$nowIndex] = 'NOW()';
                unset($data['created_at']);
                $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute(array_values($data));
            
            if ($success) {
                return $this->pdo->lastInsertId();
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Database insert error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 通用查詢方法 - 用於WebSocket服務器
     * @param string $sql SQL查詢語句
     * @param array $params 參數陣列
     * @return array|false 查詢結果或false
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 通用單行查詢方法
     * @param string $sql SQL查詢語句
     * @param array $params 參數陣列
     * @return array|false 查詢結果或false
     */
    public function fetch($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database fetch error: " . $e->getMessage());
            return false;
        }
    }
}