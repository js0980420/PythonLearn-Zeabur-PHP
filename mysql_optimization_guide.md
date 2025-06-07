# MySQL 優化建議流程

本文件概述了針對 PythonLearn-Zeabur 平台 MySQL 資料庫的優化步驟，旨在確保核心功能的穩定運行。

## 1. 確認與建立資料庫連線及設定

*   **目標：** 確保後端能穩定、安全地連接到 MySQL 資料庫。
*   **操作：**
    *   檢查 `backend/classes/Database.php` 和 `backend/config/database.php` 檔案。
    *   確保 `Database.php` 使用 `mysqli` 擴展（現為 `PDO`），並具備連接 MySQL 和 SQLite 的邏輯。
    *   確保 `database.php` 中的配置符合 Zeabur 環境變數規範，並能自動偵測 XAMPP 設定。
    *   確保 `Database::getInstance()->initialize();` 在應用程式啟動時被正確呼叫（已在 `backend/api/health.php` 中添加）。

## 2. 定義資料庫 Schema 並建立核心資料表

*   **目標：** 建立一個穩固的資料儲存基礎，所有資料表遵循小寫和下劃線命名規範，並包含 `id`、`created_at`、`updated_at` 等標準欄位。
*   **操作：**
    *   在 `backend/classes/Database.php` 中定義並修正以下資料表 Schema：
        *   **`users` 表**：儲存使用者名稱、使用者類型。
        *   **`rooms` 表**：儲存房間 ID、房間名稱、密碼，並新增 `current_code` 欄位。
        *   **`room_users` 表**：儲存房間成員，`room_id` 欄位類型修正為 `INT` 並添加外鍵約束。
        *   **`code_history` 表**：儲存代碼歷史版本，`room_id` 欄位類型修正為 `INT` 並添加外鍵約束，`code_content` 更名為 `code`，`save_name` 更名為 `description`。
        *   **`chat_messages` 表**：新增用於儲存聊天訊息，包括 `room_id`、`user_id`、`message_content`、`created_at`，並添加外鍵約束。
        *   `conflicts` 表：儲存衝突信息，`room_id` 欄位類型修正為 `INT`。
        *   `ai_requests` 表：儲存 AI 請求記錄。

## 3. 整合使用者與房間管理到 MySQL

*   **目標：** 實現最基本的使用者與房間管理功能，為後續的代碼操作和協作功能奠定基礎。
*   **操作：**
    *   修改 `backend/api/auth.php`：
        *   將 `MockDatabase` 替換為實際的 `Database` 類別。
        *   移除 `addTestData()` 呼叫。
    *   修改 `backend/api/rooms.php`：
        *   將 `MockDatabase` 替換為實際的 `Database` 類別。
        *   移除 `addTestData()` 呼叫。
        *   確保所有房間和用戶操作透過 `Database` 類別與 MySQL 互動。

## 4. 整合代碼保存、載入與歷史紀錄到 MySQL

*   **目標：** 實現代碼內容的可靠儲存與版本追溯。
*   **操作：**
    *   修改 `backend/api/code.php`：
        *   將 `MockDatabase` 替換為實際的 `Database` 類別。
        *   移除 `addTestData()` 呼叫。
        *   調整 `handleSaveCode` 和 `handleLoadCode` 函數，使其與 `rooms.current_code` 和 `code_history` 表中的 `code`、`description` 欄位正確互動，並處理版本號邏輯。
        *   確保 `user_id` 從 `$_SESSION['user_id']` 獲取，並為 `INT` 型別。
    *   修改 `backend/api/history.php`：
        *   將 `MockDatabase` 替換為實際的 `Database` 類別。
        *   移除 `addTestData()` 呼叫。
        *   調整 `handleGetHistory`、`handleLoadVersion` 和 `handleSaveVersion` 函數，使其與 `code_history` 表正確互動，並確保 `user_id` 的正確性。

## 5. 整合聊天室訊息到 MySQL

*   **目標：** 確保聊天記錄不會因斷線而丟失。
*   **操作：**
    *   新增 `backend/api/chat.php`：提供 HTTP API 端點，用於獲取聊天歷史記錄和備用發送訊息功能。
    *   修改 `websocket/server.php`：
        *   將 `MockDatabase` 替換為實際的 `Database` 類別。
        *   調整 `handleChatMessage` 函數，使其將聊天訊息保存到 `chat_messages` 資料表，並廣播給房間內其他用戶。

## 6. 整合教師監控功能到 MySQL

*   **目標：** 提供教師後台所需的資料來源。
*   **操作：**
    *   修改 `backend/classes/Room.php`：將其從檔案系統操作全面轉換為使用 `Database` 類別與 MySQL 互動，重寫所有與房間、用戶和代碼相關的資料庫操作函數。
    *   修改 `backend/api/teacher.php`：更新其使用 `Room` 類別中更新後的方法，從 MySQL 資料庫中取得房間和用戶的即時監控數據。

## 7. 完善錯誤處理與資料驗證

*   **目標：** 提高系統的穩定性和安全性。
*   **操作：**
    *   確保所有與 MySQL 的互動都包含健壯的錯誤處理機制，並將錯誤詳細記錄到日誌中。
    *   所有傳入 MySQL 的資料都將經過嚴格的驗證和清理，以防止 SQL 注入和其他安全問題。 