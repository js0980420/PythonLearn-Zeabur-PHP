# PythonLearn 進階功能開發分工計劃

## 🎯 **系統架構總覽**

基於WebSocket日誌分析，系統包含以下核心模組：
- ✅ **基礎功能** (已部分完成): 5槽位保存/載入系統
- 🔧 **WebSocket整合** (進行中): 實時協作通信
- 📊 **SQL優化** (急需): 數據庫結構和性能
- 🤖 **AI助教** (待整合): 智能程式輔導
- 💬 **聊天室** (待開發): 即時討論功能  
- 👨‍🏫 **教師監控** (待實現): 學習進度追蹤
- ⚠️ **衝突檢測** (部分bug): 多用戶協作衝突處理

---

## 📋 **第一階段：核心基礎修復** (Week 1-2)

### 🖥️ **主機任務 (Backend Critical)**
**優先級：🔴 極高**

#### 1. 資料庫結構修復
```sql
-- 修復當前問題
ALTER TABLE code_history MODIFY version_number INT NOT NULL AUTO_INCREMENT;
ALTER TABLE code_changes MODIFY change_type ENUM('edit', 'delete', 'insert', 'replace') NOT NULL;

-- 新增AI助教表
CREATE TABLE ai_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    question TEXT NOT NULL,
    ai_response LONGTEXT NOT NULL,
    interaction_type ENUM('code_help', 'explanation', 'debug', 'suggestion') NOT NULL,
    code_context LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_user (room_id, user_id)
);
```

#### 2. WebSocket服務器優化
```php
// 修復主要問題
class CodeCollaborationServer {
    // 修復Database::insert()錯誤
    private function logCodeChange($roomId, $userId, $changeType, $codeLength) {
        // 使用PDO預處理語句替代insert()方法
    }
    
    // 改進衝突檢測邏輯
    private function detectConflict($roomId, $userId, $newCode) {
        // 智能衝突檢測算法
    }
}
```

### 💻 **筆電任務 (Frontend Foundation)**
**優先級：🟡 中等**

#### 1. UI框架建立
- 創建AI助教聊天界面框架
- 設計教師監控儀表板佈局
- 建立聊天室基礎組件

#### 2. 前端錯誤處理
- 完善WebSocket連接重試機制  
- 添加用戶友好的錯誤提示
- 優化載入狀態顯示

---

## 📋 **第二階段：功能模組開發** (Week 3-4)

### 🖥️ **主機任務組A：AI助教後端**
```php
// AI助教核心類
class AITeacher {
    public function analyzeCode($code, $roomId, $userId) {
        // 代碼分析和建議
    }
    
    public function explainError($errorMessage, $code) {
        // 錯誤解釋
    }
    
    public function generateHint($code, $difficulty) {
        // 智能提示生成
    }
}
```

### 🖥️ **主機任務組B：衝突檢測系統**
```php
class ConflictDetector {
    public function detectRealTimeConflict($roomId, $userId, $changes) {
        // 實時衝突檢測
    }
    
    public function mergeChanges($conflictingChanges) {
        // 智能合併策略
    }
    
    public function notifyConflict($roomId, $conflictData) {
        // 衝突通知機制
    }
}
```

### 💻 **筆電任務組A：AI助教前端**
```javascript
class AITeacherUI {
    showCodeSuggestion(suggestion) {
        // 顯示AI建議
    }
    
    handleErrorExplanation(explanation) {
        // 錯誤解釋界面
    }
    
    displayHint(hint) {
        // 智能提示顯示
    }
}
```

### 💻 **筆電任務組B：聊天室系統**
```javascript
class ChatRoom {
    sendMessage(message, roomId) {
        // 發送聊天消息
    }
    
    displayMessage(message, userInfo) {
        // 顯示聊天內容
    }
    
    handleFileShare(file) {
        // 文件分享功能
    }
}
```

---

## 📋 **第三階段：進階功能** (Week 5-6)

