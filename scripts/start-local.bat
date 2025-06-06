@echo off
chcp 65001 >nul
echo 🚀 啟動 PythonLearn 本地開發環境...
echo.

REM 檢查 PowerShell 是否可用
powershell -Command "Get-Host" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PowerShell 不可用，請確保已安裝 PowerShell
    pause
    exit /b 1
)

REM 設定執行策略
echo 📋 設定 PowerShell 執行策略...
powershell -Command "Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force"

REM 執行 PowerShell 腳本
echo 🔧 啟動本地開發環境...
powershell -ExecutionPolicy Bypass -File "%~dp0start-local.ps1"

if %errorlevel% neq 0 (
    echo.
    echo ❌ 腳本執行失敗，錯誤代碼: %errorlevel%
    echo 💡 請檢查 PowerShell 腳本是否有語法錯誤
    pause
    exit /b %errorlevel%
)

echo.
echo ✅ 腳本執行完成
pause 