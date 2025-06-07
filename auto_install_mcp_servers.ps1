# auto_install_mcp_servers.ps1
# 完整的 Cursor MCP Server 自動安裝腳本

param(
    [string]$InstallPath = "C:\Users\$env:USERNAME\MCP_Servers"
)

# 設定控制台輸出編碼
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "🚀 開始完整的 Cursor MCP Server 自動安裝程序..." -ForegroundColor Green
Write-Host "目標安裝目錄: $InstallPath" -ForegroundColor Cyan

# === 步驟 1: 檢查和安裝必要的系統組件 ===
Write-Host ""
Write-Host "=== 步驟 1: 檢查系統環境 ===" -ForegroundColor Magenta

# 檢查 PowerShell 執行策略
$executionPolicy = Get-ExecutionPolicy
if ($executionPolicy -eq "Restricted") {
    Write-Host "⚠️  需要修改 PowerShell 執行策略..." -ForegroundColor Yellow
    Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force
    Write-Host "✅ PowerShell 執行策略已設定為 RemoteSigned" -ForegroundColor Green
}

# 檢查 winget
Write-Host ""
Write-Host "檢查 winget (Windows Package Manager)..." -ForegroundColor Cyan
try {
    winget --version | Out-Null
    Write-Host "✅ winget 已安裝" -ForegroundColor Green
} catch {
    Write-Host "❌ winget 未安裝。請從 Microsoft Store 安裝 '應用程式安裝程式' 或手動下載。" -ForegroundColor Red
    Write-Host "繼續嘗試其他安裝方法..." -ForegroundColor Yellow
}

# 檢查和安裝 Node.js
Write-Host ""
Write-Host "檢查 Node.js..." -ForegroundColor Cyan
try {
    $nodeVersion = node --version 2>$null
    Write-Host "✅ Node.js 已安裝版本: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Node.js 未安裝，正在嘗試安裝..." -ForegroundColor Yellow
    try {
        winget install OpenJS.NodeJS --accept-source-agreements --accept-package-agreements
        Write-Host "✅ Node.js 安裝完成，請重啟 PowerShell 後重新運行此腳本。" -ForegroundColor Green
        Read-Host "按 Enter 鍵退出"
        exit
    } catch {
        Write-Host "❌ 無法透過 winget 安裝 Node.js。" -ForegroundColor Red
        Write-Host "請手動下載並安裝 Node.js: https://nodejs.org/" -ForegroundColor Red
        Read-Host "安裝完成後按 Enter 鍵繼續，或 Ctrl+C 退出"
    }
}

# 檢查和安裝 Git
Write-Host ""
Write-Host "檢查 Git..." -ForegroundColor Cyan
try {
    $gitVersion = git --version 2>$null
    Write-Host "✅ Git 已安裝版本: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Git 未安裝，正在嘗試安裝..." -ForegroundColor Yellow
    try {
        winget install Git.Git --accept-source-agreements --accept-package-agreements
        Write-Host "✅ Git 安裝完成" -ForegroundColor Green
        # 重新載入環境變數
        $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    } catch {
        Write-Host "❌ 無法透過 winget 安裝 Git。" -ForegroundColor Red
        Write-Host "請手動下載並安裝 Git: https://git-scm.com/" -ForegroundColor Red
        Read-Host "安裝完成後按 Enter 鍵繼續，或 Ctrl+C 退出"
    }
}

# 檢查和安裝 uv (Python 依賴管理工具)
Write-Host ""
Write-Host "檢查 uv..." -ForegroundColor Cyan
try {
    $uvVersion = uv --version 2>$null
    Write-Host "✅ uv 已安裝版本: $uvVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ uv 未安裝，正在嘗試安裝..." -ForegroundColor Yellow
    try {
        # 使用 PowerShell 安裝 uv
        Invoke-RestMethod https://astral.sh/uv/install.ps1 | Invoke-Expression
        Write-Host "✅ uv 安裝完成" -ForegroundColor Green
        # 重新載入環境變數
        $env:Path = "$env:USERPROFILE\.cargo\bin;" + $env:Path
    } catch {
        Write-Host "⚠️  uv 安裝失敗，但 Tavily MCP Server 需要它。" -ForegroundColor Yellow
        Write-Host "您可以稍後手動安裝: https://docs.astral.sh/uv/" -ForegroundColor Yellow
    }
}

