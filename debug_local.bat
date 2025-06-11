@echo off
chcp 65001 > nul
echo 🛠️ PythonLearn 本地調試環境啟動
echo ================================

echo 📊 檢查端口 8080...
netstat -ano | findstr ":8080" > nul
if %errorlevel%==0 (
    echo ⚠️  端口 8080 已被占用，正在清理...
    for /f "tokens=5" %%p in ('netstat -ano ^| findstr ":8080"') do (
        taskkill /PID %%p /F > nul 2>&1
    )
    timeout /t 2 > nul
)

echo 🚀 啟動 PHP 服務器 (localhost:8080)...
start "PHP Server" php -S localhost:8080 -t public

echo ⏳ 等待服務器啟動...
timeout /t 3 > nul

echo 🌐 測試服務器連通性...
curl -s http://localhost:8080 > nul
if %errorlevel%==0 (
    echo ✅ 服務器啟動成功！
    echo 🎯 準備就緒，您可以：
    echo    1. 使用 Playwright 自動化測試
    echo    2. 打開瀏覽器控制台進行手動調試
    echo    3. 訪問 http://localhost:8080
    echo.
    echo 💡 調試提示：
    echo    - Playwright: npm run test:debug 
    echo    - 瀏覽器F12: 直接在控制台調試
    echo    - API測試: /api.php?action=status
) else (
    echo ❌ 服務器啟動失敗，請檢查 PHP 安裝
)

pause 