/**
 * 統一用戶管理器 - 確保用戶名稱在整個系統中的一致性
 * 
 * 📅 創建日期: 2025-06-10
 * 🎯 目標: 統一管理用戶身份，避免不同模組間的用戶名不一致問題
 */

class UserManager {
    constructor() {
        this.currentUser = null;
        this.isConnected = false;
        this.connectionStartTime = null;
        this.onlineUsers = new Map();
        this.userChangeCallbacks = [];
        this.isInitialized = false;
        
        // 用戶登入記錄功能
        this.loginHistory = this.loadLoginHistory();
        this.maxHistorySize = 10; // 最多保存10個用戶記錄
        
        this.initializeLoginHistory();
        
        console.log('👤 用戶管理器初始化');
        
        // 從本地存儲恢復用戶信息
        this.restoreUserFromStorage();
    }

    /**
     * 設置當前用戶
     * @param {Object} userData - 用戶數據
     * @param {string} userData.name - 用戶名稱
     * @param {string} userData.room - 房間名稱
     * @param {string} userData.id - 用戶ID（可選）
     */
    setCurrentUser(userData) {
        const previousUser = this.currentUser;
        
        // 創建標準化的用戶對象
        this.currentUser = {
            id: userData.id || this.generateUserId(userData.name, userData.room),
            name: userData.name,
            room: userData.room,
            joinTime: Date.now(),
            lastActivity: Date.now(),
            isOnline: true
        };

        // 保存到本地存儲
        this.saveUserToStorage();
        
        // 更新在線用戶列表
        this.onlineUsers.set(this.currentUser.id, this.currentUser);
        
        // 通知所有相關模組用戶已變更
        this.notifyUserChange(this.currentUser, previousUser);
        
        this.isInitialized = true;
        console.log('👤 當前用戶已設置:', this.currentUser);
        
        return this.currentUser;
    }

    /**
     * 獲取當前用戶
     * @returns {Object|null} 當前用戶對象
     */
    getCurrentUser() {
        return this.currentUser;
    }

    /**
     * 獲取當前用戶名稱
     * @returns {string|null} 用戶名稱
     */
    getCurrentUserName() {
        return this.currentUser ? this.currentUser.name : null;
    }

    /**
     * 獲取當前用戶ID
     * @returns {string|null} 用戶ID
     */
    getCurrentUserId() {
        return this.currentUser ? this.currentUser.id : null;
    }

    /**
     * 獲取當前房間
     * @returns {string|null} 房間名稱
     */
    getCurrentRoom() {
        return this.currentUser ? this.currentUser.room : null;
    }

    /**
     * 更新用戶活動時間
     */
    updateUserActivity() {
        if (this.currentUser) {
            this.currentUser.lastActivity = Date.now();
            this.saveUserToStorage();
        }
    }

    /**
     * 更新在線用戶列表
     * @param {Array} users - 在線用戶列表
     */
    updateOnlineUsers(users) {
        this.onlineUsers.clear();
        
        users.forEach(user => {
            const standardUser = {
                id: user.id || user.user_id,
                name: user.name || user.user_name,
                joinTime: user.joined_at * 1000 || Date.now(),
                lastActivity: user.last_seen * 1000 || Date.now(),
                isOnline: true
            };
            
            this.onlineUsers.set(standardUser.id, standardUser);
        });

        // 確保當前用戶在在線列表中
        if (this.currentUser && !this.onlineUsers.has(this.currentUser.id)) {
            this.onlineUsers.set(this.currentUser.id, this.currentUser);
        }
        
        console.log('👥 在線用戶列表已更新:', Array.from(this.onlineUsers.values()));
    }

    /**
     * 獲取在線用戶列表
     * @returns {Array} 在線用戶數組
     */
    getOnlineUsers() {
        return Array.from(this.onlineUsers.values());
    }

    /**
     * 檢查用戶是否在線
     * @param {string} userId - 用戶ID
     * @returns {boolean} 是否在線
     */
    isUserOnline(userId) {
        return this.onlineUsers.has(userId);
    }

