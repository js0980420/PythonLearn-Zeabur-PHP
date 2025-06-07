/**
 * 本地存儲支援腳本
 * 用於在本地開發環境中同步前端和後端的數據
 */

class LocalStorageManager {
    constructor() {
        this.storageKey = 'pythonlearn_local_data';
        this.syncInterval = 5000; // 5秒同步一次
        this.isEnabled = this.checkLocalMode();
        
        if (this.isEnabled) {
            console.log('🔧 本地存儲模式已啟用');
            this.startSync();
        }
    }
    
    /**
     * 檢查是否在本地開發模式
     */
    checkLocalMode() {
        return window.location.hostname === 'localhost' || 
               window.location.hostname === '127.0.0.1' ||
               window.location.hostname === '';
    }
    
    /**
     * 開始同步
     */
    startSync() {
        // 初始載入
        this.loadFromLocalStorage();
        
        // 定期同步
        setInterval(() => {
            this.syncWithServer();
        }, this.syncInterval);
        
        // 頁面卸載時保存
        window.addEventListener('beforeunload', () => {
            this.saveToLocalStorage();
        });
    }
    
    /**
     * 從 localStorage 載入數據
     */
    loadFromLocalStorage() {
        try {
            const data = localStorage.getItem(this.storageKey);
            if (data) {
                const parsedData = JSON.parse(data);
                console.log('📥 從本地存儲載入數據:', parsedData);
                return parsedData;
            }
        } catch (error) {
            console.error('❌ 載入本地存儲數據失敗:', error);
        }
        return null;
    }
    
    /**
     * 保存數據到 localStorage
     */
    saveToLocalStorage(data = null) {
        if (!this.isEnabled) return;
        
        try {
            const dataToSave = data || this.getCurrentData();
            localStorage.setItem(this.storageKey, JSON.stringify(dataToSave));
            console.log('💾 數據已保存到本地存儲');
        } catch (error) {
            console.error('❌ 保存到本地存儲失敗:', error);
        }
    }
    
    /**
     * 獲取當前數據
     */
    getCurrentData() {
        return {
            timestamp: Date.now(),
            room_id: window.currentRoom || null,
            user_id: window.currentUser || null,
            code_content: this.getCurrentCode(),
            chat_history: this.getChatHistory(),
            user_list: this.getUserList()
        };
    }
    
    /**
     * 獲取當前代碼
     */
    getCurrentCode() {
        const editor = document.getElementById('code-editor');
        return editor ? editor.value : '';
    }
    
    /**
     * 獲取聊天歷史
     */
    getChatHistory() {
        const chatMessages = document.querySelectorAll('.chat-message');
        const history = [];
        
        chatMessages.forEach(msg => {
            const author = msg.querySelector('.message-author')?.textContent || '';
            const content = msg.querySelector('.message-content')?.textContent || '';
            const time = msg.querySelector('.message-time')?.textContent || '';
            
            if (author && content) {
                history.push({ author, content, time });
            }
        });
        
        return history;
    }
    
    /**
     * 獲取用戶列表
     */
    getUserList() {
        const userElements = document.querySelectorAll('.user-item');
        const users = [];
        
        userElements.forEach(userEl => {
            const username = userEl.textContent.trim();
            if (username) {
                users.push(username);
            }
        });
        
        return users;
    }
    
    /**
     * 與服務器同步
     */
    async syncWithServer() {
        if (!this.isEnabled) return;
        
        try {
            // 檢查服務器狀態
            const response = await fetch('/health');
            const healthData = await response.json();
            
            if (healthData.services?.database?.mode === 'localStorage') {
                // 服務器也在使用本地存儲模式，進行同步
                const currentData = this.getCurrentData();
                this.saveToLocalStorage(currentData);
                
                // 可以在這裡添加與服務器的數據同步邏輯
                console.log('🔄 與服務器同步完成');
            }
            
        } catch (error) {
            console.warn('⚠️ 服務器同步失敗:', error.message);
        }
    }
    
    /**
     * 清理本地存儲
     */
    clearLocalStorage() {
        if (!this.isEnabled) return;
        
        try {
            localStorage.removeItem(this.storageKey);
            console.log('🗑️ 本地存儲已清理');
        } catch (error) {
            console.error('❌ 清理本地存儲失敗:', error);
        }
    }
    
    /**
     * 導出數據
     */
    exportData() {
        const data = this.loadFromLocalStorage();
        if (data) {
            const blob = new Blob([JSON.stringify(data, null, 2)], {
                type: 'application/json'
            });
            
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `pythonlearn_backup_${new Date().toISOString().slice(0, 10)}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            console.log('📤 數據已導出');
        }
    }
    
    /**
     * 導入數據
     */
    importData(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                try {
                    const data = JSON.parse(e.target.result);
                    this.saveToLocalStorage(data);
                    console.log('📥 數據已導入');
                    resolve(data);
                } catch (error) {
                    console.error('❌ 導入數據失敗:', error);
                    reject(error);
                }
            };
            
            reader.onerror = () => {
                reject(new Error('文件讀取失敗'));
            };
            
            reader.readAsText(file);
        });
    }
    
    /**
     * 獲取存儲統計信息
     */
    getStorageStats() {
        if (!this.isEnabled) return null;
        
        try {
            const data = this.loadFromLocalStorage();
            const storageSize = new Blob([JSON.stringify(data)]).size;
            
            return {
                enabled: this.isEnabled,
                size: `${(storageSize / 1024).toFixed(2)} KB`,
                last_sync: data?.timestamp ? new Date(data.timestamp).toLocaleString() : 'Never',
                room_id: data?.room_id || 'None',
                user_id: data?.user_id || 'None',
                code_length: data?.code_content?.length || 0,
                chat_messages: data?.chat_history?.length || 0,
                users_count: data?.user_list?.length || 0
            };
        } catch (error) {
            console.error('❌ 獲取存儲統計失敗:', error);
            return null;
        }
    }
    
    /**
     * 顯示存儲狀態
     */
    showStorageStatus() {
        const stats = this.getStorageStats();
        if (stats) {
            console.table(stats);
        }
    }
}

// 創建全域實例
const localStorageManager = new LocalStorageManager();

// 添加到全域對象以便調試
window.localStorageManager = localStorageManager;

// 添加調試命令
if (window.location.hostname === 'localhost') {
    console.log(`
🔧 本地存儲調試命令:
- localStorageManager.showStorageStatus() - 顯示存儲狀態
- localStorageManager.exportData() - 導出數據
- localStorageManager.clearLocalStorage() - 清理存儲
- localStorageManager.syncWithServer() - 手動同步
    `);
} 