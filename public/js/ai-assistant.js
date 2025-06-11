// AI助教模組
class AIAssistantManager {
    constructor() {
        this.currentResponse = '';
        this.responseContainer = null;
        this.shareOptions = null;
        this.isFirstPrompt = true; // 用於判斷是否是初始提示狀態
        this.isProcessing = false; // 防止重複請求
        this.currentAction = null; // 用於儲存當前動作
        this.editorReady = false; // 編輯器就緒標誌
        this.currentUser = null; // 用於儲存當前用戶
    }

    // 初始化AI助教
    initialize() {
        console.log('🤖 [AI] 開始初始化AI助教...');
        
        // 重新獲取DOM元素
        this.responseContainer = document.getElementById('aiResponse');
        this.shareOptions = document.getElementById('aiShareOptions');
        
        if (!this.responseContainer) {
            console.error("❌ AI Response container 'aiResponse' not found!");
            console.log("🔍 嘗試在1秒後重新查找...");
            setTimeout(() => {
                this.responseContainer = document.getElementById('aiResponse');
                if (this.responseContainer) {
                    console.log("✅ 延遲找到 aiResponse 容器");
                    this.clearResponse();
                }
            }, 1000);
        } else {
            console.log("✅ 找到 aiResponse 容器");
        }
        
        if (!this.shareOptions) {
            console.error("❌ AI Share options 'aiShareOptions' not found!");
            setTimeout(() => {
                this.shareOptions = document.getElementById('aiShareOptions');
                if (this.shareOptions) {
                    console.log("✅ 延遲找到 aiShareOptions 容器");
                }
            }, 1000);
        } else {
            console.log("✅ 找到 aiShareOptions 容器");
        }
        
        // 🎯 與用戶管理器集成
        if (window.UserManager) {
            this.currentUser = window.UserManager.getCurrentUserName();
            console.log('🤖 [AI] 與用戶管理器集成，當前用戶:', this.currentUser);
            
            // 監聽用戶變更
            window.UserManager.onUserChange((newUser, oldUser) => {
                this.currentUser = newUser ? newUser.name : null;
                console.log('🤖 [AI] 用戶變更:', oldUser?.name, '->', newUser?.name);
            });
        }
        
        // 等待編輯器就緒
        this.waitForEditorReady();
        
        this.clearResponse(); // 初始化時清空回應並隱藏分享
        console.log('✅ AI助教模組初始化完成 (V6 - 用戶管理器集成版本)');
    }

    // 等待編輯器就緒
    waitForEditorReady() {
        const checkEditor = () => {
            const editorReady = window.Editor && 
                               typeof window.Editor.getCode === 'function' &&
                               window.Editor.editor && 
                               window.Editor.editor.getValue;
            
            if (editorReady) {
                console.log('✅ [AI] 編輯器已就緒');
                this.editorReady = true;
                return;
            }
            
            console.log('⏳ [AI] 等待編輯器就緒...', {
                'window.Editor': !!window.Editor,
                'window.Editor.getCode': !!(window.Editor && window.Editor.getCode),
                'window.Editor.editor': !!(window.Editor && window.Editor.editor),
                'CodeMirror可用': typeof CodeMirror !== 'undefined'
            });
            
            setTimeout(checkEditor, 500);
        };
        
        checkEditor();
    }

    // 清空AI回應並隱藏分享選項
    clearResponse() {
        if (this.responseContainer) {
            // 初始化時顯示空白狀態
            this.responseContainer.innerHTML = `
                <div class="text-center text-muted p-4">
                    <div style="min-height: 50px; display: flex; align-items: center; justify-content: center;">
                        <span style="opacity: 0.5;">選擇上方功能開始使用 AI 助教</span>
                    </div>
                </div>
            `;
        }
        this.currentResponse = '';
        this.hideShareOptions();
        this.isFirstPrompt = true; // 重置標誌
        this.isProcessing = false; // 重置處理狀態
    }

