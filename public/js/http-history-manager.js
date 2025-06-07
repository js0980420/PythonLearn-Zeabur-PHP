/**
 * HTTP API 歷史記錄管理器
 * 獨立於 WebSocket 的歷史記錄功能
 */
class HTTPHistoryManager {
    constructor() {
        // 本地開發時 API 使用 8080 埠，生產環境使用當前 origin
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            this.baseUrl = `${window.location.protocol}//${window.location.hostname}:8080`;
        } else {
            this.baseUrl = window.location.origin;
        }
        this.roomId = 'test-room'; // 默認房間ID
        this.cache = new Map(); // 緩存歷史記錄
        this.lastFetchTime = 0;
        this.cacheTimeout = 30000; // 30秒緩存
        
        console.log('📚 HTTP 歷史記錄管理器初始化');
        this.init();
    }
    
    init() {
        // 監聽房間變更
        document.addEventListener('roomChanged', (event) => {
            this.roomId = event.detail.roomId;
            this.clearCache();
            console.log(`🏠 房間變更為: ${this.roomId}`);
        });
        
        // 暫時禁用自動獲取歷史記錄，使用內存模式
        console.log('📚 HTTP 歷史記錄管理器已初始化，但暫時禁用自動載入');
    }
    
    /**
     * 設置房間ID
     */
    setRoomId(roomId) {
        if (this.roomId !== roomId) {
            this.roomId = roomId;
            this.clearCache();
            console.log(`🏠 設置房間ID: ${roomId}`);
        }
    }
    
    /**
     * 清除緩存
     */
    clearCache() {
        this.cache.clear();
        this.lastFetchTime = 0;
    }
    
    /**
     * 獲取歷史記錄列表
     */
    async loadHistory(forceRefresh = false) {
        const now = Date.now();
        const cacheKey = `history_${this.roomId}`;
        
        // 檢查緩存
        if (!forceRefresh && this.cache.has(cacheKey) && (now - this.lastFetchTime) < this.cacheTimeout) {
            console.log('📚 使用緩存的歷史記錄');
            return this.cache.get(cacheKey);
        }
        
        try {
            console.log(`📚 通過 HTTP API 請求歷史記錄: ${this.roomId}`);
            
            const url = `${this.baseUrl}/api.php?room_id=${encodeURIComponent(this.roomId)}&limit=20`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                const history = data.data?.history || data.history || [];
                console.log(`✅ 獲取到 ${history.length} 條歷史記錄`);
                
                // 更新緩存
                this.cache.set(cacheKey, history);
                this.lastFetchTime = now;
                
                // 更新UI
                this.updateHistoryUI(history);
                
                return history;
            } else {
                throw new Error(data.message || '獲取歷史記錄失敗');
            }
            
        } catch (error) {
            console.error('❌ HTTP API 歷史記錄請求失敗:', error);
            this.showError(`獲取歷史記錄失敗: ${error.message}`);
            return [];
        }
    }
    
    /**
     * 載入特定版本的代碼
     */
    async loadVersion(historyId) {
        try {
            console.log(`📖 載入歷史版本: ${historyId}`);
            
            const url = `${this.baseUrl}/api.php`;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: 'load',
                    history_id: historyId,
                    room_id: this.roomId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                const versionData = data.data || data;
                console.log('✅ 版本載入成功');
                
                // 更新編輯器
                if (window.editor && versionData.code_content) {
                    window.editor.setValue(versionData.code_content);
                    this.showSuccess(`已載入版本: ${versionData.title || versionData.save_name}`);
                }
                
                return versionData;
            } else {
                throw new Error(data.message || '載入版本失敗');
            }
            
        } catch (error) {
            console.error('❌ 載入版本失敗:', error);
            this.showError(`載入版本失敗: ${error.message}`);
            return null;
        }
    }
    
    /**
     * 保存當前代碼為新版本
     */
    async saveVersion(code, description = null) {
        try {
            const saveName = description || `保存 ${new Date().toLocaleString()}`;
            console.log(`💾 保存新版本: ${saveName}`);
            
            const url = `${this.baseUrl}/api.php`;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: 'save',
                    room_id: this.roomId,
                    code: code,
                    description: saveName,
                    save_name: saveName
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ 版本保存成功');
                this.showSuccess(`版本保存成功: ${saveName}`);
                
                // 清除緩存並重新載入歷史記錄
                this.clearCache();
                this.loadHistory(true);
                
                return data.data || data;
            } else {
                throw new Error(data.message || '保存版本失敗');
            }
            
        } catch (error) {
            console.error('❌ 保存版本失敗:', error);
            this.showError(`保存版本失敗: ${error.message}`);
            return null;
        }
    }
    
    /**
     * 刪除歷史版本
     */
    async deleteVersion(historyId) {
        try {
            console.log(`🗑️ 刪除歷史版本: ${historyId}`);
            
            const url = `${this.baseUrl}/api.php`;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    history_id: historyId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ 版本刪除成功');
                this.showSuccess('版本刪除成功');
                
                // 清除緩存並重新載入歷史記錄
                this.clearCache();
                this.loadHistory(true);
                
                return true;
            } else {
                throw new Error(data.message || '刪除版本失敗');
            }
            
        } catch (error) {
            console.error('❌ 刪除版本失敗:', error);
            this.showError(`刪除版本失敗: ${error.message}`);
            return false;
        }
    }
    
    /**
     * 更新歷史記錄UI
     */
    updateHistoryUI(history) {
        // 更新下拉選單
        if (window.SaveLoadManager) {
            window.SaveLoadManager.updateHistoryDropdown(history);
        }
        
        // 更新歷史記錄面板
        this.updateHistoryPanel(history);
        
        // 觸發自定義事件
        document.dispatchEvent(new CustomEvent('historyUpdated', {
            detail: { history, roomId: this.roomId }
        }));
    }
    
    /**
     * 更新歷史記錄面板
     */
    updateHistoryPanel(history) {
        const panel = document.getElementById('history-panel');
        if (!panel) return;
        
        const listContainer = panel.querySelector('.history-list') || this.createHistoryList(panel);
        
        listContainer.innerHTML = '';
        
        if (history.length === 0) {
            listContainer.innerHTML = '<div class="no-history">暫無歷史記錄</div>';
            return;
        }
        
        history.forEach(item => {
            const historyItem = this.createHistoryItem(item);
            listContainer.appendChild(historyItem);
        });
    }
    
    /**
     * 創建歷史記錄列表容器
     */
    createHistoryList(panel) {
        const listContainer = document.createElement('div');
        listContainer.className = 'history-list';
        panel.appendChild(listContainer);
        return listContainer;
    }
    
    /**
     * 創建歷史記錄項目
     */
    createHistoryItem(item) {
        const div = document.createElement('div');
        div.className = 'history-item';
        div.innerHTML = `
            <div class="history-info">
                <div class="history-title">${item.title || item.save_name || '未命名'}</div>
                <div class="history-meta">
                    <span class="author">${item.author || item.username || '未知'}</span>
                    <span class="time">${this.formatTime(item.timestamp || item.saved_at)}</span>
                </div>
                <div class="code-preview">${item.code_preview || '無預覽'}</div>
            </div>
            <div class="history-actions">
                <button class="btn-load" data-id="${item.id}">載入</button>
                <button class="btn-delete" data-id="${item.id}">刪除</button>
            </div>
        `;
        
        // 綁定事件
        div.querySelector('.btn-load').addEventListener('click', () => {
            this.loadVersion(item.id);
        });
        
        div.querySelector('.btn-delete').addEventListener('click', () => {
            if (confirm('確定要刪除這個版本嗎？')) {
                this.deleteVersion(item.id);
            }
        });
        
        return div;
    }
    
    /**
     * 格式化時間
     */
    formatTime(timeString) {
        if (!timeString) return '未知時間';
        
        try {
            const date = new Date(timeString);
            return date.toLocaleString('zh-TW');
        } catch (error) {
            return timeString;
        }
    }
    
    /**
     * 顯示成功消息
     */
    showSuccess(message) {
        this.showMessage(message, 'success');
    }
    
    /**
     * 顯示錯誤消息
     */
    showError(message) {
        this.showMessage(message, 'error');
    }
    
    /**
     * 顯示消息
     */
    showMessage(message, type = 'info') {
        console.log(`📢 ${type.toUpperCase()}: ${message}`);
        
        // 如果有 SaveLoadManager，使用它的消息系統
        if (window.SaveLoadManager && window.SaveLoadManager.showMessage) {
            window.SaveLoadManager.showMessage(message, type);
            return;
        }
        
        // 否則創建簡單的消息提示
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${type}`;
        messageDiv.textContent = message;
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 4px;
            color: white;
            z-index: 10000;
            background-color: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        `;
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 3000);
    }
    
    /**
     * 創建歷史記錄面板
     */
    createHistoryPanel() {
        const panel = document.createElement('div');
        panel.id = 'history-panel';
        panel.className = 'history-panel';
        panel.innerHTML = `
            <div class="panel-header">
                <h3>📚 歷史記錄</h3>
                <div class="panel-actions">
                    <button id="refresh-history" class="btn-refresh">🔄 刷新</button>
                    <button id="save-current" class="btn-save">💾 保存當前</button>
                </div>
            </div>
            <div class="history-list"></div>
        `;
        
        // 綁定事件
        panel.querySelector('#refresh-history').addEventListener('click', () => {
            this.loadHistory(true);
        });
        
        panel.querySelector('#save-current').addEventListener('click', () => {
            if (window.editor) {
                const code = window.editor.getValue();
                const description = prompt('請輸入保存描述:', `保存 ${new Date().toLocaleString()}`);
                if (description !== null) {
                    this.saveVersion(code, description);
                }
            }
        });
        
        return panel;
    }
}

// 全局實例
window.HTTPHistoryManager = HTTPHistoryManager;

// 自動初始化
document.addEventListener('DOMContentLoaded', () => {
    if (!window.httpHistoryManager) {
        window.httpHistoryManager = new HTTPHistoryManager();
        console.log('✅ HTTP 歷史記錄管理器已初始化');
    }
});

// 導出類
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HTTPHistoryManager;
} 