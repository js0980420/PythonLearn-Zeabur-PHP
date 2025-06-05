@echo off
echo 🚀 Python 教學多人協作平台 - 純 PHP 版本
echo ================================================

REM 檢查 PHP 是否可用
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ 錯誤: 找不到 PHP，請確保 PHP 已安裝並在 PATH 中
    pause
    exit /b 1
)

echo ✅ PHP 已就緒

REM 檢查 Composer 依賴
if not exist vendor\autoload.php (
    echo ❌ 錯誤: 未找到 vendor\autoload.php
    echo 請運行: composer install
    pause
    exit /b 1
)

echo ✅ Composer 依賴已就緒

REM 創建必要目錄
if not exist data mkdir data
if not exist data\rooms mkdir data\rooms
if not exist logs mkdir logs

echo 📁 目錄結構已就緒

echo.
echo 🚀 啟動服務器...
echo ================================

REM 在後台啟動 WebSocket 服務器
echo 📡 啟動 WebSocket 服務器 (端口 8080)...
start /B php websocket\server.php

REM 等待 WebSocket 服務器啟動
timeout /t 3 /nobreak >nul

echo 🌐 啟動 Web 服務器 (端口 8000)...
echo.
echo 🌟 服務器已啟動！
echo ================================
echo 📱 Web 界面: http://localhost:8000
echo 📡 WebSocket: ws://localhost:8080
echo 💊 健康檢查: http://localhost:8000/backend/api/health.php
echo 🎓 教師後台: http://localhost:8000/teacher-dashboard.html
echo.
echo 按 Ctrl+C 停止服務器
echo ================================

REM 啟動 PHP 內建 Web 服務器
php -S localhost:8000 