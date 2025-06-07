# 🔧 MCP Server 筆電先行安裝測試指南

## 🎯 安裝策略：筆電測試 → 桌機部署

### 階段一：筆電環境測試 (當前階段)

#### 1. 📍 當前狀態檢查
```powershell
# 檢查已安裝的 MCP Server
Get-ChildItem "C:\Users\$env:USERNAME\MCP_Servers"

# 檢查 Node.js 環境
node --version
npm --version
```

#### 2. 🔧 配置 mcp.json
```powershell
# 手動操作步驟：
# 1. 開啟檔案總管到：C:\Users\user\.cursor\
# 2. 如果 .cursor 目錄不存在，手動創建
# 3. 創建新檔案：mcp.json
# 4. 複製 mcp_config_template.json 的內容到 mcp.json
# 5. 替換所有 <your-xxx-key> 為實際的 API Key
```

#### 3. ✅ 測試步驟

**Step 1: 基礎測試 (不需 API Key)**
```powershell
# Playwright MCP Server 測試 (無需額外 API Key)
# 重啟 Cursor → 開啟任意檔案 → 問 AI：
# "使用 Playwright 檢查 localhost:8080 的標題"
```

**Step 2: OpenAI 功能測試**
```json
// 在 mcp.json 中先配置 Magic MCP Server
"Magic AI Generator": {
  "command": "npx",
  "args": [
    "-y", 
    "@21st-dev/magic@latest", 
    "API_KEY=sk-proj-your-actual-openai-key"
  ]
}
```

**Step 3: 完整功能測試**
- 重啟 Cursor
- 檢查 Cursor 設定 → Features → MCP
- 測試 AI 助教是否能使用新功能

#### 4. 🐛 常見問題處理

**問題 1: .cursor 目錄不存在**
```powershell
# 手動創建目錄
New-Item -Path "C:\Users\$env:USERNAME\.cursor" -ItemType Directory -Force
```

**問題 2: MCP Server 無法連接**
- 檢查 JSON 格式是否正確
- 確認路徑使用雙反斜線 `\\`
- 重啟 Cursor 編輯器

**問題 3: API Key 錯誤**
- 先測試不需要額外 API Key 的 Playwright
- 逐步添加需要 API Key 的服務

---

### 階段二：桌機環境部署

#### 📦 打包準備 (筆電完成測試後)

**打包內容清單：**
```
📁 MCP_Package_for_Desktop/
├── 📁 MCP_Servers/
│   ├── 📁 opik-mcp/          # 完整的 Opik MCP Server
│   └── 📁 playwright-mcp-server/  # Playwright MCP Server
├── 📄 mcp.json              # 已配置的 mcp.json (移除敏感 API Key)
├── 📄 install_mcp_fixed.ps1 # 自動安裝腳本
├── 📄 mcp_installation_guide.md  # 完整安裝指南
└── 📄 desktop_setup_guide.md     # 桌機專用設定指南
```

#### 🚛 傳輸方式選項

**選項 1: USB 隨身碟**
```powershell
# 壓縮打包
Compress-Archive -Path "C:\Users\user\MCP_Servers" -DestinationPath "C:\Users\user\Desktop\MCP_Package.zip"
```

**選項 2: 雲端同步**
- OneDrive / Google Drive
- GitHub Private Repository (不包含 API Key)

**選項 3: 區域網路共享**
```powershell
# 筆電設定共享資料夾
# 桌機透過網路存取
```

#### 🖥️ 桌機安裝流程

```powershell
# 1. 解壓縮到桌機
# 2. 檢查 Node.js 環境
node --version

# 3. 複製 MCP_Servers 到桌機用戶目錄
Copy-Item -Path ".\MCP_Servers" -Destination "C:\Users\桌機用戶名\" -Recurse

# 4. 配置 mcp.json (調整路徑為桌機的用戶路徑)
# 5. 重啟桌機的 Cursor
```

---

### 🎯 推薦時程

**Day 1 (筆電)**：
- ✅ 已完成：MCP Server 安裝
- 🔄 進行中：配置 mcp.json 和測試

**Day 2 (筆電)**：
- 完整功能測試
- 問題排除和調優
- 打包準備

**Day 3 (桌機)**：
- 傳輸安裝包
- 桌機環境配置
- 功能驗證

---

### 🛡️ 安全注意事項

1. **API Key 保護**：
   - 打包時移除真實 API Key
   - 使用環境變數或配置檔案
   - 桌機單獨配置 API Key

2. **路徑適配**：
   - 筆電路徑：`C:\Users\user\`
   - 桌機路徑：`C:\Users\桌機用戶名\`
   - mcp.json 中的路徑需要調整

3. **版本管理**：
   - 記錄筆電測試的配置版本
   - 桌機部署時保持版本一致

---

### 📞 下一步行動

**立即行動 (筆電)**：
1. 配置 mcp.json 檔案
2. 測試 Playwright MCP Server (無需額外 API Key)
3. 逐步添加其他 MCP Server

**準備行動 (桌機)**：
1. 確認桌機有 Node.js 環境
2. 準備傳輸方式 (USB/雲端/網路)
3. 準備桌機的 API Key 配置 