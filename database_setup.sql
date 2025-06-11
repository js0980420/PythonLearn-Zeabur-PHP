-- 🗄️ PythonLearn 平台 MySQL 資料庫初始化
-- 支援 XAMPP 和 Zeabur MySQL 環境
-- 創建時間: 2024年

-- 創建資料庫（如果不存在）
CREATE DATABASE IF NOT EXISTS `pythonlearn` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `pythonlearn`;

-- ============================================
-- 📊 用戶基本資料表
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_name` VARCHAR(100) NOT NULL COMMENT '用戶名稱',
    `user_id` VARCHAR(50) NOT NULL COMMENT '用戶ID（會話級別）',
    `is_teacher` BOOLEAN DEFAULT FALSE COMMENT '是否為教師',
    `first_login` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '首次登入時間',
    `last_login` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最後登入時間',
    `total_logins` INT DEFAULT 1 COMMENT '總登入次數',
    `preferred_room` VARCHAR(100) DEFAULT 'general-room' COMMENT '偏好房間',
    `last_room` VARCHAR(100) DEFAULT 'general-room' COMMENT '最後使用房間',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user_name` (`user_name`),
    INDEX `idx_last_login` (`last_login`),
    INDEX `idx_is_teacher` (`is_teacher`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='用戶基本資料表';

-- ============================================
-- 📝 用戶登入記錄表
-- ============================================
CREATE TABLE IF NOT EXISTS `user_login_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_name` VARCHAR(100) NOT NULL COMMENT '用戶名稱',
    `user_id` VARCHAR(50) NOT NULL COMMENT '用戶ID',
    `room_id` VARCHAR(100) NOT NULL COMMENT '房間ID',
    `is_teacher` BOOLEAN DEFAULT FALSE COMMENT '是否為教師',
    `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '登入時間',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
    `user_agent` TEXT DEFAULT NULL COMMENT '瀏覽器資訊',
    `session_duration` INT DEFAULT 0 COMMENT '會話持續時間（秒）',
    `logout_time` TIMESTAMP NULL DEFAULT NULL COMMENT '登出時間',
    
    INDEX `idx_user_name` (`user_name`),
    INDEX `idx_room_id` (`room_id`),
    INDEX `idx_login_time` (`login_time`),
    INDEX `idx_is_teacher` (`is_teacher`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='用戶登入記錄表';

-- ============================================
-- 💾 用戶代碼歷史表
-- ============================================
CREATE TABLE IF NOT EXISTS `user_code_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_name` VARCHAR(100) NOT NULL COMMENT '用戶名稱',
    `room_id` VARCHAR(100) NOT NULL COMMENT '房間ID',
    `code_content` LONGTEXT NOT NULL COMMENT '代碼內容',
    `code_length` INT DEFAULT 0 COMMENT '代碼長度',
    `save_type` ENUM('manual', 'auto', 'latest', 'slot') DEFAULT 'auto' COMMENT '保存類型',
    `slot_name` VARCHAR(50) DEFAULT NULL COMMENT '槽位名稱（如果是槽位保存）',
    `version_number` INT DEFAULT 1 COMMENT '版本號',
    `is_latest` BOOLEAN DEFAULT FALSE COMMENT '是否為最新版本',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '保存時間',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user_name` (`user_name`),
    INDEX `idx_room_id` (`room_id`),
    INDEX `idx_save_type` (`save_type`),
    INDEX `idx_is_latest` (`is_latest`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_user_room` (`user_name`, `room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='用戶代碼歷史表';

-- ============================================
-- 💬 聊天訊息記錄表
-- ============================================
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` VARCHAR(100) NOT NULL COMMENT '房間ID',
    `user_name` VARCHAR(100) NOT NULL COMMENT '發送者名稱',
    `user_id` VARCHAR(50) NOT NULL COMMENT '發送者ID',
    `message_content` TEXT NOT NULL COMMENT '訊息內容',
    `message_type` ENUM('chat', 'system', 'ai_share') DEFAULT 'chat' COMMENT '訊息類型',
    `is_teacher` BOOLEAN DEFAULT FALSE COMMENT '是否為教師發送',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '發送時間',
    
    INDEX `idx_room_id` (`room_id`),
    INDEX `idx_user_name` (`user_name`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_message_type` (`message_type`),
    INDEX `idx_room_time` (`room_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='聊天訊息記錄表';

-- ============================================
-- 📢 廣播訊息記錄表
-- ============================================
CREATE TABLE IF NOT EXISTS `broadcast_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `teacher_name` VARCHAR(100) NOT NULL COMMENT '教師名稱',
    `target_room` VARCHAR(100) DEFAULT 'all' COMMENT '目標房間（all表示所有房間）',
    `message_content` TEXT NOT NULL COMMENT '廣播內容',
    `broadcast_type` ENUM('info', 'warning', 'success', 'error') DEFAULT 'info' COMMENT '廣播類型',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '廣播時間',
    
    INDEX `idx_teacher_name` (`teacher_name`),
    INDEX `idx_target_room` (`target_room`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_broadcast_type` (`broadcast_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='廣播訊息記錄表';

-- ============================================
-- 📊 用戶活動統計表
-- ============================================
CREATE TABLE IF NOT EXISTS `user_activity_stats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_name` VARCHAR(100) NOT NULL COMMENT '用戶名稱',
    `date` DATE NOT NULL COMMENT '統計日期',
    `total_logins` INT DEFAULT 0 COMMENT '當日登入次數',
    `total_code_saves` INT DEFAULT 0 COMMENT '當日代碼保存次數',
    `total_chat_messages` INT DEFAULT 0 COMMENT '當日聊天訊息數',
    `total_time_spent` INT DEFAULT 0 COMMENT '當日使用時間（秒）',
    `rooms_visited` TEXT DEFAULT NULL COMMENT '當日訪問的房間列表（JSON格式）',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `unique_user_date` (`user_name`, `date`),
    INDEX `idx_user_name` (`user_name`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='用戶活動統計表';

-- ============================================
-- 🏠 房間使用記錄表
-- ============================================
CREATE TABLE IF NOT EXISTS `room_usage_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` VARCHAR(100) NOT NULL COMMENT '房間ID',
    `user_name` VARCHAR(100) NOT NULL COMMENT '用戶名稱',
    `action_type` ENUM('join', 'leave', 'code_edit', 'chat', 'save') DEFAULT 'join' COMMENT '動作類型',
    `action_details` TEXT DEFAULT NULL COMMENT '動作詳情（JSON格式）',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '動作時間',
    
    INDEX `idx_room_id` (`room_id`),
    INDEX `idx_user_name` (`user_name`),
    INDEX `idx_action_type` (`action_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_room_time` (`room_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='房間使用記錄表';

-- ============================================
-- 🔧 系統配置表
-- ============================================
CREATE TABLE IF NOT EXISTS `system_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(100) NOT NULL UNIQUE COMMENT '配置鍵',
    `config_value` TEXT NOT NULL COMMENT '配置值',
    `config_type` ENUM('string', 'int', 'boolean', 'json') DEFAULT 'string' COMMENT '配置類型',
    `description` TEXT DEFAULT NULL COMMENT '配置描述',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='系統配置表';

-- ============================================
-- 📝 插入初始配置數據
-- ============================================
INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`, `description`) VALUES
('platform_name', 'Python多人協作教學平台', 'string', '平台名稱'),
('auto_save_interval', '30', 'int', '自動保存間隔（秒）'),
('max_code_history', '100', 'int', '最大代碼歷史記錄數'),
('chat_message_limit', '1000', 'int', '聊天訊息保留上限'),
('default_room', 'general-room', 'string', '預設房間名稱'),
('enable_auto_load', 'true', 'boolean', '是否啟用自動載入最新代碼'),
('max_recent_users', '10', 'int', '最近用戶名稱保留數量')
ON DUPLICATE KEY UPDATE 
    `updated_at` = CURRENT_TIMESTAMP;

-- ============================================
-- 🎯 創建視圖（方便查詢）
-- ============================================

-- 最近活躍用戶視圖
CREATE OR REPLACE VIEW `view_recent_active_users` AS
SELECT 
    u.user_name,
    u.is_teacher,
    u.last_login,
    u.total_logins,
    u.last_room,
    TIMESTAMPDIFF(MINUTE, u.last_login, NOW()) as minutes_since_last_login
FROM users u
WHERE u.last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY u.last_login DESC
LIMIT 50;

-- 用戶最新代碼視圖
CREATE OR REPLACE VIEW `view_user_latest_code` AS
SELECT 
    uch.user_name,
    uch.room_id,
    uch.code_content,
    uch.code_length,
    uch.created_at as last_save_time
FROM user_code_history uch
INNER JOIN (
    SELECT user_name, room_id, MAX(created_at) as max_time
    FROM user_code_history
    WHERE is_latest = TRUE
    GROUP BY user_name, room_id
) latest ON uch.user_name = latest.user_name 
    AND uch.room_id = latest.room_id 
    AND uch.created_at = latest.max_time;

-- 房間活動統計視圖
CREATE OR REPLACE VIEW `view_room_activity_stats` AS
SELECT 
    room_id,
    COUNT(DISTINCT user_name) as unique_users,
    COUNT(*) as total_activities,
    MAX(created_at) as last_activity_time,
    MIN(created_at) as first_activity_time
FROM room_usage_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY room_id
ORDER BY total_activities DESC;

-- ============================================
-- 🧹 清理舊數據功能（簡化版本，避免存儲過程問題）
-- ============================================
-- 如需清理，請手動執行以下 SQL 語句：
    
    -- 清理90天前的登入記錄
-- DELETE FROM user_login_logs WHERE login_time < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- 清理30天前的聊天記錄
-- DELETE FROM chat_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- 清理90天前的房間使用記錄
-- DELETE FROM room_usage_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- ============================================
-- ✅ 初始化完成
-- ============================================
SELECT '🎉 PythonLearn 平台資料庫初始化完成！' as message; 