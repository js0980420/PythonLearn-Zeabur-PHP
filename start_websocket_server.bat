@echo off
echo Starting WebSocket Server...
echo.

REM Check if port 8081 is occupied
netstat -ano | findstr :8081 > nul
if %errorlevel% == 0 (
    echo Port 8081 is occupied, terminating existing process...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8081') do (
        taskkill /F /PID %%a > nul 2>&1
    )
    timeout /t 2 > nul
)

echo Starting location: %cd%
echo WebSocket URL: ws://localhost:8081
echo Using: Test WebSocket Server with full collaboration features
echo.

REM Start the working test server
php test-servers/websocket-test/test_websocket_server.php

pause 