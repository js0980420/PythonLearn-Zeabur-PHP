# XAMPP MariaDB 管理員權限設置腳本
# 自動請求管理員權限並設置 MariaDB 為 Windows 服務

# 檢查是否以管理員身份運行
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "🔑 請求管理員權限..." -ForegroundColor Yellow
    Start-Process PowerShell -Verb RunAs "-File `"$PSCommandPath`""
    exit
}

Write-Host "🚀 XAMPP MariaDB 管理員設置" -ForegroundColor Cyan
Write-Host "=============================" -ForegroundColor Cyan

$xamppPath = "C:\xampp"
$mysqlPath = "$xamppPath\mysql"
$mysqlBin = "$mysqlPath\bin"

# 檢查 XAMPP 安裝
if (!(Test-Path $xamppPath)) {
    Write-Host "❌ 未找到 XAMPP 安裝目錄: $xamppPath" -ForegroundColor Red
    Read-Host "按 Enter 鍵退出"
    exit
}

Write-Host "✅ 找到 XAMPP 安裝: $xamppPath" -ForegroundColor Green

# 1. 停止所有 MySQL 相關服務和進程
Write-Host "`n🛑 停止現有 MySQL 服務和進程..." -ForegroundColor Yellow
Get-Process -Name "mysqld" -ErrorAction SilentlyContinue | Stop-Process -Force
Stop-Service -Name "mysql" -ErrorAction SilentlyContinue
Stop-Service -Name "mysql80" -ErrorAction SilentlyContinue
Stop-Service -Name "MySQL93" -ErrorAction SilentlyContinue
Start-Sleep -Seconds 3

# 2. 清理現有 MySQL 服務
Write-Host "`n🧹 清理現有 MySQL 服務..." -ForegroundColor Yellow
$services = @("mysql", "mysql80", "MySQL93")
foreach ($service in $services) {
    $serviceExists = Get-Service -Name $service -ErrorAction SilentlyContinue
    if ($serviceExists) {
        Write-Host "   🗑️ 移除服務: $service" -ForegroundColor Red
        sc.exe stop $service 2>$null
        sc.exe delete $service 2>$null
    }
}

# 3. 安裝 XAMPP MariaDB 為 Windows 服務
Write-Host "`n📥 安裝 XAMPP MariaDB 為 Windows 服務..." -ForegroundColor Yellow
$mysqldExe = "$mysqlBin\mysqld.exe"
$myIni = "$mysqlBin\my.ini"

if (!(Test-Path $mysqldExe)) {
    Write-Host "❌ 未找到 mysqld.exe: $mysqldExe" -ForegroundColor Red
    Read-Host "按 Enter 鍵退出"
    exit
}

Write-Host "   執行: $mysqldExe --install mysql --defaults-file=$myIni" -ForegroundColor Gray
$installResult = & $mysqldExe --install mysql --defaults-file=$myIni 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ MariaDB 服務安裝成功" -ForegroundColor Green
} else {
    Write-Host "❌ MariaDB 服務安裝失敗" -ForegroundColor Red
    Write-Host "   錯誤: $installResult" -ForegroundColor Red
}

# 4. 設置服務為自動啟動
Write-Host "`n⚙️ 設置 MariaDB 服務為自動啟動..." -ForegroundColor Yellow
$configResult = sc.exe config mysql start= auto 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ 自動啟動設置成功" -ForegroundColor Green
} else {
    Write-Host "❌ 自動啟動設置失敗: $configResult" -ForegroundColor Red
}

# 5. 啟動服務
Write-Host "`n🚀 啟動 MariaDB 服務..." -ForegroundColor Yellow
$startResult = net start mysql 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ MariaDB 服務啟動成功" -ForegroundColor Green
} else {
    Write-Host "❌ MariaDB 服務啟動失敗: $startResult" -ForegroundColor Red
    Write-Host "   請檢查錯誤日誌: $mysqlPath\data\mysql_error.log" -ForegroundColor Yellow
}

# 6. 驗證服務狀態
Write-Host "`n🔍 驗證服務狀態..." -ForegroundColor Yellow
$serviceStatus = sc.exe query mysql 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host $serviceStatus -ForegroundColor Gray
} else {
    Write-Host "❌ 無法查詢服務狀態" -ForegroundColor Red
}

# 7. 檢查端口
Write-Host "`n🌐 檢查端口 3306..." -ForegroundColor Yellow
$portCheck = netstat -ano | findstr ":3306"
if ($portCheck) {
    Write-Host "✅ 端口 3306 正在使用" -ForegroundColor Green
    Write-Host $portCheck -ForegroundColor Gray
} else {
    Write-Host "⚠️ 端口 3306 未被使用" -ForegroundColor Yellow
}

# 8. 測試連接
Write-Host "`n🧪 測試資料庫連接..." -ForegroundColor Yellow
$mysqlExe = "$mysqlBin\mysql.exe"
if (Test-Path $mysqlExe) {
    $connectionTest = & $mysqlExe -u root -e "SELECT VERSION();" 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ 資料庫連接測試成功" -ForegroundColor Green
        Write-Host "   版本: $connectionTest" -ForegroundColor Gray
    } else {
        Write-Host "⚠️ 資料庫連接測試失敗 (這是正常的，可能需要設置密碼)" -ForegroundColor Yellow
    }
}

# 9. 更新 XAMPP 控制面板配置
Write-Host "`n⚙️ 更新 XAMPP 控制面板配置..." -ForegroundColor Yellow
$controlConfig = "$xamppPath\xampp-control.ini"
if (Test-Path $controlConfig) {
    $config = Get-Content $controlConfig -Raw
    if ($config -notmatch "\[mysql\]") {
        Add-Content $controlConfig "`n[mysql]`nServiceName=mysql"
        Write-Host "✅ XAMPP 控制面板配置已更新" -ForegroundColor Green
    } else {
        Write-Host "✅ XAMPP 控制面板配置已存在" -ForegroundColor Green
    }
}

Write-Host "`n🎉 設置完成！" -ForegroundColor Green
Write-Host "=================" -ForegroundColor Green
Write-Host "📋 下一步操作：" -ForegroundColor Cyan
Write-Host "1. 現在可以通過 XAMPP 控制面板管理 MySQL 服務" -ForegroundColor White
Write-Host "2. MariaDB 將隨 Windows 自動啟動" -ForegroundColor White
Write-Host "3. 預設 root 用戶無密碼" -ForegroundColor White
Write-Host "4. 可使用 phpMyAdmin 管理資料庫" -ForegroundColor White

Write-Host "`n💡 提示：" -ForegroundColor Cyan
Write-Host "- 如果服務啟動失敗，可能是系統表需要修復" -ForegroundColor Yellow
Write-Host "- 可以使用 'services.msc' 查看 Windows 服務" -ForegroundColor Yellow
Write-Host "- XAMPP 控制面板現在可以控制 MySQL 服務" -ForegroundColor Yellow

Read-Host "`n按 Enter 鍵退出" 