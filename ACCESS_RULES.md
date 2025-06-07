# 🔐 PythonLearn-Zeabur-PHP 存取規則

## 📋 檔案存取權限管理

### 🔒 核心檔案 (唯讀保護)
這些檔案對系統運行至關重要，不應隨意修改：

```
📁 根目錄
├── zeabur.yaml          # Zeabur 部署配置
├── composer.json        # PHP 依賴管理
├── .gitignore          # Git 版本控制忽略規則
├── router.php          # 主路由配置
└── .cursorrules        # Cursor 開發規則
```

### ✏️ 可編輯檔案
這些檔案可以根據需求進行修改：

```
📁 前端檔案
├── public/js/
│   ├── editor.js       # 代碼編輯器邏輯
│   ├── websocket.js    # WebSocket 連接管理
│   ├── save-load.js    # 保存載入功能
│   ├── ai-assistant.js # AI 助教功能
│   ├── ui.js          # 用戶界面管理
│   ├── chat.js        # 聊天功能
│   └── conflict.js    # 衝突檢測
├── css/
│   └── styles.css     # 樣式表
└── *.html             # HTML 頁面

📁 後端檔案
├── backend/api/
│   ├── auth.php       # 用戶認證
│   ├── code.php       # 代碼操作
│   ├── rooms.php      # 房間管理
│   ├── history.php    # 歷史記錄
│   ├── ai.php         # AI 助教 API
│   ├── teacher.php    # 教師功能
│   └── health.php     # 健康檢查
├── backend/classes/
│   ├── Database.php   # 資料庫類
│   ├── Room.php       # 房間類
│   ├── CodeManager.php # 代碼管理類
│   ├── ConflictDetector.php # 衝突檢測類
│   ├── AIAssistant.php # AI 助教類
│   └── Logger.php     # 日誌類
└── websocket/
    └── server.php     # WebSocket 服務器
```

### 🗑️ 臨時檔案 (可刪除)
這些檔案可以安全刪除，系統會自動重新生成：

```
📁 臨時數據
├── data/
│   ├── rooms/         # 房間數據檔案
│   ├── logs/          # 日誌檔案
│   └── cache/         # 快取檔案
├── *.log              # 各種日誌檔案
├── *.tmp              # 臨時檔案
└── *.cache            # 快取檔案
```

## 🛡️ 資料庫存取規則

### 本地存儲模式
```php
class Database {
    private $localStorage = [];
    private $dataFile = 'data/local_storage.json';
    
    public function insert($table, $data) {
        // 自動添加必要欄位
        $id = $this->generateId();
        $data['id'] = $id;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // 特殊處理 code_history 表
        if ($table === 'code_history') {
            $data['version_number'] = $this->getNextVersion($data['room_id']);
            $data['username'] = $data['username'] ?? '未知用戶';
            $data['description'] = $data['description'] ?? '程式碼保存';
        }
        
        $this->localStorage[$table][] = $data;
        $this->saveToFile();
        
        return $id;
    }
    
    private function getNextVersion($roomId) {
        $maxVersion = 0;
        foreach ($this->localStorage['code_history'] ?? [] as $record) {
            if ($record['room_id'] === $roomId) {
                $version = $record['version_number'] ?? 1;
                $maxVersion = max($maxVersion, $version);
            }
        }
        return $maxVersion + 1;
    }
}
```

### 雲端資料庫模式
```php
class Database {
    private $pdo;
    
    public function connect() {
        if ($this->isZeaburEnvironment()) {
            $this->pdo = new PDO(
                $_ENV['DATABASE_URL'],
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        }
    }
    
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return $this->pdo->lastInsertId();
    }
}
```

## 🌐 API 存取控制

### 速率限制規則
```php
class RateLimiter {
    private $limits = [
        'ai_request' => ['count' => 10, 'window' => 60],    // AI 請求：每分鐘 10 次
        'code_save' => ['count' => 30, 'window' => 60],     // 代碼保存：每分鐘 30 次
        'room_create' => ['count' => 5, 'window' => 300],   // 房間創建：每 5 分鐘 5 次
        'room_join' => ['count' => 20, 'window' => 60],     // 房間加入：每分鐘 20 次
        'chat_message' => ['count' => 50, 'window' => 60],  // 聊天消息：每分鐘 50 次
        'general' => ['count' => 100, 'window' => 60]       // 一般請求：每分鐘 100 次
    ];
    
    public function checkLimit($userId, $action) {
        $key = "rate_limit:{$userId}:{$action}";
        $current = $this->getCount($key);
        $limit = $this->limits[$action] ?? $this->limits['general'];
        
        if ($current >= $limit['count']) {
            throw new RateLimitException('請求過於頻繁，請稍後再試');
        }
        
        $this->incrementCount($key, $limit['window']);
        return true;
    }
}
```

