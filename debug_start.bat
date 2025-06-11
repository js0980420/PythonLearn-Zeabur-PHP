@echo off
chcp 65001 > nul
echo.
echo 🎯 PythonLearn 雙重調試環境
echo ============================
echo.

echo 📊 檢查服務器狀態...
curl -s http://localhost:8080/api.php?action=status > nul 2>&1
if %errorlevel%==0 (
    echo ✅ 本地服務器運行中 (localhost:8080)
) else (
    echo ❌ 服務器未啟動，請先運行: php -S localhost:8080 -t public
    pause
    exit /b 1
)

echo.
echo 🎭 調試選項:
echo 1. 打開瀏覽器控制台調試
echo 2. 運行 Playwright 自動化測試
echo 3. 同時使用兩種方法
echo.

set /p choice="請選擇 (1/2/3): "

if "%choice%"=="1" goto browser_debug
if "%choice%"=="2" goto playwright_debug
if "%choice%"=="3" goto both_debug
goto invalid_choice

:browser_debug
echo.
echo 🌐 啟動瀏覽器控制台調試...
start http://localhost:8080
echo.
echo 📝 調試步驟:
echo 1. 按 F12 打開開發者工具
echo 2. 切換到 Console 標籤
echo 3. 貼入以下代碼:
echo.
echo // 快速調試助手
echo window.apiTest = { async call(action, params = {}) { /* ... */ } };
echo await apiTest.call('get_recent_users', { limit: 5 });
echo.
goto end

:playwright_debug
echo.
echo 🎭 運行 Playwright 自動化測試...
if exist "node_modules" (
    node playwright_debug.js
) else (
    echo ⚠️  Node.js 模塊未安裝，使用基本測試...
    echo 正在測試 API...
    curl http://localhost:8080/api.php?action=status
)
goto end

:both_debug
echo.
echo 🚀 啟動雙重調試模式...
start http://localhost:8080
echo.
echo 🎭 同時啟動 Playwright 測試...
if exist "node_modules" (
    start node playwright_debug.js
) else (
    echo ℹ️  Playwright 需要 Node.js 模塊，僅啟動瀏覽器調試
)
goto end

:invalid_choice
echo ❌ 無效選擇，請輸入 1、2 或 3
pause
exit /b 1

:end
echo.
echo 🎯 調試環境已啟動！
echo 💡 提示: 查看 debug_guide.md 獲取詳細調試指南
echo.
pause 