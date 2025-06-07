// 衝突檢測和解決管理器
class ConflictResolverManager {
    constructor() {
        this.conflictData = null;
        this.modal = null;
        this.modalElement = null;
        this.currentConflict = null;
        this.lastAIAnalysis = null;
        console.log('🔧 ConflictResolverManager 已創建');
    }

    // 初始化衝突解決器
    initialize() {
        this.modalElement = document.getElementById('conflictModal');
        if (!this.modalElement) {
            console.error('❌ Conflict modal element #conflictModal not found during initialization!');
        } else {
            console.log('✅ ConflictResolver modal element found');
        }
        console.log('✅ ConflictResolver initialized. Modal element cached.');
    }

    // 🆕 顯示主改方決定界面
    showMainChangerDecision(conflictData) {
        console.log('🚨 顯示主改方決定界面:', conflictData);
        
        this.currentConflict = conflictData;
        
        // 更新UI內容
        this.updateConflictModalContent(conflictData, true); // true表示主改方
        
        // 顯示模態框
        this.showModal();
        
        // 顯示狀態訊息
        this.showStatusMessage(`您是主改方，請決定如何處理與 ${conflictData.other_changer} 的代碼衝突`, 'warning', 0);
    }

    // 🆕 顯示非主改方等待界面
    showWaitingForDecision(conflictData) {
        console.log('⏳ 顯示等待主改方決定界面:', conflictData);
        
        this.currentConflict = conflictData;
        
        // 更新UI內容
        this.updateConflictModalContent(conflictData, false); // false表示非主改方
        
        // 顯示模態框
        this.showModal();
        
        // 顯示狀態訊息
        const mainChangerName = conflictData.main_changer || '其他同學';
        const changeType = this.getChangeTypeText(conflictData.change_type || 'edit');
        this.showStatusMessage(`⏳ ${mainChangerName} 正在處理代碼衝突 (${changeType})，請等待決定...`, 'info', 0);
    }

    // 🆕 更新模態框內容
    updateConflictModalContent(conflictData, isMainChanger) {
        // 更新標題
        const modalTitle = document.querySelector('#conflictModal .modal-title');
        if (modalTitle) {
            modalTitle.innerHTML = isMainChanger ? 
                '<i class="fas fa-user-edit"></i> 您是主改方 - 請決定衝突處理方式' :
                '<i class="fas fa-hourglass-half"></i> 等待主改方決定';
        }

        // 更新用戶信息
        const conflictUserSpan = document.getElementById('conflictUserName');
        const otherUserSpan = document.getElementById('otherUserName');
        if (conflictUserSpan && conflictData.other_changer) {
            conflictUserSpan.textContent = conflictData.other_changer;
        }
        if (otherUserSpan && conflictData.other_changer) {
            otherUserSpan.textContent = conflictData.other_changer;
        }

        // 顯示代碼差異
        this.displayCodeDifference(
            conflictData.local_code || '', 
            conflictData.remote_code || '', 
            conflictData.other_changer || '其他同學'
        );

        // 更新按鈕狀態
        this.updateConflictButtons(isMainChanger);

        // 顯示操作類型
        this.displayChangeType(conflictData.change_type || 'edit');
    }

