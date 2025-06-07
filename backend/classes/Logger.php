<?php

namespace App;

// 引入真正的 Database 類別
use Database; // 確保這是指向您實際的 Database 類別

class Logger {
    private $logFile;
    private $minLevel;
    private $database;
    
    const LOG_LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];
    
    public function __construct($logFile = 'app.log', $minLevel = 'INFO') {
        $this->logFile = __DIR__ . '/../../logs/' . $logFile;
        $this->minLevel = self::LOG_LEVELS[$minLevel];
        
        // 改為使用真正的 Database 類別
        $this->database = Database::getInstance();
        
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function critical($message, $context = []) {
        $this->log('CRITICAL', $message, $context);
    }
    
    private function log($level, $message, $context) {
        if (self::LOG_LEVELS[$level] < $this->minLevel) {
            return;
        }
        
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[2] ?? [];
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'memory' => memory_get_usage(true),
            'file' => $caller['file'] ?? 'unknown',
            'line' => $caller['line'] ?? 0
        ];
        
        // 寫入文件
        $this->writeToFile($logEntry);
        
        // 寫入資料庫
        $this->writeToDatabase($logEntry);
    }
    
    private function writeToFile($logEntry) {
        $logLine = sprintf(
            "[%s] %s: %s %s\n",
            $logEntry['timestamp'],
            $logEntry['level'],
            $logEntry['message'],
            !empty($logEntry['context']) ? json_encode($logEntry['context'], JSON_UNESCAPED_UNICODE) : ''
        );
        
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    private function writeToDatabase($logEntry) {
        try {
            // 確保 system_logs 表存在，並插入日誌數據
            $this->database->insert('system_logs', [
                'level' => $logEntry['level'],
                'message_content' => $logEntry['message'], // 使用 message_content 以匹配資料庫欄位
                'context' => json_encode($logEntry['context'], JSON_UNESCAPED_UNICODE),
                'file_path' => $logEntry['file'], // 使用 file_path 以匹配資料庫欄位
                'line_number' => $logEntry['line'], // 使用 line_number 以匹配資料庫欄位
                'memory_usage' => $logEntry['memory']
            ]);
        } catch (\Exception $e) {
            // 如果資料庫寫入失敗，只寫入文件
            error_log("Logger database write failed: " . $e->getMessage());
        }
    }
    
    public function getRecentLogs($limit = 100, $level = null) {
        $sql = "SELECT * FROM system_logs";
        $params = [];
        
        if ($level) {
            $sql .= " WHERE level = :level";
            $params['level'] = $level;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit";
        $params['limit'] = $limit;
        
        return $this->database->fetchAll($sql, $params);
    }
    
    public function clearOldLogs($days = 30) {
        $sql = "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        return $this->database->query($sql, ['days' => $days]);
    }
} 