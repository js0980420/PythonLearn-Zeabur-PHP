@echo off
chcp 65001 >nul
echo.
echo 🔍 ========================================
echo    Git 提交前檢查 - PythonLearn
echo ========================================
echo.

:: 設置變數
set "ERRORS=0"
set "WARNINGS=0"
set "CHECK_PASSED=1"

echo 📋 開始執行提交前檢查...
echo.

:: 1. 檢查PHP語法
echo 🔧 [1/8] 檢查 PHP 語法...
for /r %%f in (*.php) do (
    php -l "%%f" >nul 2>&1
    if errorlevel 1 (
        echo ❌ PHP語法錯誤: %%f
        set /a ERRORS+=1
        set "CHECK_PASSED=0"
    )
)
if %ERRORS%==0 (
    echo ✅ PHP 語法檢查通過
) else (
    echo ❌ 發現 %ERRORS% 個 PHP 語法錯誤
)
echo.

:: 2. 檢查JavaScript語法（簡單檢查）
echo 📝 [2/8] 檢查 JavaScript 語法...
set "JS_ERRORS=0"
for /r public\js %%f in (*.js) do (
    findstr /c:"function(" /c:"=>" /c:"class " "%%f" >nul
    if errorlevel 1 (
        echo ⚠️ JavaScript文件可能為空: %%f
        set /a WARNINGS+=1
    ) else (
        echo ✅ %%f
    )
)
echo.

:: 3. 檢查必要文件存在
echo 📁 [3/8] 檢查必要文件...
set "REQUIRED_FILES=router.php public\index.html backend\api\auth.php websocket\server.php"
for %%f in (%REQUIRED_FILES%) do (
    if not exist "%%f" (
        echo ❌ 缺少必要文件: %%f
        set /a ERRORS+=1
        set "CHECK_PASSED=0"
    ) else (
        echo ✅ %%f
    )
)
echo.

:: 4. 檢查數據庫文件
echo 📊 [4/8] 檢查數據庫...
if exist "data\pythonlearn.db" (
    echo ✅ 數據庫文件存在
) else (
    echo ⚠️ 數據庫文件不存在，首次運行時會自動創建
    set /a WARNINGS+=1
)
echo.

:: 5. 檢查Composer依賴
echo 📦 [5/8] 檢查 Composer 依賴...
if exist "vendor\autoload.php" (
    echo ✅ Composer 依賴已安裝
) else (
    echo ❌ Composer 依賴未安裝，請運行: composer install
    set /a ERRORS+=1
    set "CHECK_PASSED=0"
)
echo.

:: 6. 運行整合驗證
echo 🔄 [6/8] 運行整合驗證...
php scripts\validate-integration.php >nul 2>&1
if errorlevel 1 (
    echo ❌ 整合驗證失敗
    set /a ERRORS+=1
    set "CHECK_PASSED=0"
) else (
    echo ✅ 整合驗證通過
)
echo.

:: 7. 檢查測試覆蓋率
echo 🧪 [7/8] 檢查測試環境...
if exist "test-servers\api-test\test_api_server.php" (
    echo ✅ 測試服務器文件存在
) else (
    echo ⚠️ 測試服務器文件不完整
    set /a WARNINGS+=1
)
echo.

:: 8. 檢查文檔更新
echo 📚 [8/8] 檢查文檔...
if exist "README.md" (
    echo ✅ README.md 存在
) else (
    echo ⚠️ 缺少 README.md
    set /a WARNINGS+=1
)

if exist "DEVELOPMENT_WORKFLOW.md" (
    echo ✅ 開發流程文檔存在
) else (
    echo ⚠️ 缺少開發流程文檔
    set /a WARNINGS+=1
)
echo.

:: 生成檢查報告
echo 📊 ========================================
echo    檢查結果摘要
echo ========================================
echo.
echo 📈 統計:
echo   ❌ 錯誤: %ERRORS%
echo   ⚠️ 警告: %WARNINGS%
echo.

if %CHECK_PASSED%==1 (
    echo 🎉 所有檢查通過！可以安全提交代碼。
    echo.
    echo 💡 建議的提交流程:
    echo   1. git add .
    echo   2. git commit -m "feat: 描述你的更改"
    echo   3. git push origin main
    echo.
    
    :: 詢問是否自動提交
    set /p AUTO_COMMIT="是否要自動執行 git add . ? (y/n): "
    if /i "%AUTO_COMMIT%"=="y" (
        echo.
        echo 📤 執行 git add ...
        git add .
        echo ✅ 文件已添加到暫存區
        echo.
        echo 💡 現在可以執行: git commit -m "你的提交信息"
    )
    
    exit /b 0
) else (
    echo ❌ 檢查失敗！請修復以下問題後再提交:
    echo.
    
    if %ERRORS% gtr 0 (
        echo 🚨 必須修復的錯誤:
        echo   - 修復 PHP 語法錯誤
        echo   - 確保所有必要文件存在
        echo   - 安裝 Composer 依賴
        echo   - 通過整合驗證測試
        echo.
    )
    
    if %WARNINGS% gtr 0 (
        echo ⚠️ 建議處理的警告:
        echo   - 檢查 JavaScript 文件內容
        echo   - 確保數據庫文件存在
        echo   - 更新文檔
        echo.
    )
    
    echo 🔧 修復建議:
    echo   1. 運行: php -l 文件名.php  (檢查PHP語法)
    echo   2. 運行: composer install   (安裝依賴)
    echo   3. 運行: php scripts\validate-integration.php (詳細驗證)
    echo   4. 運行測試服務器確保功能正常
    echo.
    
    exit /b 1
)

:: 保存檢查日誌
echo %date% %time% - 錯誤:%ERRORS% 警告:%WARNINGS% >> logs\pre-commit-check.log 