    // 🆕 更新衝突解決按鈕
    updateConflictButtons(isMainChanger) {
        const buttonContainer = document.querySelector('#conflictModal .modal-footer');
        if (!buttonContainer) return;

        if (isMainChanger) {
            // 主改方按鈕
            buttonContainer.innerHTML = `
                <button type="button" class="btn btn-success" onclick="ConflictResolver.resolveConflict('force')">
                    <i class="fas fa-lock"></i> 強制修改
                </button>
                <button type="button" class="btn btn-info" onclick="ConflictResolver.shareToChat()">
                    <i class="fas fa-comments"></i> 分享到聊天室
                </button>
                <button type="button" class="btn btn-warning" onclick="ConflictResolver.requestAIAnalysis()">
                    <i class="fas fa-robot"></i> AI協助分析
                </button>
            `;
        } else {
            // 非主改方按鈕
            buttonContainer.innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="ConflictResolver.hideModal()">
                    <i class="fas fa-times"></i> 關閉
                </button>
                <button type="button" class="btn btn-info" onclick="ConflictResolver.shareToChat()">
                    <i class="fas fa-comments"></i> 討論
                </button>
                <button type="button" class="btn btn-outline-primary" onclick="ConflictResolver.showConflictHistory()">
                    <i class="fas fa-history"></i> 查看歷史
                </button>
            `;
        }
    }

    // 🆕 顯示操作類型
    displayChangeType(changeType) {
        const changeTypeElement = document.getElementById('conflictChangeType');
        if (changeTypeElement) {
            const typeText = this.getChangeTypeText(changeType);
            const typeIcon = this.getChangeTypeIcon(changeType);
            changeTypeElement.innerHTML = `${typeIcon} ${typeText}`;
        }
    }

    // 🆕 獲取操作類型文字
    getChangeTypeText(changeType) {
        const types = {
            'edit': '一般編輯',
            'paste': '貼上代碼',
            'cut': '剪切代碼',
            'import': '導入文件',
            'load': '載入歷史',
            'replace': '替換內容'
        };
        return types[changeType] || '未知操作';
    }

    // 🆕 獲取操作類型圖標
    getChangeTypeIcon(changeType) {
        const icons = {
            'edit': '<i class="fas fa-edit text-primary"></i>',
            'paste': '<i class="fas fa-clipboard text-success"></i>',
            'cut': '<i class="fas fa-cut text-danger"></i>',
            'import': '<i class="fas fa-file-import text-info"></i>',
            'load': '<i class="fas fa-history text-warning"></i>',
            'replace': '<i class="fas fa-exchange-alt text-purple"></i>'
        };
        return icons[changeType] || '<i class="fas fa-question text-muted"></i>';
    }

    // 🆕 顯示編輯被阻擋提示
    showEditBlocked(conflictData) {
        console.log('🚫 顯示編輯被阻擋提示:', conflictData);
        
        const mainChangerName = conflictData.main_changer || '主改方';
        const changeType = this.getChangeTypeText(conflictData.change_type || 'edit');
        
        this.showStatusMessage(
            `🚫 編輯已暫停：${mainChangerName} 正在處理衝突 (${changeType})，請等待決定後再編輯`, 
            'warning', 
            5000
        );
    }

    // 顯示代碼差異對比
    displayCodeDifference(myCode, otherCode, otherUserName) {
        console.log('🔍 顯示代碼差異對比...');
        console.log(`📝 我的代碼長度: ${myCode?.length || 0}`);
        console.log(`📝 ${otherUserName}代碼長度: ${otherCode?.length || 0}`);

        const myCodeElement = document.getElementById('myCodeVersion');
        const otherCodeElement = document.getElementById('otherCodeVersion');
        
        if (myCodeElement) {
            myCodeElement.textContent = myCode || '(空白)';
            console.log('✅ 已設置我的代碼內容');
        } else {
            console.error('❌ 找不到 myCodeVersion 元素');
        }
        
        if (otherCodeElement) {
            otherCodeElement.textContent = otherCode || '(空白)';
            console.log('✅ 已設置對方代碼內容');
        } else {
            console.error('❌ 找不到 otherCodeVersion 元素');
        }

        // 執行差異分析
        const diffAnalysis = this.performLocalDiffAnalysis(myCode, otherCode);
        this.displayDiffSummary(diffAnalysis, otherUserName);
        
        console.log('✅ 代碼差異對比顯示完成');
    }

    // 本地差異分析
    performLocalDiffAnalysis(code1, code2) {
        console.log('🔍 執行本地差異分析...');
        
        const text1 = (code1 || '').trim();
        const text2 = (code2 || '').trim();
        
        const lines1 = text1.split('\n');
        const lines2 = text2.split('\n');
        
        const analysis = {
            myLines: lines1.length,
            otherLines: lines2.length,
            myChars: text1.length,
            otherChars: text2.length,
            isSame: text1 === text2,
            addedLines: 0,
            removedLines: 0,
            modifiedLines: 0,
            hasSignificantChanges: false,
            changeType: 'unknown'
        };

        if (analysis.isSame) {
            analysis.changeType = 'identical';
            return analysis;
        }

        // 簡單的行級比較
        const maxLines = Math.max(lines1.length, lines2.length);
        for (let i = 0; i < maxLines; i++) {
            const line1 = (lines1[i] || '').trim();
            const line2 = (lines2[i] || '').trim();
            
            if (line1 !== line2) {
                if (!line1 && line2) {
                    analysis.addedLines++;
                } else if (line1 && !line2) {
                    analysis.removedLines++;
                } else if (line1 && line2) {
                    analysis.modifiedLines++;
                }
            }
        }

        // 判斷變更類型
        if (analysis.addedLines > 0 && analysis.removedLines === 0 && analysis.modifiedLines === 0) {
            analysis.changeType = 'addition';
        } else if (analysis.addedLines === 0 && analysis.removedLines > 0 && analysis.modifiedLines === 0) {
            analysis.changeType = 'deletion';
        } else if (analysis.addedLines === 0 && analysis.removedLines === 0 && analysis.modifiedLines > 0) {
            analysis.changeType = 'modification';
        } else {
            analysis.changeType = 'complex';
        }

        // 判斷是否有重大變更
        analysis.hasSignificantChanges = 
            analysis.addedLines > 2 || 
            analysis.removedLines > 2 || 
            analysis.modifiedLines > 3 ||
            Math.abs(analysis.myChars - analysis.otherChars) > 50;

        console.log('📊 本地差異分析結果:', analysis);
        return analysis;
    }

    // 顯示差異摘要
    displayDiffSummary(analysis, otherUserName) {
        const summaryElement = document.getElementById('diffSummary');
        if (!summaryElement) {
            console.error('❌ 找不到差異摘要元素');
            return;
        }

        let summaryText = '';
        let summaryIcon = '';
        
        if (analysis.isSame) {
            summaryIcon = '🟢';
            summaryText = '代碼內容相同，可能是編輯時序問題';
        } else {
            // 根據變更類型生成摘要
            const changes = [];
            if (analysis.addedLines > 0) changes.push(`新增 ${analysis.addedLines} 行`);
            if (analysis.removedLines > 0) changes.push(`刪除 ${analysis.removedLines} 行`);
            if (analysis.modifiedLines > 0) changes.push(`修改 ${analysis.modifiedLines} 行`);
            
            // 選擇合適的圖標和描述
            if (analysis.hasSignificantChanges) {
                summaryIcon = '🔴';
                summaryText = `重大差異: ${changes.join(', ')}`;
            } else {
                summaryIcon = '🟡';
                summaryText = `輕微差異: ${changes.join(', ')}`;
            }
            
            // 添加詳細信息
            summaryText += ` | 您: ${analysis.myLines} 行 (${analysis.myChars} 字符) vs ${otherUserName}: ${analysis.otherLines} 行 (${analysis.otherChars} 字符)`;
            
            // 添加變更類型提示
            switch (analysis.changeType) {
                case 'addition':
                    summaryText += ' | 類型: 主要是新增內容';
                    break;
                case 'deletion':
                    summaryText += ' | 類型: 主要是刪除內容';
                    break;
                case 'modification':
                    summaryText += ' | 類型: 主要是修改現有內容';
                    break;
                case 'complex':
                    summaryText += ' | 類型: 複雜變更 (新增+刪除+修改)';
                    break;
            }
        }

        summaryElement.textContent = `${summaryIcon} ${summaryText}`;
        console.log('📊 差異摘要已更新:', summaryText);
    }

    // 🆕 狀態消息系統
    showStatusMessage(message, type = 'info', autoHide = 3000) {
        console.log(`📢 顯示狀態消息 [${type}]:`, message);
        
        // 找到輸出區域
        const outputContainer = document.getElementById('executionOutput') || 
                               document.getElementById('execution-result') || 
                               document.querySelector('.execution-output');
        
        if (!outputContainer) {
            console.warn('⚠️ 找不到輸出容器，使用fallback顯示');
            // 創建臨時狀態顯示區域
            this.createTemporaryStatusArea(message, type);
            return;
        }

        // 清除之前的狀態消息
        this.clearStatusMessage();

        // 創建狀態消息元素
        const statusDiv = document.createElement('div');
        statusDiv.id = 'statusMessage';
        statusDiv.className = `status-message alert alert-${this.getBootstrapAlertType(type)} d-flex align-items-center`;
        statusDiv.innerHTML = `
            <div class="me-2">${this.getStatusIcon(type)}</div>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close btn-close-sm" onclick="ConflictResolver.clearStatusMessage()"></button>
        `;

        // 插入到輸出區域的開頭
        outputContainer.insertBefore(statusDiv, outputContainer.firstChild);

        // 自動隱藏
        if (autoHide > 0) {
            setTimeout(() => {
                this.clearStatusMessage();
            }, autoHide);
        }

        console.log('✅ 狀態消息已顯示');
    }

    // 🆕 清除狀態消息
    clearStatusMessage() {
        const statusMessage = document.getElementById('statusMessage');
        if (statusMessage) {
            statusMessage.remove();
            console.log('✅ 狀態消息已清除');
        }
    }

    // 🆕 創建臨時狀態區域
    createTemporaryStatusArea(message, type) {
        // 移除舊的臨時狀態
        const oldTemp = document.getElementById('tempStatusArea');
        if (oldTemp) oldTemp.remove();

        const tempDiv = document.createElement('div');
        tempDiv.id = 'tempStatusArea';
        tempDiv.className = 'fixed-top';
        tempDiv.style.cssText = 'top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; max-width: 600px;';
        
        tempDiv.innerHTML = `
            <div class="alert alert-${this.getBootstrapAlertType(type)} alert-dismissible shadow">
                <div class="d-flex align-items-center">
                    <div class="me-2">${this.getStatusIcon(type)}</div>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close" onclick="document.getElementById('tempStatusArea').remove()"></button>
                </div>
            </div>
        `;

        document.body.appendChild(tempDiv);

        // 3秒後自動移除
        setTimeout(() => {
            if (tempDiv.parentNode) {
                tempDiv.remove();
            }
        }, 3000);
    }

    // 🆕 獲取Bootstrap警告類型
    getBootstrapAlertType(type) {
        const typeMap = {
            'info': 'info',
            'warning': 'warning', 
            'error': 'danger',
            'success': 'success'
        };
        return typeMap[type] || 'info';
    }

    // 🆕 獲取狀態圖標
    getStatusIcon(type) {
        const iconMap = {
            'info': '<i class="fas fa-info-circle text-info"></i>',
            'warning': '<i class="fas fa-exclamation-triangle text-warning"></i>',
            'error': '<i class="fas fa-times-circle text-danger"></i>',
            'success': '<i class="fas fa-check-circle text-success"></i>'
        };
        return iconMap[type] || '<i class="fas fa-info-circle"></i>';
    }

    // 🆕 解決衝突 (主改方專用)
    resolveConflict(solution) {
        console.log('✅ 主改方選擇解決方案:', solution);
        
        if (!this.currentConflict) {
            console.error('❌ 沒有當前衝突數據');
            this.showStatusMessage('沒有衝突數據，無法解決', 'error');
            return;
        }

        // 發送解決方案到WebSocket
        if (window.wsManager && window.wsManager.isConnected()) {
            window.wsManager.sendMessage({
                type: 'conflict_resolution',
                room_id: window.wsManager.currentRoom,
                conflict_id: this.currentConflict.conflict_id,
                resolution: solution,
                user_id: window.wsManager.currentUser
            });

            // 顯示處理中狀態
            this.showStatusMessage(`正在執行 ${this.getSolutionText(solution)}...`, 'info', 2000);
            
            // 隱藏模態框
            this.hideModal();
        } else {
            console.error('❌ WebSocket未連接');
            this.showStatusMessage('網絡連接失敗，無法發送解決方案', 'error');
        }
    }

    // 🆕 獲取解決方案文字
    getSolutionText(solution) {
        const solutionMap = {
            'force': '強制修改',
            'share': '分享到聊天室',
            'ai_analyze': 'AI協助分析'
        };
        return solutionMap[solution] || solution;
    }

    // 🆕 分享到聊天室
    shareToChat() {
        if (!this.currentConflict) {
            this.showStatusMessage('沒有衝突數據可分享', 'error');
            return;
        }

        const summary = `💬 代碼衝突討論：${this.currentConflict.main_changer || '某同學'} vs ${this.currentConflict.other_changer || '某同學'} 的代碼修改發生衝突，大家來討論一下最佳解決方案`;
        
        if (window.Chat && typeof window.Chat.addChatMessage === 'function') {
            window.Chat.addChatMessage(summary, window.wsManager?.currentUser || 'Unknown');
            this.showStatusMessage('衝突信息已分享到聊天室', 'success');
        } else {
            this.showStatusMessage('聊天功能不可用', 'error');
        }
    }

    // AI分析請求
    requestAIAnalysis() {
        console.log('🤖 請求AI協助分析衝突...');
        
        if (!this.currentConflict) {
            this.showStatusMessage('沒有衝突數據，無法進行AI分析', 'error');
            return;
        }

        // 顯示AI分析載入狀態
        this.showStatusMessage('🤖 AI正在分析衝突，請稍候...', 'info', 0);

        // 發送AI分析請求
        if (window.wsManager && window.wsManager.isConnected()) {
            window.wsManager.sendMessage({
                type: 'ai_request',
                action: 'conflict_analysis',
                data: {
                    userCode: this.currentConflict.local_code || '',
                    conflictCode: this.currentConflict.remote_code || '',
                    userName: window.wsManager.currentUser || 'Unknown',
                    conflictUser: this.currentConflict.other_changer || '其他同學',
                    roomId: window.wsManager.currentRoom || 'unknown'
                }
            });
        } else {
            this.showStatusMessage('網絡連接失敗，無法請求AI分析', 'error');
        }
    }

    // 顯示/隱藏模態框
    showModal() {
        if (!this.modalElement) {
            console.error('❌ 模態框元素不存在');
            return;
        }

        try {
            this.modal = bootstrap.Modal.getInstance(this.modalElement);
            if (!this.modal) {
                this.modal = new bootstrap.Modal(this.modalElement, { backdrop: 'static' });
            }
            this.modal.show();
            console.log('✅ 模態框已顯示');
        } catch (error) {
            console.error('❌ 顯示模態框失敗:', error);
        }
    }

    hideModal() {
        if (this.modal) {
            this.modal.hide();
            console.log('✅ 模態框已隱藏');
        }
    }

    // 🆕 衝突歷史管理
    showConflictHistory() {
        const conflictHistory = JSON.parse(localStorage.getItem('conflict_history') || '[]');
        
        if (conflictHistory.length === 0) {
            this.showStatusMessage('暫無衝突歷史記錄', 'info');
            return;
        }
        
        console.log('📜 顯示衝突歷史:', conflictHistory.length, '條記錄');
        // 這裡可以實現歷史記錄的詳細顯示
        this.showStatusMessage(`發現 ${conflictHistory.length} 條衝突歷史記錄`, 'info');
    }

    // 🆕 處理衝突解決結果
    handleConflictResolved(data) {
        console.log('✅ 衝突已解決:', data);
        
        this.hideModal();
        this.clearStatusMessage();
        
        const message = data.message || '衝突已成功解決';
        this.showStatusMessage(message, 'success', 3000);
        
        // 清理衝突狀態
        this.currentConflict = null;
        this.lastAIAnalysis = null;
    }

    // 🆕 處理AI分析回應
    handleAIAnalysisResponse(data) {
        console.log('🤖 收到AI分析回應:', data);
        
        this.clearStatusMessage();
        
        if (data.success) {
            this.lastAIAnalysis = data.response;
            this.showStatusMessage('🤖 AI分析完成，請查看分析結果', 'success', 3000);
            // 這裡可以顯示詳細的AI分析結果
        } else {
            this.showStatusMessage('AI分析失敗: ' + (data.error || '未知錯誤'), 'error');
        }
    }
}

// 創建全局實例
const ConflictResolver = new ConflictResolverManager();
window.ConflictResolver = ConflictResolver;

// 全局函數供HTML調用
function resolveConflict(solution) {
    ConflictResolver.resolveConflict(solution);
}

function askAIForConflictHelp() {
    ConflictResolver.requestAIAnalysis();
}

console.log('✅ ConflictResolver 模組已載入'); 