    /**
     * 生成用戶ID
     * @param {string} userName - 用戶名稱
     * @param {string} roomName - 房間名稱
     * @returns {string} 生成的用戶ID
     */
    generateUserId(userName, roomName = 'general-room') {
        // 🔥 基於用戶名和房間生成固定ID，避免重新整理產生新ID
        const cleanUserName = userName.replace(/[^a-zA-Z0-9]/g, '');
        const cleanRoomName = roomName.replace(/[^a-zA-Z0-9]/g, '');
        
        // 使用簡單的哈希函數生成固定ID
        const hash = this.simpleHash(cleanUserName + cleanRoomName);
        return `${cleanUserName}_${cleanRoomName}_${hash}`;
    }

    /**
     * 🔥 簡單哈希函數
     * @param {string} str - 要哈希的字符串
     * @returns {string} 哈希值
     */
    simpleHash(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // 轉換為32位整數
        }
        return Math.abs(hash).toString(36).substr(0, 8);
    }

    /**
     * 註冊用戶變更回調
     * @param {Function} callback - 回調函數
     */
    onUserChange(callback) {
        if (typeof callback === 'function') {
            this.userChangeCallbacks.push(callback);
        }
    }

    /**
     * 移除用戶變更回調
     * @param {Function} callback - 要移除的回調函數
     */
    removeUserChangeCallback(callback) {
        const index = this.userChangeCallbacks.indexOf(callback);
        if (index > -1) {
            this.userChangeCallbacks.splice(index, 1);
        }
    }

    /**
     * 通知用戶變更
     * @param {Object} newUser - 新用戶
     * @param {Object} oldUser - 舊用戶
     */
    notifyUserChange(newUser, oldUser) {
        this.userChangeCallbacks.forEach(callback => {
            try {
                callback(newUser, oldUser);
            } catch (error) {
                console.error('用戶變更回調執行失敗:', error);
            }
        });
    }

    /**
     * 保存用戶到本地存儲
     */
    saveUserToStorage() {
        if (this.currentUser) {
            try {
                localStorage.setItem('pythonlearn_current_user', JSON.stringify(this.currentUser));
            } catch (error) {
                console.error('保存用戶到本地存儲失敗:', error);
            }
        }
    }

    /**
     * 從本地存儲恢復用戶
     */
    restoreUserFromStorage() {
        try {
            const savedUser = localStorage.getItem('pythonlearn_current_user');
            if (savedUser) {
                const userData = JSON.parse(savedUser);
                
                // 檢查用戶數據是否過期（超過24小時）
                const now = Date.now();
                const userAge = now - (userData.joinTime || 0);
                const maxAge = 24 * 60 * 60 * 1000; // 24小時
                
                if (userAge < maxAge) {
                    this.currentUser = userData;
                    this.isInitialized = true;
                    console.log('👤 從本地存儲恢復用戶:', this.currentUser);
                } else {
                    console.log('👤 本地用戶數據過期，已清除');
                    localStorage.removeItem('pythonlearn_current_user');
                }
            }
        } catch (error) {
            console.error('從本地存儲恢復用戶失敗:', error);
            localStorage.removeItem('pythonlearn_current_user');
        }
    }

    /**
     * 清除當前用戶
     */
    clearCurrentUser() {
        const previousUser = this.currentUser;
        this.currentUser = null;
        this.isInitialized = false;
        
        localStorage.removeItem('pythonlearn_current_user');
        this.notifyUserChange(null, previousUser);
        
        console.log('👤 當前用戶已清除');
    }

    /**
     * 驗證用戶數據
     * @param {Object} userData - 用戶數據
     * @returns {Object} 驗證結果
     */
    validateUserData(userData) {
        const errors = [];
        
        if (!userData) {
            errors.push('用戶數據不能為空');
            return { isValid: false, errors };
        }
        
        if (!userData.name || typeof userData.name !== 'string' || userData.name.trim().length === 0) {
            errors.push('用戶名稱不能為空');
        }
        
        if (userData.name && userData.name.length > 30) {
            errors.push('用戶名稱不能超過30個字符');
        }
        
        if (!userData.room || typeof userData.room !== 'string' || userData.room.trim().length === 0) {
            errors.push('房間名稱不能為空');
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    /**
     * 檢查是否已初始化
     * @returns {boolean} 是否已初始化
     */
    isReady() {
        return this.isInitialized && this.currentUser !== null;
    }

    /**
     * 獲取用戶統計信息
     * @returns {Object} 統計信息
     */
    getStats() {
        return {
            currentUser: this.currentUser,
            onlineUsersCount: this.onlineUsers.size,
            isInitialized: this.isInitialized,
            callbacks: this.userChangeCallbacks.length
        };
    }

    /**
     * 🔥 清理用戶管理器狀態（頁面卸載時調用）
     */
    cleanup() {
        console.log('🧹 用戶管理器正在清理...');
        
        // 清除所有回調
        this.userChangeCallbacks = [];
        
        // 清除在線用戶
        this.onlineUsers.clear();
        
        // 保存當前用戶到本地存儲（為下次恢復做準備）
        this.saveUserToStorage();
        
        console.log('✅ 用戶管理器清理完成');
    }

    /**
     * 初始化用戶登入記錄功能
     */
    initializeLoginHistory() {
        // 頁面載入時更新下拉選單
        this.updateUsernameDropdown();
        
        // 監聽輸入框變化，實時過濾選項
        const usernameInput = document.getElementById('username');
        if (usernameInput) {
            usernameInput.addEventListener('input', () => {
                this.filterUsernameDropdown();
            });
            
            // 點擊輸入框時顯示下拉選單
            usernameInput.addEventListener('focus', () => {
                this.showUsernameDropdown();
            });
        }
    }
    
    /**
     * 從本地存儲載入登入歷史
     */
    loadLoginHistory() {
        try {
            const history = localStorage.getItem('pythonlearn_login_history');
            return history ? JSON.parse(history) : [];
        } catch (error) {
            console.warn('載入登入歷史時出錯:', error);
            return [];
        }
    }
    
    /**
     * 保存登入歷史到本地存儲
     */
    saveLoginHistory() {
        try {
            localStorage.setItem('pythonlearn_login_history', JSON.stringify(this.loginHistory));
            console.log('✅ 登入歷史已保存:', this.loginHistory);
        } catch (error) {
            console.error('保存登入歷史時出錯:', error);
        }
    }
    
    /**
     * 添加用戶到登入記錄
     */
    addToLoginHistory(username) {
        if (!username || username.trim() === '') return;
        
        const trimmedUsername = username.trim();
        
        // 移除重複的用戶名稱（如果存在）
        this.loginHistory = this.loginHistory.filter(user => user.name !== trimmedUsername);
        
        // 添加到列表開頭
        this.loginHistory.unshift({
            name: trimmedUsername,
            lastLogin: new Date().toISOString(),
            timestamp: Date.now()
        });
        
        // 限制記錄數量
        if (this.loginHistory.length > this.maxHistorySize) {
            this.loginHistory = this.loginHistory.slice(0, this.maxHistorySize);
        }
        
        // 保存到本地存儲
        this.saveLoginHistory();
        
        // 更新下拉選單
        this.updateUsernameDropdown();
        
        console.log(`✅ 用戶 "${trimmedUsername}" 已添加到登入記錄`);
    }
    
    /**
     * 更新用戶名稱下拉選單
     */
    updateUsernameDropdown() {
        const dropdown = document.getElementById('usernameDropdown');
        const noUsersMessage = document.getElementById('noUsersMessage');
        
        if (!dropdown) return;
        
        // 清除現有項目（保留標題和分隔線）
        const existingItems = dropdown.querySelectorAll('.username-history-item');
        existingItems.forEach(item => item.remove());
        
        if (this.loginHistory.length === 0) {
            // 顯示無記錄消息
            if (noUsersMessage) {
                noUsersMessage.style.display = 'block';
            }
        } else {
            // 隱藏無記錄消息
            if (noUsersMessage) {
                noUsersMessage.style.display = 'none';
            }
            
            // 添加用戶歷史項目
            this.loginHistory.forEach((user, index) => {
                const li = document.createElement('li');
                li.className = 'username-history-item';
                
                const a = document.createElement('a');
                a.className = 'dropdown-item d-flex justify-content-between align-items-center';
                a.href = '#';
                a.style.cursor = 'pointer';
                
                // 用戶名稱
                const nameSpan = document.createElement('span');
                nameSpan.textContent = user.name;
                
                // 時間標記
                const timeSpan = document.createElement('small');
                timeSpan.className = 'text-muted';
                timeSpan.textContent = this.formatLoginTime(user.lastLogin);
                
                a.appendChild(nameSpan);
                a.appendChild(timeSpan);
                
                // 點擊事件
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.selectUsername(user.name);
                });
                
                li.appendChild(a);
                dropdown.appendChild(li);
            });
            
            // 添加清除記錄選項
            const clearLi = document.createElement('li');
            clearLi.className = 'username-history-item';
            clearLi.innerHTML = `
                <hr class="dropdown-divider">
                <a class="dropdown-item text-danger text-center" href="#" onclick="window.UserManager.clearLoginHistory()">
                    <i class="fas fa-trash"></i> 清除所有記錄
                </a>
            `;
            dropdown.appendChild(clearLi);
        }
    }
    
    /**
     * 選擇用戶名稱
     */
    selectUsername(username) {
        const usernameInput = document.getElementById('username');
        if (usernameInput) {
            usernameInput.value = username;
            // 隱藏下拉選單
            const dropdown = new bootstrap.Dropdown(usernameInput);
            dropdown.hide();
        }
    }
    
    /**
     * 顯示用戶名稱下拉選單
     */
    showUsernameDropdown() {
        const usernameInput = document.getElementById('username');
        if (usernameInput && this.loginHistory.length > 0) {
            const dropdown = new bootstrap.Dropdown(usernameInput);
            dropdown.show();
        }
    }
    
    /**
     * 過濾用戶名稱下拉選單
     */
    filterUsernameDropdown() {
        const usernameInput = document.getElementById('username');
        const filterText = usernameInput ? usernameInput.value.toLowerCase() : '';
        
        const historyItems = document.querySelectorAll('.username-history-item a');
        let hasVisibleItems = false;
        
        historyItems.forEach(item => {
            const parentLi = item.closest('li');
            if (item.textContent.toLowerCase().includes(filterText)) {
                parentLi.style.display = 'block';
                hasVisibleItems = true;
            } else {
                parentLi.style.display = 'none';
            }
        });
        
        // 如果有匹配項目且輸入框有內容，顯示下拉選單
        if (hasVisibleItems && filterText.length > 0) {
            this.showUsernameDropdown();
        }
    }
    
    /**
     * 清除所有登入記錄
     */
    clearLoginHistory() {
        if (confirm('確定要清除所有登入記錄嗎？')) {
            this.loginHistory = [];
            this.saveLoginHistory();
            this.updateUsernameDropdown();
            console.log('✅ 已清除所有登入記錄');
        }
    }
    
    /**
     * 格式化登入時間
     */
    formatLoginTime(isoString) {
        try {
            const date = new Date(isoString);
            const now = new Date();
            const diffMs = now - date;
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) {
                return '今天';
            } else if (diffDays === 1) {
                return '昨天';
            } else if (diffDays < 7) {
                return `${diffDays}天前`;
            } else {
                return date.toLocaleDateString('zh-TW');
            }
        } catch (error) {
            return '';
        }
    }

    /**
     * 🔥 調試方法：檢查登入記錄狀態
     */
    checkLoginHistoryStatus() {
        return {
            historyCount: this.loginHistory.length,
            maxSize: this.maxHistorySize,
            history: this.loginHistory,
            localStorage: localStorage.getItem('pythonlearn_login_history'),
            domElements: {
                usernameInput: !!document.getElementById('username'),
                usernameDropdown: !!document.getElementById('usernameDropdown'),
                noUsersMessage: !!document.getElementById('noUsersMessage')
            }
        };
    }

    async joinRoom(roomName, userName) {
        if (!roomName || !userName) {
            alert('請輸入房間名稱和用戶名稱');
            return false;
        }

        try {
            this.currentUser = userName.trim();
            this.connectionStartTime = Date.now();
            
            // 添加到登入記錄
            this.addToLoginHistory(this.currentUser);
            
            // 設置用戶數據
            const userData = {
                name: this.currentUser,
                room: roomName.trim(),
                id: this.generateUserId(this.currentUser, roomName),
                joinTime: new Date().toISOString()
            };
            
            // 保存用戶數據
            this.setCurrentUser(userData);
            this.saveUserToStorage();
            
            console.log(`✅ 用戶 "${this.currentUser}" 準備加入房間 "${roomName}"`);
            return true;
            
        } catch (error) {
            console.error('加入房間時出錯:', error);
            return false;
        }
    }
}

