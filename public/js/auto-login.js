// 自動登入管理器 - 設置默認用戶"艾克斯王"
class AutoLoginManager {
    constructor() {
        this.defaultUser = {
            id: 1,
            username: 'Alex Wang',
            display_name: '艾克斯王',
            user_type: 'student'
        };
        
        this.defaultRoom = 'test-room';
        
        console.log('🔐 自動登入管理器初始化');
    }
    
    /**
     * 初始化自動登入
     */
    initialize() {
        // 設置默認值
        this.setDefaultValues();
        
        // 設置用戶會話
        this.setUserSession();
        
        // 添加快速登入按鈕
        this.addQuickLoginButton();
        
        console.log('✅ 自動登入設置完成');
    }
    
    /**
     * 設置表單默認值
     */
    setDefaultValues() {
        const roomInput = document.getElementById('roomInput');
        const nameInput = document.getElementById('nameInput');
        
        if (roomInput && !roomInput.value) {
            roomInput.value = this.defaultRoom;
        }
        
        if (nameInput && !nameInput.value) {
            nameInput.value = this.defaultUser.username;
        }
    }
    
    /**
     * 設置用戶會話信息
     */
    setUserSession() {
        // 設置本地存儲
        localStorage.setItem('default_user', JSON.stringify(this.defaultUser));
        localStorage.setItem('default_room', this.defaultRoom);
        
        // 如果有session API，也設置session
        if (typeof fetch !== 'undefined') {
            this.setServerSession();
        }
    }
    
    /**
     * 設置服務器端會話
     */
    async setServerSession() {
        try {
            // 根據環境決定 API URL
            const apiUrl = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
                ? `http://${window.location.host}/api.php`
                : '/api.php';
            
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'login',
                    username: this.defaultUser.username,
                    user_type: this.defaultUser.user_type
                })
            });
            
            const result = await response.json();
            
            if (result && result.success) {
                console.log('✅ 服務器端會話設置成功:', result.data);
                
                // 安全地更新用戶ID
                if (result.data && result.data.user_id) {
                    this.defaultUser.id = result.data.user_id;
                    localStorage.setItem('default_user', JSON.stringify(this.defaultUser));
                }
            } else {
                console.warn('⚠️ 服務器端會話設置失敗:', result ? result.message : '無響應數據');
            }
        } catch (error) {
            console.warn('⚠️ 無法設置服務器端會話:', error.message);
        }
    }
    
    /**
     * 添加快速登入按鈕
     */
    addQuickLoginButton() {
        const loginSection = document.getElementById('loginSection');
        if (!loginSection) return;
        
        // 查找按鈕容器
        const buttonContainer = loginSection.querySelector('.col-md-6');
        if (!buttonContainer) return;
        
        // 創建快速登入按鈕
        const quickLoginBtn = document.createElement('button');
        quickLoginBtn.className = 'btn btn-success w-100 mb-2';
        quickLoginBtn.innerHTML = '<i class="fas fa-bolt"></i> 快速登入 (艾克斯王)';
        quickLoginBtn.onclick = () => this.quickLogin();
        
        // 插入到主登入按鈕之前
        const mainLoginBtn = buttonContainer.querySelector('.btn-primary');
        if (mainLoginBtn) {
            buttonContainer.insertBefore(quickLoginBtn, mainLoginBtn);
        }
    }
    
    /**
     * 快速登入
     */
    quickLogin() {
        const roomInput = document.getElementById('roomInput');
        const nameInput = document.getElementById('nameInput');
        
        if (roomInput) roomInput.value = this.defaultRoom;
        if (nameInput) nameInput.value = this.defaultUser.username;
        
        // 觸發登入
        if (window.globalJoinRoom) {
            console.log('🚀 執行快速登入...');
            window.globalJoinRoom();
        } else {
            console.error('❌ globalJoinRoom 函數不可用');
        }
    }
    
    /**
     * 獲取當前用戶信息
     */
    getCurrentUser() {
        const stored = localStorage.getItem('default_user');
        return stored ? JSON.parse(stored) : this.defaultUser;
    }
    
    /**
     * 獲取默認房間
     */
    getDefaultRoom() {
        return localStorage.getItem('default_room') || this.defaultRoom;
    }
    
    /**
     * 檢查是否已登入
     */
    isLoggedIn() {
        return localStorage.getItem('default_user') !== null;
    }
    
    /**
     * 自動填充用戶信息到其他表單
     */
    fillUserInfo() {
        const user = this.getCurrentUser();
        
        // 填充所有可能的用戶名輸入框
        const userInputs = document.querySelectorAll('input[name="username"], input[id*="user"], input[id*="name"]');
        userInputs.forEach(input => {
            if (!input.value && input.type === 'text') {
                input.value = user.username;
            }
        });
        
        // 填充用戶ID隱藏字段
        const userIdInputs = document.querySelectorAll('input[name="user_id"], input[id*="userId"]');
        userIdInputs.forEach(input => {
            if (!input.value) {
                input.value = user.id;
            }
        });
    }
}

// 創建全局實例
const AutoLogin = new AutoLoginManager();

// 頁面加載完成後初始化
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 頁面加載完成，初始化自動登入...');
    
    // 延遲初始化，確保其他腳本已加載
    setTimeout(() => {
        AutoLogin.initialize();
    }, 500);
});

// 導出到全局作用域
window.AutoLogin = AutoLogin; 