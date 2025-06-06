# 🚀 Python多人協作教學平台 - 部署指南

## 📋 概述
本指南將協助您在本地開發環境和Zeabur雲端平台上部署Python多人協作教學平台。

---

## 🏠 本地開發環境部署

### 系統要求
- **PHP**: 8.0 或更高版本
- **Composer**: 最新版本
- **MySQL**: 8.0+ (可選，系統會自動降級到SQLite)
- **Node.js**: 16+ (可選，用於前端工具)

### 快速啟動

#### 方法一：使用啟動腳本 (推薦)
```bash
# Windows
.\start.bat

# 或者手動啟動
php -S localhost:8080 router.php &
php websocket/server.php
```

#### 方法二：分別啟動服務
```bash
# 終端1：啟動Web服務器
php -S localhost:8080 router.php

# 終端2：啟動WebSocket服務器
php websocket/server.php
```

### 訪問地址
- **學生端**: http://localhost:8080
- **教師後台**: http://localhost:8080/teacher-dashboard.html
- **健康檢查**: http://localhost:8080/health

### 本地配置
系統會自動檢測本地環境並使用以下配置：
- **WebSocket URL**: `ws://localhost:8080`
- **數據庫**: SQLite (自動降級)
- **AI API**: 需要設置 `OPENAI_API_KEY` 環境變數

---

## ☁️ Zeabur 雲端部署

### 前置準備

#### 1. 準備GitHub倉庫
```bash
# 確保代碼已推送到GitHub
git add .
git commit -m "🚀 準備Zeabur部署"
git push origin main
```

#### 2. 檢查必要檔案
確保以下檔案存在且配置正確：
- ✅ `zeabur.yaml` - Zeabur部署配置
- ✅ `composer.json` - PHP依賴配置
- ✅ `router.php` - 路由處理器
- ✅ `websocket/server.php` - WebSocket服務器

### Zeabur 部署步驟

