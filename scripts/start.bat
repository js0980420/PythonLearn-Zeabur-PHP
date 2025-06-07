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
if not exist "vendor" (
    echo WARNING: Vendor directory not found, installing dependencies...
    composer install
)
echo SUCCESS: Dependencies found

echo [4/5] Starting services...

echo.
echo 選擇啟動模式:
echo [1] Caddy 代理模式 (推薦，模擬生產環境)
echo [2] 傳統直連模式 (向後兼容)
echo.
set /p MODE="請選擇模式 (1-2): "

if "%MODE%"=="1" (
    echo.
    echo 🚀 啟動 Caddy 代理模式...
    echo.
    
    REM 檢查 Caddy 是否安裝
    caddy version > nul 2>&1
    if errorlevel 1 (
        echo ❌ 錯誤: Caddy 未安裝
        echo.
        echo 請先安裝 Caddy:
        echo 1. 下載: https://caddyserver.com/download
        echo 2. 或使用 Chocolatey: choco install caddy
        echo 3. 或使用 Scoop: scoop install caddy
        pause
        exit /b 1
    )
    
    echo Starting WebSocket Server...
    start "WebSocket Server" cmd /c "php websocket/server.php"
    timeout /t 2 > nul
    
    echo Starting PHP Server...
    start "PHP Server" cmd /c "php -S localhost:8080 router.php"
    timeout /t 2 > nul
    
    echo Starting Caddy Proxy...
    start "Caddy Proxy" cmd /c "caddy run --config Caddyfile"
    timeout /t 3 > nul
    
    set "MAIN_URL=http://localhost:3000"
    set "WS_INFO=透過 Caddy 代理 (localhost:3000/ws)"
    
) else (
    echo.
    echo 🔧 啟動傳統直連模式...
    echo.
    
    echo Starting WebSocket Server...
    start "WebSocket Server" cmd /c "php websocket/server.php"
    timeout /t 2 > nul
    
    echo Starting PHP Server...
    start "PHP Server" cmd /c "php -S localhost:8080 router.php"
    timeout /t 2 > nul
    
    set "MAIN_URL=http://localhost:8080"
    set "WS_INFO=直連 WebSocket (localhost:8081)"
)

echo [5/5] Verifying services...
timeout /t 3 > nul

REM 檢查服務狀態
curl -s "http://localhost:8080/health" > nul 2>&1
if errorlevel 1 (
    echo WARNING: PHP Server may not be ready yet
) else (
    echo SUCCESS: PHP Server running on port 8080
)

netstat -an | findstr ":8081" > nul
if errorlevel 1 (
    echo WARNING: WebSocket Server may not be ready yet
) else (
    echo SUCCESS: WebSocket Server running on port 8081
)

if "%MODE%"=="1" (
    curl -s "http://localhost:3000" > nul 2>&1
    if errorlevel 1 (
        echo WARNING: Caddy Proxy may not be ready yet
    ) else (
        echo SUCCESS: Caddy Proxy running on port 3000
    )
)

echo ================================================================
echo                   Services Started Successfully
echo ================================================================

echo Access URLs:
echo   Student Interface:     !MAIN_URL!
echo   Main Page:            !MAIN_URL!/index.html
echo   Teacher Dashboard:    !MAIN_URL!/teacher-dashboard.html
echo   Health Check:         !MAIN_URL!/health

echo System Info:
echo   WebSocket:            !WS_INFO!
echo   Web Server Port:      8080
echo   Working Directory:    %CD%

if "%MODE%"=="1" (
    echo   Caddy Proxy Port:     3000
    echo   Architecture:         Caddy Reverse Proxy
) else (
    echo   Architecture:         Direct Connection
)

echo Tips:
echo   - Multiple users can collaborate in real-time
echo   - Use teacher dashboard for monitoring
echo   - Check console for WebSocket connection status

if "%MODE%"=="1" (
    echo   - This mode simulates production environment
    echo   - WebSocket connects via /ws path
) else (
    echo   - This mode is for backward compatibility
    echo   - WebSocket connects directly to port 8081
)

echo Choose an option:
echo [1] Open Student Interface in Browser
echo [2] Open Teacher Dashboard in Browser
echo [3] Show System Status
echo [4] Run System Cleanup
echo [5] Stop All Servers
echo [0] Exit

:menu
set /p choice="Enter option (0-5): "

if "%choice%"=="1" (
    start "" "!MAIN_URL!"
    goto menu
) else if "%choice%"=="2" (
    start "" "!MAIN_URL!/teacher-dashboard.html"
    goto menu
) else if "%choice%"=="3" (
    echo.
    echo ========== System Status ==========
    curl -s "http://localhost:8080/health"
    echo.
    echo ===================================
    goto menu
) else if "%choice%"=="4" (
    start "" "scripts\system-cleanup.bat"
    goto menu
) else if "%choice%"=="5" (
    echo Stopping all servers...
    taskkill /f /im php.exe > nul 2>&1
    taskkill /f /im caddy.exe > nul 2>&1
    echo All servers stopped.
    pause
    exit /b
) else if "%choice%"=="0" (
    exit /b
) else (
    echo Invalid option. Please try again.
    goto menu
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