// WebSocket 連接和通訊管理
class WebSocketManager {
    constructor() {
        this.ws = null;
        this.currentUser = null;
        this.currentRoom = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.messageQueue = [];
        this.heartbeatInterval = null;
        this.lastHeartbeat = 0;
    }

    // 檢查連接狀態
    isConnected() {
        return this.ws && this.ws.readyState === WebSocket.OPEN;
    }

    // 建立 WebSocket 連接
    connect(roomName, userName) {
        this.currentRoom = roomName;
        this.currentUser = userName;
        
        let wsUrl;
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const hostname = window.location.hostname;
        const port = window.location.port;

        // 根據環境決定 WebSocket URL
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            // 本地開發環境 - 直接連接到 WebSocket 服務器
            wsUrl = `ws://${hostname}:8081`;
            console.log('🏠 本地開發環境 (直連)，WebSocket 連接: ' + wsUrl);
        } else {
            // 生產環境 - Zeabur 使用 Caddy 反向代理
            wsUrl = `${protocol}//${hostname}/ws`;
            console.log('☁️ 生產環境 (Caddy 代理)，WebSocket 連接: ' + wsUrl);
        }
        
        console.log(`🔌 嘗試連接到 WebSocket: ${wsUrl}`);
        console.log(`👤 用戶: ${userName}, 🏠 房間: ${roomName}`);
        
