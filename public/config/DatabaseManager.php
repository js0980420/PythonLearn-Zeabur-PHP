<?php

/**
 * 🗄️ PythonLearn 平台資料庫管理類
 * 
 * 開發順序和優先級：
 * 1. 🥇 XAMPP 內建 MySQL (本地開發優先)
 * 2. 🥈 Zeabur MySQL (雲端部署)
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

require_once __DIR__ . '/database.php';

class DatabaseManager
{
    private $db;
    private static $instance = null;

    private function __construct()
    {
        $this->db = getDbConnection();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ============================================
    // 👥 用戶管理方法
    // ============================================

    /**
     * 📝 記錄用戶登入
     * @param string $userName 用戶名稱
     * @param string $userId 用戶ID
     * @param string $roomId 房間ID
     * @param bool $isTeacher 是否為教師
     * @return array 操作結果
     */
    public function recordUserLogin($userName, $userId, $roomId, $isTeacher = false)
    {
        try {
            $this->db->beginTransaction();

            // 1. 更新或插入用戶基本資料
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

            // 2. 記錄登入日誌
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

            // 3. 記錄房間使用
            $this->recordRoomAction($roomId, $userName, 'join');

            $this->db->commit();

            return [
                'success' => true,
                'message' => '用戶登入記錄成功',
                'user_name' => $userName,
                'room_id' => $roomId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("記錄用戶登入失敗: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '登入記錄失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 📊 獲取最近用戶列表
     * @param int $limit 限制數量
     * @return array 用戶列表
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
            error_log("獲取最近用戶失敗: " . $e->getMessage());
            return [
                'success' => false,
                'users' => [],
                'count' => 0,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 🔍 獲取房間內活躍用戶
     * @param string $roomId 房間ID
     * @param int $minutesAgo 多少分鐘內活躍
     * @return array 活躍用戶列表
     */
    public function getRoomActiveUsers($roomId, $minutesAgo = 5)
    {
        try {
            $sql = "SELECT DISTINCT u.user_name, u.is_teacher, u.last_login
                    FROM users u
                    INNER JOIN user_login_logs l ON u.user_name = l.user_name
                    WHERE l.room_id = :room_id 
                    AND l.login_time > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
                    AND l.logout_time IS NULL
                    ORDER BY u.last_login DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'room_id' => $roomId,
                'minutes' => $minutesAgo
            ]);

            return [
                'success' => true,
                'users' => $stmt->fetchAll(),
                'room_id' => $roomId,
                'count' => $stmt->rowCount()
            ];
        } catch (Exception $e) {
            error_log("獲取房間活躍用戶失敗: " . $e->getMessage());
            return [
                'success' => false,
                'users' => [],
                'count' => 0,
                'message' => $e->getMessage()
            ];
        }
    }

    // ============================================
    // 💾 代碼管理方法
    // ============================================

    /**
     * 💾 保存用戶代碼
     * @param string $userName 用戶名稱
     * @param string $roomId 房間ID
     * @param string $codeContent 代碼內容
     * @param string $saveType 保存類型 (auto/manual/latest/slot)
     * @param string $slotName 槽位名稱
     * @return array 操作結果
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

            // 記錄房間活動
            $this->recordRoomAction($roomId, $userName, 'save', [
                'save_type' => $saveType,
                'code_length' => strlen($codeContent),
                'version' => $nextVersion
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
            error_log("保存用戶代碼失敗: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '代碼保存失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 📖 載入用戶代碼
     * @param string $userName 用戶名稱
     * @param string $roomId 房間ID
     * @param string $type 載入類型 (latest/version/slot)
     * @param mixed $identifier 識別符（版本號或槽位名稱）
     * @return array 代碼內容
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

                case 'slot':
                    $sql = "SELECT * FROM user_code_history 
                           WHERE user_name = :user_name AND room_id = :room_id AND slot_name = :slot_name
                           ORDER BY created_at DESC LIMIT 1";
                    $params = ['user_name' => $userName, 'room_id' => $roomId, 'slot_name' => $identifier];
                    break;

                default:
                    // 如果沒有指定類型，載入最新的任何代碼
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
            error_log("載入用戶代碼失敗: " . $e->getMessage());
            return [
                'success' => false,
                'code' => null,
                'found' => false,
                'message' => '載入代碼失敗: ' . $e->getMessage()
            ];
        }
    }

    // ============================================
    // 💬 聊天管理方法
    // ============================================

    /**
     * 💬 保存聊天消息
     * @param string $roomId 房間ID
     * @param string $userName 用戶名稱
     * @param string $userId 用戶ID
     * @param string $messageContent 消息內容
     * @param string $messageType 消息類型
     * @param bool $isTeacher 是否為教師
     * @return array 操作結果
     */
    public function saveChatMessage($roomId, $userName, $userId, $messageContent, $messageType = 'chat', $isTeacher = false)
    {
        try {
            $sql = "INSERT INTO chat_messages (room_id, user_name, user_id, message_content, message_type, is_teacher)
                    VALUES (:room_id, :user_name, :user_id, :message_content, :message_type, :is_teacher)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'room_id' => $roomId,
                'user_name' => $userName,
                'user_id' => $userId,
                'message_content' => $messageContent,
                'message_type' => $messageType,
                'is_teacher' => $isTeacher ? 1 : 0
            ]);

            // 記錄房間活動
            $this->recordRoomAction($roomId, $userName, 'chat', [
                'message_type' => $messageType,
                'message_length' => strlen($messageContent)
            ]);

            return [
                'success' => true,
                'message_id' => $this->db->lastInsertId(),
                'message' => '消息保存成功'
            ];
        } catch (Exception $e) {
            error_log("保存聊天消息失敗: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '消息保存失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 📜 獲取聊天記錄
     * @param string $roomId 房間ID
     * @param int $limit 限制數量
     * @param int $offset 偏移量
     * @return array 聊天記錄
     */
    public function getChatMessages($roomId, $limit = 50, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM chat_messages 
                    WHERE room_id = :room_id 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':room_id', $roomId);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            $messages = array_reverse($stmt->fetchAll()); // 反轉為時間正序

            return [
                'success' => true,
                'messages' => $messages,
                'count' => count($messages)
            ];
        } catch (Exception $e) {
            error_log("獲取聊天記錄失敗: " . $e->getMessage());
            return [
                'success' => false,
                'messages' => [],
                'count' => 0,
                'message' => $e->getMessage()
            ];
        }
    }

    // ============================================
    // 🏠 房間管理方法
    // ============================================

    /**
     * 📝 記錄房間活動
     * @param string $roomId 房間ID
     * @param string $userName 用戶名稱
     * @param string $actionType 動作類型
     * @param array $actionDetails 動作詳情
     * @return bool 是否成功
     */
    private function recordRoomAction($roomId, $userName, $actionType, $actionDetails = null)
    {
        try {
            $sql = "INSERT INTO room_usage_logs (room_id, user_name, action_type, action_details)
                    VALUES (:room_id, :user_name, :action_type, :action_details)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'room_id' => $roomId,
                'user_name' => $userName,
                'action_type' => $actionType,
                'action_details' => $actionDetails ? json_encode($actionDetails, JSON_UNESCAPED_UNICODE) : null
            ]);

            return true;
        } catch (Exception $e) {
            error_log("記錄房間活動失敗: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 📊 獲取房間統計
     * @param string $roomId 房間ID
     * @return array 房間統計信息
     */
    public function getRoomStats($roomId)
    {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT user_name) as total_users,
                        COUNT(DISTINCT CASE WHEN action_type = 'join' THEN user_name END) as joined_users,
                        COUNT(CASE WHEN action_type = 'save' THEN 1 END) as total_saves,
                        COUNT(CASE WHEN action_type = 'chat' THEN 1 END) as total_messages,
                        MAX(created_at) as last_activity
                    FROM room_usage_logs 
                    WHERE room_id = :room_id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['room_id' => $roomId]);
            $stats = $stmt->fetch();

            return [
                'success' => true,
                'stats' => $stats,
                'room_id' => $roomId
            ];
        } catch (Exception $e) {
            error_log("獲取房間統計失敗: " . $e->getMessage());
            return [
                'success' => false,
                'stats' => null,
                'message' => $e->getMessage()
            ];
        }
    }

    // ============================================
    // 🧹 維護和清理方法
    // ============================================

    /**
     * 🧹 清理過期數據
     * @param array $options 清理選項
     * @return array 清理結果
     */
    public function cleanupExpiredData($options = [])
    {
        $defaults = [
            'login_logs_days' => 30,    // 登入日誌保留天數
            'chat_messages_days' => 7,  // 聊天記錄保留天數
            'room_logs_days' => 14,     // 房間日誌保留天數
            'code_history_count' => 100 // 每用戶保留的代碼版本數
        ];

        $options = array_merge($defaults, $options);
        $result = ['success' => true, 'cleaned' => []];

        try {
            $this->db->beginTransaction();

            // 清理過期登入日誌
            $sql = "DELETE FROM user_login_logs 
                    WHERE login_time < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['days' => $options['login_logs_days']]);
            $result['cleaned']['login_logs'] = $stmt->rowCount();

            // 清理過期聊天記錄
            $sql = "DELETE FROM chat_messages 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['days' => $options['chat_messages_days']]);
            $result['cleaned']['chat_messages'] = $stmt->rowCount();

            // 清理過期房間日誌
            $sql = "DELETE FROM room_usage_logs 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['days' => $options['room_logs_days']]);
            $result['cleaned']['room_logs'] = $stmt->rowCount();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            error_log("清理過期數據失敗: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * 🔍 檢查資料庫狀態
     * @return array 狀態信息
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
            $tables = ['users', 'user_login_logs', 'user_code_history', 'chat_messages', 'room_usage_logs', 'system_config'];

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

            $sql = "SELECT COUNT(*) as count FROM chat_messages";
            $stmt = $this->db->query($sql);
            $status['stats']['total_messages'] = $stmt->fetchColumn();

            return ['success' => true, 'status' => $status];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => ['connection' => false]
            ];
        }
    }
}

// 📊 全域輔助函數

/**
 * 🔗 獲取資料庫管理器實例
 */
function getDbManager()
{
    return DatabaseManager::getInstance();
}

/**
 * 🧹 執行資料庫清理任務
 */
function performDatabaseCleanup()
{
    return getDbManager()->cleanupExpiredData();
}

/**
 * 🔍 快速檢查資料庫狀態
 */
function quickDbCheck()
{
    return getDbManager()->checkDatabaseStatus();
}
