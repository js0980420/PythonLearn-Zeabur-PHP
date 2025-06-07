# PythonLearn-Zeabur-PHP 停止脚本 (中文版)
# 支持中文显示，避免乱码

param(
    [switch]$Force,    # 强制终止所有 PHP 进程
    [switch]$Verbose   # 详细输出
)

# 设置控制台编码为 UTF-8
$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

Write-Host "=====================================" -ForegroundColor Red
Write-Host "🛑 PythonLearn-Zeabur-PHP 停止脚本" -ForegroundColor Red
Write-Host "=====================================" -ForegroundColor Red
Write-Host ""

# 函数：检查端口占用
function Test-PortOccupied {
    param([int]$Port)
    $result = netstat -ano | findstr ":$Port "
    return $result -ne $null -and $result.Count -gt 0
}

# 函数：终止占用端口的进程
function Stop-PortProcess {
    param([int]$Port, [string]$ServiceName)
    $connections = netstat -ano | findstr ":$Port "
    if ($connections) {
        Write-Host "🔍 发现 $ServiceName 进程 (端口 $Port)..." -ForegroundColor Yellow
        foreach ($line in $connections) {
            if ($line -match '\s+(\d+)$') {
                $processId = $matches[1]
                try {
                    $process = Get-Process -Id $processId -ErrorAction SilentlyContinue
                    if ($process) {
                        Write-Host "🗑️ 终止进程: $($process.ProcessName) (PID: $processId)" -ForegroundColor Yellow
                        Stop-Process -Id $processId -Force -ErrorAction SilentlyContinue
                        Start-Sleep -Milliseconds 500
                        
                        if ($Verbose) {
                            Write-Host "   ✅ 进程 $processId 已终止" -ForegroundColor Green
                        }
                    }
                } catch {
                    if ($Verbose) {
                        Write-Host "   ⚠️ 无法终止进程 PID: $processId" -ForegroundColor Yellow
                    }
                }
            }
        }
    } else {
        if ($Verbose) {
            Write-Host "✅ 端口 $Port 没有占用进程" -ForegroundColor Green
        }
    }
}

# 检查当前服务状态
$webRunning = Test-PortOccupied -Port 8080
$wsRunning = Test-PortOccupied -Port 8081

if (-not $webRunning -and -not $wsRunning) {
    Write-Host "✅ 没有发现运行中的服务" -ForegroundColor Green
    Write-Host ""
    Write-Host "=====================================" -ForegroundColor Red
    exit 0
}

Write-Host "🔍 检测到运行中的服务..." -ForegroundColor Cyan
if ($webRunning) {
    Write-Host "   🌐 Web 服务器 (端口 8080)" -ForegroundColor Blue
}
if ($wsRunning) {
    Write-Host "   🔌 WebSocket 服务器 (端口 8081)" -ForegroundColor Blue
}

Write-Host ""

# 停止特定端口的服务
if ($webRunning) {
    Write-Host "🛑 停止 Web 服务器..." -ForegroundColor Red
    Stop-PortProcess -Port 8080 -ServiceName "Web服务器"
}

if ($wsRunning) {
    Write-Host "🛑 停止 WebSocket 服务器..." -ForegroundColor Red
    Stop-PortProcess -Port 8081 -ServiceName "WebSocket服务器"
}

# 强制模式：停止所有 PHP 进程
if ($Force) {
    Write-Host ""
    Write-Host "⚡ 强制模式：停止所有 PHP 进程..." -ForegroundColor Red
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        Write-Host "   🗑️ 发现 $($phpProcesses.Count) 个 PHP 进程" -ForegroundColor Yellow
        $phpProcesses | ForEach-Object {
            if ($Verbose) {
                Write-Host "   🗑️ 终止: $($_.ProcessName) (PID: $($_.Id))" -ForegroundColor Yellow
            }
            Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
        }
    } else {
        Write-Host "   ✅ 没有发现 PHP 进程" -ForegroundColor Green
    }
}

# 等待进程完全终止
Start-Sleep -Seconds 2

Write-Host ""
Write-Host "🔍 验证停止结果..." -ForegroundColor Cyan

$webStillRunning = Test-PortOccupied -Port 8080
$wsStillRunning = Test-PortOccupied -Port 8081

if (-not $webStillRunning -and -not $wsStillRunning) {
    Write-Host "✅ 所有服务已成功停止！" -ForegroundColor Green
} else {
    Write-Host "⚠️ 部分服务可能仍在运行：" -ForegroundColor Yellow
    if ($webStillRunning) {
        Write-Host "   🌐 Web 服务器 (端口 8080) 仍在运行" -ForegroundColor Yellow
    }
    if ($wsStillRunning) {
        Write-Host "   🔌 WebSocket 服务器 (端口 8081) 仍在运行" -ForegroundColor Yellow
    }
    Write-Host "   💡 建议使用 -Force 参数强制停止" -ForegroundColor Blue
}

Write-Host ""
Write-Host "🎉 停止脚本执行完成！" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Red 