        try {
        this.ws = new WebSocket(wsUrl);

        this.ws.onopen = () => {
            console.log('✅ WebSocket 連接成功到服務器!');
            console.log(`📍 連接地址: ${wsUrl}`);
            this.reconnectAttempts = 0;
                
                // 啟動心跳
                this.startHeartbeat();
                
                // 發送加入房間請求
            this.sendMessage({
                type: 'join_room',
                room_id: roomName,
                user_id: userName,
                username: userName
            });

                // 處理消息隊列
            this.processMessageQueue();
                
                // 觸發連接成功事件
                if (window.onWebSocketConnected) {
                    window.onWebSocketConnected();
                }
        };

        this.ws.onmessage = (event) => {
            try {
                const message = JSON.parse(event.data);
                this.handleMessage(message);
            } catch (error) {
                    console.error('❌ 解析消息失敗:', error, event.data);
            }
        };

        this.ws.onclose = (event) => {
                console.log(`🔌 WebSocket 連接關閉: ${event.code} - ${event.reason}`);
                this.stopHeartbeat();
                
                // 嘗試重連
                if (this.reconnectAttempts < this.maxReconnectAttempts && event.code !== 1000) {
                    this.reconnectAttempts++;
                    console.log(`🔄 嘗試重連 (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
                    setTimeout(() => {
                        this.connect(roomName, userName);
                    }, this.reconnectDelay * this.reconnectAttempts);
                } else {
                    console.log('❌ 重連次數已達上限或正常關閉');
                    if (window.onWebSocketDisconnected) {
                        window.onWebSocketDisconnected();
                    }
                }
            };

            this.ws.onerror = (error) => {
                console.error('❌ WebSocket 錯誤:', error);
            };

        } catch (error) {
            console.error('❌ 建立 WebSocket 連接失敗:', error);
        }
    }

    // 發送消息
    sendMessage(message) {
        if (this.isConnected()) {
            try {
                this.ws.send(JSON.stringify(message));
                console.log('📤 發送消息:', message.type);
            } catch (error) {
                console.error('❌ 發送消息失敗:', error);
                // 添加到消息隊列以便重連後發送
                this.messageQueue.push(message);
            }
        } else {
            console.log('📝 WebSocket 未連接，消息已加入隊列');
            this.messageQueue.push(message);
        }
    }

    // 處理收到的消息
    handleMessage(message) {
        console.log('📨 收到消息:', message.type);
        console.log('📨 完整消息內容:', JSON.stringify(message, null, 2));
        
        switch (message.type) {
            case 'room_joined':
                this.handleRoomJoined(message);
                break;
            case 'join_room_error':
                this.handleJoinRoomError(message);
                break;
            case 'user_joined':
            case 'user_reconnected':
                this.handleUserJoined(message);
                break;
            case 'user_left':
                this.handleUserLeft(message);
                break;
            case 'code_change':
            case 'code_sync':
                this.handleCodeChange(message);
                break;
            case 'save_success':
            case 'code_saved':  // 向後兼容
                this.handleCodeSaved(message);
                break;
            case 'code_loaded':
                this.handleCodeLoaded(message);
                break;
            case 'cursor_changed':
                this.handleCursorChange(message);
                break;
            case 'chat_message':
                this.handleChatMessage(message);
                break;
            case 'ai_response':
                this.handleAIResponse(message);
                break;
                            case 'code_execution_result':
                    this.handleCodeExecutionResult(message);
                    break;
                    
                case 'history_loaded':
                    this.handleHistoryLoaded(message);
                    break;
                case 'history_data':
                    this.handleHistoryData(message);
                    break;
            case 'conflict_notification':
                this.handleConflictNotification(message);
                break;
            case 'user_list_update':
                console.log('👥 收到用戶列表更新:', message);
                this.updateUserList(message.users);
                if (message.users && message.total_users !== undefined) {
                    console.log(`👥 當前房間用戶數: ${message.total_users}`);
                }
                break;
            case 'pong':
                this.lastHeartbeat = Date.now();
                break;
            case 'error':
                console.error('❌ 收到服務器錯誤消息:', message.error, message.details);
                if (window.UI) {
                    window.UI.showToast('服務器錯誤', message.error || '發生未知錯誤', 'error');
                }
                break;
            default:
                console.warn('⚠️ 未知消息類型:', message.type);
        }
    }

    // 處理房間加入成功
    handleRoomJoined(message) {
        console.log(`✅ 成功加入房間: ${message.room_id}`);
        console.log('📥 房間數據:', message);
        console.log('   - 代碼長度:', (message.current_code || '').length);
        console.log('   - 用戶數量:', (message.users || []).length);
        
        // 更新編輯器內容 - 使用正確的屬性名
        if (window.Editor && message.current_code !== undefined) {
            console.log('🔄 設置編輯器代碼...');
            window.Editor.setCode(message.current_code);
            console.log('✅ 編輯器代碼已設置');
        } else {
            console.error('❌ 編輯器未找到或房間代碼為空');
            console.log('   - Editor 存在:', !!window.Editor);
            console.log('   - 代碼內容:', message.current_code);
        }
        
        // 更新用戶列表
        this.updateUserList(message.users);
        
        // 更新房間信息顯示
        this.updateRoomInfo(message.room_id, message.users);
        
        // 初始化 SaveLoadManager
        if (window.SaveLoadManager) {
            window.SaveLoadManager.init(this.currentUser, message.room_id);
        }
        
        // 自動載入歷史記錄到下拉選單
        this.getHistory();
        
        // 顯示加入提示
        if (window.UI) {
            if (message.isReconnect) {
                window.UI.showToast('重連成功', '已重新連接到房間', 'success');
            } else {
                window.UI.showToast('加入成功', `已加入房間 "${message.room_id}"`, 'success');
            }
        }
    }

    // 處理加入房間錯誤
    handleJoinRoomError(message) {
        console.error('❌ 加入房間失敗:', message.message);
        
        if (message.error === 'name_duplicate') {
            // 用戶名稱重複
            if (window.UI) {
                window.UI.showToast('用戶名稱重複', message.message, 'error');
            }
            
            // 提示用戶修改用戶名稱
            const newUserName = prompt('您的用戶名稱已被使用，請輸入新的用戶名稱：', this.currentUser + '_' + Math.floor(Math.random() * 100));
            if (newUserName && newUserName.trim()) {
                this.currentUser = newUserName.trim();
                // 重新嘗試加入
                this.sendMessage({
                    type: 'join_room',
                    room_id: this.currentRoom,
                    user_id: this.currentUser,
                    username: this.currentUser
                });
            }
        } else {
            // 其他錯誤
            if (window.UI) {
                window.UI.showToast('加入失敗', message.message, 'error');
            }
        }
    }

    // 處理用戶加入
    handleUserJoined(message) {
        console.log(`👤 用戶加入: ${message.username}`);
        
        // 更新用戶列表
        if (message.users) {
            this.updateUserList(message.users);
        }
        
        // 顯示通知
        if (window.UI && message.username !== this.currentUser) {
            window.UI.showToast('新用戶加入', `${message.username} 加入了房間`, 'info');
        }
    }

    // 處理用戶離開
    handleUserLeft(message) {
        console.log(`👋 用戶離開: ${message.user_id}`);
        
        // 更新用戶列表
        if (message.users) {
            this.updateUserList(message.users);
        }
        
        // 顯示通知
        if (window.UI && message.user_id !== this.currentUser) {
            window.UI.showToast('用戶離開', `用戶離開了房間`, 'info');
        }
    }

    // 處理代碼變更
    handleCodeChange(message) {
        console.log('📨 收到代碼變更消息:', message);
        console.log('   - 來源用戶:', message.username);
        console.log('   - 代碼長度:', (message.code || '').length);
        
        // 確保編輯器存在並調用處理方法
        if (window.Editor && typeof window.Editor.handleRemoteCodeChange === 'function') {
            console.log('🔄 調用編輯器處理遠程代碼變更...');
            window.Editor.handleRemoteCodeChange(message);
        } else {
            console.error('❌ 編輯器未找到或方法不存在');
            console.log('   - Editor 存在:', !!window.Editor);
            console.log('   - handleRemoteCodeChange 方法存在:', !!(window.Editor && window.Editor.handleRemoteCodeChange));
            
            // 降級處理：直接更新代碼
            if (window.Editor && typeof window.Editor.setCode === 'function') {
                console.log('🔄 降級處理：直接設置代碼');
                window.Editor.setCode(message.code, message.version);
            }
        }
    }

    // 處理代碼保存確認
    handleCodeSaved(message) {
        console.log('✅ 代碼保存成功:', message);
        
        if (window.Editor && typeof window.Editor.handleSaveSuccess === 'function') {
            window.Editor.handleSaveSuccess(message);
        }
        
        if (window.UI) {
            window.UI.showToast('保存成功', '代碼已保存到服務器', 'success');
        }
    }

    // 處理代碼載入
    handleCodeLoaded(message) {
        console.log('📥 代碼載入成功:', message);
        
        if (window.Editor && message.code !== undefined) {
            console.log('🔄 設置載入的代碼...');
            window.Editor.setCode(message.code);
            console.log('✅ 代碼已設置到編輯器');
        }
        
        if (window.UI) {
            window.UI.showToast('載入成功', '代碼已從服務器載入', 'success');
        }
    }

    // 處理歷史數據
    handleHistoryData(message) {
        console.log('📜 收到歷史數據:', message);
        
        // 將歷史數據傳遞給 SaveLoadManager
        if (window.SaveLoadManager && typeof window.SaveLoadManager.handleMessage === 'function') {
            console.log('🔄 傳遞歷史數據給 SaveLoadManager...');
            window.SaveLoadManager.handleMessage(message);
        } else {
            console.warn('⚠️ SaveLoadManager 不可用，使用降級處理');
            // 降級處理：直接更新下拉選單
            if (window.SaveLoadManager && typeof window.SaveLoadManager.updateHistoryDropdown === 'function') {
                window.SaveLoadManager.updateHistoryDropdown(message.history || []);
            }
        }
    }

    // 處理游標變更
    handleCursorChange(message) {
        if (window.editorManager) {
            window.editorManager.handleRemoteCursorChange(message);
        }
    }

    // 處理聊天消息
    handleChatMessage(message) {
                    if (window.Chat) {
                window.Chat.addMessage(message.userName || '用戶', message.message || message.content || '', message.isSystem || false, message.isTeacher || false);
            }
    }

    // 處理AI回應
    handleAIResponse(message) {
        console.log('🤖 收到 AI 回應:', message);
        
        // 優先檢查 window.aiAssistant，然後檢查其他實例
        const aiInstance = window.aiAssistant || window.AIAssistant || AIAssistant;
        
        if (aiInstance && typeof aiInstance.handleAIResponse === 'function') {
            if (message.success) {
                // 成功的回應
                console.log('✅ 調用 AI 助教處理成功回應');
                aiInstance.handleAIResponse(message.data);
            } else {
                // 錯誤回應
                console.log('❌ 調用 AI 助教處理錯誤回應');
                aiInstance.handleAIError(message.error || 'AI 服務暫時不可用');
            }
        } else {
            console.warn('⚠️ AI Assistant 未初始化，使用降級處理');
            
            // 降級處理：直接顯示回應
                const responseContainer = document.getElementById('aiResponse');
                if (responseContainer) {
                if (message.success && message.data) {
                    responseContainer.innerHTML = `
                        <div class="alert alert-success">
                            <h6><i class="fas fa-robot"></i> AI 助教回應</h6>
                            <div style="white-space: pre-wrap;">${JSON.stringify(message.data, null, 2)}</div>
                        </div>
                    `;
                } else {
                    responseContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-circle"></i> AI 助教錯誤</h6>
                            <div>${message.error || '無法處理 AI 請求'}</div>
                        </div>
                    `;
                }
            }
        }
    }

