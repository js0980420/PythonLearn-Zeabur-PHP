@echo off
echo 🎭 啟動 Playwright MCP 服務器...
echo ================================
echo.
echo 服務器將在 Port 8931 啟動
echo 按 Ctrl+C 停止服務器
echo.
npx @playwright/mcp --port 8931
pause 