<?php

/**
 * 🗄️ PythonLearn 資料庫管理器
 * 支援 XAMPP 本地和 Zeabur 雲端 MySQL
 */

require_once __DIR__ . '/../config.php';

class DatabaseManager
{
    private static $instance = null;
    private $connection = null;
    private $config = [];

    private function __construct()
    {
        $this->config = [
            'host' => MYSQL_HOST,
            'user' => MYSQL_USER,
            'password' => MYSQL_PASSWORD,
            'database' => MYSQL_DATABASE,
            'port' => MYSQL_PORT,
            'charset' => 'utf8mb4'
        ];

        $this->connect();
        $this->createTables();
    }

    /**
     * 獲取單例實例
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 建立資料庫連接
     */
    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};charset={$this->config['charset']}";

            $this->connection = new PDO($dsn, $this->config['user'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);

            // 建立資料庫（如果不存在）
            $this->connection->exec("CREATE DATABASE IF NOT EXISTS `{$this->config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->connection->exec("USE `{$this->config['database']}`");

            error_log("✅ 資料庫連接成功: {$this->config['host']}:{$this->config['port']}/{$this->config['database']}");
        } catch (PDOException $e) {
            error_log("❌ 資料庫連接失敗: " . $e->getMessage());
            throw new Exception("資料庫連接失敗: " . $e->getMessage());
        }
    }

    /**
     * 創建必要的表格
     */
    private function createTables()
    {
        try {
            // 用戶代碼保存表
            $sql = "
                CREATE TABLE IF NOT EXISTS user_code_saves (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_name VARCHAR(255) NOT NULL,
                    slot_id INT NOT NULL DEFAULT 0,
                    slot_name VARCHAR(255) NOT NULL DEFAULT '未命名',
                    code_content LONGTEXT NOT NULL,
                    is_latest BOOLEAN NOT NULL DEFAULT FALSE,
                    is_auto_save BOOLEAN NOT NULL DEFAULT FALSE,
                    room_id VARCHAR(255) DEFAULT 'general-room',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_slot (user_name, slot_id),
                    INDEX idx_user_name (user_name),
                    INDEX idx_latest (user_name, is_latest),
                    INDEX idx_updated (updated_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";

            $this->connection->exec($sql);

            // 用戶活動記錄表
            $sql = "
                CREATE TABLE IF NOT EXISTS user_activity_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_name VARCHAR(255) NOT NULL,
                    action_type VARCHAR(50) NOT NULL,
                    slot_id INT DEFAULT NULL,
                    slot_name VARCHAR(255) DEFAULT NULL,
                    code_length INT DEFAULT 0,
                    room_id VARCHAR(255) DEFAULT 'general-room',
                    ip_address VARCHAR(45) DEFAULT NULL,
                    user_agent TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_activity (user_name, created_at),
                    INDEX idx_action_type (action_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";

            $this->connection->exec($sql);

            error_log("✅ 資料庫表格檢查/創建完成");
        } catch (PDOException $e) {
            error_log("❌ 創建表格失敗: " . $e->getMessage());
            throw new Exception("創建表格失敗: " . $e->getMessage());
        }
    }

    /**
     * 保存用戶代碼到指定槽位
     */
    public function saveUserCode($userName, $slotId, $slotName, $codeContent, $isLatest = false, $roomId = 'general-room')
    {
        try {
            // 如果是保存到最新槽位，先清除其他的最新標記
            if ($isLatest || $slotId == 0) {
                $sql = "UPDATE user_code_saves SET is_latest = FALSE WHERE user_name = ? AND is_latest = TRUE";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$userName]);
            }

            // 插入或更新代碼保存記錄
            $sql = "
                INSERT INTO user_code_saves (user_name, slot_id, slot_name, code_content, is_latest, is_auto_save, room_id, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    slot_name = VALUES(slot_name),
                    code_content = VALUES(code_content),
                    is_latest = VALUES(is_latest),
                    is_auto_save = VALUES(is_auto_save),
                    room_id = VALUES(room_id),
                    updated_at = NOW()
            ";

            $stmt = $this->connection->prepare($sql);
            $isAutoSave = ($slotId == 0);
            $isLatestFlag = ($isLatest || $slotId == 0);

            $result = $stmt->execute([
                $userName,
                $slotId,
                $slotName,
                $codeContent,
                $isLatestFlag,
                $isAutoSave,
                $roomId
            ]);

            if ($result) {
                // 記錄活動日誌
                $this->logUserActivity($userName, 'save', $slotId, $slotName, strlen($codeContent), $roomId);

                error_log("✅ 用戶 {$userName} 代碼已保存到槽位 {$slotId}: {$slotName}");
                return [
                    'success' => true,
                    'message' => "代碼已保存到 {$slotName}",
                    'slot_id' => $slotId,
                    'slot_name' => $slotName
                ];
            }

            throw new Exception("保存失敗");
        } catch (PDOException $e) {
            error_log("❌ 保存用戶代碼失敗: " . $e->getMessage());
            return [
                'success' => false,
                'error' => '資料庫保存失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 載入用戶代碼
     */
    public function loadUserCode($userName, $slotId = null)
    {
        try {
            if ($slotId === null) {
                // 載入最新代碼
                $sql = "SELECT * FROM user_code_saves WHERE user_name = ? AND is_latest = TRUE ORDER BY updated_at DESC LIMIT 1";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$userName]);
            } else {
                // 載入指定槽位代碼
                $sql = "SELECT * FROM user_code_saves WHERE user_name = ? AND slot_id = ? ORDER BY updated_at DESC LIMIT 1";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$userName, $slotId]);
            }

            $result = $stmt->fetch();

            if ($result) {
                // 記錄活動日誌
                $this->logUserActivity($userName, 'load', $result['slot_id'], $result['slot_name'], strlen($result['code_content']), $result['room_id']);

                error_log("✅ 用戶 {$userName} 載入槽位 {$result['slot_id']}: {$result['slot_name']}");
                return [
                    'success' => true,
                    'data' => $result
                ];
            }

            return [
                'success' => false,
                'error' => '找不到保存的代碼'
            ];
        } catch (PDOException $e) {
            error_log("❌ 載入用戶代碼失敗: " . $e->getMessage());
            return [
                'success' => false,
                'error' => '資料庫載入失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 獲取用戶所有槽位列表
     */
    public function getUserSlots($userName)
    {
        try {
            $sql = "SELECT slot_id, slot_name, LEFT(code_content, 100) as preview, updated_at, is_latest, is_auto_save 
                    FROM user_code_saves 
                    WHERE user_name = ? 
                    ORDER BY slot_id ASC";

            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$userName]);
            $results = $stmt->fetchAll();

            return [
                'success' => true,
                'data' => $results
            ];
        } catch (PDOException $e) {
            error_log("❌ 獲取用戶槽位失敗: " . $e->getMessage());
            return [
                'success' => false,
                'error' => '資料庫查詢失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 刪除用戶槽位
     */
    public function deleteUserSlot($userName, $slotId)
    {
        try {
            // 不允許刪除槽位 0（最新槽位）
            if ($slotId == 0) {
                return [
                    'success' => false,
                    'error' => '無法刪除最新槽位'
                ];
            }

            $sql = "DELETE FROM user_code_saves WHERE user_name = ? AND slot_id = ?";
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute([$userName, $slotId]);

            if ($result && $stmt->rowCount() > 0) {
                $this->logUserActivity($userName, 'delete', $slotId, null, 0);

                return [
                    'success' => true,
                    'message' => "槽位 {$slotId} 已刪除"
                ];
            }

            return [
                'success' => false,
                'error' => '找不到要刪除的槽位'
            ];
        } catch (PDOException $e) {
            error_log("❌ 刪除用戶槽位失敗: " . $e->getMessage());
            return [
                'success' => false,
                'error' => '資料庫刪除失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 記錄用戶活動
     */
    private function logUserActivity($userName, $actionType, $slotId = null, $slotName = null, $codeLength = 0, $roomId = 'general-room')
    {
        try {
            $sql = "INSERT INTO user_activity_log (user_name, action_type, slot_id, slot_name, code_length, room_id, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $userName,
                $actionType,
                $slotId,
                $slotName,
                $codeLength,
                $roomId,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (PDOException $e) {
            error_log("❌ 記錄用戶活動失敗: " . $e->getMessage());
        }
    }

    /**
     * 獲取資料庫狀態
     */
    public function getStatus()
    {
        try {
            $status = [
                'connected' => $this->connection !== null,
                'database' => $this->config['database'],
                'host' => $this->config['host'],
                'port' => $this->config['port']
            ];

            if ($this->connection) {
                // 檢查表格存在
                $sql = "SHOW TABLES LIKE 'user_code_saves'";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute();
                $status['tables_exist'] = $stmt->rowCount() > 0;

                // 統計數據
                if ($status['tables_exist']) {
                    $sql = "SELECT COUNT(*) as total_saves, COUNT(DISTINCT user_name) as total_users FROM user_code_saves";
                    $stmt = $this->connection->prepare($sql);
                    $stmt->execute();
                    $stats = $stmt->fetch();
                    $status['stats'] = $stats;
                }
            }

            return $status;
        } catch (Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 測試資料庫連接
     */
    public function testConnection()
    {
        try {
            $sql = "SELECT 1 as test";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();

            return [
                'success' => true,
                'message' => '資料庫連接測試成功',
                'test_result' => $result['test']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 獲取資料庫連接
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * 防止克隆
     */
    private function __clone() {}

    /**
     * 防止反序列化
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
