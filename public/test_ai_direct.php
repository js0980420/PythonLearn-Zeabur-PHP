<?php
header('Content-Type: application/json; charset=utf-8');

// 模擬 AI API 請求
$_POST['action'] = 'analyze';
$_POST['code'] = 'print("hello world")';

try {
    // 包含 AI API 文件
    ob_start();
    include __DIR__ . '/api/ai.php';
    $output = ob_get_clean();

    echo $output;
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
