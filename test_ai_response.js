// AI回應測試腳本 - 在瀏覽器控制台中運行
// 複製以下代碼到瀏覽器控制台並執行

console.log('🧪 開始AI回應測試...');

// 1. 檢查AI助教實例
console.log('1. 檢查AI助教實例:');
console.log('   - window.AIAssistant 存在:', !!window.AIAssistant);
console.log('   - AIAssistant 類型:', typeof window.AIAssistant);

if (window.AIAssistant) {
    console.log('   - handleWebSocketAIResponse 方法:', typeof window.AIAssistant.handleWebSocketAIResponse);
    console.log('   - responseContainer:', !!window.AIAssistant.responseContainer);
    console.log('   - shareOptions:', !!window.AIAssistant.shareOptions);
    console.log('   - isProcessing:', window.AIAssistant.isProcessing);
}

// 2. 檢查DOM元素
console.log('2. 檢查DOM元素:');
const aiResponse = document.getElementById('aiResponse');
const aiShareOptions = document.getElementById('aiShareOptions');
console.log('   - aiResponse 元素存在:', !!aiResponse);
console.log('   - aiShareOptions 元素存在:', !!aiShareOptions);

if (aiResponse) {
    console.log('   - aiResponse 當前內容長度:', aiResponse.innerHTML.length);
    console.log('   - aiResponse 當前內容預覽:', aiResponse.innerHTML.substring(0, 100) + '...');
}

// 3. 測試AI回應顯示
console.log('3. 測試AI回應顯示:');
if (window.AIAssistant) {
    const testResponse = {
        success: true,
        response: `這是一個測試AI回應。

**優點:**
- 代碼結構清晰
- 變數命名規範

**建議:**
- 可以添加更多註釋
- 考慮使用函數封裝重複代碼

**改進建議:**
1. 添加錯誤處理
2. 優化算法效率
3. 增加代碼註釋`,
        requestId: 'test_' + Date.now(),
        timestamp: Date.now()
    };
    
    console.log('   - 發送測試回應:', testResponse);
    window.AIAssistant.handleWebSocketAIResponse(testResponse);
    console.log('   - 測試完成，檢查AI回應區域');
} else {
    console.log('   - ❌ AI助教實例不存在，無法測試');
}

// 4. 手動顯示測試（降級處理）
console.log('4. 手動顯示測試:');
if (aiResponse) {
    const originalContent = aiResponse.innerHTML;
    aiResponse.innerHTML = `
        <div class="alert alert-success">
            <h6><i class="fas fa-robot"></i> 手動測試AI回應</h6>
            <div>這是手動插入的測試內容，用於驗證DOM元素是否正常工作。</div>
            <div class="mt-2">
                <strong>測試時間:</strong> ${new Date().toLocaleString()}
            </div>
        </div>
    `;
    console.log('   - ✅ 手動顯示成功');
    
    // 3秒後恢復原內容
    setTimeout(() => {
        aiResponse.innerHTML = originalContent;
        console.log('   - 🔄 已恢復原內容');
    }, 3000);
} else {
    console.log('   - ❌ aiResponse 元素不存在，無法手動測試');
}

// 5. 檢查WebSocket管理器
console.log('5. 檢查WebSocket管理器:');
console.log('   - window.wsManager 存在:', !!window.wsManager);
if (window.wsManager) {
    console.log('   - handleAIResponse 方法:', typeof window.wsManager.handleAIResponse);
    console.log('   - 連接狀態:', window.wsManager.isConnected ? window.wsManager.isConnected() : 'N/A');
}

console.log('🧪 AI回應測試完成！');
console.log('💡 如果手動顯示成功但AI回應不顯示，問題在AI助教邏輯');
console.log('💡 如果手動顯示也失敗，問題在DOM元素或CSS'); 