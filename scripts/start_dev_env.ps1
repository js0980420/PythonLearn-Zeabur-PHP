# -*- coding: utf-8 -*-
# scripts/start_dev_env.ps1
# Python教學多人協作平台 - 開發環境一鍵啟動腳本

# 設定終端機編碼為 UTF-8，確保中文顯示正常
chcp 65001 > $null

Write-Host "🚀 開始啟動開發環境..." -ForegroundColor Green

# 定義專案根目錄 (當前腳本所在的父目錄)
$ProjectRoot = Get-Item -Path $PSScriptRoot | Select-Object -ExpandProperty FullName

# --- 啟動 XAMPP 服務 ---
Write-Host ""
Write-Host "--- 啟動 XAMPP Apache 和 MySQL ---" -ForegroundColor Blue
$xamppPath = "C:\xampp" # 假設 XAMPP 安裝在 C:\xampp

if (Test-Path "$xamppPath\xampp_start.exe") {
    # 啟動 Apache (預設為 8082，如果已配置)
    Write-Host "嘗試啟動 Apache..." -ForegroundColor Cyan
    Start-Process -FilePath "$xamppPath\apache_start.bat" -WindowStyle Hidden -ErrorAction SilentlyContinue

    # 啟動 MySQL
    Write-Host "嘗試啟動 MySQL..." -ForegroundColor Cyan
    Start-Process -FilePath "$xamppPath\mysql_start.bat" -WindowStyle Hidden -ErrorAction SilentlyContinue

    Write-Host "✅ Apache 和 MySQL 啟動指令已發送 (請確認 XAMPP 控制台狀態)" -ForegroundColor Green
} else {
    Write-Host "❌ 未找到 XAMPP 安裝路徑或啟動腳本。請確認 XAMPP 已安裝在 '$xamppPath'。" -ForegroundColor Red
    Write-Host "請手動啟動 XAMPP Apache 和 MySQL。" -ForegroundColor Yellow
}

# --- 啟動主應用程式 (Node.js/NPM) ---
Write-Host ""
Write-Host "--- 啟動主應用程式 (端口 8080) ---" -ForegroundColor Blue

# 檢查 npm install 是否已運行
if (-not (Test-Path "$ProjectRoot\node_modules")) {
    Write-Host "⚠️ node_modules 目錄不存在。正在執行 npm install..." -ForegroundColor Yellow
    Set-Location -Path $ProjectRoot
    npm install --quiet
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ npm install 失敗，無法啟動主應用程式。" -ForegroundColor Red
        Write-Host "請手動檢查並解決 npm install 問題後，重新運行此腳本。" -ForegroundColor Yellow
        exit 1
    }
    Write-Host "✅ npm install 完成。" -ForegroundColor Green
}

# 啟動主應用程式 (在背景運行，輸出將重新導向到 app_output.log)
Write-Host "正在啟動主應用程式..." -ForegroundColor Cyan
Set-Location -Path $ProjectRoot
Start-Process -FilePath "npm.cmd" -ArgumentList "start" -RedirectStandardOutput "$ProjectRoot\app_output.log" -RedirectStandardError "$ProjectRoot\app_error.log" -WindowStyle Hidden -PassThru -ErrorAction SilentlyContinue | Out-Null

Write-Host "✅ 主應用程式啟動指令已發送 (端口 8080)。日誌輸出到 app_output.log 和 app_error.log" -ForegroundColor Green
Write-Host "   您可以在瀏覽器中訪問 http://localhost:8080" -ForegroundColor DarkYellow

Write-Host ""
Write-Host "🚀 開發環境啟動程序完成！" -ForegroundColor Green
Write-Host "注意：XAMPP 服務的啟動可能需要一些時間，請檢查 XAMPP 控制台以確認服務狀態。" -ForegroundColor DarkYellow
Write-Host "按任意鍵結束此腳本..." -ForegroundColor Cyan
[System.Console]::ReadKey() | Out-Null 