@echo off
echo 🚀 Python多人協作教學平台 - 本地開發環境啟動
echo ================================================

REM 檢查PHP是否安裝
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ 錯誤: 未找到PHP，請先安裝PHP 8.0+
    pause
    exit /b 1
)

echo ✅ PHP版本檢查通過

REM 檢查端口8080是否被占用
netstat -an | findstr :8080 >nul
if %errorlevel% equ 0 (
    echo ⚠️  警告: 端口8080已被占用，正在嘗試終止相關進程...
    taskkill /F /IM php.exe >nul 2>&1
    timeout /t 2 >nul
)

REM 檢查端口8081是否被占用
netstat -an | findstr :8081 >nul
if %errorlevel% equ 0 (
    echo ⚠️  警告: 端口8081已被占用，正在嘗試終止相關進程...
    taskkill /F /IM php.exe >nul 2>&1
    timeout /t 2 >nul
)

REM 安裝Composer依賴 (如果需要)
if exist composer.json (
    if not exist vendor (
        echo 📦 安裝Composer依賴...
        composer install --no-dev
    )
)

echo 🔌 啟動WebSocket服務器 (端口8081)...
start "WebSocket Server" cmd /k "php websocket/server.php"

REM 等待WebSocket服務器啟動
timeout /t 3 >nul

echo 🌐 啟動Web服務器 (端口8080)...
start "Web Server" cmd /k "php -S localhost:8080 router.php"

REM 等待服務器啟動
timeout /t 3 >nul

echo 🔍 檢查服務器狀態...
curl -s http://localhost:8080/health >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ 服務器啟動成功！
    echo.
    echo 📱 訪問地址:
    echo    學生端: http://localhost:8080
    echo    教師後台: http://localhost:8080/teacher-dashboard.html
    echo    健康檢查: http://localhost:8080/health
    echo.
    echo 🛑 按任意鍵停止服務器...
    pause >nul
    
    echo 🔄 正在停止服務器...
    taskkill /F /IM php.exe >nul 2>&1
    echo ✅ 服務器已停止
) else (
    echo ❌ 服務器啟動失敗，請檢查錯誤信息
    pause
)

echo.
echo 👋 感謝使用Python多人協作教學平台！
pause 