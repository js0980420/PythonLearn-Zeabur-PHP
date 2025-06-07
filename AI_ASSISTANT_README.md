# AI助教功能測試與部署指南

## 🎯 功能概述

本平台的AI助教提供五大核心功能：

### 1. 解釋程式碼 (explainCode)
- **功能**：分析Python代碼並提供詳細解釋
- **參數**：代碼內容、詳細程度（basic/detailed/expert）
- **回應**：代碼功能說明、執行流程、學習重點

### 2. 檢查錯誤 (checkErrors)
- **功能**：檢測代碼中的各種錯誤
- **檢查類型**：語法錯誤、邏輯錯誤、性能問題、安全隱患
- **回應**：錯誤報告、修正建議、代碼評價

### 3. 改進建議 (suggestImprovements)
- **功能**：提供代碼優化建議
- **關注領域**：性能優化、可讀性改進、最佳實踐
- **回應**：具體改進建議、優化代碼範例

### 4. 衝突分析 (analyzeConflict)
- **功能**：分析多人協作時的代碼衝突
- **分析內容**：衝突原因、差異比較、合併建議
- **回應**：衝突解決方案、風險評估

### 5. 詢問問題 (answerQuestion)
- **功能**：回答Python程式設計相關問題
- **問題類別**：Python程式設計、網頁操作、一般問題
- **回應**：詳細解答、代碼範例、學習建議

## 🔧 本地測試

### 方法一：使用批次文件（推薦）
1. 雙擊執行 `start_local_server.bat`
2. 瀏覽器開啟 `http://localhost:8000`
3. 點擊「🧪 測試AI助教功能」進入測試頁面

### 方法二：手動啟動
```bash
# 如果已安裝PHP
php -S localhost:8000 start_server.php

# 或直接訪問測試頁面
# 將 test_ai_functions.html 放到Web伺服器目錄
```

### 測試步驟
1. **模擬登入**：點擊「模擬登入」按鈕
2. **測試五大功能**：
   - 解釋程式碼：使用預設的Fibonacci代碼
   - 檢查錯誤：測試除零錯誤檢測
   - 改進建議：優化find_max函數
   - 衝突分析：比較兩個greet函數版本
   - 詢問問題：測試Python知識問答

## 🌐 Zeabur部署

### 1. 環境變數設置
在Zeabur控制台設置以下環境變數：
```
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4o-mini
OPENAI_MAX_TOKENS=2000
APP_ENV=production
APP_DEBUG=false
```

### 2. 部署配置
- 使用 `zeabur.json` 配置文件
- 支援HTTP (80端口) 和WebSocket (8080端口)
- 自動載入環境變數

### 3. 部署後測試
1. 訪問部署的域名
2. 點擊「🧪 測試AI助教功能」
3. 確認所有五大功能正常運作

## 📋 API端點

### AI助教API (`/backend/api/ai.php`)

#### 1. 解釋代碼
```javascript
POST /backend/api/ai.php
{
    "action": "explain",
    "code": "Python代碼",
    "detail_level": "basic" // basic/detailed/expert
}
```

#### 2. 檢查錯誤
```javascript
POST /backend/api/ai.php
{
    "action": "check_errors",
    "code": "Python代碼",
    "error_types": ["syntax", "logic", "performance", "security"]
}
```

#### 3. 改進建議
```javascript
POST /backend/api/ai.php
{
    "action": "suggest_improvements",
    "code": "Python代碼",
    "focus_areas": ["performance", "readability", "best_practices"]
}
```

#### 4. 衝突分析
```javascript
POST /backend/api/ai.php
{
    "action": "conflict",
    "original_code": "原始代碼",
    "conflict_code": "衝突代碼"
}
```

#### 5. 詢問問題
```javascript
POST /backend/api/ai.php
{
    "action": "question",
    "question": "問題內容",
    "context": "相關上下文（可選）",
    "category": "python_programming" // python_programming/web_operation/general
}
```

## 🔍 技術實現

### 核心類別
- **AIAssistant.php**：AI助教主要邏輯
- **MockDatabase.php**：模擬資料庫（測試用）
- **Logger.php**：日誌記錄系統
- **APIResponse.php**：統一API響應格式

### 特色功能
- **智能提示生成**：根據不同功能類型生成專業提示詞
- **錯誤處理**：完整的錯誤捕獲和日誌記錄
- **使用統計**：追蹤API使用量和性能指標
- **速率限制**：防止API濫用
- **模擬模式**：無API密鑰時提供模擬響應

## 🚀 下一步驟

1. **本地測試完成**：確認所有五大功能正常運作
2. **部署到Zeabur**：使用提供的配置文件
3. **整合到主平台**：將AI助教功能整合到編輯器界面
4. **WebSocket整合**：支援多人協作時的AI功能
5. **教師監控**：添加教師查看學生AI使用情況的功能

## 📞 支援

如果遇到問題：
1. 檢查OpenAI API密鑰是否正確設置
2. 確認網路連接正常
3. 查看瀏覽器開發者工具的錯誤信息
4. 檢查伺服器日誌文件

---

**注意**：本系統使用真實的OpenAI API，請妥善保管API密鑰，避免洩露。 