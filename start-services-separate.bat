@echo off
chcp 65001 >nul
echo 🚀 啟動 PythonLearn 協作平台 - 獨立終端機模式
echo.

echo 📋 清理舊進程...
taskkill /F /IM php.exe 2>nul
timeout /t 2 /nobreak >nul

echo.
echo 🔧 啟動服務中 (每個服務獨立終端機)...
echo.

echo 📡 1. 啟動 WebSocket 服務器 (端口 8081)...
start "🔌 WebSocket Server - Port 8081" cmd /k "title WebSocket Server && echo 📡 WebSocket 服務器啟動中... && echo 🔗 端口: 8081 && echo 📊 功能: 即時協作、衝突檢測、聊天 && echo. && cd /d %~dp0 && php websocket/server.php"
timeout /t 3 /nobreak >nul

echo 🌐 2. 啟動主 API 服務器 (端口 8080)...
start "🌐 Main API Server - Port 8080" cmd /k "title Main API Server && echo 🌐 主 API 服務器啟動中... && echo 🔗 端口: 8080 && echo 📊 功能: 主應用、API路由、用戶界面 && echo 🌍 訪問: http://localhost:8080 && echo. && cd /d %~dp0 && php -S localhost:8080 router.php"
timeout /t 2 /nobreak >nul

echo 🧪 3. 啟動測試服務器 (端口 8082)...
start "🧪 Test Server - Port 8082" cmd /k "title Test Server && echo 🧪 測試服務器啟動中... && echo 🔗 端口: 8082 && echo 📊 功能: 測試頁面、調試工具 && echo 🌍 訪問: http://localhost:8082 && echo. && cd /d %~dp0 && php -S localhost:8082 -t ."
timeout /t 2 /nobreak >nul

echo.
echo ✅ 所有服務已啟動！每個服務使用獨立終端機
echo.
echo 📊 服務狀態:
echo    🔌 WebSocket: ws://localhost:8081 (獨立終端機)
echo    🌐 主應用: http://localhost:8080 (獨立終端機)  
echo    🧪 測試頁面: http://localhost:8082 (獨立終端機)
echo.
echo 💡 優點:
echo    ✅ 每個服務日誌獨立，便於調試
echo    ✅ 可單獨重啟某個服務
echo    ✅ 資源使用情況清晰可見
echo    ✅ 錯誤定位更精確
echo.
echo 🌐 正在開啟瀏覽器...
start http://localhost:8080
echo.
echo ⚠️  管理提示:
echo    - 每個終端機標題顯示服務類型
echo    - 關閉某個終端機只會停止對應服務
echo    - 建議保持所有終端機開啟以監控狀態
pause 