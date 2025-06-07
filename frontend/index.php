<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python教學多人協作平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
        }
    </style>
</head>
<body>
    <!-- 導航欄 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-code"></i> Python協作平台
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">功能特色</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">使用方式</a>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-outline-light ms-2" onclick="showLoginModal()">開始使用</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 主要區域 -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Python教學多人協作平台</h1>
            <p class="lead mb-5">實時協作編程，AI智能助教，讓Python學習更高效</p>
            <button class="btn btn-primary btn-lg px-5 py-3" onclick="showLoginModal()">
                <i class="fas fa-rocket"></i> 立即開始
            </button>
        </div>
    </section>

    <!-- 功能特色 -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">功能特色</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">多人實時協作</h5>
                            <p class="card-text">支援多人同時編輯代碼，實時同步，智能衝突檢測與解決</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-robot fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">AI智能助教</h5>
                            <p class="card-text">五大AI功能：代碼解釋、錯誤檢查、改進建議、衝突分析、問題解答</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-play-circle fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">代碼執行</h5>
                            <p class="card-text">在線運行Python代碼，即時查看結果，支援代碼保存與歷史記錄</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">衝突檢測</h5>
                            <p class="card-text">智能檢測代碼衝突，提供四種解決方案：同意、拒絕、分享、AI分析</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-eye fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">教師監控</h5>
                            <p class="card-text">教師可實時監控學生學習進度，查看代碼變更歷史</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-cloud fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">無痕支援</h5>
                            <p class="card-text">支援無痕瀏覽器使用，無需複雜註冊，簡單快速開始</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 使用方式 -->
    <section id="how-it-works" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">使用方式</h2>
            <div class="row g-4">
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-plus fa-3x text-primary"></i>
                    </div>
                    <h5>1. 輸入用戶名</h5>
                    <p>簡單輸入用戶名即可開始</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-door-open fa-3x text-primary"></i>
                    </div>
                    <h5>2. 創建或加入房間</h5>
                    <p>創建新房間或加入現有房間</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-code fa-3x text-primary"></i>
                    </div>
                    <h5>3. 開始編程</h5>
                    <p>與他人實時協作編寫Python代碼</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mb-3">
                        <i class="fas fa-magic fa-3x text-primary"></i>
                    </div>
                    <h5>4. AI助教協助</h5>
                    <p>使用AI助教獲得即時幫助</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 登入模態框 -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">開始使用</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">用戶名</label>
                            <input type="text" class="form-control" id="username" required
                                   placeholder="請輸入用戶名（2-20個字符）">
                            <div class="form-text">支援中文、英文、數字和下劃線</div>
                        </div>
                        <div class="mb-3">
                            <label for="userType" class="form-label">用戶類型</label>
                            <select class="form-select" id="userType">
                                <option value="student">學生</option>
                                <option value="teacher">教師</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" form="loginForm" class="btn btn-primary">開始使用</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 頁腳 -->
    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            <p>&copy; 2024 Python教學多人協作平台. 使用PHP + WebSocket + AI技術構建</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showLoginModal() {
            const modal = new bootstrap.Modal(document.getElementById('loginModal'));
            modal.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 清除所有登入相關的localStorage
            localStorage.removeItem('userId');
            localStorage.removeItem('username');
            localStorage.removeItem('userType');
            localStorage.removeItem('user_info');
            
            const urlParams = new URLSearchParams(window.location.search);
            const redirectRoom = urlParams.get('room_id_join');
            
            showLoginModal();
            
            if (redirectRoom) {
                const modalTitle = document.querySelector('#loginModal .modal-title');
                if (modalTitle) {
                    modalTitle.textContent = '請輸入用戶名稱以加入房間 ' + redirectRoom;
                }
            }
        });

        document.getElementById('loginForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const usernameInput = document.getElementById('username');
            const username = usernameInput.value.trim();
            const userType = document.getElementById('userType').value;

            if (!username) {
                alert('請輸入用戶名');
                usernameInput.focus();
                return;
            }
            if (username.length < 2 || username.length > 20) {
                alert('用戶名長度必須在2-20個字符之間');
                usernameInput.focus();
                return;
            }

            try {
                // 調用後端auth API
                const response = await fetch('/backend/api/auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'login',
                        username: username,
                        user_type: userType
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    // 設置localStorage
                    const userInfo = {
                        user_id: result.data.user_id,
                        username: result.data.username,
                        user_type: result.data.user_type,
                        session_id: result.data.session_id
                    };
                    
                    localStorage.setItem('user_info', JSON.stringify(userInfo));
                    localStorage.setItem('userId', result.data.user_id);
                    localStorage.setItem('username', result.data.username);
                    localStorage.setItem('userType', result.data.user_type);
                    
                    alert(`登入成功！用戶ID: ${result.data.user_id}, 用戶名: ${result.data.username}`);

                    // 關閉模態框
                    const loginModalElement = document.getElementById('loginModal');
                    if (loginModalElement) {
                        const modal = bootstrap.Modal.getInstance(loginModalElement);
                        if (modal) {
                            modal.hide();
                        }
                    }

                    // 跳轉頁面
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('room_id_join')) {
                        window.location.href = `editor.php?room_id=${urlParams.get('room_id_join')}`;
                    } else {
                        window.location.href = 'rooms.php';
                    }
                } else {
                    alert('登入失敗: ' + result.message);
                }
            } catch (error) {
                console.error('登入錯誤:', error);
                alert('網絡錯誤，請稍後重試');
            }
        });

        function generateRandomId(length) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }
    </script>
</body>
</html> 