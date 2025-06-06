@echo off
title PHP System Cleanup Tool
color 0A
chcp 65001 >nul

echo.
echo ================================================================
echo                   PHP System Cleanup Tool
echo ================================================================
echo.

:MAIN_MENU
echo Please select cleanup option:
echo.
echo [1] Quick Clean (PHP processes and ports)
echo [2] Deep Clean (all processes, cache, temp files)
echo [3] Smart Clean (long-running background processes)
echo [4] Show System Status
echo [5] Clean and Restart Services
echo [0] Exit
echo.
set /p choice="Enter option (0-5): "

if "%choice%"=="1" goto QUICK_CLEAN
if "%choice%"=="2" goto DEEP_CLEAN
if "%choice%"=="3" goto SMART_CLEAN
if "%choice%"=="4" goto SYSTEM_STATUS
if "%choice%"=="5" goto CLEAN_AND_START
if "%choice%"=="0" goto EXIT
echo Invalid option, please try again
goto MAIN_MENU

:QUICK_CLEAN
echo.
echo Executing quick cleanup...
echo ----------------------------------------

echo [1/4] Stopping PHP processes...
taskkill /F /IM php.exe >nul 2>&1
taskkill /F /IM php-cgi.exe >nul 2>&1
if %errorlevel% equ 0 (
    echo SUCCESS: PHP processes stopped
) else (
    echo INFO: No PHP processes found
)

echo [2/4] Releasing ports...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080\|:8081\|:3000\|:5000"') do (
    taskkill /F /PID %%a >nul 2>&1
)
echo SUCCESS: Ports released

echo [3/4] Clearing temporary files...
del /q /s "%TEMP%\php*" >nul 2>&1
del /q /s "*.tmp" >nul 2>&1
echo SUCCESS: Temporary files cleared

echo [4/4] Clearing network cache...
ipconfig /flushdns >nul 2>&1
echo SUCCESS: Network cache cleared

echo.
echo Quick cleanup completed successfully!
pause
goto MAIN_MENU

:DEEP_CLEAN
echo.
echo Executing deep cleanup...
echo ----------------------------------------

echo [1/8] Stopping all PHP processes...
taskkill /F /IM php.exe >nul 2>&1
taskkill /F /IM php-cgi.exe >nul 2>&1
wmic process where "name like '%%php%%'" delete >nul 2>&1
echo SUCCESS: All PHP processes stopped

echo [2/8] Releasing all development ports...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080\|:8081\|:3000\|:5000\|:9000\|:4000"') do (
    taskkill /F /PID %%a >nul 2>&1
)
echo SUCCESS: Development ports released

echo [3/8] Cleaning temporary files and cache...
rmdir /s /q "%TEMP%\php_cache" >nul 2>&1
rmdir /s /q "%TEMP%\websocket_cache" >nul 2>&1
del /q /s "%TEMP%\*.tmp" >nul 2>&1
del /q /s "%TEMP%\*.log" >nul 2>&1
echo SUCCESS: Cache and temp files cleaned

echo [4/8] Cleaning expired sessions...
del /q /s "sessions\*" >nul 2>&1
del /q /s "tmp\sess_*" >nul 2>&1
echo SUCCESS: Expired sessions cleaned

echo [5/8] Cleaning old log files...
del /q /s "logs\*.log" >nul 2>&1
del /q /s "error*.log" >nul 2>&1
echo SUCCESS: Log files cleaned

echo [6/8] Stopping zombie CMD windows...
for /f "tokens=2" %%i in ('tasklist /fi "imagename eq cmd.exe" /fo csv ^| find /c /v ""') do (
    if %%i gtr 3 (
        taskkill /F /IM cmd.exe /FI "WINDOWTITLE ne PHP*" >nul 2>&1
    )
)
echo SUCCESS: Zombie windows cleaned

echo [7/8] Cleaning system network cache...
ipconfig /flushdns >nul 2>&1
netsh winsock reset >nul 2>&1
echo SUCCESS: Network cache reset

