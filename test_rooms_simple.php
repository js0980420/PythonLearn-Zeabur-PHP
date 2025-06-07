<?php
// 簡單的房間API測試
echo "🧪 測試房間API\n";
echo "===============\n\n";

// 直接測試房間列表API，檢查JSON響應
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/backend/api/rooms.php?action=list',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP狀態碼: $httpCode\n";

if ($error) {
    echo "❌ cURL錯誤: $error\n";
} else {
    echo "✅ cURL請求成功\n";
    echo "📄 響應內容:\n";
    echo $response . "\n\n";
    
    if (empty($response)) {
        echo "❌ 響應為空，可能有致命錯誤\n";
    } else {
        $result = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ JSON解析成功\n";
            if ($result['success']) {
                echo "🎉 API正常運作\n";
            } else {
                echo "⚠️  API返回錯誤: " . $result['message'] . "\n";
            }
        } else {
            echo "❌ JSON解析失敗: " . json_last_error_msg() . "\n";
        }
    }
}

echo "\n完成時間: " . date('Y-m-d H:i:s') . "\n";
?> 