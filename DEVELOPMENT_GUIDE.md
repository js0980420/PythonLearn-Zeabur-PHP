# 🗄️ PythonLearn 平台開發順序指南

## 📋 核心原則：資料庫優先，避免結構不匹配

根據經驗，**先建立穩固的 MySQL 資料庫架構**是最佳策略，能有效避免：
- 檔案存儲的競態條件問題
- 數據不一致性
- 結構不匹配錯誤
- 並發操作衝突

---

## 🏆 開發優先順序

### 1. 🥇 **XAMPP 內建 MySQL (本地開發優先)**
```
環境: localhost:3306
用戶: root
密碼: (空)
資料庫: pythonlearn
```

**為什麼優先？**
- ✅ 本地開發穩定可靠
- ✅ 無網路依賴，開發速度快
- ✅ 完整的 phpMyAdmin 管理界面
- ✅ 容易調試和測試

### 2. 🥈 **Zeabur MySQL (雲端部署次之)**
```
環境: 環境變數配置
MYSQL_HOST, MYSQL_PORT, MYSQL_DATABASE
MYSQL_USERNAME, MYSQL_PASSWORD
```

**為什麼次之？**
- ✅ 生產環境部署
- ✅ 自動擴展和備份
- ⚠️ 網路延遲可能影響開發
- ⚠️ 調試相對困難

### 3. 🥉 **其他 MySQL 環境**
- Railway、Docker、AWS RDS 等

---

## 📈 完整開發流程

### 階段 1: 資料庫基礎架構 (🔥 重要!)
```bash
# 1. 確保 XAMPP MySQL 運行
# 2. 執行資料庫初始化
mysql -u root -p < database_setup.sql

# 3. 檢查表格創建
php -r "
require 'public/config/database.php';
$result = testDbConnection();
var_dump($result);
"
```

**確保以下表格存在：**
- ✅ `users` - 用戶基本資料
- ✅ `user_login_logs` - 登入記錄
- ✅ `user_code_history` - 代碼歷史
- ✅ `chat_messages` - 聊天記錄
- ✅ `room_usage_logs` - 房間使用記錄
- ✅ `system_config` - 系統配置

### 階段 2: 資料庫操作層
```php
# 4. 測試資料庫管理類
require 'public/config/database.php';
require 'db_manager.php';

$manager = getDbManager();
$status = $manager->checkDatabaseStatus();
echo json_encode($status, JSON_PRETTY_PRINT);
```

### 階段 3: API 端點重構
```bash
# 5. 修改 api.php 使用資料庫而非檔案
# 重點: 所有數據操作都通過 DatabaseManager
```

### 階段 4: 前端功能測試
```bash
# 6. 啟動本地服務器
php -S localhost:8080 -t public

# 7. 測試所有功能
# - 用戶登入/登出
# - 代碼保存/載入
# - 聊天消息
# - AI 助教功能
```

### 階段 5: 雲端部署
```bash
# 8. 配置 Zeabur 環境變數
# 9. 部署到雲端
# 10. 測試雲端功能
```

---

## ⚡ 關鍵配置文件

### `public/config/database.php`
```php
/**
 * 🔍 環境自動檢測
 * 1. 檢測 Zeabur: getenv('ZEABUR')
 * 2. 檢測 XAMPP: file_exists('/xampp')
 * 3. 預設本地: localhost:3306
 */
```

### `database_setup.sql`
```sql
/**
 * 🗄️ 統一資料庫結構
 * - 支援 XAMPP 和 Zeabur
 * - UTF8MB4 字符集
 * - 完整的索引設計
 * - 視圖和初始配置
 */
```

---

## 🚨 常見錯誤和解決方案

### 錯誤 1: 資料庫連接失敗
```
❌ PDOException: SQLSTATE[HY000] [1049] Unknown database
```
**解決方案:**
```bash
# 確保 MySQL 服務運行
net start mysql  # Windows
sudo service mysql start  # Linux

# 手動創建資料庫
mysql -u root -p -e "CREATE DATABASE pythonlearn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 錯誤 2: 表格不存在
```
❌ Table 'pythonlearn.users' doesn't exist
```
**解決方案:**
```bash
# 重新執行資料庫初始化
mysql -u root -p pythonlearn < database_setup.sql
```

### 錯誤 3: 環境檢測錯誤
```
❌ 檢測到錯誤的環境配置
```
**解決方案:**
```php
// 創建 public/config/local-config.php
<?php
return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'pythonlearn',
    'username' => 'root',
    'password' => '',
    'environment' => 'local'
];
```

---

## 📊 檢查清單

### ✅ 資料庫基礎
- [ ] XAMPP MySQL 運行正常
- [ ] pythonlearn 資料庫已創建
- [ ] 所有表格存在且結構正確
- [ ] 基本配置數據已插入

### ✅ 連接配置
- [ ] DatabaseConfig 類正常工作
- [ ] 環境自動檢測正確
- [ ] 本地配置檔案有效

### ✅ 數據操作
- [ ] DatabaseManager 初始化成功
- [ ] 用戶登入記錄正常
- [ ] 代碼保存/載入正常
- [ ] 聊天記錄正常

### ✅ API 功能
- [ ] api.php 使用資料庫而非檔案
- [ ] 所有端點響應正常
- [ ] 錯誤處理完善

### ✅ 前端功能
- [ ] 用戶登入/登出正常
- [ ] 代碼編輯器正常
- [ ] AI 助教功能正常
- [ ] 聊天功能正常

---

## 🎯 成功標準

**✅ 當看到以下日誌時，表示配置成功：**
```
[2025-06-10T19:54:09] ✅ 資料庫連接成功: localhost:3306/pythonlearn
[2025-06-10T19:54:09] ✅ 資料庫表格檢查/創建完成
[2025-06-10T19:54:09] ✅ 用戶管理表格創建/檢查完成
```

**✅ 資料庫狀態檢查通過：**
```json
{
    "success": true,
    "status": {
        "connection": true,
        "tables": {
            "users": true,
            "user_login_logs": true,
            "user_code_history": true,
            "chat_messages": true
        },
        "stats": {
            "total_users": 5
        }
    }
}
```

---

## 📝 維護和監控

### 定期檢查
```bash
# 檢查資料庫狀態
php -r "
require 'public/config/database.php';
echo json_encode(testDbConnection(), JSON_PRETTY_PRINT);
"

# 清理過期數據
php -r "
require 'db_manager.php';
echo json_encode(performDatabaseCleanup(), JSON_PRETTY_PRINT);
"
```

### 備份策略
```bash
# 本地備份
mysqldump -u root -p pythonlearn > backup_$(date +%Y%m%d).sql

# 雲端自動備份（Zeabur 提供）
```

---

**🎯 記住：資料庫優先，結構統一，環境自動檢測，這是避免錯誤的最佳實踐！** 