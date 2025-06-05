<?php

namespace App;

use PDO;
use PDOException;
use Exception;

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
            // 使用SQLite進行開發測試
            $dbPath = __DIR__ . '/../../data/database.sqlite';
            $dbDir = dirname($dbPath);
            
            // 確保data目錄存在
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            $dsn = "sqlite:$dbPath";
            
            $this->connection = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // 啟用外鍵約束
            $this->connection->exec('PRAGMA foreign_keys = ON');
            
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
    
    public function createTables() {
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
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(100) NOT NULL,
            user_id INT NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            left_at TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX idx_room_user (room_id, user_id)
        );
        
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
        );
        
        CREATE TABLE IF NOT EXISTS code_changes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(100) NOT NULL,
            user_id INT NOT NULL,
            change_type ENUM('insert', 'delete', 'replace') NOT NULL,
            line_number INT NOT NULL,
            content TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX idx_room_timestamp (room_id, timestamp)
        );
        
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
        );
        
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
            INDEX idx_user_type (user_id, request_type),
            INDEX idx_created_at (created_at)
        );
        
        CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL,
            message TEXT NOT NULL,
            context JSON,
            file VARCHAR(255),
            line INT,
            memory_usage BIGINT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_level_time (level, created_at)
        );
        ";
        
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $this->query($statement);
            }
        }
    }
} 