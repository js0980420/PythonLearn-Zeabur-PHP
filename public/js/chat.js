// 聊天功能管理
class ChatManager {
    constructor() {
        this.chatContainer = null;
        this.chatInput = null;
        this.initialized = false;
        this.recentSentMessage = null; // 🔥 用於防止重複顯示消息
        this.displayedMessages = new Set(); // 🔥 已顯示的消息ID集合
        this.messageBuffer = new Map(); // 🔥 消息緩衝區，防止重複
    }

    // 初始化聊天功能
    initialize() {
        console.log('🔍 開始初始化聊天模組...');
        console.log('🔍 當前DOM狀態:', {
            document_ready: document.readyState,
            chatSection_exists: !!document.getElementById('chatSection'),
            chatContainer_exists: !!document.getElementById('chatContainer'),
            chatInput_exists: !!document.getElementById('chatInput')
        });
        
        // 確保DOM完全準備好
        if (document.readyState === 'loading') {
            console.log('📄 DOM尚未完全載入，註冊DOMContentLoaded事件...');
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => this.attemptInitialization(), 500);
            });
        } else {
            console.log('📄 DOM已準備好，延遲初始化...');
            // DOM已經準備好，延遲一下再初始化
            setTimeout(() => this.attemptInitialization(), 200);
        }
    }

    // 嘗試初始化
    attemptInitialization() {
        let attempts = 0;
        const maxAttempts = 10;
        
        const tryInit = () => {
            attempts++;
            console.log(`🔍 嘗試初始化聊天室 (第${attempts}次)...`);
            
            this.chatContainer = document.getElementById('chatContainer');
            this.chatInput = document.getElementById('chatInput');
            
            console.log('🔍 查找結果:', {
                chatContainer: !!this.chatContainer,
                chatInput: !!this.chatInput,
                chatContainerElement: this.chatContainer,
                chatInputElement: this.chatInput
            });
            
            if (this.chatContainer && this.chatInput) {
                this.setupChatElements();
                return;
            }
            
            // 如果找不到元素，嘗試創建
            if (attempts <= 3) {
                this.createChatElements();
                
                // 重新查找
                this.chatContainer = document.getElementById('chatContainer');
                this.chatInput = document.getElementById('chatInput');
                
                if (this.chatContainer && this.chatInput) {
                    this.setupChatElements();
                    return;
                }
            }
            
            // 如果還是失敗，繼續嘗試
            if (attempts < maxAttempts) {
                setTimeout(tryInit, 1000);
            } else {
                console.error('❌ 聊天室初始化失敗，已達到最大嘗試次數');
            }
        };
        
        tryInit();
    }

    // 設置聊天元素
    setupChatElements() {
        if (this.initialized) return;
        
        console.log('✅ 找到聊天元素，開始設置...');
        
        // 動態設置聊天室樣式
        this.setupChatStyles();
        
        // 設置Enter鍵發送
        this.chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // 添加歡迎消息
        this.addSystemMessage('聊天室已準備就緒！可以開始對話了 💬');
        
        this.initialized = true;
        console.log('✅ 聊天模組初始化完成');
    }

    // 動態設置聊天室樣式
    setupChatStyles() {
        console.log('🎨 開始設置聊天室樣式...');
        
        // 設置聊天區域樣式
        const chatSection = document.getElementById('chatSection');
        if (chatSection) {
            chatSection.style.cssText = `
                padding: 0 !important;
                border: none !important;
                background: transparent !important;
                border-radius: 0 !important;
                margin-top: 0 !important;
                min-height: 400px !important;
                display: block !important;
            `;
        }
        
        // 設置聊天容器樣式
        if (this.chatContainer) {
            this.chatContainer.style.cssText = `
                height: 300px !important;
                overflow-y: auto !important;
                border: 1px solid #dee2e6 !important;
                border-radius: 10px !important;
                padding: 15px !important;
                background: #f8f9fa !important;
                box-shadow: inset 0 1px 3px rgba(0,0,0,0.1) !important;
                margin-bottom: 10px !important;
            `;
        }
        
        // 設置輸入框樣式
        if (this.chatInput) {
            this.chatInput.style.cssText = `
                border-radius: 5px 0 0 5px !important;
                border: 1px solid #ced4da !important;
                padding: 8px 12px !important;
                font-size: 14px !important;
            `;
        }
        
        console.log('✅ 聊天室樣式設置完成');
    }

    // 創建聊天元素
    createChatElements() {
        console.log('🔧 嘗試創建聊天元素...');
        
        const chatSection = document.getElementById('chatSection');
        if (!chatSection) {
            console.error('❌ 找不到聊天區域容器');
            return;
        }
        
        // 創建聊天容器
        if (!document.getElementById('chatContainer')) {
            console.log('🔧 創建聊天容器...');
            const container = document.createElement('div');
            container.id = 'chatContainer';
            container.className = 'chat-container';
            chatSection.insertBefore(container, chatSection.firstChild);
        }
        
        // 創建輸入區域
        let inputGroup = chatSection.querySelector('.input-group');
        if (!inputGroup) {
            console.log('🔧 創建輸入區域...');
            inputGroup = document.createElement('div');
            inputGroup.className = 'input-group mt-2';
            inputGroup.innerHTML = `
                <input type="text" class="form-control" id="chatInput" placeholder="輸入消息...">
                <button class="btn btn-primary" onclick="sendChat()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            `;
            chatSection.appendChild(inputGroup);
        } else if (!document.getElementById('chatInput')) {
            console.log('🔧 創建輸入框...');
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.id = 'chatInput';
            input.placeholder = '輸入消息...';
            inputGroup.insertBefore(input, inputGroup.firstChild);
        }
        
        console.log('✅ 聊天元素創建完成');
    }

    // 發送聊天消息
    sendMessage() {
        // 檢查聊天輸入框是否已初始化
        if (!this.chatInput) {
            console.error('❌ 聊天輸入框未初始化，嘗試重新初始化...');
            this.attemptInitialization();
            
            // 如果初始化後仍然沒有輸入框，則退出
            if (!this.chatInput) {
                console.error('❌ 無法初始化聊天輸入框，發送失敗');
                if (window.UI && window.UI.showErrorToast) {
                    window.UI.showErrorToast('聊天功能尚未準備就緒，請稍後再試');
                }
                return;
            }
        }
        
        const message = this.chatInput.value.trim();
        
        console.log(`💬 學生嘗試發送聊天消息: "${message}"`);
        console.log(`🔗 HTTP輪詢連接狀態: ${wsManager.isConnected()}`);
        
        if (!message) {
            console.log(`❌ 消息為空，取消發送`);
            return;
        }
        
        if (!wsManager.isConnected()) {
            console.log(`❌ HTTP輪詢未連接，無法發送消息`);
            if (window.UI && window.UI.showErrorToast) {
                window.UI.showErrorToast('未連接到服務器，無法發送消息');
            }
            return;
        }
        
        console.log(`📤 發送聊天消息到服務器...`);
        
        // 🔥 立即在本地顯示消息（樂觀更新）
        const currentUser = window.UserManager?.getCurrentUser?.()?.name || window.wsManager?.currentUser || '我';
        
        // 🔥 創建本地消息對象，包含臨時ID
        const localMessage = {
            id: `local_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            userName: currentUser,
            message: message,
            timestamp: Date.now(),
            isLocal: true
        };
        
        // 🔥 記錄本地消息，防止服務器返回時重複顯示
        const messageId = this.generateMessageHash(localMessage);
        this.displayedMessages.add(messageId);
        
        // 🔥 同時記錄消息內容哈希，防止服務器返回相同內容
        const contentHash = this.simpleHash(`${currentUser}:${message}`);
        this.displayedMessages.add(`content_${contentHash}`);
        
        this.addMessage(currentUser, message, false, false);
        
        wsManager.sendMessage({
            type: 'chat_message',
            message: message
        });
        
        this.chatInput.value = '';
        console.log(`✅ 聊天消息已發送並顯示，輸入框已清空`);
    }

    // 發送AI回應到聊天室
    sendAIResponseToChat(aiResponse) {
        if (!aiResponse || !wsManager.isConnected()) return;
        
        // 清理HTML標籤，保留文本內容
        const cleanResponse = this.stripHtmlTags(aiResponse);
        const formattedMessage = `🤖 AI助教回應：\n${cleanResponse}`;
        
        wsManager.sendMessage({
            type: 'chat_message',
            message: formattedMessage
        });
        
        // 顯示成功提示
        if (UI && UI.showSuccessToast) {
            UI.showSuccessToast('AI回應已分享到聊天室');
        }
        
        // 切換到聊天室查看
        if (UI && UI.switchToChat) {
            UI.switchToChat();
        }
    }

    // 清理HTML標籤
    stripHtmlTags(html) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // 處理列表項目
        const listItems = tempDiv.querySelectorAll('li');
        listItems.forEach(li => {
            li.innerHTML = '• ' + li.innerHTML;
        });
        
        // 獲取純文本
        let text = tempDiv.textContent || tempDiv.innerText || '';
        
        // 清理多餘的空行
        text = text.replace(/\n\s*\n/g, '\n').trim();
        
        return text;
    }

    // 獲取用戶顯示名稱
    getDisplayName(username) {
        const userDisplayNames = {
            'Alex Wang': '艾克斯王'
        };
        return userDisplayNames[username] || username;
    }

    // 添加聊天消息
    addMessage(userName, message, isSystem = false, isTeacher = false) {
        if (!this.chatContainer) {
            console.error('❌ 聊天容器未初始化');
            return;
        }
        
        const messageDiv = document.createElement('div');
        let messageClass = 'chat-message';
        
        if (isSystem) {
            messageClass += ' system-message';
        } else if (isTeacher) {
            messageClass += ' teacher-message';
        }
        
        messageDiv.className = messageClass;
        
        // 動態設置消息樣式
        this.setChatMessageStyles(messageDiv, isSystem, isTeacher);
        
        if (message.includes('=== 程式碼衝突討論 ===')) {
            // 衝突代碼特殊格式
            messageDiv.innerHTML = this.formatConflictMessage(userName, message);
        } else {
            // 獲取顯示名稱
            const displayName = this.getDisplayName(userName);
            // 為教師消息添加特殊標識
            const userDisplay = isTeacher ? `👨‍🏫 ${displayName}` : displayName;
            messageDiv.innerHTML = `<strong>${userDisplay}:</strong> ${this.escapeHtml(message)}`;
        }
        
        this.chatContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }

    // 設置聊天消息樣式
    setChatMessageStyles(messageDiv, isSystem = false, isTeacher = false) {
        if (isSystem) {
            messageDiv.style.cssText = `
                margin-bottom: 12px !important;
                padding: 10px 15px !important;
                border-radius: 8px !important;
                background: #e9ecef !important;
                border-left: 3px solid #6c757d !important;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
                font-style: italic !important;
            `;
        } else if (isTeacher) {
            messageDiv.style.cssText = `
                margin-bottom: 12px !important;
                padding: 10px 15px !important;
                border-radius: 8px !important;
                background: #e8f5e8 !important;
                border-left: 3px solid #28a745 !important;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
                font-weight: 500 !important;
            `;
        } else {
            messageDiv.style.cssText = `
                margin-bottom: 12px !important;
                padding: 10px 15px !important;
                border-radius: 8px !important;
                background: white !important;
                border-left: 3px solid #007bff !important;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
            `;
        }
    }

    // 添加系統消息
    addSystemMessage(message) {
        this.addMessage('系統', message, true);
    }

    // 載入聊天歷史
    loadHistory(messages) {
        if (!this.chatContainer) {
            console.error('❌ 聊天容器未初始化，無法載入歷史');
            return;
        }
        
        console.log(`📜 載入聊天歷史: ${messages.length} 條消息`);
        this.chatContainer.innerHTML = '';
        
        messages.forEach(msg => {
            // 檢查是否為教師消息
            const isTeacher = msg.isTeacher || false;
            const isSystem = msg.type === 'system';
            
            console.log(`📝 載入消息: ${msg.userName} - ${msg.message.substring(0, 50)}... (教師: ${isTeacher})`);
            this.addMessage(msg.userName, msg.message, isSystem, isTeacher);
        });
        
        // 添加歷史載入完成的提示
        if (messages.length > 0) {
            this.addSystemMessage(`已載入 ${messages.length} 條歷史消息`);
        }
    }

    // 格式化衝突消息
    formatConflictMessage(userName, message) {
        const parts = message.split('\n');
        let formattedMessage = `<strong>${userName}:</strong><br>`;
        let inCodeBlock = false;
        
        parts.forEach(part => {
            if (part.includes('我的版本') || part.includes('服務器版本')) {
                formattedMessage += `<br><strong>${part}</strong><br>`;
                inCodeBlock = true;
            } else if (part.includes('請大家討論')) {
                inCodeBlock = false;
                formattedMessage += `<br><em>${part}</em>`;
            } else if (inCodeBlock && part.trim()) {
                formattedMessage += `<div class="conflict-code-block">${this.escapeHtml(part)}</div>`;
            } else if (part.trim()) {
                formattedMessage += this.escapeHtml(part) + '<br>';
            }
        });
        
        return formattedMessage;
    }

    // 轉義HTML
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 滾動到底部
    scrollToBottom() {
        this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
    }

    // 聚焦輸入框
    focusInput() {
        if (this.chatInput) {
            this.chatInput.focus();
        }
    }

    // 清除聊天記錄
    clearChat() {
        if (this.chatContainer) {
            this.chatContainer.innerHTML = '';
        }
        
        // 🔥 清除重複消息防護機制的緩存
        this.displayedMessages.clear();
        this.messageBuffer.clear();
        this.recentSentMessage = null;
        
        console.log('🧹 聊天記錄和防重複緩存已清除');
    }

    // 顯示從服務器接收的聊天消息
    displayMessage(message) {
        console.log('💬 顯示聊天消息:', message);
        
        if (!message || !message.userName || !message.message) {
            console.warn('⚠️ 收到無效的聊天消息:', message);
            return;
        }
        
        // 🔥 創建消息的唯一標識符
        const messageId = message.id || this.generateMessageHash(message);
        
        // 🔥 檢查是否已經顯示過這條消息
        if (this.displayedMessages.has(messageId)) {
            console.log('🔄 跳過重複消息 (ID):', messageId, message.message.substring(0, 30));
            return;
        }
        
        // 🔥 檢查內容哈希是否已存在（防止內容相同但ID不同的重複）
        const contentHash = this.simpleHash(`${message.userName}:${message.message}`);
        const contentHashId = `content_${contentHash}`;
        if (this.displayedMessages.has(contentHashId)) {
            console.log('🔄 跳過重複消息 (內容):', contentHash, message.message.substring(0, 30));
            return;
        }
        
        // 🔥 檢查消息緩衝區是否有相同內容的消息（防止快速重複）
        const messageKey = `${message.userName}:${message.message}:${message.timestamp || Date.now()}`;
        const now = Date.now();
        
        if (this.messageBuffer.has(messageKey)) {
            const lastTime = this.messageBuffer.get(messageKey);
            if (now - lastTime < 1000) { // 1秒內的重複消息
                console.log('🔄 跳過1秒內的重複消息:', message.message.substring(0, 30));
                return;
            }
        }
        
        // 🔥 記錄消息ID和內容哈希
        this.displayedMessages.add(messageId);
        this.displayedMessages.add(contentHashId);
        this.messageBuffer.set(messageKey, now);
        
        // 🔥 清理舊的緩衝區記錄（保持最近100條）
        if (this.displayedMessages.size > 100) {
            const oldestEntries = Array.from(this.displayedMessages).slice(0, 20);
            oldestEntries.forEach(id => this.displayedMessages.delete(id));
        }
        
        // 🔥 清理舊的消息緩衝區（保持最近50條）
        if (this.messageBuffer.size > 50) {
            const oldestKeys = Array.from(this.messageBuffer.keys()).slice(0, 10);
            oldestKeys.forEach(key => this.messageBuffer.delete(key));
        }
        
        // 處理消息格式
        const userName = message.userName;
        const messageText = message.message;
        const isTeacher = message.isTeacher || false;
        const isSystem = message.type === 'system';
        
        this.addMessage(userName, messageText, isSystem, isTeacher);
    }

    // 🔥 生成消息哈希值
    generateMessageHash(message) {
        const content = `${message.userName}:${message.message}:${message.timestamp || ''}`;
        return this.simpleHash(content);
    }

    // 🔥 簡單哈希函數
    simpleHash(str) {
        let hash = 0;
        if (str.length === 0) return hash;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // 轉換為32位整數
        }
        return Math.abs(hash).toString(36);
    }

    // 🔥 獲取防重複機制狀態（調試用）
    getDeduplicationStats() {
        return {
            displayedMessagesCount: this.displayedMessages.size,
            messageBufferCount: this.messageBuffer.size,
            recentSentMessage: this.recentSentMessage,
            displayedMessages: Array.from(this.displayedMessages).slice(-10), // 最近10個
            messageBufferKeys: Array.from(this.messageBuffer.keys()).slice(-5) // 最近5個
        };
    }
}

// 全局聊天管理器實例
const Chat = new ChatManager();

// 同時設置為window全域變數，確保在任何地方都能存取
window.Chat = Chat;

console.log('🔧 聊天管理器已創建');
console.log('✅ 全域 Chat 實例已創建並設置到 window.Chat:', Chat);

// 等待 DOM 完全載入後再初始化聊天
function initializeChatWhenReady() {
    const chatSection = document.getElementById('chatSection');
    const chatInput = document.getElementById('chatInput');
    
    if (chatSection && chatInput) {
        // DOM 和聊天元素都已準備好
        Chat.initialize();
        console.log('✅ 聊天系統已在 DOM 準備完成後初始化');
    } else {
        // 尚未準備好，延遲重試
        console.log('⏳ 等待聊天 DOM 元素準備中...');
        setTimeout(initializeChatWhenReady, 100);
    }
}

// 檢查 DOM 狀態並初始化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeChatWhenReady);
} else {
    // DOM 已經載入完成
    initializeChatWhenReady();
}

// 全局函數供HTML調用
function sendChat() {
    Chat.sendMessage();
} 