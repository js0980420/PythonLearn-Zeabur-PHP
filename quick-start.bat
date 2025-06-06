@echo off
chcp 65001 >nul
title PythonLearn-Zeabur-PHP 快速啟動

echo.
echo 🚀 PythonLearn-Zeabur-PHP 快速啟動
echo ===============================
echo.

:: 檢查工作目錄
if not exist "router.php" (
    echo ❌ 錯誤: 請在專案根目錄執行此腳本
    echo    當前目錄: %CD%
    pause
    exit /b 1
)

echo ✅ 工作目錄: %CD%
echo.

:: 清理舊進程
echo 🧹 清理舊的 PHP 進程...
taskkill /f /im php.exe >nul 2>&1
timeout /t 2 >nul

:: 啟動 Web 服務器
echo 🌐 啟動 Web 服務器 (端口 8080)...
start "Web Server - PythonLearn" cmd /k "title Web Server - PHP:8080 && php -S localhost:8080 router.php"
timeout /t 3 >nul

:: 啟動 WebSocket 服務器
echo 🔌 啟動 WebSocket 服務器 (端口 8081)...
start "WebSocket Server - PythonLearn" cmd /k "title WebSocket Server - PHP:8081 && cd websocket && php server.php"
timeout /t 3 >nul

:: 檢查服務狀態
echo.
echo ✅ 正在檢查服務狀態...
netstat -ano | findstr ":8080" >nul
if %errorlevel% == 0 (
    echo    ✅ Web 服務器運行中 (端口 8080)
) else (
    echo    ❌ Web 服務器啟動失敗
)

netstat -ano | findstr ":8081" >nul
if %errorlevel% == 0 (
    echo    ✅ WebSocket 服務器運行中 (端口 8081)
) else (
    echo    ❌ WebSocket 服務器啟動失敗
)

echo.
echo 🎉 啟動完成！
echo 📝 注意：請保持開啟的終端機視窗運行
echo.
echo 🌐 Web 介面: http://localhost:8080
echo 🔌 WebSocket: ws://localhost:8081
echo.

:: 詢問是否打開瀏覽器
set /p choice="是否要打開瀏覽器? (y/n): "
if /i "%choice%"=="y" (
    echo 🌍 正在打開瀏覽器...
    start "" http://localhost:8080
)

echo.
echo 💡 提示：
echo    - 關閉任一終端機視窗將停止對應服務
echo    - 或運行 stop.ps1 停止所有服務
echo.
pause 