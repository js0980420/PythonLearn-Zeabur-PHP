<?php
/**
 * 歷史記錄 API 端點
 * 為前端提供歷史記錄數據
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $roomId = $_GET['room_id'] ?? '';
    
    if (empty($roomId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => '缺少 room_id 參數'
        ]);
        exit();
    }
    
    // 模擬歷史記錄數據（實際應用中應該從數據庫獲取）
    $historyData = [
        [
            'id' => 1,
            'name' => '初始代碼',
            'timestamp' => date('c', strtotime('-1 hour')),
            'user' => '系統',
            'description' => '房間初始化代碼'
        ],
        [
            'id' => 2,
            'name' => 'Hello World 範例',
            'timestamp' => date('c', strtotime('-30 minutes')),
            'user' => '教師',
            'description' => 'Python 基礎範例'
        ],
        [
            'id' => 3,
            'name' => '變數練習',
            'timestamp' => date('c', strtotime('-15 minutes')),
            'user' => '學生A',
            'description' => '變數宣告和使用練習'
        ]
    ];
    
    // 返回成功響應
    echo json_encode([
        'success' => true,
        'room_id' => $roomId,
        'history' => $historyData,
        'count' => count($historyData),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '服務器錯誤: ' . $e->getMessage()
    ]);
}
?> 