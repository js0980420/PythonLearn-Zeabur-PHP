// 🎭 PythonLearn Playwright 調試腳本
const { chromium } = require('playwright');

async function debugPythonLearn() {
    console.log('🚀 啟動 Playwright 調試...');
    
    // 啟動瀏覽器
    const browser = await chromium.launch({ 
        headless: false,  // 顯示瀏覽器視窗
        slowMo: 1000     // 操作間延遲 1 秒，便於觀察
    });
    
    const context = await browser.newContext({
        viewport: { width: 1280, height: 720 }
    });
    
    const page = await context.newPage();
    
    try {
        console.log('🌐 導航到 PythonLearn...');
        await page.goto('http://localhost:8080');
        
        console.log('🔧 注入調試助手...');
        await page.addInitScript(() => {
            window.playwrightDebug = {
                async testAPI(action, params = {}) {
                    const url = new URL('/api.php', 'http://localhost:8080');
                    url.searchParams.set('action', action);
                    Object.entries(params).forEach(([key, value]) => {
                        url.searchParams.set(key, value);
                    });
                    
                    const response = await fetch(url);
                    const data = await response.json();
                    console.log(`🔍 API [${action}]:`, data);
                    return data;
                }
            };
        });
        
        console.log('🧪 執行自動化測試...');
        
        const testResults = await page.evaluate(async () => {
            console.log('🔥 開始 Playwright 自動化測試...');
            
            const results = {};
            
            // 1. 測試系統狀態
            console.log('📊 測試系統狀態...');
            results.status = await window.playwrightDebug.testAPI('status');
            
            // 2. 測試用戶系統
            console.log('👥 測試用戶系統...');
            results.users = await window.playwrightDebug.testAPI('get_recent_users', { limit: 5 });
            
            // 3. 測試加入房間
            console.log('🚪 測試加入房間...');
            const roomId = 'playwright-debug-' + Date.now();
            results.join = await window.playwrightDebug.testAPI('join', {
                room_id: roomId,
                user_id: 'playwright-user',
                user_name: 'Playwright 測試用戶',
                is_teacher: false
            });
            
            // 4. 測試發送消息
            console.log('💬 測試聊天功能...');
            results.sendMessage = await window.playwrightDebug.testAPI('send_message', {
                room_id: roomId,
                user_id: 'playwright-user',
                message: '來自 Playwright 的測試消息 ' + new Date().toLocaleTimeString()
            });
            
            // 5. 測試獲取聊天記錄
            console.log('📜 測試聊天記錄...');
            results.chatHistory = await window.playwrightDebug.testAPI('get_chat_messages', {
                room_id: roomId,
                limit: 10
            });
            
            // 6. 測試心跳
            console.log('💓 測試心跳...');
            results.heartbeat = await window.playwrightDebug.testAPI('heartbeat', {
                room_id: roomId,
                user_id: 'playwright-user'
            });
            
            console.log('✅ 所有測試完成！', results);
            return results;
        });
        
        // 顯示結果
        console.log('\n🎉 Playwright 測試結果:');
        console.log('='.repeat(50));
        
        Object.entries(testResults).forEach(([test, result]) => {
            console.log(`\n🔍 ${test.toUpperCase()}:`);
            console.log(JSON.stringify(result, null, 2));
        });
        
        // 保持瀏覽器開啟 5 秒讓用戶觀察
        console.log('\n⏳ 保持瀏覽器開啟 5 秒...');
        await page.waitForTimeout(5000);
        
    } catch (error) {
        console.error('❌ Playwright 測試失敗:', error);
    } finally {
        await browser.close();
        console.log('🏁 Playwright 調試完成');
    }
}

// 如果直接運行此腳本
if (require.main === module) {
    debugPythonLearn().catch(console.error);
}

module.exports = { debugPythonLearn }; 