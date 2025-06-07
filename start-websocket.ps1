# WebSocket 服務器啟動腳本
# 適用於 PowerShell 環境

Write-Host "🚀 啟動 WebSocket 服務器..." -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan

# 檢查當前目錄
$currentPath = Get-Location
Write-Host "📁 當前目錄: $currentPath" -ForegroundColor Yellow

# 檢查是否在正確的專案目錄
if (-not (Test-Path "websocket/server.php")) {
    Write-Host "❌ 錯誤: 未找到 websocket/server.php" -ForegroundColor Red
    Write-Host "💡 請確保在 PythonLearn-Zeabur-PHP 專案根目錄執行此腳本" -ForegroundColor Yellow
    Read-Host "按 Enter 鍵退出"
    exit 1
}

# 檢查 PHP 是否可用
try {
    $phpVersion = php -v 2>$null
    if ($LASTEXITCODE -eq 0) {
        $versionLine = ($phpVersion -split "`n")[0]
        Write-Host "✅ PHP 版本: $versionLine" -ForegroundColor Green
    } else {
        throw "PHP not found"
    }
} catch {
    Write-Host "❌ 錯誤: 未找到 PHP" -ForegroundColor Red
    Write-Host "💡 請確保 PHP 已安裝並添加到 PATH 環境變數" -ForegroundColor Yellow
    Read-Host "按 Enter 鍵退出"
    exit 1
}

# 檢查端口 8080 是否被占用
Write-Host "🔍 檢查端口 8080..." -ForegroundColor Yellow
$portCheck = netstat -ano | findstr ":8080"
if ($portCheck) {
    Write-Host "⚠️ 端口 8080 已被占用:" -ForegroundColor Yellow
    $portCheck | ForEach-Object { Write-Host "   $_" -ForegroundColor Gray }
    
    $choice = Read-Host "是否要終止占用進程並繼續? (Y/N)"
    if ($choice -eq 'Y' -or $choice -eq 'y') {
        # 提取 PID 並終止進程
        $pids = $portCheck | ForEach-Object {
            if ($_ -match '\s+(\d+)$') { $matches[1] }
        } | Select-Object -Unique
        
        foreach ($pid in $pids) {
            try {
                Stop-Process -Id $pid -Force
                Write-Host "✅ 已終止進程 PID: $pid" -ForegroundColor Green
            } catch {
                Write-Host "❌ 無法終止進程 PID: $pid" -ForegroundColor Red
            }
        }
        
        # 等待端口釋放
        Start-Sleep -Seconds 2
    } else {
        Write-Host "❌ 用戶取消操作" -ForegroundColor Red
        Read-Host "按 Enter 鍵退出"
        exit 1
    }
}

# 切換到專案目錄並啟動 WebSocket 服務器
Write-Host "🔄 啟動 WebSocket 服務器..." -ForegroundColor Cyan

try {
    # 使用 Start-Process 在新視窗中啟動 WebSocket 服務器
    $process = Start-Process -FilePath "php" -ArgumentList "-f", "websocket/server.php" -PassThru -WindowStyle Normal
    
    if ($process) {
        Write-Host "✅ WebSocket 服務器已啟動" -ForegroundColor Green
        Write-Host "📊 進程 ID: $($process.Id)" -ForegroundColor Yellow
        Write-Host "🌐 服務地址: ws://localhost:8080" -ForegroundColor Cyan
        Write-Host "📝 日誌將顯示在新開啟的視窗中" -ForegroundColor Yellow
        
        # 等待幾秒鐘檢查進程是否正常運行
        Start-Sleep -Seconds 3
        
        if (-not $process.HasExited) {
            Write-Host "✅ WebSocket 服務器運行正常" -ForegroundColor Green
            Write-Host "💡 要停止服務器，請關閉 PHP 視窗或使用 Ctrl+C" -ForegroundColor Blue
        } else {
            Write-Host "❌ WebSocket 服務器啟動失敗" -ForegroundColor Red
            Write-Host "💡 請檢查 websocket/server.php 檔案是否存在錯誤" -ForegroundColor Yellow
        }
    } else {
        Write-Host "❌ 無法啟動 WebSocket 服務器" -ForegroundColor Red
    }
    
} catch {
    Write-Host "❌ 啟動過程中發生錯誤: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "💡 請檢查 PHP 安裝和檔案權限" -ForegroundColor Yellow
}

Write-Host "`n🔧 其他有用的指令:" -ForegroundColor Cyan
Write-Host "   測試 AI API: php test_ai_api.php" -ForegroundColor Gray
Write-Host "   檢查端口: netstat -ano | findstr :8080" -ForegroundColor Gray
Write-Host "   停止所有 PHP 進程: taskkill /IM php.exe /F" -ForegroundColor Gray

Write-Host "`n🎯 WebSocket 啟動腳本執行完成" -ForegroundColor Green
Read-Host "按 Enter 鍵退出" 