@echo off
title WebSocket 測試服務器
color 0A

echo.
echo ========================================
echo    WebSocket 測試服務器啟動器
echo ========================================
echo.

REM 檢查 PHP 是否可用
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP 未安裝或不在 PATH 中
    echo 請確保 PHP 已安裝並添加到系統 PATH
    pause
    exit /b 1
)

echo ✅ PHP 已找到
php --version | findstr "PHP"

echo.
echo 🔍 檢查端口 8081...

REM 檢查端口是否被佔用
netstat -ano | findstr :8081 >nul 2>&1
if %errorlevel% == 0 (
    echo ⚠️ 端口 8081 已被佔用，正在終止現有進程...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8081') do (
        echo 終止進程 PID: %%a
        taskkill /F /PID %%a >nul 2>&1
    )
    timeout /t 2 >nul
    echo ✅ 已清理端口 8081
) else (
    echo ✅ 端口 8081 可用
)

echo.
echo 🚀 啟動 WebSocket 測試服務器...
echo 📍 服務器地址: ws://127.0.0.1:8081
echo 🌐 測試頁面: http://localhost:8080
echo ⏹️  按 Ctrl+C 停止服務器
echo.

REM 切換到 websocket 目錄並啟動服務器
cd /d "%~dp0websocket"
php test_server.php

echo.
echo 服務器已停止
pause 