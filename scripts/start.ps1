# 🚀 PythonLearn-Zeabur-PHP 智能啟動腳本
# 版本: v2.0
# 功能: 自動清理、啟動服務、監控狀態

param(
    [switch]$Clean,     # 強制清理所有相關進程
    [switch]$Monitor,   # 啟動後持續監控
    [switch]$Verbose,   # 詳細輸出
    [switch]$NoLogs     # 不顯示服務器日誌
)

# 設置控制台編碼為 UTF-8
chcp 65001 > $null

Write-Host "🚀 PythonLearn-Zeabur-PHP 啟動腳本 v2.0" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

# 檢查工作目錄
$projectPath = Get-Location
if (-not (Test-Path "server.js")) {
    Write-Host "❌ 錯誤: 請在專案根目錄執行此腳本" -ForegroundColor Red
    Write-Host "   當前目錄: $projectPath" -ForegroundColor Yellow
    Write-Host "   請切換到包含 server.js 的目錄" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ 工作目錄: $projectPath" -ForegroundColor Green

# 函數：檢查端口占用
function Test-PortOccupied {
    param([int]$Port)
    $result = netstat -ano | findstr ":$Port "
    return $result -ne $null
}

# 函數：終止占用端口的進程
function Stop-PortProcess {
    param([int]$Port)
    $connections = netstat -ano | findstr ":$Port "
    if ($connections) {
        foreach ($line in $connections) {
            if ($line -match '\s+(\d+)$') {
                $processId = $matches[1]
                try {
                    $process = Get-Process -Id $processId -ErrorAction SilentlyContinue
                    if ($process) {
                        Write-Host "🗑️ 終止進程: $($process.ProcessName) (PID: $processId)" -ForegroundColor Yellow
                        Stop-Process -Id $processId -Force -ErrorAction SilentlyContinue
                        Start-Sleep -Milliseconds 500
                    }
                } catch {
                    Write-Host "⚠️ 無法終止進程 PID: $processId" -ForegroundColor Yellow
                }
            }
        }
    }
}

# 函數：清理PHP進程
function Stop-PhpProcesses {
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        Write-Host "🧹 清理 PHP 進程..." -ForegroundColor Yellow
        foreach ($proc in $phpProcesses) {
            Write-Host "   終止 PHP PID: $($proc.Id)" -ForegroundColor Gray
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
        }
        Start-Sleep -Seconds 2
    } else {
        Write-Host "✅ 無需清理 PHP 進程" -ForegroundColor Green
    }
}

# 函數：檢查服務器狀態
function Test-ServerStatus {
    param([string]$Url, [string]$Name)
    try {
        $response = Invoke-WebRequest -Uri $Url -TimeoutSec 3 -UseBasicParsing
        if ($response.StatusCode -eq 200) {
            Write-Host "✅ $Name 運行正常" -ForegroundColor Green
            return $true
        }
    } catch {
        Write-Host "❌ $Name 無法訪問" -ForegroundColor Red
        return $false
    }
}

# 函數：啟動服務器
function Start-WebServer {
    Write-Host "🌐 啟動 Web 服務器 (端口 8080)..." -ForegroundColor Cyan
    $webJob = Start-Job -ScriptBlock {
        Set-Location $using:projectPath
        php -S localhost:8080 router.php
    }
    
    # 等待啟動
    Start-Sleep -Seconds 3
    
    if (Test-PortOccupied -Port 8080) {
        Write-Host "✅ Web 服務器啟動成功" -ForegroundColor Green
        return $webJob
    } else {
        Write-Host "❌ Web 服務器啟動失敗" -ForegroundColor Red
        Stop-Job $webJob -ErrorAction SilentlyContinue
        Remove-Job $webJob -ErrorAction SilentlyContinue
        return $null
    }
}

function Start-WebSocketServer {
    Write-Host "🔌 啟動 WebSocket 服務器 (端口 8081)..." -ForegroundColor Cyan
    $wsJob = Start-Job -ScriptBlock {
        Set-Location "$using:projectPath\websocket"
        php server.php
    }
    
    # 等待啟動
    Start-Sleep -Seconds 3
    
    if (Test-PortOccupied -Port 8081) {
        Write-Host "✅ WebSocket 服務器啟動成功" -ForegroundColor Green
        return $wsJob
    } else {
        Write-Host "❌ WebSocket 服務器啟動失敗" -ForegroundColor Red
        Stop-Job $wsJob -ErrorAction SilentlyContinue
        Remove-Job $wsJob -ErrorAction SilentlyContinue
        return $null
    }
}

