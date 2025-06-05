<?php

/**
 * XAMPP MySQL 數據庫配置
 * 支援多種常見的 XAMPP 設置
 */

// 常見的 XAMPP 配置選項
$xampp_configs = [
    // 標準 XAMPP (無密碼)
    [
        'host' => 'localhost',
        'port' => 3306,
        'username' => 'root',
        'password' => '',
        'name' => 'XAMPP 標準配置 (無密碼)'
    ],
    // XAMPP 有密碼的情況
    [
        'host' => 'localhost',
        'port' => 3306,
        'username' => 'root',
        'password' => 'root',
        'name' => 'XAMPP 配置 (密碼: root)'
    ],
    [
        'host' => 'localhost',
        'port' => 3306,
        'username' => 'root',
        'password' => 'password',
        'name' => 'XAMPP 配置 (密碼: password)'
    ],
    [
        'host' => 'localhost',
        'port' => 3306,
        'username' => 'root',
        'password' => '123456',
        'name' => 'XAMPP 配置 (密碼: 123456)'
    ],
    // 本地 MySQL 8.0 的情況
    [
        'host' => 'localhost',
        'port' => 3306,
        'username' => 'root',
        'password' => '',
        'name' => 'MySQL 8.0 配置'
    ]
];

// 嘗試連接函數
function testDatabaseConnection($config) {
    try {
        $dsn = sprintf(
            "mysql:host=%s;port=%d;charset=utf8mb4",
            $config['host'],
            $config['port']
        );
        
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// 自動檢測可用配置
$working_config = null;
foreach ($xampp_configs as $config) {
    if (testDatabaseConnection($config)) {
        $working_config = $config;
        break;
    }
}

// 如果找到可用配置，使用它；否則使用默認配置
if ($working_config) {
    $selected_config = $working_config;
    echo "<!-- ✅ 自動檢測到可用配置: {$working_config['name']} -->\n";
} else {
    // 降級到默認配置
    $selected_config = $xampp_configs[0];
    echo "<!-- ⚠️ 無法連接數據庫，使用默認配置 -->\n";
}

return [
    // 使用檢測到的配置
    'host' => $selected_config['host'],
    'port' => $selected_config['port'],
    'database' => 'python_collaboration',
    'username' => $selected_config['username'],
    'password' => $selected_config['password'],
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    
    // 連接選項
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_TIMEOUT => 10
    ],
    
    // 連接池設置
    'pool' => [
        'min_connections' => 2,
        'max_connections' => 10,
        'timeout' => 30
    ],
    
    // 配置信息
    'detected_config' => $working_config['name'] ?? '未知配置'
];

?> 