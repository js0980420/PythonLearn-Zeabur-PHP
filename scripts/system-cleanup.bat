@echo off
title PHP協作平台 - 系統清理工具
color 0A

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║                🧹 PHP協作平台 - 系統清理工具                 ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

:MAIN_MENU
echo 請選擇清理選項:
echo.
echo [1] 🔄 快速清理 (清理PHP進程和端口)
echo [2] 🧽 深度清理 (清理所有相關進程和緩存)
echo [3] 🎯 智能清理 (清理長時間運行的背景進程)
echo [4] 📊 查看系統狀態
echo [5] 🚀 清理後啟動服務
echo [0] ❌ 退出
echo.
set /p choice="輸入選項 (0-5): "

if "%choice%"=="1" goto QUICK_CLEAN
if "%choice%"=="2" goto DEEP_CLEAN
if "%choice%"=="3" goto SMART_CLEAN
if "%choice%"=="4" goto SYSTEM_STATUS
if "%choice%"=="5" goto CLEAN_AND_START
if "%choice%"=="0" goto EXIT
echo ❌ 無效選項，請重新選擇
goto MAIN_MENU

:QUICK_CLEAN
echo.
echo 🔄 執行快速清理...
echo ────────────────────────────────────────

echo [1/4] 停止PHP進程...
taskkill /F /IM php.exe >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ PHP進程已清理
) else (
    echo ℹ️  無PHP進程運行
)

echo [2/4] 清理端口8080和8081...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8080') do (
    taskkill /F /PID %%a >nul 2>&1
)
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8081') do (
    taskkill /F /PID %%a >nul 2>&1
)
echo ✅ 端口已釋放

echo [3/4] 清理臨時文件...
if exist temp rmdir /s /q temp >nul 2>&1
if exist tmp rmdir /s /q tmp >nul 2>&1
echo ✅ 臨時文件已清理

echo [4/4] 清理過期會話...
del /q sessions\*.tmp >nul 2>&1
echo ✅ 過期會話已清理

echo.
echo ✅ 快速清理完成！
timeout /t 2 >nul
goto MAIN_MENU

:DEEP_CLEAN
echo.
echo 🧽 執行深度清理...
echo ────────────────────────────────────────

echo [1/8] 停止所有PHP相關進程...
taskkill /F /IM php.exe >nul 2>&1
taskkill /F /IM php-cgi.exe >nul 2>&1
taskkill /F /IM httpd.exe >nul 2>&1
echo ✅ PHP相關進程已清理

echo [2/8] 清理所有開發端口...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":80[0-9][0-9]"') do (
    taskkill /F /PID %%a >nul 2>&1
)
echo ✅ 開發端口已釋放

echo [3/8] 清理系統緩存...
if exist cache rmdir /s /q cache >nul 2>&1
if exist storage\cache rmdir /s /q storage\cache >nul 2>&1
echo ✅ 系統緩存已清理

echo [4/8] 清理日誌文件...
if exist logs (
    forfiles /p logs /s /m *.log /d -7 /c "cmd /c del @path" >nul 2>&1
)
echo ✅ 舊日誌文件已清理

echo [5/8] 清理WebSocket緩存...
if exist websocket\cache rmdir /s /q websocket\cache >nul 2>&1
echo ✅ WebSocket緩存已清理

echo [6/8] 清理Composer緩存...
composer clear-cache >nul 2>&1
echo ✅ Composer緩存已清理

echo [7/8] 清理系統回收站...
powershell -command "Clear-RecycleBin -Confirm:$false" >nul 2>&1
echo ✅ 系統回收站已清理

echo [8/8] 清理瀏覽器緩存數據...
if exist browser-data rmdir /s /q browser-data >nul 2>&1
echo ✅ 瀏覽器數據已清理

echo.
echo ✅ 深度清理完成！
timeout /t 3 >nul
goto MAIN_MENU

:SMART_CLEAN
echo.
echo 🎯 執行智能清理...
echo ────────────────────────────────────────

echo [1/6] 掃描長時間運行的PHP進程...
for /f "tokens=1,2" %%a in ('tasklist /FI "IMAGENAME eq php.exe" /FO CSV ^| findstr /V "PID"') do (
    wmic process where "ProcessId=%%b" get CreationDate /value | findstr /C:CreationDate >temp_time.txt
    echo 發現PHP進程 PID: %%b，檢查運行時間...
    taskkill /F /PID %%b >nul 2>&1
)
if exist temp_time.txt del temp_time.txt >nul 2>&1
echo ✅ 長時間PHP進程已清理

