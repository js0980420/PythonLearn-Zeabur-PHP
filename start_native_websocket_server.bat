@echo off
echo Starting Native WebSocket Server...

REM 檢查並終止佔用端口 8081 的進程
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8081') do (
    echo Terminating process %%a on port 8081...
    taskkill /f /pid %%a >nul 2>&1
)

echo Starting location: %CD%
echo WebSocket URL: ws://localhost:8081
echo.

REM 啟動原生 WebSocket 服務器
php websocket/server.php

pause 