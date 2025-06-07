# PythonLearn 垂直切片部署計劃

## 🚀 **第一波上船：緊急修復** (立即執行)

### **主機任務** (Backend Critical - 1小時內完成)
```bash
# 分支: hotfix/database-critical
git checkout -b hotfix/database-critical
```

**修復清單：**
1. ❌ **Database::insert()方法缺失** 
   - 文件：`classes/Database.php` (第1243行)
   - 錯誤：WebSocket服務器調用不存在的方法
   
2. ❌ **version_number字段錯誤**
   - 問題：`code_history`表沒有`version_number`字段
   - 修復：移除所有對此字段的引用

3. ❌ **change_type枚舉值錯誤**
   - 錯誤：`Data truncated for column 'change_type'`
   - 修復：調整枚舉值定義

**完成標準：**
- ✅ WebSocket服務器可以正常啟動
- ✅ 保存功能不報錯
- ✅ 代碼變更記錄正常

---

## 🎯 **第二波上船：保存UI對齊** (第一波完成後)

### **筆電任務** (Frontend Focus - 2小時內完成)
```bash
# 分支: feature/save-dropdown-ui
git checkout develop
git pull origin develop
git checkout -b feature/save-dropdown-ui
```

**開發清單：**
1. 🎨 **保存按鈕下拉選單**
   - 文件：`public/index.html`
   - 功能：複製載入按鈕的下拉結構
   
2. 🎨 **槽位選擇界面**
   - 文件：`public/js/save-load.js`
   - 功能：實現保存槽位選擇對話框
   
3. 🎨 **按鈕交互邏輯**
   - 文件：`public/js/editor.js`
   - 功能：保存按鈕對應載入按鈕的行為

**完成標準：**
- ✅ 保存按鈕有下拉選單（槽位1-4）
- ✅ 保存按鈕直接保存到槽位0
- ✅ 下拉選單對應載入下拉選單

---

## 🌊 **第三波上船：核心功能整合** (前兩波完成後)

### **主機任務** (Backend Integration - 4小時)
```bash
# 分支: feature/websocket-optimization
git checkout develop
git pull origin develop  
git checkout -b feature/websocket-optimization
```

**開發清單：**
1. 🔧 **WebSocket消息優化**
   - 衝突檢測改進
   - AI助教接口準備
   - 聊天室基礎結構

2. 🔧 **數據庫性能優化**
   - 索引優化
   - 查詢效率提升
   - 連接池管理

### **筆電任務** (Frontend Polish - 4小時)
```bash
# 分支: feature/ui-polish
git checkout develop
git pull origin develop
git checkout -b feature/ui-polish
```

**開發清單：**
1. 🎨 **界面美化**
   - 響應式設計改進
   - 動畫效果添加
   - 用戶體驗優化

2. 🎨 **錯誤處理UI**
   - 友好的錯誤提示
   - 加載狀態顯示
   - 操作反饋改進

---

## 📋 **Git工作流程**

### **立即執行命令** (主機)
```bash
# 1. 初始化Git倉庫
git init
git add .
git commit -m "Initial commit: PythonLearn 協作平台基礎版本"

# 2. 創建develop分支
git checkout -b develop

# 3. 立即修復數據庫錯誤
git checkout -b hotfix/database-critical

# 修復完成後
git add classes/Database.php websocket/server.php
git commit -m "🔥 緊急修復: Database::insert()方法缺失和version_number字段錯誤"
git push -u origin hotfix/database-critical
```

### **筆電同步命令**
```bash
# 1. 克隆倉庫
git clone <repository-url>
cd PythonLearn-Zeabur-PHP

# 2. 等待第一波修復完成
git checkout develop
git pull origin develop

# 3. 開始前端開發
git checkout -b feature/save-dropdown-ui

# 完成後
git add public/
git commit -m "✨ 新增: 保存按鈕下拉選單，對齊載入功能"
git push -u origin feature/save-dropdown-ui
```

---

## ⏰ **時間線規劃**

### **Day 1 (今天)**
- **0-1小時**: 主機修復數據庫錯誤 → 上船
- **1-3小時**: 筆電開發保存UI → 上船  
- **3-4小時**: 測試整合，準備第三波

### **Day 2**
- **0-4小時**: 並行開發第三波功能
- **4-6小時**: 整合測試
- **6-8小時**: 部署準備

### **Day 3**
- **AI助教整合**
- **聊天室功能**
- **教師監控面板**

---

## 🔧 **接口定義** (避免衝突)

### **後端提供** (主機負責)
```php
// Database.php 必須實現的方法
public function insert($table, $data) // 緊急
public function saveCode($roomId, $userId, $code, $saveName = null, $slotId = null)
public function getCodeHistory($roomId, $limit = 5)
```

### **前端調用** (筆電負責)
```javascript
// save-load.js 必須實現的方法
displaySaveSlotDialog()      // 顯示槽位選擇
selectSaveSlot(slotId)       // 選擇槽位保存
showSaveDropdown()           // 顯示保存下拉選單
```

---

## 🎯 **成功標準**

### **第一波完成標準**
- ✅ WebSocket服務器無錯誤啟動
- ✅ 保存代碼功能正常
- ✅ 載入歷史記錄正常

### **第二波完成標準**  
- ✅ 保存按鈕有下拉選單
- ✅ 保存邏輯與載入邏輯對應
- ✅ 5槽位系統完整運作

### **第三波完成標準**
- ✅ 多用戶協作穩定
- ✅ UI美觀易用
- ✅ 性能優化完成

---

## 🚢 **上船檢查清單**

每次提交前確認：
- [ ] 功能完整測試通過
- [ ] 代碼格式整潔
- [ ] 提交信息清晰
- [ ] 無衝突文件
- [ ] 接口文檔更新 