# PythonLearn 協作平台啟動指南

## 🚀 快速啟動

### 方法一：使用快速啟動腳本（推薦）
```bash
quick-start.bat
```

### 方法二：使用完整啟動腳本
```bash
start-pythonlearn.bat
```

### 方法三：直接命令行
```bash
php -S localhost:8080 -t public
```

## 📋 系統要求

- **PHP 8.1+** - 必須安裝並加入 PATH
- **現代瀏覽器** - 支援 ES6+ 的瀏覽器

## 🌐 訪問平台

啟動後訪問：**http://localhost:8080**

## 🔧 架構說明

- **純 PHP 後端** - 使用 PHP 內建服務器
- **HTTP 輪詢** - 實時協作通過 HTTP 輪詢實現
- **無 WebSocket** - 不需要 WebSocket 服務器
- **無 Node.js** - 不需要 Node.js 環境

## 📁 項目結構

```
PythonLearn-Zeabur-PHP/
├── public/                 # Web 根目錄
│   ├── index.html         # 主頁面
│   ├── api.php           # API 端點
│   ├── js/               # JavaScript 文件
│   └── css/              # 樣式文件
├── quick-start.bat        # 快速啟動腳本
├── start-pythonlearn.bat  # 完整啟動腳本
└── composer.json          # PHP 依賴配置
```

## 🛠️ 開發模式

使用 Composer 腳本：
```bash
composer run start    # 啟動開發服務器
composer run dev      # 同上
```

## ❌ 常見問題

### PHP 未找到
```
ERROR: PHP not found in PATH!
```
**解決方案：** 安裝 PHP 並將其加入系統 PATH

### 端口被占用
```
Address already in use
```
**解決方案：** 啟動腳本會自動終止占用端口的進程

### 找不到項目文件
```
ERROR: public\index.html not found!
```
**解決方案：** 確保在正確的項目目錄中運行腳本

## 🔄 停止服務器

在命令行窗口中按 `Ctrl+C` 停止服務器

## 📝 注意事項

- 所有啟動腳本都是 `.bat` 格式，避免 PowerShell 執行權限問題
- 平台使用 HTTP 輪詢，無需額外的 WebSocket 服務器
- 開發時建議使用 `quick-start.bat` 快速啟動 