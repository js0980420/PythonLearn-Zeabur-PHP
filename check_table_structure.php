<?php
require_once 'backend/classes/Database.php';

$db = App\Database::getInstance();
$result = $db->query('DESCRIBE ai_requests');

echo "=== ai_requests 表結構 ===\n";
while ($row = $result->fetch_assoc()) {
    echo sprintf("%-15s %-20s %-5s %-10s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Default'] ?? 'NULL'
    );
}
?> 