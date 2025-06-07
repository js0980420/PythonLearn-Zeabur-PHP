# 🌐 端口配置總結 - PythonLearn

## 📋 端口分配策略

為了避免測試環境與正式版衝突，我們採用了分離的端口配置策略：

---

## 🏠 正式版服務器端口

### 主要服務
- **🌐 主Web服務器**: `localhost:8080`
- **🔌 WebSocket服務器**: `localhost:8080` (同一端口，不同協議)
- **📊 API端點**: `localhost:8080/api/*`

### 特點
- 所有正式功能都在 `8080` 端口
- 生產環境使用的標準配置
- 用戶實際使用的服務端口

---

## 🧪 測試環境端口

### 獨立測試服務器
- **🔌 API測試服務器**: `localhost:9081`
- **🌐 WebSocket測試服務器**: `localhost:9082`
- **📝 前端測試服務器**: `localhost:9083`
- **🔄 整合測試服務器**: `localhost:9084` (預留)

### 特點
- 使用 `90xx` 系列端口，避免衝突
- 完全獨立於正式版
- 可以同時運行而不互相影響

---

## 🔄 使用場景

### 開發階段
```bash
# 正式版服務器 (8080)
php -S localhost:8080 router.php

# 測試服務器 (90xx)
scripts\start-test-servers.bat
```

### 測試階段
```bash
# 同時運行正式版和測試版
# 正式版: http://localhost:8080
# 測試版: http://localhost:9081, ws://localhost:9082, http://localhost:9083
```

### 整合測試
```bash
# 整合測試環境 (包含正式版 + 測試版)
scripts\start-test-servers.bat
選擇 [5] 整合測試
```

---

## 📊 端口檢查命令

### Windows (PowerShell/CMD)
```bash
# 檢查端口占用
netstat -an | findstr ":8080"
netstat -an | findstr ":9081"
netstat -an | findstr ":9082"
netstat -an | findstr ":9083"

# 檢查特定端口
netstat -ano | findstr ":8080"
```

### 停止占用端口的進程
```bash
# 查找進程ID
netstat -ano | findstr ":8080"

# 停止進程 (替換 PID 為實際進程ID)
taskkill /PID <PID> /F

# 停止所有PHP進程
taskkill /f /im php.exe
```

---

## 🚨 端口衝突解決

### 常見衝突情況
1. **8080端口被占用**
   - 檢查是否有其他Web服務器運行
   - 檢查XAMPP、WAMP等是否啟動
   - 檢查其他PHP開發服務器

2. **90xx端口被占用**
   - 檢查是否有其他測試服務器運行
   - 檢查防火牆設置
   - 檢查其他開發工具

### 解決步驟
```bash
# 1. 檢查端口占用
netstat -ano | findstr ":8080"

# 2. 停止衝突進程
taskkill /PID <PID> /F

# 3. 重新啟動服務器
php -S localhost:8080 router.php

# 4. 驗證服務器運行
curl http://localhost:8080/api/status
```

---

## 🔧 配置文件位置

### 測試服務器配置
- **API測試**: `test-servers/api-test/test_api_server.php`
- **WebSocket測試**: `test-servers/websocket-test/test_websocket_server.php`
- **前端測試**: `test-servers/frontend-test/test_complete_flow.html`

### 啟動腳本配置
- **測試服務器管理器**: `scripts/start-test-servers.bat`
- **快速測試**: `scripts/quick-test.bat`
- **提交前檢查**: `scripts/pre-commit-check.bat`

---

## 📝 修改端口的步驟

如果需要修改端口配置，請按以下順序更新：

### 1. 測試服務器文件
```php
// test-servers/api-test/test_api_server.php
// 修改註釋中的端口號和狀態返回中的端口

// test-servers/websocket-test/test_websocket_server.php  
// 修改 IoServer::factory() 的端口參數
```

### 2. 前端測試文件
```javascript
// test-servers/frontend-test/test_complete_flow.html
const API_BASE = 'http://localhost:新端口';
const WS_BASE = 'ws://localhost:新端口';
```

### 3. 啟動腳本
```batch
// scripts/start-test-servers.bat
// 更新所有 localhost:端口 的引用

// scripts/quick-test.bat
// 更新測試URL和端口檢查
```

### 4. 文檔更新
```markdown
// DEVELOPMENT_WORKFLOW.md
// TEST_DRIVEN_DEVELOPMENT.md
// PORT_CONFIGURATION.md (本文件)
```

---

## 🎯 最佳實踐

### ✅ 端口管理規範
1. **正式版固定**: 8080端口專用於正式版
2. **測試版分離**: 90xx系列專用於測試
3. **文檔同步**: 端口變更必須更新所有相關文檔
4. **腳本一致**: 所有腳本使用統一的端口配置

### ✅ 開發建議
1. **先啟動測試**: 開發新功能時先啟動測試環境
2. **並行運行**: 測試和正式版可以同時運行
3. **端口檢查**: 啟動前檢查端口是否可用
4. **清理資源**: 測試完成後及時停止服務器

---

## 📞 技術支援

### 常用檢查命令
```bash
# 檢查所有服務器狀態
curl http://localhost:8080/api/status    # 正式版
curl http://localhost:9081/api/status    # API測試
curl http://localhost:9082/status        # WebSocket測試
curl http://localhost:9083               # 前端測試
```

### 快速重啟
```bash
# 停止所有PHP進程
taskkill /f /im php.exe

# 重新啟動測試環境
scripts\start-test-servers.bat
```

---

**🎯 目標**: 通過清晰的端口分配，確保開發和測試環境完全隔離，避免相互干擾。

**📅 更新日期**: 2025-06-07  
**📝 版本**: v1.0  
**👥 適用範圍**: 所有開發人員 