@echo off
title Python Collaboration Platform - Quick Start
color 0B

echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║            🚀 Python多人協作教學平台 - 一鍵啟動工具            ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.

REM 設置錯誤處理
setlocal EnableDelayedExpansion

REM 檢查是否需要清理
echo [1/5] 檢查系統狀態...
netstat -an | findstr ":8080\|:8081" | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo ⚠️  檢測到端口占用，執行自動清理...
    echo.
    
    echo    🔄 停止現有PHP進程...
    taskkill /F /IM php.exe >nul 2>&1
    timeout /t 1 >nul
    
    echo    🔄 釋放占用端口...
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080\|:8081"') do (
        taskkill /F /PID %%a >nul 2>&1
    )
    timeout /t 1 >nul
    
    echo ✅ 清理完成
) else (
    echo ✅ 系統狀態正常
)

echo [2/5] 檢查PHP環境...
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ 錯誤: 未找到PHP，請先安裝PHP 8.0+
    echo.
    echo 🔧 解決方案:
    echo    1. 下載並安裝 PHP 8.0+ 
    echo    2. 將PHP添加到系統PATH環境變量
    echo    3. 重新運行此腳本
    echo.
    pause
    exit /b 1
)
echo ✅ PHP環境檢查通過

echo [3/5] 檢查項目依賴...
if exist composer.json (
    if not exist vendor (
        echo 📦 安裝Composer依賴...
        composer install --no-dev --quiet
        if %errorlevel% neq 0 (
            echo ❌ Composer依賴安裝失敗
            echo 請手動執行: composer install
            pause
            exit /b 1
        )
        echo ✅ 依賴安裝完成
    ) else (
        echo ✅ 依賴已安裝
    )
) else (
    echo ℹ️  未找到composer.json，跳過依賴檢查
)

echo [4/5] 創建必要目錄...
if not exist sessions mkdir sessions >nul 2>&1
if not exist logs mkdir logs >nul 2>&1
if not exist temp mkdir temp >nul 2>&1
echo ✅ 目錄結構準備完成

echo [5/5] 啟動服務器...
echo.

REM 啟動WebSocket服務器
echo 🔌 啟動WebSocket服務器 (端口8081)...
start "🔌 WebSocket Server - Port 8081" cmd /c "title WebSocket Server ^& color 0E ^& echo WebSocket服務器啟動中... ^& echo 監聽端口: 8081 ^& echo 按Ctrl+C停止服務器 ^& echo. ^& php websocket/server.php ^& pause"

REM 等待WebSocket服務器啟動
echo    ⏳ 等待WebSocket服務器啟動...
timeout /t 3 >nul

REM 檢查WebSocket是否成功啟動
netstat -an | findstr :8081 | findstr LISTENING >nul
if %errorlevel% neq 0 (
    echo ❌ WebSocket服務器啟動失敗
    echo 請檢查websocket/server.php文件是否存在
    pause
    exit /b 1
)
echo ✅ WebSocket服務器啟動成功

REM 啟動Web服務器
echo 🌐 啟動Web服務器 (端口8080)...
start "🌐 Web Server - Port 8080" cmd /c "title Web Server ^& color 0A ^& echo Web服務器啟動中... ^& echo 監聽端口: 8080 ^& echo 文檔根目錄: %CD% ^& echo 按Ctrl+C停止服務器 ^& echo. ^& php -S localhost:8080 router.php ^& pause"

REM 等待Web服務器啟動
echo    ⏳ 等待Web服務器啟動...
timeout /t 3 >nul

REM 檢查Web服務器是否成功啟動
netstat -an | findstr :8080 | findstr LISTENING >nul
if %errorlevel% neq 0 (
    echo ❌ Web服務器啟動失敗
    echo 請檢查router.php文件是否存在
    pause
    exit /b 1
)
echo ✅ Web服務器啟動成功

echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║                        🎉 平台啟動成功！                       ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.
echo 📱 訪問地址:
echo    💻 學生編程界面:     http://localhost:8080
echo    💻 或直接訪問:       http://localhost:8080/index.html
echo    👨‍🏫 教師監控後台:     http://localhost:8080/teacher-dashboard.html
echo    🔍 系統健康檢查:     http://localhost:8080/health
echo.
echo 🔧 系統信息:
echo    🔌 WebSocket端口:    8081
echo    🌐 Web服務端口:      8080
echo    📁 項目根目錄:       %CD%
echo.
echo 💡 使用提示:
echo    • 兩個服務器視窗會自動開啟，請保持運行
echo    • 學生可以直接訪問主頁面進行編程
echo    • 教師可以通過後台監控所有學生活動
echo    • 支持多人實時協作和聊天功能
echo.
echo ⚠️  重要提醒:
echo    • 請不要關閉WebSocket和Web服務器視窗
echo    • 如需重啟，請先關閉所有服務器視窗再重新運行
echo    • 遇到問題可運行 system-cleanup.bat 進行系統清理
echo.

REM 提供選項
echo 請選擇下一步操作:
echo [1] 🌐 在瀏覽器中打開學生界面
echo [2] 👨‍🏫 在瀏覽器中打開教師後台
echo [3] 📊 查看系統狀態
echo [4] 🧹 執行系統清理
echo [5] 🛑 停止所有服務器
echo [0] ⏹️  保持運行並退出腳本
echo.
set /p choice="輸入選項 (0-5): "

if "%choice%"=="1" (
    echo 🌐 正在打開學生界面...
    start http://localhost:8080
    goto KEEP_RUNNING
)
if "%choice%"=="2" (
    echo 👨‍🏫 正在打開教師後台...
    start http://localhost:8080/teacher-dashboard.html
    goto KEEP_RUNNING
)
if "%choice%"=="3" (
    call :SHOW_STATUS
    goto KEEP_RUNNING
)
if "%choice%"=="4" (
    echo 🧹 啟動系統清理工具...
    start system-cleanup.bat
    goto KEEP_RUNNING
)
if "%choice%"=="5" goto STOP_SERVERS
if "%choice%"=="0" goto KEEP_RUNNING

echo ❌ 無效選項，默認保持運行
goto KEEP_RUNNING

:SHOW_STATUS
echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║                        📊 系統狀態報告                         ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.

echo 🔍 服務器狀態:
netstat -an | findstr :8080 | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo    ✅ Web服務器 (8080) - 運行中
) else (
    echo    ❌ Web服務器 (8080) - 未運行
)

netstat -an | findstr :8081 | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo    ✅ WebSocket服務器 (8081) - 運行中
) else (
    echo    ❌ WebSocket服務器 (8081) - 未運行
)

echo.
echo 🔍 PHP進程狀態:
for /f %%a in ('tasklist /FI "IMAGENAME eq php.exe" ^| find /c "php.exe"') do set php_count=%%a
echo    📈 運行中的PHP進程: !php_count! 個

echo.
echo 🔍 連接狀態:
for /f %%a in ('netstat -an ^| findstr "ESTABLISHED.*:80" ^| find /c /v ""') do set conn_count=%%a
echo    🔗 活躍連接數: !conn_count! 個

echo.
echo 按任意鍵返回...
pause >nul
return

:STOP_SERVERS
echo.
echo 🛑 正在停止所有服務器...
echo.
echo    🔄 停止PHP進程...
taskkill /F /IM php.exe >nul 2>&1
if %errorlevel% equ 0 (
    echo    ✅ PHP進程已停止
) else (
    echo    ℹ️  無PHP進程需要停止
)

echo    🔄 釋放端口...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080\|:8081"') do (
    taskkill /F /PID %%a >nul 2>&1
)
echo    ✅ 端口已釋放

timeout /t 2 >nul
echo.
echo ✅ 所有服務器已停止
goto EXIT

:KEEP_RUNNING
echo.
echo ✅ 服務器將繼續在後台運行
echo 💡 如需停止服務器，請關閉相應的CMD視窗或重新運行此腳本選擇停止選項
echo.

:EXIT
echo.
echo 👋 感謝使用Python多人協作教學平台！
echo 🌟 如有問題，請查看項目文檔或聯繫技術支援
echo.
timeout /t 3 >nul
endlocal
exit /b 0 