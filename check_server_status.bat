@echo off
echo 🔍 檢查原生 WebSocket 服務器狀態...
echo.

echo 📡 檢查端口 8081 狀態:
netstat -ano | findstr :8081
echo.

echo 🔧 檢查 PHP 進程:
tasklist | findstr php.exe
echo.

echo 📝 檢查日誌文件:
if exist "websocket\native_websocket.log" (
    echo ✅ 日誌文件存在
    echo 📄 最後 10 行日誌:
    powershell "Get-Content 'websocket\native_websocket.log' -Tail 10"
) else (
    echo ❌ 日誌文件不存在
)
echo.

echo 🧪 測試頁面:
echo - test_simple_connection.html (簡單測試)
echo - test_native_websocket.html (完整測試)
echo.

pause 