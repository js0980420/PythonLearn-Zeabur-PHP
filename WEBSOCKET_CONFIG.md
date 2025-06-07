# 🌐 WebSocket 配置指南

## 📋 概述
此文檔說明如何在不同環境（本地開發、Zeabur雲端）中配置 WebSocket 連接。

---

## 🏠 本地開發環境

### 自動配置
本地開發環境會自動檢測 `localhost` 或 `127.0.0.1`，並連接到：
```
ws://localhost:8080
```

### 手動配置
如果需要手動指定，可以在 `index.html` 中添加：
```html
<script>
window.WSS_URL = 'ws://localhost:8080';
</script>
```

### 啟動指令
```bash
# 啟動 WebSocket 服務器
php websocket/server.php

# 啟動 Web 服務器
php -S localhost:8080 router.php
```

---

## ☁️ Zeabur 雲端環境

### 環境變數配置

#### 在 Zeabur 控制台設置：
```bash
# 主要 WebSocket URL (自動替換域名)
WSS_URL=wss://your-domain.zeabur.app/ws

# WebSocket 服務器配置
WEBSOCKET_HOST=0.0.0.0
WEBSOCKET_PORT=8081

# 數據庫配置
MYSQL_HOST=your-mysql-host
MYSQL_USER=your-username  
MYSQL_PASSWORD=your-password
MYSQL_DATABASE=python_collaboration

# AI 配置 (可選)
OPENAI_API_KEY=sk-proj-your-api-key-here
```

#### zeabur.yaml 配置：
```yaml
services:
  app:
    envs:
      WSS_URL: wss://${ZEABUR_WEB_DOMAIN}/ws
      WEBSOCKET_PORT: 8081
      WEBSOCKET_HOST: 0.0.0.0
    ports:
      - 8080  # HTTP 服務
      - 8081  # WebSocket 服務
```

### 手動域名配置
如果使用自定義域名，請更新 `index.html`：
```html
<script>
window.WSS_URL = 'wss://your-custom-domain.com/ws';
</script>
```

---

## 🔧 多環境配置

### 通用配置方法
在 `index.html` 中添加環境檢測：
```html
<script>
// 環境自動檢測配置
(function() {
    const hostname = window.location.hostname;
    
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
        // 本地開發環境
        window.WSS_URL = 'ws://localhost:8080';
    } else if (hostname.includes('zeabur.app')) {
        // Zeabur 環境
        window.WSS_URL = `wss://${hostname}/ws`;
    } else {
        // 自定義域名
        window.WSS_URL = `wss://${hostname}/ws`;
    }
    
    console.log('🔧 WebSocket URL 已設置:', window.WSS_URL);
})();
</script>
```

---

## 🚨 故障排除

### 常見問題

#### 1. WebSocket 連接失敗 (Code: 1006)
**原因**: 端口未開放或服務未啟動
**解決**: 
- 確認 WebSocket 服務器正在運行
- 檢查防火牆設置
- 驗證端口配置

#### 2. CORS 錯誤
**原因**: 跨域請求被阻止
**解決**: 確認 `router.php` 中的 CORS 設置：
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
```

#### 3. SSL/TLS 證書問題
**原因**: HTTPS 頁面嘗試連接 WS (非安全) 協議
**解決**: 確保使用 WSS 協議：
```javascript
const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
```

#### 4. 代理配置問題
**原因**: 反向代理未正確轉發 WebSocket 請求
**解決**: 檢查 Nginx 配置：
```nginx
location /ws {
    proxy_pass http://127.0.0.1:8081;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
}
```

---

## 📊 測試工具

### WebSocket 連接測試
```javascript
// 在瀏覽器控制台中運行
const testWS = new WebSocket(window.WSS_URL || 'ws://localhost:8080');
testWS.onopen = () => console.log('✅ WebSocket 連接成功');
testWS.onerror = (error) => console.error('❌ WebSocket 連接失敗', error);
testWS.onclose = (event) => console.log('🔌 WebSocket 已關閉', event.code, event.reason);
```

### 健康檢查
```bash
# 檢查 Web 服務
curl https://your-domain.zeabur.app/health

# 檢查 WebSocket 服務 (本地)
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Key: test" -H "Sec-WebSocket-Version: 13" http://localhost:8080/ws
```

---

## 🎯 最佳實踐

### 1. 環境變數優先級
```
1. window.WSS_URL (最高)
2. 環境變數 ZEABUR_WEB_DOMAIN  
3. 自動檢測 hostname
4. 預設值 localhost:8080
```

### 2. 連接重試機制
- 最大重試次數: 5 次
- 重試間隔: 遞增 (1s, 2s, 4s, 8s, 16s)
- 心跳檢測: 每 25 秒

### 3. 錯誤記錄
```javascript
console.log('🔌 WebSocket 連接狀態:', ws.readyState);
console.log('🌐 當前 URL:', window.WSS_URL);
console.log('🏷️ 用戶代理:', navigator.userAgent);
```

---

## 📞 技術支援

如果遇到連接問題，請提供以下信息：
1. 瀏覽器控制台錯誤訊息
2. 當前使用的域名/URL
3. WebSocket 連接狀態 (readyState)
4. 網絡環境 (本地/雲端)

**文檔版本**: v1.0  
**最後更新**: 2025-01-28 