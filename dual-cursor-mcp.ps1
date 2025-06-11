# Dual Cursor with MCP Integration
# 整合MCP的雙Cursor開發環境

param(
    [string]$Project1Path = ".",
    [string]$Project2Path = "",
    [string]$MCPServerPath = "",
    [switch]$WithTavily = $false,
    [switch]$WithPlaywright = $false
)

Write-Host "🚀 Dual Cursor + MCP Development Environment" -ForegroundColor Cyan
Write-Host "雙Cursor + MCP整合開發環境" -ForegroundColor Yellow
Write-Host ""

# MCP伺服器配置
function Setup-MCPServers {
    Write-Host "=== 設定 MCP 伺服器 ===" -ForegroundColor Yellow
    
    # 基本MCP配置
    $mcpConfig = @{
        mcpServers = @{}
    }
    
    # Tavily搜索支援
    if ($WithTavily) {
        Write-Host "設定Tavily MCP伺服器..." -ForegroundColor Cyan
        $mcpConfig.mcpServers["tavily"] = @{
            command = "npx"
            args    = @("-y", "@tavily/tavily-mcp-server")
            env     = @{
                TAVILY_API_KEY = $env:TAVILY_API_KEY
            }
        }
    }
    
    # Playwright測試支援
    if ($WithPlaywright) {
        Write-Host "設定Playwright MCP伺服器..." -ForegroundColor Cyan
        $mcpConfig.mcpServers["playwright"] = @{
            command = "npx"
            args    = @("-y", "@playwright/mcp-server")
        }
    }
    
    # 文件系統支援
    $mcpConfig.mcpServers["filesystem"] = @{
        command = "npx"
        args    = @("-y", "@modelcontextprotocol/server-filesystem", $Project1Path)
    }
    
    # Git支援
    if (Test-Path (Join-Path $Project1Path ".git")) {
        $mcpConfig.mcpServers["git"] = @{
            command = "npx"
            args    = @("-y", "@modelcontextprotocol/server-git", "--repository", $Project1Path)
        }
    }
    
    return $mcpConfig
}

# 啟動MCP伺服器
function Start-MCPServers {
    param($config)
    
    Write-Host "啟動MCP伺服器..." -ForegroundColor Green
    
    foreach ($serverName in $config.mcpServers.Keys) {
        $server = $config.mcpServers[$serverName]
        Write-Host "  啟動 $serverName MCP伺服器" -ForegroundColor Cyan
        
        try {
            $process = Start-Process -FilePath $server.command -ArgumentList $server.args -WindowStyle Hidden -PassThru
            Write-Host "  ✓ $serverName 伺服器已啟動 (PID: $($process.Id))" -ForegroundColor Green
        }
        catch {
            Write-Host "  ⚠️ $serverName 伺服器啟動失敗: $($_.Exception.Message)" -ForegroundColor Yellow
        }
    }
}

# 創建Cursor工作區配置
function Create-CursorWorkspace {
    param($projectPath, $workspaceName)
    
    $workspaceConfig = @{
        folders    = @(
            @{ path = $projectPath }
        )
        settings   = @{
            "mcp.enabled" = $true
            "mcp.servers" = $global:mcpConfig.mcpServers
        }
        extensions = @{
            recommendations = @(
                "ms-vscode.vscode-typescript-next",
                "bradlc.vscode-tailwindcss",
                "ms-playwright.playwright"
            )
        }
    }
    
    $workspaceFile = Join-Path $projectPath "$workspaceName.code-workspace"
    $workspaceConfig | ConvertTo-Json -Depth 10 | Set-Content -Path $workspaceFile -Encoding UTF8
    
    return $workspaceFile
}

# 主要執行流程
Write-Host "檢測Cursor安裝..." -ForegroundColor Yellow
$cursorPath = "$env:LOCALAPPDATA\Programs\Cursor\Cursor.exe"
if (-not (Test-Path $cursorPath)) {
    Write-Host "ERROR: 找不到Cursor" -ForegroundColor Red
    exit 1
}

