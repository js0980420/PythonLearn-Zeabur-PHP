# 🎯 PythonLearn 雙重調試指南
## Playwright + Browser Console 完美組合

### 🚀 當前環境狀態
✅ **本地服務器**: `php -S localhost:8080 -t public` (已啟動)
✅ **訪問地址**: http://localhost:8080
✅ **API 端點**: http://localhost:8080/api.php

---

## 🎭 Method 1: Playwright 自動化調試

### 基本設置
```javascript
// 1. 導航到應用
await page.goto('http://localhost:8080');

// 2. 注入調試助手
await page.addInitScript(() => {
    window.debugHelper = {
        // API 測試函數
        async testAPI(action, params = {}) {
            const url = new URL('/api.php', 'http://localhost:8080');
            url.searchParams.set('action', action);
            Object.entries(params).forEach(([key, value]) => {
                url.searchParams.set(key, value);
            });
            
            const response = await fetch(url);
            const data = await response.json();
            console.log(`🔍 API [${action}]:`, data);
            return data;
        },
        
        // 用戶管理測試
        async testUserFlow() {
            console.log('🧪 開始用戶流程測試...');
            
            // 1. 獲取最近用戶
            const users = await this.testAPI('get_recent_users', { limit: 5 });
            
            // 2. 測試加入房間
            const joinResult = await this.testAPI('join', {
                room_id: 'debug-room',
                user_id: 'debug-user-001',
                user_name: '調試用戶',
                is_teacher: 'false'
            });
            
            // 3. 獲取在線用戶
            const onlineUsers = await this.testAPI('get_online_users', {
                room_id: 'debug-room'
            });
            
            return { users, joinResult, onlineUsers };
        },
        
        // 聊天功能測試
        async testChat() {
            console.log('💬 測試聊天功能...');
            
            // 發送測試消息
            const sendResult = await this.testAPI('send_message', {
                room_id: 'debug-room',
                user_id: 'debug-user-001',
                message: '這是一條調試消息 ' + new Date().toLocaleTimeString()
            });
            
            // 獲取聊天記錄
            const chatHistory = await this.testAPI('get_chat_messages', {
                room_id: 'debug-room',
                limit: 10
            });
            
            return { sendResult, chatHistory };
        },
        
        // AI 功能測試（如果可用）
        async testAI() {
            console.log('🤖 測試 AI 功能...');
            
            const testCode = 'print("Hello, World!")\\nfor i in range(3):\\n    print(f"數字: {i}")';
            
            try {
                const aiResult = await this.testAPI('ai_analyze', {
                    code: testCode,
                    analysis_type: 'explain'
                });
                return aiResult;
            } catch (error) {
                console.log('ℹ️ AI 功能不可用:', error.message);
                return { error: 'AI 功能不可用' };
            }
        }
    };
    
    // 自動運行基礎測試
    console.log('🔧 調試助手已加載！使用 debugHelper.testUserFlow() 開始測試');
});
```

### Playwright 執行命令
```javascript
// 在 Playwright 中執行
await page.evaluate(async () => {
    // 運行完整測試套件
    const userFlow = await window.debugHelper.testUserFlow();
    const chatTest = await window.debugHelper.testChat();
    const aiTest = await window.debugHelper.testAI();
    
    console.log('📊 測試結果總覽:', {
        userFlow,
        chatTest,
        aiTest
    });
    
    return { userFlow, chatTest, aiTest };
});
```

---

## 🌐 Method 2: Browser Console 手動調試

### 打開瀏覽器控制台 (F12)
1. 訪問 http://localhost:8080
2. 按 F12 打開開發者工具
3. 切換到 Console 標籤

### 控制台調試命令

#### 🔍 API 測試助手
```javascript
// 貼到控制台執行
window.apiTest = {
    async call(action, params = {}) {
        const url = new URL('/api.php', location.origin);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([key, value]) => {
            url.searchParams.set(key, value);
        });
        
        console.log(`🔄 調用 API: ${action}`, params);
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            console.log(`✅ API 響應:`, data);
            return data;
        } catch (error) {
            console.error(`❌ API 錯誤:`, error);
            return { error: error.message };
        }
    }
};

console.log('🎯 API 測試助手已加載！使用 apiTest.call("action", {params}) 進行測試');
```

#### 📝 快速測試命令
```javascript
// 1. 測試系統狀態
await apiTest.call('status');

// 2. 獲取最近用戶
await apiTest.call('get_recent_users', { limit: 10 });

// 3. 測試加入房間
await apiTest.call('join', {
    room_id: 'console-debug',
    user_id: 'console-user',
    user_name: '控制台用戶',
    is_teacher: false
});

// 4. 測試發送消息
await apiTest.call('send_message', {
    room_id: 'console-debug',
    user_id: 'console-user',
    message: '來自控制台的測試消息'
});

// 5. 獲取聊天記錄
await apiTest.call('get_chat_messages', {
    room_id: 'console-debug',
    limit: 5
});

// 6. 測試 AI 功能（如果可用）
await apiTest.call('ai_analyze', {
    code: 'print("Hello from console!")',
    analysis_type: 'explain'
});
```

