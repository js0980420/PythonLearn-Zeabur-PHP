# ⚡ Cursor MCP Server 安裝與配置指南

本指南將詳細說明如何在您的本地開發環境中安裝和配置常用的 Cursor MCP (Model Context Protocol) Server。這些 MCP Server 將增強 Cursor 內建 AI 助理的上下文理解能力，提升開發效率，且不會影響您現有的 PHP 專案程式碼。

---

## 📋 前置條件：安裝 Node.js

儘管您的專案後端是 PHP，但大多數 Cursor MCP Server 是基於 Node.js 或 Python 環境運行。因此，您需要在本地開發機器上安裝 Node.js。

**注意：** 安裝 Node.js 僅用於運行這些本地開發工具，它不會影響您的 PHP 專案程式碼或部署流程。

### 安裝 Node.js 的推薦方式 (Windows)

1.  **使用 `winget` (Windows 封裝管理器)**：
    如果您的 Windows 系統版本較新 (Windows 10 1709 版本後通常內建)，可以在 PowerShell 或命令提示字元中運行：
    ```powershell
    winget install OpenJS.Nodejs
    ```
2.  **手動下載安裝器**：
    前往 Node.js 官方網站 ([https://nodejs.org/zh-cn/download](https://nodejs.org/zh-cn/download))，下載最新推薦的 LTS (長期支援) 版本安裝器，然後按照提示一步步完成安裝。這將同時安裝 Node.js 和 npm (Node Package Manager)。

### 定位 Cursor MCP 配置檔案 (`mcp.json`)

所有 MCP Server 的配置都需要添加到 Cursor 的配置檔案 `mcp.json` 中。如果此檔案不存在，您需要手動創建它。

*   **Windows 路徑**: `C:\Users\您的用戶名\.cursor\mcp.json`
*   **macOS/Linux 路徑**: `~/.cursor/mcp.json`

此檔案的初始內容應該是一個空的 JSON 物件，例如：
```json
{
  "mcpServers": {}
}
```
您將在 `mcpServers` 物件中添加每個 MCP Server 的具體配置。

---

## 🔧 安裝與配置 MCP Server

每次修改 `mcp.json` 後，請務必**重啟 Cursor 編輯器**，以載入新的 MCP Server 配置。

### 1. Apidog MCP Server (用於 API 規範理解)

*   **說明**: 將您的 API 文件（如 OpenAPI 規範）提供給 AI 助理，讓 AI 了解後端 API 的結構和細節，提升程式碼生成和問題診斷的精準度。
*   **安裝**: Apidog MCP Server 通常透過 `npx` 命令運行，無需預先全域安裝。
*   **配置 `mcp.json`**: 在 `mcpServers` 物件中加入以下配置。請將 `<your-apidog-project-id>` 替換為您的 Apidog 專案 ID，並將 `<your-apidog-access-token>` 替換為您的 Apidog Access Token。
    ```json
    // ... existing code ...
    "Apidog API Specs": {
      "command": "cmd",
      "args": [
        "/c",
        "npx",
        "-y",
        "apidog-mcp-server@latest",
        "--project=<your-apidog-project-id>"
      ],
      "env": {
        "APIDOG_ACCESS_TOKEN": "<your-apidog-access-token>"
      }
    }
    // ... existing code ...
    ```
    *   **`"Apidog API Specs"`**: 您為這個 MCP Server 自定義的名稱。
    *   **`"command": "cmd"` 和 `"args": ["/c", "npx", ...]`**: 適用於 Windows 系統。如果您是 macOS 或 Linux，`"command"` 通常直接是 `"npx"`，`"args"` 則不需要 `/c`。
*   **驗證**: 重啟 Cursor。在 Cursor Agent 模式下，您可以嘗試問 AI 關於您的 Apidog 專案的 API 問題，例如：「請透過 Apidog API Specs 獲取 API 規範，並告訴我專案中有多少個端點？」

### 2. Magic MCP Server (用於生成式 AI 能力)

*   **說明**: 整合生成式 AI 能力，用於生成程式碼範例、文本轉換、內容摘要等，增強 AI 助教的輸出品質。
*   **安裝**: Magic MCP Server 也推薦使用 `npx` 運行。
*   **配置 `mcp.json`**: 在 `mcpServers` 物件中加入以下配置。請將 `<your-openai-api-key>` 替換為您的 OpenAI API Key。
    ```json
    // ... existing code ...
    "Magic AI Generator": {
      "command": "npx",
      "args": ["-y", "@21st-dev/magic@latest", "API_KEY="<your-openai-api-key>""]
    }
    // ... existing code ...
    ```
    *   **`"Magic AI Generator"`**: 您為這個 MCP Server 自定義的名稱。
*   **驗證**: 重啟 Cursor。在 Composer 或 Agent 模式下，您可以嘗試讓 AI 生成一些程式碼片段或對文本進行摘要，觀察是否能利用 Magic MCP Server 的能力。

### 3. Opik MCP Server / Tavily MCP Server (用於即時網路搜尋)

**這兩個工具擇一或都安裝，取決於您的需求。它們都提供即時網路搜尋能力。**

#### Opik MCP Server

*   **說明**: 提供 AI 助理即時的網路搜尋能力，避免依賴過時的訓練資料，確保 AI 助教的知識是最新的。
*   **安裝**:
    1.  開啟終端機，選擇一個您想存放 MCP Server 的目錄（例如：`C:\Users\您的用戶名\MCP_Servers`）。
    2.  克隆 Opik MCP Server 專案：
        ```bash
        git clone https://github.com/comet-ml/opik-mcp.git
        ```
    3.  進入專案目錄：
        ```bash
        cd opik-mcp
        ```
    4.  安裝依賴並建構專案：
        ```bash
        npm install
        npm run build
        ```
*   **配置 `mcp.json`**: 在 `mcpServers` 物件中加入以下配置。請將 `<absolute-path-to-node-executable>` 替換為您 Node.js 可執行檔的絕對路徑（例如：`C:\Program Files\nodejs\node.exe`），將 `<absolute-path-to-opik-mcp-directory>` 替換為您克隆的 Opik MCP 專案目錄的絕對路徑，以及 `<your-opik-api-key>` 替換為您的 Opik API Key。
    ```json
    // ... existing code ...
    "Opik Web Search": {
      "command": "<absolute-path-to-node-executable>",
      "args": [
        "<absolute-path-to-opik-mcp-directory>/build/index.js",
        "--apiUrl",
        "https://www.comet.com/opik/api",
        "--apiKey",
        "<your-opik-api-key>",
        "--workspace",
        "default",
        "--debug",
        "true"
      ],
      "env": {
        "OPIK_API_BASE_URL": "https://www.comet.com/opik/api",
        "OPIK_API_KEY": "<your-opik-api-key>",
        "OPIK_WORKSPACE_NAME": "default"
      }
    }
    // ... existing code ...
    ```

#### Tavily MCP Server

*   **說明**: 專注於提供高品質、策劃過的知識檢索，結合多個知識來源，讓 AI 助理獲得更相關的資訊。
*   **安裝**:
    1.  您可能需要先安裝 `uv` (一個用於管理 Python 虛擬環境和依賴的工具)。請參考 `uv` 官方文件進行安裝。
    2.  克隆 Tavily MCP Server 專案到您選擇的目錄。
*   **配置 `mcp.json`**: 在 `mcpServers` 物件中加入以下配置。請將 `<absolute-path-to-tavily-mcp-directory>` 替換為您 Tavily MCP 專案的絕對路徑，並將 `<your-tavily-api-key>` 替換為您的 Tavily API Key。
    ```json
    // ... existing code ...
    "Tavily Curated Search": {
      "command": "uv",
      "args": [
        "--directory",
        "<absolute-path-to-tavily-mcp-directory>",
        "run",
        "tavily-search"
      ],
      "env": {
        "TAVILY_API_KEY": "<your-tavily-api-key>",
        "PYTHONIOENCODING": "utf-8"
      }
    }
    // ... existing code ...
    ```
*   **驗證**: 重啟 Cursor。在 Cursor Agent 模式下，您可以嘗試讓 AI 搜尋最新的技術資訊或回答需要即時網路查詢的問題。

### 4. Playwright MCP Server (用於前端自動化測試)

*   **說明**: 讓 AI 助理能夠理解並互動網頁的 DOM 上下文，常用於自動化測試、問題重現與診斷。
*   **安裝**: 您可以在任何一個專案目錄（不一定是您的 PHP 專案目錄，可以是一個專門用於 MCP Server 的目錄）中安裝 `playwright-mcp`。
    ```bash
    npm install playwright-mcp
    ```
*   **配置 `mcp.json`**: 在 `mcpServers` 物件中加入以下配置。
    ```json
    // ... existing code ...
    "Playwright UI Tester": {
      "command": "npx",
      "args": ["-y", "playwright-mcp"]
    }
    // ... existing code ...
    ```
*   **驗證**: 重啟 Cursor。在 Cursor Agent 模式下，您可以嘗試要求 AI 執行與網頁互動的任務，例如：「檢查 `localhost:8080` 上登入按鈕的文字是什麼？」或者「為 `index.html` 中的表單生成測試案例。」

---

## 疑難排解 (Troubleshooting)

如果 MCP Server 未能成功連接或運行，請檢查以下幾點：

1.  **重啟 Cursor**: 每次修改 `mcp.json` 後，務必重啟 Cursor。
2.  **Cursor 設定頁面**: 在 Cursor 內部，前往 `設定 (Settings)` -> `功能 (Features)` -> `MCP` 頁面。檢查您配置的 MCP Server 是否被列出，並且狀態是否為「已連接」。如果狀態異常，通常會顯示錯誤訊息。
3.  **API Keys 檢查**: 確保您填寫的所有 API Key 都正確無誤，並且是有效的。
4.  **絕對路徑檢查**: 對於需要指定絕對路徑的 MCP Server (例如 Opik、Tavily)，請確保路徑是正確的，並且您的系統可以執行這些路徑下的文件。
5.  **終端機輸出**: 如果您是手動在終端機中運行 MCP Server (對於某些安裝方式可能需要)，請檢查終端機的輸出，通常會有詳細的錯誤日誌。
6.  **防火牆/網路**: 確保沒有防火牆規則阻擋了 MCP Server 的通訊。

希望這份指南能幫助您順利安裝和配置所有所需的 Cursor MCP Server！ 