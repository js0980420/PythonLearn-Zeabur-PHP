# MySQL 優化建議流程 (更新版)

本文件概述了針對 PythonLearn-Zeabur 平台 MySQL 資料庫的優化步驟，確保核心功能穩定運行並避免 WebSocket 連接失敗。

## 🎯 資料庫優先級策略

**優先順序：XAMPP內建MySQL > Zeabur MYSQL > SQLite > 系統MySQL(避免衝突) > localStorage**

### 理由分析：
- **XAMPP內建MySQL**：版本穩定、配置統一、與Apache完美整合
- **Zeabur MYSQL**：雲端生產環境標準配置
- **SQLite**：零配置、穩定降級方案
- **避免系統MySQL**：防止端口衝突、版本不一致問題
- **localStorage**：僅作最後備援

## 🚨 關鍵問題解決目標

### 必須解決的核心問題：
1. **WebSocket連接失敗** - 確保前後端通信穩定
2. **函數映射錯誤** - 統一前後端API接口
3. **代碼保存載入失敗** - 完善資料庫存取邏輯
4. **用戶加入房間失敗** - 優化房間管理機制
5. **前後端數據同步問題** - 確保資料一致性

## 1. 🔧 XAMPP MySQL 環境確認與優化

### 目標：建立穩定的 XAMPP MySQL 基礎環境
### 操作步驟：

#### 1.1 XAMPP MySQL 服務檢查
```bash
# 檢查 XAMPP MySQL 狀態
netstat -an | findstr 3306
# 確認 XAMPP Control Panel 中 MySQL 為綠燈狀態
```

