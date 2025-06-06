# 🌐 環境配置指南

## 📋 概述
本文檔詳細說明Python多人協作教學平台在不同環境下的配置方法。

---

## 🏠 本地開發環境

### 自動檢測配置
系統會自動檢測 `localhost` 或 `127.0.0.1` 並使用本地配置：

```javascript
// 自動配置 (index.html)
if (hostname === 'localhost' || hostname === '127.0.0.1') {
    window.WSS_URL = 'ws://localhost:8080';
    console.log('🏠 本地開發環境 - WebSocket URL:', window.WSS_URL);
}
```

### 手動配置 (可選)
如需手動指定本地配置，在 `index.html` 中添加：

```html
<script>
// 強制使用本地配置
window.WSS_URL = 'ws://localhost:8080';
window.API_BASE_URL = 'http://localhost:8080';
</script>
```

### 啟動命令
```bash
# 方法1：使用啟動腳本
.\start.bat

# 方法2：手動啟動
# 終端1
php -S localhost:8080 router.php

# 終端2  
php websocket/server.php
```

### 本地環境特點
- ✅ **WebSocket**: `ws://localhost:8080`
- ✅ **數據庫**: 自動降級到SQLite
- ✅ **AI API**: 可選，需要 `OPENAI_API_KEY`
- ✅ **HTTPS**: 不需要，使用HTTP
- ✅ **域名**: localhost

---

## ☁️ Zeabur 雲端環境

### 自動檢測配置
系統會自動檢測Zeabur環境：

```javascript
// 自動配置 (index.html)
if (hostname.includes('zeabur.app') || hostname.includes('python-learn')) {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    window.WSS_URL = `${protocol}//${hostname}/ws`;
    console.log('☁️ Zeabur 雲端環境 - WebSocket URL:', window.WSS_URL);
}
```

### 環境變數配置
在Zeabur控制台設置：

```bash
# 必要配置
WSS_URL=wss://your-domain.zeabur.app/ws
WEBSOCKET_HOST=0.0.0.0
WEBSOCKET_PORT=8081

# 可選配置
OPENAI_API_KEY=sk-proj-your-api-key-here
MYSQL_HOST=your-mysql-host
MYSQL_USER=your-username
MYSQL_PASSWORD=your-password
MYSQL_DATABASE=python_collaboration
```

### zeabur.yaml 配置
```yaml
name: pythonlearn-collaboration

services:
  app:
    start: |
      php websocket/server.php &
      php -S 0.0.0.0:8080 router.php
    
    envs:
      WSS_URL: wss://${ZEABUR_WEB_DOMAIN}/ws
      WEBSOCKET_PORT: 8081
      WEBSOCKET_HOST: 0.0.0.0
```

### 雲端環境特點
- ✅ **WebSocket**: `wss://your-domain.zeabur.app/ws`
- ✅ **數據庫**: MySQL (可選) 或 SQLite (降級)
- ✅ **AI API**: 支援OpenAI API
- ✅ **HTTPS**: 自動配置SSL證書
- ✅ **域名**: Zeabur提供的域名

---

## 🔧 配置對比表

| 配置項目 | 本地環境 | Zeabur環境 |
|---------|---------|-----------|
| **WebSocket URL** | `ws://localhost:8080` | `wss://domain.zeabur.app/ws` |
| **Web服務器** | `localhost:8080` | `0.0.0.0:8080` |
| **WebSocket服務器** | `localhost:8080` | `0.0.0.0:8081` |
| **協議** | HTTP/WS | HTTPS/WSS |
| **數據庫** | SQLite | MySQL/SQLite |
| **SSL** | 不需要 | 自動配置 |
| **環境檢測** | 自動 | 自動 |

---

## 🚀 快速切換配置

### 開發 → 生產環境
```bash
# 1. 更新代碼
git add .
git commit -m "🚀 準備部署到生產環境"
git push origin main

# 2. Zeabur會自動部署
# 3. 檢查環境變數配置
# 4. 驗證功能正常
```

### 生產 → 開發環境
```bash
# 1. 拉取最新代碼
git pull origin main

# 2. 啟動本地服務
.\start.bat

# 3. 訪問 http://localhost:8080
```

---

## 🔍 環境檢測邏輯

### 前端檢測 (index.html)
```javascript
(function() {
    const hostname = window.location.hostname;
    
    console.log('🔍 檢測當前環境:', hostname);
    
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
        // 本地開發環境
        window.WSS_URL = 'ws://localhost:8080';
        window.ENV_TYPE = 'local';
        console.log('🏠 本地開發環境');
    } else if (hostname.includes('zeabur.app')) {
        // Zeabur 環境
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        window.WSS_URL = `${protocol}//${hostname}/ws`;
        window.ENV_TYPE = 'zeabur';
        console.log('☁️ Zeabur 雲端環境');
    } else {
        // 自定義域名或其他環境
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        window.WSS_URL = `${protocol}//${hostname}/ws`;
        window.ENV_TYPE = 'custom';
        console.log('🌐 自定義環境');
    }
    
    console.log('📡 WebSocket URL:', window.WSS_URL);
})();
```

### 後端檢測 (websocket/server.php)
```php
// 獲取環境變數配置
$host = $_ENV['WEBSOCKET_HOST'] ?? '0.0.0.0';
$port = $_ENV['WEBSOCKET_PORT'] ?? 8080;

// 環境檢測
$isZeabur = isset($_ENV['ZEABUR_DOMAIN']);
$environment = $isZeabur ? '雲端' : '本地';

echo "WebSocket服務器啟動在 {$host}:{$port}\n";
echo "環境: {$environment}\n";
```

---

## 🛠️ 故障排除

### 環境檢測問題
```javascript
// 在瀏覽器控制台執行
console.log('當前環境:', window.ENV_TYPE);
console.log('WebSocket URL:', window.WSS_URL);
console.log('主機名:', window.location.hostname);
console.log('協議:', window.location.protocol);
```

### WebSocket連接問題
```bash
# 本地環境測試
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Key: test" -H "Sec-WebSocket-Version: 13" http://localhost:8080/

# 雲端環境測試
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Key: test" -H "Sec-WebSocket-Version: 13" https://your-domain.zeabur.app/ws
```

### 健康檢查
```bash
# 本地
curl http://localhost:8080/health

# 雲端
curl https://your-domain.zeabur.app/health
```

---

## 📝 配置檢查清單

### 本地環境檢查
- [ ] PHP 8.0+ 已安裝
- [ ] Composer 依賴已安裝
- [ ] 端口8080可用
- [ ] WebSocket服務器正常啟動
- [ ] 健康檢查返回200

### Zeabur環境檢查
- [ ] GitHub代碼已推送
- [ ] zeabur.yaml配置正確
- [ ] 環境變數已設置
- [ ] 域名配置正確
- [ ] SSL證書正常
- [ ] WebSocket連接成功

---

**📝 文檔版本**: v1.0  
**📅 最後更新**: 2025-06-05  
**🔧 維護狀態**: 活躍維護

**🎯 配置成功標準**: 
- ✅ 環境自動檢測正確
- ✅ WebSocket URL配置正確
- ✅ 服務器正常啟動
- ✅ 功能測試通過 