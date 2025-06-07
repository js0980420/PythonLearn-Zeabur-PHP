# Python協作平台 - 整合服務器啟動腳本 (PowerShell版本)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   Python協作平台 - 整合服務器啟動" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 檢查PHP是否可用
try {
    $phpVersion = php --version 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP not found"
    }
    Write-Host "✅ PHP 已找到" -ForegroundColor Green
} catch {
    Write-Host "❌ 錯誤: 找不到PHP，請確保PHP已安裝並在PATH中" -ForegroundColor Red
    Read-Host "按Enter鍵退出"
    exit 1
}

# 停止可能運行的PHP進程
Write-Host "🔄 停止現有的PHP進程..." -ForegroundColor Yellow
try {
    Get-Process -Name "php" -ErrorAction SilentlyContinue | Stop-Process -Force
    Start-Sleep -Seconds 2
} catch {
    # 忽略錯誤，可能沒有運行的PHP進程
}

Write-Host ""
Write-Host "🚀 啟動整合服務器..." -ForegroundColor Green
Write-Host ""

# 啟動主服務器 (8080端口)
Write-Host "📡 啟動主服務器 (端口: 8080)..." -ForegroundColor Blue
$mainServerJob = Start-Job -ScriptBlock {
    Set-Location $using:PWD
    php -S localhost:8080 router.php
}

# 等待主服務器啟動
Start-Sleep -Seconds 3

# 啟動修復版本的WebSocket服務器 (8081端口)
Write-Host "🔌 啟動WebSocket服務器 (端口: 8081)..." -ForegroundColor Blue
$websocketServerJob = Start-Job -ScriptBlock {
    Set-Location $using:PWD
    Set-Location websocket
    php server_fixed.php
}

# 等待WebSocket服務器啟動
Start-Sleep -Seconds 3

Write-Host ""
Write-Host "✅ 服務器啟動完成！" -ForegroundColor Green
Write-Host ""
Write-Host "📋 服務器信息:" -ForegroundColor Cyan
Write-Host "   🌐 主服務器:     http://localhost:8080" -ForegroundColor White
Write-Host "   🔌 WebSocket:    ws://localhost:8081" -ForegroundColor White
Write-Host "   💬 聊天室:       http://localhost:8080 (主頁面包含聊天功能)" -ForegroundColor White
Write-Host ""
Write-Host "📖 使用說明:" -ForegroundColor Cyan
Write-Host "   1. 打開瀏覽器訪問: http://localhost:8080" -ForegroundColor White
Write-Host "   2. 輸入房間名稱和用戶名稱" -ForegroundColor White
Write-Host "   3. 點擊'加入房間'開始協作" -ForegroundColor White
Write-Host "   4. 使用'聊天室'標籤進行實時聊天" -ForegroundColor White
Write-Host ""

# 檢查服務器是否成功啟動
Write-Host "🔍 檢查服務器狀態..." -ForegroundColor Yellow
Start-Sleep -Seconds 2

# 檢查端口8080
$port8080 = netstat -an | Select-String ":8080"
if ($port8080) {
    Write-Host "✅ 主服務器 (8080) 運行正常" -ForegroundColor Green
} else {
    Write-Host "❌ 警告: 主服務器 (8080) 可能未成功啟動" -ForegroundColor Red
}

# 檢查端口8081
$port8081 = netstat -an | Select-String ":8081"
if ($port8081) {
    Write-Host "✅ WebSocket服務器 (8081) 運行正常" -ForegroundColor Green
} else {
    Write-Host "❌ 警告: WebSocket服務器 (8081) 可能未成功啟動" -ForegroundColor Red
}

Write-Host ""
Write-Host "🌟 準備就緒！請在瀏覽器中訪問 http://localhost:8080" -ForegroundColor Green
Write-Host ""

# 詢問是否自動打開瀏覽器
$openBrowser = Read-Host "是否自動打開瀏覽器? (Y/N)"
if ($openBrowser -eq "Y" -or $openBrowser -eq "y") {
    Write-Host "🌐 正在打開瀏覽器..." -ForegroundColor Blue
    Start-Process "http://localhost:8080"
}

Write-Host ""
Write-Host "💡 提示: 服務器正在後台運行" -ForegroundColor Yellow
Write-Host "🛑 要停止服務器，請按 Ctrl+C 或關閉此窗口" -ForegroundColor Yellow
Write-Host ""

# 監控服務器狀態
try {
    Write-Host "📊 服務器監控中... (按 Ctrl+C 停止)" -ForegroundColor Cyan
    while ($true) {
        Start-Sleep -Seconds 10
        
        # 檢查作業狀態
        $mainStatus = Get-Job -Id $mainServerJob.Id
        $websocketStatus = Get-Job -Id $websocketServerJob.Id
        
        if ($mainStatus.State -eq "Failed" -or $websocketStatus.State -eq "Failed") {
            Write-Host "❌ 檢測到服務器異常，正在重啟..." -ForegroundColor Red
            break
        }
        
        Write-Host "." -NoNewline -ForegroundColor Green
    }
} catch {
    Write-Host ""
    Write-Host "🛑 正在停止服務器..." -ForegroundColor Yellow
} finally {
    # 清理作業
    Stop-Job -Job $mainServerJob -ErrorAction SilentlyContinue
    Stop-Job -Job $websocketServerJob -ErrorAction SilentlyContinue
    Remove-Job -Job $mainServerJob -ErrorAction SilentlyContinue
    Remove-Job -Job $websocketServerJob -ErrorAction SilentlyContinue
    
    # 停止PHP進程
    Get-Process -Name "php" -ErrorAction SilentlyContinue | Stop-Process -Force
    
    Write-Host "✅ 服務器已停止" -ForegroundColor Green
} 