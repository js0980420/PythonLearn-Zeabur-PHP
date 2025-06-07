<?php

namespace App;

use PDO;
use PDOException;
use Exception;

/**
 * 數據庫管理類
 * 支援 MySQL（雲端）和 localStorage 降級（本地）
 */
class Database {
    private static $instance = null;
    private $pdo = null;
    private $mode = 'mysql';
    private $localStorage = [];
    private $nextId = 1;
    
    private function __construct() {
        $this->initializeDatabase();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 初始化數據庫連接
     */
    private function initializeDatabase() {
        // 1. 優先檢查 Zeabur 雲端環境
        if ($this->isZeaburEnvironment()) {
            $this->initializeMySQL('zeabur');
        } 
        // 2. 檢查 XAMPP 本地環境
        elseif ($this->isXAMPPEnvironment()) {
            $this->initializeMySQL('xampp');
        } 
        // 3. 降級到本地存儲模式
        else {
            $this->initializeLocalStorage();
        }
    }
    
    /**
     * 檢查是否在 Zeabur 環境
     */
    private function isZeaburEnvironment() {
        return isset($_ENV['ZEABUR_DOMAIN']) || 
               isset($_ENV['DATABASE_URL']) || 
               isset($_ENV['MYSQL_HOST']);
    }
    
    /**
     * 檢查是否在 XAMPP 環境
     */
    private function isXAMPPEnvironment() {
        // 檢查 XAMPP 常見路徑和環境標識
        $xamppPaths = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\xampp\\apache\\bin\\httpd.exe',
            '/opt/lampp/bin/mysql',
            '/Applications/XAMPP/xamppfiles/bin/mysql'
        ];
        
        foreach ($xamppPaths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }
        
        // 檢查環境變數或手動設定
        return isset($_ENV['XAMPP_MODE']) || 
               (isset($_ENV['USE_MYSQL']) && $_ENV['USE_MYSQL'] === 'true');
    }
    
