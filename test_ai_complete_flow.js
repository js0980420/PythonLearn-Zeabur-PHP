// 完整AI請求流程測試腳本
// 在瀏覽器控制台中運行此腳本

console.log('🧪 開始完整AI請求流程測試...');

// 測試配置
const TEST_CONFIG = {
    wsUrl: 'ws://localhost:8081',
    apiUrl: 'http://localhost:8080/api/ai',
    testCode: `# 測試代碼
print("Hello, World!")
for i in range(3):
    print(f"數字: {i}")`,
    roomId: 'test_room_001',
    userId: 'test_user',
    username: 'Test User'
};

// 測試步驟
let testStep = 0;
let testResults = {};

function logTest(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const icon = type === 'error' ? '❌' : type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';
    console.log(`[${timestamp}] ${icon} 步驟${testStep}: ${message}`);
}

function nextStep(description) {
    testStep++;
    console.log(`\n🔄 步驟 ${testStep}: ${description}`);
}

// 步驟1: 測試直接API調用
async function testDirectAPI() {
    nextStep('測試直接API調用');
    
    try {
        const response = await fetch(TEST_CONFIG.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'explain_code',
                code: TEST_CONFIG.testCode,
                user_id: TEST_CONFIG.userId
            })
        });
        
        const data = await response.json();
        testResults.directAPI = {
            success: data.success,
            hasAnalysis: !!(data.data && data.data.analysis),
            responseTime: Date.now()
        };
        
        if (data.success && data.data && data.data.analysis) {
            logTest('直接API調用成功', 'success');
            logTest(`回應長度: ${data.data.analysis.length} 字符`);
            return true;
        } else {
            logTest(`直接API調用失敗: ${data.message || '未知錯誤'}`, 'error');
            return false;
        }
    } catch (error) {
        logTest(`直接API調用異常: ${error.message}`, 'error');
        testResults.directAPI = { success: false, error: error.message };
        return false;
    }
}

// 步驟2: 測試WebSocket連接
function testWebSocketConnection() {
    nextStep('測試WebSocket連接');
    
    return new Promise((resolve) => {
        const ws = new WebSocket(TEST_CONFIG.wsUrl);
        const timeout = setTimeout(() => {
            logTest('WebSocket連接超時', 'error');
            testResults.wsConnection = { success: false, error: 'timeout' };
            resolve(false);
        }, 5000);
        
        ws.onopen = function() {
            clearTimeout(timeout);
            logTest('WebSocket連接成功', 'success');
            testResults.wsConnection = { success: true };
            
            // 加入房間
            ws.send(JSON.stringify({
                type: 'join_room',
                room_id: TEST_CONFIG.roomId,
                user_id: TEST_CONFIG.userId,
                username: TEST_CONFIG.username
            }));
            
            // 等待房間加入確認
            ws.onmessage = function(event) {
                const message = JSON.parse(event.data);
                if (message.type === 'room_joined') {
                    logTest('成功加入房間', 'success');
                    ws.close();
                    resolve(true);
                }
            };
        };
        
        ws.onerror = function(error) {
            clearTimeout(timeout);
            logTest(`WebSocket連接錯誤: ${error}`, 'error');
            testResults.wsConnection = { success: false, error: 'connection_error' };
            resolve(false);
        };
    });
}

// 步驟3: 測試完整WebSocket AI請求流程
function testWebSocketAIRequest() {
    nextStep('測試完整WebSocket AI請求流程');
    
    return new Promise((resolve) => {
        const ws = new WebSocket(TEST_CONFIG.wsUrl);
        const timeout = setTimeout(() => {
            logTest('WebSocket AI請求超時', 'error');
            testResults.wsAIRequest = { success: false, error: 'timeout' };
            resolve(false);
        }, 30000); // 30秒超時
        
        let roomJoined = false;
        let aiRequestSent = false;
        
        ws.onopen = function() {
            logTest('WebSocket已連接，準備加入房間');
            
            // 加入房間
            ws.send(JSON.stringify({
                type: 'join_room',
                room_id: TEST_CONFIG.roomId,
                user_id: TEST_CONFIG.userId,
                username: TEST_CONFIG.username
            }));
        };
        
        ws.onmessage = function(event) {
            const message = JSON.parse(event.data);
            logTest(`收到消息: ${message.type}`);
            
            if (message.type === 'room_joined' && !roomJoined) {
                roomJoined = true;
                logTest('房間加入成功，發送AI請求');
                
                // 發送AI請求
                const aiRequest = {
                    type: 'ai_request',
                    action: 'explain_code',
                    requestId: `test_${Date.now()}`,
                    user_id: TEST_CONFIG.userId,
                    username: TEST_CONFIG.username,
                    room_id: TEST_CONFIG.roomId,
                    data: {
                        code: TEST_CONFIG.testCode
                    }
                };
                
                ws.send(JSON.stringify(aiRequest));
                aiRequestSent = true;
                logTest('AI請求已發送');
            }
            
            if (message.type === 'ai_response' && aiRequestSent) {
                clearTimeout(timeout);
                logTest('收到AI回應', 'success');
                
                testResults.wsAIRequest = {
                    success: message.success,
                    hasResponse: !!message.response,
                    error: message.error,
                    requestId: message.requestId
                };
                
                if (message.success && message.response) {
                    logTest(`AI回應成功，內容長度: ${message.response.length}`, 'success');
                    logTest(`回應內容預覽: ${message.response.substring(0, 100)}...`);
                } else {
                    logTest(`AI回應失敗: ${message.error}`, 'error');
                }
                
                ws.close();
                resolve(message.success);
            }
        };
        
        ws.onerror = function(error) {
            clearTimeout(timeout);
            logTest(`WebSocket錯誤: ${error}`, 'error');
            testResults.wsAIRequest = { success: false, error: 'websocket_error' };
            resolve(false);
        };
    });
}

