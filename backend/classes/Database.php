<?php

// 引入 composer autoload
require_once __DIR__ . '/../../vendor/autoload.php';

class Database {
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct() {
        $this->config = require __DIR__ . '/../config/database.php';
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            // 檢查是否能連接到 XAMPP MySQL
            $mysqlDsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );
            
            // 嘗試連接 MySQL
            try {
                $this->connection = new PDO(
                    $mysqlDsn,
                    $this->config['username'],
                    $this->config['password'],
                    $this->config['options']
                );
                
                echo "<!-- ✅ 已連接到 XAMPP MySQL 數據庫 -->\n";
                
            } catch (PDOException $mysqlException) {
                // MySQL 連接失敗，降級到 SQLite
                echo "<!-- ⚠️ MySQL 連接失敗，使用 SQLite: " . $mysqlException->getMessage() . " -->\n";
                
                $dbPath = __DIR__ . '/../../data/database.sqlite';
                $dbDir = dirname($dbPath);
                
                // 確保data目錄存在
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }
                
                $sqliteDsn = "sqlite:$dbPath";
                
                $this->connection = new PDO($sqliteDsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                // 啟用外鍵約束
                $this->connection->exec('PRAGMA foreign_keys = ON');
                
                echo "<!-- ✅ 已降級到 SQLite 數據庫 -->\n";
            }
            
        } catch (PDOException $e) {
            throw new Exception("資料庫連接失敗: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("查詢執行失敗: " . $e->getMessage());
        }
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->connection->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        return $this->query($sql, $params);
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params);
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * 檢查當前使用的數據庫類型
     */
    public function getDatabaseType() {
        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        return $driver;
    }
    
    /**
     * 創建表格 - 支援 MySQL 和 SQLite
     */
    public function createTables() {
        $isMySQL = $this->getDatabaseType() === 'mysql';
        
        if ($isMySQL) {
            $this->createMySQLTables();
        } else {
            $this->createSQLiteTables();
        }
    }
    
    /**
     * 創建 MySQL 表格
     */
    private function createMySQLTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            user_type VARCHAR(20) NOT NULL DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_name VARCHAR(200) NOT NULL,
            description TEXT,
            max_users INT DEFAULT 10,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS room_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(100) NOT NULL,
            user_id INT NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            left_at TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX idx_room_user (room_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS code_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(100) NOT NULL,
            user_id INT NOT NULL,
            code LONGTEXT NOT NULL,
            version INT NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX idx_room_version (room_id, version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS conflicts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conflict_id VARCHAR(100) NOT NULL UNIQUE,
            room_id VARCHAR(100) NOT NULL,
            conflict_type ENUM('LINE_CONFLICT', 'REGION_CONFLICT', 'SYNTAX_CONFLICT', 'LOGIC_CONFLICT') NOT NULL,
            affected_lines JSON,
            users_involved JSON,
            original_code TEXT,
            conflicted_versions JSON,
            status ENUM('pending', 'resolved', 'escalated') DEFAULT 'pending',
            resolution ENUM('accept', 'reject', 'share', 'ai_analyze') NULL,
            resolved_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL,
            FOREIGN KEY (resolved_by) REFERENCES users(id),
            INDEX idx_room_status (room_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS ai_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            request_type ENUM('explain', 'check_errors', 'suggest_improvements', 'analyze_conflict', 'answer_question') NOT NULL,
            prompt TEXT NOT NULL,
            response TEXT,
            execution_time FLOAT,
            token_usage INT,
            success BOOLEAN DEFAULT TRUE,
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX idx_user_type (user_id, request_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // 分割並執行每個 CREATE TABLE 語句
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $this->connection->exec($statement);
            }
        }
    }
    
    /**
     * 創建 SQLite 表格
     */
    private function createSQLiteTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            user_type VARCHAR(20) NOT NULL DEFAULT 'student',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS rooms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_name VARCHAR(200) NOT NULL,
            description TEXT,
            max_users INTEGER DEFAULT 10,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS room_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id VARCHAR(100) NOT NULL,
            user_id INTEGER NOT NULL,
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            left_at DATETIME NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS code_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id VARCHAR(100) NOT NULL,
            user_id INTEGER NOT NULL,
            code TEXT NOT NULL,
            version INTEGER NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS conflicts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            conflict_id VARCHAR(100) NOT NULL UNIQUE,
            room_id VARCHAR(100) NOT NULL,
            conflict_type VARCHAR(50) NOT NULL,
            affected_lines TEXT,
            users_involved TEXT,
            original_code TEXT,
            conflicted_versions TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            resolution VARCHAR(20) NULL,
            resolved_by INTEGER NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME NULL,
            FOREIGN KEY (resolved_by) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS ai_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            request_type VARCHAR(50) NOT NULL,
            prompt TEXT NOT NULL,
            response TEXT,
            execution_time REAL,
            token_usage INTEGER,
            success BOOLEAN DEFAULT TRUE,
            error_message TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        ";
        
        // 分割並執行每個 CREATE TABLE 語句
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $this->connection->exec($statement);
            }
        }
    }
    
    /**
     * 初始化數據庫 - 創建表格並插入測試數據
     */
    public function initialize() {
        $this->createTables();
        $this->insertTestData();
    }
    
    /**
     * 插入測試數據
     */
    private function insertTestData() {
        try {
            // 檢查是否已有數據
            $userCount = $this->fetch("SELECT COUNT(*) as count FROM users")['count'];
            if ($userCount > 0) {
                return; // 已有數據，跳過初始化
            }
            
            // 插入測試用戶
            $testUsers = [
                ['username' => 'teacher', 'user_type' => 'teacher'],
                ['username' => '學生A', 'user_type' => 'student'],
                ['username' => '學生B', 'user_type' => 'student'],
                ['username' => '張三', 'user_type' => 'student'],
                ['username' => '李四', 'user_type' => 'student']
            ];
            
            foreach ($testUsers as $user) {
                $this->insert('users', $user);
            }
            
            echo "<!-- ✅ 已插入測試數據 -->\n";
            
        } catch (Exception $e) {
            echo "<!-- ⚠️ 插入測試數據失敗: " . $e->getMessage() . " -->\n";
        }
    }
    
    /**
     * 獲取數據庫狀態信息
     */
    public function getStatus() {
        try {
            if (!$this->connection) {
                return [
                    'connected' => false,
                    'type' => 'none',
                    'error' => '數據庫未連接'
                ];
            }
            
            $databaseType = $this->getDatabaseType();
            $status = [
                'connected' => true,
                'type' => $databaseType === 'mysql' ? 'MySQL' : 'SQLite',
                'driver' => $databaseType
            ];
            
            // 獲取表數量
            try {
                if ($databaseType === 'mysql') {
                    $result = $this->fetch("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()");
                } else {
                    $result = $this->fetch("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                }
                $status['tables_count'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                $status['tables_count'] = 0;
                $status['table_error'] = $e->getMessage();
            }
            
            // 獲取用戶數量
            try {
                $result = $this->fetch("SELECT COUNT(*) as count FROM users");
                $status['users_count'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                $status['users_count'] = 0;
                $status['users_error'] = '用戶表不存在或無法訪問';
            }
            
            // 獲取房間數量
            try {
                $result = $this->fetch("SELECT COUNT(*) as count FROM rooms");
                $status['rooms_count'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                $status['rooms_count'] = 0;
                $status['rooms_error'] = '房間表不存在或無法訪問';
            }
            
            return $status;
            
        } catch (Exception $e) {
            return [
                'connected' => false,
                'type' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
}

?> 