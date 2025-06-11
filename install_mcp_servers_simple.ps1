# MCP 服務器簡化安裝腳本
# 支援：Playwright、Tavily、Fetch、Filesystem
# 編碼：UTF-8
# 日期：2025-01-28

Write-Host "🚀 開始安裝 MCP 服務器..." -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan

# 檢查 Node.js 安裝
Write-Host "🔍 檢查 Node.js 版本..." -ForegroundColor Yellow
try {
    $nodeVersion = node --version
    Write-Host "✅ Node.js 版本: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ 未找到 Node.js，請先安裝 Node.js" -ForegroundColor Red
    exit 1
}

# 檢查 npm 版本
Write-Host "🔍 檢查 npm 版本..." -ForegroundColor Yellow
try {
    $npmVersion = npm --version
    Write-Host "✅ npm 版本: $npmVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ 未找到 npm" -ForegroundColor Red
    exit 1
}

# 創建 MCP 工作目錄
$mcpDir = "mcp-servers"
Write-Host "📁 創建目錄: $mcpDir" -ForegroundColor Yellow
if (-not (Test-Path $mcpDir)) {
    New-Item -ItemType Directory -Path $mcpDir -Force | Out-Null
    Write-Host "✅ 目錄已創建" -ForegroundColor Green
} else {
    Write-Host "✅ 目錄已存在" -ForegroundColor Green
}

Set-Location $mcpDir

# 初始化 package.json
Write-Host "📦 初始化 package.json..." -ForegroundColor Yellow
if (-not (Test-Path "package.json")) {
    npm init -y | Out-Null
    Write-Host "✅ package.json 已創建" -ForegroundColor Green
} else {
    Write-Host "✅ package.json 已存在" -ForegroundColor Green
}

# 安裝 Playwright MCP
Write-Host ""
Write-Host "🎭 安裝 Playwright MCP 服務器..." -ForegroundColor Cyan
try {
    Write-Host "正在安裝 @playwright/mcp..." -ForegroundColor Yellow
    npm install @playwright/mcp
    Write-Host "✅ Playwright MCP 安裝完成" -ForegroundColor Green
} catch {
    Write-Host "❌ Playwright MCP 安裝失敗: $_" -ForegroundColor Red
}

# 安裝 Tavily MCP
Write-Host ""
Write-Host "🔍 安裝 Tavily MCP..." -ForegroundColor Cyan
try {
    Write-Host "正在安裝 @tavily/mcp..." -ForegroundColor Yellow
    npm install @tavily/mcp
    Write-Host "✅ Tavily MCP 安裝完成" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Tavily MCP 安裝失敗，請確認包名稱" -ForegroundColor Yellow
}

# 安裝其他 MCP 服務器
Write-Host ""
Write-Host "🖥️ 安裝額外的 MCP 服務器..." -ForegroundColor Cyan

# Fetch MCP
try {
    Write-Host "正在安裝 fetch MCP 服務器..." -ForegroundColor Yellow
    npm install @modelcontextprotocol/server-fetch
    Write-Host "✅ Fetch MCP 安裝完成" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Fetch MCP 安裝失敗" -ForegroundColor Yellow
}

# Filesystem MCP
try {
    Write-Host "正在安裝 filesystem MCP 服務器..." -ForegroundColor Yellow
    npm install @modelcontextprotocol/server-filesystem
    Write-Host "✅ Filesystem MCP 安裝完成" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Filesystem MCP 安裝失敗" -ForegroundColor Yellow
}

# 創建配置文件
Write-Host ""
Write-Host "⚙️ 創建配置文件..." -ForegroundColor Cyan

# 創建 Claude Desktop 配置
$claudeConfig = '{
  "mcpServers": {
    "playwright": {
      "command": "npx",
      "args": ["@playwright/mcp@latest"]
    },
    "tavily": {
      "command": "npx", 
      "args": ["@tavily/mcp@latest"],
      "env": {
        "TAVILY_API_KEY": "your_tavily_api_key_here"
      }
    },
    "fetch": {
      "command": "npx",
      "args": ["@modelcontextprotocol/server-fetch"]
    },
    "filesystem": {
      "command": "npx",
      "args": ["@modelcontextprotocol/server-filesystem", "--allowed-directories", "."]
    }
  }
}'

$claudeConfig | Out-File -FilePath "claude_desktop_config.json" -Encoding UTF8
Write-Host "✅ Claude Desktop 配置文件已創建" -ForegroundColor Green

