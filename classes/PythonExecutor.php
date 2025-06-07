<?php
/**
 * Python代碼執行器 - 安全沙箱環境
 * 
 * 功能特點：
 * - 安全沙箱執行環境
 * - 執行時間限制
 * - 內存使用限制
 * - 禁止危險操作
 * - 支持基本Python庫
 * - 錯誤捕獲和格式化
 */

class PythonExecutor {
    private $pythonPath;
    private $tempDir;
    private $maxExecutionTime;
    private $maxMemoryMB;
    private $allowedModules;
    private $blockedFunctions;
    
    // 執行統計屬性
    private $lastExecutionTime = 0;
    private $lastMemoryUsage = '0MB';
    
    public function __construct($config = []) {
        // 配置Python路徑
        $this->pythonPath = $config['python_path'] ?? $this->detectPythonPath();
        
        // 配置臨時目錄
        $this->tempDir = $config['temp_dir'] ?? sys_get_temp_dir() . '/pythonlearn_sandbox';
        $this->ensureTempDir();
        
        // 安全限制配置 - 調整內存限制
        $this->maxExecutionTime = $config['max_execution_time'] ?? 10; // 10秒
        $this->maxMemoryMB = $config['max_memory_mb'] ?? 128; // 增加到128MB
        
        // 允許的Python模組
        $this->allowedModules = $config['allowed_modules'] ?? [
            'math', 'random', 'datetime', 'json', 'string', 're',
            'collections', 'itertools', 'functools', 'operator',
            'statistics', 'decimal', 'fractions', 'turtle'
        ];
        
        // 禁止的危險函數
        $this->blockedFunctions = $config['blocked_functions'] ?? [
            'open', 'file', 'input', 'raw_input', 'execfile',
            'compile', 'reload', '__import__', 'eval', 'exec',
            'exit', 'quit', 'help', 'license', 'credits',
            'os.system', 'os.popen', 'os.spawn', 'subprocess',
            'socket', 'urllib', 'httplib', 'ftplib'
        ];
    }
    