#### 1. 創建Zeabur專案
1. 登入 [Zeabur控制台](https://zeabur.com)
2. 點擊 "New Project"
3. 選擇 "Deploy from GitHub"
4. 選擇您的倉庫：`PythonLearn-Zeabur-PHP`

#### 2. 配置環境變數
在Zeabur控制台中設置以下環境變數：

```bash
# WebSocket配置
WSS_URL=wss://your-domain.zeabur.app/ws
WEBSOCKET_HOST=0.0.0.0
WEBSOCKET_PORT=8081

# 數據庫配置 (可選)
MYSQL_HOST=your-mysql-host
MYSQL_USER=your-username
MYSQL_PASSWORD=your-password
MYSQL_DATABASE=python_collaboration

# AI配置 (可選)
OPENAI_API_KEY=sk-proj-your-api-key-here
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_MAX_TOKENS=1000
```

#### 3. 部署配置
Zeabur會自動讀取 `zeabur.yaml` 配置檔案：

```yaml
# zeabur.yaml
name: pythonlearn-collaboration

services:
  app:
    # 啟動配置
    start: |
      php websocket/server.php &
      php -S 0.0.0.0:8080 router.php
    
    # 環境變數
    envs:
      WSS_URL: wss://${ZEABUR_WEB_DOMAIN}/ws
      WEBSOCKET_PORT: 8081
      WEBSOCKET_HOST: 0.0.0.0
```

#### 4. 域名配置
1. 部署完成後，Zeabur會提供一個默認域名
2. 記錄域名，例如：`python-learn.zeabur.app`
3. 更新環境變數中的 `WSS_URL`

### 部署後驗證

#### 1. 健康檢查
```bash
curl https://your-domain.zeabur.app/health
```

預期回應：
```json
{
  "status": "healthy",
  "timestamp": "2025-06-05T22:51:11+00:00",
  "services": {
    "web": "running",
    "websocket": "running",
    "php_version": "8.4.7"
  },
  "environment": {
    "is_zeabur": true,
    "websocket_port": 8081,
    "domain": "your-domain.zeabur.app"
  }
}
```

#### 2. 功能測試
- ✅ 訪問主頁：`https://your-domain.zeabur.app`
- ✅ WebSocket連接：檢查瀏覽器控制台
- ✅ AI助教功能：測試代碼解釋功能
- ✅ 多人協作：開啟多個瀏覽器標籤測試

---

## 🔧 故障排除

### 常見問題

#### 1. WebSocket連接失敗
**症狀**: 瀏覽器控制台顯示 `WebSocket connection failed`

**解決方案**:
```javascript
// 檢查前端WebSocket URL配置
console.log('WebSocket URL:', window.WSS_URL);

// 本地環境應該是: ws://localhost:8080
// 雲端環境應該是: wss://your-domain.zeabur.app/ws
```

**修復步驟**:
1. 確認環境變數 `WSS_URL` 設置正確
2. 檢查WebSocket服務器是否正常啟動
3. 驗證路由器是否正確處理 `/ws` 路徑

#### 2. AI API 404錯誤
**症狀**: AI助教功能返回404錯誤

**解決方案**:
```bash
# 檢查路由器配置
curl http://localhost:8080/backend/api/ai.php

# 確認檔案結構
ls -la backend/api/ai.php
```

**修復步驟**:
1. 確認 `backend/api/ai.php` 檔案存在
2. 檢查 `router.php` 路由配置
3. 驗證 `OPENAI_API_KEY` 環境變數

#### 3. 數據庫連接問題
**症狀**: 用戶數據無法保存

**解決方案**:
系統會自動降級到SQLite，無需手動處理。

**檢查方法**:
```bash
# 查看健康檢查
curl http://localhost:8080/health

# 檢查日誌
tail -f logs/app.log
```

### 日誌檢查

#### 本地環境
```bash
# WebSocket服務器日誌
php websocket/server.php

# Web服務器日誌
php -S localhost:8080 router.php
```

#### Zeabur環境
1. 登入Zeabur控制台
2. 選擇您的專案
3. 點擊 "Logs" 標籤
4. 查看實時日誌輸出

---

## 📊 性能監控

### 監控指標
- **WebSocket連接數**: 實時用戶數量
- **API響應時間**: 平均 < 500ms
- **內存使用**: 建議 < 512MB
- **CPU使用率**: 建議 < 80%

### 監控工具
```bash
# 健康檢查端點
GET /health

# 回應範例
{
  "status": "healthy",
  "services": {
    "web": "running",
    "websocket": "running"
  },
  "metrics": {
    "active_connections": 5,
    "memory_usage": "45MB",
    "uptime": "2h 30m"
  }
}
```

---

## 🔄 更新部署

### 本地更新
```bash
# 拉取最新代碼
git pull origin main

# 更新依賴
composer install

# 重啟服務
.\start.bat
```

### Zeabur更新
1. 推送代碼到GitHub：
```bash
git add .
git commit -m "🔧 更新功能"
git push origin main
```

2. Zeabur會自動檢測並重新部署

### 回滾策略
```bash
# 回滾到上一個版本
git revert HEAD
git push origin main

# 或者回滾到特定提交
git reset --hard <commit-hash>
git push origin main --force
```

---

## 📚 進階配置

### 自定義域名
1. 在Zeabur控制台中添加自定義域名
2. 更新DNS記錄指向Zeabur
3. 更新環境變數中的域名配置

### SSL證書
Zeabur會自動為您的域名配置SSL證書，無需手動設置。

### 數據庫升級
如需使用MySQL數據庫：
1. 在Zeabur中添加MySQL服務
2. 配置相關環境變數
3. 系統會自動切換到MySQL

---

## 🆘 技術支援

### 聯繫方式
- **GitHub Issues**: [提交問題](https://github.com/js0980420/PythonLearn-Zeabur/issues)
- **文檔**: 查看 `WEBSOCKET_CONFIG.md`
- **日誌**: 檢查 `/health` 端點

### 常用命令
```bash
# 檢查系統狀態
curl http://localhost:8080/health

# 重啟服務
.\start.bat

# 查看WebSocket連接
netstat -an | findstr :8080

# 檢查PHP版本
php --version

# 驗證Composer依賴
composer validate
```

---

**📝 文檔版本**: v1.0  
**📅 最後更新**: 2025-06-05  
**🔧 維護狀態**: 活躍維護

**🎯 部署成功標準**: 
- ✅ 健康檢查返回200狀態
- ✅ WebSocket連接正常建立
- ✅ AI助教功能正常回應
- ✅ 多人協作同步正常
- ✅ 所有頁面正常載入 