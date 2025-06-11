@echo off
echo 🔍 啟動 Tavily MCP 服務器...
echo ==============================
echo.
set /p api_key="請輸入您的 Tavily API Key: "
if "%api_key%"=="" (
    echo ❌ 未輸入 API Key，程序結束
    pause
    exit /b 1
)
echo.
echo 服務器將在 Port 8932 啟動
echo 按 Ctrl+C 停止服務器
echo.
set TAVILY_API_KEY=%api_key%
npx tavily-mcp --port 8932
pause 