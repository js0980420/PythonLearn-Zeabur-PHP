@echo off
chcp 65001 >nul
title PythonLearn 完整服務啟動
color 0A

echo.
echo ========================================
echo    🚀 PythonLearn 完整服務啟動
echo ========================================
echo.

echo [1/7] 清理舊進程...
taskkill /F /IM php.exe 2>nul
taskkill /F /IM httpd.exe 2>nul
echo    ✅ 進程清理完成

echo [2/7] 等待進程清理...
timeout /t 2 /nobreak >nul

echo [3/7] 修復數據庫結構...
php fix-database-structure.php >nul 2>&1
echo    ✅ 數據庫結構檢查完成

echo [4/7] 啟動 PHP 主服務器 (端口 8080)...
start "PHP主服務器" cmd /k "title PHP主服務器 && echo 🌐 PHP主服務器運行中 (localhost:8080) && php -S localhost:8080 router.php"

echo [5/7] 等待主服務器啟動...
timeout /t 3 /nobreak >nul

echo [6/7] 啟動 WebSocket 服務器 (端口 8081)...
start "WebSocket服務器" cmd /k "title WebSocket服務器 && echo 🔌 WebSocket服務器運行中 (localhost:8081) && php websocket/server.php"

echo [7/7] 等待 WebSocket 服務器啟動...
timeout /t 5 /nobreak >nul

echo.
echo ========================================
echo           🎉 所有服務已啟動！
echo ========================================
echo.
echo 📱 主應用: http://localhost:8080
echo 🔌 WebSocket: ws://localhost:8081
echo 🧪 測試頁面: http://localhost:8080/test-websocket-connection.html
echo.
echo 💡 提示:
echo    - 關閉對應的命令視窗將停止該服務
echo    - 如果遇到問題，請先運行 cleanup-ports.bat
echo.

echo 正在開啟瀏覽器...
start http://localhost:8080

echo.
echo 按任意鍵退出此視窗 (服務將繼續運行)...
pause >nul 