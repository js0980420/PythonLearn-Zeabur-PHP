@echo off
echo 🤖 開啟AI助教測試環境
echo ================================

echo 📊 檢查測試服務器狀態...
netstat -an | findstr ":908" > nul
if %errorlevel% neq 0 (
    echo ❌ 測試服務器未運行，請先執行 start-test-servers.bat
    pause
    exit /b 1
)

echo ✅ 測試服務器運行中

echo 🌐 開啟AI助教測試頁面...
start http://localhost:9083/test_ai_assistant.html

echo 📋 測試頁面功能說明：
echo   - 🔌 AI API 功能測試
echo   - 📝 代碼分析測試  
echo   - 💬 AI聊天功能測試
echo   - 🔄 AI助教整合測試

echo.
echo 🎯 測試重點：
echo   1. 測試AI問答功能
echo   2. 測試代碼分析和建議
echo   3. 測試錯誤檢查功能
echo   4. 測試聊天互動
echo   5. 測試性能和可靠性

echo.
echo 📊 測試服務器地址：
echo   - API測試服務器: http://localhost:9081
echo   - WebSocket測試服務器: ws://localhost:9082  
echo   - 前端測試服務器: http://localhost:9083
echo   - AI助教測試頁面: http://localhost:9083/test_ai_assistant.html

echo.
echo 🔧 如需查看測試日誌，請檢查 test-logs/ 目錄
echo ✨ 測試完成後，請記錄測試結果並提交改進建議

pause 