<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>完整功能測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .test-section { background: white; margin: 20px 0; padding: 20px; border-radius: 10px; }
        .test-result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; background: #007bff; color: white; }
        button:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .nav { display: flex; gap: 10px; margin-bottom: 20px; }
        .nav a { padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
        .nav a:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Python教學多人協作平台 - 完整功能測試</h1>
        
        <div class="nav">
            <a href="frontend/index.php">首頁</a>
            <a href="frontend/rooms.php">房間列表</a>
            <a href="frontend/editor.php?room_id=1">代碼編輯器</a>
            <a href="test_complete.php">功能測試</a>
        </div>
        
        <div class="test-section">
            <h2>📋 系統狀態檢查</h2>
            <button onclick="checkSystemStatus()">檢查系統狀態</button>
            <div id="system-status"></div>
        </div>
        
        <div class="test-section">
            <h2>👤 用戶認證測試</h2>
            <button onclick="testLogin()">測試登入</button>
            <button onclick="testCurrentUser()">檢查當前用戶</button>
            <button onclick="testLogout()">測試登出</button>
            <div id="auth-results"></div>
        </div>
        
        <div class="test-section">
            <h2>🏠 房間管理測試</h2>
            <button onclick="testCreateRoom()">創建測試房間</button>
            <button onclick="testListRooms()">獲取房間列表</button>
            <button onclick="testJoinRoom()">加入房間</button>
            <div id="room-results"></div>
        </div>
        
        <div class="test-section">
            <h2>💻 代碼操作測試</h2>
            <button onclick="testSaveCode()">保存代碼</button>
            <button onclick="testLoadCode()">載入代碼</button>
            <button onclick="testExecuteCode()">執行代碼</button>
            <div id="code-results"></div>
        </div>
        
        <div class="test-section">
            <h2>🤖 AI助教測試</h2>
            <button onclick="testExplainCode()">解釋代碼</button>
            <button onclick="testCheckErrors()">檢查錯誤</button>
            <button onclick="testSuggestImprovements()">改進建議</button>
            <button onclick="testAnswerQuestion()">詢問問題</button>
            <div id="ai-results"></div>
        </div>
        
        <div class="test-section">
            <h2>📊 測試結果總結</h2>
            <div id="test-summary"></div>
        </div>
    </div>

    <script>
        let testResults = [];
        
        function addResult(category, test, success, message, data = null) {
            const result = { category, test, success, message, data, timestamp: new Date() };
            testResults.push(result);
            
            const resultDiv = document.createElement('div');
            resultDiv.className = `test-result ${success ? 'success' : 'error'}`;
            resultDiv.innerHTML = `
                <strong>${test}:</strong> ${message}
                ${data ? `<pre>${JSON.stringify(data, null, 2)}</pre>` : ''}
            `;
            
            const container = document.getElementById(category + '-results');
            container.appendChild(resultDiv);
            
            updateSummary();
        }
        
        function updateSummary() {
            const summary = document.getElementById('test-summary');
            const total = testResults.length;
            const passed = testResults.filter(r => r.success).length;
            const failed = total - passed;
            
            summary.innerHTML = `
                <h3>測試統計</h3>
                <p>總測試數: ${total}</p>
                <p class="success">通過: ${passed}</p>
                <p class="error">失敗: ${failed}</p>
                <p>成功率: ${total > 0 ? Math.round(passed / total * 100) : 0}%</p>
            `;
        }
        
        async function apiCall(endpoint, data = null, method = 'GET') {
            try {
                const config = {
                    method: method,
                    headers: { 'Content-Type': 'application/json' }
                };
                
                if (data && method !== 'GET') {
                    config.body = JSON.stringify(data);
                }
                
                const response = await fetch(`/backend/api/${endpoint}`, config);
                const result = await response.json();
                
                return result;
            } catch (error) {
                throw new Error(`網絡錯誤: ${error.message}`);
            }
        }
        
        async function checkSystemStatus() {
            try {
                // 檢查各個API端點
                const endpoints = ['auth.php', 'rooms.php', 'code.php', 'ai.php'];
                const results = {};
                
                for (const endpoint of endpoints) {
                    try {
                        const response = await fetch(`/backend/api/${endpoint}`);
                        results[endpoint] = response.status === 200 ? '正常' : '異常';
                    } catch (error) {
                        results[endpoint] = '無法連接';
                    }
                }
                
                addResult('system', '系統狀態檢查', true, '系統狀態檢查完成', results);
            } catch (error) {
                addResult('system', '系統狀態檢查', false, error.message);
            }
        }
        
        async function testLogin() {
            try {
                const result = await apiCall('auth.php', {
                    action: 'login',
                    username: 'test_user_' + Date.now(),
                    user_type: 'student'
                }, 'POST');
                
                addResult('auth', '用戶登入', result.success, result.message, result.data);
            } catch (error) {
                addResult('auth', '用戶登入', false, error.message);
            }
        }
        
        async function testCurrentUser() {
            try {
                const result = await apiCall('auth.php?action=current');
                addResult('auth', '獲取當前用戶', result.success, result.message, result.data);
            } catch (error) {
                addResult('auth', '獲取當前用戶', false, error.message);
            }
        }
        
        async function testLogout() {
            try {
                const result = await apiCall('auth.php', { action: 'logout' }, 'POST');
                addResult('auth', '用戶登出', result.success, result.message);
            } catch (error) {
                addResult('auth', '用戶登出', false, error.message);
            }
        }
        
        async function testCreateRoom() {
            try {
                const result = await apiCall('rooms.php', {
                    action: 'create',
                    room_name: '測試房間_' + Date.now(),
                    description: '這是一個測試房間',
                    max_users: 10
                }, 'POST');
                
                addResult('room', '創建房間', result.success, result.message, result.data);
                
                // 保存房間ID供後續測試使用
                if (result.success && result.data) {
                    window.testRoomId = result.data.room_id;
                }
            } catch (error) {
                addResult('room', '創建房間', false, error.message);
            }
        }
        
        async function testListRooms() {
            try {
                const result = await apiCall('rooms.php?action=list');
                addResult('room', '獲取房間列表', result.success, result.message, 
                    result.data ? `找到 ${result.data.length} 個房間` : null);
            } catch (error) {
                addResult('room', '獲取房間列表', false, error.message);
            }
        }
        
        async function testJoinRoom() {
            try {
                const roomId = window.testRoomId || 1;
                const result = await apiCall('rooms.php', {
                    action: 'join',
                    room_id: roomId
                }, 'POST');
                
                addResult('room', '加入房間', result.success, result.message, result.data);
            } catch (error) {
                addResult('room', '加入房間', false, error.message);
            }
        }
        
        async function testSaveCode() {
            try {
                const testCode = `# 測試代碼
print("Hello, World!")
x = 10
y = 20
print(f"x + y = {x + y}")`;
                
                const result = await apiCall('code.php', {
                    action: 'save',
                    room_id: window.testRoomId || 1,
                    code: testCode
                }, 'POST');
                
                addResult('code', '保存代碼', result.success, result.message, result.data);
            } catch (error) {
                addResult('code', '保存代碼', false, error.message);
            }
        }
        
        async function testLoadCode() {
            try {
                const result = await apiCall(`code.php?action=load&room_id=${window.testRoomId || 1}`);
                addResult('code', '載入代碼', result.success, result.message, 
                    result.data ? `代碼長度: ${result.data.code?.length || 0} 字符` : null);
            } catch (error) {
                addResult('code', '載入代碼', false, error.message);
            }
        }
        
        async function testExecuteCode() {
            try {
                const result = await apiCall('code.php', {
                    action: 'execute',
                    code: 'print("Hello from test!")\nprint(2 + 3)'
                }, 'POST');
                
                addResult('code', '執行代碼', result.success, result.message, result.data);
            } catch (error) {
                addResult('code', '執行代碼', false, error.message);
            }
        }
        
        async function testExplainCode() {
            try {
                const result = await apiCall('ai.php', {
                    action: 'explain',
                    code: 'def hello():\n    print("Hello, World!")\nhello()',
                    user_id: 'test_user'
                }, 'POST');
                
                addResult('ai', 'AI解釋代碼', result.success, result.message, 
                    result.data ? `回應長度: ${result.data.analysis?.length || 0} 字符` : null);
            } catch (error) {
                addResult('ai', 'AI解釋代碼', false, error.message);
            }
        }
        
        async function testCheckErrors() {
            try {
                const result = await apiCall('ai.php', {
                    action: 'check_errors',
                    code: 'print("Hello World")',
                    user_id: 'test_user'
                }, 'POST');
                
                addResult('ai', 'AI檢查錯誤', result.success, result.message, 
                    result.data ? `回應長度: ${result.data.analysis?.length || 0} 字符` : null);
            } catch (error) {
                addResult('ai', 'AI檢查錯誤', false, error.message);
            }
        }
        
        async function testSuggestImprovements() {
            try {
                const result = await apiCall('ai.php', {
                    action: 'suggest_improvements',
                    code: 'x=1\ny=2\nprint(x+y)',
                    user_id: 'test_user'
                }, 'POST');
                
                addResult('ai', 'AI改進建議', result.success, result.message, 
                    result.data ? `回應長度: ${result.data.analysis?.length || 0} 字符` : null);
            } catch (error) {
                addResult('ai', 'AI改進建議', false, error.message);
            }
        }
        
        async function testAnswerQuestion() {
            try {
                const result = await apiCall('ai.php', {
                    action: 'answer_question',
                    question: '什麼是Python？',
                    user_id: 'test_user'
                }, 'POST');
                
                addResult('ai', 'AI回答問題', result.success, result.message, 
                    result.data ? `回應長度: ${result.data.analysis?.length || 0} 字符` : null);
            } catch (error) {
                addResult('ai', 'AI回答問題', false, error.message);
            }
        }
        
        // 頁面載入時自動檢查系統狀態
        window.addEventListener('load', function() {
            checkSystemStatus();
        });
    </script>
</body>
</html> 