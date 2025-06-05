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
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; display: flex; gap: 20px; }
        .editor-panel { flex: 2; background: white; padding: 20px; border-radius: 10px; }
        .ai-panel { flex: 1; background: white; padding: 20px; border-radius: 10px; }
        textarea { width: 100%; height: 400px; font-family: monospace; }
        button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .output { background: #f8f9fa; padding: 15px; margin-top: 10px; border-radius: 5px; }
        
        /* 模態框樣式 */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: none;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow: hidden;
        }
        
        .modal-header {
            background: #007bff;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            opacity: 0.7;
        }
        
        .modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .history-item {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 10px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .history-item:hover {
            background: #e9ecef;
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .history-meta {
            font-size: 0.9em;
            color: #6c757d;
        }
        
        .history-preview {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 0.9em;
            margin: 10px 0;
            max-height: 100px;
            overflow: hidden;
        }
        
        .history-actions {
            text-align: right;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 0.9em;
        }

        /* 衝突模態框增強樣式 */
        #conflictModal .modal-content {
            max-width: 95vw;
            width: 95vw;
            max-height: 90vh;
            overflow-y: auto;
        }

        #conflictModal .modal-body {
            max-height: none;
        }

        .conflict-code-container {
            display: flex;
            gap: 15px;
            margin: 15px 0;
        }

        .conflict-code-panel {
            flex: 1;
            min-width: 250px;
        }

        .conflict-code-panel h4 {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 5px 5px 0 0;
            margin: 0 0 0 0;
            font-size: 1em;
        }

        .conflict-code-panel:first-child h4 {
            background: #e3f2fd;
            color: #1976d2;
        }

        .conflict-code-panel:nth-child(2) h4 {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .conflict-code-panel:last-child h4 {
            background: #fff3e0;
            color: #f57c00;
        }

        .conflict-code-panel pre {
            margin: 0;
            border-radius: 0 0 5px 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .modal-footer {
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 15px 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .modal-footer button {
            min-width: 120px;
        }

        #conflictDetails {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 12px;
            margin-top: 15px;
        }

        .conflict-info-badge {
            display: inline-block;
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-right: 8px;
        }

        .conflict-stats {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            font-size: 0.9em;
            color: #6c757d;
        }

        .conflict-stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .diff-highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 2px;
        }

        .ai-analysis-section {
            margin-top: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            overflow: hidden;
        }

        .ai-analysis-header {
            background: #007bff;
            color: white;
            padding: 10px 15px;
            font-weight: 500;
        }

        .ai-analysis-content {
            padding: 15px;
            background: #f8f9fa;
        }

        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
            border-width: 0.2em;
        }

        @media (max-width: 768px) {
            .conflict-code-container {
                flex-direction: column;
            }
            
            .modal-footer {
                flex-direction: column;
            }
            
            .modal-footer button {
                width: 100%;
                margin: 2px 0;
            }
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
            <h3>代碼編輯器</h3>
            <div>
                <button class="btn-success" onclick="saveCode()">保存</button>
                <button class="btn-primary" onclick="runCode()">運行</button>
                <button class="btn-primary" onclick="downloadCode()">下載</button>
                <button class="btn-warning" onclick="handleFileImport()">📁 導入檔案</button>
                <button class="btn-secondary" onclick="showHistory()">歷史記錄</button>
            </div>
            <textarea id="code-editor" placeholder="輸入Python代碼...">print("Hello, World!")</textarea>
            <div class="output">
                <h4>執行結果：</h4>
                <pre id="output"></pre>
            </div>
        </div>
        
        <div class="ai-panel">
            <h3>🤖 AI助教</h3>
            <div style="margin-bottom: 15px;">
                <button class="btn-primary" onclick="aiExplainCode()">📖 解釋代碼</button>
                <button class="btn-primary" onclick="aiCheckErrors()">🔍 檢查錯誤</button>
                <button class="btn-primary" onclick="aiSuggestImprovements()">⚡ 改進建議</button>
                <button class="btn-primary" onclick="aiAnalyzeConflict()">⚔️ 衝突分析</button>
                <button class="btn-primary" onclick="aiAskQuestion()">❓ 詢問問題</button>
            </div>
            
            <!-- AI問題輸入區域 -->
            <div id="ai-question-input" style="display: none; margin-bottom: 15px;">
                <textarea id="ai-question-text" placeholder="請輸入您的問題..." style="width: 100%; height: 80px; margin-bottom: 10px;"></textarea>
                <div>
                    <button class="btn-success" onclick="submitAIQuestion()">發送問題</button>
                    <button class="btn-secondary" onclick="hideAIQuestionInput()">取消</button>
                </div>
            </div>
            
            <!-- AI結果顯示區域 -->
            <div class="output" id="ai-result" style="max-height: 400px; overflow-y: auto;">
                <div style="text-align: center; color: #666; padding: 20px;">
                    <i style="font-size: 24px;">🤖</i>
                    <p>AI助教等待您的指令</p>
                    <small>點擊上方按鈕開始使用AI功能</small>
                </div>
            </div>
            
            <!-- AI狀態指示器 -->
            <div id="ai-status" style="text-align: center; margin-top: 10px; font-size: 12px; color: #666;">
                <span id="ai-status-text">待機中</span>
            </div>
        </div>
    </div>

    <!-- 歷史記錄模態框 -->
    <div id="historyModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>代碼歷史記錄</h3>
                <span class="close" onclick="closeHistory()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="historyList">載入中...</div>
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
        const roomId = '<?php echo htmlspecialchars($_GET["room_id"] ?? "default_room"); ?>';
        
        // 檢查是否為頁面重新整理
        const isReload = (
            (performance.navigation && performance.navigation.type === performance.navigation.TYPE_RELOAD) ||
            (performance.getEntriesByType && performance.getEntriesByType('navigation')[0]?.type === 'reload')
        );
        
        if (isReload) {
            // 如果是重新整理，清除登入狀態並跳轉到首頁
            localStorage.removeItem('user_info');
            localStorage.removeItem('userId');
            localStorage.removeItem('username');
            localStorage.removeItem('userType');
            alert('頁面重新整理，請重新登入');
            window.location.href = `index.php?room_id_join=${roomId}`;
        }
        
        // 檢查用戶登入狀態
        const userInfoStr = localStorage.getItem('user_info');
        if (!userInfoStr) {
            alert('未登入，跳轉到登入頁面');
            window.location.href = `index.php?room_id_join=${roomId}`;
        }
        
        const userInfo = JSON.parse(userInfoStr);
        const userId = userInfo.user_id;
        const username = userInfo.username;
        
        if (!userId || !username) {
            alert('用戶信息不完整，跳轉到登入頁面');
            window.location.href = `index.php?room_id_join=${roomId}`;
        }
        
        alert(`editor.php: 已登入用戶 - ID: ${userId}, 用戶名: ${username}, 房間: ${roomId}`);

        // 初始化ConflictResolver
        if (window.ConflictResolver) {
            window.ConflictResolver.initialize();
        }

        // 創建WebSocket管理器
        window.wsManager = {
            websocket: null,
            currentRoom: roomId,
            currentUser: userId,
            isConnected: () => window.wsManager.websocket && window.wsManager.websocket.readyState === WebSocket.OPEN,
            sendMessage: (data) => {
                if (window.wsManager.isConnected()) {
                    window.wsManager.websocket.send(JSON.stringify(data));
                    return true;
                }
                return false;
            }
        };

        let websocket = null;
        let lastCodeContent = ''; // 追蹤最後的代碼內容
        const wsPort = <?php echo (isset($_ENV['WEBSOCKET_PORT']) ? $_ENV['WEBSOCKET_PORT'] : 8080); ?>;
        const wsHost = <?php echo (isset($_ENV['WEBSOCKET_HOST']) ? json_encode($_ENV['WEBSOCKET_HOST']) : json_encode('localhost')); ?>;
        
        function connectWebSocket() {
            try {
                websocket = new WebSocket(`ws://${wsHost}:${wsPort}`);
                
                websocket.onopen = function() {
                    console.log('WebSocket連接成功');
                    console.log('用戶信息:', { userId, username, roomId });
                    
                    // 更新WebSocket管理器
                    window.wsManager.websocket = websocket;
                    
                    // 加入房間
                    websocket.send(JSON.stringify({
                        type: 'join_room',
                        room_id: roomId,
                        user_id: userId,
                        username: username
                    }));
                };
                
                websocket.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    console.log('收到WebSocket消息:', data);
                    
                    switch(data.type) {
                        case 'code_changed':  // 修正：WebSocket服務器發送的是 code_changed
                            if (data.user_id !== userId) {
                                // 其他用戶的代碼變更
                                document.getElementById('code-editor').value = data.code;
                                console.log(`代碼已更新 - 來自用戶: ${data.username}`);
                            }
                            break;
                        case 'room_joined':
                            console.log('成功加入房間:', data.room_id);
                            // 載入房間當前代碼
                            if (data.current_code) {
                                document.getElementById('code-editor').value = data.current_code;
                            }
                            break;
                        case 'user_joined':
                            console.log('用戶加入:', data.username);
                            break;
                        case 'user_left':
                            console.log('用戶離開:', data.username);
                            break;
                        case 'conflict_main_changer_decision':
                            // 主改方決定界面
                            if (window.ConflictResolver) {
                                const conflictData = {
                                    conflict_id: data.conflict_id,
                                    conflict_type: data.conflict_type,
                                    room_id: data.room_id,
                                    main_changer: data.main_changer_name,
                                    other_changer: data.other_changer_name,
                                    local_code: data.main_changer_code,
                                    remote_code: data.other_changer_code,
                                    change_type: data.main_change_type,
                                    message: data.message
                                };
                                
                                console.log('🎯 主改方決定:', conflictData);
                                
                                // 顯示主改方決定界面
                                window.ConflictResolver.showMainChangerDecision(conflictData);
                            }
                            break;
                        case 'conflict_waiting_decision':
                            // 非主改方等待界面
                            if (window.ConflictResolver) {
                                const waitingData = {
                                    conflict_id: data.conflict_id,
                                    conflict_type: data.conflict_type,
                                    main_changer: data.main_changer_name,
                                    other_changer: data.other_changer_name,
                                    local_code: data.other_changer_code,
                                    remote_code: data.main_changer_code,
                                    change_type: data.main_change_type,
                                    message: data.message
                                };
                                
                                console.log('⏳ 等待主改方決定:', waitingData);
                                window.ConflictResolver.showWaitingForDecision(waitingData);
                            }
                            break;
                        case 'edit_blocked_waiting_decision':
                            // 編輯被阻擋，顯示等待消息
                            if (window.ConflictResolver) {
                                window.ConflictResolver.showEditBlocked({
                                    main_changer: data.main_changer_name,
                                    change_type: data.main_change_type
                                });
                            }
                            break;
                        case 'conflict_resolved':
                            console.log('衝突已被其他用戶解決:', data);
                            
                            // 更新代碼編輯器為解決後的代碼
                            if (data.final_code) {
                                document.getElementById('code-editor').value = data.final_code;
                            }
                            
                            // 使用新的ConflictResolver處理解決結果
                            if (window.ConflictResolver) {
                                window.ConflictResolver.handleConflictResolved(data);
                            }
                            break;
                        case 'ai_analysis_result':
                            if (window.ConflictResolver) {
                                window.ConflictResolver.handleAIAnalysisResponse(data);
                            } else {
                                console.error('ConflictResolver not available for AI analysis');
                            }
                            break;
                        case 'conflict_shared':
                            alert('衝突已分享到聊天室，請在聊天中討論解決方案');
                            break;
                        case 'error':
                            console.error('WebSocket錯誤:', data.message);
                            break;
                    }
                };
                
                websocket.onclose = function() {
                    console.log('WebSocket連接關閉');
                    // 5秒後重連
                    setTimeout(connectWebSocket, 5000);
                };
                
                websocket.onerror = function(error) {
                    console.error('WebSocket錯誤:', error);
                };
            } catch (error) {
                console.error('WebSocket連接失敗:', error);
                setTimeout(connectWebSocket, 5000);
            }
        }
        
        // 創建全局wsManager對象供ConflictResolver使用
        window.wsManager = {
            websocket: null,
            currentRoom: roomId,
            currentUser: userId,
            isConnected: () => websocket && websocket.readyState === WebSocket.OPEN,
            sendMessage: (data) => {
                if (websocket && websocket.readyState === WebSocket.OPEN) {
                    websocket.send(JSON.stringify(data));
                    console.log('📤 發送消息:', data.type);
                    return true;
                }
                console.log('❌ WebSocket未連接');
                return false;
            }
        };

        // 頁面載入時連接WebSocket和載入代碼
        window.addEventListener('load', function() {
            connectWebSocket();
            loadRoomCode();
            
            // 更新wsManager的websocket引用
            window.wsManager.websocket = websocket;
        });
        
        async function loadRoomCode() {
            try {
                const result = await apiCall('code.php?action=load&room_id=' + roomId, {}, 'GET');
                if (result.success && result.data && result.data.code) {
                    document.getElementById('code-editor').value = result.data.code;
                }
            } catch (error) {
                console.error('載入代碼失敗:', error);
            }
        }
        
        // 頁面關閉時斷開連接
        window.addEventListener('beforeunload', function() {
            if (websocket) {
                websocket.close();
            }
        });
        
        async function apiCall(endpoint, data, method = 'POST') {
            const config = {
                method: method,
                headers: { 'Content-Type': 'application/json' }
            };
            
            if (method !== 'GET' && data) {
                config.body = JSON.stringify(data);
            }
            
            const response = await fetch(`/backend/api/${endpoint}`, config);
            return await response.json();
        }
        
        async function saveCode() {
            const code = document.getElementById('code-editor').value;
            const result = await apiCall('code.php', {
                action: 'save',
                room_id: roomId,
                code: code,
                user_id: userId,
                username: username
            });
            alert(result.message);
            
            // 通過WebSocket同步代碼
            if (websocket && websocket.readyState === WebSocket.OPEN) {
                websocket.send(JSON.stringify({
                    type: 'code_change',
                    room_id: roomId,
                    user_id: userId,
                    code: code,
                    change_type: 'save'
                }));
            }
        }
        
        // 🔥 增強代碼變更檢測，包含各種大量操作
        let lastCodeLength = 0;
        let lastLineCount = 0;
        
        // 檢測大量代碼變更的類型
        function detectChangeType(oldCode, newCode) {
            const oldLength = oldCode.length;
            const newLength = newCode.length;
            const oldLines = oldCode.split('\n').length;
            const newLines = newCode.split('\n').length;
            
            const lengthChange = Math.abs(newLength - oldLength);
            const lineChange = Math.abs(newLines - oldLines);
            
            // 🔥 檢測各種大量操作類型
            if (lengthChange > Math.max(oldLength * 0.5, 100)) {
                if (newLength > oldLength) {
                    return 'paste'; // 大量貼上
                } else {
                    return 'cut'; // 大量剪下/刪除
                }
            }
            
            if (lineChange > Math.max(oldLines * 0.3, 5)) {
                if (newLines > oldLines) {
                    return 'paste'; // 多行貼上
                } else {
                    return 'cut'; // 多行剪下
                }
            }
            
            // 整個編輯器內容被替換
            if (oldLength > 50 && lengthChange > oldLength * 0.8) {
                return 'replace';
            }
            
            return 'edit'; // 一般編輯
        }

        // 監聽代碼編輯器的變化 - 增強版
        let codeChangeTimeout = null;
        document.getElementById('code-editor').addEventListener('input', function() {
            const currentCode = this.value;
            const changeType = detectChangeType(lastCodeContent || '', currentCode);
            
            // 記錄當前狀態
            lastCodeLength = currentCode.length;
            lastLineCount = currentCode.split('\n').length;
            
            // 防抖：500ms後發送變更
            clearTimeout(codeChangeTimeout);
            codeChangeTimeout = setTimeout(function() {
                if (websocket && websocket.readyState === WebSocket.OPEN) {
                    websocket.send(JSON.stringify({
                        type: 'code_change',
                        room_id: roomId,
                        user_id: userId,
                        code: currentCode,
                        change_type: changeType
                    }));
                }
                lastCodeContent = currentCode; // 更新記錄
            }, 500);
        });

        // 🔥 監聽貼上事件
        document.getElementById('code-editor').addEventListener('paste', function(e) {
            setTimeout(() => {
                const code = this.value;
                if (websocket && websocket.readyState === WebSocket.OPEN) {
                    websocket.send(JSON.stringify({
                        type: 'code_change',
                        room_id: roomId,
                        user_id: userId,
                        code: code,
                        change_type: 'paste'
                    }));
                }
            }, 100); // 稍微延遲確保貼上內容已生效
        });

        // 🔥 監聽剪下事件
        document.getElementById('code-editor').addEventListener('cut', function(e) {
            setTimeout(() => {
                const code = this.value;
                if (websocket && websocket.readyState === WebSocket.OPEN) {
                    websocket.send(JSON.stringify({
                        type: 'code_change',
                        room_id: roomId,
                        user_id: userId,
                        code: code,
                        change_type: 'cut'
                    }));
                }
            }, 100);
        });

        // 🔥 監聽鍵盤快捷鍵
        document.getElementById('code-editor').addEventListener('keydown', function(e) {
            // Ctrl+V (貼上)
            if (e.ctrlKey && e.key === 'v') {
                setTimeout(() => {
                    const code = this.value;
                    if (websocket && websocket.readyState === WebSocket.OPEN) {
                        websocket.send(JSON.stringify({
                            type: 'code_change',
                            room_id: roomId,
                            user_id: userId,
                            code: code,
                            change_type: 'paste'
                        }));
                    }
                }, 100);
            }
            
            // Ctrl+X (剪下)
            if (e.ctrlKey && e.key === 'x') {
                setTimeout(() => {
                    const code = this.value;
                    if (websocket && websocket.readyState === WebSocket.OPEN) {
                        websocket.send(JSON.stringify({
                            type: 'code_change',
                            room_id: roomId,
                            user_id: userId,
                            code: code,
                            change_type: 'cut'
                        }));
                    }
                }, 100);
            }
        });
        
        async function runCode() {
            const code = document.getElementById('code-editor').value;
            const result = await apiCall('code.php', {
                action: 'execute',
                code: code
            });
            document.getElementById('output').textContent = result.data?.output || result.message;
        }
        
        async function explainCode() {
            const code = document.getElementById('code-editor').value;
            const result = await apiCall('ai.php', {
                action: 'explain',
                code: code,
                user_id: userId
            });
            document.getElementById('ai-result').innerHTML = result.data?.analysis || result.message;
        }
        
        async function checkErrors() {
            const code = document.getElementById('code-editor').value;
            const result = await apiCall('ai.php', {
                action: 'check_errors',
                code: code,
                user_id: userId
            });
            document.getElementById('ai-result').innerHTML = result.data?.analysis || result.message;
        }
        
        async function suggestImprovements() {
            const code = document.getElementById('code-editor').value;
            const result = await apiCall('ai.php', {
                action: 'suggest_improvements',
                code: code,
                user_id: userId
            });
            document.getElementById('ai-result').innerHTML = result.data?.analysis || result.message;
        }
        
        async function askQuestion() {
            const question = prompt('請輸入問題：');
            if (!question) return;
            
            const result = await apiCall('ai.php', {
                action: 'answer_question',
                question: question,
                user_id: userId
            });
            document.getElementById('ai-result').innerHTML = result.data?.analysis || result.message;
        }
        
        function downloadCode() {
            const code = document.getElementById('code-editor').value;
            const blob = new Blob([code], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'code.py';
            a.click();
            URL.revokeObjectURL(url);
        }
        
        // 歷史記錄相關函數
        async function showHistory() {
            document.getElementById('historyModal').style.display = 'block';
            await loadHistory();
        }

        function closeHistory() {
            document.getElementById('historyModal').style.display = 'none';
        }

        async function loadHistory() {
            try {
                const result = await apiCall('history.php?action=list&room_id=' + roomId, {}, 'GET');
                const historyList = document.getElementById('historyList');
                
                if (result.success && result.data && result.data.history) {
                    const history = result.data.history;
                    
                    if (history.length === 0) {
                        historyList.innerHTML = '<p>暫無歷史記錄</p>';
                        return;
                    }
                    
                    let html = '';
                    history.forEach(item => {
                        html += `
                            <div class="history-item">
                                <div class="history-header">
                                    <strong>版本 ${item.version}</strong>
                                    <div class="history-meta">
                                        ${item.user_id} | ${item.saved_at} | ${item.code_length} 字符
                                    </div>
                                </div>
                                <div class="history-preview">${item.code_preview}</div>
                                <div class="history-actions">
                                    <button class="btn-primary btn-small" onclick="loadVersion(${item.id})">載入此版本</button>
                                    <button class="btn-secondary btn-small" onclick="deleteVersion(${item.id})">刪除</button>
                                </div>
                            </div>
                        `;
                    });
                    
                    historyList.innerHTML = html;
                } else {
                    historyList.innerHTML = '<p>載入歷史記錄失敗</p>';
                }
            } catch (error) {
                console.error('載入歷史記錄失敗:', error);
                document.getElementById('historyList').innerHTML = '<p>載入歷史記錄失敗</p>';
            }
        }
        
        async function loadVersion(historyId) {
            try {
                const result = await apiCall('history.php?action=load&history_id=' + historyId, {}, 'GET');
                
                if (result.success && result.data && result.data.code) {
                    document.getElementById('code-editor').value = result.data.code;
                    closeHistory();
                    alert('版本載入成功！');
                    
                    // 通過WebSocket同步代碼
                    if (websocket && websocket.readyState === WebSocket.OPEN) {
                        websocket.send(JSON.stringify({
                            type: 'code_change',
                            room_id: roomId,
                            user_id: userId,
                            code: result.data.code
                        }));
                    }
                } else {
                    alert('載入版本失敗：' + result.message);
                }
            } catch (error) {
                console.error('載入版本失敗:', error);
                alert('載入版本失敗');
            }
        }
        
        async function deleteVersion(historyId) {
            if (!confirm('確定要刪除這個版本嗎？')) {
                return;
            }
            
            try {
                const result = await apiCall('history.php', {
                    action: 'delete',
                    history_id: historyId
                });
                
                if (result.success) {
                    alert('版本刪除成功！');
                    await loadHistory(); // 重新載入歷史記錄
                } else {
                    alert('刪除版本失敗：' + result.message);
                }
            } catch (error) {
                console.error('刪除版本失敗:', error);
                alert('刪除版本失敗');
            }
        }
        
        // 點擊模態框外部關閉
        window.onclick = function(event) {
            const modal = document.getElementById('historyModal');
            if (event.target === modal) {
                closeHistory();
            }
        }

        // ==================== 衝突解決器已經在 conflict.js 中定義 ====================
        
        // 等待 conflict.js 載入後再進行初始化
        function waitForConflictResolver() {
            if (typeof ConflictResolverManager !== 'undefined') {
                // ConflictResolverManager 已載入，進行初始化
                initializeConflictResolver();
                } else {
                // 還沒載入，100ms 後再檢查
                setTimeout(waitForConflictResolver, 100);
            }
        }
        
        function initializeConflictResolver() {
            if (window.conflictResolver) {
                return; // 已經初始化過了
            }
            
            // 確保衝突彈窗不會自動顯示 - 只在真正發生衝突時才顯示
            const conflictModal = document.getElementById('conflictModal');
            if (conflictModal) {
                conflictModal.style.display = 'none'; // 確保初始隱藏
            }
            
            // 創建衝突解決器實例
            window.conflictResolver = new ConflictResolverManager();
            console.log('✅ ConflictResolver 已初始化');
        }
        
        // 檢查是否有測試參數要自動顯示衝突彈窗 (僅用於測試)
        function checkForTestConflict() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('test_conflict') === 'true') {
                // 僅在測試模式下自動顯示衝突
                console.log('🧪 測試模式：自動顯示衝突彈窗');
                setTimeout(() => {
                    if (window.conflictResolver) {
                        window.conflictResolver.showConflict({
                            other_user_id: '測試用戶',
                            conflict_type: 'same_line_conflict',
                            yourCode: 'print("Hello World")',
                            otherCode: 'print("Hello PHP")',
                            total_conflicts: 1
                        });
                    }
                }, 1000);
            }
        }
        
        // 頁面載入完成後初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 等待衝突解決器載入
            waitForConflictResolver();
            
            // 檢查測試參數
            checkForTestConflict();
            
            console.log('✅ 編輯器初始化完成');
        });

                 // 創建全局衝突解決器實例 (等待 conflict.js 載入後)
        function createGlobalConflictResolver() {
            if (window.conflictResolver) {
                return; // 已經創建過了
            }
            
            if (typeof ConflictResolverManager !== 'undefined') {
                window.conflictResolver = new ConflictResolverManager();
                console.log('✅ 全域 ConflictResolver 實例已創建:', window.conflictResolver);
                } else {
                console.warn('⚠️ ConflictResolverManager 尚未載入');
            }
        }

        // 全局函數供HTML調用
        function resolveConflict(solution) {
            if (window.conflictResolver) {
                window.conflictResolver.resolveConflict(solution);
            }
        }

        function askAIForConflictHelp() {
            if (window.conflictResolver) {
                window.conflictResolver.requestAIAnalysis();
            } else {
                console.error('ConflictResolver not available');
            }
        }

        // 全局函數供HTML調用 (使用 conflict.js 中的 ConflictResolverManager)
        window.resolveConflict = function(solution) {
            if (window.conflictResolver) {
                window.conflictResolver.resolveConflict(solution);
                } else {
                console.error('ConflictResolver not available');
            }
        };

        window.askAIForConflictHelp = function() {
                if (window.conflictResolver) {
                    window.conflictResolver.requestAIAnalysis();
            } else {
                console.error('ConflictResolver not available');
            }
        };
        // 確保衝突彈窗初始狀態為隱藏
        document.addEventListener('DOMContentLoaded', function() {
            const conflictModal = document.getElementById('conflictModal');
            if (conflictModal) {
                conflictModal.style.display = 'none';
            }
                 });

        // 狀態消息顯示函數
        function showStatusMessage(message, type = 'info', timeout = 5000) {
            const outputArea = document.getElementById('output');
            if (!outputArea) {
                console.log('Status:', message);
                return;
            }
            
            // 清除之前的狀態消息
            clearStatusMessage();
            
            const statusDiv = document.createElement('div');
            statusDiv.id = 'status-message';
            statusDiv.className = `status-message status-${type}`;
            statusDiv.textContent = message;
            
            // 添加樣式
            statusDiv.style.cssText = `
                margin: 10px 0;
                padding: 10px;
                border-radius: 4px;
                font-weight: bold;
                ${type === 'info' ? 'background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;' : ''}
                ${type === 'warning' ? 'background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7;' : ''}
                ${type === 'error' ? 'background-color: #f8d7da; color: #721c24; border: 1px solid #f1b0b7;' : ''}
                ${type === 'success' ? 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : ''}
            `;
            
            outputArea.insertBefore(statusDiv, outputArea.firstChild);
            
            // 如果有超時時間，自動移除
            if (timeout > 0) {
                setTimeout(() => {
                    clearStatusMessage();
                }, timeout);
            }
        }
        
        function clearStatusMessage() {
            const existingMessage = document.getElementById('status-message');
            if (existingMessage) {
                existingMessage.remove();
            }
        }

        // 🔥 檔案導入功能 - 更新以包含衝突檢測
        function handleFileImport() {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = '.py';
            fileInput.style.display = 'none';
            
            fileInput.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const importedCode = e.target.result;
                        const editor = document.getElementById('code-editor');
                        const oldCode = editor.value;
                        
                        // 🚨 設定新代碼前先檢測是否為大量變更
                        editor.value = importedCode;
                        
                        // 🔥 立即發送導入變更，觸發衝突檢測
                        if (websocket && websocket.readyState === WebSocket.OPEN) {
                            websocket.send(JSON.stringify({
                                type: 'code_change',
                                room_id: roomId,
                                user_id: userId,
                                code: importedCode,
                                change_type: 'import'
                            }));
                        }
                        
                        lastCodeContent = importedCode;
                        showMessage('檔案導入成功！', 'success');
                    };
                    reader.readAsText(file);
                } else {
                    showMessage('未選擇檔案', 'error');
                }
            };
            
            document.body.appendChild(fileInput);
            fileInput.click();
            document.body.removeChild(fileInput);
        }

        // 🔥 歷史載入功能 - 更新以包含衝突檢測
        async function handleLoadVersion(historyId) {
            try {
                const response = await fetch('/backend/api/history.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'load',
                        room_id: roomId,
                        history_id: historyId
                    })
                });

                const result = await response.json();
                if (result.success) {
                    const loadedCode = result.data.code_content || result.data.code || '';
                    const editor = document.getElementById('code-editor');
                    const oldCode = editor.value;
                    
                    // 🚨 設定載入的代碼
                    editor.value = loadedCode;
                    
                    // 🔥 立即發送載入變更，觸發衝突檢測
                    if (websocket && websocket.readyState === WebSocket.OPEN) {
                        websocket.send(JSON.stringify({
                            type: 'code_change',
                            room_id: roomId,
                            user_id: userId,
                            code: loadedCode,
                            change_type: 'load'
                        }));
                    }
                    
                    lastCodeContent = loadedCode;
                    hideHistoryModal();
                    showMessage('歷史版本載入成功！', 'success');
                } else {
                    showMessage('載入失敗：' + result.message, 'error');
                }
            } catch (error) {
                console.error('載入歷史版本錯誤:', error);
                showMessage('載入失敗，請稍後重試', 'error');
            }
        }

        // ========================================
        // 🤖 AI助教功能實現
        // ========================================

        // AI狀態管理
        function setAIStatus(status, isLoading = false) {
            const statusElement = document.getElementById('ai-status-text');
            if (statusElement) {
                statusElement.textContent = isLoading ? `${status}...` : status;
                statusElement.style.color = isLoading ? '#007bff' : '#666';
            }
        }

        // 顯示AI結果
        function displayAIResult(result, title) {
            const resultDiv = document.getElementById('ai-result');
            if (!resultDiv) return;

            let content = '';
            
            if (result.success) {
                // 處理不同的回應格式
                let aiContent = '';
                if (typeof result.data === 'string') {
                    aiContent = result.data;
                } else if (result.data && result.data.analysis) {
                    aiContent = result.data.analysis;
                } else if (result.data && result.data.content) {
                    aiContent = result.data.content;
                } else {
                    aiContent = JSON.stringify(result.data, null, 2);
                }

                content = `
                    <div style="border-left: 4px solid #28a745; padding-left: 15px; margin-bottom: 15px;">
                        <h4 style="color: #28a745; margin: 0 0 10px 0;">✅ ${title}</h4>
                        <div style="white-space: pre-wrap; line-height: 1.6;">${aiContent}</div>
                        <small style="color: #666; margin-top: 10px; display: block;">
                            ⏱️ ${new Date().toLocaleTimeString()} | 
                            📊 ${result.data && result.data.token_usage ? result.data.token_usage + ' tokens' : '處理完成'}
                        </small>
                    </div>
                `;
            } else {
                content = `
                    <div style="border-left: 4px solid #dc3545; padding-left: 15px; margin-bottom: 15px;">
                        <h4 style="color: #dc3545; margin: 0 0 10px 0;">❌ ${title}失敗</h4>
                        <div style="color: #dc3545;">${result.message || '未知錯誤'}</div>
                        <small style="color: #666; margin-top: 10px; display: block;">
                            ⏱️ ${new Date().toLocaleTimeString()}
                        </small>
                    </div>
                `;
            }

            resultDiv.innerHTML = content + resultDiv.innerHTML;
            setAIStatus('完成', false);
        }

        // 通用AI API調用函數
        async function callAI(action, data) {
            try {
                setAIStatus('正在處理', true);
                
                const response = await fetch('/backend/api/ai.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: action,
                        room_id: roomId,
                        ...data
                    })
                });

                const result = await response.json();
                console.log(`AI API響應 (${action}):`, result);
                
                return result;
            } catch (error) {
                console.error('AI API 調用錯誤:', error);
                return {
                    success: false,
                    message: '網路錯誤: ' + error.message
                };
            }
        }

        // 1. 解釋代碼功能
        async function aiExplainCode() {
            const code = document.getElementById('code-editor').value.trim();
            
            if (!code) {
                alert('請先輸入一些代碼');
                return;
            }

            const result = await callAI('explain', { code: code });
            displayAIResult(result, '代碼解釋');
        }

        // 2. 檢查錯誤功能
        async function aiCheckErrors() {
            const code = document.getElementById('code-editor').value.trim();
            
            if (!code) {
                alert('請先輸入一些代碼');
                return;
            }

            const result = await callAI('check_errors', { 
                code: code,
                error_types: ['syntax', 'logic', 'performance', 'security']
            });
            displayAIResult(result, '錯誤檢查');
        }

        // 3. 改進建議功能
        async function aiSuggestImprovements() {
            const code = document.getElementById('code-editor').value.trim();
            
            if (!code) {
                alert('請先輸入一些代碼');
                return;
            }

            const result = await callAI('suggest_improvements', { 
                code: code,
                focus_areas: ['performance', 'readability', 'best_practices']
            });
            displayAIResult(result, '改進建議');
        }

        // 4. 衝突分析功能
        async function aiAnalyzeConflict() {
            // 檢查是否有最近的衝突數據
            const currentCode = document.getElementById('code-editor').value.trim();
            
            if (!currentCode) {
                alert('請先輸入一些代碼');
                return;
            }

            // 模擬衝突場景或使用實際衝突數據
            const conflictCode = prompt('請輸入要比較的代碼版本（用於衝突分析）:');
            
            if (!conflictCode) {
                alert('需要兩個代碼版本才能進行衝突分析');
                return;
            }

            const result = await callAI('conflict', { 
                original_code: currentCode,
                conflict_code: conflictCode
            });
            displayAIResult(result, '衝突分析');
        }

        // 5. 詢問問題功能
        function aiAskQuestion() {
            const questionInput = document.getElementById('ai-question-input');
            const questionText = document.getElementById('ai-question-text');
            
            questionInput.style.display = 'block';
            questionText.focus();
        }

        function hideAIQuestionInput() {
            const questionInput = document.getElementById('ai-question-input');
            const questionText = document.getElementById('ai-question-text');
            
            questionInput.style.display = 'none';
            questionText.value = '';
        }

        async function submitAIQuestion() {
            const questionText = document.getElementById('ai-question-text');
            const question = questionText.value.trim();
            
            if (!question) {
                alert('請輸入問題');
                return;
            }

            const currentCode = document.getElementById('code-editor').value.trim();
            const context = currentCode ? `當前代碼：\n${currentCode}` : '沒有當前代碼';

            const result = await callAI('question', { 
                question: question,
                context: context,
                category: 'python_programming'
            });
            
            displayAIResult(result, `問題回答: "${question}"`);
            hideAIQuestionInput();
        }

        // 回車鍵提交問題
        document.addEventListener('DOMContentLoaded', function() {
            const questionText = document.getElementById('ai-question-text');
            if (questionText) {
                questionText.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && e.ctrlKey) {
                        submitAIQuestion();
                    }
                });
            }
        });

        // 衝突情況下的AI分析（與衝突解決器整合）
        window.requestConflictAIAnalysis = async function(conflictData) {
            if (!conflictData) {
                console.warn('沒有衝突數據可供分析');
                return;
            }

            const result = await callAI('conflict', {
                original_code: conflictData.yourCode || '',
                conflict_code: conflictData.otherCode || '',
                conflict_type: conflictData.conflict_type || 'unknown'
            });

            // 如果衝突模態框是開啟的，直接顯示在那裡
            const conflictModal = document.getElementById('conflictModal');
            const aiAnalysisContent = document.getElementById('aiAnalysisContent');
            
            if (conflictModal && aiAnalysisContent && conflictModal.style.display !== 'none') {
                if (result.success) {
                    aiAnalysisContent.innerHTML = `
                        <div style="white-space: pre-wrap; line-height: 1.6;">
                            ${result.data.analysis || result.data.content || result.data}
                        </div>
                    `;
                } else {
                    aiAnalysisContent.innerHTML = `
                        <div style="color: #dc3545;">
                            AI分析失敗：${result.message}
                        </div>
                    `;
                }
                
                // 顯示AI分析區域
                const aiAnalysisCard = document.getElementById('conflictAIAnalysis');
                if (aiAnalysisCard) {
                    aiAnalysisCard.style.display = 'block';
                }
            } else {
                // 否則顯示在AI面板
                displayAIResult(result, '衝突分析');
            }

            return result;
        };
    </script>
</body>
</html> 