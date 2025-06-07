#!/bin/bash

# PythonLearn-Zeabur-PHP 停止腳本

echo "🛑 正在停止 PythonLearn-Zeabur-PHP 服務..."

# 從 PID 文件停止服務
if [ -f .websocket.pid ]; then
    WEBSOCKET_PID=$(cat .websocket.pid)
    if ps -p $WEBSOCKET_PID > /dev/null; then
        echo "🔌 停止 WebSocket 服務器 (PID: $WEBSOCKET_PID)..."
        kill $WEBSOCKET_PID
        sleep 2
        if ps -p $WEBSOCKET_PID > /dev/null; then
            echo "⚠️ 強制終止 WebSocket 服務器..."
            kill -9 $WEBSOCKET_PID
        fi
        echo "✅ WebSocket 服務器已停止"
    else
        echo "ℹ️ WebSocket 服務器未運行"
    fi
    rm -f .websocket.pid
fi

if [ -f .web.pid ]; then
    WEB_PID=$(cat .web.pid)
    if ps -p $WEB_PID > /dev/null; then
        echo "🌐 停止 Web 服務器 (PID: $WEB_PID)..."
        kill $WEB_PID
        sleep 2
        if ps -p $WEB_PID > /dev/null; then
            echo "⚠️ 強制終止 Web 服務器..."
            kill -9 $WEB_PID
        fi
        echo "✅ Web 服務器已停止"
    else
        echo "ℹ️ Web 服務器未運行"
    fi
    rm -f .web.pid
fi

# 額外清理：終止所有相關的 PHP 進程
echo "🧹 清理殘留進程..."
pkill -f "test_server.php" 2>/dev/null || true
pkill -f "php -S.*8080" 2>/dev/null || true

echo "✅ 所有服務已停止" 