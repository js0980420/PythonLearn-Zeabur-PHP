@echo off
chcp 65001 >nul
title PythonLearn - 快速啟動

REM 清理端口
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8080 ^| findstr LISTENING') do taskkill /F /PID %%a >nul 2>&1

echo 🚀 PythonLearn 啟動中...
echo 🌐 http://localhost:8080
echo 🛑 Ctrl+C 停止

php -S localhost:8080 -t public 