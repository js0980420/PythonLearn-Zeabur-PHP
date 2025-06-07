// 高級衝突檢測系統
class AdvancedConflictDetector {
    constructor() {
        this.isMainEditor = false; // 是否為主改方
        this.lastCodeSnapshot = '';
        this.lastChangeTime = 0;
        this.conflictThreshold = {
            sameLineModification: true,
            massiveChange: 50, // 超過50個字符變化視為大量修改
            pasteDetection: true,
            importDetection: true
        };
        this.activeConflict = null;
        this.votingSession = null;
        
        console.log('🔧 AdvancedConflictDetector 已初始化');
    }

    // 設置主改方狀態
    setMainEditor(isMain) {
        this.isMainEditor = isMain;
        console.log(`🎯 設置主改方狀態: ${isMain ? '是' : '否'}`);
    }

    // 檢測代碼變化類型
    detectChangeType(oldCode, newCode) {
        const oldLines = oldCode.split('\n');
        const newLines = newCode.split('\n');
        
        const changeInfo = {
            type: 'normal',
            severity: 'low',
            affectedLines: [],
            changeSize: Math.abs(newCode.length - oldCode.length),
            lineChanges: {
                added: 0,
                removed: 0,
                modified: 0
            }
        };

        // 檢測大量變化
        if (changeInfo.changeSize > this.conflictThreshold.massiveChange) {
            changeInfo.severity = 'high';
            
            // 檢測是否為貼上操作
            if (this.isPasteOperation(oldCode, newCode)) {
                changeInfo.type = 'paste';
            }
            // 檢測是否為導入操作
            else if (this.isImportOperation(oldCode, newCode)) {
                changeInfo.type = 'import';
            }
            // 檢測是否為大量刪除
            else if (newCode.length < oldCode.length * 0.5) {
                changeInfo.type = 'mass_delete';
            }
            else {
                changeInfo.type = 'mass_change';
            }
        }

        // 逐行比較
        const maxLines = Math.max(oldLines.length, newLines.length);
        for (let i = 0; i < maxLines; i++) {
            const oldLine = oldLines[i] || '';
            const newLine = newLines[i] || '';
            
            if (oldLine !== newLine) {
                changeInfo.affectedLines.push({
                    lineNumber: i + 1,
                    oldContent: oldLine,
                    newContent: newLine,
                    changeType: this.getLineChangeType(oldLine, newLine)
                });
                
                if (!oldLine && newLine) {
                    changeInfo.lineChanges.added++;
                } else if (oldLine && !newLine) {
                    changeInfo.lineChanges.removed++;
                } else {
                    changeInfo.lineChanges.modified++;
                }
            }
        }

        return changeInfo;
    }

    // 檢測是否為貼上操作
    isPasteOperation(oldCode, newCode) {
        // 如果新代碼比舊代碼長很多，且包含多行，可能是貼上
        const lineDiff = newCode.split('\n').length - oldCode.split('\n').length;
        const charDiff = newCode.length - oldCode.length;
        
        return lineDiff > 5 || charDiff > 100;
    }

    // 檢測是否為導入操作
    isImportOperation(oldCode, newCode) {
        // 檢測是否包含典型的導入語句
        const importPatterns = [
            /^import\s+\w+/m,
            /^from\s+\w+\s+import/m,
            /^#.*導入|匯入|import/m
        ];
        
        return importPatterns.some(pattern => pattern.test(newCode) && !pattern.test(oldCode));
    }

    // 獲取行變化類型
    getLineChangeType(oldLine, newLine) {
        if (!oldLine && newLine) return 'added';
        if (oldLine && !newLine) return 'removed';
        if (oldLine !== newLine) return 'modified';
        return 'unchanged';
    }

