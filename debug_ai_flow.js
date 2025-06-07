// AI請求流程調試腳本
// 在瀏覽器控制台中運行此腳本來調試整個AI請求流程

console.log('🔍 開始AI請求流程調試...');

// 1. 檢查WebSocket連接狀態
console.log('1. 檢查WebSocket連接狀態:');
if (window.wsManager) {
    console.log('   - wsManager 存在:', !!window.wsManager);
    console.log('   - 連接狀態:', window.wsManager.isConnected());
    console.log('   - 當前房間:', window.wsManager.currentRoom);
    console.log('   - 當前用戶:', window.wsManager.currentUser);
} else {
    console.log('   - ❌ wsManager 不存在');
}

// 2. 檢查AI助教實例
console.log('2. 檢查AI助教實例:');
if (window.AIAssistant) {
    console.log('   - AIAssistant 存在:', !!window.AIAssistant);
    console.log('   - responseContainer:', !!window.AIAssistant.responseContainer);
    console.log('   - isProcessing:', window.AIAssistant.isProcessing);
    console.log('   - handleWebSocketAIResponse 方法:', typeof window.AIAssistant.handleWebSocketAIResponse);
} else {
    console.log('   - ❌ AIAssistant 不存在');
}

// 3. 檢查編輯器代碼
console.log('3. 檢查編輯器代碼:');
if (window.Editor) {
    const code = window.Editor.getCode();
    console.log('   - Editor 存在:', !!window.Editor);
    console.log('   - 代碼長度:', code ? code.length : 0);
    console.log('   - 代碼預覽:', code ? code.substring(0, 50) + '...' : '無代碼');
} else {
    console.log('   - ❌ Editor 不存在');
}

// 4. 模擬AI請求
console.log('4. 模擬AI請求:');
if (window.wsManager && window.wsManager.isConnected() && window.AIAssistant) {
    console.log('   - 準備發送AI請求...');
    
    // 監聽WebSocket消息
    const originalHandleMessage = window.wsManager.handleMessage;
    window.wsManager.handleMessage = function(message) {
        if (message.type === 'ai_response') {
            console.log('   - 🤖 收到AI回應:', message);
        }
        return originalHandleMessage.call(this, message);
    };
    
    // 發送測試AI請求
    const testCode = '# 測試代碼\nprint("Hello, World!")';
    const requestId = `test_${Date.now()}`;
    
    const aiRequest = {
        type: 'ai_request',
        action: 'explain_code',
        requestId: requestId,
        user_id: window.wsManager.currentUser || 'test_user',
        username: window.wsManager.currentUser || 'Test User',
        room_id: window.wsManager.currentRoom || 'test_room_001',
        data: {
            code: testCode
        }
    };
    
    console.log('   - 發送AI請求:', aiRequest);
    window.wsManager.sendMessage(aiRequest);
    
    // 設置超時檢查
    setTimeout(() => {
        console.log('   - 5秒後檢查AI回應狀態...');
        const aiResponseContainer = document.getElementById('aiResponse');
        if (aiResponseContainer) {
            console.log('   - AI回應容器內容:', aiResponseContainer.innerHTML.substring(0, 200) + '...');
        }
    }, 5000);
    
} else {
    console.log('   - ❌ 無法發送AI請求，缺少必要組件');
}

// 5. 檢查API端點
console.log('5. 檢查API端點:');
fetch('/api/ai', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        action: 'explain_code',
        code: '# 測試\nprint("Hello")',
        user_id: 'test_user'
    })
})
.then(response => {
    console.log('   - API回應狀態:', response.status);
    return response.json();
})
.then(data => {
    console.log('   - API回應數據:', data);
})
.catch(error => {
    console.log('   - API請求錯誤:', error);
});

// 6. 檢查控制台錯誤
console.log('6. 檢查控制台錯誤:');
console.log('   - 請查看控制台中是否有紅色錯誤信息');
console.log('   - 特別注意WebSocket連接錯誤或AI相關錯誤');

// 7. 手動觸發AI請求
console.log('7. 手動觸發AI請求:');
console.log('   - 您可以手動運行: window.AIAssistant.requestAnalysis("analyze")');
console.log('   - 或者點擊頁面上的AI按鈕');

console.log('🔍 AI請求流程調試完成！');
console.log('💡 如果看到AI回應但UI沒有更新，問題在前端顯示邏輯');
console.log('💡 如果沒有收到AI回應，問題在WebSocket通信或後端API'); 