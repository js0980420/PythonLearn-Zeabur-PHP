// 衝突檢測和解決管理
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
        // 延遲初始化，確保DOM已載入
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initializeModal();
            });
        } else {
            this.initializeModal();
        }
        console.log('✅ ConflictResolver initialized.');
    }

    // 初始化模態框元素
    initializeModal() {
        this.modalElement = document.getElementById('conflictModal');
        if (!this.modalElement) {
            console.error('❌ Conflict modal element #conflictModal not found during initialization!');
            // 嘗試創建基本的模態框結構
            this.createFallbackModal();
        } else {
            console.log('✅ ConflictResolver modal element found');
        }
    }

    // 創建備用模態框
    createFallbackModal() {
        console.log('🔧 創建備用衝突模態框...');
        const modalHtml = `
            <div class="modal fade" id="conflictModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">⚠️ 協作衝突</h5>
                        </div>
                        <div class="modal-body">
                            <p>檢測到代碼衝突，請選擇解決方案：</p>
                            <div id="diffSummary" class="alert alert-info"></div>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>您的版本</h6>
                                    <pre id="myCodeVersion" class="bg-light p-2" style="max-height: 200px; overflow-y: auto;"></pre>
                                </div>
                                <div class="col-md-6">
                                    <h6><span id="otherUserName">對方</span>的版本</h6>
                                    <pre id="otherCodeVersion" class="bg-light p-2" style="max-height: 200px; overflow-y: auto;"></pre>
                                </div>
                            </div>
                            <div id="conflictAIAnalysis" style="display: none;">
                                <h6>AI分析</h6>
                                <div id="aiAnalysisContent"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                                            <button type="button" class="btn btn-success" onclick="window.ConflictResolver.resolveConflict('accept')">接受對方修改</button>
                <button type="button" class="btn btn-secondary" onclick="window.ConflictResolver.resolveConflict('reject')">保持我的版本</button>
                            <button type="button" class="btn btn-info" onclick="window.ConflictResolver.requestAIAnalysis()">AI協助</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        this.modalElement = document.getElementById('conflictModal');
        console.log('✅ 備用模態框已創建');
    }

    // 顯示衝突解決模態框
    showConflict(message) {
        try {
            console.log('🚨 顯示協作衝突模態框', message);
            
            // 更新衝突用戶名稱顯示
            const conflictUserSpan = document.getElementById('conflictUserName');
            const otherUserSpan = document.getElementById('otherUserName');
            if (conflictUserSpan && message.userName) {
                conflictUserSpan.textContent = message.userName;
            }
            if (otherUserSpan && message.userName) {
                otherUserSpan.textContent = message.userName;
            }
            
            // 獲取代碼並分析差異
            const myCode = Editor.editor ? Editor.editor.getValue() : '';
            const otherCode = message.code || '';
            
            // 顯示代碼差異
            this.displayCodeDifference(myCode, otherCode, message.userName || '其他同學');
            
            // 存儲當前衝突信息，用於AI分析
            this.currentConflict = {
                userCode: myCode,
                serverCode: otherCode,
                userVersion: Editor.codeVersion || 0,
                serverVersion: message.version || 0,
                conflictUser: message.userName || '其他同學',
                roomId: wsManager.currentRoom || 'unknown',
                code: otherCode,
                userName: message.userName,
                version: message.version
            };
            
            // 隱藏AI分析區域
            const aiAnalysis = document.getElementById('conflictAIAnalysis');
            if (aiAnalysis) {
                aiAnalysis.style.display = 'none';
            }
            
            // 顯示模態框
            const modal = document.getElementById('conflictModal');
            if (modal) {
                const bsModal = new bootstrap.Modal(modal, { backdrop: 'static' });
                bsModal.show();
                console.log('✅ 協作衝突模態框已顯示');
            } else {
                console.error('❌ 找不到衝突模態框元素');
                alert(`協作衝突！${message.userName || '其他同學'}也在修改程式碼。請重新載入頁面獲取最新版本。`);
            }
        } catch (error) {
            console.error('❌ 顯示衝突模態框時發生錯誤:', error);
            alert(`協作衝突！${message.userName || '其他同學'}也在修改程式碼。請重新載入頁面。`);
        }
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

        // 進行簡單的本地差異分析
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
            added: [],
            removed: [],
            modified: []
        };

        if (analysis.isSame) {
            return analysis;
        }

        // 簡單的行級比較
        const maxLines = Math.max(lines1.length, lines2.length);
        for (let i = 0; i < maxLines; i++) {
            const line1 = (lines1[i] || '').trim();
            const line2 = (lines2[i] || '').trim();
            
            if (line1 !== line2) {
                if (!line1 && line2) {
                    analysis.added.push(i);
                } else if (line1 && !line2) {
                    analysis.removed.push(i);
                } else if (line1 && line2) {
                    analysis.modified.push(i);
                }
            }
        }

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
        
        if (analysis.isSame) {
            summaryText = '🟢 代碼內容相同，可能是編輯時序問題';
        } else {
            const changes = [];
            if (analysis.added.length > 0) changes.push(`新增 ${analysis.added.length} 行`);
            if (analysis.removed.length > 0) changes.push(`刪除 ${analysis.removed.length} 行`);
            if (analysis.modified.length > 0) changes.push(`修改 ${analysis.modified.length} 行`);
            
            summaryText = `🟡 差異: ${changes.join(', ')} | 您: ${analysis.myLines} 行 vs ${otherUserName}: ${analysis.otherLines} 行`;
        }

        summaryElement.textContent = summaryText;
        console.log('📊 差異摘要已更新:', summaryText);
    }

    // 解決衝突
    resolveConflict(choice) {
        console.log('✅ [ConflictResolver] 用戶選擇解決方案:', choice);
        
        if (!this.currentConflict) {
            console.error('❌ 沒有當前衝突數據');
            return;
        }
        
        const conflictData = this.currentConflict;
        let resolution;
        let message;
        
        if (choice === 'accept') {
            // 接受對方修改
            if (window.Editor && Editor.applyRemoteCode) {
                // 正確格式：傳入包含code和version的對象
                Editor.applyRemoteCode({
                    code: conflictData.serverCode || '',
                    version: conflictData.serverVersion || 0,
                    userName: conflictData.conflictUser || '其他用戶'
                });
            } else if (window.Editor && Editor.editor) {
                // 直接設置編輯器內容
                Editor.editor.setValue(conflictData.serverCode || '');
                if (Editor.codeVersion !== undefined) {
                    Editor.codeVersion = conflictData.serverVersion || 0;
                }
                if (Editor.updateVersionDisplay) {
                    Editor.updateVersionDisplay();
                }
            }
            console.log('✅ 選擇接受對方修改解決衝突');
            resolution = 'accepted';
            message = '已接受對方修改';
        } else if (choice === 'reject') {
            // 拒絕對方修改，保持自己的版本
            console.log('✅ 選擇拒絕對方修改解決衝突');
            resolution = 'rejected';
            message = '已拒絕對方修改，保持我的版本';
        } else {
            console.error('❌ 未知的解決方案選擇:', choice);
            return;
        }
        
        // 記錄衝突歷史
        if (this.lastAIAnalysis) {
            this.saveConflictToHistory(conflictData, resolution, this.lastAIAnalysis);
        } else {
            this.saveConflictToHistory(conflictData, resolution);
        }
        
        // 關閉模態框
        this.hideConflictModal();
        
        // 通知成功
        if (window.showToast) {
            window.showToast(message, 'success');
        } else if (window.UI && window.UI.showSuccessToast) {
            window.UI.showSuccessToast(message);
        } else {
            alert(message);
        }
        
        // 清理衝突狀態
        this.currentConflict = null;
        this.lastAIAnalysis = null;
        
        if (window.Editor && Editor.resetEditingState) {
            Editor.resetEditingState();
        }
    }

    // 隱藏衝突模態框
    hideConflictModal() {
        const modal = document.getElementById('conflictModal');
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }
    }

    // AI分析請求
    requestAIAnalysis() {
        console.log('🤖 用戶主動請求AI協助分析衝突...');
        
        if (!this.currentConflict && !this.conflictData) {
            console.warn('❌ 無存儲的衝突數據，無法進行AI分析');
            if (window.UI && window.UI.showErrorToast) {
                window.UI.showErrorToast('沒有衝突數據，無法進行AI分析');
            }
            return;
        }
        
        const conflictInfo = this.conflictData || this.currentConflict;
        const userCode = conflictInfo.localCode || conflictInfo.userCode || '';
        const serverCode = conflictInfo.remoteCode || conflictInfo.serverCode || '';
        const conflictUser = conflictInfo.remoteUserName || conflictInfo.conflictUser || '其他同學';
        
        console.log('📊 準備AI分析的數據:');
        console.log(`   - 用戶代碼長度: ${userCode.length} 字符`);
        console.log(`   - 衝突用戶代碼長度: ${serverCode.length} 字符`);
        console.log(`   - 衝突用戶: ${conflictUser}`);
        
        // 顯示AI分析區域並設置載入狀態
        const aiAnalysis = document.getElementById('conflictAIAnalysis');
        const aiContent = document.getElementById('aiAnalysisContent');
        
        if (aiAnalysis && aiContent) {
            aiAnalysis.style.display = 'block';
            aiContent.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">載入中...</span>
                    </div>
                    <h6 class="mt-2 mb-0"><i class="fas fa-robot me-2"></i>AI 正在分析協作衝突...</h6>
                </div>
            `;
        }
        
        // 驗證數據完整性
        if (!userCode && !serverCode) {
            console.error('❌ AI請求數據驗證失敗: 用戶代碼和衝突代碼都為空');
            this.displayAIAnalysisError('無法分析：代碼內容為空');
            return;
        }

        // 準備發送給AI的數據 - 格式化為衝突分析提示
        const conflictAnalysisPrompt = `請分析以下協作編程中的代碼衝突，並提供解決建議：

【我的代碼版本】：
\`\`\`python
${userCode || '（空）'}
\`\`\`

【${conflictUser}的代碼版本】：
\`\`\`python
${serverCode || '（空）'}
\`\`\`

請提供：
1. 主要差異分析
2. 哪個版本更好的建議
3. 如何合併的具體步驟
4. 注意事項`;

        // 直接調用 AI API
        this.sendAIRequest(conflictAnalysisPrompt);
    }
    
    // 發送 AI 請求到 AI API 端點
    async sendAIRequest(prompt) {
        try {
            console.log('📤 直接調用 AI API...');
            
            const response = await fetch('/api/ai.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'analyze',
                    message: prompt,
                    context: 'conflict_analysis'
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP錯誤: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('✅ AI API 回應:', data);

            if (data.success && data.response) {
                this.lastAIAnalysis = data.response;
                this.displayAIAnalysis(data.response);
            } else {
                throw new Error(data.error || 'AI分析失敗');
            }
            
        } catch (error) {
            console.error('❌ AI API 調用失敗:', error);
            this.displayAIAnalysisError('AI分析失敗: ' + error.message);
        }
    }

    // 顯示AI分析載入狀態
    displayAIAnalysisLoading() {
        const aiAnalysis = document.getElementById('conflictAIAnalysis');
        const aiContent = document.getElementById('aiAnalysisContent');
        
        if (!aiAnalysis || !aiContent) {
            console.warn('⚠️ AI分析顯示區域未找到');
            return;
        }
        
        aiAnalysis.style.display = 'block';
        aiContent.innerHTML = `
            <div class="alert alert-info mb-0">
                <h6><i class="fas fa-robot"></i> AI正在分析衝突...</h6>
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">載入中...</span>
                    </div>
                    <span>請稍候，AI助教正在分析代碼差異並提供建議...</span>
                </div>
            </div>
        `;
    }

    // 顯示AI分析錯誤
    displayAIAnalysisError(errorMessage) {
        const aiContent = document.getElementById('aiAnalysisContent');
        if (!aiContent) return;

        aiContent.innerHTML = `
            <div class="alert alert-warning mb-0">
                <h6><i class="fas fa-exclamation-triangle"></i> AI分析失敗</h6>
                <p class="mb-2">${errorMessage}</p>
                <div class="small">
                    <strong>💡 手動解決建議：</strong><br>
                    • 仔細比較上方的代碼差異<br>
                    • 在聊天室與同學討論<br>
                    • 選擇功能更完整或更正確的版本
                </div>
            </div>
        `;
    }

    // 顯示AI分析結果
    displayAIAnalysis(analysisText) {
        console.log('🤖 [ConflictResolver] 顯示AI分析結果');
        
        const aiAnalysis = document.getElementById('conflictAIAnalysis');
        const aiContent = document.getElementById('aiAnalysisContent');
        
        if (!aiAnalysis || !aiContent) {
            console.error('❌ AI分析顯示區域未找到');
            return;
        }
        
        aiAnalysis.style.display = 'block';
        
        if (analysisText && analysisText.trim()) {
            const formattedAnalysis = this.formatAIAnalysisForUI(analysisText);
            aiContent.innerHTML = `
                ${formattedAnalysis}
                <div class="mt-3 text-center">
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="window.ConflictResolver.shareAIAnalysisToChat()">
                        <i class="fas fa-share"></i> 複製到聊天室
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.ConflictResolver.hideAIAnalysis()">
                        <i class="fas fa-times"></i> 關閉
                    </button>
                </div>
            `;
            console.log('✅ AI分析結果已成功顯示在UI中');
        } else {
            aiContent.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> AI分析失敗或回應為空
                </div>
            `;
            console.warn('⚠️ AI分析結果為空');
        }
    }

    // 格式化AI分析結果
    formatAIAnalysisForUI(analysisText) {
        if (!analysisText) return '';
        
        let formatted = analysisText
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code class="bg-light px-1 rounded">$1</code>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');
        
        if (!formatted.startsWith('<p>')) {
            formatted = '<p>' + formatted;
        }
        if (!formatted.endsWith('</p>')) {
            formatted = formatted + '</p>';
        }

        return formatted;
    }

    // 分享AI分析結果到聊天室
    shareAIAnalysisToChat() {
        if (!this.lastAIAnalysis) {
            console.warn('❌ 沒有AI分析結果可以分享');
            if (window.UI && window.UI.showErrorToast) {
                window.UI.showErrorToast('沒有AI分析結果可以分享');
            }
            return;
        }

        // 檢查聊天功能是否可用
        if (!window.Chat || typeof window.Chat.sendAIResponseToChat !== 'function') {
            console.error('❌ 聊天功能不可用');
            if (window.UI && window.UI.showErrorToast) {
                window.UI.showErrorToast('聊天功能不可用，無法分享');
            }
            return;
        }

        // 格式化分析結果用於聊天室
        const formattedMessage = `🔧 衝突分析結果：\n${this.lastAIAnalysis}`;
        
        try {
            window.Chat.sendAIResponseToChat(formattedMessage);
            console.log('✅ AI衝突分析結果已分享到聊天室');
            
            if (window.UI && window.UI.showSuccessToast) {
                window.UI.showSuccessToast('AI分析結果已分享到聊天室');
            }
        } catch (error) {
            console.error('❌ 分享AI分析結果失敗:', error);
            if (window.UI && window.UI.showErrorToast) {
                window.UI.showErrorToast('分享失敗: ' + error.message);
            }
        }
    }

    // 隱藏AI分析區域
    hideAIAnalysis() {
        const aiAnalysis = document.getElementById('conflictAIAnalysis');
        if (aiAnalysis) {
            aiAnalysis.style.display = 'none';
        }
    }

    // 保存衝突到歷史記錄
    saveConflictToHistory(conflictData, resolution = null, aiAnalysis = null) {
        let conflictHistory = JSON.parse(localStorage.getItem('conflict_history') || '[]');
        
        const historyEntry = {
            id: Date.now(),
            timestamp: new Date().toISOString(),
            userCode: conflictData.userCode || conflictData.localCode || '',
            serverCode: conflictData.serverCode || conflictData.remoteCode || '',
            conflictUser: conflictData.conflictUser || conflictData.remoteUserName || '其他同學',
            resolution: resolution,
            aiAnalysis: aiAnalysis
        };
        
        conflictHistory.unshift(historyEntry);
        
        // 只保留最近50條記錄
        if (conflictHistory.length > 50) {
            conflictHistory = conflictHistory.slice(0, 50);
        }
        
        localStorage.setItem('conflict_history', JSON.stringify(conflictHistory));
        console.log('✅ 衝突已保存到歷史記錄');
    }

    // 測試衝突分析功能
    testConflictAnalysis() {
        console.log('🧪 開始測試衝突分析功能...');
        
        // 創建測試衝突數據
        const testConflictData = {
            localCode: `# 我的代碼版本
def calculate_sum(numbers):
    """計算數字列表的總和"""
    total = 0
    for num in numbers:
        total += num
    return total

# 測試
result = calculate_sum([1, 2, 3, 4, 5])
print(f"總和: {result}")`,
            remoteCode: `# 其他同學的代碼版本
def calculate_sum(numbers):
    """計算數字列表的總和，並處理錯誤"""
    if not numbers:
        return 0
    
    total = sum(numbers)
    return total

# 測試
nums = [1, 2, 3, 4, 5]
result = calculate_sum(nums)
print(f"計算結果: {result}")
print(f"數字個數: {len(nums)}")`,
            remoteUserName: '同學小明',
            localVersion: 1,
            remoteVersion: 2
        };
        
        // 設置測試衝突數據
        this.conflictData = testConflictData;
        this.currentConflict = testConflictData;
        
        // 觸發 AI 分析
        console.log('🤖 觸發 AI 衝突分析...');
        this.requestAIAnalysis();
        
        // 顯示測試提示
        if (window.UI && window.UI.showInfoToast) {
            window.UI.showInfoToast('正在測試 AI 衝突分析功能，請稍候...');
        }
    }
}

// 創建全局實例
const ConflictResolver = new ConflictResolverManager();
window.ConflictResolver = ConflictResolver;

// 全局函數（向後兼容）
function resolveConflict(solution) {
    ConflictResolver.resolveConflict(solution);
}

function askAIForConflictHelp() {
    ConflictResolver.requestAIAnalysis();
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    ConflictResolver.initialize();
});

console.log('✅ ConflictResolver 模組已載入'); 