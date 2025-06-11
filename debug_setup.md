# 🛠️ PythonLearn 本地調試環境

## 🎯 雙重調試模式：Playwright + Browser Console

### 📋 準備工作

1. **啟動本地服務器**
   ```bash
   php -S localhost:8080 -t public
   ```

2. **確認服務狀態**
   - 🌐 訪問：http://localhost:8080
   - 📊 API測試：http://localhost:8080/api.php?action=status

---

## 🎭 Playwright 自動化調試

### 🔧 基本 Playwright 操作

```javascript
// 1. 導航到主頁
await page.goto('http://localhost:8080');

// 2. 測試用戶加入
await page.evaluate(() => {
    return fetch('/api.php?action=join&room_id=debug-room&user_id=debug001&user_name=調試用戶&is_teacher=false')
        .then(r => r.json());
});

// 3. 檢查在線用戶
await page.evaluate(() => {
    return fetch('/api.php?action=get_online_users&room_id=debug-room')
        .then(r => r.json());
});

// 4. 模擬輪詢
await page.evaluate(() => {
    return fetch('/api.php?action=poll&room_id=debug-room&user_id=debug001')
        .then(r => r.json());
});
```

### 📊 監控網絡請求
```javascript
// 監聽所有API請求
page.on('response', response => {
    if (response.url().includes('/api.php')) {
        console.log(`API 調用: ${response.url()}`);
        console.log(`狀態: ${response.status()}`);
    }
});
```

---

## 🌐 Browser Console 手動調試

### 🔥 實時 API 測試

開啟瀏覽器開發者工具 (F12)，在 Console 中執行：

```javascript
// 1. 測試 API 狀態
fetch('/api.php?action=status')
    .then(r => r.json())
    .then(data => {
        console.log('📊 API 狀態:', data);
    });

// 2. 加入房間測試
fetch('/api.php?action=join&room_id=console-test&user_id=console001&user_name=控制台用戶&is_teacher=false')
    .then(r => r.json())
    .then(data => {
        console.log('🚪 加入房間:', data);
    });

// 3. 獲取最近用戶
fetch('/api.php?action=get_recent_users&limit=5')
    .then(r => r.json())
    .then(data => {
        console.log('👥 最近用戶:', data);
    });

// 4. 實時輪詢測試
function startPolling() {
    setInterval(() => {
        fetch('/api.php?action=poll&room_id=console-test&user_id=console001')
            .then(r => r.json())
            .then(data => {
                console.log('🔄 輪詢結果:', data.users.length + ' 用戶在線');
            });
    }, 2000);
}
// 執行: startPolling();
```

### 🎯 用戶狀態調試

```javascript
// 用戶管理調試函數
const UserDebug = {
    // 創建測試用戶
    async createTestUser(name, isTeacher = false) {
        const response = await fetch(`/api.php?action=join&room_id=debug&user_id=${Date.now()}&user_name=${name}&is_teacher=${isTeacher}`);
        const data = await response.json();
        console.log(`✅ 創建用戶 ${name}:`, data);
        return data;
    },
    
    // 檢查在線用戶
    async checkOnlineUsers(roomId = 'debug') {
        const response = await fetch(`/api.php?action=get_online_users&room_id=${roomId}`);
        const data = await response.json();
        console.log('👥 在線用戶:', data.users);
        return data.users;
    },
    
    // 資料庫用戶檢查
    async checkRecentUsers() {
        const response = await fetch('/api.php?action=get_recent_users&limit=10');
        const data = await response.json();
        console.log('📊 資料庫用戶:', data.users);
        return data.users;
    }
};

// 使用範例：
// UserDebug.createTestUser('測試學生A', false);
// UserDebug.createTestUser('張老師', true);
// UserDebug.checkOnlineUsers();
// UserDebug.checkRecentUsers();
```

---

## 🔍 綜合調試場景

### 🎪 場景1：多用戶房間測試

```javascript
// Playwright 腳本
async function testMultiUserRoom() {
    // 模擬學生1加入
    await page.evaluate(() => {
        return fetch('/api.php?action=join&room_id=classroom-a&user_id=student001&user_name=小明&is_teacher=false');
    });
    
    // 模擬教師加入
    await page.evaluate(() => {
        return fetch('/api.php?action=join&room_id=classroom-a&user_id=teacher001&user_name=王老師&is_teacher=true');
    });
    
    // 檢查房間狀態
    const roomStatus = await page.evaluate(() => {
        return fetch('/api.php?action=poll&room_id=classroom-a&user_id=student001')
            .then(r => r.json());
    });
    
    console.log('🏫 教室狀態:', roomStatus);
}
```

### 🔄 場景2：實時同步測試

```javascript
// Browser Console 腳本
async function testRealTimeSync() {
    let pollCount = 0;
    
    const pollInterval = setInterval(async () => {
        const response = await fetch('/api.php?action=poll&room_id=sync-test&user_id=sync001');
        const data = await response.json();
        
        console.log(`🔄 第${++pollCount}次輪詢:`, {
            在線用戶數: data.online_count,
            代碼變更: data.code_changes.length,
            時間戳: new Date(data.timestamp * 1000).toLocaleTimeString()
        });
        
        if (pollCount >= 10) {
            clearInterval(pollInterval);
            console.log('✅ 同步測試完成');
        }
    }, 1000);
}
```

---

## 🎛️ 調試技巧

### 🔧 Playwright 技巧

1. **頁面截圖**：`await page.screenshot({path: 'debug.png'})`
2. **網絡監控**：記錄所有API調用
3. **自動等待**：等待特定元素或狀態
4. **批量測試**：同時模擬多個用戶

### 🌐 Browser Console 技巧

1. **網絡面板**：查看API請求詳情
2. **應用面板**：檢查LocalStorage/SessionStorage
3. **控制台面板**：實時執行JavaScript
4. **性能面板**：監控資源使用

### 🎯 組合使用優勢

- **Playwright**：自動化重複測試，模擬複雜場景
- **Browser Console**：即時調試，快速驗證
- **雙重驗證**：確保功能在不同環境下都正常

---

## 🚀 快速開始

1. 啟動服務：`php -S localhost:8080 -t public`
2. 開啟瀏覽器：http://localhost:8080
3. 按F12開啟開發者工具
4. 同時準備Playwright腳本

現在您可以同時使用兩種工具進行全面的調試了！ 