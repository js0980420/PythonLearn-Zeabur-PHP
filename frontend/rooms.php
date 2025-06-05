<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>房間列表 - Python協作平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .room-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .room-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .user-info {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
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
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-code"></i> Python協作平台
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <span id="currentUsername">用戶</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="logout()">
                            <i class="fas fa-sign-out-alt"></i> 登出
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- 用戶信息卡片 -->
        <div class="card user-info mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="card-title mb-1">
                            <i class="fas fa-user-circle"></i> 歡迎，<span id="welcomeUsername">用戶</span>
                        </h5>
                        <p class="card-text mb-0">
                            <i class="fas fa-tag"></i> 身份：<span id="userType">學生</span>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-light" onclick="showCreateRoomModal()">
                            <i class="fas fa-plus"></i> 創建房間
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 房間列表 -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-door-open"></i> 可用房間</h4>
                    <button class="btn btn-outline-primary" onclick="loadRooms()">
                        <i class="fas fa-sync-alt"></i> 刷新
                    </button>
                </div>
                
                <div id="roomsList" class="row g-3">
                    <!-- 房間卡片將在這裡動態載入 -->
                </div>
                
                <div id="noRooms" class="text-center py-5" style="display: none;">
                    <i class="fas fa-door-closed fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">暫無可用房間</h5>
                    <p class="text-muted">點擊上方「創建房間」按鈕來創建第一個房間</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 創建房間模態框 -->
    <div class="modal fade" id="createRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">創建新房間</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createRoomForm">
                        <div class="mb-3">
                            <label for="roomName" class="form-label">房間名稱</label>
                            <input type="text" class="form-control" id="roomName" required 
                                   placeholder="請輸入房間名稱">
                        </div>
                        <div class="mb-3">
                            <label for="roomDescription" class="form-label">房間描述</label>
                            <textarea class="form-control" id="roomDescription" rows="3" 
                                      placeholder="請輸入房間描述（可選）"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="maxUsers" class="form-label">最大人數</label>
                            <select class="form-select" id="maxUsers">
                                <option value="5">5人</option>
                                <option value="10" selected>10人</option>
                                <option value="15">15人</option>
                                <option value="20">20人</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="createRoom()">創建房間</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentUser = null;

        // 頁面載入時初始化
        document.addEventListener('DOMContentLoaded', function() {
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
                window.location.href = 'index.php';
                return;
            }
            
            // 檢查登入狀態
            const userInfo = localStorage.getItem('user_info');
            if (!userInfo) {
                // 重定向到首頁進行登入
                window.location.href = 'index.php';
                return;
            }

            currentUser = JSON.parse(userInfo);
            
            // 🎯 檢查如果是教師用戶，直接跳轉到教師監控後台
            if (currentUser.user_type === 'teacher') {
                console.log('檢測到教師用戶，跳轉到教師監控後台');
                window.location.href = '../teacher-dashboard.html';
                return;
            }
            
            // 更新用戶信息顯示（僅限學生用戶）
            document.getElementById('currentUsername').textContent = currentUser.username;
            document.getElementById('welcomeUsername').textContent = currentUser.username;
            document.getElementById('userType').textContent = currentUser.user_type === 'teacher' ? '教師' : '學生';

            // 載入房間列表（僅限學生用戶）
            loadRooms();
        });

        async function loadRooms() {
            try {
                const response = await fetch('/backend/api/rooms.php?action=list');
                const result = await response.json();

                if (result.success) {
                    displayRooms(result.data);
                } else {
                    console.error('載入房間失敗:', result.message);
                    alert('載入房間失敗: ' + result.message);
                }
            } catch (error) {
                console.error('載入房間錯誤:', error);
                alert('網絡錯誤，請稍後重試');
            }
        }

        function displayRooms(rooms) {
            const roomsList = document.getElementById('roomsList');
            const noRooms = document.getElementById('noRooms');

            if (rooms.length === 0) {
                roomsList.innerHTML = '';
                noRooms.style.display = 'block';
                return;
            }

            noRooms.style.display = 'none';
            
            roomsList.innerHTML = rooms.map(room => `
                <div class="col-md-6 col-lg-4">
                    <div class="card room-card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-door-open text-primary"></i> ${room.room_name}
                            </h5>
                            <p class="card-text text-muted">${room.description || '無描述'}</p>
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> 創建者：${room.creator_name || '未知'}
                                </small>
                            </div>
                            <div class="mb-3">
                                <span class="badge bg-info">
                                    <i class="fas fa-users"></i> ${room.current_users}/${room.max_users}
                                </span>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-clock"></i> ${formatDate(room.created_at)}
                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <button class="btn btn-primary w-100" 
                                    onclick="joinRoom(${room.id})"
                                    ${room.current_users >= room.max_users ? 'disabled' : ''}>
                                <i class="fas fa-sign-in-alt"></i> 
                                ${room.current_users >= room.max_users ? '房間已滿' : '加入房間'}
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function showCreateRoomModal() {
            const modal = new bootstrap.Modal(document.getElementById('createRoomModal'));
            modal.show();
        }

        async function createRoom() {
            const roomName = document.getElementById('roomName').value.trim();
            const description = document.getElementById('roomDescription').value.trim();
            const maxUsers = parseInt(document.getElementById('maxUsers').value);

            if (!roomName) {
                alert('請輸入房間名稱');
                return;
            }

            try {
                const response = await fetch('/backend/api/rooms.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'create',
                        room_name: roomName,
                        description: description,
                        max_users: maxUsers
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // 關閉模態框
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createRoomModal'));
                    modal.hide();
                    
                    // 清空表單
                    document.getElementById('createRoomForm').reset();
                    
                    // 跳轉到代碼編輯器頁面
                    window.location.href = `editor.php?room_id=${result.data.room_id}`;
                } else {
                    alert(result.message || '創建房間失敗');
                }
            } catch (error) {
                console.error('創建房間錯誤:', error);
                alert('網絡錯誤，請稍後重試');
            }
        }

        async function joinRoom(roomId) {
            try {
                const response = await fetch('/backend/api/rooms.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'join',
                        room_id: roomId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // 跳轉到代碼編輯器頁面
                    window.location.href = `editor.php?room_id=${roomId}`;
                } else {
                    alert(result.message || '加入房間失敗');
                }
            } catch (error) {
                console.error('加入房間錯誤:', error);
                alert('網絡錯誤，請稍後重試');
            }
        }

        async function logout() {
            try {
                await fetch('/backend/api/auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'logout'
                    })
                });
            } catch (error) {
                console.error('登出錯誤:', error);
            }

            // 清除本地存儲
            localStorage.removeItem('user_info');
            
            // 跳轉到首頁
            window.location.href = 'index.php';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) { // 1分鐘內
                return '剛剛';
            } else if (diff < 3600000) { // 1小時內
                return Math.floor(diff / 60000) + '分鐘前';
            } else if (diff < 86400000) { // 1天內
                return Math.floor(diff / 3600000) + '小時前';
            } else {
                return date.toLocaleDateString('zh-TW');
            }
        }

        // Enter鍵創建房間
        document.getElementById('roomName').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                createRoom();
            }
        });
    </script>
</body>
</html> 