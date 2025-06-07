<?php

require_once __DIR__ . '/backend/classes/Database.php';
require_once __DIR__ . '/backend/classes/Logger.php'; // 確保 Logger 可用

use App\Database;
use App\Logger;

// 實例化 Logger 以便記錄初始化過程
$logger = new Logger('db_init.log', 'DEBUG'); // 獨立的日誌檔案，級別設為 DEBUG

try {
    $logger->info("開始執行資料庫初始化腳本");
    $db = Database::getInstance();
    
    // 確保日誌系統已連接到真正的資料庫
    // 這裡調用 initialize() 會自動創建表格並插入測試數據
    $db->initialize();
    
    $logger->info("資料庫初始化腳本執行完成");
    echo "資料庫初始化腳本執行完成，請檢查 logs/db_init.log 檔案獲取詳細資訊。\n";

} catch (Exception $e) {
    $logger->critical("資料庫初始化腳本執行失敗", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    echo "資料庫初始化腳本執行失敗：" . $e->getMessage() . "，請檢查 logs/db_init.log 檔案獲取詳細資訊。\n";
}

?> 