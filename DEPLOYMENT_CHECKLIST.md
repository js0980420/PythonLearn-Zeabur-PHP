# 🚀 Zeabur 部署檢查清單

## 📋 部署前檢查

### ✅ 代碼準備
- [x] 所有修改已提交到 GitHub
- [x] WebSocket 端口衝突已修復 (8080→8081)
- [x] 前端編輯器引用已修正
- [x] 健康檢查端點已增強
- [x] 系統監控腳本已創建
- [x] vendor 依賴已清理

### ✅ 配置檔案
- [x] `zeabur.yaml` 配置完整
- [x] `composer.json` 依賴正確
- [x] `router.php` 路由配置
- [x] `.gitignore` 排除規則
- [x] 環境變數配置

### ✅ 功能測試
- [x] 本地 Web 服務器正常 (8080)
- [x] 本地 WebSocket 服務器正常 (8081)
- [x] AI API 回應正常
- [x] 健康檢查端點正常
- [x] 系統檢查腳本正常

## 🌐 Zeabur 部署步驟

### 1. 連接 GitHub 倉庫
```
倉庫 URL: https://github.com/js0980420/PythonLearn-Zeabur-PHP
分支: main
```

### 2. 環境變數設定
```bash
# WebSocket 配置
WSS_URL=wss://${ZEABUR_WEB_DOMAIN}/ws
WEBSOCKET_PORT=8081
WEBSOCKET_HOST=0.0.0.0

# AI 配置 (需要設定)
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_MAX_TOKENS=1000
OPENAI_TEMPERATURE=0.3

# 數據庫配置 (Zeabur 自動設定)
MYSQL_HOST=${MYSQL_HOST}
MYSQL_USER=${MYSQL_USER}
MYSQL_PASSWORD=${MYSQL_PASSWORD}
MYSQL_DATABASE=python_collaboration
MYSQL_PORT=3306

# 應用配置
MAX_CONCURRENT_USERS=100
MAX_ROOMS=50
MAX_USERS_PER_ROOM=8
```

### 3. 服務配置
- **主應用**: PHP 8.4 + WebSocket
- **數據庫**: MySQL 8.0
- **端口**: 8080 (HTTP), 8081 (WebSocket)
- **域名**: 自動分配或自定義

### 4. 部署後驗證

#### 基本功能檢查
- [ ] 主頁面載入正常
- [ ] WebSocket 連接成功
- [ ] AI 助教回應正常
- [ ] 多用戶協作功能
- [ ] 代碼執行功能

#### API 端點檢查
```bash
# 健康檢查
curl https://your-domain.zeabur.app/health

# AI API 測試
curl -X POST https://your-domain.zeabur.app/backend/api/ai.php \
  -H "Content-Type: application/json" \
  -d '{"message":"Hello"}'

# WebSocket 測試
# 在瀏覽器開發者工具中測試 WebSocket 連接
```

#### 系統監控
- [ ] 檢查日誌輸出
- [ ] 監控資源使用
- [ ] 確認服務穩定性

## 🔧 常見問題排除

### WebSocket 連接失敗
1. 檢查 WSS_URL 環境變數
2. 確認端口 8081 開放
3. 檢查 WebSocket 服務器日誌

### AI API 404 錯誤
1. 檢查 router.php 配置
2. 確認 OPENAI_API_KEY 設定
3. 檢查 API 路由規則

### 數據庫連接失敗
1. 確認 MySQL 服務運行
2. 檢查數據庫環境變數
3. 驗證連接權限

### 前端載入問題
1. 檢查靜態檔案路徑
2. 確認編輯器引用正確
3. 檢查 CORS 設定

## 📊 性能優化建議

### 生產環境配置
- 啟用 PHP OPcache
- 配置適當的內存限制
- 設定合理的並發連接數
- 啟用 Gzip 壓縮

### 監控指標
- HTTP 請求響應時間
- WebSocket 連接數量
- AI API 調用頻率
- 數據庫查詢性能

## 🎯 部署成功標準

### 功能完整性
- [x] 用戶可以創建和加入房間
- [x] 實時代碼同步正常
- [x] AI 助教回應準確
- [x] 衝突檢測和解決
- [x] 代碼執行功能

### 性能指標
- 頁面載入時間 < 3秒
- WebSocket 連接延遲 < 100ms
- AI 回應時間 < 5秒
- 支援 50+ 並發用戶

### 穩定性要求
- 99% 可用性
- 自動錯誤恢復
- 優雅的降級處理
- 完整的錯誤日誌

---

## 🚀 部署完成後的下一步

1. **設定自定義域名** (可選)
2. **配置 SSL 證書** (Zeabur 自動)
3. **設定監控告警**
4. **建立備份策略**
5. **準備用戶文檔**

---

**部署時間**: 預計 5-10 分鐘  
**維護窗口**: 建議在低峰時段部署  
**回滾計劃**: 保留前一版本，必要時快速回滾 