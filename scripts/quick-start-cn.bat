@echo off
:: 设置编码为 UTF-8
chcp 65001 >nul
title PythonLearn-Zeabur-PHP 快速启动 (中文版)

echo.
echo =====================================
echo 🚀 PythonLearn-Zeabur-PHP 快速启动
echo =====================================
echo.

:: 获取脚本所在目录的父目录作为项目根目录
cd /d "%~dp0\.."

:: 检查工作目录
if not exist "router.php" (
    echo ❌ 错误: 找不到 router.php 文件
    echo    当前目录: %CD%
    echo    请确保脚本在正确的位置
    pause
    exit /b 1
)

echo ✅ 工作目录: %CD%
echo.

:: 清理旧进程
echo 🧹 清理旧的 PHP 进程...
taskkill /f /im php.exe >nul 2>&1
timeout /t 2 >nul

:: 启动 Web 服务器
echo 🌐 启动 Web 服务器 (端口 8080)...
start "Web服务器 - PythonLearn" cmd /k "title Web服务器 - PHP:8080 && echo 🌐 Web服务器启动中... && php -S localhost:8080 router.php"
timeout /t 3 >nul

:: 启动 WebSocket 服务器
echo 🔌 启动 WebSocket 服务器 (端口 8081)...
start "WebSocket服务器 - PythonLearn" cmd /k "title WebSocket服务器 - PHP:8081 && echo 🔌 WebSocket服务器启动中... && cd websocket && php server.php"
timeout /t 3 >nul

:: 检查服务状态
echo.
echo ✅ 正在检查服务状态...
netstat -ano | findstr ":8080" >nul
if %errorlevel% == 0 (
    echo    ✅ Web 服务器运行正常 (端口 8080)
) else (
    echo    ❌ Web 服务器启动失败
)

netstat -ano | findstr ":8081" >nul
if %errorlevel% == 0 (
    echo    ✅ WebSocket 服务器运行正常 (端口 8081)
) else (
    echo    ❌ WebSocket 服务器启动失败
)

echo.
echo 🎉 启动完成！
echo 📝 注意：请保持开启的终端窗口运行
echo.
echo 🌐 Web 界面: http://localhost:8080
echo 🔌 WebSocket: ws://localhost:8081
echo.

:: 询问是否打开浏览器
set /p choice="是否要打开浏览器? (y/n): "
if /i "%choice%"=="y" (
    echo 🌍 正在打开浏览器...
    start "" http://localhost:8080
)

echo.
echo 💡 提示:
echo    - 关闭任一终端窗口将停止对应服务
echo    - 或运行 scripts\stop-cn.ps1 停止所有服务
echo.
echo =====================================
pause 