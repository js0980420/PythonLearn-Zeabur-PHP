@echo off
title Python協作教學平台 - 本地服務器
color 0A

echo.
echo ========================================
echo   🚀 Python協作教學平台 - 本地服務器
echo ========================================
echo.

REM 檢查 XAMPP PHP 是否存在
if not exist "C:\xampp\php\php.exe" (
    echo ❌ 找不到 XAMPP PHP，請確認 XAMPP 已安裝在 C:\xampp\
    echo.
    echo 💡 如果 XAMPP 安裝在其他位置，請修改此腳本中的路徑
    pause
    exit /b 1
)

echo ✅ 找到 XAMPP PHP 8.2.12
echo.

REM 檢查端口是否被占用
netstat -ano | findstr :8080 >nul
if %errorlevel% equ 0 (
    echo ⚠️  端口 8080 已被占用，正在嘗試終止...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8080') do (
        taskkill /PID %%a /F >nul 2>&1
    )
    timeout /t 2 >nul
)

echo 🌐 啟動服務器...
echo.
echo 📍 本地訪問地址:
echo    http://localhost:8080
echo    http://127.0.0.1:8080
echo.
echo 📍 網路訪問地址:
echo    http://192.168.31.59:8080
echo.
echo 🔧 功能說明:
echo    - 多人協作代碼編輯
echo    - AI 助教功能 (需配置 API 密鑰)
echo    - 即時聊天和衝突檢測
echo    - 教師監控面板
echo.
echo ⏹️  按 Ctrl+C 停止服務器
echo ========================================
echo.

REM 啟動 PHP 內建服務器
C:\xampp\php\php.exe -S 0.0.0.0:8080 -t public router.php 