### 輸入驗證規則
```php
class InputValidator {
    public function validateRoomId($roomId) {
        if (!preg_match('/^room_[a-zA-Z0-9_-]{1,50}$/', $roomId)) {
            throw new InvalidArgumentException('房間ID格式無效');
        }
        return true;
    }
    
    public function validateUserId($userId) {
        if (!preg_match('/^[a-zA-Z0-9_\u4e00-\u9fa5]{2,20}$/u', $userId)) {
            throw new InvalidArgumentException('用戶ID格式無效');
        }
        return true;
    }
    
    public function validateCode($code) {
        if (strlen($code) > 50000) {
            throw new InvalidArgumentException('代碼長度超過限制 (50KB)');
        }
        
        if (!mb_check_encoding($code, 'UTF-8')) {
            throw new InvalidArgumentException('代碼編碼格式錯誤');
        }
        
        return true;
    }
    
    public function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
```

## 🔌 WebSocket 存取規則

### 連接驗證
```javascript
class WebSocketManager {
    constructor() {
        this.maxConnections = 100;
        this.connectionTimeout = 30000; // 30 秒
        this.heartbeatInterval = 25000; // 25 秒
    }
    
    async connect(url, userId, roomId) {
        // 驗證參數
        if (!this.validateUserId(userId)) {
            throw new Error('用戶ID格式無效');
        }
        
        if (!this.validateRoomId(roomId)) {
            throw new Error('房間ID格式無效');
        }
        
        // 檢查連接數限制
        if (this.getConnectionCount() >= this.maxConnections) {
            throw new Error('服務器連接數已滿，請稍後再試');
        }
        
        return this.establishConnection(url, userId, roomId);
    }
    
    validateUserId(userId) {
        return /^[a-zA-Z0-9_\u4e00-\u9fa5]{2,20}$/u.test(userId);
    }
    
    validateRoomId(roomId) {
        return /^room_[a-zA-Z0-9_-]{1,50}$/.test(roomId);
    }
}
```

### 消息過濾和驗證
```php
class MessageHandler {
    private $allowedMessageTypes = [
        'join_room',
        'leave_room',
        'code_change',
        'cursor_position',
        'chat_message',
        'save_code',
        'load_specific_code',
        'get_history',
        'ai_request'
    ];
    
    public function handleMessage($connection, $message) {
        try {
            $data = json_decode($message, true);
            
            // 基本格式驗證
            if (!$this->validateMessageFormat($data)) {
                $this->sendError($connection, '消息格式無效');
                return;
            }
            
            // 消息類型驗證
            if (!in_array($data['type'], $this->allowedMessageTypes)) {
                $this->sendError($connection, '不支援的消息類型');
                return;
            }
            
            // 權限檢查
            if (!$this->checkPermission($connection, $data)) {
                $this->sendError($connection, '權限不足');
                return;
            }
            
            // 速率限制檢查
            if (!$this->checkRateLimit($connection, $data['type'])) {
                $this->sendError($connection, '請求過於頻繁');
                return;
            }
            
            $this->processMessage($connection, $data);
            
        } catch (Exception $e) {
            $this->logger->error('消息處理錯誤', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->resourceId
            ]);
            $this->sendError($connection, '消息處理失敗');
        }
    }
    
    private function validateMessageFormat($data) {
        return is_array($data) && 
               isset($data['type']) && 
               is_string($data['type']) &&
               strlen($data['type']) <= 50;
    }
    
    private function checkPermission($connection, $data) {
        // 檢查用戶是否已加入房間
        if (!isset($connection->roomId) && $data['type'] !== 'join_room') {
            return false;
        }
        
        // 檢查房間權限
        if (isset($data['room_id']) && $data['room_id'] !== $connection->roomId) {
            return false;
        }
        
        return true;
    }
}
```

## 🛠️ 開發環境存取規則