    // 處理代碼執行結果
    handleCodeExecutionResult(message) {
        console.log('🔍 收到代碼執行結果:', message);
        
        if (window.Editor && typeof window.Editor.handleExecutionResult === 'function') {
            console.log('🔄 調用編輯器處理執行結果...');
            window.Editor.handleExecutionResult(message);
        } else {
            console.error('❌ 編輯器未找到或方法不存在');
            console.log('   - Editor 存在:', !!window.Editor);
            console.log('   - handleExecutionResult 方法存在:', !!(window.Editor && window.Editor.handleExecutionResult));
            
            // 降級處理：直接顯示結果
            if (message.success) {
                alert(`執行成功:\n${message.message}`);
            } else {
                alert(`執行失敗:\n${message.message}`);
            }
        }
    }

    // 處理歷史記錄載入結果
    handleHistoryLoaded(message) {
        console.log('📜 收到歷史記錄:', message);
        
        if (message.success && message.history) {
            console.log(`✅ 載入了 ${message.history.length} 條歷史記錄`);
            
            // 嘗試調用編輯器的歷史處理方法
            if (window.Editor && typeof window.Editor.handleHistoryLoaded === 'function') {
                console.log('🔄 調用編輯器處理歷史記錄...');
                window.Editor.handleHistoryLoaded(message.history);
            } else {
                console.warn('⚠️ 編輯器歷史處理方法未找到，使用降級處理');
                this.displayHistoryFallback(message.history);
            }
        } else {
            console.error('❌ 歷史記錄載入失敗:', message.error || '未知錯誤');
            
            if (window.UI && typeof window.UI.showToast === 'function') {
                window.UI.showToast('歷史記錄', '載入歷史記錄失敗', 'error');
            } else {
                alert('載入歷史記錄失敗: ' + (message.error || '未知錯誤'));
            }
        }
    }

