@echo off
chcp 65001 >nul
title PythonLearn 協作平台 - HTTP 輪詢版

echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║                PythonLearn 協作教學平台                    ║
echo ║                    HTTP 輪詢架構版本                       ║
echo ╚════════════════════════════════════════════════════════════╝
echo.

REM 檢查項目文件
if not exist "public\index.html" (
    echo ❌ 錯誤: 找不到 public\index.html
    echo    請確認您在正確的 PythonLearn 項目目錄中
    echo.
    pause
    exit /b 1
)

REM 檢查PHP
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ 錯誤: 找不到 PHP
    echo    請安裝 PHP 並將其加入系統 PATH
    echo    下載地址: https://windows.php.net/download/
    echo.
    pause
    exit /b 1
)

echo ✅ 找到項目文件
echo ✅ PHP 環境檢查通過
echo.

REM 清理端口8080上的進程
echo 🔧 清理端口 8080...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8080 ^| findstr LISTENING') do (
    echo    終止進程 PID: %%a
    taskkill /F /PID %%a >nul 2>&1
)

echo.
echo 🚀 啟動 PythonLearn 協作平台...
echo.
echo 📡 架構: HTTP 輪詢 (無需 WebSocket)
echo 🌐 訪問地址: http://localhost:8080
echo 💾 資料庫: MySQL (支援多用戶協作)
echo 🔄 實時同步: HTTP 長輪詢技術
echo.
echo ⚡ 功能特色:
echo    • 多人即時協作編程
echo    • AI 助教智能輔導
echo    • 代碼版本管理
echo    • 即時聊天交流
echo    • 衝突智能檢測
echo.
echo 🛑 按 Ctrl+C 停止服務器
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.

REM 啟動PHP服務器
php -S localhost:8080 -t public

echo.
echo �� 服務器已停止
echo.
pause 