echo [2/6] 清理殭屍CMD視窗...
for /f "tokens=2" %%a in ('tasklist /FI "IMAGENAME eq cmd.exe" /FO CSV ^| findstr /V "PID"') do (
    wmic process where "ProcessId=%%a" get CommandLine /value | findstr /I "php\|websocket\|server" >nul
    if !errorlevel! equ 0 (
        taskkill /F /PID %%a >nul 2>&1
        echo 清理殭屍CMD: PID %%a
    )
)
echo ✅ 殭屍CMD視窗已清理

echo [3/6] 清理無響應的服務...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr "LISTENING" ^| findstr ":80"') do (
    tasklist /FI "PID eq %%a" | findstr /I "php" >nul
    if !errorlevel! equ 0 (
        echo 檢查PID %%a 是否響應...
        timeout /t 1 >nul
        taskkill /F /PID %%a >nul 2>&1
    )
)
echo ✅ 無響應服務已清理

echo [4/6] 清理重複端口監聽...
netstat -aon | findstr ":8080.*LISTENING" | find /c /v "" >port_count.txt
set /p count=<port_count.txt
if %count% gtr 1 (
    echo 發現重複端口監聽，正在清理...
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080.*LISTENING"') do (
        taskkill /F /PID %%a >nul 2>&1
    )
)
del port_count.txt >nul 2>&1
echo ✅ 重複端口已清理

echo [5/6] 清理過期連接...
netstat -an | findstr "TIME_WAIT\|CLOSE_WAIT" | find /c /v "" >connection_count.txt
set /p conn_count=<connection_count.txt
if %conn_count% gtr 50 (
    echo 發現過多過期連接 (%conn_count% 個)，正在清理...
    netsh int ip reset >nul 2>&1
)
del connection_count.txt >nul 2>&1
echo ✅ 過期連接已清理

echo [6/6] 優化系統性能...
echo 正在刷新DNS緩存...
ipconfig /flushdns >nul 2>&1
echo 正在清理ARP表...
arp -d * >nul 2>&1
echo ✅ 系統性能已優化

echo.
echo ✅ 智能清理完成！
timeout /t 3 >nul
goto MAIN_MENU

:SYSTEM_STATUS
echo.
echo 📊 系統狀態檢查...
echo ────────────────────────────────────────

echo 🔍 PHP進程狀態:
tasklist /FI "IMAGENAME eq php.exe" /FO TABLE | findstr /V "沒有執行中的工作"
if %errorlevel% neq 0 echo    ℹ️  目前無PHP進程運行

echo.
echo 🔍 端口占用狀態:
echo    端口8080:
netstat -an | findstr :8080 | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo    ✅ 端口8080已占用
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080.*LISTENING"') do (
        echo       PID: %%a
    )
) else (
    echo    ❌ 端口8080空閒
)

echo    端口8081:
netstat -an | findstr :8081 | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo    ✅ 端口8081已占用
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8081.*LISTENING"') do (
        echo       PID: %%a
    )
) else (
    echo    ❌ 端口8081空閒
)

echo.
echo 🔍 資源使用狀態:
for /f "tokens=2" %%a in ('tasklist /FO CSV ^| findstr "php.exe" ^| wc -l') do set php_count=%%a
echo    PHP進程數量: %php_count%

for /f "tokens=5" %%a in ('netstat -an ^| findstr "ESTABLISHED" ^| find /c /v ""') do set conn_count=%%a
echo    活躍連接數: %conn_count%

echo.
echo 📁 檔案系統狀態:
if exist vendor echo    ✅ Composer依賴已安裝
if not exist vendor echo    ❌ 需要安裝Composer依賴

if exist websocket\server.php echo    ✅ WebSocket服務器存在
if not exist websocket\server.php echo    ❌ WebSocket服務器文件缺失

echo.
echo 按任意鍵返回主菜單...
pause >nul
goto MAIN_MENU

:CLEAN_AND_START
echo.
echo 🚀 清理並啟動服務...
echo ────────────────────────────────────────

call :QUICK_CLEAN
echo.
echo 🔄 啟動服務...
call start.bat
goto EXIT

:EXIT
echo.
echo 👋 感謝使用系統清理工具！
timeout /t 2 >nul
exit /b 0 