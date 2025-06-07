# Zeabur部署指南

## 📋 部署前檢查清單

### ✅ 必要文件確認
- [x] `index.php` - 主入口點
- [x] `config.env` - 環境變數配置
- [x] `zeabur.json` - Zeabur部署配置
- [x] `backend/` - 後端代碼目錄
- [x] `frontend/` - 前端代碼目錄
- [x] `websocket/` - WebSocket伺服器
- [x] `test_ai_functions.html` - AI功能測試頁面

### ✅ AI助教功能確認
- [x] OpenAI API Key 已設置
- [x] AIAssistant.php 類別完整
- [x] 五大功能實現完成
- [x] API端點正常運作
- [x] 錯誤處理機制完善

## 🚀 部署步驟

### 1. 準備GitHub倉庫
```bash
# 初始化Git倉庫（如果還沒有）
git init

# 添加所有文件
git add .

# 提交變更
git commit -m "AI助教功能完成 - 準備部署到Zeabur"

# 推送到GitHub
git remote add origin https://github.com/你的用戶名/python-teaching-platform.git
git push -u origin main
```

### 2. 在Zeabur創建專案
1. 登入 [Zeabur控制台](https://zeabur.com)
2. 點擊「Create Project」
3. 選擇「Import from GitHub」
4. 選擇你的倉庫

### 3. 配置環境變數
在Zeabur專案設置中添加以下環境變數：

```env
# OpenAI配置
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4o-mini
OPENAI_MAX_TOKENS=2000

# 應用配置
APP_ENV=production
APP_DEBUG=false
APP_URL=https://你的域名.zeabur.app

# WebSocket配置
WS_HOST=0.0.0.0
WS_PORT=8080

# 日誌配置
LOG_LEVEL=info
LOG_TO_FILE=true
LOG_TO_DB=false
```

### 4. 部署配置
Zeabur會自動檢測到 `zeabur.json` 配置文件：

```json
{
  "name": "python-teaching-platform",
  "type": "php",
  "environment": {
    "OPENAI_API_KEY": "你的API密鑰",
    "OPENAI_MODEL": "gpt-4o-mini",
    "OPENAI_MAX_TOKENS": "2000",
    "APP_ENV": "production"
  },
  "ports": [
    {"port": 80, "type": "http"},
    {"port": 8080, "type": "websocket"}
  ]
}
```

### 5. 啟動部署
1. 點擊「Deploy」按鈕
2. 等待部署完成（通常需要2-5分鐘）
3. 獲取部署域名

## 🧪 部署後測試

### 1. 基本功能測試
訪問：`https://你的域名.zeabur.app`
- 確認主頁正常顯示
- 檢查系統狀態顯示

### 2. AI助教功能測試
訪問：`https://你的域名.zeabur.app/test-ai`
- 測試模擬登入功能
- 逐一測試五大AI功能：
  - ✅ 解釋程式碼
  - ✅ 檢查錯誤
  - ✅ 改進建議
  - ✅ 衝突分析
  - ✅ 詢問問題

### 3. API端點測試
```bash
# 測試AI API（需要先登入獲取session）
curl -X POST https://你的域名.zeabur.app/backend/api/ai.php \
  -H "Content-Type: application/json" \
  -d '{"action":"explain","code":"print(\"Hello World\")"}'
```

## 🔧 故障排除

### 常見問題

#### 1. API密鑰錯誤
**症狀**：AI功能返回錯誤
**解決**：檢查環境變數中的OPENAI_API_KEY是否正確設置

#### 2. 會話問題
**症狀**：提示「請先登入」
**解決**：確認auth.php正常運作，或使用模擬登入

#### 3. CORS錯誤
**症狀**：前端無法調用API
**解決**：檢查APIResponse::setCORSHeaders()是否正確設置

#### 4. WebSocket連接失敗
**症狀**：多人協作功能無法使用
**解決**：確認8080端口已開放，WebSocket伺服器正常運行

### 日誌檢查
在Zeabur控制台查看應用日誌：
1. 進入專案詳情頁
2. 點擊「Logs」標籤
3. 查看錯誤信息

## 📊 性能監控

### 監控指標
- API響應時間
- OpenAI Token使用量
- 錯誤率
- 用戶活躍度

### 優化建議
1. **快取機制**：對常見問題實施快取
2. **速率限制**：防止API濫用
3. **負載均衡**：高流量時的擴展策略
4. **監控告警**：設置關鍵指標告警

## 🔄 持續部署

### 自動部署設置
1. 在GitHub設置Webhook
2. 配置自動部署觸發條件
3. 設置部署前測試流程

### 版本管理
```bash
# 標記版本
git tag -a v1.0.0 -m "AI助教功能完整版本"
git push origin v1.0.0

# 發布新版本
git add .
git commit -m "功能更新：新增XXX功能"
git push origin main
```

## 📞 技術支援

### 聯繫方式
- GitHub Issues：報告Bug和功能請求
- 技術文檔：查看詳細API文檔
- 社群討論：參與開發者社群

### 備份策略
1. 定期備份代碼到GitHub
2. 導出重要配置和數據
3. 建立災難恢復計劃

---

**部署完成後，您的Python教學多人協作平台就可以正式投入使用了！** 🎉 