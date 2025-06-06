@echo off
chcp 65001 >nul
title PythonLearn 本地開發環境
color 0A

echo.
echo ========================================
echo    PythonLearn 本地開發環境啟動
echo ========================================
echo.

echo [1/5] 清理佔用的進程...
taskkill /F /IM php.exe 2>nul
taskkill /F /IM httpd.exe 2>nul
taskkill /F /IM mysqld.exe 2>nul

echo [2/5] 等待進程清理完成...
timeout /t 3 /nobreak >nul

echo [3/5] 啟動 PHP 內建服務器 (端口 8080)...
start /B php -S localhost:8080 router.php

echo [4/5] 啟動 WebSocket 服務器 (端口 8081)...
start /B php websocket/server.php

echo [5/5] 等待服務啟動...
timeout /t 5 /nobreak >nul

echo.
echo ========================================
echo           服務啟動完成！
echo ========================================
echo.
echo 主應用: http://localhost:8080
echo WebSocket: ws://localhost:8081
echo.
echo 正在開啟瀏覽器...
start http://localhost:8080

echo.
echo 提示: 關閉此視窗將停止所有服務
echo 按任意鍵退出...
pause >nul

echo.
echo 正在停止服務...
taskkill /F /IM php.exe 2>nul
echo 服務已停止 