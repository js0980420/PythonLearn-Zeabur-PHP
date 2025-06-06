# 🛑 PythonLearn-Zeabur-PHP 服務停止腳本
# 版本: v1.0
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
                        Write-Host "   🗑️ 終止進程: $($process.ProcessName) (PID: $processId)" -ForegroundColor Yellow
                        Stop-Process -Id $processId -Force -ErrorAction SilentlyContinue
                        Start-Sleep -Milliseconds 500
                    }
                } catch {
                    Write-Host "   ⚠️ 無法終止進程 PID: $processId" -ForegroundColor Yellow
                }
            }
        }
    } else {
        Write-Host "✅ $ServiceName 未運行" -ForegroundColor Green
    }
}

# 函數：清理PHP進程
function Stop-PhpProcesses {
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        Write-Host "🧹 清理所有 PHP 進程..." -ForegroundColor Yellow
        foreach ($proc in $phpProcesses) {
            $cmdLine = ""
            try {
                $cmdLine = (Get-WmiObject Win32_Process -Filter "ProcessId = $($proc.Id)").CommandLine
            } catch {
                $cmdLine = "無法獲取命令行"
            }
            
            if ($Verbose) {
                Write-Host "   📋 PID: $($proc.Id) - $cmdLine" -ForegroundColor Gray
            }
            
            Write-Host "   🗑️ 終止 PHP PID: $($proc.Id)" -ForegroundColor Yellow
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
        }
        Start-Sleep -Seconds 2
        Write-Host "✅ PHP 進程清理完成" -ForegroundColor Green
    } else {
        Write-Host "✅ 無 PHP 進程需要清理" -ForegroundColor Green
    }
}

# 主程序
try {
    Write-Host "`n🔍 檢查服務狀態..." -ForegroundColor Cyan
    
    # 檢查並停止 Web 服務器 (端口 8080)
    if (Test-PortOccupied -Port 8080) {
        Stop-PortProcess -Port 8080 -ServiceName "Web 服務器"
    } else {
        Write-Host "✅ Web 服務器未運行" -ForegroundColor Green
    }
    
    # 檢查並停止 WebSocket 服務器 (端口 8081)
    if (Test-PortOccupied -Port 8081) {
        Stop-PortProcess -Port 8081 -ServiceName "WebSocket 服務器"
    } else {
        Write-Host "✅ WebSocket 服務器未運行" -ForegroundColor Green
    }
    
    # 強制清理所有PHP進程（如果指定了 -Force 參數）
    if ($Force) {
        Write-Host "`n🔄 強制清理模式..." -ForegroundColor Red
        Stop-PhpProcesses
    }
    
    # 最終檢查
    Write-Host "`n✅ 最終檢查..." -ForegroundColor Cyan
    Start-Sleep -Seconds 2
    
    $port8080Occupied = Test-PortOccupied -Port 8080
    $port8081Occupied = Test-PortOccupied -Port 8081
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    
    if (-not $port8080Occupied -and -not $port8081Occupied) {
        Write-Host "🎉 所有服務已成功停止！" -ForegroundColor Green
        Write-Host "   ✅ 端口 8080 已釋放" -ForegroundColor Green
        Write-Host "   ✅ 端口 8081 已釋放" -ForegroundColor Green
        
        if ($phpProcesses) {
            Write-Host "   ⚠️ 仍有 $($phpProcesses.Count) 個 PHP 進程運行" -ForegroundColor Yellow
            Write-Host "   💡 使用 -Force 參數強制清理所有 PHP 進程" -ForegroundColor Cyan
        } else {
            Write-Host "   ✅ 無殘留 PHP 進程" -ForegroundColor Green
        }
    } else {
        Write-Host "⚠️ 部分服務可能仍在運行" -ForegroundColor Yellow
        if ($port8080Occupied) { Write-Host "   ❌ 端口 8080 仍被占用" -ForegroundColor Red }
        if ($port8081Occupied) { Write-Host "   ❌ 端口 8081 仍被占用" -ForegroundColor Red }
    }
    
} catch {
    Write-Host "`n❌ 腳本執行錯誤: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host "`n📋 服務停止完成" -ForegroundColor Cyan
Write-Host "   💡 如需重新啟動，請使用: .\start.ps1" -ForegroundColor Gray
Write-Host "   💡 快速啟動，請使用: .\quick-start.bat" -ForegroundColor Gray 