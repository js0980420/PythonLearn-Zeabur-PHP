// 表單管理器 - 簡化登入流程
class FormManager {
    constructor() {
        this.defaultRooms = [
            'general-room',  // 預設房間
            'custom-room'    // 自定義房間
        ];
        
        this.storageKey = 'pythonlearn_usernames';
        this.maxStoredUsernames = 10; // 最多記住10個用戶名
        
        console.log('📝 表單管理器初始化');
    }
    
    /**
     * 初始化表單功能
     */
    init() {
        this.setupRoomOptions();
        this.setupUsernameDropdown();
        this.setupFormValidation();
        console.log('✅ 表單管理器已初始化');
    }
    
    /**
     * 設置房間選項
     */
    setupRoomOptions() {
        const roomSelect = document.getElementById('roomSelect');
        if (!roomSelect) return;
        
        // 清空現有選項
        roomSelect.innerHTML = '';
        
        // 添加預設房間選項
        const defaultOption = document.createElement('option');
        defaultOption.value = 'general-room';
        defaultOption.textContent = 'general-room (預設房間)';
        roomSelect.appendChild(defaultOption);
        
        // 添加自定義房間選項
        const customOption = document.createElement('option');
        customOption.value = 'custom-room';
        customOption.textContent = 'custom-room (自定義房間)';
        roomSelect.appendChild(customOption);
        
        // 設置默認選中預設房間
        roomSelect.value = 'general-room';
        
        console.log('🏠 房間選項已設置');
    }
    
    /**
     * 設置用戶名稱下拉選單
     */
    setupUsernameDropdown() {
        const usernameInput = document.getElementById('username');
        if (!usernameInput) return;

        // 創建datalist元素
        const datalist = document.createElement('datalist');
        datalist.id = 'username-history';
        
        // 設置input的list屬性
        usernameInput.setAttribute('list', 'username-history');
        
        // 插入到input後面
        usernameInput.parentNode.insertBefore(datalist, usernameInput.nextSibling);
        
        // 載入歷史用戶名
        this.loadUsernameHistory();
        
        console.log('📝 用戶名稱下拉選單已設置');
    }
    
    /**
     * 載入歷史用戶名
     */
    loadUsernameHistory() {
        const datalist = document.getElementById('username-history');
        if (!datalist) return;
            
        const storedUsernames = this.getStoredUsernames();
        
        // 清空現有選項
        datalist.innerHTML = '';
        
        // 添加歷史用戶名選項
        storedUsernames.forEach(username => {
            const option = document.createElement('option');
            option.value = username;
            datalist.appendChild(option);
        });
        
        console.log(`📚 載入了 ${storedUsernames.length} 個歷史用戶名`);
    }

