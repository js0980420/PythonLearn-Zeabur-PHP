<?php
/**
 * 數據庫管理類
 * 支援 MySQL 主數據庫和 SQLite 降級機制
 */
class Database {
    private $pdo;
    private $sqlitePdo;
    private $isMySQL;
    private $dbConfig;
    
    public function __construct() {
        $this->loadConfig();
        $this->initializeConnections();
    }
    
    /**
     * 載入數據庫配置
     */
    private function loadConfig() {
        // 優先使用環境變數（Zeabur 部署）
        $this->dbConfig = [
            'mysql' => [
                'host' => $_ENV['MYSQL_HOST'] ?? 'localhost',
                'port' => $_ENV['MYSQL_PORT'] ?? 3306,
                'dbname' => $_ENV['MYSQL_DATABASE'] ?? 'python_collaboration',
                'username' => $_ENV['MYSQL_USER'] ?? 'root',
                'password' => $_ENV['MYSQL_PASSWORD'] ?? '',
                'charset' => 'utf8mb4'
            ],
            'sqlite' => [
                'path' => __DIR__ . '/../data/app.db'
            ]
        ];
    }
    
    /**
     * 初始化數據庫連接
     */
    private function initializeConnections() {
        $this->isMySQL = false;
        
        // 嘗試連接 MySQL
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $this->dbConfig['mysql']['host'],
                $this->dbConfig['mysql']['port'],
                $this->dbConfig['mysql']['dbname'],
                $this->dbConfig['mysql']['charset']
            );
            
            $this->pdo = new PDO(
                $dsn,
                $this->dbConfig['mysql']['username'],
                $this->dbConfig['mysql']['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            $this->isMySQL = true;
            $this->initializeMySQLTables();
            
            echo "✅ MySQL 連接成功\n";
            
        } catch (PDOException $e) {
            echo "⚠️ MySQL 連接失敗，使用 SQLite 降級: " . $e->getMessage() . "\n";
            $this->initializeSQLite();
        }
    }
    
    /**
     * 初始化 SQLite 連接
     */
    private function initializeSQLite() {
        try {
            // 確保數據目錄存在
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
            
            $this->initializeSQLiteTables();
            echo "✅ SQLite 連接成功\n";
            
        } catch (PDOException $e) {
            throw new Exception("數據庫連接完全失敗: " . $e->getMessage());
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
                    current_code TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id VARCHAR(50) PRIMARY KEY,
                    username VARCHAR(50) NOT NULL,
                    room_id VARCHAR(50),
                    join_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            'code_history' => "
                CREATE TABLE IF NOT EXISTS code_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(50) NOT NULL,
                    user_id VARCHAR(50) NOT NULL,
                    code_content TEXT,
                    version_number INT,
                    save_name VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            'chat_messages' => "
                CREATE TABLE IF NOT EXISTS chat_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id VARCHAR(50) NOT NULL,
                    user_id VARCHAR(50) NOT NULL,
                    username VARCHAR(50) NOT NULL,
                    message TEXT NOT NULL,
                    message_type ENUM('user', 'system', 'ai') DEFAULT 'user',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
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
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ",
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id TEXT PRIMARY KEY,
                    username TEXT NOT NULL,
                    room_id TEXT,
                    join_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (room_id) REFERENCES rooms(id)
                )
            ",
            'code_history' => "
                CREATE TABLE IF NOT EXISTS code_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    room_id TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    code_content TEXT,
                    version_number INTEGER,
                    save_name TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (room_id) REFERENCES rooms(id)
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
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (room_id) REFERENCES rooms(id)
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
    
    /**
     * 保存代碼到數據庫
     * @param string $roomId 房間ID
     * @param string $userId 用戶ID
     * @param string $code 代碼內容
     * @param string|null $saveName 保存名稱
     * @return array 保存結果
     */
    public function saveCode($roomId, $userId, $code, $saveName = null) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. 更新房間當前代碼
            $updateRoomSql = "
                INSERT INTO rooms (id, name, current_code, updated_at) 
                VALUES (?, ?, ?, " . ($this->isMySQL ? "NOW()" : "CURRENT_TIMESTAMP") . ") 
                ON " . ($this->isMySQL ? "DUPLICATE KEY UPDATE" : "CONFLICT(id) DO") . "
                " . ($this->isMySQL ? "current_code = VALUES(current_code), updated_at = NOW()" : 
                     "UPDATE SET current_code = excluded.current_code, updated_at = CURRENT_TIMESTAMP");
                     
            $stmt = $this->pdo->prepare($updateRoomSql);
            $stmt->execute([$roomId, $roomId, $code]);
            
            // 2. 獲取下一個版本號
            $versionSql = "SELECT COALESCE(MAX(version_number), 0) + 1 as next_version FROM code_history WHERE room_id = ?";
            $stmt = $this->pdo->prepare($versionSql);
            $stmt->execute([$roomId]);
            $nextVersion = $stmt->fetchColumn();
            
