<?php

namespace App;

use PDO;
use PDOException;
use Exception;

/**
 * 簡化的數據庫管理類 - 專用於API調用
 * 移除所有調試輸出，確保純JSON響應
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
        
        return isset($_ENV['XAMPP_MODE']) || 
               (isset($_ENV['USE_MYSQL']) && $_ENV['USE_MYSQL'] === 'true');
    }
    
    /**
     * 初始化 MySQL 連接
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
                
                // 首先嘗試連接並創建數據庫（如果不存在）
                $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                $tempPdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // 創建數據庫（如果不存在）
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
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
            }
            
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
                
            // 創建表格（如果不存在）
            $this->createTables();
                
        } catch (PDOException $e) {
            error_log("MySQL 連接失敗，降級到本地存儲模式: " . $e->getMessage());
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
                    current_code TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            "
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                error_log("創建表格 {$tableName} 失敗: " . $e->getMessage());
            }
        }
    }
    
    /**
     * 查詢單條記錄
     */
    public function fetch($sql, $params = []) {
        if ($this->mode === 'mysql') {
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetch();
            } catch (PDOException $e) {
                error_log("查詢錯誤: " . $e->getMessage());
                return false;
            }
        } else {
            return $this->fetchLocal($sql, $params);
        }
    }
    
    /**
     * 查詢多條記錄
     */
    public function fetchAll($sql, $params = []) {
        if ($this->mode === 'mysql') {
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll();
            } catch (PDOException $e) {
                error_log("查詢錯誤: " . $e->getMessage());
                return [];
            }
        } else {
            return $this->fetchAllLocal($sql, $params);
        }
    }
    
    /**
     * 插入記錄
     */
    public function insert($table, $data) {
        if ($this->mode === 'mysql') {
            try {
                $columns = implode(',', array_keys($data));
                $placeholders = ':' . implode(', :', array_keys($data));
                
                $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($data);
                
                return $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                error_log("插入錯誤: " . $e->getMessage());
                return false;
            }
        } else {
            return $this->insertLocal($table, $data);
        }
    }
    
    /**
     * 更新記錄
     */
    public function update($table, $data, $where) {
        if ($this->mode === 'mysql') {
            try {
                $setParts = [];
                foreach ($data as $key => $value) {
                    $setParts[] = "{$key} = :{$key}";
                }
                $setClause = implode(', ', $setParts);
                
                $whereParts = [];
                foreach ($where as $key => $value) {
                    $whereParts[] = "{$key} = :where_{$key}";
                }
                $whereClause = implode(' AND ', $whereParts);
                
                $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
                
                $params = $data;
                foreach ($where as $key => $value) {
                    $params["where_{$key}"] = $value;
                }
                
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($params);
            } catch (PDOException $e) {
                error_log("更新錯誤: " . $e->getMessage());
                return false;
            }
        } else {
            return $this->updateLocal($table, $data, $where);
        }
    }
    
    // 本地存儲方法的簡化實現
    private function fetchLocal($sql, $params = []) {
        // 簡化的本地查詢實現
        return null;
    }
    
    private function fetchAllLocal($sql, $params = []) {
        // 簡化的本地查詢實現
        return [];
    }
    
    private function insertLocal($table, $data) {
        // 簡化的本地插入實現
        $id = $this->nextId++;
        $data['id'] = $id;
        $this->localStorage[$table][] = $data;
        return $id;
    }
    
    private function updateLocal($table, $data, $where) {
        // 簡化的本地更新實現
        return true;
    }
    
    private function loadLocalData() {
        // 載入本地數據的實現
    }
    
    /**
     * 檢查是否連接
     */
    public function isConnected() {
        return $this->pdo !== null;
    }
}
?> 