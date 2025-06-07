# 測試 AI 後端啟動腳本
# 在端口 8082 啟動測試 AI 服務器

Write-Host "🧪 啟動測試 AI 後端服務器..." -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan

# 檢查當前目錄
$currentPath = Get-Location
Write-Host "📁 當前目錄: $currentPath" -ForegroundColor Yellow

# 檢查是否存在測試後端檔案
if (-not (Test-Path "test-ai-backend.php")) {
    Write-Host "❌ 錯誤: 未找到 test-ai-backend.php" -ForegroundColor Red
    Write-Host "💡 請確保在專案根目錄執行此腳本" -ForegroundColor Yellow
    Read-Host "按 Enter 鍵退出"
    exit 1
}

# 檢查端口 8082 是否被占用
$port8082 = Get-NetTCPConnection -LocalPort 8082 -ErrorAction SilentlyContinue
if ($port8082) {
    Write-Host "⚠️ 端口 8082 已被占用" -ForegroundColor Yellow
    Write-Host "🔄 嘗試終止占用進程..." -ForegroundColor Yellow
    
    foreach ($conn in $port8082) {
        try {
            Stop-Process -Id $conn.OwningProcess -Force -ErrorAction SilentlyContinue
            Write-Host "✅ 已終止進程 ID: $($conn.OwningProcess)" -ForegroundColor Green
        } catch {
            Write-Host "⚠️ 無法終止進程 ID: $($conn.OwningProcess)" -ForegroundColor Yellow
        }
    }
    
    # 等待端口釋放
    Start-Sleep -Seconds 2
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

Write-Host ""
Write-Host "🚀 啟動測試 AI 後端服務器..." -ForegroundColor Green
Write-Host "📍 服務器地址: http://localhost:8082" -ForegroundColor Cyan
Write-Host "📄 測試頁面: http://localhost:8082/test-ai-page.html" -ForegroundColor Cyan
Write-Host "🔧 後端檔案: test-ai-backend.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "💡 使用說明:" -ForegroundColor Yellow
Write-Host "   1. 服務器啟動後，開啟瀏覽器訪問測試頁面" -ForegroundColor White
Write-Host "   2. 在編輯器中輸入 Python 代碼" -ForegroundColor White
Write-Host "   3. 點擊 AI 助教按鈕測試功能" -ForegroundColor White
Write-Host "   4. 按 Ctrl+C 停止服務器" -ForegroundColor White
Write-Host ""

# 啟動 PHP 開發服務器
try {
    Write-Host "🔄 正在啟動服務器..." -ForegroundColor Yellow
    php -S localhost:8082
} catch {
    Write-Host "❌ 服務器啟動失敗: $($_.Exception.Message)" -ForegroundColor Red
    Read-Host "按 Enter 鍵退出"
    exit 1
}

Write-Host ""
Write-Host "👋 測試 AI 後端服務器已停止" -ForegroundColor Yellow 