# 🔑 OpenAI API Key 設置完整指南

## **🚨 當前狀況**
- ✅ 本地環境配置文件讀取正常
- ❌ **API Key 無效** (HTTP 401 錯誤)
- ✅ 系統智能回退模擬模式工作正常

## **📋 檢查結果摘要**

### **本地環境 (localhost:8080)**
- **配置來源**: 環境變數
- **API Key**: `sk-proj-***Y_kA` (無效)
- **狀態**: 回退到模擬模式
- **5個AI按鈕**: 全部正常工作，使用本地智能回應

### **需要修復**
1. **獲取有效的 OpenAI API Key**
2. **更新本地配置**
3. **設置 Zeabur 環境變數**

---

## **🔧 解決步驟**

### **步驟1: 獲取有效的 OpenAI API Key**

1. 訪問 [OpenAI Platform](https://platform.openai.com/account/api-keys)
2. 登入您的 OpenAI 帳戶
3. 點擊 "Create new secret key"
4. 複製新生成的 API Key (格式: `sk-proj-...`)

### **步驟2: 本地環境設置**

#### **方法A: 更新 ai_config.json (推薦)**
```bash
# 編輯配置文件
notepad ai_config.json
```

將 `openai_api_key` 替換為您的新 API Key：
```json
{
    "openai_api_key": "sk-proj-您的新API密鑰",
    "model": "gpt-3.5-turbo",
    "max_tokens": 1000,
    "temperature": 0.7,
    "timeout": 30000,
    "enabled": true
}
```

#### **方法B: 設置環境變數**
```bash
# PowerShell
$env:OPENAI_API_KEY = "sk-proj-您的新API密鑰"

# 或命令提示符
set OPENAI_API_KEY=sk-proj-您的新API密鑰
```

### **步驟3: Zeabur 環境設置**

1. **登入 Zeabur 控制台**
   - 訪問 [Zeabur Dashboard](https://zeabur.com/dashboard)

2. **選擇您的專案**
   - 找到 PythonLearn 專案

3. **設置環境變數**
   - 進入 Project Settings
   - 點擊 Environment Variables
   - 添加新變數：
     ```
     Key: OPENAI_API_KEY
     Value: sk-proj-您的新API密鑰
     ```

4. **重新部署**
   - 點擊 Deploy 或等待自動部署

---

## **🧪 測試驗證**

### **本地測試**
```bash
# 測試API配置
php test_real_api.php

# 測試AI功能
curl -X POST http://localhost:8080/api/ai.php \
  -H "Content-Type: application/json" \
  -d '{"action":"analyze","code":"print(\"Hello World\")","requestId":"test"}'
```

### **Zeabur測試**
訪問您的 Zeabur 應用並測試5個AI助教按鈕：
- 解釋程式
- 檢查錯誤  
- 改進建議
- 衝突分析
- AI運行代碼

---

## **🎯 5個AI助教按鈕確認**

所有按鈕都已正確配置調用 OpenAI API：

| 按鈕 | 功能 | API調用 | 狀態 |
|------|------|---------|------|
| 🔍 解釋程式 | `analyze` | ✅ 調用OpenAI | 正常 |
| 🐛 檢查錯誤 | `check_errors` | ✅ 調用OpenAI | 正常 |
| 💡 改進建議 | `improvement_tips` | ✅ 調用OpenAI | 正常 |
| 🌿 衝突分析 | `conflict_analysis` | ✅ 調用OpenAI | 已修復 |
| ▶️ AI運行代碼 | `run_code` | ✅ 調用OpenAI | 已添加 |

---

## **📊 當前系統智能回退機制**

系統已實現完善的回退機制：

1. **優先嘗試真實API** - 有效API Key時使用OpenAI
2. **智能本地回應** - API失敗時使用本地分析
3. **無縫用戶體驗** - 用戶無感知切換

### **模式說明**
- `real_api` - 真實OpenAI API調用
- `mock` - 本地智能模擬模式  
- `mock_fallback` - API失敗後的智能回退

---

## **🚀 部署確認**

### **完成後確認清單**
- [ ] 本地 API Key 有效性測試
- [ ] Zeabur 環境變數設置  
- [ ] 5個AI助教按鈕功能測試
- [ ] 真實API模式確認 (`mode: "real_api"`)

### **成功指標**
- HTTP Status: 200
- Mode: `real_api` (而非 `mock_fallback`)
- 回應內容豐富且具體
- 響應時間合理 (通常1-3秒)

---

## **🆘 故障排除**

### **常見問題**
1. **API Key無效** → 檢查Key格式和有效性
2. **網路問題** → 檢查防火牆和代理設置
3. **額度不足** → 檢查OpenAI帳戶餘額
4. **模型不存在** → 確認使用支援的模型名稱

### **調試命令**
```bash
# 檢查配置狀態
php public/test_ai_config.php

# 測試真實API
php test_real_api.php

# 查看服務器日誌
tail -f php_server.log
```

---

## **📞 支援**

如需進一步協助：
1. 檢查 OpenAI Platform 帳戶狀態
2. 確認 API Key 權限設置
3. 檢查帳戶餘額是否充足
4. 嘗試重新生成 API Key 