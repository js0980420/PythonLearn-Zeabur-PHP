<?php
require_once 'classes/Database.php';

try {
    $db = new Database();
    
    echo "修復 code_history 表結構...\n";
    
    // 1. 檢查當前表結構
    echo "1. 檢查當前表結構...\n";
    $result = $db->query("DESCRIBE code_history");
    
    $hasVersionNumber = false;
    $hasSlotId = false;
    
    foreach ($result as $field) {
        if ($field['Field'] === 'version_number') {
            $hasVersionNumber = true;
        }
        if ($field['Field'] === 'slot_id') {
            $hasSlotId = true;
        }
    }
    
    echo "   version_number 字段存在: " . ($hasVersionNumber ? "是" : "否") . "\n";
    echo "   slot_id 字段存在: " . ($hasSlotId ? "是" : "否") . "\n";
    
    // 2. 如果存在 version_number 字段，將數據遷移到 slot_id
    if ($hasVersionNumber) {
        echo "2. 遷移 version_number 數據到 slot_id...\n";
        
        // 更新所有記錄，將 version_number 映射到 slot_id (0-4)
        $db->query("UPDATE code_history SET slot_id = CASE 
            WHEN version_number <= 0 THEN 0
            WHEN version_number = 1 THEN 1
            WHEN version_number = 2 THEN 2
            WHEN version_number = 3 THEN 3
            WHEN version_number >= 4 THEN 4
            ELSE 0
        END WHERE slot_id IS NULL OR slot_id = 0");
        
        echo "   ✅ 數據遷移完成\n";
        
        // 3. 刪除 version_number 字段
        echo "3. 刪除 version_number 字段...\n";
        $db->query("ALTER TABLE code_history DROP COLUMN version_number");
        echo "   ✅ version_number 字段已刪除\n";
    }
    
    // 4. 確保 slot_id 有默認值
    echo "4. 設置 slot_id 默認值...\n";
    $db->query("ALTER TABLE code_history MODIFY COLUMN slot_id INT NOT NULL DEFAULT 0");
    echo "   ✅ slot_id 字段已設置默認值 0\n";
    
    // 5. 驗證修復結果
    echo "5. 驗證修復結果...\n";
    $result = $db->query("DESCRIBE code_history");
    
    $finalHasVersionNumber = false;
    $finalHasSlotId = false;
    $slotIdDefault = null;
    
    foreach ($result as $field) {
        if ($field['Field'] === 'version_number') {
            $finalHasVersionNumber = true;
        }
        if ($field['Field'] === 'slot_id') {
            $finalHasSlotId = true;
            $slotIdDefault = $field['Default'];
        }
    }
    
    echo "   version_number 字段存在: " . ($finalHasVersionNumber ? "❌ 是" : "✅ 否") . "\n";
    echo "   slot_id 字段存在: " . ($finalHasSlotId ? "✅ 是" : "❌ 否") . "\n";
    echo "   slot_id 默認值: " . ($slotIdDefault !== null ? "✅ {$slotIdDefault}" : "❌ 無") . "\n";
    
    if (!$finalHasVersionNumber && $finalHasSlotId && $slotIdDefault !== null) {
        echo "\n🎉 數據庫表結構修復成功！\n";
    } else {
        echo "\n❌ 修復可能不完整，請檢查上述結果\n";
    }
    
} catch (Exception $e) {
    echo "❌ 修復失敗: " . $e->getMessage() . "\n";
}
?> 