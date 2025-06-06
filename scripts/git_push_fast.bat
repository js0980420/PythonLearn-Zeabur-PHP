@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

echo ⚡ 快速Git上傳腳本 - PythonLearn-Zeabur-PHP ⚡
echo =====================================================

REM 設置顏色
set "GREEN=[32m"
set "RED=[31m"
set "YELLOW=[33m"
set "BLUE=[34m"
set "NC=[0m"

echo %BLUE%[1/6]%NC% 檢查Git狀態...
git status --porcelain >nul 2>&1
if errorlevel 1 (
    echo %RED%❌ Git未初始化或不在Git倉庫中%NC%
    pause
    exit /b 1
)

echo %BLUE%[2/6]%NC% 檢查是否有變更...
for /f %%i in ('git status --porcelain') do set CHANGES=%%i
if "!CHANGES!"=="" (
    echo %YELLOW%ℹ️  沒有檔案變更，無需上傳%NC%
    echo.
    echo 當前狀態:
    git status --short
    pause
    exit /b 0
)

echo %GREEN%✅ 檢測到檔案變更%NC%
echo.
echo 變更檔案:
git status --short

echo.
echo %BLUE%[3/6]%NC% 添加所有變更到暫存區...
git add .
if errorlevel 1 (
    echo %RED%❌ 添加檔案失敗%NC%
    pause
    exit /b 1
)
echo %GREEN%✅ 檔案添加完成%NC%

echo.
echo %BLUE%[4/6]%NC% 檢查暫存區狀態...
git diff --cached --stat
if errorlevel 1 (
    echo %YELLOW%⚠️  暫存區為空%NC%
    pause
    exit /b 1
)

REM 自動生成提交訊息
echo.
echo %BLUE%[5/6]%NC% 生成提交訊息...

REM 檢查變更類型
set "COMMIT_TYPE=更新"
set "COMMIT_SCOPE="

REM 檢查是否有新檔案
for /f %%i in ('git diff --cached --name-status ^| findstr "^A"') do set NEW_FILES=1
if defined NEW_FILES set "COMMIT_TYPE=新增"

REM 檢查是否有刪除檔案
for /f %%i in ('git diff --cached --name-status ^| findstr "^D"') do set DELETED_FILES=1
if defined DELETED_FILES set "COMMIT_TYPE=刪除"

REM 檢查特定檔案類型
git diff --cached --name-only | findstr "zeabur.yaml" >nul && set "COMMIT_SCOPE=Zeabur配置"
git diff --cached --name-only | findstr "router.php" >nul && set "COMMIT_SCOPE=路由配置"
git diff --cached --name-only | findstr "websocket" >nul && set "COMMIT_SCOPE=WebSocket服務"
git diff --cached --name-only | findstr ".js$" >nul && set "COMMIT_SCOPE=前端功能"
git diff --cached --name-only | findstr ".bat$" >nul && set "COMMIT_SCOPE=腳本工具"

REM 生成最終提交訊息
set "COMMIT_MSG=🔧 %COMMIT_TYPE%"
if defined COMMIT_SCOPE set "COMMIT_MSG=!COMMIT_MSG!: !COMMIT_SCOPE!"

REM 添加時間戳
for /f "tokens=1-3 delims=/ " %%a in ('date /t') do set COMMIT_DATE=%%c-%%a-%%b
for /f "tokens=1-2 delims=: " %%a in ('time /t') do set COMMIT_TIME=%%a:%%b
set "COMMIT_MSG=!COMMIT_MSG! - !COMMIT_DATE! !COMMIT_TIME!"

echo 提交訊息: !COMMIT_MSG!

echo.
set /p CUSTOM_MSG="是否使用自定義提交訊息? (直接按Enter使用自動生成的訊息): "
if not "!CUSTOM_MSG!"=="" set "COMMIT_MSG=!CUSTOM_MSG!"

echo.
echo %BLUE%[6/6]%NC% 提交並推送到GitHub...
git commit -m "!COMMIT_MSG!"
if errorlevel 1 (
    echo %RED%❌ 提交失敗%NC%
    pause
    exit /b 1
)

echo %GREEN%✅ 提交完成%NC%
echo.
echo 推送到遠端倉庫...
git push origin main
if errorlevel 1 (
    echo %RED%❌ 推送失敗，嘗試其他分支...%NC%
    git push origin master
    if errorlevel 1 (
        echo %RED%❌ 推送失敗%NC%
        echo.
        echo 可能的原因:
        echo - 網路連接問題
        echo - 需要身份驗證
        echo - 分支名稱不正確
        echo.
        echo 手動推送指令:
        echo git push origin main
        echo 或
        echo git push origin master
        pause
        exit /b 1
    )
)

echo.
echo %GREEN%🎉 Git上傳完成！%NC%
echo =====================================================
echo.
echo 📊 上傳摘要:
echo 提交訊息: !COMMIT_MSG!
echo 推送時間: !COMMIT_DATE! !COMMIT_TIME!
echo.
echo 🌐 Zeabur部署:
echo 專案會自動部署到: https://python-learn.zeabur.app
echo 部署通常需要2-5分鐘完成
echo.
echo 📋 檢查清單:
echo - ✅ 代碼已提交到GitHub
echo - ⏳ Zeabur自動部署中...
echo - 🔄 建議等待5分鐘後測試WebSocket連接
echo.

REM 顯示最後幾次提交
echo 📜 最近提交記錄:
git log --oneline -5

echo.
echo %YELLOW%💡 提示:%NC%
echo 1. 如需查看部署狀態，訪問 Zeabur 控制台
echo 2. WebSocket連接問題可通過健康檢查端點確認: /health
echo 3. 如遇問題，運行 system-cleanup.bat 清理本地環境
echo.

pause 