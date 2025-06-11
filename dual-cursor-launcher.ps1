# Dual Cursor Development Environment Launcher
# 同時開啟兩個Cursor進行軟體開發

param(
    [string]$Project1Path = ".",
    [string]$Project2Path = "",
    [switch]$WithMCP = $false,
    [switch]$WithTerminal = $true,
    [switch]$WithBackground = $false
)

Write-Host "=== Dual Cursor Development Environment ===" -ForegroundColor Cyan
Write-Host "啟動雙Cursor開發環境" -ForegroundColor Yellow
Write-Host ""

# 檢查Cursor是否安裝
function Test-CursorInstallation {
    $cursorPaths = @(
        "$env:LOCALAPPDATA\Programs\Cursor\Cursor.exe",
        "$env:PROGRAMFILES\Cursor\Cursor.exe",
        "C:\Users\$env:USERNAME\AppData\Local\Programs\Cursor\Cursor.exe"
    )
    
    foreach ($path in $cursorPaths) {
        if (Test-Path $path) {
            return $path
        }
    }
    
    # 嘗試從PATH找
    try {
        $result = Get-Command cursor -ErrorAction SilentlyContinue
        if ($result) {
            return $result.Source
        }
    }
    catch {}
    
    return $null
}

# 取得專案配置
function Get-ProjectConfig {
    param([string]$path)
    
    $config = @{
        Path            = $path
        Name            = Split-Path -Leaf $path
        HasGit          = Test-Path (Join-Path $path ".git")
        HasNodeModules  = Test-Path (Join-Path $path "node_modules")
        HasPackageJson  = Test-Path (Join-Path $path "package.json")
        HasComposerJson = Test-Path (Join-Path $path "composer.json")
        Language        = "Unknown"
    }
    
    # 偵測專案類型
    if ($config.HasPackageJson) {
        $config.Language = "Node.js/JavaScript"
    }
    elseif ($config.HasComposerJson) {
        $config.Language = "PHP"
    }
    elseif (Test-Path (Join-Path $path "*.py")) {
        $config.Language = "Python"
    }
    elseif (Test-Path (Join-Path $path "*.cs")) {
        $config.Language = "C#"
    }
    
    return $config
}

# 主要執行
$cursorPath = Test-CursorInstallation
if (-not $cursorPath) {
    Write-Host "ERROR: 找不到Cursor安裝路徑" -ForegroundColor Red
    Write-Host "請確保Cursor已正確安裝" -ForegroundColor Yellow
    exit 1
}

Write-Host "找到Cursor: $cursorPath" -ForegroundColor Green

# 設定專案路徑
$project1 = Get-ProjectConfig -path (Resolve-Path $Project1Path).Path
Write-Host "專案1: $($project1.Name) [$($project1.Language)]" -ForegroundColor Cyan

if ($Project2Path -eq "") {
    # 如果沒有指定第二個專案，詢問用戶
    Write-Host ""
    Write-Host "請選擇第二個專案模式:" -ForegroundColor Yellow
    Write-Host "1. 同一專案的不同分支" -ForegroundColor White
    Write-Host "2. 完全不同的專案" -ForegroundColor White
    Write-Host "3. 建立新的測試專案" -ForegroundColor White
    
    $choice = Read-Host "請輸入選擇 (1-3)"
    
    switch ($choice) {
        "1" {
            $Project2Path = $project1.Path
            Write-Host "將在同一專案開啟兩個Cursor實例" -ForegroundColor Green
        }
        "2" {
            $Project2Path = Read-Host "請輸入第二個專案的路徑"
            if (-not (Test-Path $Project2Path)) {
                Write-Host "ERROR: 第二個專案路徑不存在" -ForegroundColor Red
                exit 1
            }
        }
        "3" {
            $Project2Path = Read-Host "請輸入新專案的路徑"
            if (-not (Test-Path $Project2Path)) {
                Write-Host "創建新專案目錄: $Project2Path" -ForegroundColor Yellow
                New-Item -ItemType Directory -Path $Project2Path -Force | Out-Null
            }
        }
        default {
            Write-Host "無效選擇，使用同一專案" -ForegroundColor Yellow
            $Project2Path = $project1.Path
        }
    }
}

$project2 = Get-ProjectConfig -path (Resolve-Path $Project2Path).Path
Write-Host "專案2: $($project2.Name) [$($project2.Language)]" -ForegroundColor Cyan

