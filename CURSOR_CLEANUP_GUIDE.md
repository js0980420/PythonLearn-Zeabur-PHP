# 🎯 Cursor開發環境清理指南

## 🚨 背景進程過多問題

當您在Cursor中開發時，可能會遇到以下問題：
- PHP服務器在背景運行多個實例
- WebSocket服務器占用端口
- 大量CMD/PowerShell視窗在背景運行
- 系統資源被過度占用
- 端口衝突導致新服務無法啟動

## ⚡ 快速解決方案

### 🥇 推薦：一鍵快速清理
```bash
.\quick-clean-cursor.bat
```
**用途**：專門為Cursor開發環境設計的快速清理工具
- ✅ 自動停止所有PHP服務器
- ✅ 釋放開發端口 (8080, 8081, 3000, 5000)
- ✅ 清理背景終端進程
- ✅ 清理PowerShell背景任務
- ✅ 清除臨時文件和網絡緩存
- ✅ 顯示清理狀態和快速重啟選項

### 🛠️ 進階：完整清理工具
```bash
.\cursor-cleanup.bat
```
**功能**：提供多種清理選項的完整工具
- 🧹 智能清理 (推薦給Cursor用戶)
- 🔄 快速PHP清理
- 🚀 完整環境重置
- 📊 顯示所有背景進程
- 🎯 選擇性進程清理
- 💻 Cursor專屬進程管理

## 📋 清理工具對比

| 工具 | 用途 | 執行時間 | 適用場景 |
|------|------|----------|----------|
| `quick-clean-cursor.bat` | **一鍵快速清理** | ~10秒 | 日常使用，背景進程過多 |
| `cursor-cleanup.bat` | 完整清理選項 | 10-60秒 | 深度清理，系統維護 |
| `cleanup-simple.bat` | 通用PHP清理 | ~15秒 | 一般PHP開發環境 |

## 🎯 使用場景和建議

### 日常開發流程
1. **遇到端口衝突時**
   ```bash
   .\quick-clean-cursor.bat
   # 選擇 [R] 重啟服務器
   ```

2. **系統卡頓時**
   ```bash
   .\cursor-cleanup.bat
   # 選擇 [1] Smart Cleanup
   ```

3. **完全重新開始**
   ```bash
   .\cursor-cleanup.bat
   # 選擇 [3] Full Environment Reset
   ```

### 特定問題解決

#### 問題：多個PHP服務器在背景運行
```bash
# 快速解決
.\quick-clean-cursor.bat

# 或者選擇性清理
.\cursor-cleanup.bat
# 選擇 [5] Selective Process Cleanup → [1] PHP processes only
```

#### 問題：端口8080/8081被占用
```bash
.\quick-clean-cursor.bat
# 自動釋放所有開發端口
```

#### 問題：大量CMD視窗開啟
```bash
.\cursor-cleanup.bat
# 選擇 [6] Cursor Process Management → [3] Reset Cursor terminal environment
```

#### 問題：系統資源不足
```bash
.\cursor-cleanup.bat
# 選擇 [1] Smart Cleanup (包含記憶體優化)
```

## 🔍 背景進程檢查

### 快速檢查當前狀態
```bash
# 檢查PHP進程
tasklist | findstr php.exe

# 檢查端口使用
netstat -an | findstr ":8080\|:8081"

# 檢查CMD進程數量
tasklist /fi "imagename eq cmd.exe" | find /c "cmd.exe"
```

### 詳細狀態報告
```bash
.\cursor-cleanup.bat
# 選擇 [4] Show All Background Processes
```

## 🚀 最佳實踐

### 預防措施
1. **定期清理**：每次開發結束後運行快速清理
2. **監控資源**：注意CPU和記憶體使用情況
3. **避免重複啟動**：啟動新服務前先檢查現有進程
4. **使用專用腳本**：避免手動php -S命令

### 開發習慣
1. **開始開發前**
   ```bash
   .\quick-clean-cursor.bat  # 清理環境
   .\start-simple.bat        # 啟動服務
   ```

2. **結束開發後**
   ```bash
   .\quick-clean-cursor.bat  # 清理背景進程
   ```

3. **遇到問題時**
   ```bash
   .\cursor-cleanup.bat      # 使用完整工具診斷
   ```

## ⚠️ 注意事項

### 安全提醒
- 清理工具會終止所有相關進程，請先保存工作
- 全環境重置會停止所有開發服務器
- 確保重要數據已保存再執行清理

### 權限要求
- 某些清理操作可能需要管理員權限
- 如遇權限問題，請以管理員身份運行命令行

### 相容性
- 支援Windows 10/11
- 需要PHP 8.0+環境
- 支援PowerShell和CMD環境

## 🆘 故障排除

### 清理工具無法運行
1. 檢查是否以管理員身份運行
2. 確認工作目錄正確
3. 檢查Windows防毒軟體是否阻擋

### 進程無法終止
1. 嘗試完整環境重置
2. 重啟命令行工具
3. 最後選擇：重啟電腦

### 服務無法重啟
1. 檢查PHP環境變數
2. 確認端口未被其他應用占用
3. 檢查防火牆設定

## 📞 技術支援

如果清理工具無法解決問題：
1. 運行 `cursor-cleanup.bat` → [4] 查看詳細狀態
2. 檢查是否有其他應用占用端口
3. 嘗試重啟Cursor編輯器
4. 最後方案：重啟計算機

---

**💡 小提示**: 建議將 `quick-clean-cursor.bat` 加入Cursor的任務或創建快捷鍵，方便快速清理！

**🔗 相關工具**:
- `start-simple.bat` - 啟動開發服務器
- `cleanup-simple.bat` - 通用PHP清理
- `PROJECT_TOOLS.md` - 完整工具文檔 