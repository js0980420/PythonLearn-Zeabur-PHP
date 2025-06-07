@echo off
echo 正在啟動Python教學平台本地測試伺服器...
echo.
echo 伺服器地址: http://localhost:8000
echo AI測試頁面: http://localhost:8000/test
echo.
echo 按 Ctrl+C 停止伺服器
echo.

REM 嘗試使用不同的PHP路徑
if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S localhost:8000 start_server.php
) else if exist "C:\php\php.exe" (
    "C:\php\php.exe" -S localhost:8000 start_server.php
) else if exist "C:\wamp64\bin\php\php8.1.0\php.exe" (
    "C:\wamp64\bin\php\php8.1.0\php.exe" -S localhost:8000 start_server.php
) else (
    echo 找不到PHP執行檔，請確保已安裝PHP並添加到PATH環境變數中
    echo 或者修改此批次文件中的PHP路徑
    pause
) 