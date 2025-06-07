@echo off
echo ========================================
echo   Python 協作學習平台 - 服務器啟動
echo ========================================
echo.

REM 檢查並停止佔用端口的進程
echo 🔍 檢查端口佔用情況...

REM 檢查 8080 端口
netstat -ano | findstr :8080 > nul
if %errorlevel% == 0 (
    echo 🛑 端口 8080 被佔用，正在終止相關進程...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8080') do (
        taskkill /F /PID %%a > nul 2>&1
    )
)

REM 檢查 8081 端口
netstat -ano | findstr :8081 > nul
if %errorlevel% == 0 (
    echo 🛑 端口 8081 被佔用，正在終止相關進程...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8081') do (
        taskkill /F /PID %%a > nul 2>&1
    )
)

echo ✅ 端口清理完成
echo.

echo 🚀 啟動 WebSocket 服務器 (端口: 8081)...
start "WebSocket Server" cmd /k "php test-servers/websocket-test/test_websocket_server.php"

echo ⏳ 等待 WebSocket 服務器啟動...
timeout /t 3 > nul

echo 🌐 啟動前端服務器 (端口: 8080)...
start "Frontend Server" cmd /k "php -S localhost:8080 -t public router.php"

echo ⏳ 等待前端服務器啟動...
timeout /t 2 > nul

echo.
echo ========================================
echo   🎉 服務器啟動完成！
echo ========================================
echo.
echo 📱 前端地址: http://localhost:8080
echo 🔌 WebSocket: ws://localhost:8081
echo 🧪 API 測試: http://localhost:8080/test_api.html
echo.
echo 💡 提示：
echo   - 兩個服務器將在新的命令窗口中運行
echo   - 關閉命令窗口即可停止對應服務器
echo   - 如遇問題，請檢查端口是否被其他程序佔用
echo.

pause 