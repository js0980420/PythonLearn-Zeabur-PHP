@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

echo ================================================================
echo          PHP Multi-User Collaboration Platform
echo ================================================================

REM 檢查是否在正確的工作目錄
if not exist "..\router.php" (
    echo ❌ 錯誤: 請確保在正確的專案目錄中運行此腳本
    echo 當前目錄: %CD%
    echo 預期位置: 專案根目錄\scripts\
    pause
    exit /b 1
)

REM 切換到專案根目錄
cd ..

echo [1/5] Checking system status...
call :check_ports
if !ERRORLEVEL! neq 0 (
    echo ERROR: Ports already in use
    echo 請運行 scripts\system-cleanup.bat 清理進程
    pause
    exit /b 1
)
echo SUCCESS: System status OK

echo [2/5] Checking PHP environment...
php --version >nul 2>&1
if !ERRORLEVEL! neq 0 (
    echo ERROR: PHP not found
    echo 請安裝 PHP 8.0+ 並添加到 PATH
    pause
    exit /b 1
)
echo SUCCESS: PHP environment OK

echo [3/5] Checking dependencies...
if not exist "vendor\autoload.php" (
    echo WARNING: Vendor dependencies not found
    echo 正在安裝 Composer 依賴...
    composer install
    if !ERRORLEVEL! neq 0 (
        echo ERROR: Failed to install dependencies
        pause
        exit /b 1
    )
)
echo SUCCESS: Dependencies found

echo [4/5] Starting services...

REM 啟動Web服務器
echo Starting Web Server...
start "Web Server" cmd /c "php -S localhost:8080 router.php"

REM 等待Web服務器啟動
timeout /t 2 >nul

REM 啟動WebSocket服務器
echo Starting WebSocket Server...
start "WebSocket Server" cmd /c "php websocket\server.php"

REM 等待服務器啟動
timeout /t 3 >nul

echo [5/5] Verifying services...

REM 檢查Web服務器
call :check_service "localhost" "8080" "Web Server"
if !ERRORLEVEL! neq 0 (
    echo ERROR: Web Server failed to start
    pause
    exit /b 1
)
echo SUCCESS: Web Server running on port 8080

REM 檢查WebSocket服務器
call :check_service "localhost" "8081" "WebSocket Server"
if !ERRORLEVEL! neq 0 (
    echo ERROR: WebSocket Server failed to start
    pause
    exit /b 1
)
echo SUCCESS: WebSocket Server running on port 8081

echo ================================================================
echo                   Services Started Successfully
echo ================================================================

echo Access URLs:
echo   Student Interface:     http://localhost:8080
echo   Main Page:            http://localhost:8080/index.html
echo   Teacher Dashboard:    http://localhost:8080/teacher-dashboard.html
echo   Health Check:         http://localhost:8080/health

echo System Info:
echo   WebSocket Port:       8081
echo   Web Server Port:      8080
echo   Working Directory:    %CD%

echo Tips:
echo   - Multiple users can collaborate in real-time
echo   - Use teacher dashboard for monitoring
echo   - Check console for WebSocket connection status

echo Choose an option:
echo [1] Open Student Interface in Browser
echo [2] Open Teacher Dashboard in Browser
echo [3] Show System Status
echo [4] Run System Cleanup
echo [5] Stop All Servers
echo [0] Exit

set /p choice="Enter option (0-5): "

if "%choice%"=="1" (
    start http://localhost:8080
    goto :menu_loop
) else if "%choice%"=="2" (
    start http://localhost:8080/teacher-dashboard.html
    goto :menu_loop
) else if "%choice%"=="3" (
    call :show_status
    goto :menu_loop
) else if "%choice%"=="4" (
    call scripts\system-cleanup.bat
    goto :menu_loop
) else if "%choice%"=="5" (
    call :stop_servers
    echo Servers stopped.
    pause
    exit /b 0
) else if "%choice%"=="0" (
    echo Exiting...
    exit /b 0
) else (
    echo Invalid option. Please try again.
    goto :menu_loop
)

:menu_loop
echo.
echo Choose an option:
echo [1] Open Student Interface in Browser
echo [2] Open Teacher Dashboard in Browser
echo [3] Show System Status
echo [4] Run System Cleanup
echo [5] Stop All Servers
echo [0] Exit

set /p choice="Enter option (0-5): "

if "%choice%"=="1" (
    start http://localhost:8080
    goto :menu_loop
) else if "%choice%"=="2" (
    start http://localhost:8080/teacher-dashboard.html
    goto :menu_loop
) else if "%choice%"=="3" (
    call :show_status
    goto :menu_loop
) else if "%choice%"=="4" (
    call scripts\system-cleanup.bat
    goto :menu_loop
) else if "%choice%"=="5" (
    call :stop_servers
    echo Servers stopped.
    pause
    exit /b 0
) else if "%choice%"=="0" (
    echo Exiting...
    exit /b 0
) else (
    echo Invalid option. Please try again.
    goto :menu_loop
)

:check_ports
netstat -ano | findstr ":8080" >nul
if !ERRORLEVEL! equ 0 (
    echo ERROR: Port 8080 is already in use
    exit /b 1
)
netstat -ano | findstr ":8081" >nul
if !ERRORLEVEL! equ 0 (
    echo ERROR: Port 8081 is already in use
    exit /b 1
)
exit /b 0

:check_service
set "host=%~1"
set "port=%~2"
set "service_name=%~3"

for /L %%i in (1,1,10) do (
    powershell -Command "try { $connection = New-Object System.Net.Sockets.TcpClient; $connection.Connect('%host%', %port%); $connection.Close(); exit 0 } catch { exit 1 }" >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        exit /b 0
    )
    timeout /t 1 >nul
)
exit /b 1

:show_status
echo.
echo ============ System Status ============
echo Web Server (8080):
curl -s http://localhost:8080/health 2>nul | findstr "status" || echo "Not responding"

echo.
echo WebSocket Server (8081):
netstat -ano | findstr ":8081" || echo "Not running"

echo.
echo Active PHP Processes:
tasklist | findstr "php.exe" || echo "No PHP processes found"

echo.
echo Port Usage:
netstat -ano | findstr ":808"
echo ======================================
pause
exit /b 0

:stop_servers
echo Stopping all servers...
taskkill /F /IM php.exe >nul 2>&1
timeout /t 2 >nul
echo All PHP processes terminated.
exit /b 0 