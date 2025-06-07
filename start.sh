#!/bin/bash

# PythonLearn-Zeabur-PHP 快速啟動腳本
# 適用於 Linux/Mac/Codespaces/Replit

echo "🚀 PythonLearn-Zeabur-PHP 協作平台啟動中..."

# 檢查 PHP 是否安裝
if ! command -v php &> /dev/null; then
    echo "❌ 錯誤：未找到 PHP，請先安裝 PHP 8.1 或更高版本"
    exit 1
fi

# 顯示 PHP 版本
echo "✅ PHP 版本：$(php -v | head -n 1)"

# 檢查端口是否被占用
check_port() {
    local port=$1
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
        echo "⚠️ 端口 $port 已被占用，正在嘗試終止..."
        lsof -ti:$port | xargs kill -9 2>/dev/null || true
        sleep 2
    fi
}

# 檢查並清理端口
check_port 8080
check_port 8081

# 創建必要的目錄
mkdir -p data/rooms
mkdir -p logs
mkdir -p storage
mkdir -p temp

# 設置權限
chmod 755 data/rooms
chmod 755 logs
chmod 755 storage
chmod 755 temp

echo "📁 目錄結構已準備完成"

# 啟動 WebSocket 服務器
echo "🔌 啟動 WebSocket 服務器 (端口 8081)..."
cd websocket
php test_server.php > ../logs/websocket.log 2>&1 &
WEBSOCKET_PID=$!
cd ..

# 等待 WebSocket 服務器啟動
sleep 3

# 檢查 WebSocket 服務器是否啟動成功
if ps -p $WEBSOCKET_PID > /dev/null; then
    echo "✅ WebSocket 服務器已啟動 (PID: $WEBSOCKET_PID)"
else
    echo "❌ WebSocket 服務器啟動失敗，請檢查日誌：logs/websocket.log"
    exit 1
fi

# 啟動 Web 服務器
echo "🌐 啟動 Web 服務器 (端口 8080)..."
php -S 0.0.0.0:8080 -t public router.php > logs/web.log 2>&1 &
WEB_PID=$!

# 等待 Web 服務器啟動
sleep 2

# 檢查 Web 服務器是否啟動成功
if ps -p $WEB_PID > /dev/null; then
    echo "✅ Web 服務器已啟動 (PID: $WEB_PID)"
else
    echo "❌ Web 服務器啟動失敗，請檢查日誌：logs/web.log"
    kill $WEBSOCKET_PID 2>/dev/null
    exit 1
fi

# 保存 PID 到文件
echo $WEBSOCKET_PID > .websocket.pid
echo $WEB_PID > .web.pid

echo ""
echo "🎉 服務器啟動成功！"
echo ""
echo "📱 訪問地址："
echo "   本地：http://localhost:8080"
echo "   網絡：http://$(hostname -I | awk '{print $1}'):8080"
echo ""
echo "🔧 管理命令："
echo "   查看狀態：./status.sh"
echo "   停止服務：./stop.sh"
echo "   查看日誌：tail -f logs/websocket.log"
echo ""
echo "📚 使用指南："
echo "   1. 在瀏覽器中打開上述地址"
echo "   2. 輸入用戶名和房間名"
echo "   3. 開始協作編程！"
echo ""
echo "🤝 團隊協作："
echo "   分享訪問地址給團隊成員"
echo "   使用相同的房間名進行協作"
echo ""

# 如果是 Codespaces 環境，顯示特殊提示
if [ -n "$CODESPACES" ]; then
    echo "☁️ GitHub Codespaces 檢測到！"
    echo "   Codespaces 會自動提供公開 URL"
    echo "   請在 'PORTS' 標籤中查看端口 8080 的 URL"
    echo ""
fi

# 如果是 Replit 環境，顯示特殊提示
if [ -n "$REPL_ID" ]; then
    echo "🔄 Replit 檢測到！"
    echo "   請點擊右上角的 'Open in new tab' 按鈕"
    echo "   或使用 Replit 提供的公開 URL"
    echo ""
fi

echo "✨ 準備就緒，開始您的協作之旅！" 