    // 降級處理：顯示歷史記錄
    displayHistoryFallback(history) {
        console.log('📋 使用降級方式顯示歷史記錄');
        
        // 嘗試找到歷史記錄容器
        let historyContainer = document.getElementById('historyList') || 
                              document.getElementById('history-list') ||
                              document.getElementById('codeHistory');
        
        if (!historyContainer) {
            // 如果沒有找到容器，創建一個簡單的顯示
            console.log('📋 創建臨時歷史記錄顯示');
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = `
                <div class="alert alert-info">
                    <h6><i class="fas fa-history"></i> 歷史記錄 (${history.length} 條)</h6>
                    <ul class="list-unstyled">
                        ${history.map(record => `
                            <li class="mb-2">
                                <strong>${record.username}</strong> - ${new Date(record.saved_at).toLocaleString()}
                                <br><small class="text-muted">${record.code_preview}</small>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
            
            // 嘗試添加到主要內容區域
            const mainContent = document.querySelector('.container') || 
                               document.querySelector('.main-content') || 
                               document.body;
            mainContent.appendChild(tempDiv);
            
            // 5秒後自動移除
            setTimeout(() => {
                if (tempDiv.parentNode) {
                    tempDiv.parentNode.removeChild(tempDiv);
                }
            }, 5000);
        } else {
            // 如果找到容器，更新內容
            historyContainer.innerHTML = history.map(record => `
                <div class="history-item mb-2 p-2 border rounded">
                    <div class="d-flex justify-content-between">
                        <strong>${record.username}</strong>
                        <small class="text-muted">${new Date(record.saved_at).toLocaleString()}</small>
                    </div>
                    <div class="code-preview mt-1">
                        <small class="text-muted">${record.code_preview}</small>
                    </div>
                    <div class="text-end">
                        <small class="badge bg-secondary">${record.code_length} 字符</small>
                    </div>
                </div>
            `).join('');
        }
    }

