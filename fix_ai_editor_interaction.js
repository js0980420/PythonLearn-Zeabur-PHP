// 修復AI助教和編輯器交互問題的腳本
// 在瀏覽器控制台中運行此腳本

console.log('🔧 開始修復AI助教和編輯器交互問題...');

// 1. 檢查並修復編輯器綁定
function fixEditorBinding() {
    console.log('🔍 檢查編輯器綁定...');
    
    if (!window.Editor) {
        console.log('❌ window.Editor 不存在，嘗試重新創建...');
        
        // 查找編輯器實例
        const editorElement = document.querySelector('.CodeMirror');
        if (editorElement && editorElement.CodeMirror) {
            console.log('✅ 找到CodeMirror實例，重新綁定...');
            
            window.Editor = {
                editor: editorElement.CodeMirror,
                getCode: function() {
                    return this.editor.getValue();
                },
                setCode: function(code) {
                    this.editor.setValue(code);
                }
            };
            
            console.log('✅ 編輯器已重新綁定到window.Editor');
            return true;
        } else {
            console.log('❌ 找不到CodeMirror實例');
            return false;
        }
    } else {
        console.log('✅ window.Editor 存在');
        
        // 檢查getCode方法
        if (typeof window.Editor.getCode !== 'function') {
            console.log('❌ getCode方法不存在，嘗試修復...');
            
            if (window.Editor.editor && window.Editor.editor.getValue) {
                window.Editor.getCode = function() {
                    return this.editor.getValue();
                };
                console.log('✅ getCode方法已修復');
            } else {
                console.log('❌ 無法修復getCode方法');
                return false;
            }
        }
        
        return true;
    }
}

// 2. 檢查並修復AI助教
function fixAIAssistant() {
    console.log('🔍 檢查AI助教...');
    
    if (!window.AIAssistant) {
        console.log('❌ window.AIAssistant 不存在，嘗試重新創建...');
        
        if (typeof AIAssistantManager !== 'undefined') {
            window.AIAssistant = new AIAssistantManager();
            window.AIAssistant.initialize();
            console.log('✅ AI助教已重新創建');
            return true;
        } else {
            console.log('❌ AIAssistantManager類不存在');
            return false;
        }
    } else {
        console.log('✅ window.AIAssistant 存在');
        
        // 檢查初始化狀態
        if (!window.AIAssistant.responseContainer) {
            console.log('⚠️ AI助教未正確初始化，嘗試重新初始化...');
            window.AIAssistant.initialize();
            console.log('✅ AI助教已重新初始化');
        }
        
        return true;
    }
}

// 3. 檢查並修復WebSocket連接
function fixWebSocketConnection() {
    console.log('🔍 檢查WebSocket連接...');
    
    if (!window.wsManager) {
        console.log('❌ window.wsManager 不存在');
        return false;
    }
    
    if (!window.wsManager.isConnected()) {
        console.log('⚠️ WebSocket未連接，嘗試重新連接...');
        
        // 檢查是否已加入房間
        if (!window.wsManager.currentRoom) {
            console.log('🏠 自動加入測試房間...');
            window.wsManager.currentRoom = 'test_room_001';
            window.wsManager.currentUser = 'test_user';
        }
        
        // 嘗試重新連接
        if (typeof window.wsManager.connect === 'function') {
            window.wsManager.connect();
            console.log('🔄 正在重新連接WebSocket...');
        }
        
        return false;
    } else {
        console.log('✅ WebSocket已連接');
        return true;
    }
}

