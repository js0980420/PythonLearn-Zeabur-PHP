<?php
// 登入功能測試腳本
echo "🧪 測試登入API功能\n";
echo "==================\n\n";

// 測試登入
$testData = [
    'action' => 'login',
    'username' => 'test_user_' . time(),
    'user_type' => 'student'
];

$postData = http_build_query($testData);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/backend/api/auth.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📡 HTTP狀態碼: $httpCode\n";

if ($error) {
    echo "❌ cURL錯誤: $error\n";
} else {
    echo "✅ cURL請求成功\n";
    echo "📄 響應內容:\n";
    echo $response . "\n\n";
    
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        echo "🎉 登入測試成功！\n";
        echo "👤 用戶ID: " . $result['data']['user_id'] . "\n";
        echo "👤 用戶名: " . $result['data']['username'] . "\n";
        echo "🏷️  用戶類型: " . $result['data']['user_type'] . "\n";
    } else {
        echo "❌ 登入測試失敗！\n";
        if ($result) {
            echo "錯誤訊息: " . $result['message'] . "\n";
        }
    }
}

echo "\n完成時間: " . date('Y-m-d H:i:s') . "\n";
?> 