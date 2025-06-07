<?php
echo "開始測試...\n";

// 模擬 POST 請求
$_SERVER['REQUEST_METHOD'] = 'POST';

// 模擬 JSON 輸入
$input = json_encode([
    'action' => 'explain',
    'code' => 'print("Hello")'
]);

// 創建臨時檔案來模擬 php://input
$temp = tmpfile();
fwrite($temp, $input);
rewind($temp);

echo "測試完成\n"; 