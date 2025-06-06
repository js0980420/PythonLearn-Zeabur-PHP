@echo off
title PHP協作平台 - 快速訪問工具
color 0C

echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║                🚀 PHP協作平台 - 快速訪問工具                  ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.

REM 檢查服務器是否運行
netstat -an | findstr :8080 | findstr LISTENING >nul
if %errorlevel% neq 0 (
    echo ⚠️  檢測到服務器未運行
    echo.
    echo 請選擇操作:
    echo [1] 🚀 啟動服務器
    echo [2] 📋 僅顯示訪問選項 (服務器可能在其他端口運行)
    echo [0] ❌ 退出
    echo.
    set /p server_choice="輸入選項 (0-2): "
    
    if "!server_choice!"=="1" (
        echo 🚀 正在啟動服務器...
        call start.bat
        goto END
    )
    if "!server_choice!"=="2" (
        goto SHOW_OPTIONS
    )
    if "!server_choice!"=="0" (
        goto END
    )
    echo ❌ 無效選項，顯示訪問選項
)

:SHOW_OPTIONS
echo 📱 快速訪問選項:
echo.
echo 🌐 瀏覽器訪問:
echo [1] 💻 學生編程界面 (主頁面)
echo [2] 👨‍🏫 教師監控後台
echo [3] 🔍 系統健康檢查
echo [4] 📊 API狀態測試
echo.
echo 🛠️ 系統工具:
echo [5] 🧹 系統清理工具
echo [6] 🔄 重啟服務器
echo [7] 📈 查看系統狀態
echo [8] 📁 打開項目目錄
echo.
echo 📚 開發工具:
echo [9] 📝 查看日誌
echo [A] ⚙️ 編輯配置
echo [B] 🔧 檢查依賴
echo [C] 🌍 局域網訪問設置
echo.
echo [0] ❌ 退出
echo.
set /p choice="輸入選項: "

if "%choice%"=="1" (
    echo 🌐 正在打開學生編程界面...
    start http://localhost:8080
    goto CONTINUE
)
if "%choice%"=="2" (
    echo 👨‍🏫 正在打開教師監控後台...
    start http://localhost:8080/teacher-dashboard.html
    goto CONTINUE
)
if "%choice%"=="3" (
    echo 🔍 正在打開系統健康檢查...
    start http://localhost:8080/health
    goto CONTINUE
)
if "%choice%"=="4" (
    echo 📊 正在測試API狀態...
    start http://localhost:8080/backend/api/test.php
    goto CONTINUE
)
if "%choice%"=="5" (
    echo 🧹 正在啟動系統清理工具...
    start system-cleanup.bat
    goto CONTINUE
)
if "%choice%"=="6" (
    echo 🔄 正在重啟服務器...
    call system-cleanup.bat
    timeout /t 2 >nul
    call start.bat
    goto END
)
if "%choice%"=="7" (
    call :SHOW_DETAILED_STATUS
    goto CONTINUE
)
if "%choice%"=="8" (
    echo 📁 正在打開項目目錄...
    start explorer "%CD%"
    goto CONTINUE
)
if "%choice%"=="9" (
    call :SHOW_LOGS
    goto CONTINUE
)
if /I "%choice%"=="A" (
    call :EDIT_CONFIG
    goto CONTINUE
)
if /I "%choice%"=="B" (
    call :CHECK_DEPENDENCIES
    goto CONTINUE
)
if /I "%choice%"=="C" (
    call :SETUP_LAN_ACCESS
    goto CONTINUE
)
if "%choice%"=="0" goto END

echo ❌ 無效選項，請重新選擇
goto SHOW_OPTIONS

:SHOW_DETAILED_STATUS
echo.
echo ╔════════════════════════════════════════════════════════════════╗
echo ║                      📊 詳細系統狀態                           ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.

echo 🖥️ 系統信息:
echo    操作系統: %OS%
echo    電腦名稱: %COMPUTERNAME%
echo    當前時間: %DATE% %TIME%
echo    項目目錄: %CD%

echo.
echo 🔍 服務狀態:
netstat -an | findstr :8080 | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo    ✅ Web服務器 (8080): 運行中
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8080.*LISTENING"') do (
        echo       PID: %%a
    )
) else (
    echo    ❌ Web服務器 (8080): 未運行
)

netstat -an | findstr :8081 | findstr LISTENING >nul
if %errorlevel% equ 0 (
    echo    ✅ WebSocket服務器 (8081): 運行中
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8081.*LISTENING"') do (
        echo       PID: %%a
    )
) else (
    echo    ❌ WebSocket服務器 (8081): 未運行
)

echo.
echo 🔍 進程信息:
tasklist /FI "IMAGENAME eq php.exe" /FO TABLE 2>nul | findstr "php.exe"
if %errorlevel% neq 0 echo    ℹ️  無PHP進程運行