    // 請求AI分析 - 修改為調用真實API
    requestAnalysis(action) {
        // 檢查編輯器是否就緒
        if (!this.editorReady && !(window.Editor && typeof window.Editor.getCode === 'function')) {
            console.warn('⚠️ [AI] 編輯器尚未就緒，嘗試等待...');
            if (this.responseContainer) {
                this.responseContainer.innerHTML = `
                    <div class="alert alert-warning p-3 text-center">
                        <i class="fas fa-hourglass-half"></i>
                        <strong>編輯器載入中...</strong>
                        <p class="mb-0">請稍候，編輯器正在初始化中...</p>
                    </div>
                `;
            }
            
            // 嘗試等待2秒後重試
            setTimeout(() => {
                if (window.Editor && typeof window.Editor.getCode === 'function') {
                    console.log('✅ [AI] 編輯器已就緒，重新執行分析');
                    this.editorReady = true;
                    this.requestAnalysis(action);
                } else {
                    console.error('❌ [AI] 編輯器超時仍未就緒');
                    if (this.responseContainer) {
                        this.responseContainer.innerHTML = `
                            <div class="alert alert-danger p-3 text-center">
                                <i class="fas fa-exclamation-circle"></i>
                                <strong>編輯器載入失敗</strong>
                                <p class="mb-2">無法連接到代碼編輯器，請重新載入頁面。</p>
                                <button class="btn btn-sm btn-warning" onclick="location.reload()">🔄 重新載入</button>
                            </div>
                        `;
                    }
                }
            }, 2000);
            return;
        }
        
        if (!wsManager.isConnected()) {
             if (this.responseContainer) {
                this.responseContainer.innerHTML = '<p class="text-danger p-3 text-center">⚠️ 請先加入房間以使用AI助教功能。</p>';
             }
             this.hideShareOptions();
             return;
        }

        if (this.isProcessing) {
            console.log('⏳ AI請求正在處理中，請稍候...');
            return;
        }
        
        this.isFirstPrompt = false; // 用戶已進行操作
        this.isProcessing = true; // 設置處理中狀態

        // 🔧 使用統一的代碼獲取方法
        console.log('🔍 [AI] 開始獲取編輯器代碼...');
        const code = this.getEditorCode();
        
        if (!code || code.trim() === '') {
            // 詳細診斷信息
            const diagnostics = {
                'window.Editor': !!window.Editor,
                'window.Editor.getCode': !!(window.Editor && window.Editor.getCode),
                'window.editor': !!window.editor,
                'window.editor.getValue': !!(window.editor && window.editor.getValue),
                'CodeMirror元素': !!document.querySelector('.CodeMirror'),
                '#codeEditor元素': !!document.querySelector('#codeEditor'),
                'textarea元素': document.querySelectorAll('textarea').length,
                'wsManager': !!window.wsManager,
                'wsManager.currentRoom': window.wsManager ? window.wsManager.currentRoom : null,
                'localStorage keys': Object.keys(localStorage).filter(k => k.includes('code')).join(', ') || '無相關鍵值'
            };
            
            let diagHtml = '';
            for (const [key, value] of Object.entries(diagnostics)) {
                const status = value ? '✅' : '❌';
                const color = value ? 'text-success' : 'text-danger';
                diagHtml += `<span class="${color}">${status} ${key}: ${value}</span><br>`;
            }
            
            this.showResponse(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>無法分析當前代碼</strong>
                    <p>編輯器中沒有找到程式碼可供分析。請確認：</p>
                    <ol>
                        <li>您已經在編輯器中輸入了Python程式碼</li>
                        <li>代碼編輯器已正確載入</li>
                        <li>您已加入了房間</li>
                    </ol>
                    <hr>
                    <details>
                        <summary><strong>🔧 技術診斷信息</strong> (點擊展開)</summary>
                        <div class="mt-2 small" style="font-family: monospace;">
                            ${diagHtml}
                        </div>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-info" onclick="console.log('🔍 手動診斷:', window.aiAssistant ? window.aiAssistant.getEditorCode() : '無AI助教物件')">
                                🔍 執行手動診斷
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="location.reload()">
                                🔄 重新載入頁面
                            </button>
                        </div>
                    </details>
                </div>
            `);
            this.isProcessing = false;
            return;
        }

        // 顯示處理中狀態
        this.showProcessing(action);

        // 生成唯一請求ID
        const requestId = `ai_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

        // 映射動作到API操作
        let apiAction = '';
        switch(action) {
            case 'check_syntax':
            case 'check_errors':
                apiAction = 'check_errors';
                break;
            case 'code_review':
            case 'analyze':
                apiAction = 'analyze';
                break;
            case 'improvement_tips':
            case 'suggest':
                apiAction = 'suggest';
                break;
            case 'conflict_analysis':
                apiAction = 'conflict_analysis';
                break;
            case 'collaboration_guide':
                // 協作指南使用本地回應，顯示操作教學
                this.showResponse(this.getCollaborationGuide());
                this.isProcessing = false;
                return;
            default:
                apiAction = 'explain_code'; // 默認為解釋程式
        }

        console.log(`🤖 發送AI請求: ${apiAction}, RequestID: ${requestId}`);

        // 獲取用戶信息，優先使用AutoLogin的用戶信息
        let userInfo = { id: 1, username: 'Alex Wang' };
        if (window.AutoLogin) {
            const autoLoginUser = window.AutoLogin.getCurrentUser();
            if (autoLoginUser) {
                userInfo = {
                    id: autoLoginUser.id,
                    username: autoLoginUser.username
                };
            }
        }

        // 發送HTTP輪詢請求
        console.log('📡 [AI] 通過HTTP發送AI請求');
        this.sendHTTPAIRequest({
            action: apiAction,
            code: code,
            requestId: requestId,
            user_id: userInfo.id,
            username: userInfo.username,
            room_id: wsManager.currentRoom || 'test-room'
        });

        // 設置超時處理
        setTimeout(() => {
            if (this.isProcessing) {
                this.showResponse(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>請求超時：</strong> AI服務回應超時，請檢查網路連接後重試。
                    </div>
                `);
                this.isProcessing = false;
            }
        }, 30000); // 30秒超時
    }

    // 顯示處理中狀態
    showProcessing(action) {
        const actionNames = {
            'check_syntax': '語法檢查',
            'check_errors': '錯誤檢查', 
            'analyze': '程式碼分析',
            'code_review': '程式碼審查',
            'suggest': '改進建議',
            'improvement_tips': '優化建議'
        };

        const actionName = actionNames[action] || 'AI分析';

        if (this.responseContainer) {
            this.responseContainer.innerHTML = `
                <div class="ai-response-card" style="background-color: #fff; border-radius: 5px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div class="ai-response-header d-flex align-items-center mb-3" style="border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <i class="fas fa-robot text-primary me-2" style="font-size: 1.2em;"></i>
                        <span class="fw-bold" style="font-size: 1.1em;">AI助教正在分析...</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm text-primary me-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="text-muted">正在進行${actionName}，請稍候...</span>
                    </div>
                </div>
            `;
        }
    }

    // 處理HTTP輪詢 AI回應
    handleWebSocketAIResponse(message) {
        console.log('🤖 [AI Assistant] 處理HTTP輪詢 AI回應:', message);
        console.log('🔍 [AI Assistant] 回應容器狀態:', !!this.responseContainer);
        console.log('🔍 [AI Assistant] 當前處理狀態:', this.isProcessing);
        
        this.isProcessing = false;
        
        // 🆕 檢查是否為代碼執行請求
        if (message.action === 'run_code' || this.currentAction === 'run_code') {
            console.log('🏃 [AI Code Runner] 處理代碼執行回應');
            this.handleCodeExecutionResponse(message);
            return;
        }
        
        if (message.success && message.response) {
            console.log('✅ [AI Assistant] AI回應成功，準備顯示');
            console.log('📝 [AI Assistant] 回應內容:', message.response);
            
            // 格式化回應
            const formattedResponse = `
                <h6><i class="fas fa-brain"></i> AI助教分析結果</h6>
                <div class="mb-3">
                    ${this.formatAIResponse(message.response)}
                </div>
            `;
            
            console.log('🎨 [AI Assistant] 格式化後的回應:', formattedResponse);
            this.showResponse(formattedResponse);
            console.log('✅ [AI Assistant] showResponse 調用完成');
        } else {
            console.log('❌ [AI Assistant] AI回應失敗:', message.error);
            const errorResponse = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>AI助教暫時無法回應：</strong> ${message.error || 'AI服務暫時不可用，請稍後再試。'}
                </div>
            `;
            console.log('🎨 [AI Assistant] 錯誤回應:', errorResponse);
            this.showResponse(errorResponse);
        }
    }

    // 🆕 處理代碼執行回應
    handleCodeExecutionResponse(message) {
        console.log('🏃 [AI Code Runner] 處理代碼執行回應:', message);
        
        if (message.success && message.response) {
            // 解析AI回應來提取執行結果
            const response = message.response;
            
            // 判斷執行是否成功（基於AI回應內容）
            const isSuccess = response.includes('執行狀態：成功') || 
                            response.includes('執行成功') ||
                            (!response.includes('錯誤') && !response.includes('失敗'));
            
            // 提取輸出結果（在```和```之間的內容）
            const outputMatch = response.match(/```\s*\n([\s\S]*?)\n```/);
            const output = outputMatch ? outputMatch[1].trim() : '';
            
            // 構造執行結果
            const executionResult = {
                success: isSuccess,
                output: output || (isSuccess ? '程式執行完成' : ''),
                error: isSuccess ? null : '代碼執行遇到問題，請查看AI分析',
                error_type: isSuccess ? null : 'ai_analysis',
                execution_time: Math.floor(Math.random() * 500 + 100), // 模擬執行時間
                analysis: response,
                timestamp: new Date().toISOString()
            };
            
            console.log('🔄 [AI Code Runner] 構造的執行結果:', executionResult);
            
            // 調用執行結果處理
            this.handleCodeExecutionResult(executionResult);
            
        } else {
            // AI回應失敗，構造錯誤結果
            const errorResult = {
                success: false,
                error: message.error || 'AI無法分析代碼',
                error_type: 'ai_error',
                execution_time: 0,
                timestamp: new Date().toISOString()
            };
            
            console.log('❌ [AI Code Runner] AI回應失敗，構造錯誤結果:', errorResult);
            this.handleCodeExecutionResult(errorResult);
        }
        
        // 重置動作狀態
        this.currentAction = null;
    }

    // 處理AI回應 (向後兼容)
    handleAIResponse(response) {
        this.isProcessing = false; // 重置處理狀態

        // 如果response是字符串，直接顯示
        if (typeof response === 'string') {
            const formattedResponse = `
                <h6><i class="fas fa-brain"></i> AI助教分析結果</h6>
                <div class="mb-3">
                    ${this.formatAIResponse(response)}
                </div>
            `;
            this.showResponse(formattedResponse);
            return;
        }

        // 處理複雜對象回應（保持向後兼容）
        if (!response.success) {
            this.showResponse(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>AI服務錯誤：</strong> ${response.error || 'AI服務暫時無法使用，請稍後重試。'}
                </div>
            `);
            return;
        }

        if (response.data && response.data.suggestions && response.data.suggestions.length > 0) {
            const suggestion = response.data.suggestions[0];
            const score = response.data.score;
            
            let formattedResponse = `
                <h6><i class="fas fa-brain"></i> AI助教分析結果</h6>
                <div class="mb-3">
            `;

            // 如果有評分，顯示評分
            if (score && score !== 'N/A' && typeof score === 'number') {
                const scoreColor = score >= 80 ? 'success' : score >= 60 ? 'warning' : 'danger';
                formattedResponse += `
                    <div class="alert alert-${scoreColor} d-flex align-items-center mb-3">
                        <i class="fas fa-chart-line me-2"></i>
                        <strong>程式碼品質評分：${score}/100</strong>
                    </div>
                `;
            }

            // 格式化AI回應內容
            const formattedSuggestion = this.formatAIResponse(suggestion);
            formattedResponse += formattedSuggestion;
            formattedResponse += `</div>`;

            this.showResponse(formattedResponse);
        } else {
            this.showResponse(`
                <div class="alert alert-warning">
                    <i class="fas fa-question-circle"></i>
                    <strong>無分析結果：</strong> AI無法分析當前程式碼，請檢查程式碼是否有效。
                </div>
            `);
        }
    }

    // 處理AI錯誤
    handleAIError(error) {
        this.isProcessing = false; // 重置處理狀態
        
        this.showResponse(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <strong>AI服務錯誤：</strong> ${error || 'AI服務暫時無法使用，請稍後重試。'}
            </div>
        `);
    }

    // 格式化AI回應
    formatAIResponse(text) {
        // 將AI回應轉換為HTML格式
        let formatted = text;
        
        // 將數字列表轉換為HTML列表
        formatted = formatted.replace(/^(\d+\.\s.+)$/gm, '<li>$1</li>');
        formatted = formatted.replace(/(<li>.*<\/li>)/gs, '<ol>$1</ol>');
        
        // 將**粗體**轉換為HTML
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // 將程式碼塊標記轉換
        formatted = formatted.replace(/`([^`]+)`/g, '<code class="text-primary">$1</code>');
        
        // 將換行轉換為HTML
        formatted = formatted.replace(/\n/g, '<br>');
        
        // 處理建議分類
        if (formatted.includes('優點:') || formatted.includes('缺點:') || formatted.includes('建議:')) {
            formatted = formatted.replace(/(優點:|缺點:|建議:|改進建議:|語法錯誤:|注意事項:)/g, '<h6 class="mt-3 mb-2 text-primary"><i class="fas fa-chevron-right"></i> $1</h6>');
        }
        
        return `<div class="ai-content">${formatted}</div>`;
    }

    // 🆕 使用AI運行代碼
    runCodeWithAI(code) {
        console.log('🤖 [AI Code Runner] 開始AI代碼執行');
        console.log('📝 [AI Code Runner] 代碼內容:', code);
        
        if (!code || code.trim() === '') {
            this.handleCodeExecutionResult({
                success: false,
                error: '代碼為空，請輸入要執行的Python代碼',
                error_type: 'empty_code',
                execution_time: 0
            });
            return;
        }
        
        // 設置處理狀態
        this.isProcessing = true;
        this.currentAction = 'run_code';
        
        // 顯示運行中狀態
        this.showCodeExecutionProgress();
        
        // 準備AI請求
        const aiRequest = {
            action: 'run_code',
            code: code,
            requestId: `ai_run_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            user_id: 1,
            username: 'Student'
        };
        
        // 🔧 修復：始終使用HTTP直接發送到AI API，避免WebSocket路由問題
        console.log('📡 [AI Code Runner] 直接通過HTTP發送AI代碼執行請求');
            this.sendHTTPAIRequest(aiRequest);
    }
    
    // 🆕 通過HTTP發送AI請求
    async sendHTTPAIRequest(aiRequest) {
        try {
            console.log('📡 [HTTP AI] 發送HTTP AI請求:', aiRequest);
            
            // 🔧 修復：確保代碼存在
            if (!aiRequest.code) {
                console.error('❌ [HTTP AI] 代碼為空，嘗試重新獲取');
                const editorCode = this.getEditorCode();
                if (editorCode && editorCode.trim()) {
                    aiRequest.code = editorCode;
                    console.log('✅ [HTTP AI] 重新獲取代碼成功:', editorCode.substring(0, 50) + '...');
                } else {
                    throw new Error('無法獲取編輯器代碼內容');
                }
            }
            
            // 構建請求數據
            const requestData = {
                action: aiRequest.action,
                code: aiRequest.code,
                requestId: aiRequest.requestId || `http_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                user_id: aiRequest.user_id || 1,
                username: aiRequest.username || 'Anonymous'
            };
            
            if (aiRequest.prompt) {
                requestData.prompt = aiRequest.prompt;
            }
            
            console.log('📤 [HTTP AI] 請求數據:', requestData);
            console.log('📤 [HTTP AI] 代碼內容預覽:', requestData.code ? requestData.code.substring(0, 100) + '...' : '無代碼');
            
            // 🔧 修復：發送到正確的AI API端點
            const response = await fetch('/api/ai.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });
            
            console.log('📥 [HTTP AI] 回應狀態:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('📥 [HTTP AI] HTTP錯誤內容:', errorText);
                throw new Error(`HTTP錯誤: ${response.status} - ${errorText}`);
            }
            
            const result = await response.json();
            console.log('✅ [HTTP AI] 收到HTTP AI回應:', result);
            
            // 處理回應
            if (result.success) {
                console.log('🎉 [HTTP AI] AI請求成功，處理回應...');
                
                if (aiRequest.action === 'run_code') {
                    this.handleCodeExecutionResult({
                        success: true,
                        output: result.output || result.response,
                        analysis: result.analysis || result.response,
                        execution_time: result.execution_time || 0
                    });
                } else {
                    // 直接顯示AI回應
                    const formattedResponse = `
                        <h6><i class="fas fa-brain"></i> AI助教分析結果</h6>
                        <div class="mb-3">
                            <div class="ai-content">
                                ${this.formatAIResponse(result.response || result.output || '分析完成')}
                            </div>
                        </div>
                        <div class="alert alert-info mt-2">
                            <small><i class="fas fa-info-circle"></i> 模式: ${result.mode || 'API'}</small>
                        </div>
                    `;
                    this.showResponse(formattedResponse);
                }
                
                this.isProcessing = false;
                
            } else {
                console.error('❌ [HTTP AI] AI請求失敗:', result.error);
                this.handleAIError(result.error || '未知錯誤');
            }
            
        } catch (error) {
            console.error('📡 [HTTP AI] HTTP AI請求異常:', error);
            this.handleAIError(`網路請求失敗: ${error.message}`);
        }
    }
    
    // 🆕 顯示代碼執行進度
    showCodeExecutionProgress() {
        if (window.editorManager && typeof window.editorManager.showOutput === 'function') {
            window.editorManager.showOutput('🤖 AI正在分析和執行代碼...', 'info');
        }
        
        this.showResponse(`
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span>🤖 AI正在分析代碼並模擬執行結果...</span>
            </div>
        `);
    }
    
    // 🆕 處理AI代碼執行結果
    handleCodeExecutionResult(result) {
        console.log('🔍 [AI Code Runner] 處理AI代碼執行結果:', result);
        
        this.isProcessing = false;
        
        // 🔧 優先確保結果顯示在編輯器下方的輸出區域
        this.showExecutionResultInEditor(result);
        
        // 同時在AI助教區域也顯示分析結果
        if (result.success) {
            this.showResponse(`
                <h6><i class="fas fa-play-circle text-success"></i> 代碼執行成功</h6>
                <div class="mb-3">
                    <div class="ai-content">
                        ${this.formatAIResponse(result.analysis || result.output || '代碼執行完成')}
                    </div>
                </div>
                ${result.execution_time ? `<small class="text-muted">執行時間: ${result.execution_time}ms</small>` : ''}
            `);
        } else {
            this.showResponse(`
                <h6><i class="fas fa-exclamation-triangle text-warning"></i> 代碼執行分析</h6>
                <div class="mb-3">
                    <div class="ai-content text-danger">
                        ${result.error || result.analysis || '代碼執行遇到問題'}
                    </div>
                </div>
            `);
        }
    }

    // 🆕 在編輯器下方顯示執行結果
    showExecutionResultInEditor(result) {
        console.log('📺 [AI Code Runner] 在編輯器區域顯示執行結果');
        
        // 嘗試多種方式找到並更新輸出區域
        const outputMethods = [
            () => {
                // 方法1：使用 window.Editor
                if (window.Editor && typeof window.Editor.handleExecutionResult === 'function') {
                    console.log('📤 方法1：調用window.Editor.handleExecutionResult');
                    window.Editor.handleExecutionResult(result);
                    return true;
                }
                return false;
            },
            () => {
                // 方法2：使用 window.editorManager
                if (window.editorManager && typeof window.editorManager.handleExecutionResult === 'function') {
                    console.log('📤 方法2：調用window.editorManager.handleExecutionResult');
                    window.editorManager.handleExecutionResult(result);
                    return true;
                }
                return false;
            },
            () => {
                // 方法3：使用 window.editorManager.showOutput
                if (window.editorManager && typeof window.editorManager.showOutput === 'function') {
                    console.log('📤 方法3：調用window.editorManager.showOutput');
                    if (result.success) {
                        window.editorManager.showOutput(result.output || result.analysis || '執行成功', 'success');
                    } else {
                        window.editorManager.showOutput(result.error || '執行失敗', 'error');
                    }
                    return true;
                }
                return false;
            },
            () => {
                // 方法4：直接操作DOM元素
                console.log('📤 方法4：直接操作DOM輸出區域');
                return this.showExecutionResultDirectDOM(result);
            }
        ];
        
        // 嘗試每種方法，直到成功
        for (let i = 0; i < outputMethods.length; i++) {
            try {
                if (outputMethods[i]()) {
                    console.log(`✅ 成功使用方法${i + 1}顯示執行結果`);
                    return;
                }
            } catch (error) {
                console.warn(`⚠️ 方法${i + 1}失敗:`, error);
            }
        }
        
        console.error('❌ 所有顯示方法都失敗了');
    }

    // 🆕 直接操作DOM顯示執行結果
    showExecutionResultDirectDOM(result) {
        // 查找輸出容器
        const outputContainers = [
            document.getElementById('codeOutput'),
            document.getElementById('outputContent'),
            document.querySelector('.code-output'),
            document.querySelector('#output'),
            document.querySelector('.output-area'),
            document.querySelector('.execution-result')
        ];
        
        let outputContainer = null;
        for (const container of outputContainers) {
            if (container) {
                outputContainer = container;
                break;
            }
        }
        
        if (!outputContainer) {
            console.warn('❌ 未找到輸出容器，嘗試創建');
            // 嘗試在編輯器後面創建輸出區域
            const editorContainer = document.querySelector('.editor-container') || 
                                  document.querySelector('#codeEditor') || 
                                  document.querySelector('.CodeMirror');
            
            if (editorContainer) {
                outputContainer = document.createElement('div');
                outputContainer.id = 'codeOutput';
                outputContainer.className = 'output-area mt-3 p-3 border rounded bg-light';
                outputContainer.style.display = 'block';
                editorContainer.parentNode.insertBefore(outputContainer, editorContainer.nextSibling);
                console.log('✅ 成功創建輸出容器');
            } else {
                console.error('❌ 無法找到編輯器容器');
                return false;
            }
        }
        
        // 顯示輸出容器
        outputContainer.style.display = 'block';
        
        // 格式化並顯示結果
        if (result.success) {
            outputContainer.innerHTML = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-check-circle"></i> 🤖 AI執行結果</h6>
                    <div class="execution-output">
                        ${this.formatAIResponse(result.output || result.analysis || '程式執行完成')}
                    </div>
                    ${result.execution_time ? `<small class="text-muted d-block mt-2">執行時間: ${result.execution_time}ms</small>` : ''}
                </div>
            `;
        } else {
            outputContainer.innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> 🤖 AI執行分析</h6>
                    <div class="execution-output">
                        ${this.escapeHtml(result.error || '代碼執行失敗')}
                    </div>
                </div>
            `;
        }
        
        // 滾動到輸出區域
        outputContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        console.log('✅ 直接DOM操作顯示執行結果成功');
        return true;
    }

    // 🆕 備用方案：直接顯示代碼執行結果
    showCodeExecutionResultFallback(result) {
        console.log('🔄 [AI Code Runner] 使用備用方案顯示執行結果');
        
        // 查找輸出容器
        const outputContainer = document.getElementById('codeOutput') || document.getElementById('outputContent');
        if (!outputContainer) {
            console.warn('❌ [AI Code Runner] 未找到輸出容器');
            return;
        }
        
        // 顯示輸出容器
        if (outputContainer.id === 'codeOutput') {
            outputContainer.style.display = 'block';
        }
        
        // 查找輸出內容區域
        const contentArea = document.getElementById('outputContent') || outputContainer;
        
        if (result.success) {
            contentArea.innerHTML = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-check-circle"></i> 執行成功</h6>
                    <pre class="mb-0">${this.escapeHtml(result.output || '程式執行完成')}</pre>
                    ${result.execution_time ? `<small class="text-muted">執行時間: ${result.execution_time}ms</small>` : ''}
                </div>
            `;
        } else {
            contentArea.innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> 執行錯誤</h6>
                    <pre class="mb-0">${this.escapeHtml(result.error || '代碼執行失敗')}</pre>
                </div>
            `;
        }
    }

    // 新增：顯示錯誤檢查建議 (模擬) - 保留為備用
    showErrorCheckSuggestions() {
        // 這個方法保留為備用，主要使用API回應
        this.requestAnalysis('check_errors');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 顯示AI回應
    showResponse(content) {
        // 如果容器不存在，嘗試重新獲取
        if (!this.responseContainer) {
            this.responseContainer = document.getElementById('aiResponse');
        }
        
        if (!this.responseContainer) {
            console.error('❌ AI回應容器不存在，無法顯示回應');
            console.log('🔍 嘗試使用降級方式顯示...');
            
            // 降級處理：嘗試找到任何可能的容器
            const fallbackContainer = document.querySelector('#aiResponse') || 
                                    document.querySelector('.ai-response') ||
                                    document.querySelector('[data-ai-response]');
            
            if (fallbackContainer) {
                console.log('✅ 找到降級容器，顯示AI回應');
                fallbackContainer.innerHTML = `
                    <div class="alert alert-info">
                        <h6><i class="fas fa-robot"></i> AI助教回應</h6>
                        <div>${content}</div>
                    </div>
                `;
                return;
            } else {
                console.error('❌ 完全找不到AI回應容器');
                return;
            }
        }
        
        console.log('✅ 顯示AI回應到容器');
        this.currentResponse = content;
        this.responseContainer.innerHTML = `
            <div class="ai-response-card" style="background-color: #fff; border-radius: 5px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div class="ai-response-header d-flex align-items-center mb-2" style="border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    <i class="fas fa-robot text-primary me-2" style="font-size: 1.2em;"></i>
                    <span class="fw-bold" style="font-size: 1.1em;">AI助教建議</span>
                </div>
                <div class="ai-response-content" style="font-size: 0.95em; line-height: 1.6;">
                    ${content}
                </div>
            </div>
        `;
        
        if (this.currentResponse.trim() !== '' && !this.isFirstPrompt) {
            if (this.shareOptions) {
                this.shareOptions.style.display = 'block';
            }
        } else {
            this.hideShareOptions();
        }
    }

    // 獲取協作指導
    getCollaborationGuide() {
        return `
            <h6><i class="fas fa-graduation-cap"></i> 🐍 Python協作學習完整指南</h6>

            <div class="accordion" id="tutorialAccordion">
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basicOperations">
                            🚀 基本操作指南
                        </button>
                    </h2>
                    <div id="basicOperations" class="accordion-collapse collapse show">
                        <div class="accordion-body">
                            <h7><strong>📝 編輯器使用：</strong></h7>
                            <ul class="mt-2">
                                <li><strong>編寫代碼：</strong>直接在編輯器中輸入 Python 代碼</li>
                                <li><strong>💾 保存：</strong>點擊「保存」按鈕，創建新版本</li>
                                <li><strong>▶️ 運行：</strong>點擊「運行」執行代碼並查看結果</li>
                                <li><strong>📥 載入：</strong>從下拉選單載入最新版本或歷史版本</li>
                            </ul>
                            
                            <h7><strong>🔢 版本管理：</strong></h7>
                            <ul class="mt-2">
                                <li>平台最多保存 <strong>5個歷史版本</strong></li>
                                <li>版本號顯示在編輯器右上角</li>
                                <li>可以隨時恢復到之前的版本</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#aiFeatures">
                            🤖 AI助教功能詳解
                        </button>
                    </h2>
                    <div id="aiFeatures" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <h7><strong>四大核心功能：</strong></h7>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <h8><strong>🔍 代碼審查</strong></h8>
                                            <p class="small mb-0">分析代碼結構和邏輯，提供風格建議</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <h8><strong>🐛 檢查錯誤</strong></h8>
                                            <p class="small mb-0">檢測語法和邏輯錯誤，提供修正方案</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <h8><strong>💡 解釋程式</strong></h8>
                                            <p class="small text-muted mb-2">分析代碼功能和邏輯結構</p>
                                            <button class="btn btn-outline-primary btn-sm w-100" onclick="askAI('analyze')">
                                                開始解釋
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <h8><strong>📚 操作教學</strong></h8>
                                            <p class="small mb-0">顯示平台完整使用指南</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <strong>🔄 分享功能：</strong>AI 分析完成後，可點擊「分享」將建議發送到聊天室與其他同學討論
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#conflictResolution">
                            ⚠️ 衝突檢測與解決
                        </button>
                    </h2>
                    <div id="conflictResolution" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <h7><strong>什麼是衝突？</strong></h7>
                            <p>當多個同學同時修改代碼時，會出現版本不一致的情況。</p>
                            
                            <h7><strong>四種解決方案：</strong></h7>
                            <div class="row mt-2">
                                <div class="col-6 mb-2">
                                    <div class="card border-primary">
                                        <div class="card-body p-2">
                                            <h8><strong>🔄 載入最新版</strong></h8>
                                            <p class="small mb-0">放棄修改，使用服務器最新版本</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div class="card border-warning">
                                        <div class="card-body p-2">
                                            <h8><strong>⚡ 強制更新我的</strong></h8>
                                            <p class="small mb-0">用你的版本覆蓋服務器版本</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div class="card border-info">
                                        <div class="card-body p-2">
                                            <h8><strong>💬 複製到聊天室</strong></h8>
                                            <p class="small mb-0">分享衝突代碼，團隊討論解決</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div class="card border-success">
                                        <div class="card-body p-2">
                                            <h8><strong>🤖 AI協助分析</strong></h8>
                                            <p class="small mb-0">讓AI分析差異並提供合併建議</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#chatFeatures">
                            💬 聊天室與協作
                        </button>
                    </h2>
                    <div id="chatFeatures" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <h7><strong>聊天室功能：</strong></h7>
                            <ul class="mt-2">
                                <li><strong>即時通訊：</strong>與房間內其他同學即時聊天</li>
                                <li><strong>AI分享：</strong>將AI助教建議分享到聊天室</li>
                                <li><strong>代碼討論：</strong>討論程式設計問題和解決方案</li>
                                <li><strong>歷史記錄：</strong>聊天記錄會保存在房間中</li>
                            </ul>
                            
                            <h7><strong>👨‍🏫 教師互動：</strong></h7>
                            <ul class="mt-2">
                                <li><strong>即時監控：</strong>教師可以看到你的代碼編輯情況</li>
                                <li><strong>廣播消息：</strong>接收教師發送的重要通知</li>
                                <li><strong>即時指導：</strong>教師可以提供即時協助</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bestPractices">
                            🏆 協作最佳實踐
                        </button>
                    </h2>
                    <div id="bestPractices" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <h7><strong>📋 協作禮儀：</strong></h7>
                            <ul class="mt-2">
                                <li>修改代碼前，先在聊天室告知其他同學</li>
                                <li>使用註解標記自己負責的代碼區域</li>
                                <li>頻繁保存和同步最新版本</li>
                                <li>遇到問題先詢問AI助教</li>
                            </ul>
                            
                            <h7><strong>🎯 學習技巧：</strong></h7>
                <ul class="mt-2">
                                <li>觀察其他同學的編程思路</li>
                                <li>在聊天室中積極提問和回答</li>
                                <li>不要害怕出錯，錯誤是學習的機會</li>
                                <li>善用版本管理功能回顧學習過程</li>
                </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#troubleshooting">
                            🔧 常見問題解決
                        </button>
                    </h2>
                    <div id="troubleshooting" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <h7><strong>❌ AI助教不響應：</strong></h7>
                            <ol class="mt-2">
                                <li>確認網路連接穩定</li>
                                <li>重新整理頁面 (F5)</li>
                                <li>確認已在編輯器中輸入代碼</li>
                            </ol>
                            
                            <h7><strong>🔄 代碼同步問題：</strong></h7>
                            <ol class="mt-2">
                                <li>檢查右上角連線狀態</li>
                                <li>重新加入房間</li>
                                <li>使用「載入最新代碼」功能</li>
                            </ol>
                            
                            <h7><strong>💬 聊天室問題：</strong></h7>
                            <ol class="mt-2">
                                <li>確認已加入房間</li>
                                <li>檢查是否在聊天標籤頁</li>
                                <li>嘗試重新連接</li>
                            </ol>
                        </div>
                    </div>
                </div>

            </div>

            <div class="alert alert-success mt-3">
                <h7><strong>🌟 開始學習之旅</strong></h7>
                <p class="mb-2">歡迎來到 Python 協作學習環境！記住：</p>
                <ul class="mb-0">
                    <li><strong>🤝 合作勝過競爭</strong> - 互相幫助，共同成長</li>
                    <li><strong>💡 提問是勇氣</strong> - 不懂就問，沒有愚蠢的問題</li>
                    <li><strong>🔄 實踐出真知</strong> - 多寫代碼，多做實驗</li>
                </ul>
            </div>
        `;
    }

    // 顯示代碼審查建議
    showCodeReviewSuggestions() {
        const code = this.getEditorCode();
        const suggestions = this.analyzeCode(code);
        this.showResponse(suggestions);
    }

    // 顯示改進建議
    showImprovementTips() {
        const code = this.getEditorCode();
        const tips = this.generateImprovementTips(code);
        this.showResponse(tips);
    }
    
    // 🆕 統一的代碼獲取方法
    getEditorCode() {
        console.log('🔍 [AI] 開始代碼獲取診斷...');
        
        // 詳細環境檢查
        const envCheck = {
            'window.Editor': !!window.Editor,
            'window.Editor.getCode': !!(window.Editor && typeof window.Editor.getCode === 'function'),
            'window.editor': !!window.editor,
            'window.editor.getValue': !!(window.editor && typeof window.editor.getValue === 'function'),
            'DOM .CodeMirror': !!document.querySelector('.CodeMirror'),
            'DOM #codeEditor': !!document.querySelector('#codeEditor'),
            'wsManager.currentRoom': window.wsManager ? window.wsManager.currentRoom : null
        };
        
        console.log('🔧 [AI] 環境檢查結果:', envCheck);
        
        let code = '';
        let method = '';
        
        // 方案1: 使用 window.Editor.getCode()
        if (window.Editor && typeof window.Editor.getCode === 'function') {
            try {
                code = window.Editor.getCode();
                method = 'window.Editor.getCode()';
                console.log('✅ [AI] 方案1成功:', method, '- 獲取', code.length, '字符');
                console.log('📝 [AI] 代碼預覽:', code.substring(0, 50) + (code.length > 50 ? '...' : ''));
                if (code && code.trim().length > 0) {
                    return code;
                }
            } catch (error) {
                console.error('❌ [AI] 方案1失敗 - window.Editor.getCode():', error);
            }
        } else {
            console.warn('⚠️ [AI] 方案1不可用 - window.Editor.getCode 不存在');
        }
        
        // 方案2: 嘗試直接從CodeMirror獲取
        if (window.editor && typeof window.editor.getValue === 'function') {
            try {
                code = window.editor.getValue();
                method = 'window.editor.getValue()';
                console.log('✅ [AI] 方案2成功:', method, '- 獲取', code.length, '字符');
                console.log('📝 [AI] 代碼預覽:', code.substring(0, 50) + (code.length > 50 ? '...' : ''));
                if (code && code.trim().length > 0) {
                    return code;
                }
            } catch (error) {
                console.error('❌ [AI] 方案2失敗 - window.editor.getValue():', error);
            }
        } else {
            console.warn('⚠️ [AI] 方案2不可用 - window.editor.getValue 不存在');
        }
        
        // 方案3: 嘗試從DOM元素獲取
        const codeMirrorElement = document.querySelector('.CodeMirror');
        const codeEditorElement = document.querySelector('#codeEditor');
        const textareaElement = document.querySelector('textarea[name="code"]');
        
        console.log('🔍 [AI] DOM元素檢查:', {
            '.CodeMirror': !!codeMirrorElement,
            '#codeEditor': !!codeEditorElement,
            'textarea[name="code"]': !!textareaElement
        });
        
        if (codeMirrorElement) {
            try {
                if (codeMirrorElement.CodeMirror && typeof codeMirrorElement.CodeMirror.getValue === 'function') {
                    code = codeMirrorElement.CodeMirror.getValue();
                    method = 'DOM CodeMirror.getValue()';
                    console.log('✅ [AI] 方案3a成功:', method, '- 獲取', code.length, '字符');
                    console.log('📝 [AI] 代碼預覽:', code.substring(0, 50) + (code.length > 50 ? '...' : ''));
                    if (code && code.trim().length > 0) {
                        return code;
                    }
                } else {
                    console.warn('⚠️ [AI] CodeMirror對象未正確初始化');
                }
            } catch (error) {
                console.error('❌ [AI] 方案3a失敗 - DOM CodeMirror:', error);
            }
        }
        
        if (codeEditorElement && codeEditorElement.value !== undefined) {
            try {
                code = codeEditorElement.value;
                method = 'DOM #codeEditor.value';
                console.log('✅ [AI] 方案3b成功:', method, '- 獲取', code.length, '字符');
                console.log('📝 [AI] 代碼預覽:', code.substring(0, 50) + (code.length > 50 ? '...' : ''));
                if (code && code.trim().length > 0) {
                    return code;
                }
            } catch (error) {
                console.error('❌ [AI] 方案3b失敗 - DOM #codeEditor:', error);
            }
        }
        
        if (textareaElement && textareaElement.value !== undefined) {
            try {
                code = textareaElement.value;
                method = 'DOM textarea.value';
                console.log('✅ [AI] 方案3c成功:', method, '- 獲取', code.length, '字符');
                console.log('📝 [AI] 代碼預覽:', code.substring(0, 50) + (code.length > 50 ? '...' : ''));
                if (code && code.trim().length > 0) {
                    return code;
                }
            } catch (error) {
                console.error('❌ [AI] 方案3c失敗 - DOM textarea:', error);
            }
        }
        
        // 方案4: 從 localStorage 獲取最新保存的代碼
        try {
            const roomId = (window.wsManager && window.wsManager.currentRoom) || 'general-room';
            console.log('🔍 [AI] 檢查localStorage，房間ID:', roomId);
            
            const possibleKeys = [
                `python_code_${roomId}`,
                'python_code',
                'lastSavedCode',
                `code_${roomId}`,
                'editor_content'
            ];
            
            for (const key of possibleKeys) {
                const savedCode = localStorage.getItem(key);
                if (savedCode && savedCode.trim().length > 0) {
                    method = `localStorage.${key}`;
                    console.log('✅ [AI] 方案4成功:', method, '- 獲取', savedCode.length, '字符');
                    console.log('📝 [AI] 代碼預覽:', savedCode.substring(0, 50) + (savedCode.length > 50 ? '...' : ''));
                    return savedCode;
                }
            }
            
            console.warn('⚠️ [AI] 方案4 - localStorage中無相關代碼');
        } catch (error) {
            console.error('❌ [AI] 方案4失敗 - localStorage:', error);
        }
        
        // 最終診斷
        console.error('❌ [AI] 所有代碼獲取方案都失敗了！');
        console.log('🔧 [AI] 最終環境診斷:', {
            'window對象': typeof window,
            'document對象': typeof document,
            'localStorage對象': typeof localStorage,
            '所有textarea元素': document.querySelectorAll('textarea').length,
            '所有input元素': document.querySelectorAll('input').length,
            '頁面title': document.title,
            '當前URL': window.location.href
        });
        
        return '';
    }

    // 分析代碼
    analyzeCode(code) {
        let suggestions = `
            <h6><i class="fas fa-search"></i> 代碼審查建議</h6>
            <div class="mb-3">
        `;

        // 基本代碼檢查
        if (code.length < 10) {
            suggestions += `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    代碼內容較少，建議添加更多功能實現
                </div>
            `;
        }

        // 檢查變數命名
        if (code.includes('a =') || code.includes('b =') || code.includes('x =')) {
            suggestions += `
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb"></i>
                    <strong>變數命名建議：</strong> 使用有意義的變數名稱，如 'student_name' 而不是 'a'
                </div>
            `;
        }

        // 檢查註解
        if (!code.includes('#')) {
            suggestions += `
                <div class="alert alert-info">
                    <i class="fas fa-comment"></i>
                    <strong>註解建議：</strong> 為重要的代碼段添加註解說明
                </div>
            `;
        }

        // 檢查print語句
        if (!code.includes('print')) {
            suggestions += `
                <div class="alert alert-success">
                    <i class="fas fa-terminal"></i>
                    <strong>調試建議：</strong> 使用 print() 來顯示結果和調試程序
                </div>
            `;
        }

        suggestions += '</div>';
        return suggestions;
    }

    // 生成改進建議
    generateImprovementTips(code) {
        let tips = `
            <h6><i class="fas fa-lightbulb"></i> 代碼改進建議</h6>
            <div class="mb-3">
        `;

        // 通用改進建議
        tips += `
            <div class="card mb-2">
                <div class="card-body p-3">
                    <h7><strong>🔧 代碼結構優化：</strong></h7>
                    <ul class="mt-2 mb-0">
                        <li>將重複的代碼提取為函數</li>
                        <li>使用適當的數據結構（列表、字典、集合）</li>
                        <li>保持函數簡短且功能單一</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-2">
                <div class="card-body p-3">
                    <h7><strong>📚 Python最佳實踐：</strong></h7>
                    <ul class="mt-2 mb-0">
                        <li>使用list comprehension提高效率</li>
                        <li>妥善處理異常情況（try-except）</li>
                        <li>使用f-string進行字符串格式化</li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body p-3">
                    <h7><strong>🎯 學習建議：</strong></h7>
                    <ul class="mt-2 mb-0">
                        <li>多練習不同類型的問題</li>
                        <li>學習使用Python內建函數</li>
                        <li>理解算法時間複雜度</li>
                    </ul>
                </div>
            </div>
        `;

        tips += '</div>';
        return tips;
    }

    // 分析衝突並提供建議
    analyzeConflict(conflictData) {
        return `
            <div class="ai-conflict-analysis">
                <h6><i class="fas fa-robot"></i> AI衝突分析</h6>
                <div class="alert alert-info">
                    <strong>🔍 衝突原因分析：</strong>
                    <p>檢測到多位同學同時修改代碼，建議採用以下解決方案：</p>
                    <ol>
                        <li><strong>溝通協調：</strong> 在聊天室討論各自的修改方向</li>
                        <li><strong>功能分工：</strong> 將不同功能分配給不同同學</li>
                        <li><strong>版本合併：</strong> 手動合併最佳的修改部分</li>
                    </ol>
                </div>
                <div class="alert alert-success">
                    <strong>💡 推薦解決步驟：</strong>
                    <p>1. 點擊「複製到聊天討論區」將衝突代碼分享</p>
                    <p>2. 團隊討論選擇最佳方案</p>
                    <p>3. 由一位同學負責最終合併</p>
                </div>
            </div>
        `;
    }

    // 分享AI回應到聊天室
    shareResponse() {
        console.log('🔄 嘗試分享AI回應到聊天室...');
        console.log('📊 當前回應:', this.currentResponse ? '有內容' : '無內容');
        console.log('📊 Chat對象檢查:', {
            'window.Chat': !!window.Chat,
            'Chat': typeof Chat !== 'undefined' ? !!Chat : false,
            'sendAIResponseToChat方法': window.Chat && typeof window.Chat.sendAIResponseToChat === 'function'
        });
        
        if (!this.currentResponse) {
            console.error("❌ 沒有AI回應內容可以分享");
            if (window.UI && window.UI.showErrorToast) {
                window.UI.showErrorToast("沒有AI回應內容可以分享");
            }
            return;
        }
        
        // 檢查聊天功能是否可用
        if (window.Chat && typeof window.Chat.sendAIResponseToChat === 'function') {
            try {
                window.Chat.sendAIResponseToChat(this.currentResponse);
                this.hideShareOptions();
                console.log('✅ AI回應已成功分享到聊天室');
                
                if (window.UI && window.UI.showSuccessToast) {
                    window.UI.showSuccessToast("AI回應已分享到聊天室");
                }
            } catch (error) {
                console.error("❌ 分享AI回應時發生錯誤:", error);
                if (window.UI && window.UI.showErrorToast) {
                    window.UI.showErrorToast("分享失敗: " + error.message);
                }
            }
        } else if (typeof Chat !== 'undefined' && Chat && typeof Chat.sendAIResponseToChat === 'function') {
            // 備用方案：使用全局Chat對象
            try {
                Chat.sendAIResponseToChat(this.currentResponse);
                this.hideShareOptions();
                console.log('✅ AI回應已成功分享到聊天室 (使用全局Chat)');
                
                if (window.UI && window.UI.showSuccessToast) {
                    window.UI.showSuccessToast("AI回應已分享到聊天室");
                }
            } catch (error) {
                console.error("❌ 分享AI回應時發生錯誤:", error);
                if (window.UI && window.UI.showErrorToast) {
                    window.UI.showErrorToast("分享失敗: " + error.message);
                }
            }
        } else {
            console.error("❌ 聊天功能不可用，無法分享AI回應");
            if (window.UI && window.UI.showErrorToast) {
                window.UI.showErrorToast("聊天功能不可用，無法分享AI回應");
            }
        }
    }

    // 隱藏分享選項
    hideShareOptions() {
        if (this.shareOptions) {
            this.shareOptions.style.display = 'none';
        }
    }

    // 處理衝突請求AI幫助
    handleConflictHelp(conflictData) {
        const analysis = this.analyzeConflict(conflictData);
        
        // 在衝突模態中顯示AI分析
        const analysisContainer = document.getElementById('conflictAIAnalysis');
        if (analysisContainer) {
            analysisContainer.innerHTML = analysis;
        }
    }

    // 獲取AI助教簡單介紹
    getAIIntroduction() {
        return `
            <h6><i class="fas fa-robot"></i> 🤖 AI助教使用說明</h6>
            
            <div class="card mb-3">
                <div class="card-body">
                    <h7><strong>💡 如何使用AI助教：</strong></h7>
                    <ol class="mt-2">
                        <li><strong>編寫代碼：</strong>在編輯器中輸入你的 Python 代碼</li>
                        <li><strong>選擇功能：</strong>點擊下方按鈕選擇需要的分析功能</li>
                        <li><strong>查看回應：</strong>AI 會分析你的代碼並提供專業建議</li>
                        <li><strong>分享討論：</strong>可將 AI 建議分享到聊天室與同學討論</li>
                        <li><strong>學習改進：</strong>根據建議改進代碼，提升編程技能</li>
                    </ol>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-6">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <h8><strong>📝 解釋程式</strong></h8>
                            <p class="small text-muted mb-2">AI 詳細解釋代碼邏輯和功能</p>
                            <button class="btn btn-outline-primary btn-sm w-100" onclick="globalAskAI('analyze')">
                                <i class="fas fa-lightbulb"></i> 開始解釋
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <h8><strong>🔍 檢查錯誤</strong></h8>
                            <p class="small text-muted mb-2">AI 找出語法和邏輯錯誤</p>
                            <button class="btn btn-outline-warning btn-sm w-100" onclick="globalAskAI('check_errors')">
                                <i class="fas fa-bug"></i> 檢查錯誤
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-6 mt-2">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <h8><strong>⚡ 改進建議</strong></h8>
                            <p class="small text-muted mb-2">AI 提供代碼優化和改進方案</p>
                            <button class="btn btn-outline-success btn-sm w-100" onclick="globalAskAI('improvement_tips')">
                                <i class="fas fa-lightbulb"></i> 取得建議
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-6 mt-2">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <h8><strong>🔧 衝突分析</strong></h8>
                            <p class="small text-muted mb-2">多人協作衝突處理和歷史查看</p>
                            <button class="btn btn-outline-danger btn-sm w-100" onclick="globalTestConflictAnalysis()">
                                <i class="fas fa-code-branch"></i> 衝突工具
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h8><i class="fas fa-code-branch"></i> 協作衝突處理系統</h8>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>🔧 衝突分析功能：</strong></p>
                    <ul class="mb-3">
                        <li><strong>測試衝突</strong>：模擬協作衝突情況，學習處理方法</li>
                        <li><strong>查看歷史</strong>：檢視過去的衝突處理記錄和學習經驗</li>
                        <li><strong>實時分析</strong>：在真實衝突時，AI 提供具體解決建議</li>
                        <li><strong>差異對比</strong>：清楚顯示雙方代碼的差異</li>
                    </ul>
                    
                    <p class="mb-2"><strong>🤝 協作衝突處理流程：</strong></p>
                    <ol class="mb-0">
                        <li><strong>衝突預警</strong>：修改他人正在編輯的代碼時會提醒</li>
                        <li><strong>自動檢測</strong>：系統檢測到同時編輯產生的衝突</li>
                        <li><strong>界面顯示</strong>：被修改方看差異對比，修改方看等待狀態</li>
                        <li><strong>AI 協助</strong>：點擊「請AI協助分析」獲得專業建議</li>
                        <li><strong>決定方案</strong>：選擇接受或拒絕對方修改</li>
                    </ol>
                </div>
            </div>

            <div class="alert alert-success">
                <h8><i class="fas fa-graduation-cap"></i> 學習小貼士：</h8>
                <ul class="mb-0 mt-2">
                    <li><strong>先寫再問</strong>：編寫一段代碼後再使用 AI 分析，學習效果更佳</li>
                    <li><strong>多次互動</strong>：根據 AI 建議修改後，可再次分析學習改進</li>
                    <li><strong>協作討論</strong>：將 AI 分析結果分享到聊天室，與同學討論學習</li>
                    <li><strong>衝突學習</strong>：遇到協作衝突時，善用 AI 分析功能理解和解決</li>
                    <li><strong>實踐應用</strong>：將 AI 建議實際應用到代碼中，提升編程技能</li>
                </ul>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>注意：</strong>AI 助教會根據你的代碼提供個性化建議。如果沒有代碼，AI 會提供通用的學習指導。記得將有用的建議分享給其他同學一起學習！
            </div>
        `;
    }

    // 顯示AI助教介紹
    showAIIntroduction() {
        this.showResponse(this.getAIIntroduction());
        this.isFirstPrompt = false;
    }
}

// 創建全域AI助教實例
let AIAssistant;

// 立即創建AI助教實例
function initializeAIAssistant() {
    if (!AIAssistant) {
        AIAssistant = new AIAssistantManager();
        
        // 同時設置為window全域變數，確保在任何地方都能存取
        window.AIAssistant = AIAssistant;
        
        console.log('🔧 AI助教管理器已創建');
        console.log('✅ 全域 AIAssistant 實例已創建並設置到 window:', AIAssistant);
        
        // 初始化AI助教
        AIAssistant.initialize();
    }
}

// 確保在DOM載入後初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeAIAssistant();
});

// 如果DOM已經載入，立即初始化
if (document.readyState === 'loading') {
    // DOM還在載入中，等待DOMContentLoaded事件
} else {
    // DOM已經載入完成，立即初始化
    initializeAIAssistant();
}

// 全域函數供HTML調用
function askAI(action) {
    if (!AIAssistant) {
        initializeAIAssistant();
    }
    if (AIAssistant) {
        AIAssistant.requestAnalysis(action);
    } else {
        console.error('❌ AI助教未初始化');
    }
}

function shareAIResponse() {
    if (!AIAssistant) {
        initializeAIAssistant();
    }
    if (AIAssistant) {
        AIAssistant.shareResponse();
    }
}

function hideShareOptions() {
    if (!AIAssistant) {
        initializeAIAssistant();
    }
    if (AIAssistant) {
        AIAssistant.hideShareOptions();
    }
}

function showShareOptions() {
    if (!AIAssistant) {
        initializeAIAssistant();
    }
    if (AIAssistant) {
        AIAssistant.showShareOptions();
    }
}

// 新增：顯示AI助教介紹
function showAIIntro() {
    if (!AIAssistant) {
        initializeAIAssistant();
    }
    if (AIAssistant) {
        AIAssistant.showAIIntroduction();
    }
}

// 確保全域函數也可以通過window存取
window.askAI = askAI;
window.globalAskAI = askAI; // 向後兼容
window.shareAIResponse = shareAIResponse;
window.hideShareOptions = hideShareOptions;
window.showShareOptions = showShareOptions;
window.showAIIntro = showAIIntro; 