    /**
     * 初始化 MySQL 連接（支援 Zeabur 雲端 和 XAMPP 本地）
     */
    private function initializeMySQL($environment = 'zeabur') {
        try {
            if ($environment === 'xampp') {
                // XAMPP 預設配置
                $host = $_ENV['MYSQL_HOST'] ?? 'localhost';
                $port = $_ENV['MYSQL_PORT'] ?? '3306';
                $dbname = $_ENV['MYSQL_DATABASE'] ?? 'pythonlearn_collaboration';
                $username = $_ENV['MYSQL_USER'] ?? 'root';
                $password = $_ENV['MYSQL_PASSWORD'] ?? '';
                
                echo "🔧 嘗試連接 XAMPP MySQL 數據庫...\n";
                echo "   主機: {$host}:{$port}\n";
                echo "   數據庫: {$dbname}\n";
                echo "   用戶: {$username}\n";
                
                // 首先嘗試連接並創建數據庫（如果不存在）
                $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                $tempPdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // 創建數據庫（如果不存在）
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "✅ 數據庫 '{$dbname}' 確認存在\n";
                
                // 連接到指定數據庫
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                
            } else {
                // Zeabur 雲端配置
                $host = $_ENV['MYSQL_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost';
                $port = $_ENV['MYSQL_PORT'] ?? $_ENV['DB_PORT'] ?? '3306';
                $dbname = $_ENV['MYSQL_DATABASE'] ?? $_ENV['DB_NAME'] ?? 'pythonlearn';
                $username = $_ENV['MYSQL_USER'] ?? $_ENV['DB_USER'] ?? 'root';
                $password = $_ENV['MYSQL_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '';
                
                // 專案專用數據庫配置
                if (!$dbname || $dbname === 'pythonlearn') {
                    $dbname = 'pythonlearn_collaboration';
                }
                
                // 如果有 DATABASE_URL（某些雲端平台使用）
                if (isset($_ENV['DATABASE_URL'])) {
                    $url = parse_url($_ENV['DATABASE_URL']);
                    $host = $url['host'];
                    $port = $url['port'] ?? 3306;
                    $dbname = ltrim($url['path'], '/');
                    $username = $url['user'];
                    $password = $url['pass'];
                }
                
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                echo "�� 嘗試連接 Zeabur MySQL 數據庫...\n";
            }
            
            $this->pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
                
            // 創建表格（如果不存在）
            $this->createTables();
                
            echo "✅ MySQL 數據庫連接成功 ({$environment} 模式)\n";
            
        } catch (PDOException $e) {
            echo "❌ MySQL 連接失敗，降級到本地存儲模式: " . $e->getMessage() . "\n";
            $this->initializeLocalStorage();
        }
    }
    
    /**
     * 初始化本地存儲模式
     */
    private function initializeLocalStorage() {
        $this->mode = 'localStorage';
        $this->localStorage = [
            'users' => [],
            'rooms' => [],
            'room_users' => [],
            'code_history' => [],
            'code_changes' => [],
            'conflicts' => [],
            'ai_requests' => [],
            'code_executions' => [],
            'system_logs' => []
        ];
        
        // 載入本地存儲的數據（如果存在）
        $this->loadLocalData();
        
        echo "✅ 本地存儲模式已啟用\n";
    }
    
    /**
     * 創建 MySQL 表格
     */
    private function createTables() {
        if ($this->mode !== 'mysql') return;
        
        $tables = [
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    user_type ENUM('student', 'teacher') DEFAULT 'student',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            
            'rooms' => "
                CREATE TABLE IF NOT EXISTS rooms (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_name VARCHAR(100) NOT NULL,
                    room_id VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NULL,
                    created_by INT,
                    max_users INT DEFAULT 10,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            
            'room_users' => "
                CREATE TABLE IF NOT EXISTS room_users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(100) NOT NULL,
                    user_id INT NOT NULL,
                    username VARCHAR(50) NOT NULL,
                    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE,
                    INDEX idx_room_user (room_id, user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            
            'code_history' => "
                CREATE TABLE IF NOT EXISTS code_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(100) NOT NULL,
                    user_id VARCHAR(50) NOT NULL,
                    username VARCHAR(50) NOT NULL,
                    code_content TEXT,
                    slot_id INT DEFAULT 0,
                    save_name VARCHAR(255) DEFAULT '',
                    operation_type VARCHAR(50) DEFAULT 'save',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_room_slot (room_id, slot_id),
                    INDEX idx_room_history (room_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            
            'code_executions' => "
                CREATE TABLE IF NOT EXISTS code_executions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(100) NOT NULL,
                    user_id VARCHAR(50) NOT NULL,
                    code TEXT,
                    output TEXT,
                    error TEXT,
                    success BOOLEAN DEFAULT FALSE,
                    execution_time DECIMAL(10,2) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_room_executions (room_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            
            'ai_requests' => "
                CREATE TABLE IF NOT EXISTS ai_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(100),
                    user_id VARCHAR(50) NOT NULL,
                    request_type VARCHAR(50) NOT NULL,
                    prompt TEXT,
                    response TEXT,
                    execution_time DECIMAL(10,6),
                    token_usage INT,
                    success BOOLEAN DEFAULT FALSE,
                    error_message TEXT,
                    request_data TEXT,
                    response_data TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_room_ai (room_id, created_at),
                    INDEX idx_user_ai (user_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            "
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->pdo->exec($sql);
                echo "✅ 表格 {$tableName} 創建/檢查完成\n";
        } catch (PDOException $e) {
                echo "❌ 創建表格 {$tableName} 失敗: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * 執行查詢（單行結果）
     */
    public function fetch($sql, $params = []) {
        if ($this->mode === 'mysql') {
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo "❌ 查詢錯誤: " . $e->getMessage() . "\n";
                return null;
            }
        } else {
            return $this->fetchLocal($sql, $params);
        }
    }
    
    /**
     * 執行查詢（多行結果）
     */
    public function fetchAll($sql, $params = []) {
        if ($this->mode === 'mysql') {
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo "❌ 查詢錯誤: " . $e->getMessage() . "\n";
                return [];
            }
        } else {
            return $this->fetchAllLocal($sql, $params);
        }
    }
    
    /**
     * 插入數據
     */
    public function insert($table, $data) {
        // 如果是本地存儲模式，在插入時自動處理ID和時間戳
        if ($this->mode === 'localStorage' && !isset($data['id'])) {
            $data['id'] = $this->nextId++;
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
        }
        
        // 為 code_history 表自動設置槽位ID
        if ($table === 'code_history' && !isset($data['slot_id'])) {
            $data['slot_id'] = $data['slot_id'] ?? 0; // 默認使用槽位0
        }

        if ($this->mode === 'mysql') {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            
            return $this->pdo->lastInsertId();
        } else {
            $id = $data['id'];
            $this->localStorage[$table][] = $data;
            $this->saveToLocalStorage(); // 確保數據持久化
            return $id;
        }
    }
    
    /**
     * 更新數據
     */
    public function update($table, $data, $where) {
        if ($this->mode === 'mysql') {
            try {
                $setClause = [];
                foreach ($data as $key => $value) {
                    $setClause[] = "{$key} = :{$key}";
                }
                
                $whereClause = [];
                foreach ($where as $key => $value) {
                    $whereClause[] = "{$key} = :where_{$key}";
                    $data["where_{$key}"] = $value;
                }
                
                $sql = "UPDATE {$table} SET " . implode(', ', $setClause) . 
                       " WHERE " . implode(' AND ', $whereClause);
                
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($data);
            } catch (PDOException $e) {
                echo "❌ 更新錯誤: " . $e->getMessage() . "\n";
                return false;
            }
        } else {
            return $this->updateLocal($table, $data, $where);
        }
    }
    
    /**
     * 刪除數據
     */
    public function delete($table, $where) {
        if ($this->mode === 'mysql') {
            try {
                $whereClause = [];
                foreach ($where as $key => $value) {
                    $whereClause[] = "{$key} = :{$key}";
                }
                
                $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClause);
                
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($where);
            } catch (PDOException $e) {
                echo "❌ 刪除錯誤: " . $e->getMessage() . "\n";
                return false;
            }
        } else {
            return $this->deleteLocal($table, $where);
        }
    }
    
    /**
     * 執行原生 SQL
     */
    public function execute($sql, $params = []) {
        if ($this->mode === 'mysql') {
            try {
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($params);
            } catch (PDOException $e) {
                echo "❌ SQL 執行錯誤: " . $e->getMessage() . "\n";
                return false;
            }
        } else {
            // 本地模式不支援原生 SQL，返回 false
            echo "⚠️ 本地模式不支援原生 SQL 執行\n";
            return false;
        }
    }
    
    // ==================== 本地存儲模式方法 ====================
    
    /**
     * 本地模式查詢（單行）
     */
    private function fetchLocal($sql, $params = []) {
        // 處理槽位查詢 - 改為支援槽位系統
        if (strpos($sql, 'SELECT') !== false && strpos($sql, 'slot_id') !== false) {
            $roomId = $params['room_id'] ?? '';
            $slotId = $params['slot_id'] ?? 0;
            
            foreach ($this->localStorage['code_history'] as $record) {
                if ($record['room_id'] == $roomId && $record['slot_id'] == $slotId) {
                    return $record;
                }
            }
            
            return null;
        }
        
        // 簡化的 SQL 解析和模擬 - 改為槽位系統
        if (strpos($sql, 'SELECT code_content') !== false || 
            (strpos($sql, 'FROM code_history') !== false && strpos($sql, 'ORDER BY') !== false)) {
            $roomId = $params['room_id'] ?? '';
            $slotId = $params['slot_id'] ?? 0;
            
            // 按槽位ID查找，時間降序查找最新的代碼
            $latestRecord = null;
            $latestTime = 0;
            
            foreach ($this->localStorage['code_history'] as $record) {
                if ($record['room_id'] == $roomId && $record['slot_id'] == $slotId) {
                    $recordTime = strtotime($record['created_at']);
                    
                    if ($recordTime > $latestTime) {
                        $latestTime = $recordTime;
                        $latestRecord = $record;
                    }
                }
            }
            
            if ($latestRecord) {
                return [
                    'code_content' => $latestRecord['code_content'],
                    'created_at' => $latestRecord['created_at'],
                    'user_id' => $latestRecord['user_id'],
                    'username' => $latestRecord['username'] ?? $latestRecord['user_id'],
                    'slot_id' => $latestRecord['slot_id'] ?? 0,
                    'save_name' => $latestRecord['save_name'] ?? '程式碼載入',
                    'operation_type' => $latestRecord['operation_type'] ?? 'save'
                ];
            }
            
            return ['code_content' => '# 歡迎使用Python協作平台\nprint("Hello, World!")'];
        }
        
        // 處理其他類型的查詢
        if (strpos($sql, 'SELECT') !== false && strpos($sql, 'WHERE') !== false) {
            // 通用的單行查詢處理
            $tableName = $this->extractTableName($sql);
            if ($tableName && isset($this->localStorage[$tableName])) {
                foreach ($this->localStorage[$tableName] as $record) {
                    if ($this->matchesWhereClause($record, $params)) {
                        return $record;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * 本地模式查詢（多行）
     */
    private function fetchAllLocal($sql, $params = []) {
        if (strpos($sql, 'SELECT * FROM code_history WHERE room_id') !== false) {
            $roomId = $params['room_id'] ?? '';
            $limit = 20; // 默認限制
            
            $history = [];
            foreach ($this->localStorage['code_history'] as $record) {
                if ($record['room_id'] == $roomId) {
                    $history[] = $record;
                }
            }
            
            // 按槽位ID和時間降序排列
            usort($history, function($a, $b) {
                $slotA = $a['slot_id'] ?? 0;
                $slotB = $b['slot_id'] ?? 0;
                
                if ($slotA == $slotB) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                }
                
                return $slotA - $slotB; // 槽位0-4順序排列
            });
            
            return array_slice($history, 0, $limit);
        }
        
        return [];
    }
    
    /**
     * 本地模式更新
     */
    private function updateLocal($table, $data, $where) {
        if (!isset($this->localStorage[$table])) {
            return false;
        }
        
        foreach ($this->localStorage[$table] as &$row) {
            $match = true;
            foreach ($where as $key => $value) {
                if (!isset($row[$key]) || $row[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                foreach ($data as $key => $value) {
                    $row[$key] = $value;
                }
                $row['updated_at'] = date('Y-m-d H:i:s');
                $this->saveToLocalStorage();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 本地模式刪除
     */
    private function deleteLocal($table, $where) {
        if (!isset($this->localStorage[$table])) {
            return false;
        }
        
        $deleted = false;
        foreach ($this->localStorage[$table] as $index => $row) {
            $match = true;
            foreach ($where as $key => $value) {
                if (!isset($row[$key]) || $row[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                unset($this->localStorage[$table][$index]);
                $deleted = true;
            }
        }
        
        if ($deleted) {
            $this->localStorage[$table] = array_values($this->localStorage[$table]);
            $this->saveToLocalStorage();
        }
        
        return $deleted;
    }
    
    /**
     * 載入本地數據
     */
    private function loadLocalData() {
        $dataFile = __DIR__ . '/../../storage/local_database.json';
        
        if (file_exists($dataFile)) {
            $jsonData = file_get_contents($dataFile);
            $data = json_decode($jsonData, true);
            
            if ($data) {
                $this->localStorage = array_merge($this->localStorage, $data);
                $this->nextId = $this->getMaxId() + 1;
            }
        }
    }
    
    /**
     * 保存本地數據
     */
    private function saveToLocalStorage() {
        $storageDir = __DIR__ . '/../../storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        $dataFile = $storageDir . '/local_database.json';
        file_put_contents($dataFile, json_encode($this->localStorage, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 獲取最大 ID
     */
    private function getMaxId() {
        $maxId = 0;
        foreach ($this->localStorage as $table => $records) {
            foreach ($records as $record) {
                if (isset($record['id']) && $record['id'] > $maxId) {
                    $maxId = $record['id'];
                }
            }
        }
        return $maxId;
    }
    
    /**
     * 獲取數據庫狀態
     */
    public function getStatus() {
                return [
            'mode' => $this->mode,
            'connected' => $this->isConnected(),
            'tables_count' => $this->mode === 'mysql' ? 'N/A' : count($this->localStorage),
            'environment' => $this->isZeaburEnvironment() ? 'Zeabur Cloud' : 'Local Development'
        ];
    }
    
    /**
     * 檢查連接狀態
     */
    public function isConnected() {
        return $this->mode === 'mysql' ? ($this->pdo !== null) : true;
    }
    
    /**
     * 從 SQL 中提取表名
     */
    private function extractTableName($sql) {
        // 簡單的表名提取
        if (preg_match('/FROM\s+(\w+)/i', $sql, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * 檢查記錄是否匹配 WHERE 條件
     */
    private function matchesWhereClause($record, $params) {
        foreach ($params as $key => $value) {
            if (!isset($record[$key]) || $record[$key] != $value) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * 清理本地存儲數據
     */
    public function clearLocalStorage() {
        if ($this->mode === 'localStorage') {
            $this->localStorage = [
                'users' => [],
                'rooms' => [],
                'room_users' => [],
                'code_history' => [],
                'code_changes' => [],
                'conflicts' => [],
                'ai_requests' => [],
                'code_executions' => [],
                'system_logs' => []
            ];
            $this->saveToLocalStorage();
            return true;
        }
        return false;
    }
    
    /**
     * 獲取本地存儲統計信息
     */
    public function getLocalStorageStats() {
        if ($this->mode !== 'localStorage') {
            return null;
        }
        
        $stats = [];
        foreach ($this->localStorage as $table => $records) {
            $stats[$table] = count($records);
        }
        
        return $stats;
    }
    
    public function fetchById($table, $id) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } else {
            // 本地存儲模式
            if (isset($this->localStorage[$table])) {
                foreach ($this->localStorage[$table] as $record) {
                    if (isset($record['id']) && $record['id'] == $id) {
                        return $record;
                    }
                }
            }
            return null;
        }
    }
    
    private function getMaxSlotId($roomId, $table) {
        $maxSlot = 0;
        if (isset($this->localStorage[$table])) {
            foreach ($this->localStorage[$table] as $record) {
                if (($record['room_id'] ?? null) == $roomId) {
                    $slot = $record['slot_id'] ?? 0;
                    if ($slot > $maxSlot) {
                        $maxSlot = $slot;
                    }
                }
            }
        }
        return $maxSlot;
    }
    
    public function initTables() {
        if ($this->mode === 'mysql' && $this->pdo) {
            $this->createTables();
        } else if ($this->mode === 'localStorage') {
            if (!isset($this->localStorage['code_history'])) {
                $this->localStorage['code_history'] = [];
            }
            // 可以為其他表做類似的初始化
             if (!isset($this->localStorage['rooms'])) {
                $this->localStorage['rooms'] = [];
            }
             if (!isset($this->localStorage['users'])) {
                $this->localStorage['users'] = [];
            }
        }
    }
}

?> 