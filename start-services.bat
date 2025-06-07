@echo off
chcp 65001 >nul
echo 🚀 啟動 PythonLearn 協作平台服務...
echo.

echo 📋 清理舊進程...
taskkill /F /IM php.exe 2>nul
timeout /t 2 /nobreak >nul

echo.
echo 🔧 啟動服務中...
echo.

echo 📡 1. 啟動 WebSocket 服務器 (端口 8081)...
start "WebSocket Server" cmd /k "cd /d %~dp0 && php websocket/server.php"
timeout /t 3 /nobreak >nul

echo 🌐 2. 啟動主 API 服務器 (端口 8080)...
start "Main API Server" cmd /k "cd /d %~dp0 && php -S localhost:8080 router.php"
timeout /t 2 /nobreak >nul

echo 🔍 3. 啟動測試服務器 (端口 8082)...
start "Test Server" cmd /k "cd /d %~dp0 && php -S localhost:8082 -t ."
timeout /t 2 /nobreak >nul

echo.
echo ✅ 所有服務已啟動！
echo.
echo 📊 服務狀態:
echo    🔗 主應用: http://localhost:8080
echo    🧪 測試頁面: http://localhost:8082
echo    📡 WebSocket: ws://localhost:8081
echo.
echo 🌐 正在開啟瀏覽器...
start http://localhost:8080
echo.
echo ⚠️  請保持此視窗開啟以監控服務狀態
pause 