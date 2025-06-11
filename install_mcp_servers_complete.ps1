# MCP 服務器完整安裝腳本
# 支援：Playwright、Browser Console、Browser Navigator、Tavily
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

# 安裝 Playwright MCP (官方 Microsoft 包)
Write-Host ""
Write-Host "🎭 安裝 Playwright MCP 服務器..." -ForegroundColor Cyan
try {
    Write-Host "正在安裝 @playwright/mcp..." -ForegroundColor Yellow
    npm install @playwright/mcp
    
    Write-Host "✅ Playwright MCP 安裝完成" -ForegroundColor Green
} catch {
    Write-Host "❌ Playwright MCP 安裝失敗: $_" -ForegroundColor Red
}

# 安裝 Tavily MCP (官方 Tavily 包)
Write-Host ""
Write-Host "🔍 安裝 Tavily MCP..." -ForegroundColor Cyan
try {
    Write-Host "正在安裝 @tavily/mcp..." -ForegroundColor Yellow
    npm install @tavily/mcp
    Write-Host "✅ Tavily MCP 安裝完成" -ForegroundColor Green
} catch {
    Write-Host "⚠️ 嘗試替代的 Tavily 包..." -ForegroundColor Yellow
    try {
        npm install tavily-mcp
        Write-Host "✅ Tavily MCP (替代版) 安裝完成" -ForegroundColor Green
    } catch {
        Write-Host "❌ Tavily MCP 安裝失敗，請手動檢查包名稱" -ForegroundColor Red
    }
}

# 安裝其他 MCP 服務器 (基於社區推薦)
Write-Host ""
Write-Host "🖥️ 安裝額外的 MCP 服務器..." -ForegroundColor Cyan

# 安裝 Fetch MCP (官方 Reference Server)
try {
    Write-Host "正在安裝 fetch MCP 服務器..." -ForegroundColor Yellow
    npm install @modelcontextprotocol/server-fetch
    Write-Host "✅ Fetch MCP 安裝完成" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Fetch MCP 安裝失敗" -ForegroundColor Yellow
}

# 安裝 Filesystem MCP (官方 Reference Server)
try {
    Write-Host "正在安裝 filesystem MCP 服務器..." -ForegroundColor Yellow
    npm install @modelcontextprotocol/server-filesystem
    Write-Host "✅ Filesystem MCP 安裝完成" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Filesystem MCP 安裝失敗" -ForegroundColor Yellow
}

# 創建 Playwright 配置文件
Write-Host ""
Write-Host "⚙️ 創建 Playwright 配置..." -ForegroundColor Cyan

# 使用 here-string 正確處理 JavaScript 內容
@'
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 30000,
  expect: {
    timeout: 5000
  },
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    ['html'],
    ['json', { outputFile: 'test-results/results.json' }]
  ],
  use: {
    baseURL: 'http://localhost:8080',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure'
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
  ],
  webServer: {
    command: 'php -S localhost:8080 -t ../public',
    port: 8080,
    reuseExistingServer: !process.env.CI,
  },
});
'@ | Out-File -FilePath "playwright.config.js" -Encoding UTF8
Write-Host "✅ Playwright 配置文件已創建" -ForegroundColor Green

# 創建測試目錄
Write-Host "📁 創建測試目錄..." -ForegroundColor Yellow
if (-not (Test-Path "tests")) {
    New-Item -ItemType Directory -Path "tests" -Force | Out-Null
}

# 創建示例測試文件
@'
import { test, expect } from '@playwright/test';

test.describe('PythonLearn 平台測試', () => {
  test('首頁載入測試', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/Python/);
    
    // 檢查登入表單
    await expect(page.locator('#username')).toBeVisible();
    await expect(page.locator('#room')).toBeVisible();
  });

  test('登入流程測試', async ({ page }) => {
    await page.goto('/');
    
    // 填寫登入表單
    await page.fill('#username', 'test_user');
    await page.fill('#room', 'test_room');
    await page.click('button[type="submit"]');
    
    // 等待頁面跳轉或狀態變化
    await page.waitForTimeout(2000);
    
    // 檢查是否成功進入工作區域
    await expect(page.locator('#editor')).toBeVisible();
  });

  test('控制台訊息監聽', async ({ page }) => {
    const messages = [];
    
    page.on('console', (msg) => {
      messages.push({
        type: msg.type(),
        text: msg.text(),
        location: msg.location()
      });
    });
    
    await page.goto('/');
    await page.waitForTimeout(3000);
    
    console.log('控制台訊息:', messages);
    expect(messages.length).toBeGreaterThan(0);
  });
});
'@ | Out-File -FilePath "tests/pythonlearn.spec.js" -Encoding UTF8
Write-Host "✅ 示例測試文件已創建" -ForegroundColor Green

