/**
 * HTTP 輪詢連接和通訊管理
 * 純 HTTP 輪詢實現，適用於 Zeabur 單端口環境
 * 
 * 📅 重構日期: 2025-01-09
 * 🎯 目標: 提供穩定的實時協作功能，無需 WebSocket
 */

class HttpPollingManager {
    constructor() {
        this.isConnectedState = false;
        this.isPolling = false;
        this.pollInterval = null;
        this.currentRoom = null;
        this.currentUser = null;
        this.messageQueue = [];
        this.pollDelay = 500; // 500ms 輪詢間隔
        this.maxReconnectAttempts = 5;
        this.reconnectAttempts = 0;
        this.reconnectDelay = 1000;
        this.lastPollTimestamp = 0; // 🔥 使用毫秒級時間戳
        this.lastUserListHash = '';
        this.lastOnlineUsers = []; // 🎯 保存在線用戶列表，用於衝突檢測
        
        console.log('🔧 HTTP輪詢管理器已創建');
    }

    /**
     * 檢查連接狀態
     */
    isConnected() {
        return this.isConnectedState && this.isPolling;
    }

    /**
     * 建立 HTTP 輪詢連接
     */
    async connect(roomName, userName) {
        console.log(`🔌 連接到房間: ${roomName}, 用戶: ${userName}`);
        
        this.currentRoom = roomName;
        this.currentUser = userName;

        try {
            // 首先發送加入房間請求
            const joinResult = await this.sendHttpRequest({
                action: 'join',
                room_id: roomName,
                user_id: userName,
                user_name: userName
            });

            if (joinResult.success) {
                console.log('✅ 成功加入房間');
                this.isConnectedState = true;
                this.reconnectAttempts = 0;
                
                // 🔄 處理房間最新代碼同步 - 無條件載入房間即時代碼
                if (joinResult.room_code !== undefined && window.editor) {
                    // 無條件載入房間即時代碼，確保所有用戶看到相同內容
                    window.editor.setValue(joinResult.room_code);
                    console.log('📝 已載入房間即時代碼 (長度:', joinResult.room_code.length, '字符)');
                    
                    if (window.UI) {
                        window.UI.showToast('代碼同步', '已載入房間即時代碼', 'info');
                    }
                } else if (window.editor) {
                    // 如果房間沒有代碼，清空編輯器
                    window.editor.setValue('');
                    console.log('📝 房間無代碼，編輯器已清空');
                }
                
                // 🎯 更新用戶管理器
                if (window.UserManager) {
                    window.UserManager.setCurrentUser({
                        name: userName,
                        room: roomName,
                        id: joinResult.user_id
                    });
                    
                    if (joinResult.users) {
                        window.UserManager.updateOnlineUsers(joinResult.users);
                    }
                }
                
                // 🗨️ 載入聊天歷史
                if (joinResult.chat_history && window.Chat) {
                    console.log('💬 載入聊天歷史:', joinResult.chat_history.length, '條消息');
                    window.Chat.loadHistory(joinResult.chat_history);
                }
                
                // 開始輪詢
                this.startPolling();
                
                // 處理消息隊列
                this.processMessageQueue();
                
                // 觸發連接成功事件
                if (window.onWebSocketConnected) {
                    window.onWebSocketConnected();
                }

                // 顯示連接狀態
                if (window.UI) {
                    const userCount = joinResult.online_count || 1;
                    window.UI.showToast('連接成功', `已加入房間 ${roomName} (${userCount} 人在線)`, 'success');
                }
                
                return joinResult;
            } else {
                throw new Error(joinResult.error || '加入房間失敗');
            }
            
        } catch (error) {
            console.error('❌ 連接失敗:', error);
            this.handleConnectionError(error);
            throw error;
        }
    }

    /**
     * 開始 HTTP 輪詢
     */
    startPolling() {
        if (this.isPolling) {
            return;
        }

        console.log('🔄 開始 HTTP 輪詢');
        this.isPolling = true;
        this.lastPollTimestamp = Date.now();

        this.pollInterval = setInterval(() => {
            this.performPoll();
        }, this.pollDelay);
    }

