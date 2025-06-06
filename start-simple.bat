@echo off
title PHP Collaboration Platform - Quick Start
color 0B
chcp 65001 >nul

echo.
echo ================================================================
echo          PHP Multi-User Collaboration Platform
echo ================================================================
echo.

REM Error handling
setlocal EnableDelayedExpansion

echo [1/5] Checking system status...
netstat -an | findstr ":8080\|:8081" | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo WARNING: Ports in use, cleaning up...
    echo.
    
    echo    Stopping PHP processes...
    taskkill /F /IM php.exe >nul 2>&1
    timeout /t 1 >nul
    
    echo    Releasing ports...
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080\|:8081"') do (
        taskkill /F /PID %%a >nul 2>&1
    )
    timeout /t 1 >nul
    
    echo SUCCESS: Cleanup completed
) else (
    echo SUCCESS: System status OK
)

echo [2/5] Checking PHP environment...
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP not found or not in PATH
    echo Please install PHP 8.0+ and add to PATH
    pause
    exit /b 1
)
echo SUCCESS: PHP environment OK

echo [3/5] Checking dependencies...
if not exist "composer.json" (
    echo WARNING: composer.json not found
) else (
    echo SUCCESS: Dependencies found
)

echo [4/5] Starting services...
echo Starting Web Server...
start /b "Web Server" php -S localhost:8080 -t .
timeout /t 2 >nul

echo Starting WebSocket Server...
start /b "WebSocket Server" php websocket/server.php
timeout /t 2 >nul

echo [5/5] Verifying services...
timeout /t 3 >nul

REM Check if services are running
netstat -an | findstr ":8080" | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo SUCCESS: Web Server running on port 8080
) else (
    echo ERROR: Web Server failed to start
)

netstat -an | findstr ":8081" | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo SUCCESS: WebSocket Server running on port 8081
) else (
    echo WARNING: WebSocket Server may not be running
)

echo.
echo ================================================================
echo                    Services Started Successfully
echo ================================================================
echo.
echo Access URLs:
echo   Student Interface:     http://localhost:8080
echo   Main Page:            http://localhost:8080/index.html
echo   Teacher Dashboard:    http://localhost:8080/teacher-dashboard.html
echo   Health Check:         http://localhost:8080/health
echo.
echo System Info:
echo   WebSocket Port:       8081
echo   Web Server Port:      8080
echo   Working Directory:    %CD%
echo.
echo Tips:
echo   - Multiple users can collaborate in real-time
echo   - Use teacher dashboard for monitoring
echo   - Check console for WebSocket connection status
echo.
echo Choose an option:
echo [1] Open Student Interface in Browser
echo [2] Open Teacher Dashboard in Browser
echo [3] Show System Status
echo [4] Run System Cleanup
echo [5] Stop All Servers
echo [0] Exit
echo.
set /p choice="Enter option (0-5): "

if "%choice%"=="1" goto OPEN_STUDENT
if "%choice%"=="2" goto OPEN_TEACHER
if "%choice%"=="3" goto SHOW_STATUS
if "%choice%"=="4" goto CLEANUP
if "%choice%"=="5" goto STOP_SERVERS
if "%choice%"=="0" goto END
echo Invalid option, please try again
goto CHOOSE_OPTION

:OPEN_STUDENT
echo Opening Student Interface...
start http://localhost:8080
goto END

:OPEN_TEACHER
echo Opening Teacher Dashboard...
start http://localhost:8080/teacher-dashboard.html
goto END

:SHOW_STATUS
echo.
echo ================================================================
echo                        System Status
echo ================================================================
echo.
echo Checking ports...
netstat -an | findstr ":8080\|:8081" | findstr LISTENING
echo.
echo PHP processes...
tasklist | findstr php.exe
echo.
pause
goto CHOOSE_OPTION

:CLEANUP
echo Running system cleanup...
if exist "system-cleanup.bat" (
    call system-cleanup.bat
) else (
    echo Stopping PHP processes...
    taskkill /F /IM php.exe >nul 2>&1
    echo Cleanup completed
)
goto CHOOSE_OPTION

:STOP_SERVERS
echo Stopping all servers...
taskkill /F /IM php.exe >nul 2>&1
echo All servers stopped
pause
goto END

:CHOOSE_OPTION
echo.
echo Choose an option:
echo [1] Open Student Interface in Browser
echo [2] Open Teacher Dashboard in Browser
echo [3] Show System Status
echo [4] Run System Cleanup
echo [5] Stop All Servers
echo [0] Exit
echo.
set /p choice="Enter option (0-5): "

if "%choice%"=="1" goto OPEN_STUDENT
if "%choice%"=="2" goto OPEN_TEACHER
if "%choice%"=="3" goto SHOW_STATUS
if "%choice%"=="4" goto CLEANUP
if "%choice%"=="5" goto STOP_SERVERS
if "%choice%"=="0" goto END
echo Invalid option, please try again
goto CHOOSE_OPTION

:END
echo.
echo Thank you for using PHP Collaboration Platform!
echo.
pause 