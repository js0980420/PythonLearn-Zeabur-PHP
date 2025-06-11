<?php

/**
 * 🗄️ PythonLearn 平台資料庫管理類
 * 
 * 開發順序和優先級：
 * 1. 🥇 XAMPP 內建 MySQL (本地開發優先) - localhost:3306, 無密碼
 * 2. 🥈 Zeabur MySQL (雲端部署) - 環境變數配置
 * 3. 🥉 其他 MySQL 環境
 * 
 * 核心原則：
 * - 統一資料庫結構，避免不匹配問題
 * - 環境自動檢測和切換
 * - 安全的數據操作方法
 * - 完整的錯誤處理和日誌
 * 
 * 📅 創建日期: 2025-06-10
 * 🎯 目標: 建立穩固的資料庫架構，避免檔案存儲問題
 */

require_once __DIR__ . '/public/config/database.php';

class DatabaseManager
{
    private $db;
    private static $instance = null;

    private function __construct()
    {
        $this->db = getDbConnection();
        $this->log("✅ 用戶管理表格創建/檢查完成");
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 📝 記錄用戶登入
     */
    public function recordUserLogin($userName, $userId, $roomId, $isTeacher = false)
    {
        try {
            $this->db->beginTransaction();

            // 更新或插入用戶基本資料
            $userSql = "INSERT INTO users (user_name, user_id, is_teacher, last_room, total_logins, last_login)
                        VALUES (:user_name, :user_id, :is_teacher, :room_id, 1, NOW())
                        ON DUPLICATE KEY UPDATE
                        last_login = NOW(),
                        total_logins = total_logins + 1,
                        last_room = :room_id,
                        user_id = :user_id";

            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([
                'user_name' => $userName,
                'user_id' => $userId,
                'is_teacher' => $isTeacher ? 1 : 0,
                'room_id' => $roomId
            ]);

            // 記錄登入日誌
            $logSql = "INSERT INTO user_login_logs (user_name, user_id, room_id, is_teacher, ip_address, user_agent)
                       VALUES (:user_name, :user_id, :room_id, :is_teacher, :ip_address, :user_agent)";

            $logStmt = $this->db->prepare($logSql);
            $logStmt->execute([
                'user_name' => $userName,
                'user_id' => $userId,
                'room_id' => $roomId,
                'is_teacher' => $isTeacher ? 1 : 0,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => '用戶登入記錄成功',
                'user_name' => $userName,
                'room_id' => $roomId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->log("❌ 記錄用戶登入失敗: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '登入記錄失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 📊 獲取最近用戶列表
     */
    public function getRecentUsers($limit = 10)
    {
        try {
            $sql = "SELECT user_name, is_teacher, last_login, total_logins, last_room
                    FROM users 
                    ORDER BY last_login DESC 
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'success' => true,
                'users' => $stmt->fetchAll(),
                'count' => $stmt->rowCount()
            ];
        } catch (Exception $e) {
            $this->log("❌ 獲取最近用戶失敗: " . $e->getMessage());
            return [
                'success' => false,
                'users' => [],
                'count' => 0,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 💾 保存用戶代碼
     */
    public function saveUserCode($userName, $roomId, $codeContent, $saveType = 'auto', $slotName = null)
    {
        try {
            $this->db->beginTransaction();

            // 如果是 latest 類型，先清除之前的 latest 標記
            if ($saveType === 'latest') {
                $clearSql = "UPDATE user_code_history 
                            SET is_latest = 0 
                            WHERE user_name = :user_name AND room_id = :room_id";
                $clearStmt = $this->db->prepare($clearSql);
                $clearStmt->execute(['user_name' => $userName, 'room_id' => $roomId]);
            }

            // 獲取版本號
            $versionSql = "SELECT COALESCE(MAX(version_number), 0) + 1 as next_version
                          FROM user_code_history 
                          WHERE user_name = :user_name AND room_id = :room_id";
            $versionStmt = $this->db->prepare($versionSql);
            $versionStmt->execute(['user_name' => $userName, 'room_id' => $roomId]);
            $nextVersion = $versionStmt->fetchColumn();

            // 插入代碼記錄
            $sql = "INSERT INTO user_code_history 
                    (user_name, room_id, code_content, code_length, save_type, slot_name, version_number, is_latest)
                    VALUES (:user_name, :room_id, :code_content, :code_length, :save_type, :slot_name, :version_number, :is_latest)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_name' => $userName,
                'room_id' => $roomId,
                'code_content' => $codeContent,
                'code_length' => strlen($codeContent),
                'save_type' => $saveType,
                'slot_name' => $slotName,
                'version_number' => $nextVersion,
                'is_latest' => ($saveType === 'latest') ? 1 : 0
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => '代碼保存成功',
                'version' => $nextVersion,
                'save_type' => $saveType
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->log("❌ 保存用戶代碼失敗: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '代碼保存失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 📖 載入用戶代碼
     */
    public function loadUserCode($userName, $roomId, $type = 'latest', $identifier = null)
    {
        try {
            switch ($type) {
                case 'latest':
                    $sql = "SELECT * FROM user_code_history 
                           WHERE user_name = :user_name AND room_id = :room_id AND is_latest = 1
                           ORDER BY created_at DESC LIMIT 1";
                    $params = ['user_name' => $userName, 'room_id' => $roomId];
                    break;

                case 'version':
                    $sql = "SELECT * FROM user_code_history 
                           WHERE user_name = :user_name AND room_id = :room_id AND version_number = :version
                           LIMIT 1";
                    $params = ['user_name' => $userName, 'room_id' => $roomId, 'version' => $identifier];
                    break;

                default:
                    $sql = "SELECT * FROM user_code_history 
                           WHERE user_name = :user_name AND room_id = :room_id
                           ORDER BY created_at DESC LIMIT 1";
                    $params = ['user_name' => $userName, 'room_id' => $roomId];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $code = $stmt->fetch();

            if ($code) {
                return [
                    'success' => true,
                    'code' => $code,
                    'found' => true
                ];
            } else {
                return [
                    'success' => true,
                    'code' => null,
                    'found' => false,
                    'message' => '未找到代碼記錄'
                ];
            }
        } catch (Exception $e) {
            $this->log("❌ 載入用戶代碼失敗: " . $e->getMessage());
            return [
                'success' => false,
                'code' => null,
                'found' => false,
                'message' => '載入代碼失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🔍 檢查資料庫狀態
     */
    public function checkDatabaseStatus()
    {
        try {
            $status = [
                'connection' => true,
                'tables' => [],
                'stats' => []
            ];

            // 檢查表格是否存在
            $tables = ['users', 'user_login_logs', 'user_code_history', 'chat_messages'];

            foreach ($tables as $table) {
                $sql = "SHOW TABLES LIKE :table";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['table' => $table]);
                $status['tables'][$table] = $stmt->rowCount() > 0;
            }

            // 獲取基本統計
            $sql = "SELECT COUNT(*) as count FROM users";
            $stmt = $this->db->query($sql);
            $status['stats']['total_users'] = $stmt->fetchColumn();

            return ['success' => true, 'status' => $status];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => ['connection' => false]
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

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}

// 全域輔助函數
function getDbManager()
{
    return DatabaseManager::getInstance();
}