    // 檢測同行衝突
    detectSameLineConflict(myCode, otherUserCode, otherUserInfo) {
        const myLines = myCode.split('\n');
        const otherLines = otherUserCode.split('\n');
        const conflicts = [];

        const maxLines = Math.max(myLines.length, otherLines.length);
        
        for (let i = 0; i < maxLines; i++) {
            const myLine = (myLines[i] || '').trim();
            const otherLine = (otherLines[i] || '').trim();
            const originalLine = (this.lastCodeSnapshot.split('\n')[i] || '').trim();
            
            // 檢測同一行被兩人修改成不同內容
            const bothModified = (myLine !== originalLine) && (otherLine !== originalLine);
            const differentContent = (myLine !== otherLine);
            
            if (bothModified && differentContent) {
                conflicts.push({
                    lineNumber: i + 1,
                    originalContent: originalLine,
                    myContent: myLine,
                    otherContent: otherLine,
                    otherUser: otherUserInfo
                });
            }
        }

        return conflicts.length > 0 ? conflicts : null;
    }

    // 主改方衝突警告
    showMainEditorConflictWarning(changeInfo, otherUsers) {
        if (!this.isMainEditor) return;

        const warningData = {
            changeType: changeInfo.type,
            severity: changeInfo.severity,
            affectedLines: changeInfo.affectedLines.length,
            otherUsers: otherUsers,
            timestamp: Date.now()
        };

        this.createConflictWarningModal(warningData);
    }

    // 創建衝突警告模態框
    createConflictWarningModal(warningData) {
        // 移除現有模態框
        const existingModal = document.getElementById('conflictWarningModal');
        if (existingModal) {
            existingModal.remove();
        }

        const modalHtml = `
            <div class="modal fade" id="conflictWarningModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle"></i> 
                                協作衝突警告
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-info-circle"></i> 衝突情況</h6>
                                <p><strong>變更類型:</strong> ${this.getChangeTypeDescription(warningData.changeType)}</p>
                                <p><strong>影響範圍:</strong> ${warningData.affectedLines} 行代碼</p>
                                <p><strong>其他協作者:</strong> ${warningData.otherUsers.map(u => u.username).join(', ')}</p>
                            </div>
                            
                            <div class="mb-3">
                                <h6>您的修改可能會影響其他同學正在編輯的代碼，請選擇處理方式：</h6>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <button class="btn btn-danger w-100" onclick="window.AdvancedConflictDetector.handleConflictChoice('force')">
                                        <i class="fas fa-bolt"></i>
                                        <div class="mt-1">
                                            <strong>強制修改</strong>
                                            <small class="d-block">立即應用修改，覆蓋其他人的工作</small>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-primary w-100" onclick="window.AdvancedConflictDetector.handleConflictChoice('vote')">
                                        <i class="fas fa-vote-yea"></i>
                                        <div class="mt-1">
                                            <strong>等待投票</strong>
                                            <small class="d-block">讓其他同學投票決定是否同意修改</small>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-info w-100" onclick="window.AdvancedConflictDetector.handleConflictChoice('discuss')">
                                        <i class="fas fa-comments"></i>
                                        <div class="mt-1">
                                            <strong>分享討論</strong>
                                            <small class="d-block">在聊天室中分享修改內容討論</small>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-success w-100" onclick="window.AdvancedConflictDetector.handleConflictChoice('ai')">
                                        <i class="fas fa-robot"></i>
                                        <div class="mt-1">
                                            <strong>AI協助</strong>
                                            <small class="d-block">使用AI分析衝突並提供建議</small>
                                        </div>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button class="btn btn-outline-secondary" onclick="window.AdvancedConflictDetector.cancelConflict()">
                                    <i class="fas fa-times"></i> 取消修改
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('conflictWarningModal'));
        modal.show();

        // 存儲當前衝突數據
        this.activeConflict = {
            warningData: warningData,
            modal: modal,
            timestamp: Date.now()
        };
    }

    // 獲取變更類型描述
    getChangeTypeDescription(type) {
        const descriptions = {
            'paste': '大量貼上操作',
            'import': '導入新代碼',
            'mass_delete': '大量刪除操作',
            'mass_change': '大量修改操作',
            'normal': '一般修改'
        };
        return descriptions[type] || '未知修改';
    }

    // 處理衝突選擇
    handleConflictChoice(choice) {
        if (!this.activeConflict) return;

        console.log(`🎯 主改方選擇: ${choice}`);

        switch (choice) {
            case 'force':
                this.forceApplyChanges();
                break;
            case 'vote':
                this.startVotingSession();
                break;
            case 'discuss':
                this.shareToChat();
                break;
            case 'ai':
                this.requestAIAssistance();
                break;
        }

        this.closeConflictModal();
    }

    // 強制應用修改
    forceApplyChanges() {
        console.log('💪 強制應用修改');
        
        // 發送強制修改通知給其他用戶
        if (window.wsManager && window.wsManager.isConnected) {
            window.wsManager.sendMessage({
                type: 'force_code_change',
                message: '主改方強制應用了修改',
                forced_by: window.wsManager.currentUser
            });
        }

        // 顯示成功提示
        this.showToast('已強制應用修改', 'warning');
        
        // 記錄到聊天室
        if (window.Chat) {
            window.Chat.addSystemMessage('⚠️ 主改方強制應用了修改，請注意代碼變化');
        }
    }

    // 開始投票會話
    startVotingSession() {
        console.log('🗳️ 開始投票會話');
        
        this.votingSession = {
            id: Date.now(),
            startTime: Date.now(),
            votes: {},
            requiredVotes: 1, // 只需要一人同意
            status: 'active'
        };

        // 發送投票請求給其他用戶
        if (window.wsManager && window.wsManager.isConnected) {
            window.wsManager.sendMessage({
                type: 'voting_request',
                voting_id: this.votingSession.id,
                message: '主改方請求修改代碼，請投票決定是否同意',
                change_description: this.getChangeDescription(),
                requested_by: window.wsManager.currentUser
            });
        }

        this.showVotingWaitModal();
    }

    // 顯示投票等待模態框
    showVotingWaitModal() {
        const modalHtml = `
            <div class="modal fade" id="votingWaitModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-vote-yea"></i> 等待投票結果
                            </h5>
                        </div>
                        <div class="modal-body text-center">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <h6>正在等待其他同學投票...</h6>
                            <p class="text-muted">只需要一人同意即可應用修改</p>
                            <div id="votingProgress">
                                <small class="text-muted">投票進行中...</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" onclick="window.AdvancedConflictDetector.cancelVoting()">
                                取消投票
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('votingWaitModal'));
        modal.show();