### 本地開發模式
```php
class DevelopmentConfig {
    public static function isLocalDevelopment() {
        return !isset($_ENV['ZEABUR']) && 
               (isset($_ENV['DEVELOPMENT']) || 
                $_SERVER['SERVER_NAME'] === 'localhost');
    }
    
    public static function getConfig() {
        if (self::isLocalDevelopment()) {
            return [
                'debug' => true,
                'database_mode' => 'localStorage',
                'ai_service' => 'mock',
                'websocket_host' => 'localhost',
                'websocket_port' => 8081,
                'log_level' => 'debug'
            ];
        }
        
        return [
            'debug' => false,
            'database_mode' => 'mysql',
            'ai_service' => 'openai',
            'websocket_host' => $_ENV['WEBSOCKET_HOST'],
            'websocket_port' => $_ENV['WEBSOCKET_PORT'],
            'log_level' => 'info'
        ];
    }
}
```

### 生產環境限制
```php
class ProductionSecurity {
    public static function enforceSecurityHeaders() {
        if (!DevelopmentConfig::isLocalDevelopment()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Strict-Transport-Security: max-age=31536000');
            header('Content-Security-Policy: default-src \'self\'');
        }
    }
    
    public static function validateEnvironment() {
        $required = ['DATABASE_URL', 'OPENAI_API_KEY', 'WEBSOCKET_HOST'];
        
        foreach ($required as $var) {
            if (!isset($_ENV[$var])) {
                throw new Exception("缺少必要的環境變數: {$var}");
            }
        }
    }
}
```

## 📊 監控和日誌存取

### 日誌記錄規則
```php
class Logger {
    private $logLevels = ['debug', 'info', 'warning', 'error', 'critical'];
    private $maxLogSize = 10 * 1024 * 1024; // 10MB
    private $maxLogFiles = 5;
    
    public function log($level, $message, $context = []) {
        if (!in_array($level, $this->logLevels)) {
            throw new InvalidArgumentException('無效的日誌級別');
        }
        
        $logEntry = [
            'timestamp' => date('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'memory_usage' => memory_get_usage(true),
            'request_id' => $this->getRequestId()
        ];
        
        $this->writeLog($logEntry);
        $this->rotateLogsIfNeeded();
    }
    
    private function writeLog($entry) {
        $logFile = "data/logs/" . date('Y-m-d') . ".log";
        $logLine = json_encode($entry) . "\n";
        
        if (!is_dir('data/logs')) {
            mkdir('data/logs', 0755, true);
        }
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
```

### 性能監控
```javascript
class PerformanceMonitor {
    constructor() {
        this.metrics = new Map();
        this.maxMetrics = 1000;
    }
    
    measureFunction(func, name) {
        return (...args) => {
            const start = performance.now();
            const result = func.apply(this, args);
            const duration = performance.now() - start;
            
            this.recordMetric(name, duration);
            
            if (duration > 1000) { // 超過 1 秒的操作
                console.warn(`⚠️ 慢操作檢測: ${name} 耗時 ${duration.toFixed(2)}ms`);
            }
            
            return result;
        };
    }
    
    recordMetric(name, value) {
        if (!this.metrics.has(name)) {
            this.metrics.set(name, []);
        }
        
        const values = this.metrics.get(name);
        values.push({
            value: value,
            timestamp: Date.now()
        });
        
        // 限制記錄數量
        if (values.length > 100) {
            values.shift();
        }
    }
    
    getMetrics() {
        const summary = {};
        
        for (const [name, values] of this.metrics) {
            const recent = values.slice(-10);
            const avg = recent.reduce((sum, item) => sum + item.value, 0) / recent.length;
            
            summary[name] = {
                average: avg.toFixed(2),
                count: values.length,
                lastValue: values[values.length - 1]?.value.toFixed(2)
            };
        }
        
        return summary;
    }
}
```

---

**📝 文檔版本**: v1.0  
**📅 最後更新**: 2025-06-06  
**🔧 維護狀態**: 活躍維護

**🎯 核心原則**: 
- 🔒 核心檔案保護，防止意外修改
- ✅ 合理的存取權限，平衡安全與便利
- 📊 完整的監控日誌，便於問題追蹤
- 🛡️ 多層安全驗證，確保系統穩定
- �� 環境適配，支援本地開發和生產部署 