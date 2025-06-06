@echo off
chcp 65001 >nul
title PythonLearn 修復版啟動
color 0A

echo.
echo ========================================
echo    PythonLearn 修復版環境啟動
echo ========================================
echo.

echo [1/6] 清理佔用的進程...
taskkill /F /IM php.exe 2>nul
taskkill /F /IM httpd.exe 2>nul
echo    ✅ 進程清理完成

echo [2/6] 等待進程清理完成...
timeout /t 2 /nobreak >nul

echo [3/6] 啟動 PHP 主服務器 (端口 8080)...
start "PHP主服務器" cmd /k "echo PHP主服務器 (localhost:8080) && php -S localhost:8080 router.php"

echo [4/6] 等待主服務器啟動...
timeout /t 3 /nobreak >nul

echo [5/6] 啟動 WebSocket 服務器 (端口 8081)...
start "WebSocket服務器" cmd /k "echo WebSocket服務器 (localhost:8081) && php websocket/server.php"

echo [6/6] 等待 WebSocket 服務器啟動...
timeout /t 5 /nobreak >nul

echo.
echo ========================================
echo           🚀 服務啟動完成！
echo ========================================
echo.
echo 📱 主應用: http://localhost:8080
echo 🔌 WebSocket: ws://localhost:8081
echo 🧪 測試頁面: http://localhost:8080/test-websocket-connection.html
echo.
echo 💡 提示：
echo    - 兩個服務器會在獨立的命令視窗中運行
echo    - 關閉對應視窗可停止相應服務
echo    - 如果遇到端口佔用，請先運行清理腳本
echo.

echo 正在開啟主應用...
start http://localhost:8080

echo.
echo 是否要開啟 WebSocket 測試頁面？ (Y/N)
set /p choice=請選擇: 
if /i "%choice%"=="Y" (
    echo 正在開啟 WebSocket 測試頁面...
    start http://localhost:8080/test-websocket-connection.html
)

echo.
echo ✅ 啟動完成！按任意鍵退出...
pause >nul 