# === 步驟 2: 創建安裝目錄 ===
Write-Host ""
Write-Host "=== 步驟 2: 創建安裝目錄 ===" -ForegroundColor Magenta
if (-not (Test-Path $InstallPath)) {
    New-Item -Path $InstallPath -ItemType Directory -Force | Out-Null
    Write-Host "✅ 已創建目錄: $InstallPath" -ForegroundColor Green
} else {
    Write-Host "✅ 目錄已存在: $InstallPath" -ForegroundColor Green
}

Set-Location -Path $InstallPath

# === 步驟 3: 安裝 MCP Server ===
Write-Host ""
Write-Host "=== 步驟 3: 安裝 MCP Server ===" -ForegroundColor Magenta

# 1. Apidog MCP Server (npx 運行，無需預安裝)
Write-Host ""
Write-Host "--- 1. Apidog MCP Server ---" -ForegroundColor Blue
Write-Host "✅ Apidog MCP Server 透過 npx 運行，無需預先安裝" -ForegroundColor Green
Write-Host "📝 請記住準備您的 Apidog Project ID 和 Access Token" -ForegroundColor Yellow

# 2. Magic MCP Server (npx 運行，無需預安裝)
Write-Host ""
Write-Host "--- 2. Magic MCP Server ---" -ForegroundColor Blue
Write-Host "✅ Magic MCP Server 透過 npx 運行，無需預先安裝" -ForegroundColor Green
Write-Host "📝 請記住準備您的 OpenAI API Key" -ForegroundColor Yellow

# 3. Opik MCP Server
Write-Host ""
Write-Host "--- 3. Opik MCP Server ---" -ForegroundColor Blue
$opikDir = Join-Path $InstallPath "opik-mcp"
if (-not (Test-Path $opikDir)) {
    Write-Host "正在克隆 Opik MCP Server..." -ForegroundColor Cyan
    try {
        git clone https://github.com/comet-ml/opik-mcp.git
        Set-Location -Path $opikDir
        Write-Host "正在安裝 Opik 依賴..." -ForegroundColor Cyan
        npm install
        Write-Host "正在建構 Opik 專案..." -ForegroundColor Cyan
        npm run build
        Set-Location -Path $InstallPath
        Write-Host "✅ Opik MCP Server 安裝完成" -ForegroundColor Green
    } catch {
        Write-Host "❌ Opik MCP Server 安裝失敗: $($_.Exception.Message)" -ForegroundColor Red
        Set-Location -Path $InstallPath
    }
} else {
    Write-Host "✅ Opik MCP Server 已存在，跳過安裝" -ForegroundColor Yellow
}
Write-Host "📁 Opik 路徑: $opikDir" -ForegroundColor Cyan
Write-Host "📝 請記住準備您的 Opik API Key" -ForegroundColor Yellow

