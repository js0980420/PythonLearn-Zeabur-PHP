<?php

echo "=== Python協作平台 API測試 ===\n\n";

// 測試認證API
echo "1. 測試認證API\n";
echo "-------------------\n";

// 模擬POST請求
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [];
file_put_contents('php://input', json_encode([
    'action' => 'login',
    'username' => '測試用戶',
    'user_type' => 'student'
]));

ob_start();
include 'backend/api/auth.php';
$authResult = ob_get_clean();

echo "登入結果: " . $authResult . "\n\n";

// 測試房間API
echo "2. 測試房間API\n";
echo "-------------------\n";

// 設置會話
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = '測試用戶';

ob_start();
include 'backend/api/rooms.php';
$roomResult = ob_get_clean();

echo "房間列表結果: " . $roomResult . "\n\n";

echo "=== 測試完成 ===\n"; 