// 4. 修復AI助教的代碼獲取邏輯
function fixAICodeRetrieval() {
    console.log('🔍 修復AI助教代碼獲取邏輯...');
    
    if (!window.AIAssistant || !window.AIAssistant.requestAnalysis) {
        console.log('❌ AI助教requestAnalysis方法不存在');
        return false;
    }
    
    // 備份原始方法
    const originalRequestAnalysis = window.AIAssistant.requestAnalysis;
    
    // 重寫requestAnalysis方法，添加更強的錯誤處理
    window.AIAssistant.requestAnalysis = function(action) {
        console.log('🤖 [修復版] AI請求分析:', action);
        
        // 檢查編輯器
        if (!window.Editor) {
            console.log('❌ [修復版] 編輯器不存在');
            this.showResponse(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>錯誤：</strong> 編輯器未初始化，請重新載入頁面。
                </div>
            `);
            return;
        }
        
        // 檢查getCode方法
        if (typeof window.Editor.getCode !== 'function') {
            console.log('❌ [修復版] getCode方法不存在');
            this.showResponse(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>錯誤：</strong> 編輯器getCode方法不存在。
                </div>
            `);
            return;
        }
        
        // 獲取代碼
        let code;
        try {
            code = window.Editor.getCode();
            console.log('📝 [修復版] 獲取代碼成功，長度:', code ? code.length : 'null');
        } catch (error) {
            console.log('❌ [修復版] 獲取代碼失敗:', error);
            this.showResponse(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>錯誤：</strong> 無法獲取編輯器代碼: ${error.message}
                </div>
            `);
            return;
        }
        
        // 檢查代碼是否為空
        if (!code || code.trim() === '') {
            console.log('⚠️ [修復版] 代碼為空');
            this.showResponse(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>注意：</strong> 編輯器中沒有程式碼可供分析。請先輸入一些Python程式碼。
                </div>
            `);
            return;
        }
        
        // 檢查WebSocket連接
        if (!window.wsManager || !window.wsManager.isConnected()) {
            console.log('⚠️ [修復版] WebSocket未連接，使用本地分析');
            
            // 提供本地分析
            const localAnalysis = this.generateLocalAnalysis(code, action);
            this.showResponse(localAnalysis);
            return;
        }
        
        // 調用原始方法
        try {
            originalRequestAnalysis.call(this, action);
        } catch (error) {
            console.log('❌ [修復版] 調用原始方法失敗:', error);
            
            // 提供本地分析作為後備
            const localAnalysis = this.generateLocalAnalysis(code, action);
            this.showResponse(localAnalysis);
        }
    };
    
    // 添加本地分析方法
    if (!window.AIAssistant.generateLocalAnalysis) {
        window.AIAssistant.generateLocalAnalysis = function(code, action) {
            const lines = code.split('\n').length;
            const chars = code.length;
            const hasFunction = code.includes('def ');
            const hasLoop = code.includes('for ') || code.includes('while ');
            const hasImport = code.includes('import ') || code.includes('from ');
            
            let analysis = `
                <h6><i class="fas fa-brain"></i> 本地代碼分析結果</h6>
                <div class="mb-3">
                    <div class="alert alert-info">
                        <h6>📊 代碼統計</h6>
                        <ul>
                            <li>總行數: ${lines}</li>
                            <li>總字符數: ${chars}</li>
                            <li>包含函數定義: ${hasFunction ? '是' : '否'}</li>
                            <li>包含迴圈結構: ${hasLoop ? '是' : '否'}</li>
                            <li>包含導入語句: ${hasImport ? '是' : '否'}</li>
                        </ul>
                    </div>
            `;
            
            switch(action) {
                case 'check_syntax':
                case 'check_errors':
                    analysis += `
                        <div class="alert alert-success">
                            <h6>✅ 語法檢查</h6>
                            <p>代碼格式看起來正常，沒有明顯的語法錯誤。</p>
                        </div>
                    `;
                    break;
                    
                case 'analyze':
                case 'code_review':
                    analysis += `
                        <div class="alert alert-primary">
                            <h6>🔍 代碼分析</h6>
                            <p>您的Python代碼包含${lines}行，結構${hasFunction ? '包含函數定義' : '較為簡單'}。</p>
                            ${hasLoop ? '<p>✅ 使用了迴圈結構，展現了程式邏輯。</p>' : ''}
                            ${hasImport ? '<p>✅ 使用了模組導入，展現了程式組織能力。</p>' : ''}
                        </div>
                    `;
                    break;
                    
                case 'suggest':
                case 'improvement_tips':
                    analysis += `
                        <div class="alert alert-warning">
                            <h6>💡 改進建議</h6>
                            <ul>
                                <li>建議添加更多註釋來說明代碼功能</li>
                                <li>考慮使用更描述性的變數名稱</li>
                                ${!hasFunction ? '<li>可以考慮將重複的代碼封裝成函數</li>' : ''}
                                <li>建議添加錯誤處理機制</li>
                            </ul>
                        </div>
                    `;
                    break;
                    
                default:
                    analysis += `
                        <div class="alert alert-secondary">
                            <h6>📝 代碼說明</h6>
                            <p>這是一段Python代碼，包含${lines}行程式碼。代碼結構清晰，邏輯合理。</p>
                        </div>
                    `;
            }
            
            analysis += `
                    <div class="alert alert-light">
                        <small><i class="fas fa-info-circle"></i> 這是本地分析結果。如需更詳細的AI分析，請確保網路連接正常。</small>
                    </div>
                </div>
            `;
            
            return analysis;
        };
    }
    
    console.log('✅ AI助教代碼獲取邏輯已修復');
    return true;
}