#### 1.2 XAMPP MySQL 認證配置
- 訪問 phpMyAdmin (http://localhost/phpmyadmin)
- 重置 root 用戶密碼為空或設定已知密碼
- 確保 `root@localhost` 具有完整權限

#### 1.3 Database.php 優先級配置
```php
// 更新 classes/Database.php 中的偵測邏輯
private function detectXAMPPHost() {
    // 強制優先使用 XAMPP MySQL
    $xamppPaths = [
        'C:/xampp/mysql/bin/mysql.exe',  // Windows XAMPP
        'C:/XAMPP/mysql/bin/mysql.exe',  // 大寫版本
        '/Applications/XAMPP/xamppfiles/bin/mysql', // macOS
        '/opt/lampp/bin/mysql'  // Linux
    ];
    
    foreach ($xamppPaths as $path) {
        if (file_exists($path)) {
            return 'localhost'; // XAMPP 固定使用 localhost
        }
    }
    
    return 'localhost'; // 預設值
}
```

## 2. 📊 資料庫 Schema 標準化與建立

### 目標：建立符合規範的資料表結構，避免前後端映射錯誤

#### 2.1 核心資料表設計（MySQL優先，SQLite兼容）

```sql
-- 用戶表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    user_type ENUM('student', 'teacher', 'admin') DEFAULT 'student',
    email VARCHAR(100) NULL,
    password_hash VARCHAR(255) NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_user_type (user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 房間表
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(50) NOT NULL UNIQUE,  -- 房間代碼
    room_name VARCHAR(100) NOT NULL,
    room_password VARCHAR(255) NULL,
    current_code LONGTEXT,  -- 當前代碼內容
    teacher_id INT NULL,
    max_users INT DEFAULT 10,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_room_code (room_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 房間用戶關聯表
CREATE TABLE IF NOT EXISTS room_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_online BOOLEAN DEFAULT TRUE,
    cursor_position JSON NULL,  -- 游標位置
    UNIQUE KEY unique_room_user (room_id, user_id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_online (room_id, is_online),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 代碼歷史表
CREATE TABLE IF NOT EXISTS code_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    code LONGTEXT,  -- 代碼內容
    description VARCHAR(200) DEFAULT NULL,  -- 保存描述
    version_number INT NOT NULL,
    operation_type ENUM('save', 'auto_save', 'load', 'restore') DEFAULT 'save',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_version (room_id, version_number),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 聊天訊息表
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message_content TEXT NOT NULL,
    message_type ENUM('user', 'system', 'ai', 'teacher') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_created (room_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI 請求記錄表
CREATE TABLE IF NOT EXISTS ai_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    request_type ENUM('explain', 'debug', 'optimize', 'suggest') NOT NULL,
    request_content TEXT NOT NULL,
    response_content TEXT,
    response_time_ms INT,
    tokens_used INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_created (room_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 3. 🔄 WebSocket 連接穩定性保障

### 目標：確保 WebSocket 服務器不受資料庫變更影響

#### 3.1 隔離策略
- **資料庫層錯誤隔離**：Database 類錯誤不影響 WebSocket 運行
- **降級機制**：MySQL 失敗時自動切換 SQLite
- **連接池管理**：避免資料庫連接耗盡

#### 3.2 WebSocket 服務器優化
```php
// websocket/server.php 中的安全資料庫調用
private function safeDbOperation($operation) {
    try {
        return $operation();
    } catch (Exception $e) {
        error_log("Database operation failed: " . $e->getMessage());
        // 繼續 WebSocket 服務，不中斷連接
        return false;
    }
}
```

## 4. 🔗 前後端API接口統一化

### 目標：解決前後端函數映射錯誤問題

#### 4.1 API 端點標準化
```php
// 統一 API 回應格式
{
    "success": boolean,
    "data": object|array|null,
    "error": string|null,
    "timestamp": string,
    "version": string
}
```

#### 4.2 前端 save-load.js 接口映射
```javascript
// 確保前端調用與後端 API 完全匹配
const API_ENDPOINTS = {
    SAVE_CODE: '/api/code.php?action=save',
    LOAD_CODE: '/api/code.php?action=load',
    GET_HISTORY: '/api/history.php?action=get',
    JOIN_ROOM: '/api/rooms.php?action=join',
    LEAVE_ROOM: '/api/rooms.php?action=leave'
};
```

## 5. 💾 代碼保存載入機制強化

### 目標：徹底解決代碼保存載入失敗問題

#### 5.1 事務完整性
```php
// 代碼保存事務
public function saveCodeWithTransaction($roomId, $userId, $code, $description = null) {
    $this->pdo->beginTransaction();
    try {
        // 1. 更新房間當前代碼
        $this->updateRoomCurrentCode($roomId, $code);
        
        // 2. 保存到歷史記錄
        $versionId = $this->saveCodeHistory($roomId, $userId, $code, $description);
        
        $this->pdo->commit();
        return ['success' => true, 'version_id' => $versionId];
    } catch (Exception $e) {
        $this->pdo->rollBack();
        throw $e;
    }
}
```

#### 5.2 版本控制優化
- **自動版本號生成**：防止版本衝突
- **增量保存**：僅保存變更部分（大代碼文件）
- **併發控制**：處理多用戶同時保存

## 6. 👥 用戶房間管理優化

### 目標：解決用戶加入房間失敗問題

#### 6.1 房間狀態管理
```php
// 房間加入邏輯優化
public function joinRoomSafely($roomCode, $userId, $password = null) {
    // 1. 驗證房間存在且活躍
    $room = $this->getRoomByCode($roomCode);
    if (!$room || !$room['is_active']) {
        return ['success' => false, 'error' => '房間不存在或已關閉'];
    }
    
    // 2. 驗證密碼
    if ($room['room_password'] && $room['room_password'] !== $password) {
        return ['success' => false, 'error' => '房間密碼錯誤'];
    }
    
    // 3. 檢查用戶容量
    $userCount = $this->getRoomUserCount($room['id']);
    if ($userCount >= $room['max_users']) {
        return ['success' => false, 'error' => '房間已滿'];
    }
    
    // 4. 加入房間
    return $this->addUserToRoom($room['id'], $userId);
}
```

## 7. 🔄 資料同步與一致性

### 目標：確保前後端資料同步

#### 7.1 實時同步機制
- **WebSocket 廣播**：代碼變更即時推送
- **心跳檢測**：定期同步用戶狀態
- **衝突檢測**：多用戶編輯衝突處理

#### 7.2 資料一致性檢查
```php
// 定期一致性檢查
public function validateDataConsistency() {
    // 檢查房間用戶數是否正確
    // 檢查代碼版本號是否連續
    // 檢查在線用戶狀態是否同步
}
```

## 8. 📝 具體實施步驟

### 第一階段：XAMPP MySQL 基礎環境（立即執行）
1. ✅ 確認 XAMPP MySQL 服務運行
2. ⭕ 配置 MySQL root 認證
3. ⭕ 更新 Database.php 優先級邏輯
4. ⭕ 測試基礎連接

### 第二階段：資料庫 Schema 建立（次要優先）
1. ⭕ 創建標準化資料表
2. ⭕ 建立外鍵約束
3. ⭕ 創建必要索引
4. ⭕ 測試 CRUD 操作

### 第三階段：API 整合（核心重點）
1. ⭕ 更新所有 API 端點
2. ⭕ 統一錯誤處理
3. ⭕ 前後端接口對接
4. ⭕ WebSocket 整合測試

### 第四階段：功能驗證（最終測試）
1. ⭕ 代碼保存載入測試
2. ⭕ 用戶房間管理測試
3. ⭕ WebSocket 連接穩定性測試
4. ⭕ 併發用戶測試

## 🎯 成功指標

### 關鍵指標：
- ✅ WebSocket 連接 99% 穩定
- ✅ 代碼保存載入 100% 成功率
- ✅ 用戶加入房間 100% 成功率  
- ✅ 前後端數據完全同步
- ✅ 零函數映射錯誤

### 監控機制：
- 實時錯誤日誌監控
- 性能指標追蹤
- 用戶體驗回饋

**優先級策略確保：XAMPP MySQL 為主力，避免系統衝突，WebSocket 穩定運行，前後端完美對接！** 