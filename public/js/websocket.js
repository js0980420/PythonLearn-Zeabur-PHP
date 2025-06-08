/**
 * WebSocket 管理器 - 帶 HTTP 降級的版本
 * 統一處理 WebSocket 連接和消息，支持 HTTP 模式降級
 */

class WebSocketManager {
    constructor() {
        this.ws = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 3; // 減少重連次數
        this.reconnectDelay = 1000;
        this.messageQueue = [];
        this.eventHandlers = new Map();
        this.httpMode = false; // HTTP 降級模式
        this.pollInterval = null;
        
        // 自動檢測 WebSocket URL
        this.wsUrl = this.getWebSocketUrl();
        
        console.log('🔧 WebSocket Manager 初始化');
        console.log('📡 WebSocket URL:', this.wsUrl);
    }
    
    /**
     * 自動檢測 WebSocket URL
     */
    getWebSocketUrl() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.host;
        
        // 對於 Zeabur 部署環境
        if (host.includes('zeabur.app')) {
            return `wss://${host}/ws`;
        }
        
        // 對於本地開發環境
        if (host.includes('localhost') || host.includes('127.0.0.1')) {
            return `ws://${host}/ws`;
        }
        
        // 默認配置
        return `${protocol}//${host}/ws`;
    }
    
    /**
     * 建立 WebSocket 連接
     */
    async connect() {
        return new Promise((resolve, reject) => {
            try {
                console.log('🔌 嘗試連接 WebSocket:', this.wsUrl);
                
                this.ws = new WebSocket(this.wsUrl);
                
                // 設置連接超時
                const connectionTimeout = setTimeout(() => {
                    console.warn('⚠️ WebSocket 連接超時，切換到 HTTP 模式');
                    this.ws.close();
                    this.switchToHttpMode();
                    resolve(true);
                }, 5000); // 減少超時時間
                
                this.ws.onopen = () => {
                    clearTimeout(connectionTimeout);
                    this.isConnected = true;
                    this.reconnectAttempts = 0;
                    this.httpMode = false;
                    
                    console.log('✅ WebSocket 連接成功');
                    
                    // 處理消息佇列
                    this.processMessageQueue();
                    
                    // 觸發連接成功事件
                    this.emit('connected');
                    
                    resolve(true);
                };
                
                this.ws.onmessage = (event) => {
                    this.handleMessage(event.data);
                };
                
                this.ws.onclose = (event) => {
                    clearTimeout(connectionTimeout);
                    this.isConnected = false;
                    console.warn('⚠️ WebSocket 連接關閉:', event.code, event.reason);
                    
                    this.emit('disconnected', { code: event.code, reason: event.reason });
                    
                    // 如果是正常關閉或達到最大重連次數，切換到 HTTP 模式
                    if (event.code === 1000 || this.reconnectAttempts >= this.maxReconnectAttempts) {
                        console.log('🔄 切換到 HTTP 降級模式');
                        this.switchToHttpMode();
                    } else {
                        this.scheduleReconnect();
                    }
                };
                
                this.ws.onerror = (error) => {
                    clearTimeout(connectionTimeout);
                    console.warn('⚠️ WebSocket 連接錯誤，將切換到 HTTP 模式');
                    
                    this.emit('error', error);
                    
                    // 立即切換到 HTTP 模式而不是拒絕
                    this.switchToHttpMode();
                    resolve(true);
                };
                
            } catch (error) {
                console.warn('⚠️ WebSocket 創建失敗，切換到 HTTP 模式:', error);
                this.switchToHttpMode();
                resolve(true);
            }
        });
    }
    
    /**
     * 切換到 HTTP 降級模式
     */
    switchToHttpMode() {
        this.httpMode = true;
        this.isConnected = true; // 在 HTTP 模式下也算是"連接"
        this.ws = null;
        
        console.log('📡 已切換到 HTTP 降級模式');
        console.log('ℹ️ 功能限制: 無實時同步，需手動刷新獲取更新');
        
        // 觸發連接成功事件
        this.emit('connected');
        this.emit('httpModeEnabled');
        
        // 處理消息佇列
        this.processMessageQueue();
        
        // 開始輪詢 (可選)
        this.startPolling();
    }
    
    /**
     * 開始 HTTP 輪詢
     */
    startPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
        
        // 每 30 秒輪詢一次狀態
        this.pollInterval = setInterval(() => {
            if (this.httpMode) {
                this.pollStatus();
            }
        }, 30000);
    }
    
    /**
     * 輪詢服務器狀態
     */
    async pollStatus() {
        try {
            const response = await fetch('/api/status');
            if (response.ok) {
                const status = await response.json();
                this.emit('statusUpdate', status);
            }
        } catch (error) {
            console.warn('⚠️ 狀態輪詢失敗:', error);
        }
    }
    
    /**
     * 安排重連
     */
    scheduleReconnect() {
        this.reconnectAttempts++;
        const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
        
        console.log(`🔄 計劃重連 (${this.reconnectAttempts}/${this.maxReconnectAttempts}) 在 ${delay}ms 後`);
        
        setTimeout(() => {
            if (!this.isConnected && !this.httpMode) {
                this.connect().catch(error => {
                    console.warn('🔄 重連失敗，切換到 HTTP 模式:', error);
                    this.switchToHttpMode();
                });
            }
        }, delay);
    }
    
    /**
     * 發送消息
     */
    async sendMessage(message) {
        return new Promise((resolve, reject) => {
            if (this.httpMode) {
                // HTTP 模式：通過 API 發送
                this.sendHttpMessage(message).then(resolve).catch(reject);
                return;
            }
            
            if (!this.isConnected || !this.ws || this.ws.readyState !== WebSocket.OPEN) {
                // 將消息加入佇列
                this.messageQueue.push({ message, resolve, reject });
                console.warn('⚠️ WebSocket 未連接，消息已加入佇列');
                return;
            }
            
            try {
                const messageStr = JSON.stringify(message);
                this.ws.send(messageStr);
                console.log('📤 發送消息:', message.type);
                resolve(true);
            } catch (error) {
                console.error('❌ 發送消息失敗:', error);
                reject(error);
            }
        });
    }
    
    /**
     * 通過 HTTP API 發送消息
     */
    async sendHttpMessage(message) {
        try {
            const response = await fetch('/api/websocket', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(message)
            });
            
            if (response.ok) {
                const result = await response.json();
                console.log('📤 HTTP 消息發送成功:', message.type);
                return result;
            } else {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
        } catch (error) {
            console.warn('⚠️ HTTP 消息發送失敗:', error);
            // 在 HTTP 模式下，即使發送失敗也不拋出錯誤
            return { success: false, error: error.message };
        }
    }
    
    /**
     * 處理接收到的消息
     */
    handleMessage(data) {
        try {
            const message = JSON.parse(data);
            console.log('📥 收到消息:', message.type);
            
            // 觸發對應的事件處理器
            this.emit(message.type, message);
            
            // 通用消息處理
            this.emit('message', message);
            
        } catch (error) {
            console.error('❌ 解析消息失敗:', error, data);
        }
    }
    
    /**
     * 處理消息佇列
     */
    processMessageQueue() {
        while (this.messageQueue.length > 0) {
            const { message, resolve, reject } = this.messageQueue.shift();
            this.sendMessage(message).then(resolve).catch(reject);
        }
    }
    
    /**
     * 註冊事件監聽器
     */
    on(event, handler) {
        if (!this.eventHandlers.has(event)) {
            this.eventHandlers.set(event, []);
        }
        this.eventHandlers.get(event).push(handler);
    }
    
    /**
     * 移除事件監聽器
     */
    off(event, handler) {
        if (this.eventHandlers.has(event)) {
            const handlers = this.eventHandlers.get(event);
            const index = handlers.indexOf(handler);
            if (index > -1) {
                handlers.splice(index, 1);
            }
        }
    }
    
    /**
     * 觸發事件
     */
    emit(event, data) {
        const handlers = this.eventHandlers.get(event);
        if (handlers) {
            handlers.forEach(handler => {
                try {
                    handler(data);
                } catch (error) {
                    console.error(`❌ 事件處理器錯誤 (${event}):`, error);
                }
            });
        }
    }
    
    /**
     * 關閉連接
     */
    disconnect() {
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
        
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        
        this.isConnected = false;
        this.httpMode = false;
        this.reconnectAttempts = this.maxReconnectAttempts; // 阻止自動重連
    }
    
    /**
     * 檢查是否已連接 (包括 HTTP 模式)
     */
    getConnectionStatus() {
        return this.isConnected;
    }
    
    /**
     * 獲取連接狀態
     */
    getConnectionState() {
        return {
            isConnected: this.isConnected,
            httpMode: this.httpMode,
            reconnectAttempts: this.reconnectAttempts,
            queuedMessages: this.messageQueue.length,
            wsUrl: this.wsUrl
        };
    }
}

// 創建全域 WebSocket 管理器實例
window.wsManager = new WebSocketManager();

// 監聽 HTTP 模式切換
window.wsManager.on('httpModeEnabled', () => {
    // 顯示 HTTP 模式狀態指示器
    setTimeout(() => {
        if (window.UI && typeof window.UI.showHttpModeStatus === 'function') {
            window.UI.showHttpModeStatus();
        } else if (window.UI && typeof window.UI.showWarningToast === 'function') {
            window.UI.showWarningToast('已切換到 HTTP 模式，部分實時功能受限');
        } else {
            console.warn('⚠️ 當前為 HTTP 模式，無法提供實時協作功能');
        }
    }, 1000); // 延遲顯示，確保 UI 已載入
});

// 自動連接
window.wsManager.connect().catch(error => {
    console.warn('⚠️ 初始連接失敗，已切換到 HTTP 模式:', error);
});

console.log('✅ WebSocket 管理器已載入 (支持 HTTP 降級)'); 