    // 🆕 處理衝突通知 - 讓主改方看到衝突處理狀態
    handleConflictNotification(message) {
        console.log('🚨 收到衝突通知:', message);
        
        if (message.targetUser === this.currentUser) {
            // 顯示主改方的衝突等待界面
            if (window.ConflictResolver && typeof window.ConflictResolver.showSenderWaitingModal === 'function') {
                window.ConflictResolver.showSenderWaitingModal(message);
                console.log('✅ 主改方衝突等待界面已顯示');
            } else {
                // 降級處理：使用簡單的通知
                if (window.UI) {
                    window.UI.showToast(
                        '協作衝突', 
                        `${message.conflictWith} 正在處理您的代碼修改衝突，請稍候...`, 
                        'warning',
                        5000  // 5秒自動消失
                    );
                }
                
                // 在聊天室顯示狀態
                if (window.Chat && typeof window.Chat.addSystemMessage === 'function') {
                    window.Chat.addSystemMessage(
                        `⏳ ${message.conflictWith} 正在處理與您的協作衝突...`
                    );
                }
                
                console.log('✅ 使用降級方式顯示衝突通知');
            }
        }
    }

    // 更新用戶列表
    updateUserList(users) {
        console.log(`👥 準備更新用戶列表: ${users ? users.length : 0} 個用戶`);
        console.log(`🔍 用戶數據完整信息:`, JSON.stringify(users, null, 2));
        
        // 使用正確的元素ID
        const userListElement = document.getElementById('onlineUsers');
        if (!userListElement) {
            console.warn('⚠️ 找不到 onlineUsers 元素');
            return;
        }
        
        if (!users || users.length === 0) {
            userListElement.innerHTML = '<strong>在線用戶:</strong> <span class="text-muted">無</span>';
            return;
        }
        
        // 創建用戶列表HTML
        let userListHTML = '<strong>在線用戶:</strong> ';
        const userNames = users.map(user => {
            const userName = user.username || user.userName || user.name || '匿名用戶';
            const status = user.isActive ? '🟢' : '🟢'; // 在線用戶默認為綠色
            return `${status} ${userName}`;
        });
        
        userListHTML += userNames.join(', ');
        userListElement.innerHTML = userListHTML;
        
        // 更新用戶計數
        const userCountElement = document.getElementById('userCount');
        if (userCountElement) {
            userCountElement.textContent = users.length;
        }
        
        console.log(`✅ 用戶列表已更新: ${users.length} 個用戶`);
        console.log(`📝 顯示內容: ${userListHTML}`);
        console.log(`🔍 各用戶詳細信息:`, users.map((user, index) => 
            `${index + 1}. ID: ${user.user_id}, 用戶名: ${user.username}, 加入時間: ${user.join_time}`
        ).join('\n'));
    }

