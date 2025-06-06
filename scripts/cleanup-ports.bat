@echo off
chcp 65001 >nul
title 端口清理工具
color 0C

echo.
echo ========================================
echo         🧹 端口清理工具
echo ========================================
echo.

echo 正在檢查並清理佔用的端口...
echo.

echo [1/4] 清理 PHP 進程...
taskkill /F /IM php.exe 2>nul
if %errorlevel%==0 (
    echo    ✅ PHP 進程已清理
) else (
    echo    ℹ️ 沒有發現 PHP 進程
)

echo [2/4] 清理 Apache/httpd 進程...
taskkill /F /IM httpd.exe 2>nul
if %errorlevel%==0 (
    echo    ✅ Apache 進程已清理
) else (
    echo    ℹ️ 沒有發現 Apache 進程
)

echo [3/4] 檢查端口佔用情況...
echo.
echo 🔍 端口 8080 佔用情況:
netstat -ano | findstr :8080
echo.
echo 🔍 端口 8081 佔用情況:
netstat -ano | findstr :8081
echo.

echo [4/4] 強制清理端口佔用...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8080') do (
    if not "%%a"=="0" (
        echo 正在終止佔用端口 8080 的進程 %%a...
        taskkill /F /PID %%a 2>nul
    )
)

for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8081') do (
    if not "%%a"=="0" (
        echo 正在終止佔用端口 8081 的進程 %%a...
        taskkill /F /PID %%a 2>nul
    )
)

echo.
echo ========================================
echo         ✅ 清理完成！
echo ========================================
echo.
echo 現在可以安全地啟動 PythonLearn 服務了
echo.
pause 