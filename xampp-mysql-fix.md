# XAMPP MySQL 端口衝突解決方案

## 🔍 問題診斷
- 端口3306已被其他MySQL服務佔用
- XAMPP MySQL無法啟動
- 系統中有多個MySQL實例運行

## 🎯 解決方案（選擇其一）

### 方案A：修改XAMPP MySQL端口（推薦）

1. **找到XAMPP安裝目錄**
   - 通常在 `C:\xampp\` 或你安裝的位置

2. **編輯MySQL配置檔案**
   - 打開 `C:\xampp\mysql\bin\my.ini`
   - 找到 `[mysqld]` 區段
   - 修改端口：
     ```ini
     [mysqld]
     port=3307
     ```

3. **重新啟動XAMPP MySQL**
   - 在XAMPP控制面板重新啟動MySQL
   - MySQL將在端口3307運行

4. **更新專案配置**
   - 修改環境變數：`MYSQL_PORT=3307`

### 方案B：使用系統MySQL（更簡單）

直接使用系統現有的MySQL服務：

1. **不啟動XAMPP的MySQL**
2. **只啟動XAMPP的Apache（如果需要）**
3. **使用端口3306的系統MySQL**

## 🔧 專案配置更新

### 對於方案A（XAMPP端口3307）：
```php
$_ENV['MYSQL_HOST'] = 'localhost';
$_ENV['MYSQL_PORT'] = '3307';  // 新端口
$_ENV['MYSQL_USER'] = 'root';
$_ENV['MYSQL_PASSWORD'] = '';
```

### 對於方案B（系統MySQL端口3306）：
```php
$_ENV['MYSQL_HOST'] = 'localhost';
$_ENV['MYSQL_PORT'] = '3306';  // 系統MySQL
$_ENV['MYSQL_USER'] = 'root';
$_ENV['MYSQL_PASSWORD'] = '';  // 可能需要密碼
```

## 🚀 測試連接

運行測試腳本驗證連接：
```bash
php use-system-mysql.php
```

## 💡 建議

**推薦方案B（使用系統MySQL）**：
- 更簡單，不需要修改配置
- 避免多個MySQL實例
- 資源使用更高效
- 與現有系統整合更好

**WebSocket服務器會自動適應**：
- 如果MySQL可用 → 使用MySQL
- 如果MySQL不可用 → 降級到localStorage
- 不會影響WebSocket穩定性 