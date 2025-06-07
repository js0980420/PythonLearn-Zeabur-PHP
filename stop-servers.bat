@echo off
echo 🛑 停止 PythonLearn 服務器...

REM 停止所有PHP進程
echo 🧹 停止 PHP 服務器進程...
taskkill /f /im php.exe >nul 2>&1

REM 等待進程完全停止
timeout /t 2 /nobreak >nul

echo.
echo ✅ 所有服務器已停止！
echo.
echo 💡 提示: 使用 start-servers.bat 來重新啟動服務
pause 