    /**
     * 獲取存儲的用戶名列表
     */
    getStoredUsernames() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.warn('⚠️ 讀取用戶名歷史失敗:', error);
            return [];
        }
    }
    
    /**
     * 保存用戶名到歷史記錄
     */
    saveUsername(username) {
        if (!username || username.trim().length < 2) return;
        
        username = username.trim();
        
        try {
            let usernames = this.getStoredUsernames();
        
            // 移除重複的用戶名（如果存在）
            usernames = usernames.filter(name => name !== username);
            
            // 添加到開頭
            usernames.unshift(username);
            
            // 限制數量
            if (usernames.length > this.maxStoredUsernames) {
                usernames = usernames.slice(0, this.maxStoredUsernames);
            }
            
            // 保存到localStorage
            localStorage.setItem(this.storageKey, JSON.stringify(usernames));
            
            // 更新下拉選單
            this.loadUsernameHistory();
            
            console.log(`💾 用戶名 "${username}" 已保存到歷史記錄`);
        } catch (error) {
            console.warn('⚠️ 保存用戶名失敗:', error);
        }
    }
    
    /**
     * 設置表單驗證
     */
    setupFormValidation() {
        const usernameInput = document.getElementById('username');
        if (usernameInput) {
            usernameInput.placeholder = '請輸入您的用戶名稱';
            usernameInput.value = '';  // 清空默認值
            
            // 添加輸入驗證
            usernameInput.addEventListener('input', function() {
                const value = this.value.trim();
                if (value.length < 2) {
                    this.setCustomValidity('用戶名稱至少需要2個字符');
        } else {
                    this.setCustomValidity('');
        }
            });
        }
        
        // 設置用戶類型默認為學生
        const userTypeSelect = document.getElementById('userType');
        if (userTypeSelect) {
            userTypeSelect.value = 'student';
        }
        
        console.log('✅ 表單驗證已設置');
    }
    
    /**
     * 獲取表單數據
     */
    getFormData() {
        // 使用getCurrentRoomName函數獲取房間名稱（如果存在）
        let roomName;
        if (typeof getCurrentRoomName === 'function') {
            roomName = getCurrentRoomName();
        } else {
            roomName = document.getElementById('roomSelect')?.value || 'general-room';
    }
    
        const username = document.getElementById('username')?.value?.trim() || '';
        const userType = document.getElementById('userType')?.value || 'student';
        
        return {
            roomName,
            username,
            userType
        };
    }
    
    /**
     * 驗證表單
     */
    validateForm() {
        const data = this.getFormData();
        
        if (!data.username || data.username.length < 2) {
            alert('請輸入有效的用戶名稱（至少2個字符）');
            return false;
        }
        
        if (!data.roomName) {
            alert('請選擇房間');
            return false;
        }
        
        // 保存用戶名到歷史記錄
        this.saveUsername(data.username);
        
        return true;
    }
    
    /**
     * 顯示用戶名輸入提示（用於處理用戶名重複）
     */
    showUsernameInput(message, suggestedName = '') {
        console.log('⚠️ 顯示用戶名重複提示:', message);
        
        // 創建模態對話框
        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            用戶名稱重複
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">${message}</p>
                        <div class="mb-3">
                            <label for="newUsername" class="form-label">請輸入新的用戶名稱：</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="newUsername" 
                                   value="${suggestedName}"
                                   placeholder="輸入新的用戶名稱"
                                   maxlength="20">
                            <div class="form-text">用戶名稱長度需要2-20個字符</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelBtn">取消</button>
                        <button type="button" class="btn btn-primary" id="confirmBtn">確認</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const newUsernameInput = modal.querySelector('#newUsername');
        const confirmBtn = modal.querySelector('#confirmBtn');
        const cancelBtn = modal.querySelector('#cancelBtn');
        
        // 自動選中建議的用戶名
        newUsernameInput.select();
        newUsernameInput.focus();
        
        // 輸入驗證
        newUsernameInput.addEventListener('input', function() {
            const value = this.value.trim();
            const isValid = value.length >= 2 && value.length <= 20;
            confirmBtn.disabled = !isValid;
            
            if (value.length < 2) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // 按 Enter 確認
        newUsernameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !confirmBtn.disabled) {
                confirmBtn.click();
            }
        });
        
        // 確認按鈕
        confirmBtn.addEventListener('click', () => {
            const newUsername = newUsernameInput.value.trim();
            if (newUsername.length >= 2 && newUsername.length <= 20) {
                // 更新用戶名輸入框
                const usernameInput = document.getElementById('username');
                if (usernameInput) {
                    usernameInput.value = newUsername;
                }
                
                // 保存到歷史記錄
                this.saveUsername(newUsername);
                
                // 重新嘗試連接
                if (window.wsManager) {
                    window.wsManager.currentUser = newUsername;
                    window.wsManager.connect(window.wsManager.currentRoom, newUsername);
                }
                
                // 關閉模態框
                document.body.removeChild(modal);
                
                console.log(`✅ 用戶名已更改為: ${newUsername}`);
            }
        });
        
        // 取消按鈕
        cancelBtn.addEventListener('click', () => {
            document.body.removeChild(modal);
            console.log('❌ 用戶取消更改用戶名');
        });
        
        // 點擊背景關閉
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }
}

// 全域實例
window.FormManager = new FormManager();

// 頁面載入完成後初始化
document.addEventListener('DOMContentLoaded', function() {
    if (window.FormManager) {
        window.FormManager.init();
    }
});

// 導出給其他模組使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormManager;
} 