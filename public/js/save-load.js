// save-load.js - 保存載入功能管理器（簡化版 + 槽位命名優化）
console.log('📄 載入 save-load.js 模組');

class SaveLoadManager {
    constructor() {
        this.currentUser = null;
        this.roomId = null;
        this.isInitialized = false;
        
        // 內存保存系統 - 優化版本
        this.memorySlots = {
            0: { code: '', name: '最新', timestamp: null, isCustomNamed: false },
            1: { code: '', name: '槽位 1', timestamp: null, isCustomNamed: false },
            2: { code: '', name: '槽位 2', timestamp: null, isCustomNamed: false },
            3: { code: '', name: '槽位 3', timestamp: null, isCustomNamed: false },
            4: { code: '', name: '槽位 4', timestamp: null, isCustomNamed: false }
        };
        
        console.log('💾 SaveLoadManager 初始化（槽位命名版）');
        this.initializeEventListeners();
        
        // 立即嘗試載入本地數據並更新UI
        this.loadSlotsFromStorage();
        this.updateAllDropdownsUI();
        
        // 設置為已初始化狀態，允許基本功能使用
        this.isInitialized = true;
        this.currentUser = 'LocalUser';
        this.roomId = 'local-room';
    }

    // 初始化事件監聽器
    initializeEventListeners() {
        // 監聽頁面載入完成
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.loadSlotsFromStorage();
                this.updateAllDropdownsUI();
            });
        } else {
            // 頁面已載入完成，延遲更新UI確保DOM元素存在
            setTimeout(() => {
                this.loadSlotsFromStorage();
                this.updateAllDropdownsUI();
            }, 500);
        }
        
        // 定期更新UI，確保下拉選單正確顯示
        setInterval(() => {
            this.loadSlotsFromStorage();
            this.updateAllDropdownsUI();
        }, 5000);
        
        // 監聽存儲變化事件，確保多標籤頁同步
        window.addEventListener('storage', (e) => {
            if (e.key === 'python_code_slots' || e.key === 'python_code_latest') {
                console.log('📦 檢測到存儲變化，重新載入數據');
                this.loadSlotsFromStorage();
                this.updateAllDropdownsUI();
            }
        });
    }

    // 顯示提示訊息的備用函數
    showMessage(message, type = 'info') {
        if (window.UI) {
            // 使用 UI 模組的提示方法
            if (type === 'success') {
                window.UI.showSuccessToast(message);
            } else if (type === 'error') {
                window.UI.showErrorToast(message);
            } else if (type === 'warning') {
                window.UI.showWarningToast(message);
            } else {
                window.UI.showInfoToast(message);
            }
        } else {
            // 備用方案：使用 console 和 alert
            console.log(`${type.toUpperCase()}: ${message}`);
            if (type === 'error' || type === 'warning') {
                alert(message);
            } else if (type === 'success') {
                console.log(`✅ ${message}`);
            }
        }
    }

    // 初始化
    init(userId, roomId) {
        this.currentUser = userId;
        this.userId = userId;
        this.roomId = roomId;
        this.isInitialized = true;
        console.log(`💾 SaveLoadManager 已初始化 - 用戶: ${userId}, 房間: ${roomId}`);
        
        // 從 localStorage 載入槽位數據
        this.loadSlotsFromStorage();
        
        // 更新UI
        this.updateAllDropdownsUI();
        
        console.log('💾 SaveLoadManager 使用內存模式，跳過歷史記錄載入');
    }

    // 從本地存儲載入槽位數據
    loadSlotsFromStorage() {
        try {
            const savedSlots = localStorage.getItem('python_code_slots');
            if (savedSlots) {
                const slots = JSON.parse(savedSlots);
                // 合併保存的數據，保持結構完整性
                for (let i = 0; i <= 4; i++) {
                    if (slots[i]) {
                        this.memorySlots[i] = {
                            ...this.memorySlots[i],
                            ...slots[i]
                        };
                    }
                }
                console.log('💾 已從本地存儲載入槽位數據');
            }
            
            // 特別處理最新版本
            const latestCode = localStorage.getItem('python_code_latest');
            const latestTimestamp = localStorage.getItem('python_code_latest_timestamp');
            if (latestCode) {
                this.memorySlots[0] = {
                    ...this.memorySlots[0],
                    code: latestCode,
                    timestamp: latestTimestamp ? parseInt(latestTimestamp) : Date.now()
                };
                console.log('💾 已載入最新版本代碼');
            }
        } catch (error) {
            console.error('載入槽位數據失敗:', error);
        }
    }

    // 保存槽位數據到本地存儲
    saveSlotsToStorage() {
        try {
            localStorage.setItem('python_code_slots', JSON.stringify(this.memorySlots));
            console.log('💾 槽位數據已保存到本地存儲');
        } catch (error) {
            console.error('保存槽位數據失敗:', error);
        }
    }

    // 檢查是否已初始化
    checkInitialized() {
        if (!this.isInitialized) {
            const message = "SaveLoadManager尚未初始化。請先加入房間。";
            console.warn(message);
            this.showMessage(message, 'warning');
            return false;
        }
        return true;
    }

    // 保存當前代碼到最新
    saveCode() {
        console.log("💾 開始保存代碼到最新");
        if (!window.Editor) {
            this.showMessage("編輯器未準備好，無法保存。", "error");
            return;
        }
        
        const code = window.Editor.getCode();
        if (!code || code.trim() === '') {
            this.showMessage('程式碼內容為空，無法保存', 'warning');
            return;
        }

        // 直接保存到槽位 0（最新）
        this.saveToLatest(code);
    }

    // 保存到最新槽位
    saveToLatest(code) {
        try {
            // 保存到內存槽位 0
            this.memorySlots[0] = {
                ...this.memorySlots[0],
                code: code,
                timestamp: Date.now()
            };

            // 也保存到 localStorage 作為備份
            localStorage.setItem('python_code_latest', code);
            localStorage.setItem('python_code_latest_timestamp', Date.now().toString());
            
            // 保存所有槽位數據
            this.saveSlotsToStorage();
            
            console.log(`💾 代碼已保存到最新版本，長度: ${code.length} 字符`);
            this.showMessage('✅ 代碼已保存到最新版本', 'success');
            
            // 立即更新UI並強制刷新
            setTimeout(() => {
                this.updateAllDropdownsUI();
            }, 100);
            
            // 再次延遲更新確保同步
            setTimeout(() => {
                this.updateAllDropdownsUI();
            }, 1000);
        } catch (error) {
            console.error('保存失敗:', error);
            this.showMessage('❌ 保存失敗: ' + error.message, 'error');
        }
    }

    // 保存到指定槽位（從下拉選單調用）
    saveToSlot(slotId) {
        console.log(`💾 開始保存到槽位 ${slotId}`);
        
        if (!window.Editor) {
            this.showMessage("編輯器未準備好，無法保存。", "error");
            return;
        }
        
        const code = window.Editor.getCode();
        if (!code || code.trim() === '') {
            this.showMessage('程式碼內容為空，無法保存', 'warning');
            return;
        }

        const currentSlot = this.memorySlots[slotId];
        const isEmpty = !currentSlot.code || currentSlot.code.trim() === '';
        
        if (isEmpty && !currentSlot.isCustomNamed) {
            // 空槽位且未自定義命名，提示用戶命名
            this.promptSlotNaming(slotId, code);
        } else {
            // 已有內容或已命名，直接保存
            this.executeSaveToSlot(slotId, currentSlot.name, code);
        }
    }

    // 提示用戶為槽位命名
    promptSlotNaming(slotId, code) {
        const defaultName = `我的程式 ${new Date().toLocaleDateString()}`;
        
        // 創建美化的命名對話框
        const modalHTML = `
            <div class="modal fade" id="slotNamingModal" tabindex="-1" aria-labelledby="slotNamingModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="slotNamingModalLabel">
                                <i class="fas fa-edit"></i> 為槽位 ${slotId} 命名
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="slotNameInput" class="form-label">槽位名稱</label>
                                <input type="text" class="form-control" id="slotNameInput" 
                                       value="${defaultName}" 
                                       placeholder="輸入一個有意義的名稱..."
                                       maxlength="30">
                                <div class="form-text">
                                    建議使用描述性名稱，例如：「作業1完成版」、「測試版本」等
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">程式碼預覽</label>
                                <pre class="bg-light p-2 rounded border" style="max-height: 120px; overflow-y: auto; font-size: 0.85em;">${this.escapeHtml(code.substring(0, 200))}${code.length > 200 ? '...' : ''}</pre>
                                <small class="text-muted">共 ${code.split('\n').length} 行，${code.length} 字符</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" onclick="window.SaveLoadManager.confirmSlotNaming(${slotId})">
                                <i class="fas fa-save"></i> 保存
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 移除舊的模態框
        const existingModal = document.getElementById('slotNamingModal');
        if (existingModal) {
            existingModal.remove();
        }

        // 添加新的模態框
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // 顯示模態框
        const modal = new bootstrap.Modal(document.getElementById('slotNamingModal'));
        modal.show();
        
        // 自動選中輸入框內容
        setTimeout(() => {
            const input = document.getElementById('slotNameInput');
            if (input) {
                input.select();
                input.focus();
            }
        }, 300);
    }

    // 確認槽位命名
    confirmSlotNaming(slotId) {
        const nameInput = document.getElementById('slotNameInput');
        const slotName = nameInput ? nameInput.value.trim() : '';
        
        if (!slotName) {
            this.showMessage('請輸入槽位名稱', 'warning');
            return;
        }
        
        // 關閉模態框
        const modal = bootstrap.Modal.getInstance(document.getElementById('slotNamingModal'));
        if (modal) {
            modal.hide();
        }
        
        // 直接從編輯器獲取當前代碼
        let actualCode = '';
        if (window.Editor && typeof window.Editor.getCode === 'function') {
            actualCode = window.Editor.getCode();
        }
        
        if (!actualCode || actualCode.trim() === '') {
            this.showMessage('程式碼內容為空，無法保存', 'warning');
            return;
        }
        
        // 執行保存
        this.executeSaveToSlot(slotId, slotName, actualCode, true);
    }

    // 執行保存到槽位
    executeSaveToSlot(slotId, saveName, code, isCustomNamed = false) {
        console.log(`💾 執行保存到槽位 ${slotId}: ${saveName}`);
        
        try {
            // 保存到內存
            this.memorySlots[slotId] = {
                code: code,
                name: saveName,
                timestamp: Date.now(),
                isCustomNamed: isCustomNamed || (this.memorySlots[slotId] && this.memorySlots[slotId].isCustomNamed)
            };
            
            // 保存到本地存儲
            this.saveSlotsToStorage();
            
            console.log(`✅ 已保存到內存槽位 ${slotId}，代碼長度: ${code.length} 字符`);
            this.showMessage(`已保存到「${saveName}」`, 'success');
            
            // 立即更新UI並強制刷新
            setTimeout(() => {
                this.updateAllDropdownsUI();
            }, 100);
            
            // 再次延遲更新確保同步
            setTimeout(() => {
                this.updateAllDropdownsUI();
            }, 1000);
            
            // 如果有 WebSocket 連接，也同步到服務器
            if (window.WebSocketManager && window.WebSocketManager.isConnected()) {
                const saveData = {
                    type: 'save_code',
                    room_id: this.roomId,
                    user_id: this.currentUser,
                    username: this.currentUser,
                    code: code,
                    slot_id: slotId,
                    save_name: saveName,
                    timestamp: Date.now()
                };
                window.WebSocketManager.sendMessage(saveData);
                console.log('📤 同步保存到服務器');
            }
        } catch (error) {
            console.error('保存失敗:', error);
            this.showMessage('❌ 保存失敗: ' + error.message, 'error');
        }
    }

    // 更新保存下拉選單UI
    updateSaveDropdownUI() {
        const saveDropdown = document.getElementById('saveCodeOptions');
        if (!saveDropdown) {
            console.log('📋 保存下拉選單元素未找到，延遲更新');
            return;
        }
        
        // 清空現有槽位項目
        const existingSlots = saveDropdown.querySelectorAll('.slot-item');
        existingSlots.forEach(item => item.remove());
        
        // 強制重新載入本地數據確保同步
        this.loadSlotsFromStorage();
        
        // 重新生成槽位項目
        for (let i = 1; i <= 4; i++) {
            const slot = this.memorySlots[i];
            const isEmpty = !slot.code || slot.code.trim() === '';
            const isCustomNamed = slot.isCustomNamed;
            
            const slotItem = document.createElement('li');
            slotItem.className = 'slot-item';
            
            const iconClass = isEmpty ? 'fas fa-plus-circle text-muted' : 
                             isCustomNamed ? 'fas fa-bookmark text-warning' : 'fas fa-folder text-info';
            const slotText = isEmpty ? `保存到槽位 ${i}` : slot.name;
            const slotSubtext = isEmpty ? '空槽位' : 
                               `${new Date(slot.timestamp).toLocaleDateString()} · ${slot.code.split('\n').length}行`;
            
            slotItem.innerHTML = `
                <a class="dropdown-item ${isEmpty ? '' : 'fw-bold'}" href="#" onclick="window.SaveLoadManager.saveToSlot(${i}); event.preventDefault(); return false;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="${iconClass}"></i> ${slotText}
                            ${isEmpty ? '' : `<br><small class="text-muted">${slotSubtext}</small>`}
                        </div>
                        ${isEmpty ? '<i class="fas fa-plus text-success ms-2"></i>' : 
                                   '<i class="fas fa-check text-success ms-2"></i>'}
                    </div>
                </a>
            `;
            
            saveDropdown.appendChild(slotItem);
        }
        
        console.log(`📋 保存下拉選單已更新，共 ${4} 個槽位`);
    }

    // 更新載入下拉選單UI
    updateLoadDropdownUI() {
        const loadDropdown = document.getElementById('loadCodeOptions');
        if (!loadDropdown) {
            console.log('📋 載入下拉選單元素未找到，延遲更新');
            return;
        }
        
        // 清空現有槽位項目
        const existingSlots = loadDropdown.querySelectorAll('.load-slot-item');
        existingSlots.forEach(item => item.remove());
        
        // 檢查是否有可載入的槽位
        let hasLoadableSlots = false;
        
        // 強制重新載入本地數據確保同步
        this.loadSlotsFromStorage();
        
        // 檢查所有槽位（包括槽位0-最新）
        for (let i = 0; i <= 4; i++) {
            const slot = this.memorySlots[i];
            const hasCode = slot.code && slot.code.trim() !== '';
            
            if (hasCode) {
                hasLoadableSlots = true;
                
                const slotItem = document.createElement('li');
                slotItem.className = 'load-slot-item';
                
                const iconClass = i === 0 ? 'fas fa-star text-warning' : 
                                 slot.isCustomNamed ? 'fas fa-bookmark text-primary' : 'fas fa-folder text-info';
                const slotText = slot.name;
                const slotSubtext = `${new Date(slot.timestamp).toLocaleDateString()} · ${slot.code.split('\n').length}行`;
                
                slotItem.innerHTML = `
                    <a class="dropdown-item" href="#" onclick="window.SaveLoadManager.loadCode(${i}); event.preventDefault(); return false;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="${iconClass}"></i> ${slotText}
                                <br><small class="text-muted">${slotSubtext}</small>
                            </div>
                            <i class="fas fa-sync-alt text-primary ms-2"></i>
                        </div>
                    </a>
                `;
                
                loadDropdown.appendChild(slotItem);
            }
        }
        
        // 如果沒有可載入的槽位，顯示提示
        if (!hasLoadableSlots) {
            // 移除現有的空槽位提示
            const existingEmpty = loadDropdown.querySelector('.empty-slots-message');
            if (existingEmpty) {
                existingEmpty.remove();
            }
            
            const emptyItem = document.createElement('li');
            emptyItem.className = 'load-slot-item empty-slots-message';
            emptyItem.innerHTML = `
                <span class="dropdown-item-text text-muted">
                    <i class="fas fa-info-circle"></i> 暫無已保存的程式碼
                </span>
            `;
            loadDropdown.appendChild(emptyItem);
        } else {
            // 移除空槽位提示
            const existingEmpty = loadDropdown.querySelector('.empty-slots-message');
            if (existingEmpty) {
                existingEmpty.remove();
            }
        }
        
        console.log(`📋 載入下拉選單已更新，共 ${hasLoadableSlots ? Object.keys(this.memorySlots).filter(k => this.memorySlots[k].code).length : 0} 個可載入槽位`);
    }

    // 更新所有下拉選單UI
    updateAllDropdownsUI() {
        this.updateSaveDropdownUI();
        this.updateLoadDropdownUI();
    }

    // 載入代碼
    loadCode(loadType = 'latest') {
        console.log(`📖 載入代碼: ${loadType}`);
        
        if (!window.Editor) {
            this.showMessage('編輯器未準備好', 'error');
            return;
        }

        // 強制重新載入本地數據確保同步
        this.loadSlotsFromStorage();

        let codeToLoad = '';
        let sourceName = '';
        let slotId = loadType;

        if (loadType === 'latest') {
            slotId = 0;
        }

        console.log(`📖 嘗試載入槽位 ${slotId}`);
        
        if (typeof slotId === 'number' && this.memorySlots[slotId]) {
            const slot = this.memorySlots[slotId];
            console.log(`📖 槽位 ${slotId} 數據:`, {
                hasCode: !!(slot.code && slot.code.trim() !== ''),
                codeLength: slot.code ? slot.code.length : 0,
                name: slot.name,
                timestamp: slot.timestamp
            });
            
            if (slot.code && slot.code.trim() !== '') {
                codeToLoad = slot.code;
                sourceName = slot.name;
            }
        } else {
            console.log(`📖 槽位 ${slotId} 不存在或無效`);
        }

        if (codeToLoad) {
            window.Editor.setCode(codeToLoad);
            this.showMessage(`✅ 已載入「${sourceName}」`, 'success');
            console.log(`📖 已載入代碼從「${sourceName}」，共 ${codeToLoad.length} 字符`);
            
            // 載入後更新UI
            setTimeout(() => {
                this.updateAllDropdownsUI();
            }, 100);
        } else {
            this.showMessage('❌ 該槽位沒有已保存的代碼', 'warning');
            console.log(`📖 槽位 ${slotId} 未找到可載入的代碼`);
            
            // 顯示所有槽位狀態用於調試
            console.log('📖 當前所有槽位狀態:', this.memorySlots);
        }
    }

    // 請求歷史記錄（簡化版）
    requestHistory(callback) {
        console.log("📜 使用內存模式，跳過歷史記錄請求");
        // 直接返回空數據，避免 API 調用
        if (callback) callback([]);
    }

    // HTML 轉義
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 更新歷史記錄下拉選單
    updateHistoryDropdown(history) {
        console.log('📋 更新歷史記錄下拉選單', history);
        
        // 查找歷史記錄下拉選單元素 (多種可能的ID)
        const historySelect = document.getElementById('historySelect') || 
                             document.getElementById('history-select') ||
                             document.querySelector('.history-dropdown select');
        
        if (!historySelect) {
            console.log('📋 未找到歷史記錄下拉選單元素，跳過更新');
            return;
        }
        
        // 清空現有選項
        historySelect.innerHTML = '<option value="">選擇歷史記錄...</option>';
        
        // 如果沒有歷史記錄，顯示提示
        if (!history || history.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = '暫無歷史記錄';
            option.disabled = true;
            historySelect.appendChild(option);
            return;
        }
        
        // 添加歷史記錄選項
        history.forEach((item, index) => {
            const option = document.createElement('option');
            option.value = index;
            
            // 格式化顯示文本
            const timestamp = item.timestamp ? new Date(item.timestamp).toLocaleString() : '未知時間';
            const title = item.title || `記錄 ${index + 1}`;
            const author = item.author || '未知作者';
            
            option.textContent = `${title} - ${author} (${timestamp})`;
            historySelect.appendChild(option);
        });
    }
}

// 創建全局實例
const saveLoadManagerInstance = new SaveLoadManager();

// 確保全局訪問
if (typeof window !== 'undefined') {
    window.SaveLoadManager = saveLoadManagerInstance;
    
    // 確保在頁面載入完成後初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.SaveLoadManager = saveLoadManagerInstance;
            console.log('✅ SaveLoadManager 在DOM載入後重新綁定');
        });
    }
}

console.log('✅ SaveLoadManager 模組載入完成'); 