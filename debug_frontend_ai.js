// 前端AI調試腳本
// 在瀏覽器控制台中運行此腳本來調試AI功能

console.log('🔧 開始前端AI調試...');

// 1. 檢查AI助教實例
function checkAIAssistant() {
    console.log('\n🔍 檢查AI助教實例...');
    
    if (window.AIAssistant) {
        console.log('✅ window.AIAssistant 存在');
        console.log('📋 AI助教實例:', window.AIAssistant);
        
        // 檢查關鍵方法
        const methods = ['initialize', 'requestAnalysis', 'handleWebSocketAIResponse', 'showResponse'];
        methods.forEach(method => {
            if (typeof window.AIAssistant[method] === 'function') {
                console.log(`✅ 方法 ${method} 存在`);
            } else {
                console.log(`❌ 方法 ${method} 不存在`);
            }
        });
        
        // 檢查狀態
        console.log('📊 AI助教狀態:');
        console.log('  - isProcessing:', window.AIAssistant.isProcessing);
        console.log('  - responseContainer:', !!window.AIAssistant.responseContainer);
        console.log('  - shareOptions:', !!window.AIAssistant.shareOptions);
        
        return true;
    } else {
        console.log('❌ window.AIAssistant 不存在');
        return false;
    }
}

// 2. 檢查WebSocket連接
function checkWebSocket() {
    console.log('\n🔍 檢查WebSocket連接...');
    
    if (window.wsManager) {
        console.log('✅ wsManager 存在');
        console.log('📋 WebSocket管理器:', window.wsManager);
        
        if (window.wsManager.isConnected()) {
            console.log('✅ WebSocket 已連接');
            console.log('📍 當前房間:', window.wsManager.currentRoom);
            console.log('👤 當前用戶:', window.wsManager.currentUser);
            return true;
        } else {
            console.log('❌ WebSocket 未連接');
            return false;
        }
    } else {
        console.log('❌ wsManager 不存在');
        return false;
    }
}

// 3. 檢查DOM元素
function checkDOMElements() {
    console.log('\n🔍 檢查DOM元素...');
    
    const elements = {
        'aiResponse': document.getElementById('aiResponse'),
        'aiShareOptions': document.getElementById('aiShareOptions')
    };
    
    let allGood = true;
    Object.keys(elements).forEach(id => {
        if (elements[id]) {
            console.log(`✅ DOM元素 ${id} 存在`);
        } else {
            console.log(`❌ DOM元素 ${id} 不存在`);
            allGood = false;
        }
    });
    
    return allGood;
}

// 4. 檢查全域函數
function checkGlobalFunctions() {
    console.log('\n🔍 檢查全域函數...');
    
    const functions = ['askAI', 'globalAskAI', 'shareAIResponse', 'showAIIntro'];
    let allGood = true;
    
    functions.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            console.log(`✅ 全域函數 ${funcName} 存在`);
        } else {
            console.log(`❌ 全域函數 ${funcName} 不存在`);
            allGood = false;
        }
    });
    
    return allGood;
}

// 5. 測試AI請求流程
function testAIRequest() {
    console.log('\n🧪 測試AI請求流程...');
    
    if (!window.AIAssistant) {
        console.log('❌ AI助教不存在，無法測試');
        return;
    }
    
    if (!window.wsManager || !window.wsManager.isConnected()) {
        console.log('❌ WebSocket未連接，無法測試');
        return;
    }
    
    // 模擬編輯器代碼
    if (!window.Editor) {
        window.Editor = {
            getCode: function() {
                return 'print("Hello, World!")';
            }
        };
        console.log('✅ 創建模擬編輯器');
    }
    
    console.log('📤 發送AI請求...');
    try {
        window.AIAssistant.requestAnalysis('explain_code');
        console.log('✅ AI請求已發送');
    } catch (error) {
        console.log('❌ 發送AI請求失敗:', error);
    }
}

// 6. 模擬AI回應
function simulateAIResponse() {
    console.log('\n🎭 模擬AI回應...');
    
    if (!window.AIAssistant) {
        console.log('❌ AI助教不存在，無法模擬');
        return;
    }
    
    const mockResponse = {
        success: true,
        response: "這是一個模擬的AI回應。您的代碼功能是輸出Hello World。這是一個經典的程式設計入門範例。",
        requestId: 'debug_test_' + Date.now(),
        action: 'explain_code'
    };
    
    console.log('📥 模擬AI回應:', mockResponse);
    
    try {
        window.AIAssistant.handleWebSocketAIResponse(mockResponse);
        console.log('✅ 模擬AI回應處理完成');
    } catch (error) {
        console.log('❌ 處理AI回應失敗:', error);
    }
}

// 7. 完整診斷
function fullDiagnosis() {
    console.log('🚀 開始完整AI功能診斷...');
    
    const results = {
        aiAssistant: checkAIAssistant(),
        webSocket: checkWebSocket(),
        domElements: checkDOMElements(),
        globalFunctions: checkGlobalFunctions()
    };
    
    console.log('\n📊 診斷結果總結:');
    console.log('==================');
    Object.keys(results).forEach(key => {
        const status = results[key] ? '✅ 正常' : '❌ 異常';
        console.log(`${key}: ${status}`);
    });
    
    // 如果基本檢查都通過，進行功能測試
    if (results.aiAssistant && results.webSocket && results.domElements) {
        console.log('\n🧪 基本檢查通過，開始功能測試...');
        setTimeout(() => {
            testAIRequest();
        }, 1000);
        
        setTimeout(() => {
            simulateAIResponse();
        }, 3000);
    } else {
        console.log('\n❌ 基本檢查未通過，請修復問題後重試');
    }
    
    return results;
}

// 8. 修復嘗試
function attemptFix() {
    console.log('\n🔧 嘗試修復AI功能...');
    
    // 嘗試重新初始化AI助教
    if (!window.AIAssistant && typeof AIAssistantManager !== 'undefined') {
        console.log('🔄 嘗試重新創建AI助教實例...');
        window.AIAssistant = new AIAssistantManager();
        window.AIAssistant.initialize();
        console.log('✅ AI助教實例已重新創建');
    }
    
    // 檢查並修復DOM元素綁定
    if (window.AIAssistant && !window.AIAssistant.responseContainer) {
        console.log('🔄 嘗試重新綁定DOM元素...');
        window.AIAssistant.responseContainer = document.getElementById('aiResponse');
        window.AIAssistant.shareOptions = document.getElementById('aiShareOptions');
        
        if (window.AIAssistant.responseContainer) {
            console.log('✅ AI回應容器已重新綁定');
        } else {
            console.log('❌ 找不到AI回應容器');
        }
    }
    
    // 重新檢查
    setTimeout(() => {
        console.log('\n🔍 修復後重新檢查...');
        fullDiagnosis();
    }, 1000);
}

// 導出函數到全域
window.debugAI = {
    checkAIAssistant,
    checkWebSocket,
    checkDOMElements,
    checkGlobalFunctions,
    testAIRequest,
    simulateAIResponse,
    fullDiagnosis,
    attemptFix
};

console.log('✅ AI調試工具已載入');
console.log('📋 可用命令:');
console.log('  - debugAI.fullDiagnosis() - 完整診斷');
console.log('  - debugAI.attemptFix() - 嘗試修復');
console.log('  - debugAI.simulateAIResponse() - 模擬AI回應');
console.log('  - debugAI.testAIRequest() - 測試AI請求');

// 自動開始診斷
setTimeout(() => {
    fullDiagnosis();
}, 500); 