# 設定專案路徑
if ($Project2Path -eq "") {
    $Project2Path = $Project1Path
}

Write-Host "專案1路徑: $Project1Path" -ForegroundColor Cyan
Write-Host "專案2路徑: $Project2Path" -ForegroundColor Cyan

# 設定MCP
if ($WithTavily -or $WithPlaywright) {
    $global:mcpConfig = Setup-MCPServers
    Start-MCPServers -config $global:mcpConfig
    
    # 創建工作區文件
    $workspace1 = Create-CursorWorkspace -projectPath $Project1Path -workspaceName "dev-workspace-1"
    $workspace2 = Create-CursorWorkspace -projectPath $Project2Path -workspaceName "dev-workspace-2"
    
    Write-Host "✓ MCP工作區配置已創建" -ForegroundColor Green
}

# 啟動Cursor實例
Write-Host ""
Write-Host "啟動Cursor實例..." -ForegroundColor Green

try {
    # 啟動第一個Cursor
    if ($WithTavily -or $WithPlaywright) {
        Start-Process -FilePath $cursorPath -ArgumentList $workspace1 -WindowStyle Normal
        Write-Host "✓ Cursor #1 (帶MCP): $Project1Path" -ForegroundColor Green
    }
    else {
        Start-Process -FilePath $cursorPath -ArgumentList $Project1Path -WindowStyle Normal
        Write-Host "✓ Cursor #1: $Project1Path" -ForegroundColor Green
    }
    
    Start-Sleep -Seconds 3
    
    # 啟動第二個Cursor
    if ($WithTavily -or $WithPlaywright) {
        Start-Process -FilePath $cursorPath -ArgumentList $workspace2 -WindowStyle Normal
        Write-Host "✓ Cursor #2 (帶MCP): $Project2Path" -ForegroundColor Green
    }
    else {
        Start-Process -FilePath $cursorPath -ArgumentList $Project2Path -WindowStyle Normal
        Write-Host "✓ Cursor #2: $Project2Path" -ForegroundColor Green
    }
    
    # 啟動開發伺服器
    if (Test-Path (Join-Path $Project1Path "public")) {
        Write-Host "啟動PHP開發伺服器..." -ForegroundColor Yellow
        Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8080", "-t", "public" -WorkingDirectory $Project1Path -WindowStyle Normal
        Write-Host "✓ PHP Server: http://localhost:8080" -ForegroundColor Green
    }
    
    Write-Host ""
    Write-Host "🎉 雙Cursor開發環境已就緒！" -ForegroundColor Cyan
    
    if ($WithTavily) {
        Write-Host ""
        Write-Host "=== Tavily MCP 使用方法 ===" -ForegroundColor Yellow
        Write-Host "• 在Cursor中使用 '@tavily search [查詢]' 進行網路搜索" -ForegroundColor White
        Write-Host "• 例如: '@tavily search latest PHP 8.4 features'" -ForegroundColor Gray
    }
    
    if ($WithPlaywright) {
        Write-Host ""
        Write-Host "=== Playwright MCP 使用方法 ===" -ForegroundColor Yellow
        Write-Host "• 使用 '@playwright navigate [URL]' 進行網頁測試" -ForegroundColor White
        Write-Host "• 使用 '@playwright screenshot' 截圖" -ForegroundColor White
        Write-Host "• 使用 '@playwright click [element]' 點擊元素" -ForegroundColor White
    }
    
}
catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== 開發流程建議 ===" -ForegroundColor Yellow
Write-Host "1. 在Cursor #1中進行主要開發工作" -ForegroundColor White
Write-Host "2. 在Cursor #2中進行測試和調試" -ForegroundColor White
Write-Host "3. 使用MCP工具進行網路搜索和自動化測試" -ForegroundColor White
Write-Host "4. 兩個實例可以共享相同的Background Agent會話" -ForegroundColor White 