// 步驟4: 測試前端AI助教類
function testFrontendAIAssistant() {
    nextStep('測試前端AI助教類');
    
    try {
        // 檢查AI助教是否存在
        if (!window.AIAssistant) {
            logTest('AI助教類不存在', 'error');
            testResults.frontendAI = { success: false, error: 'ai_assistant_not_found' };
            return false;
        }
        
        // 檢查關鍵方法
        const requiredMethods = ['initialize', 'requestAnalysis', 'handleWebSocketAIResponse', 'showResponse'];
        const missingMethods = requiredMethods.filter(method => typeof window.AIAssistant[method] !== 'function');
        
        if (missingMethods.length > 0) {
            logTest(`AI助教缺少方法: ${missingMethods.join(', ')}`, 'error');
            testResults.frontendAI = { success: false, error: 'missing_methods', missingMethods };
            return false;
        }
        
        // 檢查DOM元素
        const responseContainer = document.getElementById('aiResponse');
        if (!responseContainer) {
            logTest('AI回應容器不存在', 'error');
            testResults.frontendAI = { success: false, error: 'response_container_not_found' };
            return false;
        }
        
        // 測試模擬AI回應
        const mockResponse = {
            success: true,
            response: "這是一個測試AI回應，用於驗證前端顯示功能。",
            requestId: 'test_frontend'
        };
        
        window.AIAssistant.handleWebSocketAIResponse(mockResponse);
        
        // 檢查是否正確顯示
        setTimeout(() => {
            const containerContent = responseContainer.innerHTML;
            if (containerContent.includes('測試AI回應')) {
                logTest('前端AI助教顯示測試成功', 'success');
                testResults.frontendAI = { success: true };
            } else {
                logTest('前端AI助教顯示測試失敗', 'error');
                testResults.frontendAI = { success: false, error: 'display_failed' };
            }
        }, 1000);
        
        return true;
    } catch (error) {
        logTest(`前端AI助教測試異常: ${error.message}`, 'error');
        testResults.frontendAI = { success: false, error: error.message };
        return false;
    }
}

// 執行完整測試流程
async function runCompleteTest() {
    console.log('🚀 開始執行完整AI請求流程測試...\n');
    
    // 步驟1: 直接API測試
    const apiSuccess = await testDirectAPI();
    
    // 步驟2: WebSocket連接測試
    const wsSuccess = await testWebSocketConnection();
    
    // 步驟3: WebSocket AI請求測試
    const wsAISuccess = await testWebSocketAIRequest();
    
    // 步驟4: 前端AI助教測試
    const frontendSuccess = testFrontendAIAssistant();
    
    // 等待前端測試完成
    setTimeout(() => {
        console.log('\n📊 測試結果總結:');
        console.log('==================');
        console.log(`✅ 直接API調用: ${apiSuccess ? '成功' : '失敗'}`);
        console.log(`✅ WebSocket連接: ${wsSuccess ? '成功' : '失敗'}`);
        console.log(`✅ WebSocket AI請求: ${wsAISuccess ? '成功' : '失敗'}`);
        console.log(`✅ 前端AI助教: ${testResults.frontendAI?.success ? '成功' : '失敗'}`);
        
        console.log('\n📋 詳細結果:');
        console.log(JSON.stringify(testResults, null, 2));
        
        // 診斷建議
        console.log('\n💡 診斷建議:');
        if (!apiSuccess) {
            console.log('❌ AI API有問題，檢查後端服務器和OpenAI配置');
        }
        if (!wsSuccess) {
            console.log('❌ WebSocket連接有問題，檢查WebSocket服務器');
        }
        if (!wsAISuccess) {
            console.log('❌ WebSocket AI請求有問題，檢查服務器間通信');
        }
        if (!testResults.frontendAI?.success) {
            console.log('❌ 前端AI助教有問題，檢查JavaScript代碼和DOM元素');
        }
        
        if (apiSuccess && wsSuccess && wsAISuccess && testResults.frontendAI?.success) {
            console.log('🎉 所有測試通過！AI功能應該正常工作。');
        }
    }, 2000);
}

// 開始測試
runCompleteTest(); 