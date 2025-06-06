@echo off
echo 🚀 啟動 PythonLearn 服務器...

REM 停止現有的PHP進程
echo 🧹 清理現有服務...
taskkill /f /im php.exe >nul 2>&1

REM 等待進程完全停止
timeout /t 2 /nobreak >nul

REM 啟動MySQL（如果需要）
echo 🗄️  檢查 MySQL...
netstat -an | find ":3306 " >nul
if errorlevel 1 (
    echo ⚠️  MySQL 未運行，請手動啟動 XAMPP MySQL
) else (
    echo ✅ MySQL 已運行
)

REM 啟動Web服務器
echo 🌐 啟動 Web 服務器 (端口 8080)...
start /min "Web Server" php -S localhost:8080 router.php

REM 等待Web服務器啟動
timeout /t 3 /nobreak >nul

REM 啟動WebSocket服務器
echo 🔌 啟動 WebSocket 服務器 (端口 8081)...
start /min "WebSocket Server" php websocket/server.php

REM 等待WebSocket服務器啟動
timeout /t 3 /nobreak >nul

echo.
echo ✅ 服務器啟動完成！
echo 🌐 Web 界面: http://localhost:8080
echo 🔌 WebSocket: ws://localhost:8081
echo.
echo 💡 提示: 使用 stop-servers.bat 來停止所有服務
pause 