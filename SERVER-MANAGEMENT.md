# PythonLearn 服務器管理指南

## 簡介

這個項目提供了統一的服務器管理腳本，可以輕鬆啟動、停止和監控所有必要的服務。

## 可用的管理腳本

### 1. server.ps1 (推薦)
主要的 PowerShell 管理腳本，功能最完整。

**用法:**
```powershell
# 啟動所有服務器
./server.ps1 start

# 停止所有服務器  
./server.ps1 stop

# 重啟所有服務器
./server.ps1 restart

# 檢查服務器狀態
./server.ps1 status

# 不指定參數時默認啟動
./server.ps1
```

### 2. 批處理腳本 (備用)
如果 PowerShell 不可用，可以使用這些批處理文件：

- `start-servers.bat` - 啟動服務器
- `stop-servers.bat` - 停止服務器  
- `check-status.bat` - 檢查狀態

## 服務器組件

### MySQL (端口 3306)
- 數據庫服務器
- 需要手動啟動 XAMPP MySQL (如果未運行)

### Web 服務器 (端口 8080)  
- PHP 內建服務器
- 提供前端界面
- 網址: http://localhost:8080

### WebSocket 服務器 (端口 8081)
- 實時通信服務器
- 處理多人協作功能
- 連接: ws://localhost:8081

## 使用流程

1. **首次啟動:**
   ```powershell
   ./server.ps1 start
   ```

2. **檢查狀態:**
   ```powershell
   ./server.ps1 status
   ```

3. **訪問應用:**
   - 打開瀏覽器訪問 http://localhost:8080

4. **停止服務:**
   ```powershell
   ./server.ps1 stop
   ```

## 故障排除

### MySQL 未運行
如果看到 "MySQL not running" 警告：
1. 啟動 XAMPP 控制面板
2. 啟動 MySQL 服務
3. 或手動執行: `cd C:\xampp\mysql\bin; .\mysqld.exe --console --standalone`

### 端口衝突
如果遇到端口被佔用的錯誤：
1. 執行 `./server.ps1 stop` 清理現有服務
2. 檢查是否有其他應用佔用端口 8080 或 8081
3. 重新啟動服務

### WebSocket 連接問題
如果 WebSocket 無法啟動：
1. 確保沒有防火牆阻擋端口 8081
2. 檢查 `websocket/server.php` 文件是否存在錯誤
3. 手動運行查看錯誤信息: `php websocket/server.php`

## 特點

- **自動清理**: 啟動前會自動停止現有的 PHP 進程
- **狀態監控**: 實時檢查所有服務器的運行狀態  
- **顏色輸出**: 用不同顏色顯示狀態，便於識別
- **後台運行**: 服務器在後台運行，不佔用終端
- **統一管理**: 一個命令管理所有服務

## 注意事項

- 確保系統已安裝 PHP 並在 PATH 中
- XAMPP MySQL 需要單獨管理
- 關閉終端不會停止後台服務器
- 使用前記得先停止舊的服務避免衝突 