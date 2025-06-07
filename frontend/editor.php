<?php
// 獲取房間ID
$roomId = $_GET['room_id'] ?? '';
if (empty($roomId)) {
    header('Location: rooms.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python代碼編輯器</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; display: flex; flex-direction: column; min-height: 100vh; }
        .container { display: flex; flex-wrap: wrap; padding: 15px; gap: 15px; flex-grow: 1; }
        .editor-panel { flex: 2; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); min-width: 500px; display: flex; flex-direction: column; }
        .ai-panel { flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); min-width: 350px; display: flex; flex-direction: column; }
        #code-editor { width: 100%; box-sizing: border-box; }
        .CodeMirror { height: 400px !important; border: 1px solid #ddd; border-radius: 4px; }
        .editor-panel h3, .ai-panel h3 { margin-top: 0; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; font-size: 1.3em; }
        .editor-panel h4, .ai-panel h4 { margin-top: 15px; margin-bottom: 8px; color: #555; font-size: 1.1em; }
        #output, #ai-response, #chatMessages {
            background: #f8f9fa; padding: 15px; border-radius: 5px; min-height: 100px; 
            white-space: pre-wrap; word-wrap: break-word; border: 1px solid #e9ecef; 
            flex-grow:1; overflow-y:auto;
        }
        #chatMessages { min-height: 150px; margin-bottom: 10px; }
        .editor-panel button, .ai-panel button, .ai-panel select, .ai-panel textarea, #chatInput {
            padding: 9px 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95em;
            margin-right: 5px;
            margin-bottom: 5px;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }
        .editor-panel button:hover, .ai-panel button:hover {
            opacity: 0.85;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        #loginSection { /* ... existing styles ... */ }
        #editorContainer { /* ... existing styles ... */ }
        
        /* --- Tab Buttons --- */
        #panel-tabs button {
            padding: 10px 15px;
            border: 1px solid #ccc;
            background-color: #fff;
            cursor: pointer;
            margin-left: -1px; /* Remove double borders */
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            font-size: 1em;
            color: #007bff;
        }
        #panel-tabs button:first-child { margin-left: 0; }
        #panel-tabs button.active-tab {
            background-color: #007bff;
            color: white;
            border-bottom: 1px solid #007bff; /* Make bottom border same color as background */
            font-weight: bold;
        }
        #panel-tabs button:not(.active-tab):hover {
            background-color: #e9ecef;
        }
        #panel-tabs button.new-message-indicator {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255, 0, 0, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 0, 0, 0);
            }
        }

        /* --- Chat Panel Specifics --- */
        #chatInputContainer { display: flex; margin-top: 10px; }
        #chatInput {
            flex-grow: 1;
            border-right: none;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            margin-right: 0;
        }
        #sendChatMessageButton {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
        #sendChatMessageButton:hover { background-color: #0056b3; border-color: #0056b3; }

        /* --- Individual Chat Messages --- */
        .chat-message-item {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 7px;
            line-height: 1.4;
            max-width: 90%; /* Prevent messages from taking full width */
            word-wrap: break-word; /* Ensure long words break */
        }
        .chat-message-item strong { color: #0d6efd; }
        .chat-message-item small { color: #6c757d; font-size: 0.8em; margin-left: 8px; }

        .chat-message-user { /* Current user's message */
            background-color: #d1e7fd; 
            margin-left: auto; /* Align to right */
            text-align: left; /* Keep text left aligned within the bubble */
        }
        .chat-message-other { /* Other users' messages */
            background-color: #ffffff; 
            border: 1px solid #e9ecef;
            margin-right: auto; /* Align to left */
        }
        .chat-message-system {
            background-color: #f8f9fa; 
            font-style: italic;
            color: #545b62;
            text-align: center;
            max-width: 100%;
            font-size: 0.9em;
            border: 1px dashed #ced4da;
        }
        .chat-message-ai {
            background-color: #e2f0d9; 
            border-left: 3px solid #5cb85c;
        }
        .chat-message-ai strong { color: #3f7f3f; } /* Darker green for AI name */

        /* History Modal Styles (from previous step, ensure they are here) */
        .history-modal-container { /* ... */ }
        .history-modal-content { /* ... */ }
        /* ... other history modal styles ... */
        .history-item { /* ... */ }

        /* Footer styling */
        footer {
            text-align: center; padding: 15px; background-color: #343a40; 
            color: white; margin-top: auto; font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>Python代碼編輯器 - 房間 <?php echo htmlspecialchars($roomId); ?></h1>
        <button class="btn-primary" onclick="window.location.href='rooms.php'">返回房間列表</button>
    </div>
    
    <div class="container">
        <div class="editor-panel">
            <h3>Python 編輯器 (房間: <span id="currentRoomId"><?php echo htmlspecialchars($roomId); ?></span>)</h3>
            <div>
                <textarea id="code-editor" style="width: 98%; height: 400px; border: 1px solid #ccc; font-family: monospace; font-size: 14px; padding: 10px;"></textarea>
            </div>
            <div style="margin-top:10px;">
                <button onclick="saveCode()" style="background-color: #007bff; color:white; padding:8px 15px; border:none; border-radius:4px; cursor:pointer;">💾 保存</button>
                <button onclick="showHistory()" style="background-color: #17a2b8; color:white; padding:8px 15px; border:none; border-radius:4px; cursor:pointer;">📚 歷史</button>
                <button onclick="runCode()" style="background-color: #28a745; color:white; padding:8px 15px; border:none; border-radius:4px; cursor:pointer;">▶️ 運行</button>
                <button onclick="copyCode()" style="background-color: #ffc107; color:black; padding:8px 15px; border:none; border-radius:4px; cursor:pointer;">📋 複製</button>
                <button onclick="downloadCode()" style="background-color: #6f42c1; color:white; padding:8px 15px; border:none; border-radius:4px; cursor:pointer;">📄 下載</button>
                <input type="file" id="importFile" accept=".py,.txt" style="display:none;" onchange="handleImportFile(event)">
                <button onclick="document.getElementById('importFile').click()" style="background-color: #fd7e14; color:white; padding:8px 15px; border:none; border-radius:4px; cursor:pointer;">📁 導入</button>
                <button onclick="clearOutput()" style="background-color: #6c757d; color:white; padding:8px 15px; border:none; border-radius:4px; cursor:pointer;">🗑️ 清除輸出</button>
            </div>
            <h4>輸出:</h4>
            <pre id="output" style="background: #e9ecef; padding: 15px; border-radius: 5px; min-height: 100px; white-space: pre-wrap; word-wrap: break-word;"></pre>
        </div>
        
        <div class="ai-panel" style="display: flex; flex-direction: column;">
            <div id="panel-tabs" style="margin-bottom: 10px; border-bottom: 1px solid #ccc;">
                <button id="aiTabButton" onclick="showAiPanel()">🤖 AI 助教</button> <!-- Removed inline style, will be controlled by active-tab class -->
                <button id="chatTabButton" onclick="showChatPanel()">💬 聊天室</button> <!-- Removed inline style -->
        </div>

            <div id="aiContentPanel" style="flex-grow: 1; display: flex; flex-direction: column;">
                <h3 style="margin-top:0;">AI 助教</h3>
                <select id="ai-action" style="padding: 8px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="explain">解釋代碼</option>
                    <option value="check_errors">檢查錯誤</option>
                    <option value="suggest_improvements">改進建議</option>
                    <option value="analyze_conflict">AI衝突分析 (測試用)</option>
                    <option value="answer_question">回答問題</option>
                </select>
                <div id="question-area" style="display:none; margin-bottom:10px;">
                    <textarea id="ai-question" placeholder="請輸入您的問題..." style="width: 95%; height: 60px; padding: 8px; border-radius: 4px; border: 1px solid #ccc;"></textarea>
                </div>
                <button onclick="callAI()" style="background-color: #007bff; color:white; padding:10px 15px; border:none; border-radius:4px; cursor:pointer; margin-bottom:10px;">發送請求給 AI</button>
                <h4>AI 回應:</h4>
                <div id="ai-response" style="background: #e9ecef; padding: 15px; border-radius: 5px; min-height: 150px; white-space: pre-wrap; word-wrap: break-word; overflow-y: auto; flex-grow: 1;">點擊上方按鈕獲取AI輔助</div>
                <button id="shareToChatButton" onclick="shareAiResponseToChat()" style="background-color: #28a745; color:white; padding:8px 15px; border:none; border-radius:4px; cursor:pointer; margin-top:10px; display:none;"><span role="img" aria-label="share">📢</span> 分享到聊天室</button>
    </div>

            <div id="chatContentPanel" style="flex-grow: 1; display: none; flex-direction: column;">
                <h3 style="margin-top:0;">聊天室 (<span id="chatRoomName"><?php echo htmlspecialchars($roomId); ?></span>)</h3>
                <div id="chatUsers" style="font-size:0.9em; color: #6c757d; margin-bottom:10px;">在線: <span id="onlineUserNames">我</span></div>
                <div id="chatMessages" style="/* Styles moved to CSS block */">
                    <!-- 聊天訊息將顯示在這裡 -->
            </div>
                <div id="chatInputContainer"> <!-- Added container for input and button -->
                    <input type="text" id="chatInput" placeholder="輸入訊息...">
                    <button id="sendChatMessageButton" onclick="sendChatMessage()">發送</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 簡化的歷史記錄模態框 -->
    <div id="historyModalContainer" class="history-modal-container"> <!-- Changed id and added class -->
        <div class="history-modal-content">
            <div class="history-modal-header">
                <h2>歷史紀錄</h2>
                <span class="close-history-btn" onclick="closeHistory()">&times;</span>
            </div>
            <div class="history-modal-body">
                <div id="historyList"><!-- 歷史項目將由JS填充 --></div>
            </div>
            <div class="history-modal-footer">
                <button onclick="closeHistory()" style="padding: 8px 15px; background-color: #6c757d; color:white; border:none; border-radius:4px; cursor:pointer;">關閉</button>
            </div>
        </div>
    </div>

    <!-- 衝突解決模態框 - Bootstrap 5 格式 -->
    <div class="modal fade" id="conflictModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle"></i> 代碼衝突檢測
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- 操作類型顯示 -->
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <div class="me-2">
                            <span id="conflictChangeType">
                                <i class="fas fa-edit text-primary"></i> 一般編輯
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            衝突檢測：<strong><span id="conflictUserName">其他同學</span></strong> 和您的修改發生衝突
                        </div>
                    </div>
                    
                    <!-- 版本信息 -->
                    <div id="conflictVersionInfo" class="alert alert-secondary mb-3">
                        <i class="fas fa-info-circle"></i> 正在分析版本差異...
                    </div>
                    
                    <!-- 代碼差異對比 -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-code-branch"></i> 代碼差異對比</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-md-6">
                                    <div class="bg-info bg-opacity-10 p-3 border-end">
                                        <h6 class="text-info mb-2"><i class="fas fa-user"></i> 您的版本</h6>
                                        <pre id="myCodeVersion" class="bg-white p-2 rounded border" style="max-height: 250px; overflow-y: auto; font-size: 0.9em; white-space: pre-wrap;">(載入中...)</pre>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="bg-warning bg-opacity-10 p-3">
                                        <h6 class="text-warning mb-2"><i class="fas fa-users"></i> <span id="otherUserName">對方</span>的版本</h6>
                                        <pre id="otherCodeVersion" class="bg-white p-2 rounded border" style="max-height: 250px; overflow-y: auto; font-size: 0.9em; white-space: pre-wrap;">(載入中...)</pre>
                                    </div>
                                </div>
                            </div>
                            <!-- 差異摘要 -->
                            <div class="bg-light p-2 border-top">
                                <small class="text-muted">
                                    <i class="fas fa-chart-bar"></i> 
                                    <span id="diffSummary">正在分析差異...</span>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- AI分析區域 -->
                    <div id="conflictAIAnalysis" class="card" style="display: none;">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-robot"></i> AI 衝突分析</h6>
                        </div>
                        <div class="card-body">
                            <div id="aiAnalysisContent">
                                <div class="text-center py-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">載入中...</span>
                                    </div>
                                    <h6 class="mt-2 mb-0">AI 正在分析衝突...</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="ConflictResolver.resolveConflict('force')">
                        <i class="fas fa-lock"></i> 強制修改
                    </button>
                    <button type="button" class="btn btn-info" onclick="ConflictResolver.shareToChat()">
                        <i class="fas fa-comments"></i> 分享到聊天室
                    </button>
                    <button type="button" class="btn btn-warning" onclick="ConflictResolver.requestAIAnalysis()">
                        <i class="fas fa-robot"></i> AI協助分析
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> 關閉
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 引入衝突處理JavaScript模組 -->
    <script src="assets/js/conflict.js"></script>
    
    <script>
        // PHP 變數傳遞到 JavaScript
        const roomId = <?php echo json_encode($roomId); ?>;
        const userId = <?php echo json_encode($userId); ?>;
        const currentUsername = <?php echo json_encode($username); ?>; 

        let editor = null; // CodeMirror editor instance
        let ws = null;     // WebSocket connection
        let unsavedChanges = false;
        let autoSaveTimer = null;
        const AUTO_SAVE_INTERVAL = 300000; // 5 minutes
        const AUTO_SAVE_DELAY = 10000;     // 10 seconds after last edit
        let lastEditTime = 0;
        let isInitializing = true; // 🆕 標記是否在初始化期
        let joinTime = Date.now(); // 🆕 記錄加入時間

        let currentPanel = 'ai'; // 'ai' or 'chat'

        // Function to update online users display
        function updateOnlineUsersDisplay(users) {
            const onlineUsersDiv = document.getElementById('onlineUserNames');
            if (onlineUsersDiv) {
                if (users && users.length > 0) {
                     // Filter out current user if present, then add "我" at the beginning
                    const otherUserNames = users.filter(u => u.userId !== userId && u.username !== currentUsername).map(u => u.username);
                    const displayNames = ["我", ...otherUserNames];
                    onlineUsersDiv.textContent = displayNames.join(', ');
                } else {
                    onlineUsersDiv.textContent = '只有我';
                }
            }
        }

        // 🆕 發送代碼變更到 WebSocket
        function sendCodeChange(forceUpdate = false) {
            if (!ws || ws.readyState !== WebSocket.OPEN || !editor) {
                console.log('WebSocket 未連接或編輯器未初始化，跳過代碼同步');
                return;
            }

            // 🆕 檢查是否在初始化期（加入房間後 10 秒內）
            const timeSinceJoin = Date.now() - joinTime;
            if (!forceUpdate && timeSinceJoin < 10000) {
                console.log(`跳過代碼同步：在初始化期內 (${timeSinceJoin}ms < 10000ms)`);
                return;
            }

            const code = editor.getValue();
            const message = {
                type: 'code_change',
                code: code,
                change_type: 'edit',
                room_id: roomId,
                user_id: userId,
                username: currentUsername,
                timestamp: Date.now()
            };

            if (forceUpdate) {
                message.force_update = true;
            }

            console.log('發送代碼變更到 WebSocket:', { length: code.length, force: forceUpdate });
            ws.send(JSON.stringify(message));
        }

        // ... (showAiPanel, showChatPanel, callAI, shareAiResponseToChat, sendChatMessage, addChatMessageToUI functions are assumed to be correctly defined from previous steps) ...
        
        function connectWebSocket() {
            const wsUrl = `ws://localhost:8080?room_id=${encodeURIComponent(roomId)}&user_id=${encodeURIComponent(userId)}&username=${encodeURIComponent(currentUsername)}`;
            ws = new WebSocket(wsUrl);

            ws.onopen = function() {
                console.log('WebSocket 連線已建立。');
                addChatMessageToUI('已連接到協作伺服器。', '系統', 'system');
                
                // 🆕 發送加入房間請求
                ws.send(JSON.stringify({ 
                        type: 'join_room',
                        room_id: roomId,
                        user_id: userId,
                    username: currentUsername 
                    }));
                };
                
            ws.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                console.log('WebSocket received:', data);

                switch (data.type) {
                    case 'room_joined': // 🆕 處理房間加入成功
                        console.log('成功加入房間:', data);
                        if (editor && data.current_code) {
                            // 設置初始代碼時，暫時停用同步
                            isInitializing = true;
                            editor.setValue(data.current_code);
                            setTimeout(() => {
                                isInitializing = false;
                                console.log('初始化期結束，代碼同步已啟用');
                            }, 2000); // 2秒後啟用同步
                        }
                        if (data.users) updateOnlineUsersDisplay(data.users);
                        addChatMessageToUI('成功加入房間，已載入最新代碼。', '系統', 'system', Date.now());
                            break;
                    case 'initial_data': // Backend sends initial code, history, and user list
                        if(editor) editor.setValue(data.code || '');
                        // if(data.history) { /* Process history if needed, localStorage is primary for now */ }
                        if(data.users) updateOnlineUsersDisplay(data.users);
                        addChatMessageToUI('成功加入房間，已載入最新代碼和用戶列表。', '系統', 'system', Date.now());
                        break;
                    case 'user_list':
                        updateOnlineUsersDisplay(data.users);
                            break;
                        case 'user_joined':
                        addChatMessageToUI(`${data.username} 加入了房間。`, '系統', 'system', data.timestamp || Date.now());
                        if(data.users) updateOnlineUsersDisplay(data.users); // Update if backend sends full list
                        else if(ws.readyState === WebSocket.OPEN) ws.send(JSON.stringify({ type: 'get_user_list', room_id: roomId }));// Or request it
                            break;
                        case 'user_left':
                        addChatMessageToUI(`${data.username} (${data.userId}) 離開了房間。`, '系統', 'system', data.timestamp || Date.now());
                        if(data.users) updateOnlineUsersDisplay(data.users); // Update if backend sends full list
                        else if(ws.readyState === WebSocket.OPEN) ws.send(JSON.stringify({ type: 'get_user_list', room_id: roomId }));// Or request it
                            break;
                    case 'chat_message':
                        let msgType = 'other';
                        if (data.user_id === userId) msgType = 'user';
                        else if (data.username && data.username.startsWith('🤖 AI 助教')) msgType = 'ai';
                        else if (data.is_system_message) msgType = 'system';
                        
                        addChatMessageToUI(data.message, data.username, msgType, data.timestamp);
                        
                        if (currentPanel !== 'chat' && data.user_id !== userId) {
                            const chatTabButton = document.getElementById('chatTabButton');
                            chatTabButton.classList.add('new-message-indicator');
                            chatTabButton.innerHTML = '💬 聊天室 <span style="color:red; font-weight:normal;">(新!)</span>';
                            }
                            break;
                    case 'code_changed': // 🆕 處理其他用戶的代碼變更
                        if (data.user_id !== userId && editor) {
                            // 暫時停用同步，避免循環觸發
                            isInitializing = true;
                            
                            // Preserve cursor/scroll position before setting value
                            const cursor = editor.getCursor();
                            const scrollInfo = editor.getScrollInfo();
                            editor.setValue(data.code);
                            editor.setCursor(cursor);
                            editor.scrollTo(scrollInfo.left, scrollInfo.top);
                            
                            console.log(data.username + " 更新了代碼。");
                            
                            // 重新啟用同步
                            setTimeout(() => {
                                isInitializing = false;
                            }, 1000);
                            
                            // Visual feedback
                            document.getElementById('code-editor').style.borderColor = '#28a745';
                            setTimeout(() => { 
                                document.getElementById('code-editor').style.borderColor = '#ccc'; 
                            }, 1000);
                            }
                            break;
                    case 'code_change':
                        if (data.user_id !== userId && editor) {
                            // Preserve cursor/scroll position before setting value
                            const cursor = editor.getCursor();
                            const scrollInfo = editor.getScrollInfo();
                            editor.setValue(data.code);
                            editor.setCursor(cursor);
                            editor.scrollTo(scrollInfo.left, scrollInfo.top);
                            console.log(data.username + " 更新了代碼。");
                            // Potentially add a subtle notification that code was updated by others
                            document.getElementById('code-editor').style.borderColor = '#28a745'; // Green border flash
                            setTimeout(() => { document.getElementById('code-editor').style.borderColor = '#ccc'; }, 1000);
                            }
                            break;
                        case 'error':
                        console.error("WebSocket 錯誤訊息:", data.message);
                        addChatMessageToUI(`錯誤: ${data.message}`, '系統', 'system', Date.now());
                        if (data.action === 'join_room_failed_duplicate_username' || (data.message && data.message.includes("用戶名已被使用"))) {
                            ws.close();
                            document.getElementById('loginSection').style.display = 'block';
                            const editorContainer = document.getElementById('editorContainer');
                            if(editorContainer) editorContainer.style.display = 'none';
                            const nameInput = document.getElementById('username');
                            if(nameInput) nameInput.focus();
                            alert("此用戶名已被使用或無效，請選擇其他名稱。");
                        } else {
                            alert('發生錯誤: ' + data.message);
                        }
                            break;
                    default:
                        console.log("收到未處理的 WebSocket 訊息:", data);
                }
            };

            ws.onclose = function() {
                console.log('WebSocket 連線已關閉。');
                addChatMessageToUI('與伺服器斷開連線。嘗試重新連接中...', '系統', 'system');
                // Simple reconnect logic
                setTimeout(connectWebSocket, 5000); // Attempt to reconnect after 5 seconds
            };

            ws.onerror = function(error) {
                console.error('WebSocket 錯誤:', error);
                addChatMessageToUI('WebSocket 連線發生錯誤。', '系統', 'system');
            };
        }

        // Initialize CodeMirror and WebSocket on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            // 🆕 重置加入時間
            joinTime = Date.now();
            isInitializing = true;
            console.log('重置加入時間，開始初始化:', new Date(joinTime).toTimeString());
            
            // Initialize CodeMirror
            editor = CodeMirror.fromTextArea(document.getElementById('code-editor'), {
                mode: 'python',
                theme: 'material-darker',
                lineNumbers: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 4,
                tabSize: 4,
                indentWithTabs: false,
                lineWrapping: true,
                styleActiveLine: true,
                extraKeys: {
                    "Ctrl-S": function(cm) { saveCode(); },
                    "Cmd-S": function(cm) { saveCode(); },
                    "Ctrl-Enter": function(cm) { runCode(); },
                    "Cmd-Enter": function(cm) { runCode(); },
                    "Ctrl-/": "toggleComment",
                    "Cmd-/": "toggleComment",
                }
            });
            
            // Set initial content if any (e.g., from localStorage or PHP)
            // editor.setValue(localStorage.getItem(`code_${roomId}`) || '# 在這裡開始編寫 Python 代碼\nprint("Hello, collaborative world!")');

            // Auto-save functionality
            editor.on('change', function(cm, change) {
                if (change.origin !== 'setValue') { // Don't trigger on programmatic changes
                    unsavedChanges = true;
                    lastEditTime = Date.now();
                    
                    // 🆕 如果不在初始化期，發送代碼變更到 WebSocket
                    if (!isInitializing) {
                        // 延遲發送，避免過於頻繁的同步
                        clearTimeout(window.syncTimeout);
                        window.syncTimeout = setTimeout(() => {
                            sendCodeChange();
                        }, 300); // 300ms 延遲
                } else {
                        console.log('跳過代碼同步：正在初始化期');
                    }
                    
                    // Clear existing auto-save timer
                    clearTimeout(autoSaveTimer);
                    // Set a new timer to save after a delay
                    autoSaveTimer = setTimeout(() => {
                        if (unsavedChanges && (Date.now() - lastEditTime >= AUTO_SAVE_DELAY)) {
                            saveCode(true); // true for auto-save
                        }
                    }, AUTO_SAVE_DELAY + 500); // Add a small buffer
                }
            });

            // Periodically check for auto-save if no changes are made for a while
            setInterval(() => {
                if (unsavedChanges && (Date.now() - lastEditTime >= AUTO_SAVE_INTERVAL)) {
                    saveCode(true);
                }
            }, AUTO_SAVE_INTERVAL);

            // Connect WebSocket
            connectWebSocket();

            // Initialize UI (e.g., show AI panel by default)
            showAiPanel(); 
            
            // Event listener for chat input Enter key
            const chatInput = document.getElementById('chatInput');
            if(chatInput) {
                chatInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sendChatMessage();
                    }
                });
            }

            // Load history from localStorage on page load
            showHistory();

            // Update room name display
            const chatRoomNameDiv = document.getElementById('chatRoomName');
            if(chatRoomNameDiv) chatRoomNameDiv.textContent = roomId;

            // Initial call to set up UI correctly based on current panel state
            if (currentPanel === 'ai') {
                showAiPanel();
                } else {
                showChatPanel();
            }
            
            // 🆕 設置一個較長的初始化期，確保所有組件都已準備好
                setTimeout(() => {
                isInitializing = false;
                console.log('前端初始化期結束，代碼同步已完全啟用');
            }, 5000); // 5秒後完全啟用同步
        });

        // ... (rest of the script, including saveCode, loadCode, showHistory, displayHistoryModal, etc.)

        function showChatPanel() {
            document.getElementById('aiContentPanel').style.display = 'none';
            document.getElementById('chatContentPanel').style.display = 'flex';
            document.getElementById('aiTabButton').classList.remove('active-tab');
            document.getElementById('aiTabButton').classList.remove('new-message-indicator'); // Should not be on AI tab
            document.getElementById('chatTabButton').classList.add('active-tab');
            document.getElementById('chatTabButton').innerHTML = '💬 聊天室'; // Reset new message text
            document.getElementById('chatTabButton').classList.remove('new-message-indicator'); // Clear indicator on click
            document.getElementById('chatTabButton').style.fontWeight = 'bold';
            currentPanel = 'chat';
            const chatMessages = document.getElementById('chatMessages');
            if(chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
            const chatInput = document.getElementById('chatInput');
            if(chatInput) chatInput.focus();
        }

        // Modify event listener for chatTabButton to also remove class
        document.getElementById('chatTabButton').addEventListener('click', function() {
            this.innerHTML = '💬 聊天室'; 
            this.style.fontWeight = 'bold';
            this.classList.remove('new-message-indicator'); // Ensure indicator class is removed
        });
    </script>
</body>
</html> 