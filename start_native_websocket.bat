@echo off
echo Starting Native WebSocket Server...
echo Port 8081 is being used for native WebSocket server
echo Starting location: %CD%
echo WebSocket URL: ws://localhost:8081

REM 終止現有的WebSocket服務器進程
for /f "tokens=2" %%i in ('netstat -ano ^| findstr :8081') do (
    taskkill /PID %%i /F >nul 2>&1
)

echo Starting native WebSocket server on port 8081...
php websocket/native_test_server.php
pause 