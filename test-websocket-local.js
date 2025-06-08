#!/usr/bin/env node

/**
 * WebSocket 連接測試腳本
 * 用於測試 Zeabur 部署的 WebSocket 服務
 * 
 * 使用方法:
 * npm install -g wscat
 * node test-websocket-local.js
 */

const WebSocket = require('ws');

// 測試配置
const TEST_CONFIG = {
    // 本地測試
    local: 'ws://localhost:8081',
    // Zeabur 測試 (替換為您的域名)
    zeabur: 'wss://python-learn.zeabur.app/ws'
};

/**
 * 測試 WebSocket 連接
 * @param {string} url - WebSocket URL
 * @param {string} name - 測試名稱
 */
async function testWebSocketConnection(url, name) {
    console.log(`\n🔍 測試 ${name}: ${url}`);
    console.log('─'.repeat(50));
    
    return new Promise((resolve) => {
        const ws = new WebSocket(url);
        const timeout = setTimeout(() => {
            console.log('❌ 連接超時');
            ws.close();
            resolve(false);
        }, 10000);
        
        ws.on('open', () => {
            clearTimeout(timeout);
            console.log('✅ WebSocket 連接成功');
            
            // 發送測試消息
            const testMessage = {
                type: 'join_room',
                room_id: 'test-room',
                user_id: 'test-user',
                username: 'Test User'
            };
            
            console.log('📤 發送測試消息:', JSON.stringify(testMessage, null, 2));
            ws.send(JSON.stringify(testMessage));
        });
        
        ws.on('message', (data) => {
            try {
                const message = JSON.parse(data.toString());
                console.log('📨 收到消息:', JSON.stringify(message, null, 2));
                
                if (message.type === 'room_joined') {
                    console.log('🎉 房間加入成功');
                    
                    // 發送 ping 測試
                    setTimeout(() => {
                        ws.send(JSON.stringify({ type: 'ping', timestamp: Date.now() }));
                    }, 1000);
                }
                
                if (message.type === 'pong') {
                    console.log('🏓 Ping-Pong 測試成功');
                    ws.close();
                    resolve(true);
                }
                
            } catch (error) {
                console.log('📨 收到原始消息:', data.toString());
            }
        });
        
        ws.on('error', (error) => {
            clearTimeout(timeout);
            console.log('❌ WebSocket 錯誤:', error.message);
            resolve(false);
        });
        
        ws.on('close', (code, reason) => {
            clearTimeout(timeout);
            console.log(`🔌 連接關閉: ${code} - ${reason || '無原因'}`);
            if (code === 1000) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    });
}

/**
 * 主測試函數
 */
async function runTests() {
    console.log('🚀 WebSocket 連接測試開始');
    console.log('═'.repeat(50));
    
    const results = {};
    
    // 測試本地連接
    if (process.argv.includes('--local')) {
        results.local = await testWebSocketConnection(TEST_CONFIG.local, '本地服務器');
    }
    
    // 測試 Zeabur 連接
    if (process.argv.includes('--zeabur') || process.argv.length === 2) {
        results.zeabur = await testWebSocketConnection(TEST_CONFIG.zeabur, 'Zeabur 部署');
    }
    
    // 顯示測試結果
    console.log('\n📊 測試結果總結');
    console.log('═'.repeat(50));
    
    Object.entries(results).forEach(([name, success]) => {
        const status = success ? '✅ 成功' : '❌ 失敗';
        console.log(`${name}: ${status}`);
    });
    
    const allPassed = Object.values(results).every(result => result);
    
    if (allPassed) {
        console.log('\n🎉 所有測試通過！WebSocket 服務正常運行');
        process.exit(0);
    } else {
        console.log('\n⚠️ 部分測試失敗，請檢查服務器配置');
        process.exit(1);
    }
}

// 顯示使用說明
if (process.argv.includes('--help')) {
    console.log(`
WebSocket 測試工具

使用方法:
  node test-websocket-local.js              # 測試 Zeabur 部署
  node test-websocket-local.js --local      # 測試本地服務器
  node test-websocket-local.js --zeabur     # 測試 Zeabur 部署
  node test-websocket-local.js --help       # 顯示此說明

注意事項:
1. 確保已安裝 Node.js 和 ws 模組: npm install ws
2. 本地測試需要先啟動本地 WebSocket 服務器
3. Zeabur 測試需要修改 TEST_CONFIG.zeabur 為您的域名
    `);
    process.exit(0);
}

// 檢查依賴
try {
    require('ws');
} catch (error) {
    console.log('❌ 缺少 ws 模組，請執行: npm install ws');
    process.exit(1);
}

// 運行測試
runTests().catch(console.error); 