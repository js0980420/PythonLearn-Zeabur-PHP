# PythonLearn-Zeabur-PHP 启动脚本 (中文版)
# 支持中文显示，避免乱码

param(
    [switch]$Clean,
    [switch]$OpenBrowser,
    [switch]$Verbose
)

# 设置控制台编码为 UTF-8
$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

# 清屏并显示标题
Clear-Host
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "🚀 PythonLearn-Zeabur-PHP 启动脚本" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# 获取项目根目录 (scripts的上级目录)
$projectRoot = Split-Path -Parent $PSScriptRoot
$originalLocation = Get-Location
Set-Location $projectRoot

Write-Host "📁 项目目录: $projectRoot" -ForegroundColor Green

# 检查必要文件
if (-not (Test-Path "router.php")) {
    Write-Host "❌ 错误: 找不到 router.php 文件" -ForegroundColor Red
    Write-Host "   请确保在正确的项目目录运行此脚本" -ForegroundColor Yellow
    Set-Location $originalLocation
    Read-Host "按任意键退出"
    exit 1
}

if (-not (Test-Path "websocket/server.php")) {
    Write-Host "❌ 错误: 找不到 websocket/server.php 文件" -ForegroundColor Red
    Set-Location $originalLocation
    Read-Host "按任意键退出"
    exit 1
}

Write-Host "✅ 项目文件检查通过" -ForegroundColor Green
Write-Host ""

# 清理旧进程函数
function Stop-PHPProcesses {
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        Write-Host "🧹 发现 $($phpProcesses.Count) 个 PHP 进程，正在清理..." -ForegroundColor Yellow
        $phpProcesses | ForEach-Object {
            if ($Verbose) {
                Write-Host "   🗑️ 终止进程: PID $($_.Id)" -ForegroundColor Gray
            }
            Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
        }
        Start-Sleep -Seconds 2
        Write-Host "✅ PHP 进程清理完成" -ForegroundColor Green
    } else {
        Write-Host "✅ 未发现运行中的 PHP 进程" -ForegroundColor Green
    }
}

# 检查端口占用函数
function Test-PortOccupied {
    param([int]$Port)
    $result = netstat -ano | findstr ":$Port "
    return $result -ne $null -and $result.Count -gt 0
}

# 清理旧进程
if ($Clean -or (Test-PortOccupied -Port 8080) -or (Test-PortOccupied -Port 8081)) {
    Stop-PHPProcesses
} else {
    Write-Host "✅ 端口 8080 和 8081 未被占用" -ForegroundColor Green
}

Write-Host ""
Write-Host "🚀 正在启动服务..." -ForegroundColor Cyan
Write-Host ""

# 启动 Web 服务器
Write-Host "🌐 启动 Web 服务器 (端口 8080)..." -ForegroundColor Blue
$webProcess = Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8080", "router.php" -WorkingDirectory $projectRoot -PassThru

Start-Sleep -Seconds 3

# 启动 WebSocket 服务器
Write-Host "🔌 启动 WebSocket 服务器 (端口 8081)..." -ForegroundColor Blue
$wsProcess = Start-Process -FilePath "php" -ArgumentList "server.php" -WorkingDirectory "$projectRoot\websocket" -PassThru

Start-Sleep -Seconds 3

# 检查服务状态
Write-Host ""
Write-Host "🔍 检查服务状态..." -ForegroundColor Cyan

$webRunning = Test-PortOccupied -Port 8080
$wsRunning = Test-PortOccupied -Port 8081

if ($webRunning) {
    Write-Host "   ✅ Web 服务器运行正常 (端口 8080)" -ForegroundColor Green
} else {
    Write-Host "   ❌ Web 服务器启动失败" -ForegroundColor Red
}

if ($wsRunning) {
    Write-Host "   ✅ WebSocket 服务器运行正常 (端口 8081)" -ForegroundColor Green
} else {
    Write-Host "   ❌ WebSocket 服务器启动失败" -ForegroundColor Red
}

Write-Host ""

if ($webRunning -and $wsRunning) {
    Write-Host "🎉 所有服务启动成功！" -ForegroundColor Green
    Write-Host ""
    Write-Host "📝 服务地址:" -ForegroundColor Cyan
    Write-Host "   🌐 Web 服务器: http://localhost:8080" -ForegroundColor White
    Write-Host "   🔌 WebSocket 服务器: ws://localhost:8081" -ForegroundColor White
    Write-Host ""
    
    if ($OpenBrowser) {
        Write-Host "🌍 正在打开浏览器..." -ForegroundColor Blue
        Start-Process "http://localhost:8080"
        Start-Sleep -Seconds 2
    }
    
    Write-Host "💡 使用提示:" -ForegroundColor Yellow
    Write-Host "   • Web 服务器进程 ID: $($webProcess.Id)" -ForegroundColor Gray
    Write-Host "   • WebSocket 服务器进程 ID: $($wsProcess.Id)" -ForegroundColor Gray
    Write-Host "   • 运行 scripts\stop-cn.ps1 停止服务" -ForegroundColor Gray
    Write-Host ""
    
} else {
    Write-Host "❌ 部分服务启动失败" -ForegroundColor Red
    Write-Host "💡 建议运行: scripts\start-cn.ps1 -Clean -Verbose" -ForegroundColor Yellow
}

# 显示进程信息
if ($Verbose) {
    Write-Host ""
    Write-Host "📊 进程信息:" -ForegroundColor Cyan
    if ($webProcess) {
        Write-Host "   Web 服务器进程 ID: $($webProcess.Id)" -ForegroundColor Gray
    }
    if ($wsProcess) {
        Write-Host "   WebSocket 服务器进程 ID: $($wsProcess.Id)" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan

# 恢复原始位置
Set-Location $originalLocation 