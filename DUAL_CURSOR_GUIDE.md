# 🚀 雙Cursor開發環境使用指南

## 概述

這個工具讓您可以同時運行兩個Cursor實例，大幅提升開發效率。支援MCP整合、Background Agent協作，以及多專案並行開發。

## 🛠️ 可用工具

### 1. 快速啟動器 (推薦)
```powershell
.\quick-dual-cursor.ps1
```
**特色：**
- 簡單易用，一鍵啟動
- 自動偵測PHP專案並啟動伺服器
- 智慧專案選擇
- 自動啟動終端

### 2. 完整版啟動器
```powershell
.\dual-cursor-launcher.ps1 -Project1Path "." -Project2Path "C:\other-project" -WithTerminal -WithBackground
```
**特色：**
- 完整的專案配置
- 支援多種程式語言偵測
- 靈活的參數配置

### 3. MCP整合版
```powershell
.\dual-cursor-mcp.ps1 -WithTavily -WithPlaywright
```
**特色：**
- 整合Tavily網路搜索
- 整合Playwright自動化測試
- MCP伺服器自動配置

## 📋 參數說明

| 參數 | 說明 | 預設值 |
|------|------|---------|
| `-Project1Path` | 第一個專案路徑 | 當前目錄 |
| `-Project2Path` | 第二個專案路徑 | 詢問用戶 |
| `-WithMCP` | 啟用MCP支援 | false |
| `-WithTavily` | 啟用Tavily搜索 | false |
| `-WithPlaywright` | 啟用Playwright測試 | false |
| `-WithTerminal` | 啟動終端 | true |
| `-WithBackground` | 啟用Background Agent | false |

## 🎯 使用場景

### 場景1：同專案多任務開發
```powershell
.\quick-dual-cursor.ps1
# 選擇 "1. 同一專案"
```
**適用於：**
- 同時編輯前端和後端
- 一個窗口寫代碼，另一個看文檔
- 對比不同文件的內容

### 場景2：多專案並行開發
```powershell
.\dual-cursor-launcher.ps1 -Project1Path "C:\project-A" -Project2Path "C:\project-B"
```
**適用於：**
- 維護多個相關專案
- 參考其他專案的實作
- API開發與測試同步進行

### 場景3：開發與測試分離
```powershell
.\dual-cursor-mcp.ps1 -WithPlaywright
```
**適用於：**
- 一個實例開發，另一個測試
- 自動化測試開發
- UI/UX測試與調整

### 場景4：研究與開發
```powershell
.\dual-cursor-mcp.ps1 -WithTavily
```
**適用於：**
- 邊查資料邊開發
- 學習新技術
- 解決技術問題

## 🔧 MCP功能詳解

### Tavily網路搜索
在Cursor中使用：
```
@tavily search PHP 8.4 new features
@tavily search how to optimize database queries
@tavily search best practices for API design
```

### Playwright自動化測試
在Cursor中使用：
```
@playwright navigate http://localhost:8080
@playwright screenshot
@playwright click "button[type=submit]"
@playwright type "#username" "testuser"
```

### 文件系統操作
```
@filesystem list
@filesystem read file.php
@filesystem write newfile.js "console.log('hello');"
```

### Git操作
```
@git status
@git log --oneline -10
@git diff HEAD~1
```

## 💡 最佳實踐

### 1. 工作區組織
- **Cursor #1**：主要開發工作
- **Cursor #2**：測試、文檔、參考代碼

### 2. Git分支策略
```bash
# 在第一個實例中開發新功能
git checkout -b feature/new-api

# 在第二個實例中保持main分支用於測試
git checkout main
```

### 3. Background Agent協作
- 兩個實例可以共享相同的Background Agent會話
- 使用 `@background` 進行跨實例的AI協作
- 利用AI進行代碼重構和優化建議

### 4. 效能監控
- 使用一個實例監控應用效能
- 另一個實例進行實際開發
- 即時看到變更對效能的影響

## 🚨 注意事項

### 1. 資源使用
- 兩個Cursor實例會消耗更多記憶體
- 建議16GB以上記憶體
- 監控CPU使用率

### 2. 文件衝突
- 避免同時編輯相同文件
- 使用Git進行版本控制
- 定期同步變更

### 3. 授權限制
- 確保Cursor授權支援多實例
- Background Agent可能有使用限制
- 監控API配額使用

## 🔍 故障排除

### 問題：Cursor無法啟動第二個實例
**解決方案：**
```powershell
# 檢查Cursor安裝路徑
Get-Command cursor
# 或手動指定路徑
$cursorPath = "C:\Users\YourName\AppData\Local\Programs\Cursor\Cursor.exe"
```

### 問題：MCP伺服器無法啟動
**解決方案：**
```powershell
# 安裝必要的MCP套件
npm install -g @modelcontextprotocol/server-filesystem
npm install -g @tavily/tavily-mcp-server
npm install -g @playwright/mcp-server
```

### 問題：PHP伺服器無法啟動
**解決方案：**
```powershell
# 檢查PHP安裝
php --version
# 檢查端口是否被佔用
netstat -an | findstr :8080
```

## 📚 進階配置

### 自訂MCP配置
創建 `.cursor-mcp-config.json`：
```json
{
  "mcpServers": {
    "custom-tools": {
      "command": "node",
      "args": ["custom-mcp-server.js"],
      "env": {
        "API_KEY": "your-api-key"
      }
    }
  }
}
```

### Windows Terminal配置
創建專用的開發者設定檔：
```json
{
  "name": "雙Cursor開發",
  "commandline": "powershell.exe -NoExit -Command \"cd C:\\your-project\"",
  "startingDirectory": "C:\\your-project"
}
```

## 🎉 結語

雙Cursor開發環境可以大幅提升您的開發效率。結合MCP工具和Background Agent，您可以創建一個強大的AI輔助開發環境。

**開始使用：**
```powershell
.\quick-dual-cursor.ps1
```

祝您開發愉快！ 🚀 