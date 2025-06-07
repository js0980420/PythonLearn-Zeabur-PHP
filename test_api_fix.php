<?php
/**
 * 簡單的API測試腳本
 * 測試修復後的API是否返回純JSON
 */

echo "🧪 測試API修復...\n";

// 設置測試環境
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/auth';

// 模擬JSON輸入
$testInput = [
    'action' => 'login',
    'username' => 'Alex Wang',
    'user_type' => 'student'
];

// 模擬file_get_contents('php://input')
$GLOBALS['test_input'] = json_encode($testInput);

if (!function_exists('file_get_contents_original')) {
    function file_get_contents_original($filename) {
        return \file_get_contents($filename);
    }
}

// 重新定義file_get_contents（僅用於測試）
if (!function_exists('file_get_contents_mock')) {
    function file_get_contents_mock($filename) {
        if ($filename === 'php://input') {
            return $GLOBALS['test_input'];
        }
        return file_get_contents_original($filename);
    }
}

// 開始輸出緩衝
ob_start();

try {
    // 包含認證API
    include 'backend/api/auth.php';
    
    $output = ob_get_clean();
    
    echo "📤 請求: " . json_encode($testInput, JSON_UNESCAPED_UNICODE) . "\n";
    echo "📥 響應: " . $output . "\n";
    
    // 檢查是否為有效JSON
    $response = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ 響應是有效的JSON\n";
        
        if (isset($response['success'])) {
            if ($response['success']) {
                echo "✅ API認證成功\n";
            } else {
                echo "⚠️ API認證失敗: " . ($response['message'] ?? '未知錯誤') . "\n";
            }
        } else {
            echo "⚠️ 響應格式異常\n";
        }
    } else {
        echo "❌ 響應不是有效的JSON\n";
        echo "JSON錯誤: " . json_last_error_msg() . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ 測試異常: " . $e->getMessage() . "\n";
}

echo "\n🎯 測試完成\n";
?> 