echo.
echo 🌐 網絡信息:
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /R "IPv4.*Address"') do (
    echo    本機IP: %%a
)

echo.
echo 📁 檔案狀態:
if exist composer.json (
    echo    ✅ composer.json 存在
) else (
    echo    ❌ composer.json 缺失
)

if exist vendor (
    echo    ✅ 依賴已安裝
) else (
    echo    ❌ 需要安裝依賴
)

if exist websocket\server.php (
    echo    ✅ WebSocket服務器文件存在
) else (
    echo    ❌ WebSocket服務器文件缺失
)

echo.
echo 按任意鍵返回...
pause >nul
return

:SHOW_LOGS
echo.
echo 📝 查看系統日誌...
echo.
if exist logs (
    echo 📁 可用日誌文件:
    dir /b logs\*.log 2>nul
    echo.
    echo 請輸入要查看的日誌文件名 (不含路徑):
    set /p log_file="日誌文件名: "
    if exist "logs\!log_file!" (
        echo 📖 正在顯示日誌內容...
        type "logs\!log_file!"
    ) else (
        echo ❌ 日誌文件不存在
    )
) else (
    echo ℹ️  日誌目錄不存在或為空
)
echo.
echo 按任意鍵返回...
pause >nul
return

:EDIT_CONFIG
echo.
echo ⚙️ 配置文件編輯...
echo.
echo 可編輯的配置文件:
echo [1] composer.json
echo [2] zeabur.yaml
echo [3] .gitignore
echo [4] router.php
echo [0] 返回
echo.
set /p config_choice="選擇配置文件: "

if "%config_choice%"=="1" if exist composer.json start notepad composer.json
if "%config_choice%"=="2" if exist zeabur.yaml start notepad zeabur.yaml
if "%config_choice%"=="3" if exist .gitignore start notepad .gitignore
if "%config_choice%"=="4" if exist router.php start notepad router.php
if "%config_choice%"=="0" return

return

:CHECK_DEPENDENCIES
echo.
echo 🔧 檢查項目依賴...
echo.

echo 🔍 PHP版本:
php --version 2>nul
if %errorlevel% neq 0 echo ❌ PHP未安裝或未加入PATH

echo.
echo 🔍 Composer狀態:
composer --version 2>nul
if %errorlevel% neq 0 (
    echo ❌ Composer未安裝
) else (
    echo ✅ Composer已安裝
    if exist composer.json (
        echo 📦 檢查依賴狀態...
        composer check-platform-reqs 2>nul
    )
)

echo.
echo 🔍 必要文件檢查:
set "required_files=index.html router.php websocket/server.php"
for %%f in (%required_files%) do (
    if exist "%%f" (
        echo    ✅ %%f
    ) else (
        echo    ❌ %%f (缺失)
    )
)

echo.
echo 按任意鍵返回...
pause >nul
return

:SETUP_LAN_ACCESS
echo.
echo 🌍 局域網訪問設置...
echo.

echo 📋 當前網絡配置:
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /R "IPv4.*Address"') do (
    set local_ip=%%a
    echo 本機IP:!local_ip!
)

echo.
echo 📝 局域網訪問地址:
echo    學生界面: http://!local_ip!:8080
echo    教師後台: http://!local_ip!:8080/teacher-dashboard.html
echo.

echo 🔧 防火牆設置建議:
echo    1. 確保Windows防火牆允許端口8080和8081
echo    2. 路由器可能需要配置端口轉發
echo    3. 確保服務器以 0.0.0.0 監聽而非 localhost
echo.

echo 💡 要啟用局域網訪問，請:
echo    1. 停止當前服務器
echo    2. 修改啟動命令為: php -S 0.0.0.0:8080
echo    3. 重新啟動服務器
echo.

echo [1] 🌐 在瀏覽器中測試局域網地址
echo [2] 🔧 配置防火牆 (需要管理員權限)
echo [0] 返回
echo.
set /p lan_choice="選擇操作: "

if "%lan_choice%"=="1" (
    start http://!local_ip!:8080
)
if "%lan_choice%"=="2" (
    echo 🔧 配置防火牆規則...
    netsh advfirewall firewall add rule name="PHP Web Server" dir=in action=allow protocol=TCP localport=8080
    netsh advfirewall firewall add rule name="PHP WebSocket Server" dir=in action=allow protocol=TCP localport=8081
    echo ✅ 防火牆規則已添加
)

return

:CONTINUE
echo.
echo 是否繼續使用快速訪問工具？
echo [Y] 是
echo [N] 否
set /p continue_choice="請選擇 (Y/N): "

if /I "%continue_choice%"=="Y" goto SHOW_OPTIONS
if /I "%continue_choice%"=="N" goto END

goto SHOW_OPTIONS

:END
echo.
echo 👋 感謝使用快速訪問工具！
timeout /t 2 >nul
exit /b 0 