    /**
     * 執行輪詢請求
     */
    async performPoll() {
        if (!this.isConnectedState) {
            return;
        }

        try {
            // 🔥 使用毫秒級時間戳進行輪詢
            const response = await fetch(`/api.php?action=poll&room_id=${this.currentRoom}&user_id=${this.currentUser}&timestamp=${this.lastPollTimestamp}`);

            if (!response.ok) {
                throw new Error(`HTTP錯誤: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                // 🔥 使用服務器提供的毫秒級時間戳
                this.lastPollTimestamp = data.server_timestamp_ms || (data.timestamp * 1000);
                
                // 🔥 添加調試信息
                if (data.sync_data_count > 0) {
                    console.log(`📊 輪詢收到 ${data.sync_data_count} 個代碼變更，時間戳更新為: ${this.lastPollTimestamp}`);
                }
                
                this.handlePollingResponse(data);
                this.reconnectAttempts = 0; // 重置重連計數
            } else {
                console.warn('⚠️ 輪詢回應錯誤:', data.error);
            }

        } catch (error) {
            console.error('❌ 輪詢請求失敗:', error);
            this.handleConnectionError(error);
        }
    }

    /**
     * 處理輪詢回應
     */
    handlePollingResponse(data) {
        // 更新用戶列表
        if (data.users) {
            this.updateUserList(data.users);
        }

        // 處理代碼變更
        if (data.code_changes && data.code_changes.length > 0) {
            data.code_changes.forEach(change => {
                this.handleCodeChange(change);
            });
        }

        // 處理聊天消息（如果有）
        if (data.messages && data.messages.length > 0) {
            data.messages.forEach(message => {
                this.handleChatMessage(message);
            });
        }

        // 🔥 處理專用聊天消息字段
        if (data.chat_messages && data.chat_messages.length > 0) {
            console.log(`💬 輪詢收到 ${data.chat_messages.length} 條聊天消息`);
            data.chat_messages.forEach(message => {
                this.handleChatMessage(message);
            });
        }

        // 更新房間信息
        if (data.room_info) {
            this.updateRoomInfo(data.room_info);
        }
    }

    /**
     * 發送消息
     */
    async sendMessage(message) {
        if (!this.isConnectedState) {
            console.log('📝 連接未就緒，消息已加入隊列');
            this.messageQueue.push(message);
            return;
        }

        try {
            await this.sendHttpRequest(message);
            console.log('📤 發送消息成功:', message.type);
        } catch (error) {
            console.error('❌ 發送消息失敗:', error);
            this.messageQueue.push(message);
        }
    }

    /**
     * 發送 HTTP 請求
     */
    async sendHttpRequest(message) {
        const formData = new FormData();
        
        // 根據消息類型設置不同的參數
        switch (message.type) {
            case 'join_room':
                formData.append('action', 'join');
                formData.append('room_id', message.room_id);
                formData.append('user_id', message.user_id);
                formData.append('user_name', message.username || message.user_id);
                break;
                
            case 'code_change':
            case 'code_sync':
                formData.append('action', 'sync_code');
                formData.append('room_id', this.currentRoom);
                formData.append('user_id', this.currentUser);
                formData.append('username', message.username || this.currentUser);
                formData.append('user_name', message.username || this.currentUser);
                formData.append('code', message.code || '');
                formData.append('change_type', message.change_type || 'update');
                formData.append('user_info', JSON.stringify(message.user_info || {}));
                break;
                
            case 'chat_message':
                formData.append('action', 'send_chat');
                formData.append('room_id', this.currentRoom);
                formData.append('user_id', this.currentUser);
                formData.append('message', message.message);
                break;
                
            default:
                // 通用消息處理
                formData.append('action', message.type);
                formData.append('room_id', this.currentRoom);
                formData.append('user_id', this.currentUser);
                
                Object.keys(message).forEach(key => {
                    if (key !== 'type' && message[key] !== undefined) {
                        formData.append(key, typeof message[key] === 'object' ? JSON.stringify(message[key]) : message[key]);
                    }
                });
                break;
        }

        const response = await fetch('/api.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP錯誤: ${response.status}`);
        }

        return await response.json();
    }

    /**
     * 處理連接錯誤
     */
    handleConnectionError(error) {
        this.reconnectAttempts++;
        
        if (this.reconnectAttempts <= this.maxReconnectAttempts) {
            const delay = Math.min(this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1), 30000);
            console.log(`🔄 重連中 (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
            
            setTimeout(() => {
                this.connect(this.currentRoom, this.currentUser);
            }, delay);
        } else {
            console.log('❌ 重連次數已達上限，停止重連');
            this.disconnect();
            
            if (window.UI) {
                window.UI.showToast('連接失敗', '無法連接到服務器，請檢查網路連接', 'error');
            }
        }
    }

    /**
     * 處理代碼變更
     */
    handleCodeChange(change) {
        console.log('📨 收到代碼變更:', change);
        
        // 🔥 過濾掉自己發送的變更，避免循環
        if (change.user_id === this.currentUser || change.username === this.currentUser) {
            console.log('🔄 跳過自己發送的代碼變更');
            return;
        }
        
        console.log(`🔄 處理來自 ${change.username || change.user_id} 的代碼變更`);
        
        if (window.Editor && typeof window.Editor.handleRemoteCodeChange === 'function') {
            window.Editor.handleRemoteCodeChange(change);
        } else {
            console.warn('⚠️ Editor.handleRemoteCodeChange 方法不可用');
        }
    }

    /**
     * 處理聊天消息
     */
    handleChatMessage(message) {
        console.log('📨 收到聊天消息:', message);
        
        if (window.Chat && typeof window.Chat.displayMessage === 'function') {
            window.Chat.displayMessage(message);
        }
    }

    /**
     * 更新用戶列表
     */
    updateUserList(users) {
        const userListHash = this.getUserListHash(users);
        
        if (userListHash !== this.lastUserListHash) {
            console.log('👥 用戶列表已更新:', users);
            this.lastUserListHash = userListHash;
            this.lastOnlineUsers = users || []; // 🎯 保存在線用戶列表
            
            // 🎯 更新用戶管理器
            if (window.UserManager && window.UserManager.updateOnlineUsers) {
                window.UserManager.updateOnlineUsers(users);
            }
            
            // 觸發用戶列表更新事件
            if (window.onUserListUpdate) {
                window.onUserListUpdate(users);
            }
            
            // 更新UI顯示
            if (window.UI && window.UI.updateOnlineUsers) {
                window.UI.updateOnlineUsers(users);
            }
        }
    }

    /**
     * 計算用戶列表的雜湊值
     */
    getUserListHash(users) {
        if (!users || !Array.isArray(users)) {
            return 'empty';
        }
        
        return users
            .map(user => `${user.id}-${user.name}`)
            .sort()
            .join('|');
    }

    /**
     * 更新房間信息
     */
    updateRoomInfo(roomInfo) {
        if (window.UI && typeof window.UI.updateRoomInfo === 'function') {
            window.UI.updateRoomInfo(roomInfo);
        }
    }

    /**
     * 處理消息隊列
     */
    processMessageQueue() {
        while (this.messageQueue.length > 0 && this.isConnectedState) {
            const message = this.messageQueue.shift();
            this.sendMessage(message);
        }
    }

    /**
     * 停止輪詢
     */
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        this.isPolling = false;
        console.log('⏹️ HTTP 輪詢已停止');
    }

    /**
     * 斷開連接
     */
    async disconnect() {
        console.log('🔌 斷開連接');
        
        this.isConnectedState = false;
        this.stopPolling();

        if (this.currentRoom && this.currentUser) {
            try {
                await this.sendHttpRequest({
                    action: 'leave',
                    room_id: this.currentRoom,
                    user_id: this.currentUser
                });
            } catch (error) {
                console.warn('⚠️ 離開房間請求失敗:', error);
            }
        }

        if (window.onWebSocketDisconnected) {
            window.onWebSocketDisconnected();
        }
    }

    /**
     * 離開房間
     */
    leaveRoom() {
        this.disconnect();
    }

    /**
     * 初始化
     */
    initialize() {
        console.log('🔧 HTTP輪詢管理器初始化中...');
        // 可以在這裡做一些初始化工作
        console.log('✅ HTTP輪詢管理器初始化完成');
    }

    /**
     * 保存代碼
     */
    saveCode(code) {
        if (!this.isConnectedState) {
            console.warn('⚠️ 未連接，無法保存代碼');
            return;
        }
        
        this.sendMessage({
            type: 'code_change',
            code: code,
            change_type: 'save',
            timestamp: Date.now()
        });
    }

    /**
     * 載入代碼
     */
    loadCode() {
        if (!this.isConnectedState) {
            console.warn('⚠️ 未連接，無法載入代碼');
            return;
        }
        
        this.sendMessage({
            type: 'load_code'
        });
    }

    /**
     * 執行代碼
     */
    runCode(code) {
        if (!this.isConnectedState) {
            console.warn('⚠️ 未連接，無法執行代碼');
            return;
        }
        
        this.sendMessage({
            type: 'run_code',
            code: code
        });
    }

    /**
     * 獲取歷史記錄
     */
    getHistory() {
        if (!this.isConnectedState) {
            console.warn('⚠️ 未連接，無法獲取歷史記錄');
            return;
        }
        
        this.sendMessage({
            type: 'get_history'
        });
    }
}

// 全局 HTTP 輪詢管理器實例（保持 wsManager 名稱以兼容現有代碼）
const wsManager = new HttpPollingManager();

// 為了向後兼容，也創建一個新名稱的實例
const httpPollingManager = wsManager;

console.log('✅ HTTP輪詢管理器已載入'); 