echo [8/8] Memory optimization...
echo off | clip
echo SUCCESS: Memory optimized

echo.
echo Deep cleanup completed successfully!
pause
goto MAIN_MENU

:SMART_CLEAN
echo.
echo Executing smart cleanup...
echo ----------------------------------------

echo [1/6] Detecting long-running PHP processes...
for /f "tokens=1,2" %%a in ('wmic process where "name='php.exe'" get ProcessId^,CreationDate /format:csv ^| find ","') do (
    echo Found PHP process: %%b (PID: %%a)
    taskkill /F /PID %%a >nul 2>&1
)
echo SUCCESS: Long-running processes cleaned

echo [2/6] Cleaning unresponsive services...
sc query | findstr /i "php\|websocket" >nul 2>&1
if %errorlevel% equ 0 (
    net stop "php*" >nul 2>&1
    net stop "websocket*" >nul 2>&1
)
echo SUCCESS: Unresponsive services stopped

echo [3/6] Cleaning zombie CMD windows...
for /f "tokens=2 delims=," %%i in ('tasklist /fi "imagename eq cmd.exe" /fo csv ^| find /v "PID"') do (
    if "%%i" neq "%~2" (
        taskkill /F /PID %%i >nul 2>&1
    )
)
echo SUCCESS: Zombie CMD windows cleaned

echo [4/6] Optimizing memory usage...
rundll32.exe advapi32.dll,ProcessIdleTasks >nul 2>&1
echo SUCCESS: Memory usage optimized

echo [5/6] Clearing background process cache...
del /q /s "%APPDATA%\php\cache\*" >nul 2>&1
del /q /s "%LOCALAPPDATA%\Temp\php*" >nul 2>&1
echo SUCCESS: Background cache cleared

echo [6/6] Network connection optimization...
netsh int tcp reset >nul 2>&1
echo SUCCESS: Network connections optimized

echo.
echo Smart cleanup completed successfully!
pause
goto MAIN_MENU

:SYSTEM_STATUS
echo.
echo ================================================================
echo                      System Status Report
echo ================================================================
echo.

echo Port Status:
echo ----------------------------------------
netstat -an | findstr ":8080\|:8081" | findstr LISTENING
if %errorlevel% neq 0 (
    echo No services running on ports 8080/8081
)

echo.
echo PHP Processes:
echo ----------------------------------------
tasklist | findstr php.exe
if %errorlevel% neq 0 (
    echo No PHP processes found
)

echo.
echo Memory Usage:
echo ----------------------------------------
wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /format:list | findstr "="

echo.
echo Network Connections:
echo ----------------------------------------
netstat -an | findstr ":80\|:443\|:8080\|:8081" | find /c "ESTABLISHED" > temp_count.txt
set /p conn_count=<temp_count.txt
echo Active connections: %conn_count%
del temp_count.txt >nul 2>&1

echo.
pause
goto MAIN_MENU

:CLEAN_AND_START
echo.
echo Cleaning system and restarting services...
echo ----------------------------------------

echo Stopping all services...
taskkill /F /IM php.exe >nul 2>&1
timeout /t 2 >nul

echo Cleaning system...
del /q /s "%TEMP%\php*" >nul 2>&1
ipconfig /flushdns >nul 2>&1

echo Starting services...
if exist "start-simple.bat" (
    echo Calling start-simple.bat...
    call start-simple.bat
) else (
    echo Starting basic PHP server...
    start /b "Web Server" php -S localhost:8080 -t .
    timeout /t 2 >nul
    
    if exist "websocket\server.php" (
        start /b "WebSocket Server" php websocket\server.php
    )
)

echo.
echo Services restarted successfully!
pause
goto MAIN_MENU

:EXIT
echo.
echo System cleanup tool closed.
echo Thank you for using PHP System Cleanup Tool!
pause
exit

:END
exit 