# 啟動配置
Write-Host ""
Write-Host "=== 啟動配置 ===" -ForegroundColor Yellow
Write-Host "MCP支援: $(if($WithMCP){'啟用'}else{'停用'})" -ForegroundColor White
Write-Host "終端支援: $(if($WithTerminal){'啟用'}else{'停用'})" -ForegroundColor White
Write-Host "Background Agent: $(if($WithBackground){'啟用'}else{'停用'})" -ForegroundColor White

# 建立啟動腳本
$launchScript1 = @"
# 啟動專案1 Cursor
Start-Process -FilePath "$cursorPath" -ArgumentList "$($project1.Path)" -WindowStyle Normal
"@

$launchScript2 = @"
# 啟動專案2 Cursor  
Start-Sleep -Seconds 2
Start-Process -FilePath "$cursorPath" -ArgumentList "$($project2.Path)" -WindowStyle Normal
"@

# 如果啟用終端支援
if ($WithTerminal) {
    $terminalScript = @"
# 啟動開發終端
Start-Sleep -Seconds 1
Start-Process -FilePath "wt" -ArgumentList "-d", "$($project1.Path)" -WindowStyle Normal
Start-Sleep -Seconds 1  
Start-Process -FilePath "wt" -ArgumentList "-d", "$($project2.Path)" -WindowStyle Normal
"@
    $launchScript2 += "`n$terminalScript"
}

# 如果啟用MCP
if ($WithMCP) {
    $mcpScript = @"
# 啟動MCP服務
Write-Host "啟動MCP服務..." -ForegroundColor Green
Start-Process -FilePath "node" -ArgumentList "server.js" -WorkingDirectory "$($project1.Path)" -WindowStyle Minimized
"@
    $launchScript1 += "`n$mcpScript"
}

# 執行啟動
Write-Host ""
Write-Host "=== 開始啟動 ===" -ForegroundColor Green

try {
    # 啟動第一個Cursor
    Write-Host "啟動Cursor #1: $($project1.Name)" -ForegroundColor Cyan
    Invoke-Expression $launchScript1
    
    # 啟動第二個Cursor
    Write-Host "啟動Cursor #2: $($project2.Name)" -ForegroundColor Cyan
    Invoke-Expression $launchScript2
    
    # 啟動PHP伺服器 (如果是PHP專案)
    if ($project1.Language -eq "PHP" -or $project2.Language -eq "PHP") {
        Write-Host "偵測到PHP專案，啟動開發伺服器..." -ForegroundColor Yellow
        Start-Sleep -Seconds 3
        if ($project1.Language -eq "PHP") {
            Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8080", "-t", "public" -WorkingDirectory $project1.Path -WindowStyle Normal
        }
        if ($project2.Language -eq "PHP" -and $project2.Path -ne $project1.Path) {
            Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8081", "-t", "public" -WorkingDirectory $project2.Path -WindowStyle Normal
        }
    }
    
    Write-Host ""
    Write-Host "=== 啟動完成 ===" -ForegroundColor Green
    Write-Host "✓ Cursor #1: $($project1.Name)" -ForegroundColor Green
    Write-Host "✓ Cursor #2: $($project2.Name)" -ForegroundColor Green
    
    if ($WithTerminal) {
        Write-Host "✓ Windows Terminal 已啟動" -ForegroundColor Green
    }
    
    if ($project1.Language -eq "PHP") {
        Write-Host "✓ PHP Server: http://localhost:8080" -ForegroundColor Green
    }
    
    if ($project2.Language -eq "PHP" -and $project2.Path -ne $project1.Path) {
        Write-Host "✓ PHP Server: http://localhost:8081" -ForegroundColor Green
    }
    
    Write-Host ""
    Write-Host "開發環境已就緒！開始編程吧！ 🚀" -ForegroundColor Cyan
    
}
catch {
    Write-Host "ERROR: 啟動過程中發生錯誤: $($_.Exception.Message)" -ForegroundColor Red
}

# 顯示使用提示
Write-Host ""
Write-Host "=== 使用提示 ===" -ForegroundColor Yellow
Write-Host "• 兩個Cursor實例可以同時編輯不同文件" -ForegroundColor White
Write-Host "• 使用Git進行版本控制協調" -ForegroundColor White
Write-Host "• 可以在一個實例中測試，另一個實例中開發" -ForegroundColor White
Write-Host "• 使用Cursor的Background Agent進行AI協作" -ForegroundColor White

if ($WithBackground) {
    Write-Host ""
    Write-Host "Background Agent 使用方法:" -ForegroundColor Cyan
    Write-Host "1. 在任一Cursor中按 Ctrl+E" -ForegroundColor White
    Write-Host "2. 或點擊聊天界面的雲圖標" -ForegroundColor White
    Write-Host "3. 使用 @background 進行AI協作" -ForegroundColor White
} 