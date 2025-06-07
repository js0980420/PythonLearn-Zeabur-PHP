<?php
// 測試房間API功能
echo "🧪 測試房間API功能\n";
echo "==================\n\n";

// 首先測試登入
echo "1. 測試登入...\n";
$loginData = [
    'action' => 'login',
    'username' => 'test_user_' . time(),
    'user_type' => 'student'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/backend/api/auth.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($loginData),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HEADER => true,
    CURLOPT_COOKIE_JAR => '/tmp/cookies.txt',
    CURLOPT_COOKIE_FILE => '/tmp/cookies.txt'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "登入響應碼: $httpCode\n";

// 提取JSON部分
$parts = explode("\r\n\r\n", $response, 2);
$jsonResponse = end($parts);
$loginResult = json_decode($jsonResponse, true);

if ($loginResult && $loginResult['success']) {
    echo "✅ 登入成功\n\n";
    
    // 測試房間列表
    echo "2. 測試房間列表...\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://localhost:8000/backend/api/rooms.php?action=list',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIE_JAR => '/tmp/cookies.txt',
        CURLOPT_COOKIE_FILE => '/tmp/cookies.txt'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "房間列表響應碼: $httpCode\n";
    
    if ($error) {
        echo "❌ cURL錯誤: $error\n";
    } else {
        echo "響應內容: $response\n";
        
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            echo "✅ 房間列表獲取成功\n";
        } else {
            echo "❌ 房間列表獲取失敗\n";
            if ($result) {
                echo "錯誤訊息: " . $result['message'] . "\n";
            }
        }
    }
    
} else {
    echo "❌ 登入失敗\n";
    if ($loginResult) {
        echo "錯誤訊息: " . $loginResult['message'] . "\n";
    }
}

echo "\n完成時間: " . date('Y-m-d H:i:s') . "\n";
?> 