<?php

/**
 * 房間管理類
 * 處理房間數據的CRUD操作和管理功能
 */
class Room {
    private $db;
    private $logger;
    
    public function __construct() {
        // 使用 MockDatabase 而不是 Database
        try {
            require_once __DIR__ . '/MockDatabase.php';
            $this->db = App\MockDatabase::getInstance();
        } catch (Exception $e) {
            // 如果MockDatabase類不可用，使用null
            $this->db = null;
        }
        
        try {
            require_once __DIR__ . '/Logger.php';
            $this->logger = new App\Logger('room.log');
        } catch (Exception $e) {
            // 如果Logger類不可用，使用null
            $this->logger = null;
        }
    }
    
    /**
     * 獲取所有房間及其用戶信息
     * @return array 包含用戶信息的房間列表
     */
    public function getAllRoomsWithUsers() {
        try {
            // 從文件系統載入房間數據
            $dataDir = __DIR__ . '/../../data/rooms/';
            $rooms = [];
            
            if (!is_dir($dataDir)) {
                if ($this->logger) $this->logger->info('房間數據目錄不存在，創建目錄');
                mkdir($dataDir, 0755, true);
                return [];
            }
            
            $roomFiles = glob($dataDir . '*.json');
            
            foreach ($roomFiles as $roomFile) {
                $roomId = basename($roomFile, '.json');
                $roomData = json_decode(file_get_contents($roomFile), true);
                
                if ($roomData) {
                    // 獲取房間中的用戶
                    $users = $this->getRoomUsers($roomId);
                    
                    $rooms[] = [
                        'id' => $roomId,
                        'name' => $roomData['name'] ?? $roomId,
                        'users' => $users,
                        'current_code' => $roomData['code'] ?? '',
                        'version' => $roomData['version'] ?? 1,
                        'created_at' => $roomData['created_at'] ?? date('c'),
                        'last_activity' => $roomData['last_activity'] ?? time()
                    ];
                }
            }
            
            if ($this->logger) $this->logger->info("載入了 " . count($rooms) . " 個房間");
            return $rooms;
            
        } catch (Exception $e) {
            if ($this->logger) $this->logger->error("獲取房間列表失敗: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 獲取房間基本信息
     * @param string $roomId 房間ID
     * @return array|null 房間信息
     */
    public function getRoomInfo($roomId) {
        try {
            $roomFile = __DIR__ . '/../../data/rooms/' . $roomId . '.json';
            
            if (!file_exists($roomFile)) {
                return null;
            }
            
            $roomData = json_decode(file_get_contents($roomFile), true);
            return $roomData ?: null;
            
        } catch (Exception $e) {
            $this->logger->error("獲取房間信息失敗: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 獲取房間中的用戶列表
     * @param string $roomId 房間ID
     * @return array 用戶列表
     */
    public function getRoomUsers($roomId) {
        try {
            $usersFile = __DIR__ . '/../../data/rooms/' . $roomId . '_users.json';
            
            if (!file_exists($usersFile)) {
                return [];
            }
            
            $usersData = json_decode(file_get_contents($usersFile), true);
            return $usersData ?: [];
            
        } catch (Exception $e) {
            $this->logger->error("獲取房間用戶失敗: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 獲取房間當前代碼
     * @param string $roomId 房間ID
     * @return string 代碼內容
     */
    public function getRoomCode($roomId) {
        try {
            $codeFile = __DIR__ . '/../../data/rooms/' . $roomId . '_code.txt';
            
            if (!file_exists($codeFile)) {
                return '';
            }
            
            return file_get_contents($codeFile) ?: '';
            
        } catch (Exception $e) {
            $this->logger->error("獲取房間代碼失敗: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * 保存房間信息
     * @param string $roomId 房間ID
     * @param array $roomData 房間數據
     * @return bool 是否成功
     */
    public function saveRoomInfo($roomId, $roomData) {
        try {
            $dataDir = __DIR__ . '/../../data/rooms/';
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            
            $roomFile = $dataDir . $roomId . '.json';
            $roomData['last_activity'] = time();
            
            $result = file_put_contents($roomFile, json_encode($roomData, JSON_PRETTY_PRINT));
            
            if ($result !== false) {
                $this->logger->info("保存房間信息成功: $roomId");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("保存房間信息失敗: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 保存房間用戶列表
     * @param string $roomId 房間ID
     * @param array $users 用戶列表
     * @return bool 是否成功
     */
    public function saveRoomUsers($roomId, $users) {
        try {
            $dataDir = __DIR__ . '/../../data/rooms/';
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            
            $usersFile = $dataDir . $roomId . '_users.json';
            $result = file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
            
            if ($result !== false) {
                $this->logger->info("保存房間用戶成功: $roomId");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("保存房間用戶失敗: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 保存房間代碼
     * @param string $roomId 房間ID
     * @param string $code 代碼內容
     * @return bool 是否成功
     */
    public function saveRoomCode($roomId, $code) {
        try {
            $dataDir = __DIR__ . '/../../data/rooms/';
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            
            $codeFile = $dataDir . $roomId . '_code.txt';
            $result = file_put_contents($codeFile, $code);
            
            if ($result !== false) {
                $this->logger->info("保存房間代碼成功: $roomId");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("保存房間代碼失敗: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 創建新房間
     * @param string $roomId 房間ID
     * @param string $roomName 房間名稱
     * @param string $creatorId 創建者ID
     * @return bool 是否成功
     */
    public function createRoom($roomId, $roomName, $creatorId) {
        try {
            $roomData = [
                'id' => $roomId,
                'name' => $roomName,
                'creator_id' => $creatorId,
                'code' => '',
                'version' => 1,
                'created_at' => date('c'),
                'last_activity' => time()
            ];
            
            $result = $this->saveRoomInfo($roomId, $roomData);
            
            if ($result) {
                // 初始化空的用戶列表
                $this->saveRoomUsers($roomId, []);
                // 初始化空代碼
                $this->saveRoomCode($roomId, '');
                
                $this->logger->info("創建房間成功: $roomId ($roomName)");
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->logger->error("創建房間失敗: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 刪除房間
     * @param string $roomId 房間ID
     * @return bool 是否成功
     */
    public function deleteRoom($roomId) {
        try {
            $dataDir = __DIR__ . '/../../data/rooms/';
            $files = [
                $dataDir . $roomId . '.json',
                $dataDir . $roomId . '_users.json',
                $dataDir . $roomId . '_code.txt'
            ];
            
            $deletedCount = 0;
            foreach ($files as $file) {
                if (file_exists($file) && unlink($file)) {
                    $deletedCount++;
                }
            }
            
            if ($deletedCount > 0) {
                $this->logger->info("刪除房間成功: $roomId");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("刪除房間失敗: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 檢查房間是否存在
     * @param string $roomId 房間ID
     * @return bool 是否存在
     */
    public function roomExists($roomId) {
        $roomFile = __DIR__ . '/../../data/rooms/' . $roomId . '.json';
        return file_exists($roomFile);
    }
    
    /**
     * 獲取房間統計信息
     * @return array 統計數據
     */
    public function getRoomStatistics() {
        try {
            $rooms = $this->getAllRoomsWithUsers();
            
            $totalRooms = count($rooms);
            $totalUsers = 0;
            $activeRooms = 0;
            
            foreach ($rooms as $room) {
                $userCount = count($room['users']);
                $totalUsers += $userCount;
                
                if ($userCount > 0) {
                    $activeRooms++;
                }
            }
            
            return [
                'totalRooms' => $totalRooms,
                'activeRooms' => $activeRooms,
                'totalUsers' => $totalUsers,
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            $this->logger->error("獲取房間統計失敗: " . $e->getMessage());
            return [
                'totalRooms' => 0,
                'activeRooms' => 0,
                'totalUsers' => 0,
                'timestamp' => time()
            ];
        }
    }
}
?> 