    /**
     * 執行Python代碼
     */
    public function execute($code, $input = '') {
        try {
            // 1. 安全檢查
            $securityCheck = $this->performSecurityCheck($code);
            if (!$securityCheck['safe']) {
                return [
                    'success' => false,
                    'error' => '安全檢查失敗: ' . $securityCheck['reason'],
                    'error_type' => 'security_violation',
                    'output' => '',
                    'execution_time' => 0
                ];
            }
            
            // 2. 創建安全的執行環境
            $sandboxCode = $this->createSandboxCode($code);
            
            // 3. 寫入臨時文件
            $tempFile = $this->createTempFile($sandboxCode);
            $inputFile = $this->createInputFile($input);
            
            // 4. 執行代碼
            $result = $this->executeInSandbox($tempFile, $inputFile);
            
            // 更新執行統計
            $this->lastExecutionTime = $result['execution_time'];
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => '執行器錯誤: ' . $e->getMessage(),
                'error_type' => 'executor_error',
                'output' => '',
                'execution_time' => 0
            ];
        } finally {
            // 清理臨時文件
            $this->cleanup($tempFile, $inputFile);
        }
    }
    
    /**
     * 安全檢查
     */
    private function performSecurityCheck($code) {
        // 檢查禁止的函數
        foreach ($this->blockedFunctions as $func) {
            if (strpos($code, $func) !== false) {
                return [
                    'safe' => false,
                    'reason' => "禁止使用函數: {$func}"
                ];
            }
        }
        
        // 檢查危險的import語句
        $dangerousImports = [
            'os', 'sys', 'subprocess', 'socket', 'urllib',
            'httplib', 'ftplib', 'smtplib', 'telnetlib',
            'pickle', 'marshal', 'shelve', 'dbm'
        ];
        
        foreach ($dangerousImports as $module) {
            if (preg_match('/import\s+' . preg_quote($module) . '/', $code) ||
                preg_match('/from\s+' . preg_quote($module) . '\s+import/', $code)) {
                return [
                    'safe' => false,
                    'reason' => "禁止導入模組: {$module}"
                ];
            }
        }
        
        return ['safe' => true];
    }
    
    /**
     * 創建沙箱代碼
     */
    private function createSandboxCode($userCode) {
        $sandboxTemplate = '#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Python學習平台 - 安全沙箱環境
執行時間限制: {max_time}秒
內存限制: {max_memory}MB
"""

import sys
import signal
import traceback
from io import StringIO
import gc

# 設置資源限制
def set_limits():
    # 設置CPU時間限制 - Windows環境下跳過
    try:
        signal.alarm({max_time})
    except AttributeError:
        # Windows環境下SIGALRM不可用，跳過時間限制
        pass
    
    # 在Windows環境下，resource模組可能不可用，使用軟限制
    try:
        import resource
        # 設置內存限制 (字節) - 使用軟限制避免過於嚴格
        max_memory_bytes = {max_memory} * 1024 * 1024
        resource.setrlimit(resource.RLIMIT_AS, (max_memory_bytes, max_memory_bytes * 2))
    except (ImportError, OSError):
        # Windows環境下resource模組不可用，跳過硬限制
        pass

# 超時處理
def timeout_handler(signum, frame):
    raise TimeoutError("代碼執行超時 ({max_time}秒)")

# 設置信號處理
try:
    signal.signal(signal.SIGALRM, timeout_handler)
except AttributeError:
    # Windows環境下SIGALRM不可用
    pass

# 重定向輸出
old_stdout = sys.stdout
old_stderr = sys.stderr
stdout_capture = StringIO()
stderr_capture = StringIO()

try:
    # 設置限制
    set_limits()
    
    # 重定向標準輸出和錯誤輸出
    sys.stdout = stdout_capture
    sys.stderr = stderr_capture
    
    # 執行用戶代碼
{user_code}
    
except TimeoutError as e:
    sys.stderr = old_stderr
    print(f"⏰ 執行超時: {e}", file=sys.stderr)
except MemoryError:
    sys.stderr = old_stderr
    print("💾 內存使用超限", file=sys.stderr)
    print("💡 提示: 嘗試減少變數使用或優化算法", file=sys.stderr)
except RecursionError:
    sys.stderr = old_stderr
    print("🔄 遞歸深度超限", file=sys.stderr)
    print("💡 提示: 檢查是否有無限遞歸", file=sys.stderr)
except KeyboardInterrupt:
    sys.stderr = old_stderr
    print("⏹️ 程序被中斷", file=sys.stderr)
except Exception as e:
    sys.stderr = old_stderr
    error_type = type(e).__name__
    print(f"❌ {error_type}: {e}", file=sys.stderr)
    
    # 提供更友好的錯誤提示
    if "SyntaxError" in error_type:
        print("💡 提示: 檢查代碼語法，如括號是否匹配、縮進是否正確", file=sys.stderr)
    elif "NameError" in error_type:
        print("💡 提示: 檢查變量名是否正確，是否已定義", file=sys.stderr)
    elif "TypeError" in error_type:
        print("💡 提示: 檢查數據類型是否匹配", file=sys.stderr)
    elif "ValueError" in error_type:
        print("💡 提示: 檢查傳入的值是否有效", file=sys.stderr)
    elif "IndexError" in error_type:
        print("💡 提示: 檢查列表或字符串索引是否超出範圍", file=sys.stderr)
    elif "ZeroDivisionError" in error_type:
        print("💡 提示: 不能除以零", file=sys.stderr)
    
    # 只在調試模式下顯示詳細錯誤
    # traceback.print_exc(file=sys.stderr)
finally:
    # 恢復標準輸出
    sys.stdout = old_stdout
    sys.stderr = old_stderr
    
    # 輸出結果
    output = stdout_capture.getvalue()
    error = stderr_capture.getvalue()
    
    if output:
        print("=== 程序輸出 ===")
        print(output.rstrip())
    
    if error:
        print("=== 錯誤信息 ===")
        print(error.rstrip())
    
    # 清理內存
    gc.collect()
    
    # 取消定時器
    try:
        signal.alarm(0)
    except AttributeError:
        pass
';
        
        // 縮進用戶代碼
        $indentedUserCode = $this->indentCode($userCode, '    ');
        
        return str_replace([
            '{max_time}',
            '{max_memory}',
            '{user_code}'
        ], [
            $this->maxExecutionTime,
            $this->maxMemoryMB,
            $indentedUserCode
        ], $sandboxTemplate);
    }
    
    /**
     * 縮進代碼
     */
    private function indentCode($code, $indent = '    ') {
        $lines = explode("\n", $code);
        $indentedLines = array_map(function($line) use ($indent) {
            return empty(trim($line)) ? $line : $indent . $line;
        }, $lines);
        return implode("\n", $indentedLines);
    }
    
    /**
     * 在沙箱中執行
     */
    private function executeInSandbox($tempFile, $inputFile) {
        $startTime = microtime(true);
        
        // 構建執行命令
        $cmd = escapeshellcmd($this->pythonPath) . ' ' . escapeshellarg($tempFile);
        
        // 如果有輸入，重定向輸入
        if ($inputFile && file_exists($inputFile)) {
            $cmd .= ' < ' . escapeshellarg($inputFile);
        }
        
        // 設置環境變量 - 改進Windows支持
        $env = null; // 使用系統默認環境變量
        if (PHP_OS_FAMILY !== 'Windows') {
            $env = [
                'PYTHONPATH' => '',
                'PYTHONHOME' => '',
                'PATH' => dirname($this->pythonPath),
                'LANG' => 'en_US.UTF-8'
            ];
        }
        
        // 執行命令
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];
        
        $process = proc_open($cmd, $descriptorspec, $pipes, null, $env);
        
        if (!is_resource($process)) {
            throw new Exception('無法啟動Python進程');
        }
        
        // 關閉stdin
        fclose($pipes[0]);
        
        // 設置非阻塞模式
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        // 讀取輸出
        $output = '';
        $error = '';
        $timeout = $this->maxExecutionTime + 2; // 額外2秒緩衝
        $endTime = time() + $timeout;
        
        while (time() < $endTime) {
            $status = proc_get_status($process);
            
            // 讀取stdout
            $stdout = fread($pipes[1], 8192);
            if ($stdout !== false) {
                $output .= $stdout;
            }
            
            // 讀取stderr
            $stderr = fread($pipes[2], 8192);
            if ($stderr !== false) {
                $error .= $stderr;
            }
            
            // 檢查進程是否結束
            if (!$status['running']) {
                break;
            }
            
            usleep(100000); // 0.1秒
        }
        
        // 關閉管道
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        // 終止進程
        $exitCode = proc_close($process);
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // 解析輸出
        return $this->parseOutput($output, $error, $exitCode, $executionTime);
    }
    
    /**
     * 解析輸出
     */
    private function parseOutput($output, $error, $exitCode, $executionTime) {
        $success = ($exitCode === 0);
        
        // 分離程序輸出和錯誤信息
        $programOutput = '';
        $errorMessage = '';
        
        if (strpos($output, '=== 程序輸出 ===') !== false) {
            $parts = explode('=== 程序輸出 ===', $output, 2);
            if (isset($parts[1])) {
                $outputPart = $parts[1];
                if (strpos($outputPart, '=== 錯誤信息 ===') !== false) {
                    $outputParts = explode('=== 錯誤信息 ===', $outputPart, 2);
                    $programOutput = trim($outputParts[0]);
                    $errorMessage = trim($outputParts[1]);
                } else {
                    $programOutput = trim($outputPart);
                }
            }
        } elseif (strpos($output, '=== 錯誤信息 ===') !== false) {
            $parts = explode('=== 錯誤信息 ===', $output, 2);
            $errorMessage = trim($parts[1]);
        } else {
            // 如果沒有格式標記，直接使用輸出
            $programOutput = trim($output);
        }
        
        // 如果stderr有內容，添加到錯誤信息
        if (!empty($error)) {
            $errorMessage = empty($errorMessage) ? trim($error) : $errorMessage . "\n" . trim($error);
        }
        
        // 如果有錯誤信息，則認為執行失敗
        if (!empty($errorMessage)) {
            $success = false;
        }
        
        return [
            'success' => $success,
            'output' => $programOutput,
            'error' => $errorMessage,
            'error_type' => $this->determineErrorType($errorMessage),
            'execution_time' => $executionTime,
            'memory_usage' => $this->estimateMemoryUsage($programOutput, $errorMessage),
            'exit_code' => $exitCode
        ];
    }
    
    /**
     * 確定錯誤類型
     */
    private function determineErrorType($errorMessage) {
        if (empty($errorMessage)) {
            return null;
        }
        
        if (strpos($errorMessage, 'SyntaxError') !== false) {
            return 'syntax_error';
        } elseif (strpos($errorMessage, 'NameError') !== false) {
            return 'name_error';
        } elseif (strpos($errorMessage, 'TypeError') !== false) {
            return 'type_error';
        } elseif (strpos($errorMessage, 'ValueError') !== false) {
            return 'value_error';
        } elseif (strpos($errorMessage, 'IndexError') !== false) {
            return 'index_error';
        } elseif (strpos($errorMessage, 'KeyError') !== false) {
            return 'key_error';
        } elseif (strpos($errorMessage, 'AttributeError') !== false) {
            return 'attribute_error';
        } elseif (strpos($errorMessage, '執行超時') !== false) {
            return 'timeout_error';
        } elseif (strpos($errorMessage, '內存使用超限') !== false) {
            return 'memory_error';
        } else {
            return 'runtime_error';
        }
    }
    
    /**
     * 估算內存使用量
     */
    private function estimateMemoryUsage($output, $error) {
        // 基於輸出長度和錯誤信息估算內存使用
        $outputLength = strlen($output) + strlen($error);
        
        if (strpos($error, '內存使用超限') !== false) {
            $memoryUsage = $this->maxMemoryMB . 'MB (超限)';
        } elseif ($outputLength > 10000) {
            $memoryUsage = '高 (>10MB)';
        } elseif ($outputLength > 1000) {
            $memoryUsage = '中 (1-10MB)';
        } else {
            $memoryUsage = '低 (<1MB)';
        }
        
        $this->lastMemoryUsage = $memoryUsage;
        return $memoryUsage;
    }
    
    /**
     * 檢測Python路徑
     */
    private function detectPythonPath() {
        $possiblePaths = [
            'python3',
            'python',
            '/usr/bin/python3',
            '/usr/bin/python',
            '/usr/local/bin/python3',
            '/usr/local/bin/python',
            'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python313\\python.exe',
            'C:\\Python313\\python.exe',
            'C:\\Python39\\python.exe',
            'C:\\Python38\\python.exe',
            'C:\\Python37\\python.exe',
            'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python39\\python.exe'
        ];
        
        foreach ($possiblePaths as $path) {
            if ($this->testPythonPath($path)) {
                return $path;
            }
        }
        
        throw new Exception('找不到Python解釋器，請確保已安裝Python 3.x');
    }
    
    /**
     * 測試Python路徑
     */
    private function testPythonPath($path) {
        $output = [];
        $returnVar = 0;
        exec(escapeshellcmd($path) . ' --version 2>&1', $output, $returnVar);
        return ($returnVar === 0 && !empty($output) && strpos(implode(' ', $output), 'Python') !== false);
    }
    
    /**
     * 確保臨時目錄存在
     */
    private function ensureTempDir() {
        if (!is_dir($this->tempDir)) {
            if (!mkdir($this->tempDir, 0755, true)) {
                throw new Exception('無法創建臨時目錄: ' . $this->tempDir);
            }
        }
    }
    
    /**
     * 創建臨時文件
     */
    private function createTempFile($code) {
        $tempFile = tempnam($this->tempDir, 'py_exec_') . '.py';
        if (file_put_contents($tempFile, $code) === false) {
            throw new Exception('無法創建臨時Python文件');
        }
        return $tempFile;
    }
    
    /**
     * 創建輸入文件
     */
    private function createInputFile($input) {
        if (empty($input)) {
            return null;
        }
        
        $inputFile = tempnam($this->tempDir, 'py_input_') . '.txt';
        if (file_put_contents($inputFile, $input) === false) {
            throw new Exception('無法創建輸入文件');
        }
        return $inputFile;
    }
    
    /**
     * 清理臨時文件
     */
    private function cleanup($tempFile, $inputFile = null) {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        if ($inputFile && file_exists($inputFile)) {
            unlink($inputFile);
        }
    }
    
    /**
     * 獲取支持的模組列表
     */
    public function getSupportedModules() {
        return $this->allowedModules;
    }
    
    /**
     * 獲取配置信息
     */
    public function getConfig() {
        return [
            'python_path' => $this->pythonPath,
            'temp_dir' => $this->tempDir,
            'max_execution_time' => $this->maxExecutionTime,
            'max_memory_mb' => $this->maxMemoryMB,
            'allowed_modules' => $this->allowedModules,
            'blocked_functions' => $this->blockedFunctions
        ];
    }
    
    /**
     * 獲取最後一次執行的執行時間
     */
    public function getExecutionTime() {
        return $this->lastExecutionTime;
    }
    
    /**
     * 獲取最後一次執行的內存使用量
     */
    public function getMemoryUsage() {
        return $this->lastMemoryUsage;
    }
} 