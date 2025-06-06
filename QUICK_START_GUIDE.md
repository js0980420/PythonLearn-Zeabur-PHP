# 🚀 Python多人協作教學平台 - 快速啟動指南

## 📋 最新更新 (2025-06-06)
- ✅ **檔案結構重組**: 所有前端檔案移動到 `public/` 目錄
- ✅ **路徑修正**: HTML中的JS/CSS引用路徑已更新  
- ✅ **架構現代化**: 移除15檔案限制，採用標準Web結構
- ✅ **Zeabur規範**: 完全符合雲端部署最佳實踐
- ✅ **配置頁面**: 新增 `public/config.html` 系統管理界面

## 🎯 腳本選擇指南

### 📋 可用的啟動腳本

| 腳本名稱 | 用途 | 推薦場景 | 語言 |
|---------|------|----------|------|
| `start-simple.bat` | **推薦使用** | 日常開發、演示 | 英文 (避免編碼問題) |
| `start.bat` | 完整功能版 | 中文環境、進階功能 | 中文 (可能有編碼問題) |
| `cleanup-simple.bat` | **推薦使用** | 系統清理、故障排除 | 英文 (穩定可靠) |
| `system-cleanup.bat` | 完整清理版 | 深度清理、系統維護 | 中文 (可能有編碼問題) |

## ⚡ 快速開始 (推薦)

### 1. 一鍵啟動服務
```bash
# 使用簡化版本 (推薦)
.\start-simple.bat

# 或者使用完整版本
.\start.bat
```

### 2. 系統清理
```bash
# 使用簡化清理工具 (推薦)
.\cleanup-simple.bat

# 或者使用完整清理工具
.\system-cleanup.bat
```

## 🔧 問題解決

### 編碼問題解決方案
如果遇到中文字符顯示為亂碼，請：

1. **使用英文版腳本** (推薦)
   ```bash
   .\start-simple.bat    # 英文啟動腳本
   .\cleanup-simple.bat  # 英文清理腳本
   ```

2. **設置UTF-8編碼**
   ```bash
   chcp 65001
   .\start.bat
   ```

3. **PowerShell環境**
   ```powershell
   # 設置編碼
   [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
   .\start.bat
   ```

### 常見問題診斷

#### 問題：端口被占用
```bash
# 快速解決
.\cleanup-simple.bat
# 選擇 [1] Quick Clean
```

#### 問題：PHP進程卡死
```bash
# 深度清理
.\cleanup-simple.bat
# 選擇 [2] Deep Clean
```

#### 問題：WebSocket連接失敗
```bash
# 清理並重啟
.\cleanup-simple.bat
# 選擇 [5] Clean and Restart Services
```

## 📋 功能對比

### start-simple.bat 功能
- ✅ 自動端口檢測和清理
- ✅ PHP環境檢查
- ✅ 服務啟動驗證
- ✅ 瀏覽器快速訪問
- ✅ 系統狀態監控
- ✅ 英文界面，避免編碼問題

### cleanup-simple.bat 功能
- ✅ 快速清理 (PHP進程、端口、緩存)
- ✅ 深度清理 (全面系統清理)
- ✅ 智能清理 (長時間進程、殭屍窗口)
- ✅ 系統狀態報告
- ✅ 清理後自動重啟服務

## 🎯 使用建議

### 日常開發流程
1. **首次啟動**: `.\start-simple.bat`
2. **遇到問題**: `.\cleanup-simple.bat` → [1] Quick Clean
3. **深度問題**: `.\cleanup-simple.bat` → [2] Deep Clean
4. **完全重啟**: `.\cleanup-simple.bat` → [5] Clean and Restart

### 最佳實踐
- **優先使用英文版腳本**：避免編碼相關問題
- **定期清理**：每次開發結束後運行快速清理
- **問題排查**：先嘗試快速清理，再考慮深度清理
- **監控狀態**：定期檢查系統狀態確保服務正常

## 🌐 訪問地址

啟動成功後，可以通過以下地址訪問：

| 服務 | 地址 | 用途 |
|------|------|------|
| 學生界面 | http://localhost:8080 | 主要編程界面 (→ public/index.html) |
| 教師後台 | http://localhost:8080/teacher-dashboard.html | 教師監控面板 |
| 系統配置 | http://localhost:8080/config.html | 系統管理界面 (新增) |
| 健康檢查 | http://localhost:8080/health | 服務狀態檢查 |

## 🚨 故障排除

### 腳本無法運行
1. 確保以管理員身份運行命令行
2. 檢查PHP是否已安裝並加入PATH
3. 使用 `chcp 65001` 設置UTF-8編碼

### 服務啟動失敗
1. 運行 `cleanup-simple.bat` 清理系統
2. 檢查端口8080和8081是否被占用
3. 重啟命令行工具重試

### 字符編碼問題
1. **推薦方案**：直接使用英文版腳本
2. **備用方案**：設置控制台編碼為UTF-8
3. **最後方案**：使用PowerShell而非CMD

## 📞 技術支援

如果問題仍然存在，請：
1. 運行 `cleanup-simple.bat` → [4] Show System Status
2. 檢查錯誤日誌信息
3. 確認PHP版本為8.0+
4. 重啟計算機後重新嘗試

---

**💡 小提示**: 建議將 `start-simple.bat` 設為快捷方式放在桌面，便於日常快速啟動！ 