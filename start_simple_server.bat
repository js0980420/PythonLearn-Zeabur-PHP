@echo off
echo 🚀 啟動簡化 WebSocket 服務器...
echo.

REM 檢查是否有現有的服務器在運行
netstat -ano | findstr :8081 > nul
if %errorlevel% == 0 (
    echo ⚠️ 端口 8081 已被佔用，正在終止現有進程...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8081') do (
        taskkill /F /PID %%a > nul 2>&1
    )
    timeout /t 2 > nul
)

echo 📍 啟動位置: %cd%
echo 📍 服務器地址: ws://127.0.0.1:8081
echo.

REM 切換到 websocket 目錄並啟動服務器
cd /d "%~dp0websocket"
php simple_server.php

pause 