    // 更新房間信息
    updateRoomInfo(roomId, users) {
        const roomNameElement = document.getElementById('roomName');
        if (roomNameElement) {
            roomNameElement.textContent = roomId;
        }
        
        const userCountElement = document.getElementById('userCount');
        if (userCountElement && users) {
            userCountElement.textContent = users.length;
        }
    }

    // 處理消息隊列
    processMessageQueue() {
        while (this.messageQueue.length > 0 && this.isConnected()) {
            const message = this.messageQueue.shift();
            this.sendMessage(message);
        }
    }

    // 啟動心跳
    startHeartbeat() {
        this.stopHeartbeat(); // 確保不會重複啟動
        
        this.heartbeatInterval = setInterval(() => {
            if (this.isConnected()) {
                this.ws.send(JSON.stringify({ type: 'ping' }));
            }
        }, 30000); // 每30秒發送一次心跳
        
        console.log('💓 心跳已啟動');
    }

    // 停止心跳
    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
            console.log('💔 心跳已停止');
        }
    }

    // 離開房間
    leaveRoom() {
        if (this.isConnected()) {
            this.sendMessage({
                type: 'leave_room',
                room_id: this.currentRoom,
                user_id: this.currentUser
            });
        }
        
        this.stopHeartbeat();
        if (this.ws) {
            this.ws.close(1000, '用戶主動離開');
        }
        
        this.currentRoom = null;
        console.log('👋 已離開房間');
    }

    // 初始化方法（為了與其他模組保持一致）
    initialize() {
        console.log('🔧 WebSocket管理器初始化中...');
        
        // 設置全域引用
        window.wsManager = this;
        
        console.log('✅ WebSocket管理器初始化完成');
        return true;
    }

    // 保存代碼
    saveCode(code) {
        if (!this.isConnected()) {
            console.warn('⚠️ WebSocket 未連接，無法保存代碼');
            return;
        }

        console.log('💾 發送保存代碼請求...');
        this.sendMessage({
            type: 'save_code',
            room_id: this.currentRoom,
            user_id: this.currentUser,
            code: code
        });
    }

    // 載入代碼
    loadCode() {
        if (!this.isConnected()) {
            console.warn('⚠️ WebSocket 未連接，無法載入代碼');
            return;
        }

        console.log('📥 發送載入代碼請求...');
        this.sendMessage({
            type: 'load_code',
            room_id: this.currentRoom,
            user_id: this.currentUser
        });
    }

    // 執行代碼
    runCode(code) {
        if (!this.isConnected()) {
            console.warn('⚠️ WebSocket 未連接，無法執行代碼');
            return;
        }

        console.log('▶️ 發送執行代碼請求...');
        this.sendMessage({
            type: 'run_code',
            room_id: this.currentRoom,
            user_id: this.currentUser,
            code: code
        });
    }

    // 獲取歷史記錄
    getHistory() {
        if (!this.isConnected()) {
            console.warn('⚠️ WebSocket 未連接，無法獲取歷史記錄');
            return;
        }

        console.log('📜 發送獲取歷史記錄請求...');
        this.sendMessage({
            type: 'get_history',
            room_id: this.currentRoom,
            user_id: this.currentUser
        });
    }
}

// 全局 WebSocket 管理器實例
const wsManager = new WebSocketManager(); 