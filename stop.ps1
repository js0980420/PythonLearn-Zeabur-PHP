# 🛑 PythonLearn-Zeabur-PHP 服務停止腳本
# 版本: v2.0
# 功能: 安全停止所有相關服務

param(
    [switch]$Force,    # 強制終止所有 PHP 進程
    [switch]$Verbose   # 詳細輸出
)

# 設置控制台編碼為 UTF-8
chcp 65001 > $null

Write-Host "🛑 PythonLearn-Zeabur-PHP 服務停止腳本" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Red

# 函數：檢查端口占用
function Test-PortOccupied {
    param([int]$Port)
    $result = netstat -ano | findstr ":$Port "
    return $result -ne $null
}

# 函數：終止占用端口的進程
function Stop-PortProcess {
    param([int]$Port, [string]$ServiceName)
    $connections = netstat -ano | findstr ":$Port "
    if ($connections) {
        Write-Host "🔍 發現 $ServiceName 進程 (端口 $Port)..." -ForegroundColor Yellow
        foreach ($line in $connections) {
            if ($line -match '\s+(\d+)$') {
                $processId = $matches[1]
                try {
                    $process = Get-Process -Id $processId -ErrorAction SilentlyContinue
                    if ($process) {
                        Write-Host "🗑️ 終止進程: $($process.ProcessName) (PID: $processId)" -ForegroundColor Yellow
                        Stop-Process -Id $processId -Force -ErrorAction SilentlyContinue
                        Start-Sleep -Milliseconds 500
                        
                        if ($Verbose) {
                            Write-Host "   ✅ 進程 $processId 已終止" -ForegroundColor Green
                        }
                    }
                } catch {
                    if ($Verbose) {
                        Write-Host "   ⚠️ 無法終止進程 PID: $processId" -ForegroundColor Yellow
                    }
                }
            }
        }
    } else {
        if ($Verbose) {
            Write-Host "✅ 端口 $Port 沒有占用進程" -ForegroundColor Green
        }
    }
}

Write-Host ""

# 檢查當前服務狀態
$webRunning = Test-PortOccupied -Port 8080
$wsRunning = Test-PortOccupied -Port 8081

if (-not $webRunning -and -not $wsRunning) {
    Write-Host "✅ 沒有發現運行中的服務" -ForegroundColor Green
    exit 0
}

Write-Host "🔍 檢測到運行中的服務..." -ForegroundColor Cyan
if ($webRunning) {
    Write-Host "   🌐 Web 服務器 (端口 8080)" -ForegroundColor Blue
}
if ($wsRunning) {
    Write-Host "   🔌 WebSocket 服務器 (端口 8081)" -ForegroundColor Blue
}

Write-Host ""

# 停止特定端口的服務
if ($webRunning) {
    Write-Host "🛑 停止 Web 服務器..." -ForegroundColor Red
    Stop-PortProcess -Port 8080 -ServiceName "Web服務器"
}

if ($wsRunning) {
    Write-Host "🛑 停止 WebSocket 服務器..." -ForegroundColor Red
    Stop-PortProcess -Port 8081 -ServiceName "WebSocket服務器"
}

# 強制模式：停止所有 PHP 進程
if ($Force) {
    Write-Host ""
    Write-Host "⚡ 強制模式：停止所有 PHP 進程..." -ForegroundColor Red
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        Write-Host "   🗑️ 發現 $($phpProcesses.Count) 個 PHP 進程" -ForegroundColor Yellow
        $phpProcesses | ForEach-Object {
            if ($Verbose) {
                Write-Host "   🗑️ 終止: $($_.ProcessName) (PID: $($_.Id))" -ForegroundColor Yellow
            }
            Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
        }
    } else {
        Write-Host "   ✅ 沒有發現 PHP 進程" -ForegroundColor Green
    }
}

# 等待進程完全終止
Start-Sleep -Seconds 2

Write-Host ""
Write-Host "🔍 驗證停止結果..." -ForegroundColor Cyan

$webStillRunning = Test-PortOccupied -Port 8080
$wsStillRunning = Test-PortOccupied -Port 8081

if (-not $webStillRunning -and -not $wsStillRunning) {
    Write-Host "✅ 所有服務已成功停止！" -ForegroundColor Green
} else {
    Write-Host "⚠️ 部分服務可能仍在運行：" -ForegroundColor Yellow
    if ($webStillRunning) {
        Write-Host "   🌐 Web 服務器 (端口 8080) 仍在運行" -ForegroundColor Yellow
    }
    if ($wsStillRunning) {
        Write-Host "   🔌 WebSocket 服務器 (端口 8081) 仍在運行" -ForegroundColor Yellow
    }
    Write-Host "   💡 建議使用 -Force 參數強制停止" -ForegroundColor Blue
}

Write-Host ""
Write-Host "🎉 停止腳本執行完成！" -ForegroundColor Green 