# 更新 package.json 腳本
Write-Host "📝 更新 package.json 腳本..." -ForegroundColor Yellow
$packageJson = Get-Content "package.json" | ConvertFrom-Json
$packageJson.scripts = @{
    "mcp:playwright" = "npx @playwright/mcp --port 8931"
    "mcp:tavily" = "npx @tavily/mcp --port 8932"
    "mcp:fetch" = "npx @modelcontextprotocol/server-fetch --port 8933"
    "mcp:filesystem" = "npx @modelcontextprotocol/server-filesystem --allowed-directories . --port 8934"
}
$packageJson | ConvertTo-Json -Depth 10 | Out-File -FilePath "package.json" -Encoding UTF8
Write-Host "✅ package.json 腳本已更新" -ForegroundColor Green

# 創建簡單的啟動腳本
Write-Host ""
Write-Host "📜 創建啟動腳本..." -ForegroundColor Cyan

# 創建 Windows 批次文件
$batContent = '@echo off
echo 🚀 MCP 服務器啟動工具
echo ========================
echo.
echo 可用服務器:
echo 1. Playwright (Port 8931)
echo 2. Tavily (Port 8932)  
echo 3. Fetch (Port 8933)
echo 4. Filesystem (Port 8934)
echo.
set /p choice="選擇服務器 (1-4): "
if "%choice%"=="1" npx @playwright/mcp --port 8931
if "%choice%"=="2" npx @tavily/mcp --port 8932
if "%choice%"=="3" npx @modelcontextprotocol/server-fetch --port 8933
if "%choice%"=="4" npx @modelcontextprotocol/server-filesystem --allowed-directories . --port 8934
pause'

$batContent | Out-File -FilePath "start-mcp.bat" -Encoding Default
Write-Host "✅ Windows 啟動腳本已創建" -ForegroundColor Green

# 顯示完成訊息
Write-Host ""
Write-Host "🎉 MCP 服務器安裝完成！" -ForegroundColor Green
Write-Host "==================================" -ForegroundColor Cyan

Write-Host ""
Write-Host "📋 已安裝的服務:" -ForegroundColor Yellow
Write-Host "  🎭 Playwright MCP - 瀏覽器自動化" -ForegroundColor White
Write-Host "  🔍 Tavily MCP - 網路搜索" -ForegroundColor White
Write-Host "  🌐 Fetch MCP - 網頁抓取" -ForegroundColor White
Write-Host "  📁 Filesystem MCP - 檔案系統" -ForegroundColor White

Write-Host ""
Write-Host "🚀 使用方法:" -ForegroundColor Yellow
Write-Host "  npm run mcp:playwright    - 啟動 Playwright (Port 8931)" -ForegroundColor White
Write-Host "  npm run mcp:tavily        - 啟動 Tavily (Port 8932)" -ForegroundColor White
Write-Host "  npm run mcp:fetch         - 啟動 Fetch (Port 8933)" -ForegroundColor White
Write-Host "  npm run mcp:filesystem    - 啟動 Filesystem (Port 8934)" -ForegroundColor White
Write-Host "  ./start-mcp.bat           - Windows 互動式啟動" -ForegroundColor White

Write-Host ""
Write-Host "📁 配置文件:" -ForegroundColor Yellow
Write-Host "  Claude Desktop: ./claude_desktop_config.json" -ForegroundColor White

Write-Host ""
Write-Host "⚙️ 配置 Claude Desktop:" -ForegroundColor Yellow
Write-Host "  1. 複製 claude_desktop_config.json 到:" -ForegroundColor White
Write-Host "     Windows: %APPDATA%\Claude\claude_desktop_config.json" -ForegroundColor White
Write-Host "     macOS: ~/Library/Application Support/Claude/" -ForegroundColor White
Write-Host "  2. 設定您的 Tavily API 密鑰" -ForegroundColor White

Write-Host ""
Write-Host "📖 資源連結:" -ForegroundColor Yellow
Write-Host "  Playwright: https://github.com/microsoft/playwright-mcp" -ForegroundColor White
Write-Host "  Tavily: https://docs.tavily.com/documentation/mcp" -ForegroundColor White
Write-Host "  MCP 官網: https://modelcontextprotocol.io/" -ForegroundColor White

Write-Host ""
Write-Host "✅ 安裝完成！開始使用 MCP 服務器吧！" -ForegroundColor Green

# 回到原目錄
Set-Location .. 