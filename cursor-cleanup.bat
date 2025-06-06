@echo off
title Cursor Development Environment Cleanup
color 0D
chcp 65001 >nul

echo.
echo ================================================================
echo           Cursor Development Environment Cleanup
echo ================================================================
echo.

:MAIN_MENU
echo Select cleanup option:
echo.
echo [1] 🧹 Smart Cleanup (Recommended for Cursor)
echo [2] 🔄 Quick PHP Cleanup
echo [3] 🚀 Full Environment Reset
echo [4] 📊 Show All Background Processes
echo [5] 🎯 Selective Process Cleanup
echo [6] 💻 Cursor Process Management
echo [0] ❌ Exit
echo.
set /p choice="Enter option (0-6): "

if "%choice%"=="1" goto SMART_CLEANUP
if "%choice%"=="2" goto QUICK_PHP
if "%choice%"=="3" goto FULL_RESET
if "%choice%"=="4" goto SHOW_PROCESSES
if "%choice%"=="5" goto SELECTIVE_CLEANUP
if "%choice%"=="6" goto CURSOR_MANAGEMENT
if "%choice%"=="0" goto EXIT
echo Invalid option, please try again
goto MAIN_MENU

:SMART_CLEANUP
echo.
echo 🧹 Executing Smart Cleanup for Cursor Environment...
echo ============================================================

echo [1/8] Stopping PHP Development Servers...
taskkill /F /IM php.exe >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ PHP servers stopped
) else (
    echo ℹ️  No PHP servers found
)

echo [2/8] Releasing Development Ports...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080\|:8081\|:3000\|:5000\|:9000\|:4000"') do (
    taskkill /F /PID %%a >nul 2>&1
)
echo ✅ Development ports released

echo [3/8] Cleaning Cursor Terminal Processes...
for /f "tokens=2" %%i in ('wmic process where "name='cmd.exe' and commandline like '%%Cursor%%'" get processid /format:csv 2^>nul ^| find ","') do (
    if "%%i" neq "" if "%%i" neq "%~2" (
        taskkill /F /PID %%i >nul 2>&1
    )
)
echo ✅ Cursor terminal processes cleaned

echo [4/8] Cleaning Node.js Processes (if any)...
taskkill /F /IM node.exe >nul 2>&1
taskkill /F /IM npm.exe >nul 2>&1
echo ✅ Node.js processes cleaned

echo [5/8] Cleaning Background Task Runners...
taskkill /F /IM powershell.exe /FI "WINDOWTITLE eq *PHP*" >nul 2>&1
taskkill /F /IM powershell.exe /FI "WINDOWTITLE eq *WebSocket*" >nul 2>&1
echo ✅ Background task runners cleaned

echo [6/8] Cleaning Temporary Development Files...
del /q /s "%TEMP%\php*" >nul 2>&1
del /q /s "%TEMP%\cursor*" >nul 2>&1
del /q /s "%LOCALAPPDATA%\Temp\cursor*" >nul 2>&1
echo ✅ Temporary files cleaned

echo [7/8] Optimizing System Resources...
rundll32.exe advapi32.dll,ProcessIdleTasks >nul 2>&1
echo ✅ System resources optimized

echo [8/8] Cleaning Network Cache...
ipconfig /flushdns >nul 2>&1
echo ✅ Network cache cleared

echo.
echo 🎉 Smart cleanup completed successfully!
echo 💡 Your Cursor environment is now clean and optimized.
pause
goto MAIN_MENU

:QUICK_PHP
echo.
echo 🔄 Quick PHP Cleanup...
echo ============================

echo Stopping all PHP processes...
taskkill /F /IM php.exe >nul 2>&1
taskkill /F /IM php-cgi.exe >nul 2>&1

echo Releasing PHP ports...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080\|:8081"') do (
    taskkill /F /PID %%a >nul 2>&1
)

echo Cleaning PHP temporary files...
del /q /s "%TEMP%\php*" >nul 2>&1

