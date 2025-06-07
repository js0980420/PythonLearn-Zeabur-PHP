# AI API 服務器啟動腳本
# 在端口 8081 啟動 PHP 開發服務器專門處理 AI API 請求

Write-Host "🤖 啟動 AI API 服務器..." -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan

# 檢查當前目錄
$currentPath = Get-Location
Write-Host "📁 當前目錄: $currentPath" -ForegroundColor Yellow

# 檢查是否在正確的專案目錄
if (-not (Test-Path "backend/api/ai.php")) {
    Write-Host "❌ 錯誤: 未找到 backend/api/ai.php" -ForegroundColor Red
    Write-Host "💡 請確保在 PythonLearn-Zeabur-PHP 專案根目錄執行此腳本" -ForegroundColor Yellow
    Read-Host "按 Enter 鍵退出"
    exit 1
}

# 檢查端口 8081 是否被占用
$port8081 = Get-NetTCPConnection -LocalPort 8081 -ErrorAction SilentlyContinue
if ($port8081) {
    Write-Host "⚠️ 端口 8081 已被占用" -ForegroundColor Yellow
    Write-Host "🔄 嘗試終止占用進程..." -ForegroundColor Yellow
    
    foreach ($conn in $port8081) {
        try {
            Stop-Process -Id $conn.OwningProcess -Force -ErrorAction SilentlyContinue
            Write-Host "✅ 已終止進程 ID: $($conn.OwningProcess)" -ForegroundColor Green
        } catch {
            Write-Host "❌ 無法終止進程 ID: $($conn.OwningProcess)" -ForegroundColor Red
        }
    }
    
    Start-Sleep -Seconds 2
}

Write-Host "🚀 在端口 8081 啟動 AI API 服務器..." -ForegroundColor Green
Write-Host "📡 AI API 端點: http://localhost:8081/api/ai" -ForegroundColor Cyan
Write-Host "⏹️ 按 Ctrl+C 停止服務器" -ForegroundColor Yellow
Write-Host "================================" -ForegroundColor Cyan

# 啟動 PHP 開發服務器
try {
    php -S localhost:8081 router.php
} catch {
    Write-Host "❌ 啟動失敗: $_" -ForegroundColor Red
    Read-Host "按 Enter 鍵退出"
} 