# install_mcp_simple.ps1
# 簡化的 Cursor MCP Server 自動安裝腳本

param(
    [string]$InstallPath = "C:\Users\$env:USERNAME\MCP_Servers"
)

Write-Host "🚀 開始 Cursor MCP Server 安裝程序..." -ForegroundColor Green
Write-Host "目標安裝目錄: $InstallPath" -ForegroundColor Cyan

# 檢查 Node.js
Write-Host ""
Write-Host "檢查 Node.js..." -ForegroundColor Cyan
$nodeInstalled = $false
try {
    $nodeVersion = node --version 2>$null
    if ($nodeVersion) {
        Write-Host "✅ Node.js 已安裝版本: $nodeVersion" -ForegroundColor Green
        $nodeInstalled = $true
    }
} catch {
    # 處理錯誤
}

if (-not $nodeInstalled) {
    Write-Host "❌ Node.js 未安裝" -ForegroundColor Red
    Write-Host "請先安裝 Node.js: https://nodejs.org/" -ForegroundColor Yellow
    Read-Host "請手動安裝 Node.js 後按 Enter 繼續，或 Ctrl+C 退出"
}

# 檢查 Git
Write-Host ""
Write-Host "檢查 Git..." -ForegroundColor Cyan
$gitInstalled = $false
try {
    $gitVersion = git --version 2>$null
    if ($gitVersion) {
        Write-Host "✅ Git 已安裝版本: $gitVersion" -ForegroundColor Green
        $gitInstalled = $true
    }
} catch {
    # 處理錯誤
}

if (-not $gitInstalled) {
    Write-Host "❌ Git 未安裝" -ForegroundColor Red
    Write-Host "請先安裝 Git: https://git-scm.com/" -ForegroundColor Yellow
    Read-Host "請手動安裝 Git 後按 Enter 繼續，或 Ctrl+C 退出"
}

# 創建安裝目錄
Write-Host ""
Write-Host "=== 創建安裝目錄 ===" -ForegroundColor Magenta
if (-not (Test-Path $InstallPath)) {
    New-Item -Path $InstallPath -ItemType Directory -Force | Out-Null
    Write-Host "✅ 已創建目錄: $InstallPath" -ForegroundColor Green
} else {
    Write-Host "✅ 目錄已存在: $InstallPath" -ForegroundColor Green
}

Set-Location -Path $InstallPath

# 安裝 Opik MCP Server
Write-Host ""
Write-Host "--- 安裝 Opik MCP Server ---" -ForegroundColor Blue
$opikDir = Join-Path $InstallPath "opik-mcp"
if (-not (Test-Path $opikDir)) {
    Write-Host "正在克隆 Opik MCP Server..." -ForegroundColor Cyan
    git clone https://github.com/comet-ml/opik-mcp.git
    if (Test-Path $opikDir) {
        Set-Location -Path $opikDir
        Write-Host "正在安裝 Opik 依賴..." -ForegroundColor Cyan
        npm install
        Write-Host "正在建構 Opik 專案..." -ForegroundColor Cyan
        npm run build
        Set-Location -Path $InstallPath
        Write-Host "✅ Opik MCP Server 安裝完成" -ForegroundColor Green
    } else {
        Write-Host "❌ Opik MCP Server 克隆失敗" -ForegroundColor Red
    }
} else {
    Write-Host "✅ Opik MCP Server 已存在，跳過安裝" -ForegroundColor Yellow
}

# 安裝 Playwright MCP Server
Write-Host ""
Write-Host "--- 安裝 Playwright MCP Server ---" -ForegroundColor Blue
$playwrightDir = Join-Path $InstallPath "playwright-mcp-server"
if (-not (Test-Path $playwrightDir)) {
    Write-Host "正在安裝 Playwright MCP Server..." -ForegroundColor Cyan
    New-Item -Path $playwrightDir -ItemType Directory -Force | Out-Null
    Set-Location -Path $playwrightDir
    npm install playwright-mcp
    Set-Location -Path $InstallPath
    Write-Host "✅ Playwright MCP Server 安裝完成" -ForegroundColor Green
} else {
    Write-Host "✅ Playwright MCP Server 已存在，跳過安裝" -ForegroundColor Yellow
}

# 總結
Write-Host ""
Write-Host "=== 安裝完成總結 ===" -ForegroundColor Magenta
Write-Host "✅ MCP Server 安裝程序已完成！" -ForegroundColor Green

Write-Host ""
Write-Host "📋 已安裝的 MCP Server:" -ForegroundColor Cyan
Write-Host "1. ✅ Apidog MCP Server (透過 npx 運行，無需預安裝)" -ForegroundColor White
Write-Host "2. ✅ Magic MCP Server (透過 npx 運行，無需預安裝)" -ForegroundColor White
Write-Host "3. ✅ Opik MCP Server (位於: $opikDir)" -ForegroundColor White
Write-Host "4. ✅ Playwright MCP Server (位於: $playwrightDir)" -ForegroundColor White

Write-Host ""
Write-Host "📝 下一步操作:" -ForegroundColor Yellow
Write-Host "1. 準備以下 API Key:" -ForegroundColor White
Write-Host "   - Apidog Access Token 和 Project ID" -ForegroundColor Gray
Write-Host "   - OpenAI API Key" -ForegroundColor Gray
Write-Host "   - Opik API Key" -ForegroundColor Gray
Write-Host "2. 參考 'mcp_installation_guide.md' 配置 ~/.cursor/mcp.json" -ForegroundColor White
Write-Host "3. 重啟 Cursor 編輯器" -ForegroundColor White

Write-Host ""
Write-Host "🎉 安裝程序完成！" -ForegroundColor Green
Read-Host "按 Enter 鍵結束" 