// 創建全域用戶管理器實例
window.UserManager = new UserManager();

// 確保DOM載入後初始化登入記錄功能
function initializeUserManagerHistory() {
    // 🔥 重試機制確保DOM元素存在
    let retryCount = 0;
    const maxRetries = 10;
    
    function tryInitialize() {
        const usernameInput = document.getElementById('username');
        const usernameDropdown = document.getElementById('usernameDropdown');
        
        if (usernameInput && usernameDropdown) {
            window.UserManager.initializeLoginHistory();
            console.log('📝 登入記錄功能已初始化');
        } else if (retryCount < maxRetries) {
            retryCount++;
            console.log(`⏳ 等待DOM元素準備中... (${retryCount}/${maxRetries})`);
            setTimeout(tryInitialize, 200);
        } else {
            console.warn('⚠️ 無法找到登入記錄所需的DOM元素');
        }
    }
    
    tryInitialize();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeUserManagerHistory);
} else {
    // DOM已經載入完成
    setTimeout(initializeUserManagerHistory, 100);
}

// 與其他模組集成
window.UserManager.onUserChange((newUser, oldUser) => {
    // 通知 SaveLoadManager
    if (window.SaveLoadManager && newUser) {
        window.SaveLoadManager.init(newUser.id, newUser.room);
    }
    
    // 通知 HTTP 輪詢管理器
    if (window.HttpPollingManager) {
        window.HttpPollingManager.currentUser = newUser ? newUser.name : null;
        window.HttpPollingManager.currentRoom = newUser ? newUser.room : null;
    }
    
    // 通知 AI 助手
    if (window.AIAssistant) {
        window.AIAssistant.currentUser = newUser ? newUser.name : null;
    }
    
    console.log('🔄 用戶變更已通知所有模組');
});

console.log('👤 用戶管理器模組載入完成');

// 🔥 全域調試函數
window.checkLoginHistory = function() {
    if (window.UserManager) {
        const status = window.UserManager.checkLoginHistoryStatus();
        console.log('📊 登入記錄狀態:', status);
        return status;
    } else {
        console.error('❌ UserManager 未初始化');
        return null;
    }
};

// 🔥 全域測試函數
window.testAddLoginHistory = function(username = '測試用戶') {
    if (window.UserManager) {
        window.UserManager.addToLoginHistory(username);
        console.log('✅ 已添加測試用戶到登入記錄:', username);
        return window.UserManager.checkLoginHistoryStatus();
    } else {
        console.error('❌ UserManager 未初始化');
        return null;
    }
}; 