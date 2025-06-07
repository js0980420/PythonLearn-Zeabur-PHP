# PythonLearn 協作開發工作流程指南

## 🚀 Git 分支策略

### 主分支結構
```
main (生產環境)
├── develop (開發主分支)
├── feature/backend-api (主機專用)
├── feature/frontend-ui (筆電專用)
└── feature/websocket-server (主機專用)
```

## 📝 分工明細

### 主機負責 (Backend Focus)
- **分支**: `feature/backend-api`
- **文件範圍**:
  - `classes/Database.php`
  - `websocket/server.php` 
  - `api/` 目錄下所有文件
  - 數據庫遷移文件

### 筆電負責 (Frontend Focus)  
- **分支**: `feature/frontend-ui`
- **文件範圍**:
  - `public/js/save-load.js`
  - `public/js/editor.js`
  - `public/index.html` (UI部分)
  - `public/css/` 目錄下所有文件

## 🔄 同步工作流程

### 初始設置 (筆電端)
```bash
# 1. 克隆倉庫
git clone <repository-url>
cd PythonLearn-Zeabur-PHP

# 2. 創建並切換到前端分支
git checkout -b feature/frontend-ui

# 3. 設置上游分支
git push -u origin feature/frontend-ui
```

### 日常工作流程

#### 主機工作流程
```bash
# 每次開始工作前
git checkout feature/backend-api
git pull origin develop
git merge develop

# 完成工作後
git add .
git commit -m "feat: 改進Database類的saveCode方法"
git push origin feature/backend-api
```

#### 筆電工作流程  
```bash
# 每次開始工作前
git checkout feature/frontend-ui
git pull origin develop
git merge develop

# 完成工作後
git add .
git commit -m "feat: 實現保存按鈕下拉選單UI"
git push origin feature/frontend-ui
```

### 整合流程
```bash
# 1. 主機先將後端改動合併到develop
git checkout develop
git merge feature/backend-api
git push origin develop

# 2. 筆電同步develop分支
git checkout feature/frontend-ui
git pull origin develop
git merge develop

# 3. 筆電將前端改動合併到develop
git checkout develop
git merge feature/frontend-ui
git push origin develop
```

## ⚠️ 衝突避免策略

### 文件分工原則
- **絕對不要同時編輯**:
  - `public/index.html` (協調好誰負責哪部分)
  - `config/` 配置文件

- **主機專屬文件**:
  - `classes/Database.php`
  - `websocket/server.php`
  - 所有PHP後端文件

- **筆電專屬文件**:
  - `public/js/save-load.js`
  - `public/js/editor.js`
  - CSS樣式文件

### 溝通協調
- **每日同步**: 固定時間(如早上10點)進行代碼同步
- **功能接口**: 提前定義好前後端接口格式
- **測試協調**: 一方完成功能後通知另一方測試

## 🛠️ 開發環境同步

### 筆電環境設置
```bash
# 1. 安裝PHP 8.x
# 2. 安裝MySQL
# 3. 配置相同的數據庫
# 4. 確保端口不衝突
```

### 配置文件同步
- 使用相同的數據庫配置
- WebSocket端口保持一致(8081)
- 開發服務器端口錯開(主機8080, 筆電8085)

## 📋 當前具體任務分配

### 主機立即任務
1. **修復Database saveCode方法**
   - 解決version_number字段問題
   - 確保5槽位系統正常工作

2. **WebSocket服務器優化**
   - 修復Database::insert()錯誤
   - 改進錯誤處理機制

### 筆電立即任務
1. **實現保存按鈕下拉選單**
   - 在index.html中添加dropdown結構
   - 修改save-load.js添加UI邏輯

2. **優化槽位選擇界面**
   - 美化對話框設計
   - 改進用戶體驗

## 🔧 測試協調

### 測試方法
- **主機測試**: 專注後端API和數據庫操作
- **筆電測試**: 專注前端交互和UI響應
- **集成測試**: 每日合併後進行完整功能測試

### 測試數據
- 使用相同的測試數據集
- 測試房間統一命名: `test-room-dev` 