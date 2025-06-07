// 測試用 AI助教模組
class TestAIAssistantManager {
    constructor() {
        this.currentResponse = '';
        this.responseContainer = document.getElementById('aiResponse');
        this.isProcessing = false;
        this.testBackendUrl = 'http://localhost:8082/test-ai-backend.php';
    }

    initialize() {
        console.log('✅ 測試 AI助教模組初始化完成');
        this.clearResponse();
    }

    clearResponse() {
        if (this.responseContainer) {
            this.responseContainer.innerHTML = `
                <div class="text-center text-muted p-4">
                    <i class="fas fa-robot fa-3x mb-3"></i>
                    <h6>🧪 測試 AI助教已準備就緒</h6>
                    <p class="mb-0">點擊下方按鈕開始使用 AI助教功能</p>
                </div>
            `;
        }
    }

    async requestAnalysis(action) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        const code = window.Editor ? window.Editor.getCode() : '';
        if (!code || code.trim() === '') {
            this.showResponse(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    請先輸入一些Python程式碼。
                </div>
            `);
            this.isProcessing = false;
            return;
        }

        this.showProcessing(action);

        let apiAction = '';
        switch(action) {
            case 'check_errors': apiAction = 'check_errors'; break;
            case 'analyze': apiAction = 'analyze'; break;
            case 'suggest': apiAction = 'suggest_improvements'; break;
            default: apiAction = 'explain';
        }

        try {
            const response = await fetch(this.testBackendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: apiAction, code: code })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showResponse(`
                    <div class="alert alert-success">
                        <h6>🧪 測試 AI助教分析結果</h6>
                        <div>${this.formatResponse(result.data.analysis)}</div>
                    </div>
                `);
            } else {
                this.showResponse(`
                    <div class="alert alert-danger">
                        錯誤: ${result.error}
                    </div>
                `);
            }
        } catch (error) {
            this.showResponse(`
                <div class="alert alert-danger">
                    連接錯誤: ${error.message}
                </div>
            `);
        } finally {
            this.isProcessing = false;
        }
    }

    showProcessing(action) {
        if (this.responseContainer) {
            this.responseContainer.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">🧪 測試 AI助教正在分析...</p>
                </div>
            `;
        }
    }

    formatResponse(text) {
        return text.replace(/\n/g, '<br>');
    }

    showResponse(content) {
        if (this.responseContainer) {
            this.responseContainer.innerHTML = content;
        }
    }
}

function testAskAI(action) {
    if (window.testAIManager) {
        window.testAIManager.requestAnalysis(action);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    window.testAIManager = new TestAIAssistantManager();
    window.testAIManager.initialize();
}); 