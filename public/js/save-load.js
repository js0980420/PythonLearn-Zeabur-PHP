// save-load.js - 保存載入功能管理器
console.log('📄 載入 save-load.js 模組');

class SaveLoadManager {
    constructor() {
        this.currentUser = null;
        this.roomId = null;
        this.isInitialized = false;
        
        console.log('💾 SaveLoadManager 初始化');
    }

    // 顯示提示訊息的備用函數
    showMessage(message, type = 'info') {
        if (window.UI && window.UI.showMessage) {
            window.UI.showMessage(message, type);
        } else {
            // 備用方案：使用 console 和 alert
            console.log(`${type.toUpperCase()}: ${message}`);
            if (type === 'error' || type === 'warning') {
                alert(message);
            }
        }
    }

    // 初始化
    init(user, roomId) {
        this.currentUser = user;
        this.roomId = roomId;
        this.isInitialized = true;
        
        console.log(`💾 SaveLoadManager 已初始化 - 用戶: ${user.name}, 房間: ${roomId}`);
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

    // 保存當前代碼
    saveCode() {
        console.log("💾 開始保存代碼");
        if (!this.checkInitialized() || !window.Editor) {
            this.showMessage("編輯器未準備好或未加入房間，無法保存。", "error");
            return;
        }
        
        const code = window.Editor.getCode();
        if (!code || code.trim() === '') {
            this.showMessage('程式碼內容為空，無法保存', 'warning');
            return;
        }

        // 顯示保存對話框
        this.showSaveDialog(code);
    }

    // 顯示保存對話框
    showSaveDialog(code) {
        const modalHTML = `
            <div class="modal fade" id="saveCodeModal" tabindex="-1" aria-labelledby="saveCodeModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="saveCodeModalLabel">
                                <i class="fas fa-save"></i> 保存程式碼到槽位
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <p class="text-muted">選擇一個槽位來保存您的程式碼：</p>
                                <div class="row g-2" id="slotButtons">
                                    <!-- 槽位按鈕將在這裡動態生成 -->
                                        </div>
                                    </div>
                            
                            <div class="mb-3">
                                <label class="form-label">程式碼預覽</label>
                                <pre class="bg-light p-2 rounded border" style="max-height: 150px; overflow-y: auto; font-size: 0.9em;">${this.escapeHtml(code)}</pre>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 移除舊的模態框
        const existingModal = document.getElementById('saveCodeModal');
        if (existingModal) {
            existingModal.remove();
        }

        // 添加新的模態框
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // 載入並顯示槽位
        this.loadAndDisplaySlots();

        // 顯示模態框
        const modal = new bootstrap.Modal(document.getElementById('saveCodeModal'));
        modal.show();
    }

    // 載入並顯示5個槽位
    loadAndDisplaySlots() {
        console.log("📋 載入5槽位系統...");
        
        // 首先顯示5個預設槽位
        this.displaySlotButtons([
            { slot_id: 0, save_name: '最新', is_empty: false, description: '自動保存最新版本' },
            { slot_id: 1, save_name: '槽位 1', is_empty: true, description: '點擊保存到此槽位' },
            { slot_id: 2, save_name: '槽位 2', is_empty: true, description: '點擊保存到此槽位' },
            { slot_id: 3, save_name: '槽位 3', is_empty: true, description: '點擊保存到此槽位' },
            { slot_id: 4, save_name: '槽位 4', is_empty: true, description: '點擊保存到此槽位' }
        ]);

        // 然後請求真實數據更新槽位
        this.requestHistory((history) => {
            if (history && Array.isArray(history)) {
                this.updateSlotsWithHistory(history);
            }
        });
    }

    // 顯示槽位按鈕
    displaySlotButtons(slots) {
        const slotContainer = document.getElementById('slotButtons');
        if (!slotContainer) return;

        slotContainer.innerHTML = '';

        slots.forEach(slot => {
            const isLatest = slot.slot_id === 0;
            const isEmpty = slot.is_empty && slot.slot_id !== 0;
            const slotName = isEmpty ? `槽位 ${slot.slot_id}` : slot.save_name;
            
            const slotButton = document.createElement('div');
            slotButton.className = 'col-12';
            slotButton.innerHTML = `
                <button type="button" 
                        class="btn ${isLatest ? 'btn-primary' : (isEmpty ? 'btn-outline-secondary' : 'btn-outline-success')} w-100 p-3 text-start slot-btn" 
                        data-slot-id="${slot.slot_id}"
                        onclick="window.SaveLoadManager.selectSlot(${slot.slot_id})">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${slotName}</strong>
                            <br><small class="text-muted">${slot.description || (isEmpty ? '空槽位' : '已保存')}</small>
                        </div>
                        <div>
                            ${isLatest ? '<i class="fas fa-star text-warning"></i>' : 
                              isEmpty ? '<i class="fas fa-plus-circle"></i>' : '<i class="fas fa-save"></i>'}
                        </div>
                    </div>
                </button>
            `;
            
            slotContainer.appendChild(slotButton);
        });
    }

    // 更新槽位顯示（使用從服務器獲取的歷史數據）
    updateSlotsWithHistory(history) {
        const slots = [
            { slot_id: 0, save_name: '最新', is_empty: false, description: '自動保存最新版本' },
            { slot_id: 1, save_name: '槽位 1', is_empty: true, description: '點擊保存到此槽位' },
            { slot_id: 2, save_name: '槽位 2', is_empty: true, description: '點擊保存到此槽位' },
            { slot_id: 3, save_name: '槽位 3', is_empty: true, description: '點擊保存到此槽位' },
            { slot_id: 4, save_name: '槽位 4', is_empty: true, description: '點擊保存到此槽位' }
        ];

        // 更新槽位信息
        history.forEach(item => {
            const slotIndex = slots.findIndex(s => s.slot_id === item.slot_id);
            if (slotIndex !== -1 && item.slot_id !== 0) {
                slots[slotIndex] = {
                    slot_id: item.slot_id,
                    save_name: item.save_name || `槽位 ${item.slot_id}`,
                    is_empty: false,
                    description: `保存於 ${item.created_at || '未知時間'}`
                };
            }
        });

        this.displaySlotButtons(slots);
    }

    // 選擇槽位
    selectSlot(slotId) {
        console.log(`🎯 選擇槽位 ${slotId}`);
        
        if (slotId === 0) {
            // 槽位0直接保存
            this.executeSaveToSlot(0, '最新');
        } else {
            // 槽位1-4需要命名
            this.promptSlotName(slotId);
        }
    }

    // 提示輸入槽位名稱
    promptSlotName(slotId) {
        const currentName = this.getCurrentSlotName(slotId);
        const slotName = prompt(`請為槽位 ${slotId} 輸入名稱：`, currentName || `我的保存 ${slotId}`);
        
        if (slotName !== null && slotName.trim() !== '') {
            this.executeSaveToSlot(slotId, slotName.trim());
        }
    }

    // 獲取當前槽位名稱
    getCurrentSlotName(slotId) {
        const slotButton = document.querySelector(`[data-slot-id="${slotId}"] strong`);
        if (slotButton) {
            const currentText = slotButton.textContent;
            if (currentText && !currentText.startsWith('槽位')) {
                return currentText;
            }
        }
        return null;
    }

    // 執行保存到指定槽位
    executeSaveToSlot(slotId, saveName) {
        if (!window.Editor) {
            this.showMessage("編輯器未準備好，無法保存。", "error");
            return;
        }

        const code = window.Editor.getCode();
        if (!code || code.trim() === '') {
            this.showMessage('程式碼內容為空，無法保存', 'warning');
            return;
        }

        console.log(`💾 保存到槽位 ${slotId}: ${saveName}`);

        // 關閉保存對話框
        const modal = bootstrap.Modal.getInstance(document.getElementById('saveCodeModal'));
        if (modal) {
            modal.hide();
        }

        // 獲取用戶信息，優先使用AutoLogin的用戶信息
        let userInfo = this.currentUser;
        if (window.AutoLogin) {
            const autoLoginUser = window.AutoLogin.getCurrentUser();
            if (autoLoginUser) {
                userInfo = {
                    id: autoLoginUser.id,
                    name: autoLoginUser.username
                };
            }
        }

        // 如果還是沒有用戶信息，使用默認的"Alex Wang"
        if (!userInfo) {
            userInfo = {
                id: 1,
                name: 'Alex Wang'
            };
        }

        // 發送保存請求
        const saveData = {
            type: 'save_code',
            room_id: this.roomId || 'test_room_001',
            user_id: userInfo.id,
            username: userInfo.name,
            code: code,
            slot_id: slotId,
            save_name: saveName,
            timestamp: Date.now()
        };

        if (window.wsManager && window.wsManager.isConnected()) {
            window.wsManager.sendMessage(saveData);
            this.showMessage(`正在保存到槽位 ${slotId}...`, 'info');
        } else {
            this.showMessage('WebSocket 未連接，無法保存', 'error');
        }
    }

    // 載入槽位信息
    // 載入槽位信息並顯示載入對話框
    loadSlotInfo() {
        console.log("📋 載入槽位信息...");
        
        // 請求歷史記錄來創建載入對話框
        this.requestHistory((history) => {
            this.displayLoadSlotDialog(history);
        });
    }

    // 顯示槽位保存對話框
    displaySaveSlotDialog() {
        console.log("💾 顯示保存槽位對話框...");
        
        // 請求歷史記錄來創建保存對話框
        this.requestHistory((history) => {
            this.showSaveSlotDialog(history);
        });
    }

    // 顯示保存槽位選擇對話框
    showSaveSlotDialog(history) {
        // 初始化5個槽位
        const slots = [
            { id: 0, name: '最新版本', hasData: false, timestamp: null, description: '自動保存，無法手動選擇' },
            { id: 1, name: '空槽位 1', hasData: false, timestamp: null, description: '可自定義命名' },
            { id: 2, name: '空槽位 2', hasData: false, timestamp: null, description: '可自定義命名' },
            { id: 3, name: '空槽位 3', hasData: false, timestamp: null, description: '可自定義命名' },
            { id: 4, name: '空槽位 4', hasData: false, timestamp: null, description: '可自定義命名' }
        ];

        // 用歷史記錄更新槽位信息
            if (history && Array.isArray(history)) {
                history.forEach(item => {
                    const slotId = item.slot_id;
                if (slotId >= 0 && slotId <= 4) {
                    slots[slotId].hasData = true;
                    slots[slotId].name = item.save_name || (slotId === 0 ? '最新版本' : `槽位 ${slotId}`);
                    slots[slotId].timestamp = item.created_at;
                    }
                });
            }

        // 生成槽位HTML（保存版本，不包含槽位0）
        const slotsHTML = slots.slice(1).map(slot => {
            const hasData = slot.hasData;
            const timeDisplay = slot.timestamp ? 
                `<small class="text-muted d-block"><i class="fas fa-clock"></i> ${new Date(slot.timestamp).toLocaleString()}</small>` :
                '<small class="text-muted d-block">此槽位為空</small>';
            
            return `
                <div class="card mb-2 ${hasData ? 'border-warning' : 'border-light'} save-slot-card" style="cursor: pointer;" onclick="window.SaveLoadManager.selectSaveSlot(${slot.id})">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <i class="fas ${hasData ? 'fa-file-code' : 'fa-folder-open'} text-${hasData ? 'warning' : 'muted'}"></i>
                                    ${this.escapeHtml(slot.name)}
                                    ${hasData ? '<i class="fas fa-exclamation-triangle text-warning ms-2" title="覆蓋現有內容"></i>' : ''}
                                </h6>
                                ${timeDisplay}
                                <small class="text-info">${slot.description}</small>
                            </div>
                            <div>
                                <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); window.SaveLoadManager.selectSaveSlot(${slot.id})" 
                                        title="${hasData ? '覆蓋此槽位' : '保存到此槽位'}">
                                    <i class="fas ${hasData ? 'fa-sync-alt' : 'fa-save'}"></i> 
                                    ${hasData ? '覆蓋' : '保存'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        const modalHTML = `
            <div class="modal fade" id="saveCodeModal" tabindex="-1" aria-labelledby="saveCodeModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="saveCodeModalLabel">
                                <i class="fas fa-save"></i> 保存程式碼
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 選擇要保存的槽位（槽位0為自動保存，不可手動選擇）
                            </div>
                            <h6 class="mb-3">選擇保存槽位：</h6>
                            ${slotsHTML}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 移除舊的模態框
        const existingModal = document.getElementById('saveCodeModal');
        if (existingModal) {
            existingModal.remove();
        }

        // 添加新的模態框
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // 顯示模態框
        const modal = new bootstrap.Modal(document.getElementById('saveCodeModal'));
        modal.show();
    }

    // 選擇保存槽位
    selectSaveSlot(slotId) {
        if (slotId < 1 || slotId > 4) {
            this.showMessage('只能選擇槽位1-4進行手動保存', 'warning');
            return;
        }

        // 關閉保存模態框
        const modalElement = document.getElementById('saveCodeModal');
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
        }

        // 詢問保存名稱
        const saveName = prompt(`請為槽位 ${slotId} 輸入保存名稱：`, `手動保存 ${new Date().toLocaleString('zh-TW', { hour12: false })}`);
        
        if (saveName === null) {
            console.log('用戶取消保存操作');
            return;
        }

        if (!saveName.trim()) {
            this.showMessage('保存名稱不能為空', 'warning');
            return;
        }

        // 獲取當前代碼並保存
        const code = window.editorManager ? window.editorManager.getCode() : '';

        const saveData = {
            type: 'save_code',
            room_id: window.wsManager ? window.wsManager.currentRoom : '',
            user_id: window.wsManager ? window.wsManager.currentUser : '',
            code: code,
            save_name: saveName.trim(),
            slot_id: slotId
        };

        console.log('💾 保存到槽位:', saveData);

        if (window.wsManager && window.wsManager.isConnected()) {
            window.wsManager.sendMessage(saveData);
            this.showMessage(`正在保存到槽位 ${slotId}...`, 'info');
        } else {
            this.showMessage('WebSocket 未連接，無法保存', 'error');
        }
    }

    // 顯示槽位載入對話框
    displayLoadSlotDialog(history) {
        // 初始化5個空槽位
        const slots = [
            { id: 0, name: '最新版本', hasData: false, timestamp: null, deletable: false },
            { id: 1, name: '空槽位 1', hasData: false, timestamp: null, deletable: true },
            { id: 2, name: '空槽位 2', hasData: false, timestamp: null, deletable: true },
            { id: 3, name: '空槽位 3', hasData: false, timestamp: null, deletable: true },
            { id: 4, name: '空槽位 4', hasData: false, timestamp: null, deletable: true }
        ];

        // 用歷史記錄更新槽位信息
        if (history && Array.isArray(history)) {
            history.forEach(item => {
                const slotId = item.slot_id;
                if (slotId >= 0 && slotId <= 4) {
                    slots[slotId].hasData = true;
                    slots[slotId].name = item.save_name || (slotId === 0 ? '最新版本' : `槽位 ${slotId}`);
                    slots[slotId].timestamp = item.created_at;
                }
            });
        }

        // 生成槽位HTML
        const slotsHTML = slots.map(slot => {
            const hasData = slot.hasData;
            const timeDisplay = slot.timestamp ? 
                `<small class="text-muted d-block"><i class="fas fa-clock"></i> ${new Date(slot.timestamp).toLocaleString()}</small>` :
                '<small class="text-muted d-block">此槽位為空</small>';
            
            return `
                <div class="card mb-2 ${hasData ? 'border-success' : 'border-light'}">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <i class="fas ${hasData ? 'fa-file-code' : 'fa-folder-open'} text-${hasData ? 'success' : 'muted'}"></i>
                                    ${this.escapeHtml(slot.name)}
                                </h6>
                                ${timeDisplay}
                            </div>
                            <div class="btn-group">
                                ${hasData ? `
                                    <button class="btn btn-outline-primary btn-sm" 
                                            onclick="SaveLoadManager.loadSlot(${slot.id})"
                                            title="載入此槽位">
                                        <i class="fas fa-download"></i> 載入
                                    </button>
                                    ${slot.deletable ? `
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="SaveLoadManager.deleteSlot(${slot.id})"
                                                title="刪除此槽位">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    ` : ''}
                                ` : `
                                    <button class="btn btn-outline-secondary btn-sm" disabled>
                                        <i class="fas fa-ban"></i> 空槽位
                                    </button>
                                `}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        const modalHTML = `
            <div class="modal fade" id="loadCodeModal" tabindex="-1" aria-labelledby="loadCodeModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="loadCodeModalLabel">
                                <i class="fas fa-download"></i> 載入程式碼
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <h6 class="mb-3">選擇要載入的槽位：</h6>
                            ${slotsHTML}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 移除舊的模態框
        const existingModal = document.getElementById('loadCodeModal');
        if (existingModal) {
            existingModal.remove();
        }

        // 添加新的模態框
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // 顯示模態框
        const modal = new bootstrap.Modal(document.getElementById('loadCodeModal'));
        modal.show();
    }

    // 載入指定槽位
    loadSlot(slotId) {
        console.log(`📂 載入槽位 ${slotId}`);
        
        const loadData = {
            type: 'load_code',
            slot_id: slotId
        };

        if (window.wsManager && window.wsManager.isConnected()) {
            window.wsManager.sendMessage(loadData);
            
            // 關閉模態框
            const modalElement = document.getElementById('loadCodeModal');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }
            
            this.showMessage(`正在載入槽位 ${slotId}...`, 'info');
        } else {
            this.showMessage('WebSocket 連接未建立，無法載入', 'error');
        }
    }

    // 刪除槽位
    deleteSlot(slotId) {
        if (slotId < 1 || slotId > 4) {
            this.showMessage('只能刪除槽位1-4', 'warning');
            return;
        }
        
        if (!confirm(`確定要刪除槽位 ${slotId} 的記錄嗎？此操作無法撤銷。`)) {
            return;
        }
        
        const deleteData = {
            type: 'delete_slot',
            slot_id: slotId
        };
        
        console.log('🗑️ 發送刪除請求:', deleteData);
        
        if (window.wsManager && window.wsManager.isConnected()) {
            window.wsManager.sendMessage(deleteData);
            this.showMessage(`正在刪除槽位 ${slotId}...`, 'info');
        } else {
            this.showMessage('WebSocket 連接未建立，無法刪除', 'error');
        }
    }

    // 顯示載入對話框 - 5槽位系統
    showLoadDialog() {
        console.log("📂 顯示載入對話框");
        if (!this.checkInitialized()) {
            this.showMessage("未加入房間，無法載入歷史記錄。", "error");
            return;
        }
        // 載入槽位信息
        this.loadSlotInfo();
    }

    // 顯示載入界面
    displayLoadDialog(history) {
        let historyHTML = '';
        
        if (history.length === 0) {
            historyHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-inbox text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2">尚無保存的程式碼</p>
                    <small class="text-muted">請先保存一些程式碼再進行載入</small>
                </div>
            `;
        } else {
            historyHTML = history.map(item => `
                <div class="card mb-2 load-item" data-id="${item.id}">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${this.escapeHtml(item.title)}</h6>
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> ${this.escapeHtml(item.author)}
                                    <i class="fas fa-clock ms-2"></i> ${new Date(item.timestamp).toLocaleString()}
                                    <i class="fas fa-code-branch ms-2"></i> v${item.version}
                                </small>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        程式碼預覽: ${item.code.split('\\n')[0].substring(0, 50)}...
                                    </small>
                                </div>
                            </div>
                            <div class="btn-group-vertical btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" 
                                        onclick="SaveLoadManager.loadSpecificCode('${item.id}')"
                                        title="載入此版本">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn btn-outline-info btn-sm"
                                        onclick="SaveLoadManager.previewCode('${item.id}')"
                                        title="預覽程式碼">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        const modalHTML = `
            <div class="modal fade" id="loadCodeModal" tabindex="-1" aria-labelledby="loadCodeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="loadCodeModalLabel">
                                <i class="fas fa-folder-open"></i> 載入程式碼
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">選擇要載入的程式碼版本</h6>
                                <div>
                                    ${history.length > 0 ? `
                                        <button class="btn btn-success btn-sm" onclick="SaveLoadManager.loadLatestCode()">
                                            <i class="fas fa-star"></i> 載入最新版本
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                            <div style="max-height: 400px; overflow-y: auto;">
                                ${historyHTML}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 移除舊的模態框
        const existingModal = document.getElementById('loadCodeModal');
        if (existingModal) {
            existingModal.remove();
        }

        // 添加新的模態框
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // 顯示模態框
        const modal = new bootstrap.Modal(document.getElementById('loadCodeModal'));
        modal.show();
    }

    // 載入最新版本
    loadLatestCode() {
        console.log('📂 載入最新版本');
        
        const loadData = {
            type: 'load_code',
            roomId: this.roomId,
            loadLatest: true
        };

        this.sendLoadRequest(loadData);
    }

    // 載入特定槽位
    loadSpecificCode(slotId) {
        console.log('📂 載入槽位:', slotId);
        
        const loadData = {
            type: 'load_code',
            roomId: this.roomId,
            slot_id: slotId
        };

        this.sendLoadRequest(loadData);
    }

    // 發送載入請求
    sendLoadRequest(loadData) {
        if (window.wsManager && window.wsManager.isConnected()) {
            window.wsManager.sendMessage(loadData);
            
            // 關閉模態框
            const modalElement = document.getElementById('loadCodeModal');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }
            
            this.showMessage('載入請求已發送...', 'info');
        } else {
            this.showMessage('WebSocket 連接未建立，無法載入', 'error');
        }
    }

    // 預覽程式碼
    previewCode(saveId) {
        console.log('👁️ 預覽程式碼:', saveId);
        
        // 獲取歷史記錄找到對應項目
        this.requestHistory((history) => {
            const item = history.find(h => h.id === saveId);
            if (item) {
                this.showCodePreview(item);
            } else {
                this.showMessage('找不到對應的程式碼版本', 'error');
            }
        });
    }

    // 顯示程式碼預覽
    showCodePreview(item) {
        const modalHTML = `
            <div class="modal fade" id="codePreviewModal" tabindex="-1" aria-labelledby="codePreviewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="codePreviewModalLabel">
                                <i class="fas fa-eye"></i> 程式碼預覽
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <h6>${this.escapeHtml(item.title)}</h6>
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> ${this.escapeHtml(item.author)}
                                    <i class="fas fa-clock ms-2"></i> ${new Date(item.timestamp).toLocaleString()}
                                    <i class="fas fa-code-branch ms-2"></i> 版本 ${item.version}
                                </small>
                            </div>
                            <div class="border rounded">
                                <pre class="p-3 mb-0" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa; font-size: 0.9em;"><code class="language-python">${this.escapeHtml(item.code)}</code></pre>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                            <button type="button" class="btn btn-primary" onclick='window.SaveLoadManager.loadSpecificCode("${item.id}")'>
                                <i class="fas fa-download"></i> 載入此版本
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 移除舊的模態框
        const existingModal = document.getElementById('codePreviewModal');
        if (existingModal) {
            existingModal.remove();
        }

        // 添加新的模態框
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // 顯示模態框
        const modal = new bootstrap.Modal(document.getElementById('codePreviewModal'));
        modal.show();
    }

    // 顯示歷史記錄對話框
    showHistoryDialog() {
        console.log("📜 顯示歷史記錄對話框");
        if (!this.checkInitialized()) {
            this.showMessage("未加入房間，無法顯示歷史記錄。", "error");
            return;
        }
        this.requestHistory((history) => {
            this.displayHistoryDialog(history);
        });
    }

    // 顯示歷史記錄界面
    displayHistoryDialog(history) {
        const stats = this.calculateStats(history);
        
        let historyHTML = '';
        if (history.length === 0) {
            historyHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-archive text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2">尚無歷史記錄</p>
                </div>
            `;
        } else {
            historyHTML = history.map((item, index) => `
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary me-2">#${history.length - index}</span>
                                    <h6 class="mb-1">${this.escapeHtml(item.title)}</h6>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> ${this.escapeHtml(item.author)}
                                    <i class="fas fa-clock ms-2"></i> ${new Date(item.timestamp).toLocaleString()}
                                    <i class="fas fa-code-branch ms-2"></i> v${item.version}
                                </small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" 
                                        onclick='window.SaveLoadManager.loadSpecificCode("${item.id}")'
                                        title="載入">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn btn-outline-info"
                                        onclick='window.SaveLoadManager.previewCode("${item.id}")'
                                        title="預覽">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        const modalHTML = `
            <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title" id="historyModalLabel">
                                <i class="fas fa-history"></i> 程式碼歷史記錄
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- 統計信息 -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center py-2">
                                            <h5 class="text-primary mb-1">${stats.total}</h5>
                                            <small class="text-muted">總保存次數</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center py-2">
                                            <h5 class="text-success mb-1">${stats.authors}</h5>
                                            <small class="text-muted">參與人數</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center py-2">
                                            <h5 class="text-info mb-1">${stats.latest}</h5>
                                            <small class="text-muted">最新版本</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 歷史記錄列表 -->
                            <div style="max-height: 400px; overflow-y: auto;">
                                ${historyHTML}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                            ${history.length > 0 ? `
                                <button type="button" class="btn btn-success" onclick="SaveLoadManager.loadLatestCode()">
                                    <i class="fas fa-star"></i> 載入最新版本
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 移除舊的模態框
        const existingModal = document.getElementById('historyModal');
        if (existingModal) {
            existingModal.remove();
        }

        // 添加新的模態框
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // 顯示模態框
        const modal = new bootstrap.Modal(document.getElementById('historyModal'));
        modal.show();
    }

    // 請求歷史記錄
    requestHistory(callback) {
        const requestData = {
            type: 'get_history',
            roomId: this.roomId
        };

        console.log('📚 請求歷史記錄:', requestData);

        // 設置回調函數
        if(callback) {
            this.requestedHistoryCallback = callback;
        }

        if (window.wsManager && window.wsManager.isConnected()) {
            window.wsManager.sendMessage(requestData);
        } else {
            this.showMessage('WebSocket 連接未建立，無法獲取歷史記錄', 'error');
            if(callback) callback([]);
        }
    }

    // 處理來自服務器的消息
    handleMessage(message) {
        console.log('💾 SaveLoadManager 收到消息:', message.type);

        switch (message.type) {
            case 'save_success':
                this.handleSaveSuccess(message);
                break;
            case 'save_error':
                this.handleSaveError(message);
                break;
            case 'code_loaded':
            case 'load_success':  // 向後兼容
                this.handleLoadSuccess(message);
                break;
            case 'load_error':
                this.handleLoadError(message);
                break;
            case 'history_data':
                this.handleHistoryData(message);
                break;
            case 'code_saved_notification':
                this.handleCodeSavedNotification(message);
                break;
            case 'code_loaded_notification':
                this.handleCodeLoadedNotification(message);
                break;
            case 'slot_deleted':
                this.handleSlotDeleted(message);
                break;
            case 'slot_deleted_notification':
                this.handleSlotDeletedNotification(message);
                break;
        }
    }

    // 處理保存成功
    handleSaveSuccess(message) {
        console.log('✅ 程式碼保存成功:', message);
        this.showMessage(message.message || `程式碼已成功保存 (版本 ${message.version || '未知'})`, 'success');
        
        // 自動刷新歷史紀錄
        this.requestHistory((history) => {
            // 如果歷史紀錄對話框是開啟的，就刷新它
            if (document.getElementById('historyModal')?.classList.contains('show')) {
                this.displayHistoryDialog(history);
            }
             // 你可以在這裡更新下拉選單
        });

        // 關閉保存對話框
        const modalElement = document.getElementById('saveCodeModal');
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
        }
    }

    // 處理保存錯誤
    handleSaveError(message) {
        console.error('❌ 程式碼保存失敗:', message);
        this.showMessage(message.error || '保存程式碼時發生錯誤。', 'error');
    }

    // 處理載入成功
    handleLoadSuccess(message) {
        console.log('✅ 程式碼載入成功:', message);
        this.showMessage(message.message || `程式碼已成功載入 (版本 ${message.version || '未知'})`, 'success');
        
        // 確保編輯器存在並設置代碼
        if (window.Editor && typeof window.Editor.setCode === 'function' && message.code !== undefined) {
            window.Editor.setCode(message.code, message.version);
            console.log('📝 編輯器代碼已更新:', message.code.length, '字符');
        } else {
            console.warn('⚠️ 編輯器不可用或代碼為空');
        }
        
        // 關閉所有相關的模態框
        ['loadCodeModal', 'historyModal', 'codePreviewModal'].forEach(modalId => {
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }
        });
    }

    // 處理載入錯誤
    handleLoadError(message) {
        console.error('❌ 程式碼載入失敗:', message);
        this.showMessage(message.error || '載入程式碼時發生錯誤。', 'error');
    }

    // 處理歷史數據
    handleHistoryData(message) {
        console.log('📜 收到歷史記錄:', message);
        if (this.requestedHistoryCallback) {
            this.requestedHistoryCallback(message.history || []);
            this.requestedHistoryCallback = null; // Reset callback
        } else {
            // 如果沒有回調，可以考慮更新一個全局的歷史紀錄列表
            this.updateHistoryDropdown(message.history || []);
        }
    }

    // 處理程式碼保存通知
    handleCodeSavedNotification(message) {
        console.log('🔔 其他用戶保存了代碼:', message);
        const notificationMessage = `${message.userName || message.author || '某位用戶'} 保存了代碼版本 "${message.title || '未命名版本'}"`;
        this.showMessage(notificationMessage, 'info');

        // 其他用戶保存了，也更新一下歷史列表
        this.requestHistory();
    }

    // 處理程式碼載入通知
    handleCodeLoadedNotification(message) {
        console.log('🔔 其他用戶載入了代碼:', message);
        const notificationMessage = `${message.userName || message.author || '某位用戶'} 載入了代碼版本 "${message.title || '未命名版本'}"`;
        this.showMessage(notificationMessage, 'info');
    }

    // 處理槽位刪除成功
    handleSlotDeleted(message) {
        console.log('✅ 槽位刪除成功:', message);
        this.showMessage(message.message || `槽位 ${message.slot_id} 已成功刪除`, 'success');
        
        // 刷新歷史記錄顯示
        this.requestHistory((history) => {
            this.updateHistoryDropdown(history);
            
            // 如果保存對話框是開啟的，也刷新槽位信息
            if (document.getElementById('saveCodeModal')?.classList.contains('show')) {
                this.loadSlotInfo();
            }
        });
    }

    // 處理槽位刪除通知（其他用戶刪除）
    handleSlotDeletedNotification(message) {
        console.log('🔔 其他用戶刪除了槽位:', message);
        const notificationMessage = `${message.username || '某位用戶'} 刪除了槽位 ${message.slot_id}`;
        this.showMessage(notificationMessage, 'info');
        
        // 刷新歷史記錄顯示
        this.requestHistory((history) => {
            this.updateHistoryDropdown(history);
        });
    }

    // 計算統計信息
    calculateStats(history) {
        const authors = new Set(history.map(item => item.author));
        const latestVersion = Math.max(...history.map(item => item.version), 0);
        
        return {
            total: history.length,
            authors: authors.size,
            latest: latestVersion
        };
    }

    // HTML 轉義
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    updateHistoryDropdown(history) {
        const dropdownMenu = document.getElementById('loadCodeOptions');
        if (!dropdownMenu) {
            console.warn('未找到歷史紀錄下拉選單 (loadCodeOptions)');
            return;
        }

        // 保留載入選項的頭部
        const headerHTML = `
            <li><h6 class="dropdown-header">載入選項</h6></li>
            <li><a class="dropdown-item" href="#" onclick="globalLoadCode('latest')">
                <i class="fas fa-sync-alt text-success"></i> 載入最新
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header">保存槽位 (5槽位系統)</h6></li>
        `;

        dropdownMenu.innerHTML = headerHTML;

        if (!history || history.length === 0) {
            dropdownMenu.innerHTML += '<li><span class="dropdown-item-text text-muted">尚無保存記錄</span></li>';
            return;
        }

        // 移除空歷史消息（如果存在）
        const emptyMessage = document.getElementById('historyEmptyMessage');
        if (emptyMessage) {
            emptyMessage.remove();
        }

        // 顯示5個槽位
        history.forEach(item => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.className = 'dropdown-item';
            a.href = '#';
            
            const slotId = item.slot_id;
            const isEmpty = item.is_empty;
            const saveName = item.save_name || `記錄 ${slotId}`;
            
            if (isEmpty) {
                // 空槽位
                a.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            <span class="badge bg-secondary me-2">槽位 ${slotId}</span>
                            ${slotId === 0 ? '最新 (空)' : saveName + ' (空)'}
                        </div>
                        <small class="text-muted">空槽位</small>
                    </div>
                `;
                a.classList.add('disabled');
            } else {
                // 有內容的槽位
                a.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge ${slotId === 0 ? 'bg-primary' : 'bg-info'} me-2">槽位 ${slotId}</span>
                            ${this.escapeHtml(saveName)}
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-user"></i> ${this.escapeHtml(item.username || '未知')}
                                <i class="fas fa-clock ms-2"></i> ${new Date(item.created_at).toLocaleString()}
                            </small>
                        </div>
                    </div>
                `;
                
                a.onclick = (e) => {
                    e.preventDefault();
                    this.loadSpecificCode(slotId);
                };
            }
            
            li.appendChild(a);
            dropdownMenu.appendChild(li);
        });

        console.log(`📚 更新歷史下拉選單，顯示5個槽位`);
    }
}

// 創建全域實例
window.SaveLoadManager = new SaveLoadManager();

document.addEventListener('DOMContentLoaded', () => {
    // 頁面載入後，如果已在房間，可以嘗試獲取一次歷史紀錄
    if (window.wsManager && window.wsManager.isConnected() && window.wsManager.roomId) {
        window.SaveLoadManager.init(window.wsManager.currentUser, window.wsManager.roomId);
        window.SaveLoadManager.requestHistory();
    }
});

console.log('✅ SaveLoadManager 模組載入完成'); 