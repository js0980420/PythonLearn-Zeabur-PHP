// AI助教和編輯器交互調試腳本
// 在瀏覽器控制台中運行此腳本

console.log('🔧 開始AI助教和編輯器交互調試...');

// 1. 檢查編輯器狀態
function checkEditor() {
    console.log('\n🔍 檢查編輯器狀態...');
    
    if (window.Editor) {
        console.log('✅ window.Editor 存在');
        console.log('📋 編輯器實例:', window.Editor);
        
        // 檢查編輯器是否初始化
        if (window.Editor.editor) {
            console.log('✅ CodeMirror 編輯器已初始化');
            
            // 檢查getCode方法
            if (typeof window.Editor.getCode === 'function') {
                console.log('✅ getCode 方法存在');
                
                const code = window.Editor.getCode();
                console.log('📝 當前編輯器代碼:', code);
                console.log('📏 代碼長度:', code ? code.length : 'null/undefined');
                console.log('🔤 代碼類型:', typeof code);
                
                if (!code || code.trim() === '') {
                    console.log('⚠️ 編輯器代碼為空');
                    return false;
                } else {
                    console.log('✅ 編輯器有代碼內容');
                    return true;
                }
            } else {
                console.log('❌ getCode 方法不存在');
                return false;
            }
        } else {
            console.log('❌ CodeMirror 編輯器未初始化');
            return false;
        }
    } else {
        console.log('❌ window.Editor 不存在');
        return false;
    }
}

// 2. 測試編輯器代碼設置和獲取
function testEditorCodeOperations() {
    console.log('\n🧪 測試編輯器代碼操作...');
    
    if (!window.Editor || !window.Editor.editor) {
        console.log('❌ 編輯器未準備好');
        return false;
    }
    
    // 保存當前代碼
    const originalCode = window.Editor.getCode();
    console.log('💾 保存原始代碼:', originalCode);
    
    // 設置測試代碼
    const testCode = `# AI助教測試代碼
print("Hello, AI Assistant!")
for i in range(3):
    print(f"測試 {i}")`;
    
    try {
        window.Editor.editor.setValue(testCode);
        console.log('✅ 測試代碼已設置');
        
        // 獲取代碼驗證
        const retrievedCode = window.Editor.getCode();
        console.log('📥 獲取的代碼:', retrievedCode);
        
        if (retrievedCode === testCode) {
            console.log('✅ 代碼設置和獲取正常');
            
            // 恢復原始代碼
            if (originalCode) {
                window.Editor.editor.setValue(originalCode);
                console.log('🔄 已恢復原始代碼');
            }
            
            return true;
        } else {
            console.log('❌ 代碼設置和獲取不一致');
            return false;
        }
    } catch (error) {
        console.log('❌ 編輯器操作失敗:', error);
        return false;
    }
}

// 3. 檢查AI助教獲取代碼的流程
function testAICodeRetrieval() {
    console.log('\n🤖 測試AI助教代碼獲取流程...');
    
    if (!window.AIAssistant) {
        console.log('❌ AI助教不存在');
        return false;
    }
    
    // 模擬AI助教的代碼獲取邏輯
    console.log('🔍 模擬AI助教代碼獲取...');
    console.log('  - window.Editor:', !!window.Editor);
    console.log('  - window.Editor.getCode:', typeof (window.Editor && window.Editor.getCode));
    
    const code = window.Editor ? window.Editor.getCode() : '';
    console.log('📝 AI助教獲取的代碼:', code);
    console.log('📏 代碼長度:', code ? code.length : 'null/undefined');
    
    if (!code || code.trim() === '') {
        console.log('⚠️ AI助教獲取的代碼為空');
        return false;
    } else {
        console.log('✅ AI助教成功獲取代碼');
        return true;
    }
}

// 4. 測試完整的AI請求流程
function testFullAIFlow() {
    console.log('\n🚀 測試完整AI請求流程...');
    
    // 確保有測試代碼
    if (!window.Editor || !window.Editor.editor) {
        console.log('❌ 編輯器未準備好');
        return;
    }
    
    const currentCode = window.Editor.getCode();
    if (!currentCode || currentCode.trim() === '') {
        console.log('📝 設置測試代碼...');
        const testCode = `# AI助教測試
print("Hello World")
x = 10
y = 20
result = x + y
print(f"結果: {result}")`;
        
        window.Editor.editor.setValue(testCode);
        console.log('✅ 測試代碼已設置');
    }
    
    // 檢查WebSocket連接
    if (!window.wsManager || !window.wsManager.isConnected()) {
        console.log('❌ WebSocket未連接，無法測試完整流程');
        console.log('🔧 嘗試模擬WebSocket...');
        
        // 模擬WebSocket管理器
        window.wsManager = {
            isConnected: () => true,
            currentRoom: 'test_room_001',
            currentUser: 'test_user',
            sendMessage: (message) => {
                console.log('📤 模擬發送WebSocket消息:', message);
                
                // 模擬AI回應
                if (message.type === 'ai_request') {
                    setTimeout(() => {
                        const mockResponse = {
                            type: 'ai_response',
                            success: true,
                            response: `AI分析結果：您的代碼功能是${message.data.code.includes('print') ? '輸出文字' : '執行計算'}。代碼結構清晰，邏輯正確。`,
                            requestId: message.requestId,
                            action: message.action
                        };
                        
                        console.log('📥 模擬AI回應:', mockResponse);
                        
                        if (window.AIAssistant && window.AIAssistant.handleWebSocketAIResponse) {
                            window.AIAssistant.handleWebSocketAIResponse(mockResponse);
                        }
                    }, 2000);
                }
            }
        };
        console.log('✅ WebSocket模擬器已創建');
    }
    
    // 發送AI請求
    console.log('🤖 發送AI請求...');
    try {
        if (window.AIAssistant && window.AIAssistant.requestAnalysis) {
            window.AIAssistant.requestAnalysis('explain_code');
            console.log('✅ AI請求已發送');
        } else {
            console.log('❌ AI助教requestAnalysis方法不存在');
        }
    } catch (error) {
        console.log('❌ 發送AI請求失敗:', error);
    }
}

