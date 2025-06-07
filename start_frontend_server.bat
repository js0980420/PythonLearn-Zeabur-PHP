@echo off
echo Starting Frontend Server...
echo.

REM Check if port 8080 is occupied
netstat -ano | findstr :8080 > nul
if %errorlevel% == 0 (
    echo Port 8080 is occupied, terminating existing process...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8080') do (
        taskkill /F /PID %%a > nul 2>&1
    )
    timeout /t 2 > nul
)

echo Starting location: %cd%
echo Frontend URL: http://localhost:8080
echo WebSocket connection: ws://localhost:8081
echo Using router.php for API routing
echo.
echo Please make sure WebSocket server is running on port 8081
echo.

REM Start PHP built-in server with router for API handling
php -S localhost:8080 -t public router.php

pause 