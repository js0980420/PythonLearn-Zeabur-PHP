<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API測試 - Python協作平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>API測試頁面</h2>
        
        <!-- 測試認證API -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>認證API測試</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>登入測試</h6>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="testUsername" placeholder="用戶名" value="測試用戶">
                        </div>
                        <button class="btn btn-primary" onclick="testLogin()">測試登入</button>
                        <button class="btn btn-secondary" onclick="testCurrentUser()">檢查當前用戶</button>
                        <button class="btn btn-warning" onclick="testLogout()">測試登出</button>
                    </div>
                    <div class="col-md-6">
                        <h6>結果：</h6>
                        <pre id="authResult" class="bg-light p-2" style="height: 200px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- 測試房間API -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>房間API測試</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>房間操作</h6>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="testRoomName" placeholder="房間名稱" value="測試房間">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="testRoomDesc" placeholder="房間描述" value="這是一個測試房間">
                        </div>
                        <button class="btn btn-primary" onclick="testCreateRoom()">創建房間</button>
                        <button class="btn btn-secondary" onclick="testListRooms()">列出房間</button>
                        <div class="mt-2">
                            <input type="number" class="form-control" id="testRoomId" placeholder="房間ID" style="width: 100px; display: inline-block;">
                            <button class="btn btn-success" onclick="testJoinRoom()">加入房間</button>
                            <button class="btn btn-danger" onclick="testLeaveRoom()">離開房間</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>結果：</h6>
                        <pre id="roomResult" class="bg-light p-2" style="height: 200px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- 測試AI API -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>AI API測試</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>AI功能</h6>
                        <div class="mb-3">
                            <textarea class="form-control" id="testCode" rows="3" placeholder="Python代碼">print("Hello, World!")</textarea>
                        </div>
                        <button class="btn btn-primary" onclick="testExplainCode()">解釋代碼</button>
                        <button class="btn btn-secondary" onclick="testCheckError()">檢查錯誤</button>
                        <button class="btn btn-success" onclick="testImproveCode()">改進建議</button>
                        <div class="mt-2">
                            <input type="text" class="form-control" id="testQuestion" placeholder="問題" value="什麼是Python？">
                            <button class="btn btn-info mt-1" onclick="testAskQuestion()">詢問問題</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>結果：</h6>
                        <pre id="aiResult" class="bg-light p-2" style="height: 200px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- 測試資料庫連接 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>資料庫測試</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-primary" onclick="testDatabase()">測試資料庫連接</button>
                <pre id="dbResult" class="bg-light p-2 mt-2" style="height: 100px; overflow-y: auto;"></pre>
            </div>
        </div>
    </div>

    <script>
        // 認證API測試
        async function testLogin() {
            const username = document.getElementById('testUsername').value;
            try {
                const response = await fetch('backend/api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'login',
                        username: username,
                        user_type: 'student'
                    })
                });
                const result = await response.json();
                document.getElementById('authResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('authResult').textContent = '錯誤: ' + error.message;
            }
        }

        async function testCurrentUser() {
            try {
                const response = await fetch('backend/api/auth.php?action=current');
                const result = await response.json();
                document.getElementById('authResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('authResult').textContent = '錯誤: ' + error.message;
            }
        }

        async function testLogout() {
            try {
                const response = await fetch('backend/api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });
                const result = await response.json();
                document.getElementById('authResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('authResult').textContent = '錯誤: ' + error.message;
            }
        }

        // 房間API測試
        async function testCreateRoom() {
            const roomName = document.getElementById('testRoomName').value;
            const description = document.getElementById('testRoomDesc').value;
            try {
                const response = await fetch('backend/api/rooms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create',
                        room_name: roomName,
                        description: description,
                        max_users: 10
                    })
                });
                const result = await response.json();
                document.getElementById('roomResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('roomResult').textContent = '錯誤: ' + error.message;
            }
        }

        async function testListRooms() {
            try {
                const response = await fetch('backend/api/rooms.php?action=list');
                const result = await response.json();
                document.getElementById('roomResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('roomResult').textContent = '錯誤: ' + error.message;
            }
        }

        async function testJoinRoom() {
            const roomId = document.getElementById('testRoomId').value;
            try {
                const response = await fetch('backend/api/rooms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'join',
                        room_id: parseInt(roomId)
                    })
                });
                const result = await response.json();
                document.getElementById('roomResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('roomResult').textContent = '錯誤: ' + error.message;
            }
        }

        async function testLeaveRoom() {
            const roomId = document.getElementById('testRoomId').value;
            try {
                const response = await fetch('backend/api/rooms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'leave',
                        room_id: parseInt(roomId)
                    })
                });
                const result = await response.json();
                document.getElementById('roomResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('roomResult').textContent = '錯誤: ' + error.message;
            }
        }

        // AI API測試
        async function testExplainCode() {
            const code = document.getElementById('testCode').value;
            try {
                const response = await fetch('backend/api/ai.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'explain',
                        code: code
                    })
                });
                const result = await response.json();
                document.getElementById('aiResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('aiResult').textContent = '錯誤: ' + error.message;
            }
        }

        async function testCheckError() {
            const code = document.getElementById('testCode').value;
            try {
                const response = await fetch('backend/api/ai.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'check',
                        code: code
                    })
                });
                const result = await response.json();
                document.getElementById('aiResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('aiResult').textContent = '錯誤: ' + error.message;
            }
        }

        async function testImproveCode() {
            const code = document.getElementById('testCode').value;
            try {
                const response = await fetch('backend/api/ai.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'improve',
                        code: code
                    })
                });
                const result = await response.json();
                document.getElementById('aiResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('aiResult').textContent = '錯誤: ' + error.message;
            }
        }

        async function testAskQuestion() {
            const question = document.getElementById('testQuestion').value;
            try {
                const response = await fetch('backend/api/ai.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'question',
                        question: question
                    })
                });
                const result = await response.json();
                document.getElementById('aiResult').textContent = JSON.stringify(result, null, 2);
            } catch (error) {
                document.getElementById('aiResult').textContent = '錯誤: ' + error.message;
            }
        }

        // 資料庫測試
        async function testDatabase() {
            try {
                const response = await fetch('test_db.php');
                const result = await response.text();
                document.getElementById('dbResult').textContent = result;
            } catch (error) {
                document.getElementById('dbResult').textContent = '錯誤: ' + error.message;
            }
        }
    </script>
</body>
</html> 