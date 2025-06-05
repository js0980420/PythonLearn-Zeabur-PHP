<?php

namespace App;

class ConflictDetector {
    private $logger;
    
    public function __construct($logger = null) {
        $this->logger = $logger;
    }
    
    /**
     * 檢測代碼衝突
     * @param string $originalCode 原始代碼
     * @param string $code1 用戶1的代碼
     * @param string $code2 用戶2的代碼
     * @param string $user1Id 用戶1 ID
     * @param string $user2Id 用戶2 ID
     * @return array|null 如果有衝突返回衝突信息，否則返回null
     */
    public function detectConflict($originalCode, $code1, $code2, $user1Id, $user2Id) {
        // 將代碼分行
        $originalLines = explode("\n", $originalCode);
        $lines1 = explode("\n", $code1);
        $lines2 = explode("\n", $code2);
        
        // 檢測行級衝突
        $conflicts = $this->detectLineConflicts($originalLines, $lines1, $lines2, $user1Id, $user2Id);
        
        if (!empty($conflicts)) {
            return [
                'type' => 'line_conflict',
                'conflicts' => $conflicts,
                'users' => [$user1Id, $user2Id],
                'timestamp' => date('c'),
                'conflict_id' => $this->generateConflictId()
            ];
        }
        
        return null;
    }
    
    /**
     * 檢測行級衝突
     */
    private function detectLineConflicts($originalLines, $lines1, $lines2, $user1Id, $user2Id) {
        $conflicts = [];
        $maxLines = max(count($lines1), count($lines2), count($originalLines));
        
        for ($i = 0; $i < $maxLines; $i++) {
            $originalLine = $originalLines[$i] ?? '';
            $line1 = $lines1[$i] ?? '';
            $line2 = $lines2[$i] ?? '';
            
            // 如果兩個用戶都修改了同一行，且修改內容不同
            if ($line1 !== $originalLine && $line2 !== $originalLine && $line1 !== $line2) {
                $conflicts[] = [
                    'line_number' => $i + 1,
                    'original' => $originalLine,
                    'versions' => [
                        $user1Id => $line1,
                        $user2Id => $line2
                    ],
                    'type' => 'concurrent_edit'
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * 檢測語法衝突
     */
    public function detectSyntaxConflict($code) {
        $conflicts = [];
        
        // 檢查括號匹配
        if (!$this->checkBracketBalance($code)) {
            $conflicts[] = [
                'type' => 'syntax_error',
                'message' => '括號不匹配',
                'severity' => 'high'
            ];
        }
        
        // 檢查縮進問題
        $indentConflicts = $this->checkIndentation($code);
        if (!empty($indentConflicts)) {
            $conflicts = array_merge($conflicts, $indentConflicts);
        }
        
        return $conflicts;
    }
    
    /**
     * 檢查括號平衡
     */
    private function checkBracketBalance($code) {
        $brackets = ['(' => ')', '[' => ']', '{' => '}'];
        $stack = [];
        
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            
            if (isset($brackets[$char])) {
                $stack[] = $char;
            } elseif (in_array($char, $brackets)) {
                if (empty($stack)) {
                    return false;
                }
                $last = array_pop($stack);
                if ($brackets[$last] !== $char) {
                    return false;
                }
            }
        }
        
        return empty($stack);
    }
    
    /**
     * 檢查Python縮進
     */
    private function checkIndentation($code) {
        $lines = explode("\n", $code);
        $conflicts = [];
        $indentStack = [0]; // 縮進級別堆疊
        
        foreach ($lines as $lineNum => $line) {
            if (trim($line) === '' || substr(trim($line), 0, 1) === '#') {
                continue; // 跳過空行和註釋
            }
            
            $indent = strlen($line) - strlen(ltrim($line));
            
            // 檢查縮進是否為4的倍數
            if ($indent % 4 !== 0) {
                $conflicts[] = [
                    'type' => 'indentation_error',
                    'line_number' => $lineNum + 1,
                    'message' => '縮進應該是4個空格的倍數',
                    'severity' => 'medium'
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * 解決衝突
     */
    public function resolveConflict($conflictId, $resolution, $resolvedCode = null) {
        $resolutionData = [
            'conflict_id' => $conflictId,
            'resolution_type' => $resolution,
            'resolved_at' => date('c'),
            'resolved_code' => $resolvedCode
        ];
        
        switch ($resolution) {
            case 'accept':
                $resolutionData['message'] = '接受其他用戶的修改';
                break;
            case 'reject':
                $resolutionData['message'] = '拒絕其他用戶的修改';
                break;
            case 'merge':
                $resolutionData['message'] = '合併兩個版本';
                break;
            case 'ai_resolve':
                $resolutionData['message'] = 'AI自動解決衝突';
                break;
            default:
                $resolutionData['message'] = '自定義解決方案';
        }
        
        if ($this->logger) {
            $this->logger->info('衝突解決', $resolutionData);
        }
        
        return $resolutionData;
    }
    
    /**
     * 生成衝突ID
     */
    private function generateConflictId() {
        return 'conflict_' . time() . '_' . mt_rand(1000, 9999);
    }
    
    /**
     * 自動合併代碼
     */
    public function autoMerge($originalCode, $code1, $code2, $user1Id, $user2Id) {
        $originalLines = explode("\n", $originalCode);
        $lines1 = explode("\n", $code1);
        $lines2 = explode("\n", $code2);
        
        $mergedLines = [];
        $maxLines = max(count($lines1), count($lines2), count($originalLines));
        
        for ($i = 0; $i < $maxLines; $i++) {
            $originalLine = $originalLines[$i] ?? '';
            $line1 = $lines1[$i] ?? '';
            $line2 = $lines2[$i] ?? '';
            
            if ($line1 === $line2) {
                // 兩個版本相同，使用任一版本
                $mergedLines[] = $line1;
            } elseif ($line1 === $originalLine) {
                // 用戶1沒有修改，使用用戶2的版本
                $mergedLines[] = $line2;
            } elseif ($line2 === $originalLine) {
                // 用戶2沒有修改，使用用戶1的版本
                $mergedLines[] = $line1;
            } else {
                // 兩個用戶都修改了，創建衝突標記
                $mergedLines[] = "<<<<<<< {$user1Id}";
                $mergedLines[] = $line1;
                $mergedLines[] = "=======";
                $mergedLines[] = $line2;
                $mergedLines[] = ">>>>>>> {$user2Id}";
            }
        }
        
        return implode("\n", $mergedLines);
    }
    
    /**
     * 檢查代碼是否包含衝突標記
     */
    public function hasConflictMarkers($code) {
        $markers = ['<<<<<<<', '=======', '>>>>>>>'];
        foreach ($markers as $marker) {
            if (strpos($code, $marker) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 獲取衝突統計
     */
    public function getConflictStats($roomId, $database) {
        // 這裡可以從資料庫獲取衝突統計信息
        return [
            'total_conflicts' => 0,
            'resolved_conflicts' => 0,
            'pending_conflicts' => 0,
            'conflict_rate' => 0.0
        ];
    }
} 