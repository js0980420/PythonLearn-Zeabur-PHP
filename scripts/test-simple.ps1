# 簡單測試腳本
Write-Host "Hello, PythonLearn!" -ForegroundColor Green
Write-Host "PowerShell 腳本運行正常" -ForegroundColor Cyan

# 檢查 PHP 是否可用
try {
    $phpVersion = php -v 2>$null
    if ($phpVersion) {
        Write-Host "PHP 已安裝" -ForegroundColor Green
    } else {
        Write-Host "PHP 未找到" -ForegroundColor Red
    }
} catch {
    Write-Host "PHP 檢查失敗" -ForegroundColor Red
}

Write-Host "按任意鍵退出..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 