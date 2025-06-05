@echo off
chcp 65001 >nul
echo 🚀 啟動 Python 多人協作教學平台...
echo.

echo 📦 檢查依賴項...
if not exist vendor\autoload.php (
    echo ❌ 缺少 Composer 依賴項，請運行: composer install
    pause
    exit /b 1
)

echo 🗄️ 初始化數據庫...
php simple_init.php
echo.

echo 🌐 啟動 Web 服務器 (端口 8000)...
start cmd /k "title Web Server (8000) && php -S localhost:8000"

timeout /t 2 /nobreak >nul

echo 🔌 啟動 WebSocket 服務器 (端口 8080)...
start cmd /k "title WebSocket Server (8080) && php websocket_server.php"

timeout /t 3 /nobreak >nul

echo ✅ 服務器啟動完成！
echo.
echo 📄 系統地址:
echo    - 主頁: http://localhost:8000
echo    - 健康檢查: http://localhost:8000/api/health
echo.
echo 🎯 AI 助教功能已啟用 (使用真實 OpenAI API)
echo 🤝 多人協作功能已就緒
echo 💬 聊天系統已啟用
echo ⚡ 衝突檢測已啟用
echo.

choice /c YN /m "是否立即打開瀏覽器"
if %errorlevel%==1 start http://localhost:8000

echo.
echo 按任意鍵退出...
pause >nul 