# 更新 package.json 腳本
Write-Host "📝 更新 package.json 腳本..." -ForegroundColor Yellow
$packageJson = Get-Content "package.json" | ConvertFrom-Json
$packageJson.scripts = @{
    "test" = "playwright test"
    "test:headed" = "playwright test --headed"
    "test:ui" = "playwright test --ui"
    "test:debug" = "playwright test --debug"
    "test:report" = "playwright show-report"
    "install:browsers" = "playwright install"
    "mcp:playwright" = "npx @playwright/mcp --port 8931"
    "mcp:tavily" = "npx @tavily/mcp"
}
$packageJson | ConvertTo-Json -Depth 10 | Out-File -FilePath "package.json" -Encoding UTF8
Write-Host "✅ package.json 腳本已更新" -ForegroundColor Green

# 創建 MCP 配置文件 (適用於 Claude Desktop 和其他 MCP 客戶端)
Write-Host ""
Write-Host "⚙️ 創建 MCP 配置文件..." -ForegroundColor Cyan
$mcpConfig = @{
    "mcpServers" = @{
        "playwright" = @{
            "command" = "npx"
            "args" = @("@playwright/mcp")
            "env" = @{
                "NODE_ENV" = "production"
            }
        }
        "tavily" = @{
            "command" = "npx"
            "args" = @("@tavily/mcp")
            "env" = @{
                "TAVILY_API_KEY" = "your_tavily_api_key_here"
            }
        }
        "fetch" = @{
            "command" = "npx"
            "args" = @("@modelcontextprotocol/server-fetch")
        }
        "filesystem" = @{
            "command" = "npx"
            "args" = @("@modelcontextprotocol/server-filesystem", "--allowed-directories", "../")
        }
    }
}

$mcpConfig | ConvertTo-Json -Depth 10 | Out-File -FilePath "mcp-config.json" -Encoding UTF8
Write-Host "✅ MCP 配置文件已創建" -ForegroundColor Green

# 創建 Claude Desktop 配置示例
Write-Host ""
Write-Host "📋 創建 Claude Desktop 配置示例..." -ForegroundColor Cyan
@'
{
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
      "args": ["@modelcontextprotocol/server-filesystem", "--allowed-directories", "$(pwd)"]
    }
  }
}
'@ | Out-File -FilePath "claude_desktop_config.json" -Encoding UTF8
Write-Host "✅ Claude Desktop 配置文件已創建" -ForegroundColor Green

# 創建啟動腳本
Write-Host ""
Write-Host "📜 創建啟動腳本..." -ForegroundColor Cyan
@'
@echo off
echo 🚀 啟動 MCP 服務器...
echo.

echo 📋 可用的 MCP 服務器:
echo   1. Playwright (瀏覽器自動化) - Port 8931
echo   2. Tavily (網路搜索) - Port 8932  
echo   3. Fetch (網頁抓取) - Port 8933
echo   4. Filesystem (檔案系統) - Port 8934
echo.

set /p choice="請選擇要啟動的服務器 (1-4): "

if "%choice%"=="1" (
    echo 🎭 啟動 Playwright MCP 服務器...
    npx @playwright/mcp --port 8931
) else if "%choice%"=="2" (
    echo 🔍 啟動 Tavily MCP 服務器...
    set /p api_key="請輸入您的 Tavily API Key: "
    set TAVILY_API_KEY=%api_key%
    npx @tavily/mcp --port 8932
) else if "%choice%"=="3" (
    echo 🌐 啟動 Fetch MCP 服務器...
    npx @modelcontextprotocol/server-fetch --port 8933
) else if "%choice%"=="4" (
    echo 📁 啟動 Filesystem MCP 服務器...
    npx @modelcontextprotocol/server-filesystem --allowed-directories ../ --port 8934
) else (
    echo ❌ 無效選擇，請重新執行腳本
    pause
    exit /b 1
)

pause
'@ | Out-File -FilePath "start-mcp-server.bat" -Encoding Default
Write-Host "✅ 啟動腳本已創建" -ForegroundColor Green

# 創建 PowerShell 啟動腳本
@'
# MCP 服務器啟動腳本
Write-Host "🚀 MCP 服務器啟動工具" -ForegroundColor Cyan
Write-Host "========================" -ForegroundColor Cyan
Write-Host ""

Write-Host "📋 可用的 MCP 服務器:" -ForegroundColor Yellow
Write-Host "  1. Playwright (瀏覽器自動化) - Port 8931" -ForegroundColor White
Write-Host "  2. Tavily (網路搜索) - Port 8932" -ForegroundColor White
Write-Host "  3. Fetch (網頁抓取) - Port 8933" -ForegroundColor White
Write-Host "  4. Filesystem (檔案系統) - Port 8934" -ForegroundColor White
Write-Host "  5. 全部啟動" -ForegroundColor White
Write-Host ""

$choice = Read-Host "請選擇要啟動的服務器 (1-5)"