        this.votingSession.modal = modal;
    }

    // 分享到聊天室討論
    shareToChat() {
        console.log('💬 分享到聊天室討論');
        
        const changeDescription = this.getChangeDescription();
        const message = `🔄 代碼修改討論\n${changeDescription}\n請大家討論是否同意這個修改。`;

        if (window.Chat) {
            window.Chat.addSystemMessage(message);
            // 自動打開聊天面板
            const chatTab = document.querySelector('[data-bs-target="#chatContainer"]');
            if (chatTab) {
                chatTab.click();
            }
        }

        this.showToast('已分享到聊天室，請在聊天中討論', 'info');
    }

    // 請求AI協助
    requestAIAssistance() {
        console.log('🤖 請求AI協助');
        
        const conflictData = {
            changeType: this.activeConflict.warningData.changeType,
            affectedLines: this.activeConflict.warningData.affectedLines,
            otherUsers: this.activeConflict.warningData.otherUsers.map(u => u.username),
            currentCode: window.Editor ? window.Editor.getCode() : '',
            changeDescription: this.getChangeDescription()
        };

        // 調用AI分析
        this.callAIForConflictAnalysis(conflictData);
    }

    // 調用AI進行衝突分析
    async callAIForConflictAnalysis(conflictData) {
        try {
            const response = await fetch('/api.php/ai', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'conflict_analysis',
                    conflict_data: {
                        type: conflictData.changeType || 'unknown',
                        old_code: this.lastCodeSnapshot || '',
                        new_code: conflictData.currentCode || '',
                        affected_lines: conflictData.affectedLines || 0,
                        other_users: conflictData.otherUsers.map(user => ({
                            username: user,
                            userId: user
                        }))
                    }
                })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showAIAnalysisResult(data.response);
                } else {
                    throw new Error(data.error || 'AI分析失敗');
                }
            } else {
                throw new Error(`HTTP ${response.status}: AI分析請求失敗`);
            }
        } catch (error) {
            console.error('❌ AI分析錯誤:', error);
            this.showToast('AI分析暫時無法使用: ' + error.message, 'error');
        }
    }

    // 顯示AI分析結果
    showAIAnalysisResult(analysis) {
        const modalHtml = `
            <div class="modal fade" id="aiAnalysisModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-robot"></i> AI衝突分析結果
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="ai-analysis-content">
                                ${this.formatAIAnalysis(analysis)}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-success" onclick="window.AdvancedConflictDetector.applyAIRecommendation()">
                                採用AI建議
                            </button>
                            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                關閉
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
        modal.show();

        // 同時分享到聊天室
        if (window.Chat) {
            window.Chat.addSystemMessage(`🤖 AI衝突分析結果：\n${analysis}`);
        }
    }

    // 格式化AI分析結果
    formatAIAnalysis(analysis) {
        return analysis.split('\n').map(line => {
            if (line.trim().startsWith('1.') || line.trim().startsWith('2.') || 
                line.trim().startsWith('3.') || line.trim().startsWith('4.')) {
                return `<p class="fw-bold text-primary">${line}</p>`;
            }
            return `<p>${line}</p>`;
        }).join('');
    }

    // 獲取變更描述
    getChangeDescription() {
        if (!this.activeConflict) return '未知修改';
        
        const data = this.activeConflict.warningData;
        return `${this.getChangeTypeDescription(data.changeType)}，影響 ${data.affectedLines} 行代碼`;
    }

    // 處理投票結果
    handleVoteResult(voteData) {
        if (!this.votingSession || this.votingSession.id !== voteData.voting_id) return;

        this.votingSession.votes[voteData.user_id] = voteData.vote;
        
        // 檢查是否有足夠的同意票
        const agreeVotes = Object.values(this.votingSession.votes).filter(vote => vote === 'agree').length;
        
        if (agreeVotes >= this.votingSession.requiredVotes) {
            this.votingSession.status = 'approved';
            this.applyVotedChanges();
        }

        this.updateVotingProgress();
    }

    // 更新投票進度
    updateVotingProgress() {
        const progressElement = document.getElementById('votingProgress');
        if (!progressElement || !this.votingSession) return;

        const totalVotes = Object.keys(this.votingSession.votes).length;
        const agreeVotes = Object.values(this.votingSession.votes).filter(vote => vote === 'agree').length;
        
        progressElement.innerHTML = `
            <small class="text-muted">
                目前投票: ${agreeVotes} 同意 / ${totalVotes - agreeVotes} 反對
                ${agreeVotes >= this.votingSession.requiredVotes ? '<br><span class="text-success">✅ 投票通過！</span>' : ''}
            </small>
        `;
    }

    // 應用投票通過的修改
    applyVotedChanges() {
        console.log('✅ 投票通過，應用修改');
        
        // 關閉投票模態框
        if (this.votingSession.modal) {
            this.votingSession.modal.hide();
        }

        // 發送通知
        if (window.wsManager && window.wsManager.isConnected) {
            window.wsManager.sendMessage({
                type: 'voted_change_applied',
                message: '投票通過，修改已應用',
                voting_id: this.votingSession.id
            });
        }

        this.showToast('投票通過，修改已應用', 'success');
        
        // 記錄到聊天室
        if (window.Chat) {
            window.Chat.addSystemMessage('✅ 投票通過，代碼修改已應用');
        }

        this.votingSession = null;
    }

    // 取消衝突
    cancelConflict() {
        console.log('❌ 取消衝突修改');
        this.closeConflictModal();
        this.showToast('已取消修改', 'info');
    }

    // 取消投票
    cancelVoting() {
        if (!this.votingSession) return;

        console.log('❌ 取消投票');
        
        // 通知其他用戶投票已取消
        if (window.wsManager && window.wsManager.isConnected) {
            window.wsManager.sendMessage({
                type: 'voting_cancelled',
                voting_id: this.votingSession.id,
                message: '投票已取消'
            });
        }

        if (this.votingSession.modal) {
            this.votingSession.modal.hide();
        }

        this.votingSession = null;
        this.showToast('投票已取消', 'info');
    }

    // 關閉衝突模態框
    closeConflictModal() {
        if (this.activeConflict && this.activeConflict.modal) {
            this.activeConflict.modal.hide();
        }
        this.activeConflict = null;
    }

    // 顯示提示消息
    showToast(message, type = 'info') {
        if (window.showToast) {
            window.showToast(message, type);
        } else {
            console.log(`📢 ${message}`);
        }
    }

    // 更新代碼快照
    updateCodeSnapshot(code) {
        this.lastCodeSnapshot = code;
        this.lastChangeTime = Date.now();
    }

    // 檢測是否需要觸發衝突警告
    shouldTriggerConflictWarning(oldCode, newCode, otherUsers) {
        if (!this.isMainEditor || !otherUsers || otherUsers.length === 0) {
            return false;
        }

        const changeInfo = this.detectChangeType(oldCode, newCode);
        
        // 觸發條件：
        // 1. 大量修改 (高嚴重性)
        // 2. 貼上操作
        // 3. 導入操作
        // 4. 大量刪除
        return changeInfo.severity === 'high' || 
               ['paste', 'import', 'mass_delete'].includes(changeInfo.type);
    }

    // 處理接收到的衝突相關消息
    handleConflictMessage(message) {
        switch (message.type) {
            case 'voting_request':
                this.showVotingRequest(message);
                break;
            case 'vote_result':
                this.handleVoteResult(message);
                break;
            case 'voting_cancelled':
                this.handleVotingCancelled(message);
                break;
            case 'force_code_change':
                this.handleForceChange(message);
                break;
            case 'voted_change_applied':
                this.handleVotedChangeApplied(message);
                break;
        }
    }

    // 顯示投票請求
    showVotingRequest(message) {
        const modalHtml = `
            <div class="modal fade" id="voteRequestModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-vote-yea"></i> 代碼修改投票
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-user"></i> ${message.requested_by} 請求修改代碼</h6>
                                <p><strong>修改描述:</strong> ${message.change_description}</p>
                            </div>
                            <p>請投票決定是否同意這個修改：</p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-success" onclick="window.AdvancedConflictDetector.vote('${message.voting_id}', 'agree')">
                                <i class="fas fa-check"></i> 同意
                            </button>
                            <button class="btn btn-danger" onclick="window.AdvancedConflictDetector.vote('${message.voting_id}', 'disagree')">
                                <i class="fas fa-times"></i> 反對
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('voteRequestModal'));
        modal.show();
    }

    // 投票
    vote(votingId, voteChoice) {
        console.log(`🗳️ 投票: ${voteChoice} for ${votingId}`);
        
        // 發送投票結果
        if (window.wsManager && window.wsManager.isConnected) {
            window.wsManager.sendMessage({
                type: 'vote_result',
                voting_id: votingId,
                vote: voteChoice,
                user_id: window.wsManager.currentUser
            });
        }

        // 關閉投票模態框
        const modal = document.getElementById('voteRequestModal');
        if (modal) {
            bootstrap.Modal.getInstance(modal).hide();
            modal.remove();
        }

        this.showToast(`已投票: ${voteChoice === 'agree' ? '同意' : '反對'}`, 'info');
    }

    // 處理投票取消
    handleVotingCancelled(message) {
        const modal = document.getElementById('voteRequestModal');
        if (modal) {
            bootstrap.Modal.getInstance(modal).hide();
            modal.remove();
        }
        this.showToast('投票已被取消', 'info');
    }

    // 處理強制修改
    handleForceChange(message) {
        this.showToast(`${message.forced_by} 強制應用了修改`, 'warning');
        if (window.Chat) {
            window.Chat.addSystemMessage(`⚠️ ${message.forced_by} 強制應用了修改`);
        }
    }

    // 處理投票通過的修改
    handleVotedChangeApplied(message) {
        this.showToast('投票通過，代碼修改已應用', 'success');
        if (window.Chat) {
            window.Chat.addSystemMessage('✅ 投票通過，代碼修改已應用');
        }
    }
}

// 創建全局實例
window.AdvancedConflictDetector = new AdvancedConflictDetector();

// 在頁面加載完成後初始化
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 AdvancedConflictDetector 已準備就緒');
}); 