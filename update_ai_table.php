<?php
require_once 'backend/classes/Database.php';

$db = App\Database::getInstance();

echo "開始更新 ai_requests 表結構...\n";

try {
    $db->execute('ALTER TABLE ai_requests ADD COLUMN prompt TEXT AFTER request_type');
    echo '✅ 添加 prompt 欄位成功\n';
} catch (Exception $e) {
    echo '⚠️ prompt 欄位可能已存在: ' . $e->getMessage() . '\n';
}

try {
    $db->execute('ALTER TABLE ai_requests ADD COLUMN response TEXT AFTER prompt');
    echo '✅ 添加 response 欄位成功\n';
} catch (Exception $e) {
    echo '⚠️ response 欄位可能已存在: ' . $e->getMessage() . '\n';
}

try {
    $db->execute('ALTER TABLE ai_requests ADD COLUMN execution_time DECIMAL(10,6) AFTER response');
    echo '✅ 添加 execution_time 欄位成功\n';
} catch (Exception $e) {
    echo '⚠️ execution_time 欄位可能已存在: ' . $e->getMessage() . '\n';
}

try {
    $db->execute('ALTER TABLE ai_requests ADD COLUMN token_usage INT AFTER execution_time');
    echo '✅ 添加 token_usage 欄位成功\n';
} catch (Exception $e) {
    echo '⚠️ token_usage 欄位可能已存在: ' . $e->getMessage() . '\n';
}

echo "表結構更新完成！\n"; 