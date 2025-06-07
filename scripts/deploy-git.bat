@echo off
chcp 65001 >nul
echo 📦 Git 一鍵部署工具...
echo.

REM 檢查 PowerShell 是否可用
powershell -Command "Get-Host" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PowerShell 不可用，請確保已安裝 PowerShell
    pause
    exit /b 1
)

REM 設定執行策略
powershell -Command "Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force" >nul 2>&1

REM 執行 PowerShell 腳本
echo 🚀 開始 Git 部署流程...
powershell -ExecutionPolicy Bypass -File "%~dp0deploy-git.ps1"

if %errorlevel% neq 0 (
    echo.
    echo ❌ 部署失敗，錯誤代碼: %errorlevel%
    pause
    exit /b %errorlevel%
)

echo.
echo ✅ 部署完成
pause 