// 5. 檢查DOM元素
function checkDOMElements() {
    console.log('\n🔍 檢查相關DOM元素...');
    
    const elements = {
        'codeEditor': document.getElementById('codeEditor'),
        'aiResponse': document.getElementById('aiResponse'),
        'aiShareOptions': document.getElementById('aiShareOptions')
    };
    
    let allGood = true;
    Object.keys(elements).forEach(id => {
        if (elements[id]) {
            console.log(`✅ DOM元素 ${id} 存在`);
            if (id === 'codeEditor') {
                console.log(`  - 元素類型: ${elements[id].tagName}`);
                console.log(`  - 元素值: "${elements[id].value}"`);
            }
        } else {
            console.log(`❌ DOM元素 ${id} 不存在`);
            allGood = false;
        }
    });
    
    return allGood;
}

// 6. 修復嘗試
function attemptFix() {
    console.log('\n🔧 嘗試修復問題...');
    
    // 檢查並修復編輯器
    if (!window.Editor) {
        console.log('🔄 嘗試重新創建編輯器...');
        if (typeof EditorManager !== 'undefined') {
            window.Editor = new EditorManager();
            console.log('✅ 編輯器已重新創建');
        } else {
            console.log('❌ EditorManager類不存在');
        }
    }
    
    // 檢查並修復AI助教
    if (!window.AIAssistant) {
        console.log('🔄 嘗試重新創建AI助教...');
        if (typeof AIAssistantManager !== 'undefined') {
            window.AIAssistant = new AIAssistantManager();
            window.AIAssistant.initialize();
            console.log('✅ AI助教已重新創建');
        } else {
            console.log('❌ AIAssistantManager類不存在');
        }
    }
    
    // 重新初始化編輯器
    if (window.Editor && typeof window.Editor.initialize === 'function') {
        try {
            window.Editor.initialize();
            console.log('✅ 編輯器已重新初始化');
        } catch (error) {
            console.log('❌ 編輯器初始化失敗:', error);
        }
    }
}

// 7. 完整診斷
function fullDiagnosis() {
    console.log('🚀 開始完整AI助教和編輯器交互診斷...');
    
    const results = {
        domElements: checkDOMElements(),
        editor: checkEditor(),
        editorOperations: testEditorCodeOperations(),
        aiCodeRetrieval: testAICodeRetrieval()
    };
    
    console.log('\n📊 診斷結果總結:');
    console.log('==================');
    Object.keys(results).forEach(key => {
        const status = results[key] ? '✅ 正常' : '❌ 異常';
        console.log(`${key}: ${status}`);
    });
    
    // 如果基本檢查都通過，進行完整流程測試
    if (results.editor && results.editorOperations && results.aiCodeRetrieval) {
        console.log('\n🧪 基本檢查通過，開始完整流程測試...');
        setTimeout(() => {
            testFullAIFlow();
        }, 1000);
    } else {
        console.log('\n❌ 基本檢查未通過，建議修復問題');
        console.log('💡 可以嘗試運行: debugAIEditor.attemptFix()');
    }
    
    return results;
}

// 導出函數到全域
window.debugAIEditor = {
    checkEditor,
    testEditorCodeOperations,
    testAICodeRetrieval,
    testFullAIFlow,
    checkDOMElements,
    attemptFix,
    fullDiagnosis
};

console.log('✅ AI助教和編輯器交互調試工具已載入');
console.log('📋 可用命令:');
console.log('  - debugAIEditor.fullDiagnosis() - 完整診斷');
console.log('  - debugAIEditor.attemptFix() - 嘗試修復');
console.log('  - debugAIEditor.testFullAIFlow() - 測試完整流程');
console.log('  - debugAIEditor.checkEditor() - 檢查編輯器');

// 自動開始診斷
setTimeout(() => {
    fullDiagnosis();
}, 500); 