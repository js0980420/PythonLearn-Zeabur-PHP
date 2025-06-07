@echo off
chcp 65001 >nul
echo.
echo ⚡ ========================================
echo    PythonLearn 快速功能測試
echo ========================================
echo.

:: 檢查是否有參數指定測試類型
if "%1"=="" goto show_menu
if "%1"=="api" goto test_api
if "%1"=="websocket" goto test_websocket
if "%1"=="frontend" goto test_frontend
if "%1"=="all" goto test_all
goto show_menu

:show_menu
echo 📋 可用的快速測試：
echo.
echo [1] 🔌 API 功能測試
echo [2] 🌐 WebSocket 連接測試
echo [3] 📝 前端頁面測試
echo [4] 🚀 完整功能測試
echo [5] 🔄 回歸測試 (檢查舊功能)
echo [0] ❌ 退出
echo.

set /p choice="請選擇測試類型 (0-5): "

if "%choice%"=="1" goto test_api
if "%choice%"=="2" goto test_websocket
if "%choice%"=="3" goto test_frontend
if "%choice%"=="4" goto test_all
if "%choice%"=="5" goto test_regression
if "%choice%"=="0" goto exit
goto show_menu

:test_api
echo.
echo 🔌 開始 API 功能測試...
echo ================================
echo.

:: 檢查主服務器是否運行
echo 🔍 檢查主服務器狀態...
curl -s http://localhost:8080/api/status >nul 2>&1
if errorlevel 1 (
    echo ❌ 主服務器未運行
    echo 💡 請先啟動主服務器: php -S localhost:8080 router.php
    pause
    goto show_menu
)
echo ✅ 主服務器運行正常

:: 測試認證API
echo.
echo 🔐 測試認證 API...
curl -s -X POST http://localhost:8080/api/auth ^
     -H "Content-Type: application/json" ^
     -d "{\"username\":\"測試用戶\",\"user_type\":\"student\"}" > temp_auth_response.json

if exist temp_auth_response.json (
    findstr "success" temp_auth_response.json >nul
    if not errorlevel 1 (
        echo ✅ 認證 API 測試通過
    ) else (
        echo ❌ 認證 API 測試失敗
        type temp_auth_response.json
    )
    del temp_auth_response.json
) else (
    echo ❌ 無法連接到認證 API
)

:: 測試房間API
echo.
echo 🏠 測試房間 API...
curl -s http://localhost:8080/api/room > temp_room_response.json
if exist temp_room_response.json (
    findstr "success\|rooms" temp_room_response.json >nul
    if not errorlevel 1 (
        echo ✅ 房間 API 測試通過
    ) else (
        echo ❌ 房間 API 測試失敗
    )
    del temp_room_response.json
)

echo.
echo 📊 API 測試完成！
pause
goto show_menu

:test_websocket
echo.
echo 🌐 開始 WebSocket 連接測試...
echo ================================
echo.

:: 檢查WebSocket服務器端口
echo 🔍 檢查 WebSocket 服務器...
netstat -an | findstr ":9082" >nul
if errorlevel 1 (
    echo ❌ WebSocket 服務器未運行
    echo 💡 請先啟動 WebSocket 服務器
    pause
    goto show_menu
)
echo ✅ WebSocket 端口可用

:: 創建簡單的WebSocket測試
echo.
echo 📝 創建 WebSocket 測試頁面...
echo ^<!DOCTYPE html^> > temp_ws_test.html
echo ^<html^>^<head^>^<title^>WebSocket Test^</title^>^</head^> >> temp_ws_test.html
echo ^<body^> >> temp_ws_test.html
echo ^<h1^>WebSocket 連接測試^</h1^> >> temp_ws_test.html
echo ^<div id="status"^>正在連接...^</div^> >> temp_ws_test.html
echo ^<script^> >> temp_ws_test.html
echo var ws = new WebSocket('ws://localhost:9082'); >> temp_ws_test.html
echo ws.onopen = function() { document.getElementById('status').innerHTML = '✅ 連接成功'; }; >> temp_ws_test.html
echo ws.onerror = function() { document.getElementById('status').innerHTML = '❌ 連接失敗'; }; >> temp_ws_test.html
echo ^</script^>^</body^>^</html^> >> temp_ws_test.html

echo ✅ 測試頁面已創建
echo 🌐 正在打開測試頁面...
start temp_ws_test.html

echo.
echo 💡 請檢查瀏覽器中的連接狀態
echo 📝 測試頁面將在5秒後自動刪除
timeout /t 5 >nul
if exist temp_ws_test.html del temp_ws_test.html

pause
goto show_menu

:test_frontend
echo.
echo 📝 開始前端頁面測試...
echo ================================
echo.

:: 檢查主要前端文件
echo 🔍 檢查前端文件...
set "FRONTEND_FILES=public\index.html public\css\styles.css public\js\websocket.js public\js\editor.js"
set "MISSING_FILES="

for %%f in (%FRONTEND_FILES%) do (
    if exist "%%f" (
        echo ✅ %%f
    ) else (
        echo ❌ %%f (缺失)
        set "MISSING_FILES=!MISSING_FILES! %%f"
    )
)

if not "%MISSING_FILES%"=="" (
    echo.
    echo ⚠️ 發現缺失的前端文件，可能影響功能
)

:: 檢查主頁面
echo.
echo 🌐 測試主頁面載入...
if exist "public\index.html" (
    echo ✅ 正在打開主頁面...
    start http://localhost:8080
    echo 💡 請檢查頁面是否正常載入
) else (
    echo ❌ 主頁面文件不存在
)

echo.
echo 📊 前端測試完成！
pause
goto show_menu

:test_all
echo.
echo 🚀 開始完整功能測試...
echo ================================
echo.

:: 依次執行所有測試
call :test_api
call :test_websocket
call :test_frontend

echo.
echo 🎉 完整功能測試完成！
echo.
echo 📊 測試摘要：
echo   🔌 API 功能: 已測試
echo   🌐 WebSocket: 已測試  
echo   📝 前端頁面: 已測試
echo.
pause
goto show_menu

:test_regression
echo.
echo 🔄 開始回歸測試 (檢查舊功能)...
echo ================================
echo.

:: 檢查之前修復的問題是否復現
echo 🔍 檢查 API 認證問題...
curl -s -X POST http://localhost:8080/api/auth ^
     -H "Content-Type: application/json" ^
     -d "{\"username\":\"回歸測試\",\"user_type\":\"student\"}" > temp_regression.json

if exist temp_regression.json (
    findstr "500\|error\|undefined" temp_regression.json >nul
    if not errorlevel 1 (
        echo ❌ 回歸測試失敗：API認證問題復現
        type temp_regression.json
    ) else (
        echo ✅ API認證問題已修復
    )
    del temp_regression.json
)

echo.
echo 🔍 檢查房間代碼載入問題...
curl -s "http://localhost:8080/api/room" > temp_room_regression.json
if exist temp_room_regression.json (
    findstr "current_code.*undefined\|current_code.*null" temp_room_regression.json >nul
    if not errorlevel 1 (
        echo ❌ 回歸測試失敗：房間代碼問題復現
    ) else (
        echo ✅ 房間代碼問題已修復
    )
    del temp_room_regression.json
)

echo.
echo 📊 回歸測試完成！
pause
goto show_menu

:exit
echo.
echo 👋 感謝使用快速測試工具！
echo.
exit /b 0 