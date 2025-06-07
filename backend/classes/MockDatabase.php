<?php

namespace App;

class MockDatabase {
    private static $instance = null;
    private $data = [];
    private $nextId = 1;
    
    private function __construct() {
        // 初始化模擬數據
        $this->data = [
            'users' => [],
            'rooms' => [],
            'room_users' => [],
            'code_history' => [],
            'code_changes' => [],
            'conflicts' => [],
            'ai_requests' => [],
            'system_logs' => []
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function fetch($sql, $params = []) {
        // 簡單的模擬查詢
        if (strpos($sql, 'SELECT * FROM users WHERE username') !== false) {
            $username = $params['username'] ?? '';
            foreach ($this->data['users'] as $user) {
                if ($user['username'] === $username) {
                    return $user;
                }
            }
            return null;
        }
        
        if (strpos($sql, 'SELECT * FROM users WHERE id') !== false) {
            $id = $params['id'] ?? 0;
            foreach ($this->data['users'] as $user) {
                if ($user['id'] == $id) {
                    return $user;
                }
            }
            return null;
        }
        
        if (strpos($sql, 'SELECT * FROM room_users WHERE room_id') !== false) {
            $roomId = $params['room_id'] ?? 0;
            $userId = $params['user_id'] ?? 0;
            foreach ($this->data['room_users'] as $roomUser) {
                if ($roomUser['room_id'] == $roomId && $roomUser['user_id'] == $userId) {
                    return $roomUser;
                }
            }
            return null;
        }
        
        if (strpos($sql, 'SELECT * FROM rooms WHERE id') !== false) {
            $id = $params['id'] ?? 0;
            foreach ($this->data['rooms'] as $room) {
                if ($room['id'] == $id) {
                    return $room;
                }
            }
            return null;
        }
        
        if (strpos($sql, 'SELECT code_content FROM code_history') !== false) {
            // 返回空代碼
            return ['code_content' => '# 歡迎使用Python協作平台\nprint("Hello, World!")'];
        }
        
        if (strpos($sql, 'SELECT COUNT(*) as count FROM room_users') !== false) {
            $roomId = $params['room_id'] ?? 0;
            $count = $this->countRoomUsers($roomId);
            return ['count' => $count];
        }
        
        if (strpos($sql, 'SELECT id FROM rooms WHERE room_name') !== false) {
            $roomName = $params['room_name'] ?? '';
            foreach ($this->data['rooms'] as $room) {
                if ($room['room_name'] === $roomName) {
                    return $room;
                }
            }
            return null;
        }
        
        if (strpos($sql, 'SELECT id FROM room_users WHERE room_id') !== false) {
            $roomId = $params['room_id'] ?? 0;
            $userId = $params['user_id'] ?? 0;
            foreach ($this->data['room_users'] as $roomUser) {
                if ($roomUser['room_id'] == $roomId && $roomUser['user_id'] == $userId) {
                    return $roomUser;
                }
            }
            return null;
        }
        
        if (strpos($sql, 'SELECT * FROM code_history WHERE id') !== false) {
            $historyId = $params['history_id'] ?? 0;
            foreach ($this->data['code_history'] as $record) {
                if ($record['id'] == $historyId) {
                    return $record;
                }
            }
            return null;
        }
        
        return null;
    }
    
    public function fetchAll($sql, $params = []) {
        if (strpos($sql, 'SELECT r.*, u.username as creator_name') !== false) {
            // 返回房間列表
            $rooms = [];
            foreach ($this->data['rooms'] as $room) {
                $creator = $this->getUserById($room['created_by']);
                $room['creator_name'] = $creator ? $creator['username'] : '未知';
                $room['current_users'] = $this->countRoomUsers($room['id']);
                $rooms[] = $room;
            }
            return $rooms;
        }
        
        if (strpos($sql, 'SELECT ru.*, u.username, u.user_type') !== false) {
            // 返回房間用戶列表
            $roomId = $params['room_id'] ?? 0;
            $users = [];
            foreach ($this->data['room_users'] as $roomUser) {
                if ($roomUser['room_id'] == $roomId) {
                    $user = $this->getUserById($roomUser['user_id']);
                    if ($user) {
                        $users[] = array_merge($roomUser, [
                            'username' => $user['username'],
                            'user_type' => $user['user_type']
                        ]);
                    }
                }
            }
            return $users;
        }
        
        if (strpos($sql, 'SELECT ar.*, u.username') !== false) {
            // 返回AI請求歷史
            return array_slice($this->data['ai_requests'], -20); // 最近20條
        }
        
        if (strpos($sql, 'SELECT * FROM code_history WHERE room_id') !== false) {
            // 返回歷史記錄
            $roomId = $params['room_id'] ?? '';
            $limit = $params['limit'] ?? 20;
            
            $history = [];
            foreach ($this->data['code_history'] as $record) {
                if ($record['room_id'] == $roomId) {
                    $history[] = $record;
                }
            }
            
            // 按保存時間降序排列
            usort($history, function($a, $b) {
                $timeA = strtotime($a['saved_at'] ?? $a['created_at']);
                $timeB = strtotime($b['saved_at'] ?? $b['created_at']);
                return $timeB - $timeA;
            });
            
            return array_slice($history, 0, $limit);
        }
        
        return [];
    }
    
    public function insert($table, $data) {
        $id = $this->nextId++;
        $data['id'] = $id;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        if (!isset($this->data[$table])) {
            $this->data[$table] = [];
        }
        
        $this->data[$table][] = $data;
        return $id;
    }
    
    public function update($table, $data, $where) {
        if (!isset($this->data[$table])) {
            return false;
        }
        
        foreach ($this->data[$table] as &$row) {
            $match = true;
            foreach ($where as $key => $value) {
                if ($row[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                foreach ($data as $key => $value) {
                    $row[$key] = $value;
                }
                $row['updated_at'] = date('Y-m-d H:i:s');
                return true;
            }
        }
        return false;
    }
    
    public function delete($table, $where) {
        if (!isset($this->data[$table])) {
            return false;
        }
        
        foreach ($this->data[$table] as $index => $row) {
            $match = true;
            foreach ($where as $key => $value) {
                if ($row[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                unset($this->data[$table][$index]);
                $this->data[$table] = array_values($this->data[$table]); // 重新索引
                return true;
            }
        }
        return false;
    }
    
    public function createTables() {
        // 模擬創建表格，實際上什麼都不做
        return true;
    }
    
    private function getUserById($id) {
        foreach ($this->data['users'] as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return null;
    }
    
    private function countRoomUsers($roomId) {
        $count = 0;
        foreach ($this->data['room_users'] as $roomUser) {
            if ($roomUser['room_id'] == $roomId) {
                $count++;
            }
        }
        return $count;
    }
    
    // 添加一些測試數據
    public function addTestData() {
        // 添加測試用戶
        $this->insert('users', [
            'username' => 'admin',
            'user_type' => 'teacher'
        ]);
        
        $this->insert('users', [
            'username' => 'student1',
            'user_type' => 'student'
        ]);
        
        // 添加測試房間
        $roomId = $this->insert('rooms', [
            'room_name' => '測試房間1',
            'description' => '這是一個測試房間',
            'max_users' => 10,
            'created_by' => 1
        ]);
        
        // 添加房間用戶關係
        $this->insert('room_users', [
            'room_id' => $roomId,
            'user_id' => 1,
            'role' => 'owner'
        ]);
    }
} 