@echo off
echo 🚀 啟動本地 PHP 協作教學平台...
echo.

REM 檢查 PHP 是否可用
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP 不可用，嘗試使用 Node.js 替代方案...
    goto :nodejs_server
)

echo ✅ 使用 PHP 內建服務器
echo 📍 本地地址: http://localhost:8080
echo 📍 網路地址: http://192.168.31.59:8080
echo.
echo 按 Ctrl+C 停止服務器
php -S 0.0.0.0:8080 -t public router.php
goto :end

:nodejs_server
echo ✅ 使用 Node.js 靜態服務器
echo 📍 本地地址: http://localhost:8080
echo.
REM 檢查是否安裝了 http-server
where http-server >nul 2>&1
if %errorlevel% neq 0 (
    echo 📦 安裝 http-server...
    npm install -g http-server
)

http-server public -p 8080 -c-1

:end
pause 