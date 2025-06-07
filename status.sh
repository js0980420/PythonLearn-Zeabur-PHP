#!/bin/bash

# PythonLearn-Zeabur-PHP 狀態檢查腳本

echo "📊 PythonLearn-Zeabur-PHP 服務狀態"
echo "=================================="

# 檢查 WebSocket 服務器
if [ -f .websocket.pid ]; then
    WEBSOCKET_PID=$(cat .websocket.pid)
    if ps -p $WEBSOCKET_PID > /dev/null; then
        echo "🔌 WebSocket 服務器: ✅ 運行中 (PID: $WEBSOCKET_PID)"
        
        # 檢查端口 8081
        if lsof -Pi :8081 -sTCP:LISTEN -t >/dev/null 2>&1; then
            echo "   端口 8081: ✅ 監聽中"
        else
            echo "   端口 8081: ❌ 未監聽"
        fi
    else
        echo "🔌 WebSocket 服務器: ❌ 未運行"
        rm -f .websocket.pid
    fi
else
    echo "🔌 WebSocket 服務器: ❌ 未啟動"
fi

# 檢查 Web 服務器
if [ -f .web.pid ]; then
    WEB_PID=$(cat .web.pid)
    if ps -p $WEB_PID > /dev/null; then
        echo "🌐 Web 服務器: ✅ 運行中 (PID: $WEB_PID)"
        
        # 檢查端口 8080
        if lsof -Pi :8080 -sTCP:LISTEN -t >/dev/null 2>&1; then
            echo "   端口 8080: ✅ 監聽中"
        else
            echo "   端口 8080: ❌ 未監聽"
        fi
    else
        echo "🌐 Web 服務器: ❌ 未運行"
        rm -f .web.pid
    fi
else
    echo "🌐 Web 服務器: ❌ 未啟動"
fi

echo ""

# 檢查服務可用性
echo "🔍 服務可用性測試:"

# 測試 Web 服務器
if curl -s http://localhost:8080 > /dev/null 2>&1; then
    echo "   Web 服務: ✅ 可訪問"
else
    echo "   Web 服務: ❌ 無法訪問"
fi

# 測試 WebSocket (簡單檢查)
if nc -z localhost 8081 2>/dev/null; then
    echo "   WebSocket: ✅ 端口開放"
else
    echo "   WebSocket: ❌ 端口關閉"
fi

echo ""

# 顯示訪問地址
echo "📱 訪問地址:"
echo "   本地: http://localhost:8080"
if command -v hostname &> /dev/null; then
    LOCAL_IP=$(hostname -I | awk '{print $1}' 2>/dev/null || echo "未知")
    if [ "$LOCAL_IP" != "未知" ] && [ -n "$LOCAL_IP" ]; then
        echo "   網絡: http://$LOCAL_IP:8080"
    fi
fi

echo ""

# 顯示日誌文件
echo "📋 日誌文件:"
if [ -f logs/websocket.log ]; then
    WS_LOG_SIZE=$(du -h logs/websocket.log | cut -f1)
    echo "   WebSocket: logs/websocket.log ($WS_LOG_SIZE)"
else
    echo "   WebSocket: 無日誌文件"
fi

if [ -f logs/web.log ]; then
    WEB_LOG_SIZE=$(du -h logs/web.log | cut -f1)
    echo "   Web 服務: logs/web.log ($WEB_LOG_SIZE)"
else
    echo "   Web 服務: 無日誌文件"
fi

echo ""

# 顯示管理命令
echo "🔧 管理命令:"
echo "   啟動服務: ./start.sh"
echo "   停止服務: ./stop.sh"
echo "   查看日誌: tail -f logs/websocket.log"
echo "   重啟服務: ./stop.sh && ./start.sh" 