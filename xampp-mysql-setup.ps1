# XAMPP MariaDB 自動啟動設置
Write-Host "🚀 XAMPP MariaDB 自動啟動設置" -ForegroundColor Cyan

# 檢查管理員權限
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "❌ 需要管理員權限，請以管理員身份運行此腳本" -ForegroundColor Red
    exit 1
}

$xamppPath = "C:\xampp"
$mysqlBin = "$xamppPath\mysql\bin"

Write-Host "✅ 使用管理員權限執行" -ForegroundColor Green

# 停止所有 MySQL 進程
Write-Host "🛑 停止 MySQL 進程..." -ForegroundColor Yellow
taskkill /F /IM mysqld.exe 2>$null

# 清理現有服務
Write-Host "🧹 清理現有 MySQL 服務..." -ForegroundColor Yellow
net stop mysql 2>$null
net stop mysql80 2>$null
net stop MySQL93 2>$null
sc delete mysql 2>$null
sc delete mysql80 2>$null
sc delete MySQL93 2>$null

# 安裝 XAMPP MariaDB 服務
Write-Host "📥 安裝 MariaDB 服務..." -ForegroundColor Yellow
& "$mysqlBin\mysqld.exe" --install mysql --defaults-file="$mysqlBin\my.ini"

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ MariaDB 服務安裝成功" -ForegroundColor Green
    
    # 設置自動啟動
    Write-Host "⚙️ 設置自動啟動..." -ForegroundColor Yellow
    sc config mysql start= auto
    
    # 啟動服務
    Write-Host "🚀 啟動服務..." -ForegroundColor Yellow
    net start mysql
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ MariaDB 服務啟動成功" -ForegroundColor Green
    } else {
        Write-Host "⚠️ 服務啟動失敗，可能需要修復系統表" -ForegroundColor Yellow
    }
} else {
    Write-Host "❌ MariaDB 服務安裝失敗" -ForegroundColor Red
}

# 檢查狀態
Write-Host "🔍 檢查服務狀態..." -ForegroundColor Yellow
sc query mysql

Write-Host "🔍 檢查端口 3306..." -ForegroundColor Yellow
netstat -ano | findstr ":3306"

Write-Host "🎉 設置完成！" -ForegroundColor Green 