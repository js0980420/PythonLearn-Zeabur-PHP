/**
 * WebSocket 管理器 - 純 PHP 整合服務器版本
 * 統一處理 WebSocket 連接和消息
 */

class WebSocketManager {
    constructor() {
        this.ws = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.messageQueue = [];
        this.eventHandlers = new Map();
        
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
                    console.error('❌ WebSocket 連接超時');
                    this.ws.close();
                    reject(new Error('Connection timeout'));
                }, 10000);
                
                this.ws.onopen = () => {
                    clearTimeout(connectionTimeout);
                    this.isConnected = true;
                    this.reconnectAttempts = 0;
                    
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
                    this.isConnected = false;
                    console.warn('⚠️ WebSocket 連接關閉:', event.code, event.reason);
                    
                    this.emit('disconnected', { code: event.code, reason: event.reason });
                    
                    // 自動重連
                    if (this.reconnectAttempts < this.maxReconnectAttempts) {
                        this.scheduleReconnect();
                    } else {
                        console.error('❌ 達到最大重連次數，停止重連');
                        this.emit('maxReconnectAttemptsReached');
                    }
                };
                
                this.ws.onerror = (error) => {
                    clearTimeout(connectionTimeout);
                    console.error('❌ WebSocket 連接錯誤:', error);
                    
                    this.emit('error', error);
                    reject(error);
                };
                
            } catch (error) {
                console.error('❌ WebSocket 創建失敗:', error);
                reject(error);
            }
        });
    }
    
    /**
     * 安排重連
     */
    scheduleReconnect() {
        this.reconnectAttempts++;
        const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
        
        console.log(`🔄 計劃重連 (${this.reconnectAttempts}/${this.maxReconnectAttempts}) 在 ${delay}ms 後`);
        
        setTimeout(() => {
            if (!this.isConnected) {
                this.connect().catch(error => {
                    console.error('🔄 重連失敗:', error);
                });
            }
        }, delay);
    }
    
    /**
     * 發送消息
     */
    async sendMessage(message) {
        return new Promise((resolve, reject) => {
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
        while (this.messageQueue.length > 0 && this.isConnected) {
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
        this.isConnected = false;
        this.reconnectAttempts = this.maxReconnectAttempts; // 阻止自動重連
    }
    
    /**
     * 獲取連接狀態
     */
    getConnectionState() {
        return {
            isConnected: this.isConnected,
            reconnectAttempts: this.reconnectAttempts,
            queuedMessages: this.messageQueue.length,
            wsUrl: this.wsUrl
        };
    }
}

// 創建全域 WebSocket 管理器實例
window.wsManager = new WebSocketManager();

// 自動連接
window.wsManager.connect().catch(error => {
    console.error('❌ 初始 WebSocket 連接失敗:', error);
});

console.log('✅ WebSocket 管理器已載入'); 