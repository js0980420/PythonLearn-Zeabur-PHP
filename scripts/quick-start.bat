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
start "Web Server" cmd /k "php -S localhost:8080 router.php"
timeout /t 3 >nul

:: 啟動 WebSocket 服務器
echo 🔌 啟動 WebSocket 服務器 (端口 8081)...
start "WebSocket Server" cmd /k "cd websocket && php server.php"
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
echo 🎉 服務啟動完成！
echo.
echo 📊 使用方法:
echo    🌐 在瀏覽器打開: http://localhost:8080
echo    🔌 WebSocket 端點: ws://localhost:8081
echo.
echo 💡 提示:
echo    - 兩個命令列視窗已開啟顯示服務器日誌
echo    - 關閉視窗即可停止對應服務器
echo    - 如需完全重啟，請先關閉所有視窗後重新執行此腳本
echo.

:: 自動打開瀏覽器 (可選)
set /p "openBrowser=是否自動打開瀏覽器? (y/n): "
if /i "%openBrowser%"=="y" (
    echo 🌐 正在打開瀏覽器...
    start http://localhost:8080
)

echo.
echo ⏳ 按任意鍵結束此腳本 (服務器將繼續在背景運行)...
pause >nul 