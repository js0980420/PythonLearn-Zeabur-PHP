@echo off
echo 🚀 XAMPP MariaDB 服務設置工具
echo =============================

rem 檢查管理員權限
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ 需要管理員權限！
    echo 💡 請右鍵點擊此檔案，選擇「以系統管理員身分執行」
    pause
    exit /b 1
)

echo ✅ 已獲得管理員權限

rem 停止所有 MySQL 進程
echo.
echo 🛑 停止現有 MySQL 進程...
taskkill /F /IM mysqld.exe >nul 2>&1

rem 停止並移除現有 MySQL 服務
echo.
echo 🧹 清理現有 MySQL 服務...
net stop mysql >nul 2>&1
net stop mysql80 >nul 2>&1
net stop MySQL93 >nul 2>&1
sc delete mysql >nul 2>&1
sc delete mysql80 >nul 2>&1
sc delete MySQL93 >nul 2>&1

rem 等待服務完全停止
timeout /t 3 /nobreak >nul

rem 安裝 XAMPP MariaDB 為 Windows 服務
echo.
echo 📥 安裝 XAMPP MariaDB 為 Windows 服務...
"C:\xampp\mysql\bin\mysqld.exe" --install mysql --defaults-file="C:\xampp\mysql\bin\my.ini"

if %errorLevel% equ 0 (
    echo ✅ MariaDB 服務安裝成功
    
    rem 設置服務為自動啟動
    echo.
    echo ⚙️ 設置服務為自動啟動...
    sc config mysql start= auto
    
    rem 啟動服務
    echo.
    echo 🚀 啟動 MariaDB 服務...
    net start mysql
    
    if %errorLevel% equ 0 (
        echo ✅ MariaDB 服務啟動成功！
    ) else (
        echo ⚠️ 服務啟動失敗，可能需要修復系統表
        echo 請檢查錯誤日誌：C:\xampp\mysql\data\mysql_error.log
    )
) else (
    echo ❌ MariaDB 服務安裝失敗
)

rem 檢查服務狀態
echo.
echo 🔍 檢查服務狀態...
sc query mysql

rem 檢查端口使用情況
echo.
echo 🌐 檢查端口 3306...
netstat -ano | findstr ":3306"

echo.
echo 🎉 設置完成！
echo.
echo 📋 使用說明：
echo - MariaDB 現在會隨 Windows 自動啟動
echo - 可以使用 XAMPP 控制面板管理服務
echo - 預設 root 用戶無密碼
echo - 可以使用 phpMyAdmin 管理資料庫

pause 