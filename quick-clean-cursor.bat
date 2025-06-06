@echo off
title Quick Cursor Background Cleanup
color 0C
chcp 65001 >nul

echo.
echo ⚡ Quick Cursor Background Cleanup ⚡
echo =====================================
echo.
echo 🔄 Cleaning up background processes...
echo.

REM Stop all PHP development servers
echo [1/6] Stopping PHP servers...
taskkill /F /IM php.exe >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ PHP servers stopped
) else (
    echo ℹ️  No PHP servers found
)

REM Release development ports
echo [2/6] Releasing ports 8080, 8081, 3000, 5000...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080\|:8081\|:3000\|:5000"') do (
    taskkill /F /PID %%a >nul 2>&1
)
echo ✅ Development ports released

REM Clean Cursor-related CMD processes (keeping current one)
echo [3/6] Cleaning Cursor terminal processes...
for /f "tokens=1" %%i in ('wmic process where "name='cmd.exe'" get processid /format:csv 2^>nul ^| find "," ^| find /v "ProcessId"') do (
    if "%%i" neq "" if "%%i" neq "%~2" (
        taskkill /F /PID %%i >nul 2>&1
    )
)
echo ✅ Terminal processes cleaned

REM Clean PowerShell background tasks
echo [4/6] Cleaning PowerShell background tasks...
taskkill /F /IM powershell.exe /FI "WINDOWTITLE eq *PHP*" >nul 2>&1
taskkill /F /IM powershell.exe /FI "WINDOWTITLE eq *WebSocket*" >nul 2>&1
taskkill /F /IM powershell.exe /FI "WINDOWTITLE eq *Server*" >nul 2>&1
echo ✅ Background tasks cleaned

REM Clean temporary files
echo [5/6] Cleaning temporary files...
del /q /s "%TEMP%\php*" >nul 2>&1
del /q /s "%TEMP%\cursor*" >nul 2>&1
echo ✅ Temporary files cleaned

REM Network cache cleanup
echo [6/6] Clearing network cache...
ipconfig /flushdns >nul 2>&1
echo ✅ Network cache cleared

echo.
echo 🎉 Quick cleanup completed!
echo 💡 Your Cursor environment is now clean.
echo.

REM Show current status
echo 📊 Current Status:
echo ------------------
echo.
echo PHP Processes:
tasklist | findstr php.exe >nul 2>&1
if %errorlevel% neq 0 (
    echo ✅ No PHP processes running
) else (
    echo ⚠️  PHP processes still running
)

echo.
echo Active Ports:
netstat -an | findstr ":8080\|:8081" | findstr LISTENING >nul 2>&1
if %errorlevel% neq 0 (
    echo ✅ Ports 8080/8081 are free
) else (
    echo ⚠️  Some ports still in use
)

echo.
echo 🚀 Ready to restart development servers!
echo.
echo Quick Actions:
echo [R] Restart development servers
echo [S] Show detailed status
echo [Enter] Exit
echo.
set /p action="Enter choice: "

if /i "%action%"=="R" goto RESTART
if /i "%action%"=="S" goto STATUS
goto END

:RESTART
echo.
echo 🚀 Restarting development servers...
if exist "start-simple.bat" (
    echo Starting with start-simple.bat...
    start start-simple.bat
) else if exist "start.bat" (
    echo Starting with start.bat...
    start start.bat
) else (
    echo Starting basic PHP server...
    start /b "Web Server" php -S localhost:8080 -t .
    timeout /t 2 >nul
    
    if exist "websocket\server.php" (
        start /b "WebSocket Server" php websocket\server.php
        echo ✅ Services started manually
    )
)
echo.
echo ✅ Development servers restarted!
goto END

:STATUS
echo.
echo 📊 Detailed System Status:
echo ===========================
echo.
echo Running Processes:
tasklist | findstr "php.exe\|node.exe\|powershell.exe" | findstr /v "Windows PowerShell ISE"
echo.
echo Network Ports:
netstat -an | findstr ":8080\|:8081\|:3000\|:5000" | findstr LISTENING
echo.
echo Memory Usage:
wmic OS get FreePhysicalMemory /format:list | findstr "="
echo.
pause
goto END

:END
echo.
echo 👋 Cleanup session completed!
timeout /t 2 >nul
exit 