switch ($choice) {
    "1" {
        Write-Host "🎭 啟動 Playwright MCP 服務器..." -ForegroundColor Green
        npx @playwright/mcp --port 8931
    }
    "2" {
        Write-Host "🔍 啟動 Tavily MCP 服務器..." -ForegroundColor Green
        $apiKey = Read-Host "請輸入您的 Tavily API Key"
        $env:TAVILY_API_KEY = $apiKey
        npx @tavily/mcp --port 8932
    }
    "3" {
        Write-Host "🌐 啟動 Fetch MCP 服務器..." -ForegroundColor Green
        npx @modelcontextprotocol/server-fetch --port 8933
    }
    "4" {
        Write-Host "📁 啟動 Filesystem MCP 服務器..." -ForegroundColor Green
        npx @modelcontextprotocol/server-filesystem --allowed-directories ../ --port 8934
    }
    "5" {
        Write-Host "🚀 啟動所有 MCP 服務器..." -ForegroundColor Green
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "npx @playwright/mcp --port 8931"
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "npx @modelcontextprotocol/server-fetch --port 8933"
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "npx @modelcontextprotocol/server-filesystem --allowed-directories ../ --port 8934"
        Write-Host "✅ 所有服務器已在背景啟動" -ForegroundColor Green
    }
    default {
        Write-Host "❌ 無效選擇" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "按任意鍵繼續..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
'@ | Out-File -FilePath "start-mcp-server.ps1" -Encoding UTF8
Write-Host "✅ PowerShell 啟動腳本已創建" -ForegroundColor Green

# 顯示安裝完成訊息
Write-Host ""
Write-Host "🎉 MCP 服務器安裝完成！" -ForegroundColor Green
Write-Host "==================================" -ForegroundColor Cyan

Write-Host ""
Write-Host "📋 已安裝的服務:" -ForegroundColor Yellow
Write-Host "  🎭 Playwright MCP - 瀏覽器自動化測試 (@playwright/mcp)" -ForegroundColor White
Write-Host "  🔍 Tavily MCP - 網路搜索服務 (@tavily/mcp)" -ForegroundColor White
Write-Host "  🌐 Fetch MCP - 網頁內容抓取 (@modelcontextprotocol/server-fetch)" -ForegroundColor White
Write-Host "  📁 Filesystem MCP - 檔案系統操作 (@modelcontextprotocol/server-filesystem)" -ForegroundColor White

Write-Host ""
Write-Host "🚀 使用指令:" -ForegroundColor Yellow
Write-Host "  npm run mcp:playwright    - 啟動 Playwright MCP 服務器" -ForegroundColor White
Write-Host "  npm run mcp:tavily        - 啟動 Tavily MCP 服務器" -ForegroundColor White
Write-Host "  ./start-mcp-server.bat    - Windows 批次啟動腳本" -ForegroundColor White
Write-Host "  ./start-mcp-server.ps1    - PowerShell 啟動腳本" -ForegroundColor White

Write-Host ""
Write-Host "📁 檔案位置:" -ForegroundColor Yellow
Write-Host "  MCP 配置: ./mcp-config.json" -ForegroundColor White
Write-Host "  Claude Desktop 配置: ./claude_desktop_config.json" -ForegroundColor White
Write-Host "  測試檔案: ./tests/" -ForegroundColor White
Write-Host "  Playwright 配置: ./playwright.config.js" -ForegroundColor White

Write-Host ""
Write-Host "⚙️ 配置說明:" -ForegroundColor Yellow
Write-Host "  1. 將 claude_desktop_config.json 複製到 Claude Desktop 配置目錄" -ForegroundColor White
Write-Host "  2. 在 Windows: %APPDATA%\Claude\claude_desktop_config.json" -ForegroundColor White
Write-Host "  3. 在 macOS: ~/Library/Application Support/Claude/claude_desktop_config.json" -ForegroundColor White
Write-Host "  4. 設定您的 Tavily API 密鑰 (從 https://app.tavily.com 獲取)" -ForegroundColor White

Write-Host ""
Write-Host "⚠️ 注意事項:" -ForegroundColor Yellow
Write-Host "  1. Tavily 需要 API 密鑰，請在配置文件中設定" -ForegroundColor White
Write-Host "  2. 執行測試前請確保專案服務器已啟動" -ForegroundColor White
Write-Host "  3. Playwright 首次使用時會自動下載瀏覽器" -ForegroundColor White
Write-Host "  4. 確保防火牆允許相關端口 (8931-8934)" -ForegroundColor White

Write-Host ""
Write-Host "📖 更多資源:" -ForegroundColor Yellow
Write-Host "  Playwright MCP: https://github.com/microsoft/playwright-mcp" -ForegroundColor White
Write-Host "  Tavily MCP: https://docs.tavily.com/documentation/mcp" -ForegroundColor White
Write-Host "  MCP 規範: https://modelcontextprotocol.io/" -ForegroundColor White

Write-Host ""
Write-Host "✅ 安裝完成！您現在可以使用這些 MCP 服務進行開發測試。" -ForegroundColor Green

# 回到原目錄
Set-Location .. 