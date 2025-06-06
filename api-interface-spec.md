# API接口規範文件

## 🔌 WebSocket 接口定義

### 保存代碼接口
```javascript
// 前端發送 (筆電負責實現)
{
    "type": "save_code",
    "room_id": "test-room",
    "code": "print('Hello World')",
    "slot_id": 1,  // 0=自動保存, 1-4=手動保存槽位
    "save_name": "用戶自定義名稱",
    "operation_type": "manual" // "auto" | "manual"
}

// 後端響應 (主機負責實現)
{
    "type": "save_response",
    "success": true,
    "message": "保存成功",
    "slot_id": 1,
    "version_number": 5
}
```

### 載入歷史記錄接口
```javascript
// 前端請求 (筆電負責實現)
{
    "type": "get_history",
    "room_id": "test-room"
}

// 後端響應 (主機負責實現)
{
    "type": "history_response",
    "success": true,
    "data": [
        {
            "id": 1,
            "slot_id": 0,
            "name": "最新版本",
            "code": "print('latest')",
            "created_at": "2025-06-07 10:30:00",
            "version_number": 10
        },
        {
            "id": 2,
            "slot_id": 1,
            "name": "第一個版本",
            "code": "print('first')",
            "created_at": "2025-06-07 09:15:00",
            "version_number": 5
        }
        // ... 其他槽位
    ]
}
```

### 載入特定代碼接口
```javascript
// 前端請求 (筆電負責實現)
{
    "type": "load_code",
    "room_id": "test-room",
    "slot_id": 1  // 可選，不提供則載入最新版本
}

// 後端響應 (主機負責實現)
{
    "type": "load_response",
    "success": true,
    "data": {
        "code": "print('loaded code')",
        "slot_id": 1,
        "save_name": "第一個版本",
        "created_at": "2025-06-07 09:15:00"
    }
}
```

## 🗄️ 數據庫表結構 (主機負責)

### code_history表
```sql
CREATE TABLE code_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    slot_id INT NOT NULL DEFAULT 0, -- 0-4槽位系統
    save_name VARCHAR(255) NOT NULL,
    code LONGTEXT NOT NULL,
    version_number INT NOT NULL,
    operation_type ENUM('auto', 'manual') DEFAULT 'manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_slot (room_id, slot_id),
    INDEX idx_room_created (room_id, created_at)
);
```

## 🎨 前端UI組件規範 (筆電負責)

### 保存按鈕組件
```html
<!-- 保存按鈕 + 下拉選單 -->
<div class="btn-group" role="group">
    <button class="btn btn-outline-primary btn-sm" onclick="quickSave()">
        <i class="fas fa-save"></i> 保存
    </button>
    <button class="btn btn-outline-primary btn-sm dropdown-toggle dropdown-toggle-split" 
            type="button" data-bs-toggle="dropdown">
        <span class="visually-hidden">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu" id="saveDropdownMenu">
        <!-- 動態填充槽位選項 -->
    </ul>
</div>
```

### 槽位選擇對話框
```html
<div class="modal fade" id="saveSlotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">選擇保存槽位</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="slotSelectionBody">
                <!-- 動態填充5個槽位選項 -->
            </div>
        </div>
    </div>
</div>
```

## 📱 JavaScript函數接口 (筆電負責)

### SaveLoadManager類方法
```javascript
class SaveLoadManager {
    // 快速保存到槽位0
    quickSave() { }
    
    // 顯示保存槽位選擇
    showSaveSlotDialog() { }
    
    // 保存到指定槽位
    saveToSlot(slotId, saveName) { }
    
    // 載入最新版本
    loadLatest() { }
    
    // 載入指定槽位
    loadFromSlot(slotId) { }
    
    // 獲取歷史記錄
    getHistory() { }
    
    // 更新保存下拉選單
    updateSaveDropdown(historyData) { }
    
    // 更新載入下拉選單  
    updateLoadDropdown(historyData) { }
}
```

## 🔧 PHP後端方法 (主機負責)

### Database類方法
```php
class Database {
    // 保存代碼到指定槽位
    public function saveCode($roomId, $userId, $code, $slotId = 0, $saveName = '', $operationType = 'manual')
    
    // 獲取房間歷史記錄
    public function getCodeHistory($roomId)
    
    // 載入指定槽位代碼
    public function loadCode($roomId, $slotId = null)
    
    // 刪除指定槽位
    public function deleteSlot($roomId, $slotId)
    
    // 獲取槽位信息
    public function getSlotInfo($roomId, $slotId)
}
```

### WebSocket服務器方法
```php
class CodeCollaborationServer {
    // 處理保存請求
    private function handleSaveCode($conn, $data)
    
    // 處理載入請求  
    private function handleLoadCode($conn, $data)
    
    // 處理歷史記錄請求
    private function handleGetHistory($conn, $data)
    
    // 廣播更新給房間內用戶
    private function broadcastToRoom($roomId, $message)
}
```

## 🚦 錯誤處理規範

### 錯誤代碼定義
```javascript
const ERROR_CODES = {
    SAVE_FAILED: 1001,
    LOAD_FAILED: 1002,
    SLOT_NOT_FOUND: 1003,
    PERMISSION_DENIED: 1004,
    DATABASE_ERROR: 1005
};
```

### 錯誤響應格式
```javascript
{
    "type": "error_response",
    "success": false,
    "error_code": 1001,
    "message": "保存失敗：數據庫錯誤",
    "details": "Field 'version_number' doesn't have a default value"
}
```

## 📋 開發檢查清單

### 主機完成標準
- [ ] Database::saveCode方法支持5槽位
- [ ] 修復version_number字段問題
- [ ] WebSocket服務器穩定運行
- [ ] 所有後端接口按規範實現

### 筆電完成標準  
- [ ] 保存按鈕下拉選單UI實現
- [ ] 槽位選擇對話框功能完整
- [ ] JavaScript接口按規範實現
- [ ] 前端用戶體驗流暢

### 集成測試標準
- [ ] 保存和載入功能正常
- [ ] 5槽位系統運作正確
- [ ] 前後端數據同步無誤
- [ ] 多用戶協作功能穩定 