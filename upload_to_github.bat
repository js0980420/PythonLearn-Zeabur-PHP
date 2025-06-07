@echo off
chcp 65001 >nul
echo 🚀 PythonLearn-Zeabur-PHP GitHub 上傳工具
echo ==========================================

:: 檢查是否在正確的目錄
if not exist "README.md" (
    echo ❌ 錯誤：請在項目根目錄運行此腳本
    echo 當前目錄應包含 README.md 文件
    pause
    exit /b 1
)

echo ✅ 項目目錄確認正確

:: 檢查 Git 是否安裝
git --version >nul 2>&1
if errorlevel 1 (
    echo ❌ 錯誤：未找到 Git，請先安裝 Git
    echo 下載地址：https://git-scm.com/download/win
    pause
    exit /b 1
)

echo ✅ Git 已安裝

:: 顯示當前 Git 狀態
echo.
echo 📊 檢查 Git 狀態...
git status

echo.
echo 📝 準備提交的文件：
echo - README.md (項目說明)
echo - LICENSE (MIT 許可證)
echo - .gitignore (Git 忽略配置)
echo - MOBILE_GUIDE.md (手機端指南)
echo - UPLOAD_GUIDE.md (上傳指南)
echo - start.sh (Linux/Mac 啟動腳本)
echo - stop.sh (停止腳本)
echo - status.sh (狀態檢查腳本)
echo - 以及其他項目文件...

echo.
set /p confirm="是否繼續上傳到 GitHub？(y/N): "
if /i not "%confirm%"=="y" (
    echo 取消上傳
    pause
    exit /b 0
)

:: 添加所有文件
echo.
echo 📦 添加文件到 Git...
git add .

:: 檢查是否有文件被添加
git diff --cached --quiet
if errorlevel 1 (
    echo ✅ 文件已添加到暫存區
) else (
    echo ⚠️ 沒有檢測到新的更改
)

:: 提交更改
echo.
echo 💾 提交更改...
git commit -m "🎉 準備 GitHub 版本 - 添加完整文檔和手機端支持

✨ 新增功能:
- 📖 完整的 README.md 文檔
- 📱 手機端使用指南 (MOBILE_GUIDE.md)
- 🚀 Linux/Mac 啟動腳本 (start.sh, stop.sh, status.sh)
- 📄 MIT 許可證
- 🚫 優化的 .gitignore 配置
- 📤 GitHub 上傳指南

🔧 技術改進:
- 測試服務器穩定運行
- WebSocket 連接優化
- 用戶重複登入問題修復
- 手機端觸控優化

🎯 協作特色:
- 支持 GitHub Codespaces
- 支持 Replit 部署
- 實時多人協作
- AI 助手集成"

if errorlevel 1 (
    echo ❌ 提交失敗，可能沒有新的更改
) else (
    echo ✅ 提交成功
)

:: 檢查遠程倉庫
echo.
echo 🔍 檢查遠程倉庫配置...
git remote -v

:: 推送到 GitHub
echo.
echo 📤 推送到 GitHub...
echo 注意：如果是第一次推送，可能需要設置遠程倉庫

git push origin main
if errorlevel 1 (
    echo.
    echo ⚠️ 推送失敗，可能的原因：
    echo 1. 遠程倉庫未設置
    echo 2. 需要身份驗證
    echo 3. 分支名稱不匹配
    echo.
    echo 💡 解決方案：
    echo 1. 在 GitHub 創建新倉庫
    echo 2. 設置遠程倉庫：
    echo    git remote add origin https://github.com/YOUR_USERNAME/PythonLearn-Zeabur-PHP.git
    echo 3. 推送：git push -u origin main
    echo.
    echo 📖 詳細說明請查看 UPLOAD_GUIDE.md
) else (
    echo ✅ 推送成功！
    echo.
    echo 🎉 恭喜！項目已成功上傳到 GitHub
    echo.
    echo 📱 接下來您可以：
    echo 1. 在 GitHub 倉庫頁面啟用 Codespaces
    echo 2. 分享倉庫 URL 給團隊成員
    echo 3. 使用手機通過 Codespaces 進行協作
    echo.
    echo 📚 手機端使用指南：MOBILE_GUIDE.md
)

echo.
echo 🔗 有用的鏈接：
echo - GitHub: https://github.com
echo - Codespaces: https://github.com/features/codespaces
echo - Replit: https://replit.com

pause 