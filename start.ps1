# PythonLearn-Zeabur-PHP 啟動腳本
param(
    [switch]$Clean,
    [switch]$OpenBrowser
)

# 設置編碼
chcp 65001 > $null

Write-Host "啟動 PythonLearn-Zeabur-PHP..." -ForegroundColor Cyan

# 檢查工作目錄
if (-not (Test-Path "router.php")) {
    Write-Host "錯誤: 請在專案根目錄執行" -ForegroundColor Red
    exit 1
}

# 清理舊進程
if ($Clean) {
    Write-Host "清理舊進程..." -ForegroundColor Yellow
    Get-Process -Name "php" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
}

# 啟動服務
Write-Host "啟動 Web 服務器 (8080)..." -ForegroundColor Green
Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8080", "router.php"

Start-Sleep -Seconds 3

Write-Host "啟動 WebSocket 服務器 (8081)..." -ForegroundColor Green
Start-Process -FilePath "php" -ArgumentList "server.php" -WorkingDirectory "websocket"

Start-Sleep -Seconds 3

# 檢查狀態
$web = netstat -ano | findstr ":8080"
$ws = netstat -ano | findstr ":8081"

if ($web -and $ws) {
    Write-Host "服務啟動成功!" -ForegroundColor Green
    Write-Host "Web: http://localhost:8080" -ForegroundColor Cyan
    Write-Host "WebSocket: ws://localhost:8081" -ForegroundColor Cyan
    
    if ($OpenBrowser) {
        Start-Process "http://localhost:8080"
    }
} else {
    Write-Host "服務啟動可能失敗，請檢查" -ForegroundColor Red
} 