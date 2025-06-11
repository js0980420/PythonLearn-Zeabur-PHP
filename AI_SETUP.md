# AI 助教設定指南

## 🚀 功能介紹

AI 助教提供 5 大核心功能：
1. **解釋程式碼** - 逐行解釋代碼功能
2. **檢查錯誤** - 識別語法和邏輯錯誤
3. **改進建議** - 提供代碼優化建議
4. **衝突分析** - 分析代碼衝突問題
5. **運行代碼** - 預測代碼執行結果

## 🔧 本地開發設定

1. 複製範例配置檔案：
   ```bash
   cp ai_config.json.example ai_config.json
   ```

2. 編輯 `ai_config.json`，填入您的 OpenAI API 金鑰：
   ```json
   {
       "openai_api_key": "sk-proj-YOUR_ACTUAL_API_KEY_HERE",
       "model": "gpt-3.5-turbo",
       "max_tokens": 1000,
       "temperature": 0.7,
       "timeout": 30000,
       "enabled": true
   }
   ```

## ☁️ 雲端部署設定

在 Zeabur 或其他雲端平台，設定環境變數：
- `OPENAI_API_KEY`: 您的 OpenAI API 金鑰

## 🔒 安全性

- `ai_config.json` 已在 `.gitignore` 中，不會被上傳到 Git
- 僅在本地開發時使用配置檔案
- 生產環境使用環境變數

## 📋 配置優先級

1. **本地配置檔案** (`ai_config.json`) - 開發環境優先
2. **環境變數** (`OPENAI_API_KEY`) - 生產環境
3. **模擬模式** - 當無有效配置時

## 🧪 測試

啟動伺服器後，在學生端使用 AI 助教功能：
```bash
php -S localhost:8080 -t public
```

查看伺服器日誌以確認 API 配置是否正確載入。 