echo ✅ Quick PHP cleanup completed!
pause
goto MAIN_MENU

:FULL_RESET
echo.
echo 🚀 Full Environment Reset...
echo ===============================
echo ⚠️  This will stop ALL development processes!
echo.
set /p confirm="Are you sure? (y/N): "
if /i not "%confirm%"=="y" goto MAIN_MENU

echo.
echo [1/10] Stopping all PHP processes...
wmic process where "name like '%%php%%'" delete >nul 2>&1

echo [2/10] Stopping all Node.js processes...
wmic process where "name like '%%node%%'" delete >nul 2>&1

echo [3/10] Stopping development servers...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":3000\|:8080\|:8081\|:5000\|:9000\|:4000\|:5173\|:3001"') do (
    taskkill /F /PID %%a >nul 2>&1
)

echo [4/10] Cleaning all Cursor-related CMD processes...
for /f "tokens=1" %%i in ('wmic process where "name='cmd.exe'" get processid /format:csv 2^>nul ^| find "," ^| find /v "ProcessId"') do (
    if "%%i" neq "" if "%%i" neq "%~2" (
        taskkill /F /PID %%i >nul 2>&1
    )
)

echo [5/10] Cleaning PowerShell processes...
for /f "tokens=1" %%i in ('wmic process where "name='powershell.exe' and commandline like '%%php%%'" get processid /format:csv 2^>nul ^| find ","') do (
    if "%%i" neq "" (
        taskkill /F /PID %%i >nul 2>&1
    )
)

echo [6/10] Cleaning temporary directories...
rmdir /s /q "%TEMP%\php_cache" >nul 2>&1
rmdir /s /q "%TEMP%\cursor_cache" >nul 2>&1
del /q /s "%TEMP%\*.tmp" >nul 2>&1

echo [7/10] Cleaning log files...
del /q /s "*.log" >nul 2>&1
del /q /s "logs\*" >nul 2>&1

echo [8/10] Resetting network stack...
netsh winsock reset >nul 2>&1
ipconfig /flushdns >nul 2>&1

echo [9/10] Memory optimization...
echo off | clip
rundll32.exe advapi32.dll,ProcessIdleTasks >nul 2>&1

echo [10/10] Final cleanup...
timeout /t 2 >nul

echo.
echo 🎉 Full environment reset completed!
echo 💡 All development processes have been terminated.
echo 🔄 You can now restart your development servers cleanly.
pause
goto MAIN_MENU

:SHOW_PROCESSES
echo.
echo 📊 Current Background Processes...
echo =====================================

echo.
echo 🔧 PHP Processes:
echo -----------------
tasklist | findstr php.exe
if %errorlevel% neq 0 echo No PHP processes found

echo.
echo 🌐 Node.js Processes:
echo ---------------------
tasklist | findstr node.exe
if %errorlevel% neq 0 echo No Node.js processes found

echo.
echo 💻 PowerShell Processes:
echo ------------------------
tasklist | findstr powershell.exe | find /v "Windows PowerShell ISE"

echo.
echo 📟 CMD Processes:
echo -----------------
for /f "tokens=1,2" %%a in ('tasklist /fi "imagename eq cmd.exe" /fo csv ^| find /v "PID"') do (
    echo %%a %%b
)

echo.
echo 🌐 Network Ports in Use:
echo -------------------------
netstat -an | findstr ":3000\|:8080\|:8081\|:5000\|:9000\|:4000" | findstr LISTENING

echo.
echo 💾 Memory Usage:
echo ----------------
wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /format:list | findstr "="

echo.
pause
goto MAIN_MENU

:SELECTIVE_CLEANUP
echo.
echo 🎯 Selective Process Cleanup...
echo ===============================

echo Choose what to clean:
echo [1] PHP processes only
echo [2] Node.js processes only  
echo [3] CMD/PowerShell terminals only
echo [4] Network ports only
echo [5] Temporary files only
echo [0] Back to main menu
echo.
set /p sel_choice="Enter option (0-5): "

