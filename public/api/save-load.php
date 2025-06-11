<?php

/**
 * 🗄️ 用戶代碼保存載入 API
 * 支援 MySQL 資料庫永久儲存
 */

// 🔧 確保純淨 JSON 響應
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

// 資料庫管理器
class SaveLoadDatabase
{
    private static $instance = null;
    private $connection = null;

    private function __construct()
    {
        $this->connect();
        $this->createTables();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect()
    {
        try {
            $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";charset=utf8mb4";

            $this->connection = new PDO($dsn, MYSQL_USER, MYSQL_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // 創建資料庫
            $this->connection->exec("CREATE DATABASE IF NOT EXISTS `" . MYSQL_DATABASE . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->connection->exec("USE `" . MYSQL_DATABASE . "`");

            error_log("✅ SaveLoad 資料庫連接成功");
        } catch (PDOException $e) {
            error_log("❌ SaveLoad 資料庫連接失敗: " . $e->getMessage());
            throw new Exception("資料庫連接失敗");
        }
    }

    private function createTables()
    {
        try {
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
                    INDEX idx_latest (user_name, is_latest)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";

            $this->connection->exec($sql);
        } catch (PDOException $e) {
            error_log("❌ 創建 SaveLoad 表格失敗: " . $e->getMessage());
        }
    }

    public function saveCode($userName, $slotId, $slotName, $codeContent, $roomId = 'general-room')
    {
        try {
            // 如果是槽位 0，清除其他最新標記
            if ($slotId == 0) {
                $sql = "UPDATE user_code_saves SET is_latest = FALSE WHERE user_name = ? AND is_latest = TRUE";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$userName]);
            }

            $sql = "
                INSERT INTO user_code_saves (user_name, slot_id, slot_name, code_content, is_latest, is_auto_save, room_id, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    slot_name = VALUES(slot_name),
                    code_content = VALUES(code_content),
                    is_latest = VALUES(is_latest),
                    is_auto_save = VALUES(is_auto_save),
                    updated_at = NOW()
            ";

            $stmt = $this->connection->prepare($sql);
            $isLatest = ($slotId == 0);
            $isAutoSave = ($slotId == 0);

            $result = $stmt->execute([
                $userName,
                $slotId,
                $slotName,
                $codeContent,
                $isLatest,
                $isAutoSave,
                $roomId
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "代碼已保存到 {$slotName}",
                    'slot_id' => $slotId,
                    'slot_name' => $slotName
                ];
            }

            throw new Exception("保存失敗");
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => '保存失敗: ' . $e->getMessage()
            ];
        }
    }

    public function loadCode($userName, $slotId = null)
    {
        try {
            if ($slotId === null) {
                // 載入最新代碼
                $sql = "SELECT * FROM user_code_saves WHERE user_name = ? AND is_latest = TRUE ORDER BY updated_at DESC LIMIT 1";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$userName]);
            } else {
                // 載入指定槽位
                $sql = "SELECT * FROM user_code_saves WHERE user_name = ? AND slot_id = ? ORDER BY updated_at DESC LIMIT 1";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$userName, $slotId]);
            }

            $result = $stmt->fetch();

            if ($result) {
                return [
                    'success' => true,
                    'data' => $result
                ];
            }

            return [
                'success' => false,
                'error' => '找不到保存的代碼'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => '載入失敗: ' . $e->getMessage()
            ];
        }
    }

    public function getUserSlots($userName)
    {
        try {
            $sql = "SELECT slot_id, slot_name, 
                           LEFT(code_content, 100) as preview, 
                           CHAR_LENGTH(code_content) as code_length,
                           updated_at, is_latest, is_auto_save 
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
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => '查詢失敗: ' . $e->getMessage()
            ];
        }
    }

    public function deleteSlot($userName, $slotId)
    {
        try {
            if ($slotId == 0) {
                return [
                    'success' => false,
                    'error' => '無法刪除自動保存槽位'
                ];
            }

            $sql = "DELETE FROM user_code_saves WHERE user_name = ? AND slot_id = ?";
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute([$userName, $slotId]);

            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => "槽位 {$slotId} 已刪除"
                ];
            }

            return [
                'success' => false,
                'error' => '找不到要刪除的槽位'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => '刪除失敗: ' . $e->getMessage()
            ];
        }
    }
}

// 處理請求
try {
    $db = SaveLoadDatabase::getInstance();

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $userName = $_GET['user_name'] ?? $_POST['user_name'] ?? '';
    $roomId = $_GET['room_id'] ?? $_POST['room_id'] ?? 'general-room';

    // 狀態檢查不需要用戶名稱
    if (empty($userName) && $action !== 'status') {
        throw new Exception('缺少用戶名稱');
    }

    switch ($action) {
        case 'save':
            $slotId = (int)($_POST['slot_id'] ?? 0);
            $slotName = $_POST['slot_name'] ?? '最新';
            $codeContent = $_POST['code_content'] ?? '';

            if (empty($codeContent)) {
                throw new Exception('代碼內容不能為空');
            }

            $result = $db->saveCode($userName, $slotId, $slotName, $codeContent, $roomId);
            break;

        case 'load':
            $slotId = isset($_GET['slot_id']) ? (int)$_GET['slot_id'] : null;
            $result = $db->loadCode($userName, $slotId);
            break;

        case 'list':
            $result = $db->getUserSlots($userName);
            break;

        case 'delete':
            $slotId = (int)($_POST['slot_id'] ?? 0);
            $result = $db->deleteSlot($userName, $slotId);
            break;

        case 'status':
            $result = [
                'success' => true,
                'message' => 'SaveLoad API 運行正常',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;

        default:
            throw new Exception('無效的操作');
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("SaveLoad API 錯誤: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