# 函數：顯示服務器日誌
function Show-ServerLogs {
    param($WebJob, $WsJob)
    
    if (-not $NoLogs) {
        Write-Host "`n📋 服務器日誌 (按 Ctrl+C 停止監控):" -ForegroundColor Cyan
        Write-Host "=" * 50 -ForegroundColor Gray
        
        while ($true) {
            if ($WebJob -and $WebJob.State -eq 'Running') {
                $webOutput = Receive-Job $WebJob -Keep
                if ($webOutput) {
                    $webOutput | ForEach-Object { 
                        Write-Host "[WEB] $_" -ForegroundColor Blue 
                    }
                }
            }
            
            if ($WsJob -and $WsJob.State -eq 'Running') {
                $wsOutput = Receive-Job $WsJob -Keep
                if ($wsOutput) {
                    $wsOutput | ForEach-Object { 
                        if ($_ -match "error|fail|fatal") {
                            Write-Host "[WS] $_" -ForegroundColor Red
                        } elseif ($_ -match "deprecat|warning") {
                            Write-Host "[WS] $_" -ForegroundColor Yellow
                        } else {
                            Write-Host "[WS] $_" -ForegroundColor Green
                        }
                    }
                }
            }
            
            Start-Sleep -Seconds 1
        }
    }
}

# 主程序開始
try {
    # 第一步：清理環境
    Write-Host "`n🧹 第一步：環境清理" -ForegroundColor Yellow
    
    if ($Clean) {
        Write-Host "🔄 強制清理模式..." -ForegroundColor Yellow
        Stop-PhpProcesses
    }
    
    # 檢查並清理端口
    if (Test-PortOccupied -Port 8080) {
        Write-Host "⚠️ 端口 8080 被占用，正在清理..." -ForegroundColor Yellow
        Stop-PortProcess -Port 8080
    }
    
    if (Test-PortOccupied -Port 8081) {
        Write-Host "⚠️ 端口 8081 被占用，正在清理..." -ForegroundColor Yellow
        Stop-PortProcess -Port 8081
    }
    
    # 最終檢查
    Start-Sleep -Seconds 2
    $port8080Free = -not (Test-PortOccupied -Port 8080)
    $port8081Free = -not (Test-PortOccupied -Port 8081)
    
    if ($port8080Free -and $port8081Free) {
        Write-Host "✅ 端口清理完成" -ForegroundColor Green
    } else {
        Write-Host "❌ 端口清理失敗，請手動檢查" -ForegroundColor Red
        if (-not $port8080Free) { Write-Host "   端口 8080 仍被占用" -ForegroundColor Red }
        if (-not $port8081Free) { Write-Host "   端口 8081 仍被占用" -ForegroundColor Red }
        exit 1
    }
    
    # 第二步：啟動服務器
    Write-Host "`n🚀 第二步：啟動服務器" -ForegroundColor Yellow
    
    $webJob = Start-WebServer
    if (-not $webJob) {
        Write-Host "❌ Web 服務器啟動失敗，退出" -ForegroundColor Red
        exit 1
    }
    
    $wsJob = Start-WebSocketServer  
    if (-not $wsJob) {
        Write-Host "❌ WebSocket 服務器啟動失敗，清理並退出" -ForegroundColor Red
        Stop-Job $webJob -ErrorAction SilentlyContinue
        Remove-Job $webJob -ErrorAction SilentlyContinue
        exit 1
    }
    
    # 第三步：驗證服務
    Write-Host "`n✅ 第三步：服務驗證" -ForegroundColor Yellow
    Start-Sleep -Seconds 2
    
    $webOk = Test-ServerStatus -Url "http://localhost:8080" -Name "Web 服務器"
    # WebSocket 服務器檢查（簡單端口檢查）
    $wsOk = Test-PortOccupied -Port 8081
    if ($wsOk) {
        Write-Host "✅ WebSocket 服務器運行正常" -ForegroundColor Green
    }
    
    if ($webOk -and $wsOk) {
        Write-Host "`n🎉 所有服務啟動成功！" -ForegroundColor Green
        Write-Host "   🌐 Web 服務器: http://localhost:8080" -ForegroundColor Blue
        Write-Host "   🔌 WebSocket: ws://localhost:8081" -ForegroundColor Blue
        Write-Host "   📊 使用方法: 在瀏覽器打開 http://localhost:8080" -ForegroundColor Blue
        
        # 服務監控
        if ($Monitor) {
            Show-ServerLogs -WebJob $webJob -WsJob $wsJob
        } else {
            Write-Host "`n💡 提示:" -ForegroundColor Cyan
            Write-Host "   - 使用 -Monitor 參數查看即時日誌" -ForegroundColor Gray
            Write-Host "   - 按 Ctrl+C 停止服務器" -ForegroundColor Gray
            Write-Host "   - 服務器正在背景運行..." -ForegroundColor Gray
            
            # 保持腳本運行
            Write-Host "`n⏳ 按任意鍵停止服務器..." -ForegroundColor Yellow
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
        }
    } else {
        Write-Host "`n❌ 服務啟動失敗" -ForegroundColor Red
        exit 1
    }
    
} catch {
    Write-Host "`n❌ 腳本執行錯誤: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
} finally {
    # 清理作業
    Write-Host "`n🛑 正在停止服務器..." -ForegroundColor Yellow
    
    if ($webJob) {
        Stop-Job $webJob -ErrorAction SilentlyContinue
        Remove-Job $webJob -ErrorAction SilentlyContinue
    }
    
    if ($wsJob) {
        Stop-Job $wsJob -ErrorAction SilentlyContinue  
        Remove-Job $wsJob -ErrorAction SilentlyContinue
    }
    
    # 最終清理
    Stop-PhpProcesses
    
    Write-Host "✅ 清理完成" -ForegroundColor Green
} 