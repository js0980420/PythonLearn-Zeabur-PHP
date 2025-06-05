<?php
/**
 * Python 教學多人協作平台 - 主路由檔案
 * 處理所有前端請求和 API 路由
 */

// 啟用錯誤報告（開發環境）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設置響應頭
header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 獲取請求路徑
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// 移除查詢參數
$path = strtok($path, '?');

// 路由處理
switch ($path) {
    case '/':
    case '/index.html':
        // 服務主頁面
        serveStaticFile('index.html');
        break;
        
    case '/teacher':
    case '/teacher-dashboard.html':
        // 服務教師後台
        serveStaticFile('teacher-dashboard.html');
        break;
        
    // API 路由
    case '/api/health':
        require_once 'backend/api/health.php';
        break;
        
    case '/api/auth':
        require_once 'backend/api/auth.php';
        break;
        
    case '/api/rooms':
        require_once 'backend/api/rooms.php';
        break;
        
    case '/api/code':
        require_once 'backend/api/code.php';
        break;
        
    case '/api/ai':
        require_once 'backend/api/ai.php';
        break;
        
    case '/api/history':
        require_once 'backend/api/history.php';
        break;
        
    case '/api/teacher':
        require_once 'backend/api/teacher.php';
        break;
    
    // 靜態資源路由
    default:
        // 檢查是否為靜態檔案
        if (preg_match('/\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$/', $path)) {
            serveStaticFile(ltrim($path, '/'));
        } else {
            // 404 錯誤
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => '請求的路徑不存在: ' . $path,
                'timestamp' => date('c')
            ]);
        }
        break;
}

/**
 * 服務靜態檔案
 */
function serveStaticFile($filePath) {
    // 安全檢查，防止目錄遍歷攻擊
    $filePath = str_replace(['../', './'], '', $filePath);
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo "檔案不存在: " . htmlspecialchars($filePath);
        return;
    }
    
    // 設置正確的 MIME 類型
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css', 
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf'
    ];
    
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $mimeType);
    
    // 輸出檔案內容
    readfile($filePath);
}

?> 