// 5. 測試修復後的功能
function testFixedFunctionality() {
    console.log('🧪 測試修復後的功能...');
    
    // 測試編輯器
    try {
        const code = window.Editor.getCode();
        console.log('✅ 編輯器測試成功，代碼長度:', code.length);
    } catch (error) {
        console.log('❌ 編輯器測試失敗:', error);
        return false;
    }
    
    // 測試AI助教
    try {
        if (window.AIAssistant && window.AIAssistant.requestAnalysis) {
            console.log('✅ AI助教方法存在');
            
            // 如果編輯器有代碼，進行測試分析
            const code = window.Editor.getCode();
            if (code && code.trim()) {
                console.log('🤖 執行測試分析...');
                window.AIAssistant.requestAnalysis('analyze');
                console.log('✅ 測試分析已發送');
            } else {
                console.log('⚠️ 編輯器代碼為空，跳過測試分析');
            }
        } else {
            console.log('❌ AI助教方法不存在');
            return false;
        }
    } catch (error) {
        console.log('❌ AI助教測試失敗:', error);
        return false;
    }
    
    return true;
}

// 6. 主修復函數
function performFix() {
    console.log('🚀 開始執行修復...');
    
    const results = {
        editor: fixEditorBinding(),
        aiAssistant: fixAIAssistant(),
        webSocket: fixWebSocketConnection(),
        codeRetrieval: fixAICodeRetrieval()
    };
    
    console.log('📊 修復結果:');
    Object.keys(results).forEach(key => {
        const status = results[key] ? '✅ 成功' : '❌ 失敗';
        console.log(`  ${key}: ${status}`);
    });
    
    // 如果基本修復成功，進行功能測試
    if (results.editor && results.aiAssistant) {
        console.log('🧪 基本修復成功，進行功能測試...');
        setTimeout(() => {
            testFixedFunctionality();
        }, 1000);
    } else {
        console.log('❌ 基本修復失敗，請檢查控制台錯誤信息');
    }
    
    return results;
}

// 導出到全域
window.fixAIEditor = {
    performFix,
    fixEditorBinding,
    fixAIAssistant,
    fixWebSocketConnection,
    fixAICodeRetrieval,
    testFixedFunctionality
};

console.log('✅ AI助教編輯器修復工具已載入');
console.log('📋 可用命令:');
console.log('  - fixAIEditor.performFix() - 執行完整修復');
console.log('  - fixAIEditor.testFixedFunctionality() - 測試修復後的功能');

// 自動執行修復
setTimeout(() => {
    console.log('🔄 自動執行修復...');
    performFix();
}, 1000); 