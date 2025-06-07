@echo off
echo 🎯 PythonLearn 服務器狀態檢查
echo ================================

REM 檢查MySQL
echo 🗄️  MySQL (端口 3306):
netstat -an | find ":3306 " >nul
if errorlevel 1 (
    echo ❌ 未運行
) else (
    echo ✅ 運行中
)

REM 檢查Web服務器
echo 🌐 Web 服務器 (端口 8080):
netstat -an | find ":8080 " >nul
if errorlevel 1 (
    echo ❌ 未運行
) else (
    echo ✅ 運行中 - http://localhost:8080
)

REM 檢查WebSocket服務器
echo 🔌 WebSocket 服務器 (端口 8081):
netstat -an | find ":8081 " >nul
if errorlevel 1 (
    echo ❌ 未運行
) else (
    echo ✅ 運行中 - ws://localhost:8081
)

echo ================================
pause 