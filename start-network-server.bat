@echo off
chcp 65001 >nul
title PythonLearn - 跨設備網路服務器

echo ========================================
echo   🌐 PythonLearn 跨設備網路服務器
echo ========================================
echo.

echo 📊 正在檢測網路配置...
echo.

:: 顯示當前IP地址
echo 🔍 當前電腦的IP地址：
for /f "tokens=2 delims=:" %%i in ('ipconfig ^| findstr /i "IPv4"') do (
    echo     %%i
)
echo.

echo 🚀 啟動支援跨設備訪問的服務器...
echo.
echo 📱 筆電訪問地址：
echo     主要: http://192.168.31.32:8080
echo     備用: http://192.168.56.1:8080
echo     測試: http://192.168.31.32:8080/network-test.html
echo.
echo 🛑 按 Ctrl+C 停止服務器
echo ========================================
echo.

:: 啟動PHP服務器，綁定到所有網路介面
php -S 0.0.0.0:8080 -t public

echo.
echo 服務器已停止
pause