#### 🔄 持續監控
```javascript
// 持續監控在線用戶
window.userMonitor = setInterval(async () => {
    const users = await apiTest.call('get_online_users', { room_id: 'console-debug' });
    console.log(`👥 在線用戶 (${new Date().toLocaleTimeString()}):`, users);
}, 5000);

// 停止監控：clearInterval(window.userMonitor);
```

---

## 🎯 調試重點功能

### 1. 用戶管理系統
```javascript
// 測試用戶生命週期
async function testUserLifecycle() {
    console.log('🔄 測試用戶生命週期...');
    
    // 加入
    const join = await apiTest.call('join', {
        room_id: 'lifecycle-test',
        user_id: 'test-user-001',
        user_name: '生命週期測試用戶',
        is_teacher: false
    });
    
    // 心跳
    const heartbeat = await apiTest.call('heartbeat', {
        room_id: 'lifecycle-test',
        user_id: 'test-user-001'
    });
    
    // 離開
    const leave = await apiTest.call('leave', {
        room_id: 'lifecycle-test',
        user_id: 'test-user-001'
    });
    
    return { join, heartbeat, leave };
}
```

### 2. 聊天系統
```javascript
// 測試聊天流程
async function testChatFlow() {
    console.log('💬 測試聊天流程...');
    
    const roomId = 'chat-test-' + Date.now();
    
    // 用戶A加入
    await apiTest.call('join', {
        room_id: roomId,
        user_id: 'user-a',
        user_name: '用戶A',
        is_teacher: false
    });
    
    // 用戶B加入
    await apiTest.call('join', {
        room_id: roomId,
        user_id: 'user-b',
        user_name: '用戶B',
        is_teacher: false
    });
    
    // 發送消息
    await apiTest.call('send_message', {
        room_id: roomId,
        user_id: 'user-a',
        message: '大家好！'
    });
    
    await apiTest.call('send_message', {
        room_id: roomId,
        user_id: 'user-b',
        message: '你好，用戶A！'
    });
    
    // 獲取聊天記錄
    const messages = await apiTest.call('get_chat_messages', {
        room_id: roomId,
        limit: 10
    });
    
    return messages;
}
```

### 3. 教師監控功能
```javascript
// 測試教師功能
async function testTeacherFeatures() {
    console.log('👨‍🏫 測試教師功能...');
    
    const roomId = 'teacher-test-' + Date.now();
    
    // 教師加入
    const teacherJoin = await apiTest.call('join', {
        room_id: roomId,
        user_id: 'teacher-001',
        user_name: '測試教師',
        is_teacher: true
    });
    
    // 學生加入
    await apiTest.call('join', {
        room_id: roomId,
        user_id: 'student-001',
        user_name: '測試學生',
        is_teacher: false
    });
    
    // 獲取房間信息
    const roomInfo = await apiTest.call('get_room_info', {
        room_id: roomId
    });
    
    return { teacherJoin, roomInfo };
}
```

---

## 📊 調試技巧

### 1. 網絡監控
- 打開 Network 標籤監控 API 請求
- 查看請求/響應詳情
- 檢查請求時間和狀態碼

### 2. 控制台日誌
- 使用 `console.log()` 追蹤執行流程
- 使用 `console.table()` 美化數據顯示
- 使用 `console.group()` 組織日誌

### 3. 性能分析
```javascript
// 性能測試
async function performanceTest() {
    console.time('API Response Time');
    await apiTest.call('get_recent_users', { limit: 50 });
    console.timeEnd('API Response Time');
}
```

---

## 🎯 調試檢查清單

### ✅ 基礎功能
- [ ] 服務器響應正常
- [ ] API 端點可訪問
- [ ] 數據庫連接成功
- [ ] 用戶加入/離開正常

### ✅ 進階功能
- [ ] 聊天系統運作
- [ ] 多用戶併發測試
- [ ] 教師監控功能
- [ ] AI 助教功能（如果配置）

### ✅ 錯誤處理
- [ ] 無效參數處理
- [ ] 網絡錯誤處理
- [ ] 數據庫錯誤處理
- [ ] 用戶離線處理

現在您可以：
1. **運行批處理文件**: `debug_local.bat`
2. **使用 Playwright**: 自動化測試流程
3. **使用瀏覽器控制台**: 手動調試和實時測試

兩種方法可以完美配合使用！🚀 