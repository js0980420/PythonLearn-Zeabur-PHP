<?php

/**
 * 🧑‍💻 PythonLearn 用戶管理器
 * 負責用戶登入記錄、用戶名稱保存、代碼追蹤等功能
 * 支援 XAMPP 和 Zeabur MySQL
 */

require_once __DIR__ . '/database.php';

class UserManager
{
    private $db;
    private static $instance = null;

    private function __construct()
    {
        $this->db = DatabaseManager::getInstance();
        $this->createUserTables();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 創建用戶相關表格
     */
    private function createUserTables()
    {
        try {
            $connection = $this->db->getConnection();

            // 用戶基本資料表
            $sql = "
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_name VARCHAR(255) NOT NULL UNIQUE,
                    display_name VARCHAR(255) DEFAULT NULL,
                    email VARCHAR(255) DEFAULT NULL,
                    is_teacher BOOLEAN NOT NULL DEFAULT FALSE,
                    last_login_at TIMESTAMP NULL,
                    last_login_ip VARCHAR(45) DEFAULT NULL,
                    last_room_id VARCHAR(255) DEFAULT 'general-room',
                    preferences JSON DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_name (user_name),
                    INDEX idx_last_login (last_login_at),
                    INDEX idx_is_teacher (is_teacher)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $connection->exec($sql);

            // 用戶登入歷史表
            $sql = "
                CREATE TABLE IF NOT EXISTS user_login_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_name VARCHAR(255) NOT NULL,
                    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45) DEFAULT NULL,
                    user_agent TEXT DEFAULT NULL,
                    room_id VARCHAR(255) DEFAULT 'general-room',
                    session_duration INT DEFAULT NULL,
                    logout_time TIMESTAMP NULL,
                    INDEX idx_user_login (user_name, login_time),
                    INDEX idx_login_time (login_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $connection->exec($sql);

            // 用戶代碼版本歷史表
            $sql = "
                CREATE TABLE IF NOT EXISTS user_code_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_name VARCHAR(255) NOT NULL,
                    code_content LONGTEXT NOT NULL,
                    code_hash VARCHAR(64) NOT NULL,
                    save_type ENUM('auto', 'manual', 'latest') NOT NULL DEFAULT 'manual',
                    slot_id INT DEFAULT NULL,
                    slot_name VARCHAR(255) DEFAULT NULL,
                    room_id VARCHAR(255) DEFAULT 'general-room',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_history (user_name, created_at),
                    INDEX idx_code_hash (code_hash),
                    INDEX idx_save_type (save_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $connection->exec($sql);

            // 用戶偏好設定表
            $sql = "
                CREATE TABLE IF NOT EXISTS user_preferences (
                    user_name VARCHAR(255) PRIMARY KEY,
                    auto_save_enabled BOOLEAN DEFAULT TRUE,
                    auto_save_interval INT DEFAULT 30,
                    theme VARCHAR(50) DEFAULT 'light',
                    editor_font_size INT DEFAULT 14,
                    editor_theme VARCHAR(50) DEFAULT 'default',
                    last_used_names JSON DEFAULT NULL,
                    favorite_slots JSON DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_updated (updated_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $connection->exec($sql);

            error_log("✅ 用戶管理表格創建/檢查完成");
        } catch (Exception $e) {
            error_log("❌ 創建用戶表格失敗: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 用戶登入處理
     */
    public function userLogin($userName, $isTeacher = false, $roomId = 'general-room')
    {
        try {
            $connection = $this->db->getConnection();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            // 更新用戶基本資料
            $sql = "
                INSERT INTO users (user_name, is_teacher, last_login_at, last_login_ip, last_room_id)
                VALUES (?, ?, NOW(), ?, ?)
                ON DUPLICATE KEY UPDATE 
                    is_teacher = VALUES(is_teacher),
                    last_login_at = NOW(),
                    last_login_ip = VALUES(last_login_ip),
                    last_room_id = VALUES(last_room_id),
                    updated_at = NOW()
            ";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName, $isTeacher, $ipAddress, $roomId]);

            // 記錄登入歷史
            $sql = "
                INSERT INTO user_login_history (user_name, ip_address, user_agent, room_id)
                VALUES (?, ?, ?, ?)
            ";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName, $ipAddress, $userAgent, $roomId]);

            // 初始化用戶偏好設定（如果不存在）
            $this->initializeUserPreferences($userName);

            // 記錄用戶名稱到最近使用列表
            $this->addToRecentUserNames($userName);

            error_log("✅ 用戶 {$userName} 已登入 (教師: " . ($isTeacher ? '是' : '否') . ")");

            return [
                'success' => true,
                'user_name' => $userName,
                'is_teacher' => $isTeacher,
                'room_id' => $roomId,
                'login_time' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("❌ 用戶登入失敗: " . $e->getMessage());
            return [
                'success' => false,
                'error' => '登入處理失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 保存用戶最新代碼
     */
    public function saveUserLatestCode($userName, $codeContent, $roomId = 'general-room', $saveType = 'latest')
    {
        try {
            $connection = $this->db->getConnection();
            $codeHash = hash('sha256', $codeContent);

            // 檢查是否已存在相同的代碼
            $sql = "SELECT id FROM user_code_history WHERE user_name = ? AND code_hash = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName, $codeHash]);

            if ($stmt->fetch()) {
                // 代碼內容相同，只更新時間戳
                $sql = "UPDATE user_code_history SET created_at = NOW() WHERE user_name = ? AND code_hash = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$userName, $codeHash]);
            } else {
                // 新的代碼內容，插入新記錄
                $sql = "
                    INSERT INTO user_code_history (user_name, code_content, code_hash, save_type, room_id)
                    VALUES (?, ?, ?, ?, ?)
                ";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$userName, $codeContent, $codeHash, $saveType, $roomId]);
            }

            // 同時更新到代碼保存表的槽位 0（最新代碼槽位）
            $result = $this->db->saveUserCode($userName, 0, '最新代碼', $codeContent, true, $roomId);

            error_log("✅ 用戶 {$userName} 最新代碼已保存 (長度: " . strlen($codeContent) . ")");

            return [
                'success' => true,
                'message' => '最新代碼已保存',
                'code_length' => strlen($codeContent),
                'save_time' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("❌ 保存最新代碼失敗: " . $e->getMessage());
            return [
                'success' => false,
                'error' => '保存最新代碼失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 獲取用戶最新代碼
     */
    public function getUserLatestCode($userName)
    {
        try {
            $connection = $this->db->getConnection();

            // 優先從代碼保存表獲取最新代碼
            $sql = "SELECT * FROM user_code_saves WHERE user_name = ? AND is_latest = TRUE ORDER BY updated_at DESC LIMIT 1";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName]);
            $result = $stmt->fetch();

            if ($result) {
                return [
                    'success' => true,
                    'code_content' => $result['code_content'],
                    'slot_name' => $result['slot_name'],
                    'updated_at' => $result['updated_at']
                ];
            }

            // 備用：從代碼歷史表獲取
            $sql = "SELECT * FROM user_code_history WHERE user_name = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName]);
            $result = $stmt->fetch();

            if ($result) {
                return [
                    'success' => true,
                    'code_content' => $result['code_content'],
                    'slot_name' => '歷史代碼',
                    'updated_at' => $result['created_at']
                ];
            }

            return [
                'success' => false,
                'message' => '未找到用戶代碼'
            ];
        } catch (Exception $e) {
            error_log("❌ 獲取用戶最新代碼失敗: " . $e->getMessage());
            return [
                'success' => false,
                'error' => '獲取最新代碼失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 獲取最近使用的用戶名稱列表
     */
    public function getRecentUserNames($limit = 10)
    {
        try {
            $connection = $this->db->getConnection();

            $sql = "
                SELECT user_name, last_login_at, is_teacher
                FROM users 
                WHERE last_login_at IS NOT NULL 
                ORDER BY last_login_at DESC 
                LIMIT ?
            ";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$limit]);
            $results = $stmt->fetchAll();

            return [
                'success' => true,
                'recent_users' => $results
            ];
        } catch (Exception $e) {
            error_log("❌ 獲取最近用戶名稱失敗: " . $e->getMessage());
            return [
                'success' => false,
                'error' => '獲取最近用戶失敗: ' . $e->getMessage(),
                'recent_users' => []
            ];
        }
    }

    /**
     * 初始化用戶偏好設定
     */
    private function initializeUserPreferences($userName)
    {
        try {
            $connection = $this->db->getConnection();

            $sql = "
                INSERT IGNORE INTO user_preferences (user_name, last_used_names)
                VALUES (?, JSON_ARRAY(?))
            ";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName, $userName]);
        } catch (Exception $e) {
            error_log("❌ 初始化用戶偏好失敗: " . $e->getMessage());
        }
    }

    /**
     * 添加到最近使用的用戶名稱列表
     */
    private function addToRecentUserNames($userName)
    {
        try {
            $connection = $this->db->getConnection();

            // 獲取當前的最近使用列表
            $sql = "SELECT last_used_names FROM user_preferences WHERE user_name = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName]);
            $result = $stmt->fetch();

            $recentNames = [];
            if ($result && $result['last_used_names']) {
                $recentNames = json_decode($result['last_used_names'], true) ?: [];
            }

            // 移除重複項目並添加到開頭
            $recentNames = array_filter($recentNames, function ($name) use ($userName) {
                return $name !== $userName;
            });
            array_unshift($recentNames, $userName);

            // 限制最多 10 個
            $recentNames = array_slice($recentNames, 0, 10);

            // 更新到資料庫
            $sql = "
                INSERT INTO user_preferences (user_name, last_used_names, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    last_used_names = VALUES(last_used_names),
                    updated_at = NOW()
            ";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName, json_encode($recentNames)]);
        } catch (Exception $e) {
            error_log("❌ 更新最近使用用戶名稱失敗: " . $e->getMessage());
        }
    }

    /**
     * 獲取用戶統計資訊
     */
    public function getUserStats($userName)
    {
        try {
            $connection = $this->db->getConnection();

            // 基本統計
            $stats = [];

            // 登入次數
            $sql = "SELECT COUNT(*) as login_count FROM user_login_history WHERE user_name = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName]);
            $stats['login_count'] = $stmt->fetch()['login_count'];

            // 代碼保存次數
            $sql = "SELECT COUNT(*) as save_count FROM user_code_saves WHERE user_name = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName]);
            $stats['save_count'] = $stmt->fetch()['save_count'];

            // 最後登入時間
            $sql = "SELECT last_login_at FROM users WHERE user_name = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userName]);
            $result = $stmt->fetch();
            $stats['last_login'] = $result['last_login_at'] ?? null;

            return [
                'success' => true,
                'stats' => $stats
            ];
        } catch (Exception $e) {
            error_log("❌ 獲取用戶統計失敗: " . $e->getMessage());
            return [
                'success' => false,
                'error' => '獲取統計失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 獲取資料庫連接（供外部使用）
     */
    public function getConnection()
    {
        return $this->db->getConnection();
    }
}
