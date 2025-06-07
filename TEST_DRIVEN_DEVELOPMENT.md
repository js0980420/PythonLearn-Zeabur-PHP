# 🧪 測試驅動開發流程 - PythonLearn

## 📋 概述

為了確保未來新功能整合不會再出現舊問題，我們建立了一套完整的測試驅動開發流程。所有新功能都必須先在獨立測試環境中驗證，確定功能正常後才整合到完整服務器。

---

## 🏗️ 測試環境架構

### 📁 目錄結構
```
PythonLearn-Zeabur-PHP/
├── 📁 test-servers/              # 獨立測試服務器
│   ├── 📁 api-test/              # API測試服務器 (端口: 9081)
│   │   └── test_api_server.php
│   ├── 📁 websocket-test/        # WebSocket測試服務器 (端口: 9082)
│   │   └── test_websocket_server.php
│   ├── 📁 frontend-test/         # 前端測試環境 (端口: 9083)
│   │   └── test_complete_flow.html
│   └── 📁 integration-test/      # 整合測試
├── 📁 scripts/                   # 自動化腳本
│   ├── start-test-servers.bat    # 測試服務器啟動管理器
│   ├── validate-integration.php  # 整合驗證腳本
│   ├── pre-commit-check.bat      # Git提交前檢查
│   └── quick-test.bat            # 快速功能測試
├── 📁 test-logs/                 # 測試日誌
├── 📁 test-reports/              # 測試報告
└── DEVELOPMENT_WORKFLOW.md       # 開發流程文檔
```

### 🌐 端口分配
- **主服務器**: `localhost:8080`
- **API測試服務器**: `localhost:9081`
- **WebSocket測試服務器**: `localhost:9082`
- **前端測試服務器**: `localhost:9083`

---

## 🔄 開發流程

### 1️⃣ 新功能開發階段
```
功能設計 → 編寫代碼 → 單元測試 → 功能測試
```

**檢查清單:**
- [ ] 功能需求明確定義
- [ ] 代碼符合編碼規範
- [ ] 添加必要的錯誤處理
- [ ] 編寫相應的測試用例

### 2️⃣ 獨立測試階段
```
啟動測試服務器 → 功能驗證 → 性能測試 → 兼容性測試
```

**測試命令:**
```bash
# 啟動測試環境
scripts\start-test-servers.bat

# 快速功能測試
scripts\quick-test.bat

# 完整驗證
php scripts\validate-integration.php
```

### 3️⃣ 整合測試階段
```
合併代碼 → 整合測試 → 回歸測試 → 性能驗證
```

**檢查清單:**
- [ ] 所有API端點正常工作
- [ ] WebSocket連接穩定
- [ ] 前端功能完整
- [ ] 舊功能未受影響
- [ ] 性能指標達標

### 4️⃣ 部署前檢查
```
代碼審查 → 提交前檢查 → 文檔更新 → Git提交
```

**提交前檢查:**
```bash
# 運行提交前檢查
scripts\pre-commit-check.bat

# 如果通過，執行提交
git add .
git commit -m "feat: 新功能描述"
git push origin main
```

---

## 🧪 測試工具使用指南

### 🚀 測試服務器管理器
```bash
scripts\start-test-servers.bat
```

**功能:**
- 啟動單個或所有測試服務器
- 整合測試環境管理
- 測試報告查看
- 環境清理

### ⚡ 快速功能測試
```bash
scripts\quick-test.bat [api|websocket|frontend|all]
```

**功能:**
- API功能測試
- WebSocket連接測試
- 前端頁面測試
- 回歸測試

### 🔍 整合驗證
```bash
php scripts\validate-integration.php
```

**檢查項目:**
- 數據庫連接
- API端點
- WebSocket服務器
- 文件結構
- 前端整合
- 性能指標

### 📋 提交前檢查
```bash
scripts\pre-commit-check.bat
```

**檢查項目:**
- PHP語法檢查
- JavaScript語法檢查
- 必要文件存在性
- Composer依賴
- 整合驗證
- 文檔完整性

---

## 📊 測試報告和日誌

### 📁 日誌文件
- `test-logs/api_test.log` - API測試日誌
- `test-logs/websocket_test.log` - WebSocket測試日誌
- `test-logs/integration_test.log` - 整合測試日誌

### 📈 測試報告
- `test-reports/integration-validation.json` - 整合驗證報告
- `test-reports/performance-test.json` - 性能測試報告
- `test-reports/regression-test.json` - 回歸測試報告

---

## 🚨 問題預防機制

### 1. 自動化檢查
- **語法檢查**: PHP和JavaScript語法驗證
- **依賴檢查**: Composer和npm依賴驗證
- **安全檢查**: SQL注入和XSS防護檢查

### 2. 回歸測試
- **API認證問題**: 檢查500錯誤是否復現
- **房間代碼問題**: 檢查undefined/null問題
- **WebSocket連接**: 檢查連接穩定性

### 3. 性能監控
- **響應時間**: API響應時間監控
- **內存使用**: 服務器內存使用監控
- **並發處理**: 多用戶並發測試

---

## 📋 最佳實踐

### ✅ 開發規範
1. **測試優先**: 新功能必須先通過測試環境驗證
2. **漸進整合**: 分階段整合，避免大規模變更
3. **文檔同步**: 代碼變更必須同步更新文檔
4. **版本標記**: 每個階段都有明確的版本標記

### ✅ 測試規範
1. **全面覆蓋**: API、WebSocket、前端都要測試
2. **真實場景**: 模擬真實用戶使用場景
3. **邊界測試**: 測試極限情況和錯誤處理
4. **性能測試**: 確保性能不會退化

### ✅ 提交規範
1. **提交前檢查**: 必須通過所有檢查才能提交
2. **提交信息**: 使用規範的提交信息格式
3. **小步提交**: 避免大規模的單次提交
4. **回滾準備**: 確保可以快速回滾

---

## 🎯 成功指標

### 📈 質量指標
- **測試覆蓋率**: > 90%
- **API成功率**: > 99%
- **WebSocket穩定性**: > 99%
- **前端載入時間**: < 3秒

### 📊 效率指標
- **測試執行時間**: < 5分鐘
- **問題發現時間**: < 1小時
- **修復時間**: < 4小時
- **部署時間**: < 10分鐘

---

## 🔧 故障排除

### 常見問題和解決方案

#### 1. 測試服務器啟動失敗
```bash
# 檢查端口占用
netstat -an | findstr ":8081"

# 檢查PHP版本
php --version

# 檢查依賴
composer install
```

#### 2. API測試失敗
```bash
# 檢查主服務器狀態
curl http://localhost:8080/api/status

# 檢查PHP語法
php -l backend/api/auth.php

# 查看錯誤日誌
type test-logs/api_test.log
```

#### 3. WebSocket連接問題
```bash
# 檢查WebSocket服務器
netstat -an | findstr ":8080"

# 檢查Ratchet依賴
composer show ratchet/pawl

# 查看WebSocket日誌
type test-logs/websocket_test.log
```

---

## 📅 維護計劃

### 🔄 定期維護
- **每週**: 運行完整測試套件
- **每月**: 更新測試用例和文檔
- **每季**: 評估和優化測試流程

### 📊 監控和報告
- **實時監控**: 服務器狀態和性能指標
- **週報**: 測試執行情況和問題統計
- **月報**: 質量趨勢和改進建議

---

**🎯 目標**: 通過完善的測試驅動開發流程，確保每個新功能都經過充分驗證，避免影響生產環境的穩定性。

**📅 更新日期**: 2025-06-07  
**📝 版本**: v1.0  
**👥 適用範圍**: 所有開發人員 