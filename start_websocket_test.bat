@echo off
echo 🚀 啟動WebSocket測試服務器
echo ============================

echo.
echo 📍 當前目錄: %CD%
echo.

echo 🔧 切換到websocket目錄...
cd websocket

echo 📝 檢查server.php文件...
if exist server.php (
    echo ✅ server.php 文件存在
) else (
    echo ❌ server.php 文件不存在
    pause
    exit /b 1
)

echo.
echo 🌐 啟動WebSocket服務器...
echo 📋 服務器將運行在 ws://localhost:8080
echo 🔄 按 Ctrl+C 停止服務器
echo.

php server.php

echo.
echo 🛑 WebSocket服務器已停止
pause 