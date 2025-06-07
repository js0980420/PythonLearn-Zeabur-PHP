@echo off
chcp 65001 >nul
title 服務管理器

:menu
cls
echo ==========================================
echo 🔧 PythonLearn 服務管理器
echo ==========================================
echo.
echo 📊 當前服務狀態:
netstat -an | findstr ":8080" >nul && echo    🌐 主API服務器 (8080): ✅ 運行中 || echo    🌐 主API服務器 (8080): ❌ 未運行
netstat -an | findstr ":8081" >nul && echo    📡 WebSocket服務器 (8081): ✅ 運行中 || echo    📡 WebSocket服務器 (8081): ❌ 未運行  
netstat -an | findstr ":8082" >nul && echo    🧪 測試服務器 (8082): ✅ 運行中 || echo    🧪 測試服務器 (8082): ❌ 未運行
echo.
echo ==========================================
echo 🎮 操作選項:
echo ==========================================
echo.
echo [1] 🚀 啟動所有服務 (獨立終端機)
echo [2] 🛑 停止所有服務
echo [3] 🔄 重啟所有服務
echo.
echo [4] 📡 單獨管理 WebSocket 服務器
echo [5] 🌐 單獨管理 主API服務器  
echo [6] 🧪 單獨管理 測試服務器
echo.
echo [7] 📋 查看進程詳情
echo [8] 🌍 開啟瀏覽器
echo [0] ❌ 退出
echo.
set /p choice=請選擇操作 (0-8): 

if "%choice%"=="1" goto start_all
if "%choice%"=="2" goto stop_all
if "%choice%"=="3" goto restart_all
if "%choice%"=="4" goto manage_websocket
if "%choice%"=="5" goto manage_api
if "%choice%"=="6" goto manage_test
if "%choice%"=="7" goto show_processes
if "%choice%"=="8" goto open_browser
if "%choice%"=="0" goto exit
goto menu

:start_all
echo.
echo 🚀 啟動所有服務...
call start-services-separate.bat
pause
goto menu

:stop_all
echo.
echo 🛑 停止所有服務...
taskkill /F /IM php.exe 2>nul
echo ✅ 所有PHP服務已停止
pause
goto menu

:restart_all
echo.
echo 🔄 重啟所有服務...
taskkill /F /IM php.exe 2>nul
timeout /t 2 /nobreak >nul
call start-services-separate.bat
pause
goto menu

:manage_websocket
cls
echo ==========================================
echo 📡 WebSocket 服務器管理
echo ==========================================
echo.
netstat -an | findstr ":8081" >nul && echo 狀態: ✅ 運行中 || echo 狀態: ❌ 未運行
echo.
echo [1] 🚀 啟動 WebSocket 服務器
echo [2] 🛑 停止 WebSocket 服務器  
echo [3] 🔄 重啟 WebSocket 服務器
echo [0] ⬅️ 返回主菜單
echo.
set /p ws_choice=請選擇操作: 

if "%ws_choice%"=="1" (
    start "🔌 WebSocket Server - Port 8081" cmd /k "title WebSocket Server && cd /d %~dp0 && php websocket/server.php"
    echo ✅ WebSocket 服務器已啟動
)
if "%ws_choice%"=="2" (
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8081"') do taskkill /F /PID %%a 2>nul
    echo ✅ WebSocket 服務器已停止
)
if "%ws_choice%"=="3" (
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8081"') do taskkill /F /PID %%a 2>nul
    timeout /t 1 /nobreak >nul
    start "🔌 WebSocket Server - Port 8081" cmd /k "title WebSocket Server && cd /d %~dp0 && php websocket/server.php"
    echo ✅ WebSocket 服務器已重啟
)
if "%ws_choice%"=="0" goto menu
pause
goto manage_websocket

:manage_api
cls
echo ==========================================
echo 🌐 主API服務器管理
echo ==========================================
echo.
netstat -an | findstr ":8080" >nul && echo 狀態: ✅ 運行中 || echo 狀態: ❌ 未運行
echo.
echo [1] 🚀 啟動 主API服務器
echo [2] 🛑 停止 主API服務器
echo [3] 🔄 重啟 主API服務器
echo [0] ⬅️ 返回主菜單
echo.
set /p api_choice=請選擇操作: 

if "%api_choice%"=="1" (
    start "🌐 Main API Server - Port 8080" cmd /k "title Main API Server && cd /d %~dp0 && php -S localhost:8080 router.php"
    echo ✅ 主API服務器已啟動
)
if "%api_choice%"=="2" (
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8080"') do taskkill /F /PID %%a 2>nul
    echo ✅ 主API服務器已停止
)
if "%api_choice%"=="3" (
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8080"') do taskkill /F /PID %%a 2>nul
    timeout /t 1 /nobreak >nul
    start "🌐 Main API Server - Port 8080" cmd /k "title Main API Server && cd /d %~dp0 && php -S localhost:8080 router.php"
    echo ✅ 主API服務器已重啟
)
if "%api_choice%"=="0" goto menu
pause
goto manage_api

:manage_test
cls
echo ==========================================
echo 🧪 測試服務器管理
echo ==========================================
echo.
netstat -an | findstr ":8082" >nul && echo 狀態: ✅ 運行中 || echo 狀態: ❌ 未運行
echo.
echo [1] 🚀 啟動 測試服務器
echo [2] 🛑 停止 測試服務器
echo [3] 🔄 重啟 測試服務器
echo [0] ⬅️ 返回主菜單
echo.
set /p test_choice=請選擇操作: 

if "%test_choice%"=="1" (
    start "🧪 Test Server - Port 8082" cmd /k "title Test Server && cd /d %~dp0 && php -S localhost:8082 -t ."
    echo ✅ 測試服務器已啟動
)
if "%test_choice%"=="2" (
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8082"') do taskkill /F /PID %%a 2>nul
    echo ✅ 測試服務器已停止
)
if "%test_choice%"=="3" (
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8082"') do taskkill /F /PID %%a 2>nul
    timeout /t 1 /nobreak >nul
    start "🧪 Test Server - Port 8082" cmd /k "title Test Server && cd /d %~dp0 && php -S localhost:8082 -t ."
    echo ✅ 測試服務器已重啟
)
if "%test_choice%"=="0" goto menu
pause
goto manage_test

:show_processes
cls
echo ==========================================
echo 📋 進程詳情
echo ==========================================
echo.
echo 🔍 PHP 進程:
tasklist | findstr php.exe
echo.
echo 🔍 端口佔用:
netstat -ano | findstr ":808"
echo.
pause
goto menu

:open_browser
echo.
echo 🌍 開啟瀏覽器...
start http://localhost:8080
start http://localhost:8082
pause
goto menu

:exit
echo.
echo 👋 感謝使用服務管理器！
pause
exit 