### 🖥️ **主機任務：教師監控系統**
```php
class TeacherDashboard {
    public function getRoomStatistics($roomId) {
        // 房間統計數據
    }
    
    public function getUserProgress($userId) {
        // 學生進度追蹤
    }
    
    public function generateReport($timeRange) {
        // 生成學習報告
    }
}
```

### 💻 **筆電任務：監控界面**
```javascript
class MonitoringUI {
    displayRoomOverview(roomData) {
        // 房間概覽
    }
    
    showStudentProgress(progressData) {
        // 學生進度圖表
    }
    
    renderRealTimeStats(stats) {
        // 實時統計顯示
    }
}
```

---

## 🔧 **詳細技術分工**

### **資料庫設計** (主機專責)

#### 現有表優化
```sql
-- 修復version_number問題
ALTER TABLE code_history ADD COLUMN IF NOT EXISTS 
    auto_version_number INT NOT NULL AUTO_INCREMENT AFTER version_number;

-- 新增索引優化
CREATE INDEX idx_room_created_slot ON code_history (room_id, created_at, slot_id);
CREATE INDEX idx_user_activity ON code_changes (user_id, created_at);
```

#### 新增表結構
```sql
-- 聊天消息表
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'code', 'file', 'system') DEFAULT 'text',
    reply_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_time (room_id, created_at),
    FOREIGN KEY (reply_to) REFERENCES chat_messages(id)
);

-- 教師監控表
CREATE TABLE teacher_monitoring (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(255) NOT NULL,
    teacher_id VARCHAR(255) NOT NULL,
    monitoring_type ENUM('real_time', 'scheduled', 'alert') NOT NULL,
    status ENUM('active', 'paused', 'stopped') DEFAULT 'active',
    config JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### **WebSocket事件擴展** (主機專責)

#### 新增消息類型
```php
// websocket/server.php 新增處理方法
private function handleAIRequest($conn, $data) {
    // AI助教請求處理
}

private function handleChatMessage($conn, $data) {
    // 聊天消息處理
}

private function handleTeacherControl($conn, $data) {
    // 教師控制命令
}

private function handleConflictResolution($conn, $data) {
    // 衝突解決處理
}
```

### **前端組件架構** (筆電專責)

#### 組件結構
```
src/
├── components/
│   ├── AI/
│   │   ├── AITeacherPanel.js
│   │   ├── CodeSuggestion.js
│   │   └── ErrorExplainer.js
│   ├── Chat/
│   │   ├── ChatRoom.js
│   │   ├── MessageList.js
│   │   └── FileShare.js
│   ├── Monitor/
│   │   ├── TeacherDashboard.js
│   │   ├── StudentProgress.js
│   │   └── RealTimeStats.js
│   └── Conflict/
│       ├── ConflictResolver.js
│       └── MergeHelper.js
└── services/
    ├── AIService.js
    ├── ChatService.js
    └── MonitorService.js
```

---

## 🚀 **集成測試策略**

### **階段性測試**
1. **Week 2**: 基礎功能整合測試
2. **Week 4**: 模組間通信測試  
3. **Week 6**: 完整系統壓力測試

### **測試用例分工**
- **主機**: API壓力測試、數據庫性能測試
- **筆電**: UI交互測試、用戶體驗測試

---

## 📞 **溝通協調機制**

### **每日同步**
- **時間**: 每天10:00-10:15
- **內容**: 進度匯報、問題討論、接口確認

### **週會總結**  
- **時間**: 每週五15:00-16:00
- **內容**: 週成果展示、下週計劃、技術難點討論

### **緊急溝通**
- **工具**: Slack/微信群組
- **響應時間**: 30分鐘內回應

---

## 🎯 **成功標準**

### **技術指標**
- WebSocket連接穩定率 > 99%
- AI響應時間 < 2秒
- 聊天消息延遲 < 100ms
- 衝突檢測準確率 > 95%

### **用戶體驗**
- 界面響應時間 < 300ms
- 功能可用性 > 98%
- 用戶滿意度 > 4.5/5

這個分工計劃確保了後續開發的高效進行，避免了重複工作，並建立了清晰的責任分工。您覺得這個規劃如何？需要調整哪個部分嗎？ 