@echo off
chcp 65001 >nul
echo.
echo 🧪 ========================================
echo    PythonLearn 測試服務器啟動管理器
echo ========================================
echo.

:: 檢查PHP是否可用
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ PHP 未安裝或未加入 PATH
    echo 請確保 XAMPP 的 PHP 已正確安裝
    pause
    exit /b 1
)

:: 創建測試日誌目錄
if not exist "test-logs" mkdir test-logs
if not exist "test-reports" mkdir test-reports

echo 📋 可用的測試服務器：
echo.
echo [1] 🔌 API 測試服務器 (端口: 9081)
echo [2] 🌐 WebSocket 測試服務器 (端口: 9082)
echo [3] 📝 前端測試頁面 (端口: 9083)
echo [4] 🚀 啟動所有測試服務器
echo [5] 🔄 整合測試 (主服務器 + 測試)
echo [6] 📊 查看測試報告
echo [7] 🧹 清理測試環境
echo [0] ❌ 退出
echo.

set /p choice="請選擇要啟動的服務器 (0-7): "

if "%choice%"=="1" goto start_api_test
if "%choice%"=="2" goto start_websocket_test
if "%choice%"=="3" goto start_frontend_test
if "%choice%"=="4" goto start_all_tests
if "%choice%"=="5" goto start_integration_test
if "%choice%"=="6" goto show_reports
if "%choice%"=="7" goto cleanup_tests
if "%choice%"=="0" goto exit
goto invalid_choice

:start_api_test
echo.
echo 🔌 啟動 API 測試服務器...
echo 📍 URL: http://localhost:9081
echo 📝 日誌: test-logs/api_test.log
echo.
start "API測試服務器" cmd /k "cd test-servers\api-test && php -S localhost:9081 test_api_server.php"
echo ✅ API 測試服務器已啟動
echo 💡 使用 Ctrl+C 停止服務器
pause
goto menu

:start_websocket_test
echo.
echo 🌐 啟動 WebSocket 測試服務器...
echo 📍 URL: ws://localhost:9082
echo 📝 日誌: test-logs/websocket_test.log
echo.
start "WebSocket測試服務器" cmd /k "cd test-servers\websocket-test && php test_websocket_server.php"
echo ✅ WebSocket 測試服務器已啟動
echo 💡 使用 Ctrl+C 停止服務器
pause
goto menu

:start_frontend_test
echo.
echo 📝 啟動前端測試頁面...
echo 📍 URL: http://localhost:9083
echo.
start "前端測試服務器" cmd /k "cd test-servers\frontend-test && php -S localhost:9083"
echo ✅ 前端測試服務器已啟動
echo 🌐 正在打開測試頁面...
timeout /t 2 >nul
start http://localhost:9083/test_complete_flow.html
pause
goto menu

:start_all_tests
echo.
echo 🚀 啟動所有測試服務器...
echo.

:: 啟動 API 測試服務器
echo 🔌 啟動 API 測試服務器 (端口: 9081)...
start "API測試服務器" cmd /k "cd test-servers\api-test && php -S localhost:9081 test_api_server.php"
timeout /t 2 >nul

:: 啟動 WebSocket 測試服務器
echo 🌐 啟動 WebSocket 測試服務器 (端口: 9082)...
start "WebSocket測試服務器" cmd /k "cd test-servers\websocket-test && php test_websocket_server.php"
timeout /t 2 >nul

:: 啟動前端測試服務器
echo 📝 啟動前端測試服務器 (端口: 9083)...
start "前端測試服務器" cmd /k "cd test-servers\frontend-test && php -S localhost:9083"
timeout /t 3 >nul

echo.
echo ✅ 所有測試服務器已啟動！
echo.
echo 📍 測試地址：
echo    🔌 API 測試: http://localhost:9081
echo    🌐 WebSocket 測試: ws://localhost:9082
echo    📝 前端測試: http://localhost:9083/test_complete_flow.html
echo.
echo 🌐 正在打開測試頁面...
start http://localhost:9083/test_complete_flow.html

echo.
echo 💡 提示：
echo    - 使用 Ctrl+C 在各個命令窗口中停止對應服務器
echo    - 測試日誌保存在 test-logs/ 目錄
echo    - 測試報告保存在 test-reports/ 目錄
echo.
pause
goto menu

:start_integration_test
echo.
echo 🔄 啟動整合測試環境...
echo.

:: 檢查主服務器是否運行
echo 🔍 檢查主服務器狀態...
curl -s http://localhost:8080/api/status >nul 2>&1
if errorlevel 1 (
    echo ⚠️ 主服務器未運行，正在啟動...
    start "主服務器" cmd /k "php -S localhost:8080 router.php"
    echo ⏳ 等待主服務器啟動...
    timeout /t 5 >nul
) else (
    echo ✅ 主服務器已運行
)

:: 啟動測試服務器
call :start_all_tests

echo.
echo 🔄 整合測試環境已就緒！
echo.
echo 📍 服務器地址：
echo    🏠 主服務器: http://localhost:8080
echo    🔌 API 測試: http://localhost:9081
echo    🌐 WebSocket 測試: ws://localhost:9082
echo    📝 測試頁面: http://localhost:9083/test_complete_flow.html
echo.
pause
goto menu

:show_reports
echo.
echo 📊 測試報告查看器
echo.

if not exist "test-reports" (
    echo ⚠️ 測試報告目錄不存在
    echo 請先運行測試生成報告
    pause
    goto menu
)

echo 📁 可用的測試報告：
echo.
dir /b test-reports\*.txt test-reports\*.json test-reports\*.html 2>nul
if errorlevel 1 (
    echo ⚠️ 暫無測試報告
    echo 請先運行測試生成報告
) else (
    echo.
    echo 💡 報告文件位於: test-reports\ 目錄
    start explorer test-reports
)

echo.
pause
goto menu

:cleanup_tests
echo.
echo 🧹 清理測試環境...
echo.

:: 停止測試服務器進程
echo 🛑 停止測試服務器進程...
taskkill /f /im php.exe >nul 2>&1

:: 清理測試日誌
echo 🗑️ 清理測試日誌...
if exist "test-logs" (
    del /q test-logs\*.log >nul 2>&1
    del /q test-logs\*.txt >nul 2>&1
    del /q test-logs\*.json >nul 2>&1
)

:: 清理測試報告
echo 📊 清理測試報告...
if exist "test-reports" (
    del /q test-reports\*.txt >nul 2>&1
    del /q test-reports\*.json >nul 2>&1
    del /q test-reports\*.html >nul 2>&1
)

:: 清理臨時文件
echo 🧽 清理臨時文件...
if exist "temp" (
    del /q temp\*.tmp >nul 2>&1
    del /q temp\*.cache >nul 2>&1
)

echo.
echo ✅ 測試環境清理完成！
echo.
pause
goto menu

:invalid_choice
echo.
echo ❌ 無效選擇，請輸入 0-7 之間的數字
echo.
pause

:menu
cls
goto :eof

:exit
echo.
echo 👋 感謝使用 PythonLearn 測試服務器管理器！
echo.
pause
exit /b 0 