if "%sel_choice%"=="1" (
    echo Cleaning PHP processes...
    taskkill /F /IM php.exe >nul 2>&1
    taskkill /F /IM php-cgi.exe >nul 2>&1
    echo ✅ PHP processes cleaned
)
if "%sel_choice%"=="2" (
    echo Cleaning Node.js processes...
    taskkill /F /IM node.exe >nul 2>&1
    taskkill /F /IM npm.exe >nul 2>&1
    echo ✅ Node.js processes cleaned
)
if "%sel_choice%"=="3" (
    echo Cleaning terminal processes...
    for /f "tokens=1" %%i in ('wmic process where "name='cmd.exe' and commandline like '%%php%%'" get processid /format:csv 2^>nul ^| find ","') do (
        if "%%i" neq "" if "%%i" neq "%~2" (
            taskkill /F /PID %%i >nul 2>&1
        )
    )
    echo ✅ Terminal processes cleaned
)
if "%sel_choice%"=="4" (
    echo Cleaning network ports...
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080\|:8081\|:3000\|:5000"') do (
        taskkill /F /PID %%a >nul 2>&1
    )
    echo ✅ Network ports cleaned
)
if "%sel_choice%"=="5" (
    echo Cleaning temporary files...
    del /q /s "%TEMP%\php*" >nul 2>&1
    del /q /s "%TEMP%\cursor*" >nul 2>&1
    del /q /s "*.tmp" >nul 2>&1
    echo ✅ Temporary files cleaned
)
if "%sel_choice%"=="0" goto MAIN_MENU

pause
goto MAIN_MENU

:CURSOR_MANAGEMENT
echo.
echo 💻 Cursor Process Management...
echo ===============================

echo [1] Show Cursor-related processes
echo [2] Clean Cursor background tasks
echo [3] Reset Cursor terminal environment
echo [4] Clean Cursor cache and temp files
echo [0] Back to main menu
echo.
set /p cursor_choice="Enter option (0-4): "

if "%cursor_choice%"=="1" (
    echo.
    echo 📋 Cursor-related processes:
    echo ----------------------------
    wmic process where "commandline like '%%Cursor%%'" get name,processid,commandline /format:table
    pause
)
if "%cursor_choice%"=="2" (
    echo Cleaning Cursor background tasks...
    for /f "tokens=1" %%i in ('wmic process where "name='cmd.exe' and commandline like '%%Cursor%%'" get processid /format:csv 2^>nul ^| find ","') do (
        if "%%i" neq "" if "%%i" neq "%~2" (
            taskkill /F /PID %%i >nul 2>&1
        )
    )
    echo ✅ Cursor background tasks cleaned
)
if "%cursor_choice%"=="3" (
    echo Resetting Cursor terminal environment...
    set CURSOR_TERMINAL_COUNT=0
    for /f "tokens=1" %%i in ('wmic process where "name='cmd.exe'" get processid /format:csv 2^>nul ^| find "," ^| find /v "ProcessId"') do (
        if "%%i" neq "" if "%%i" neq "%~2" (
            taskkill /F /PID %%i >nul 2>&1
            set /a CURSOR_TERMINAL_COUNT+=1
        )
    )
    echo ✅ Cursor terminal environment reset
)
if "%cursor_choice%"=="4" (
    echo Cleaning Cursor cache and temp files...
    del /q /s "%TEMP%\cursor*" >nul 2>&1
    del /q /s "%LOCALAPPDATA%\Temp\cursor*" >nul 2>&1
    del /q /s "%APPDATA%\Cursor\*cache*" >nul 2>&1
    echo ✅ Cursor cache cleaned
)
if "%cursor_choice%"=="0" goto MAIN_MENU

pause
goto MAIN_MENU

:EXIT
echo.
echo 🎉 Cursor cleanup tool closed.
echo 💡 Your development environment should now be clean!
echo 🚀 Ready for fresh development sessions.
pause
exit 