# 4. Tavily MCP Server
Write-Host ""
Write-Host "--- 4. Tavily MCP Server ---" -ForegroundColor Blue
$tavilyDir = Join-Path $InstallPath "mcp-server-tavily"
if (-not (Test-Path $tavilyDir)) {
    Write-Host "正在克隆 Tavily MCP Server..." -ForegroundColor Cyan
    try {
        git clone https://github.com/tavilydotcom/python-mcp-server.git mcp-server-tavily
        Set-Location -Path $tavilyDir
        Write-Host "正在安裝 Tavily 依賴..." -ForegroundColor Cyan
        if (Get-Command uv -ErrorAction SilentlyContinue) {
            uv pip install -r requirements.txt
        } else {
            Write-Host "⚠️  uv 未找到，嘗試使用 pip..." -ForegroundColor Yellow
            python -m pip install -r requirements.txt
        }
        Set-Location -Path $InstallPath
        Write-Host "✅ Tavily MCP Server 安裝完成" -ForegroundColor Green
    } catch {
        Write-Host "❌ Tavily MCP Server 安裝失敗: $($_.Exception.Message)" -ForegroundColor Red
        Set-Location -Path $InstallPath
    }
} else {
    Write-Host "✅ Tavily MCP Server 已存在，跳過安裝" -ForegroundColor Yellow
}
Write-Host "📁 Tavily 路徑: $tavilyDir" -ForegroundColor Cyan
Write-Host "📝 請記住準備您的 Tavily API Key" -ForegroundColor Yellow

# 5. Playwright MCP Server
Write-Host ""
Write-Host "--- 5. Playwright MCP Server ---" -ForegroundColor Blue
$playwrightDir = Join-Path $InstallPath "playwright-mcp-server"
if (-not (Test-Path $playwrightDir)) {
    Write-Host "正在安裝 Playwright MCP Server..." -ForegroundColor Cyan
    try {
        New-Item -Path $playwrightDir -ItemType Directory -Force | Out-Null
        Set-Location -Path $playwrightDir
        npm install playwright-mcp
        Set-Location -Path $InstallPath
        Write-Host "✅ Playwright MCP Server 安裝完成" -ForegroundColor Green
    } catch {
        Write-Host "❌ Playwright MCP Server 安裝失敗: $($_.Exception.Message)" -ForegroundColor Red
        Set-Location -Path $InstallPath
    }
} else {
    Write-Host "✅ Playwright MCP Server 已存在，跳過安裝" -ForegroundColor Yellow
}
Write-Host "📁 Playwright 路徑: $playwrightDir" -ForegroundColor Cyan

# === 步驟 4: 總結和下一步 ===
Write-Host ""
Write-Host "=== 安裝完成總結 ===" -ForegroundColor Magenta
Write-Host "✅ 所有 MCP Server 安裝程序已完成！" -ForegroundColor Green

Write-Host ""
Write-Host "📋 已安裝的 MCP Server:" -ForegroundColor Cyan
Write-Host "1. ✅ Apidog MCP Server (透過 npx 運行)" -ForegroundColor White
Write-Host "2. ✅ Magic MCP Server (透過 npx 運行)" -ForegroundColor White
Write-Host "3. ✅ Opik MCP Server (位於: $opikDir)" -ForegroundColor White
Write-Host "4. ✅ Tavily MCP Server (位於: $tavilyDir)" -ForegroundColor White
Write-Host "5. ✅ Playwright MCP Server (位於: $playwrightDir)" -ForegroundColor White

Write-Host ""
Write-Host "📝 下一步操作:" -ForegroundColor Yellow
Write-Host "1. 準備以下 API Key:" -ForegroundColor White
Write-Host "   - Apidog Access Token 和 Project ID" -ForegroundColor Gray
Write-Host "   - OpenAI API Key" -ForegroundColor Gray
Write-Host "   - Opik API Key" -ForegroundColor Gray
Write-Host "   - Tavily API Key" -ForegroundColor Gray
Write-Host "2. 參考 'mcp_installation_guide.md' 配置 ~/.cursor/mcp.json" -ForegroundColor White
Write-Host "3. 重啟 Cursor 編輯器" -ForegroundColor White
Write-Host "4. 如需傳輸到桌機，請打包以下目錄:" -ForegroundColor White
Write-Host "   - $opikDir" -ForegroundColor Gray
Write-Host "   - $tavilyDir" -ForegroundColor Gray
Write-Host "   - $playwrightDir" -ForegroundColor Gray

Write-Host ""
Write-Host "🎉 安裝程序完成！" -ForegroundColor Green
Read-Host "按 Enter 鍵結束"