            // 3. 插入代碼歷史記錄
            $historySql = "
                INSERT INTO code_history (room_id, user_id, code_content, version_number, save_name, created_at) 
                VALUES (?, ?, ?, ?, ?, " . ($this->isMySQL ? "NOW()" : "CURRENT_TIMESTAMP") . ")
            ";
            $stmt = $this->pdo->prepare($historySql);
            $stmt->execute([$roomId, $userId, $code, $nextVersion, $saveName]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'version' => $nextVersion,
                'save_name' => $saveName,
                'timestamp' => date('c')
            ];
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("保存代碼失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 載入代碼
     * @param string $roomId 房間ID
     * @param int|null $version 版本號，null為最新版本
     * @return array|null 代碼數據
     */
    public function loadCode($roomId, $version = null) {
        try {
            if ($version === null) {
                // 載入房間當前代碼
                $sql = "SELECT current_code as code_content, 'latest' as version_number FROM rooms WHERE id = ?";
                $params = [$roomId];
            } else {
                // 載入特定版本
                $sql = "SELECT code_content, version_number, save_name, created_at FROM code_history WHERE room_id = ? AND version_number = ?";
                $params = [$roomId, $version];
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'success' => true,
                    'code' => $result['code_content'] ?? '',
                    'version' => $result['version_number'],
                    'save_name' => $result['save_name'] ?? null,
                    'timestamp' => $result['created_at'] ?? date('c')
                ];
            }
            
            return null;
            
        } catch (PDOException $e) {
            throw new Exception("載入代碼失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 獲取房間代碼歷史
     * @param string $roomId 房間ID
     * @param int $limit 限制數量
     * @return array 歷史記錄
     */
    public function getCodeHistory($roomId, $limit = 20) {
        try {
            $sql = "
                SELECT version_number, save_name, created_at, user_id 
                FROM code_history 
                WHERE room_id = ? 
                ORDER BY version_number DESC 
                LIMIT ?
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId, $limit]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new Exception("獲取歷史記錄失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 更新用戶活動時間
     * @param string $userId 用戶ID
     * @param string $roomId 房間ID
     * @param string $username 用戶名
     */
    public function updateUserActivity($userId, $roomId, $username) {
        try {
            $sql = "
                INSERT INTO users (id, username, room_id, last_activity) 
                VALUES (?, ?, ?, " . ($this->isMySQL ? "NOW()" : "CURRENT_TIMESTAMP") . ") 
                ON " . ($this->isMySQL ? "DUPLICATE KEY UPDATE" : "CONFLICT(id) DO") . "
                " . ($this->isMySQL ? "room_id = VALUES(room_id), last_activity = NOW()" : 
                     "UPDATE SET room_id = excluded.room_id, last_activity = CURRENT_TIMESTAMP");
                     
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $username, $roomId]);
            
        } catch (PDOException $e) {
            echo "⚠️ 更新用戶活動失敗: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 獲取房間內活躍用戶
     * @param string $roomId 房間ID
     * @return array 用戶列表
     */
    public function getRoomUsers($roomId) {
        try {
            $sql = "
                SELECT id, username, join_time, last_activity 
                FROM users 
                WHERE room_id = ? 
                AND last_activity > DATE_SUB(" . ($this->isMySQL ? "NOW()" : "CURRENT_TIMESTAMP") . ", INTERVAL 5 MINUTE)
                ORDER BY join_time ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roomId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            echo "⚠️ 獲取房間用戶失敗: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    /**
     * 保存聊天消息
     * @param string $roomId 房間ID
     * @param string $userId 用戶ID
     * @param string $username 用戶名
     * @param string $message 消息內容
     * @param string $messageType 消息類型
     * @return array 保存結果
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
            throw new Exception("保存聊天消息失敗: " . $e->getMessage());
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
            return array_reverse($messages); // 最舊的在前面
            
        } catch (PDOException $e) {
            echo "⚠️ 獲取聊天歷史失敗: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    /**
     * 檢查數據庫連接狀態
     * @return array 狀態信息
     */
    public function getStatus() {
        return [
            'connected' => $this->pdo !== null,
            'type' => $this->isMySQL ? 'MySQL' : 'SQLite',
            'tables_count' => $this->getTablesCount()
        ];
    }
    
    /**
     * 獲取表數量
     * @return int 表數量
     */
    private function getTablesCount() {
        try {
            if ($this->isMySQL) {
                $sql = "SHOW TABLES";
            } else {
                $sql = "SELECT name FROM sqlite_master WHERE type='table'";
            }
            
            $stmt = $this->pdo->query($sql);
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            return 0;
        }
    }
}
?> 