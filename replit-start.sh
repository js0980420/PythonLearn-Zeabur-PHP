#!/bin/bash

echo "🚀 啟動 Python 協作學習平台..."

# 安裝 Composer 依賴
echo "📦 安裝依賴..."
composer install --no-dev --optimize-autoloader

# 檢查環境變數
if [ -z "$OPENAI_API_KEY" ]; then
    echo "⚠️  警告: OPENAI_API_KEY 未設置，AI 功能將使用模擬模式"
else
    echo "✅ OpenAI API Key 已設置"
fi

# 創建必要的目錄
mkdir -p storage
mkdir -p data

# 啟動 WebSocket 服務器（背景執行）
echo "🔌 啟動 WebSocket 服務器 (端口 8081)..."
php websocket/server.php &
WEBSOCKET_PID=$!

# 等待 WebSocket 服務器啟動
sleep 3

# 檢查 WebSocket 服務器是否啟動成功
if ps -p $WEBSOCKET_PID > /dev/null; then
    echo "✅ WebSocket 服務器啟動成功 (PID: $WEBSOCKET_PID)"
else
    echo "❌ WebSocket 服務器啟動失敗"
fi

# 啟動 PHP 開發服務器
echo "🌐 啟動 PHP 服務器 (端口 8080)..."
echo "📱 應用將在 Replit 提供的 URL 上運行"
echo "🔌 WebSocket 連接: ws://your-repl-url:8081"
echo "🎉 準備就緒！開始協作學習吧！"

# 設置清理函數
cleanup() {
    echo "🛑 正在停止服務器..."
    if ps -p $WEBSOCKET_PID > /dev/null; then
        kill $WEBSOCKET_PID
        echo "✅ WebSocket 服務器已停止"
    fi
    exit 0
}

# 捕獲中斷信號
trap cleanup SIGINT SIGTERM

php -S 0.0.0.0:8080 router.php 