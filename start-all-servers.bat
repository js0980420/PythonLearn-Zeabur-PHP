@echo off
title Python協作教學平台 - 完整服務器啟動
color 0A

echo.
echo ========================================
echo   🚀 Python協作教學平台 - 完整啟動
echo ========================================
echo.

REM 檢查 XAMPP PHP 是否存在
if not exist "C:\xampp\php\php.exe" (
    echo ❌ 找不到 XAMPP PHP，請確認 XAMPP 已安裝在 C:\xampp\
    pause
    exit /b 1
)

echo ✅ 找到 XAMPP PHP 8.2.12
echo.

REM 終止現有的 PHP 進程
echo 🔄 終止現有的 PHP 進程...
taskkill /F /IM php.exe >nul 2>&1
timeout /t 2 >nul

REM 檢查端口是否被占用
echo 🔍 檢查端口狀態...
netstat -ano | findstr :8080 >nul
if %errorlevel% equ 0 (
    echo ⚠️  端口 8080 已被占用，正在嘗試終止...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8080') do (
        taskkill /PID %%a /F >nul 2>&1
    )
    timeout /t 2 >nul
)

netstat -ano | findstr :8081 >nul
if %errorlevel% equ 0 (
    echo ⚠️  端口 8081 已被占用，正在嘗試終止...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8081') do (
        taskkill /PID %%a /F >nul 2>&1
    )
    timeout /t 2 >nul
)

echo.
echo 🌐 啟動 PHP Web 服務器 (端口 8080)...
start "PHP Web Server" cmd /c "C:\xampp\php\php.exe -S 0.0.0.0:8080 -t public router.php"
timeout /t 3 >nul

echo 🔌 啟動 WebSocket 服務器 (端口 8081)...
start "WebSocket Server" cmd /c "C:\xampp\php\php.exe test-servers/stable-websocket-server.php"
timeout /t 3 >nul

echo.
echo 🔍 檢查服務器狀態...
timeout /t 2 >nul

netstat -ano | findstr :8080 >nul
if %errorlevel% equ 0 (
    echo ✅ PHP Web 服務器已啟動 (端口 8080)
) else (
    echo ❌ PHP Web 服務器啟動失敗
)

netstat -ano | findstr :8081 >nul
if %errorlevel% equ 0 (
    echo ✅ WebSocket 服務器已啟動 (端口 8081)
) else (
    echo ❌ WebSocket 服務器啟動失敗
)

echo.
echo 📍 訪問地址:
echo    本地訪問: http://localhost:8080
echo    網路訪問: http://192.168.31.59:8080
echo.
echo 🔧 功能說明:
echo    - 多人協作代碼編輯
echo    - AI 助教功能
echo    - 實時聊天系統
echo    - 代碼保存/載入
echo    - 教師監控後台
echo.
echo 📝 注意事項:
echo    - 確保防火牆允許端口 8080 和 8081
echo    - WebSocket 連接地址: ws://localhost:8081
echo    - API 端點: http://localhost:8080/api.php
echo.
echo ⏹️ 按任意鍵關閉所有服務器...
pause >nul

echo.
echo 🔄 正在關閉服務器...
taskkill /F /IM php.exe >nul 2>&1
echo ✅ 所有服務器已關閉
pause 