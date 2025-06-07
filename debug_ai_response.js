// AI回應調試腳本
// 在瀏覽器控制台中運行此腳本來調試AI回應問題

console.log('🔍 開始AI回應調試...');

// 1. 檢查AI助教實例
console.log('1. 檢查AI助教實例:');
console.log('   - window.AIAssistant:', !!window.AIAssistant);
console.log('   - AIAssistant類型:', typeof window.AIAssistant);
console.log('   - handleWebSocketAIResponse方法:', !!(window.AIAssistant && window.AIAssistant.handleWebSocketAIResponse));

// 2. 檢查WebSocket管理器
console.log('2. 檢查WebSocket管理器:');
console.log('   - window.wsManager:', !!window.wsManager);
console.log('   - WebSocket連接狀態:', window.wsManager ? window.wsManager.isConnected() : 'N/A');

// 3. 檢查AI回應容器
console.log('3. 檢查AI回應容器:');
const aiResponseContainer = document.getElementById('aiResponse');
console.log('   - aiResponse元素:', !!aiResponseContainer);
console.log('   - 當前內容:', aiResponseContainer ? aiResponseContainer.innerHTML.substring(0, 100) + '...' : 'N/A');

// 4. 模擬AI回應測試
console.log('4. 模擬AI回應測試:');
if (window.AIAssistant && window.AIAssistant.handleWebSocketAIResponse) {
    const testResponse = {
        success: true,
        response: '這是一個測試AI回應，用於檢查前端處理是否正常。',
        requestId: 'test_123',
        action: 'explain',
        timestamp: new Date().toISOString()
    };
    
    console.log('   - 發送測試回應:', testResponse);
    window.AIAssistant.handleWebSocketAIResponse(testResponse);
    console.log('   - 測試完成，檢查AI回應區域是否有內容');
} else {
    console.log('   - ❌ 無法進行測試，AI助教實例或方法不存在');
}

// 5. 檢查WebSocket消息處理
console.log('5. WebSocket消息處理檢查:');
if (window.wsManager && window.wsManager.handleAIResponse) {
    console.log('   - handleAIResponse方法存在');
    
    // 模擬WebSocket AI回應
    const mockWebSocketResponse = {
        type: 'ai_response',
        success: true,
        response: '這是模擬的WebSocket AI回應',
        requestId: 'mock_456',
        action: 'analyze'
    };
    
    console.log('   - 模擬WebSocket回應:', mockWebSocketResponse);
    window.wsManager.handleAIResponse(mockWebSocketResponse);
} else {
    console.log('   - ❌ WebSocket AI回應處理方法不存在');
}

// 6. 檢查最近的WebSocket消息
console.log('6. 檢查控制台中的WebSocket消息:');
console.log('   - 請查看控制台中是否有以下消息:');
console.log('     * 🤖 收到 AI 回應:');
console.log('     * ✅ 調用 AI 助教處理 WebSocket 回應');
console.log('     * 🤖 [AI Assistant] 處理WebSocket AI回應:');

// 7. 手動觸發AI請求測試
console.log('7. 手動觸發AI請求測試:');
if (window.AIAssistant && window.AIAssistant.requestAnalysis) {
    console.log('   - 可以手動測試: window.AIAssistant.requestAnalysis("analyze")');
} else {
    console.log('   - ❌ 無法手動測試，requestAnalysis方法不存在');
}

console.log('🔍 AI回應調試完成！');
console.log('💡 如果測試回應顯示正常，問題可能在WebSocket通信或服務器端');
console.log('💡 如果測試回應不顯示，問題在前端AI助教處理邏輯'); 