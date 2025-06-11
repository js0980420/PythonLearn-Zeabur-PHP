# PythonLearn 跨設備網路服務器啟動腳本
# 包含網路診斷和防火牆檢查功能

param(
    [switch]$CheckFirewall = $false,
    [switch]$ShowHelp = $false
)

if ($ShowHelp) {
    Write-Host "🌐 PythonLearn 跨設備網路服務器" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "用法："
    Write-Host "  .\start-network-server.ps1              # 啟動服務器"
    Write-Host "  .\start-network-server.ps1 -CheckFirewall # 檢查並配置防火牆"
    Write-Host ""
    exit
}

function Write-Header {
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "  🌐 PythonLearn 跨設備網路服務器" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
}

function Get-LocalIPAddresses {
    Write-Host "📊 正在檢測網路配置..." -ForegroundColor Yellow
    Write-Host ""
    
    $ipAddresses = @()
    $networkAdapters = Get-NetIPAddress -AddressFamily IPv4 | Where-Object {
        $_.IPAddress -ne "127.0.0.1" -and 
        $_.IPAddress -ne "169.254.*" -and
        $_.SuffixOrigin -eq "Dhcp" -or $_.SuffixOrigin -eq "Manual"
    }
    
    Write-Host "🔍 當前電腦的IP地址：" -ForegroundColor Green
    foreach ($adapter in $networkAdapters) {
        $ipAddress = $adapter.IPAddress
        $interfaceAlias = (Get-NetAdapter -InterfaceIndex $adapter.InterfaceIndex).Name
        Write-Host "    $ipAddress ($interfaceAlias)" -ForegroundColor White
        $ipAddresses += $ipAddress
    }
    
    if ($ipAddresses.Count -eq 0) {
        Write-Host "    ❌ 未找到可用的網路連接" -ForegroundColor Red
        Write-Host "    請檢查網路設置或WiFi連接" -ForegroundColor Yellow
    }
    
    Write-Host ""
    return $ipAddresses
}

function Test-FirewallRule {
    param($Port = 8080)
    
    Write-Host "🛡️ 檢查防火牆設置..." -ForegroundColor Yellow
    
    try {
        $firewallRules = Get-NetFirewallRule -Direction Inbound | Where-Object {
            $_.Action -eq "Allow" -and $_.Enabled -eq "True"
        }
        
        $portRules = $firewallRules | Get-NetFirewallPortFilter | Where-Object {
            $_.LocalPort -eq $Port
        }
        
        if ($portRules.Count -gt 0) {
            Write-Host "    ✅ 端口 $Port 已允許通過防火牆" -ForegroundColor Green
            return $true
        }
        else {
            Write-Host "    ⚠️ 端口 $Port 未在防火牆中開放" -ForegroundColor Yellow
            return $false
        }
    }
    catch {
        Write-Host "    ❌ 無法檢查防火牆設置 (需要管理員權限)" -ForegroundColor Red
        return $false
    }
}

function Add-FirewallRule {
    param($Port = 8080)
    
    Write-Host "🔧 正在配置防火牆規則..." -ForegroundColor Yellow
    
    try {
        # 檢查是否以管理員身份運行
        $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
        $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
        $isAdmin = $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
        
        if (-not $isAdmin) {
            Write-Host "    ❌ 需要管理員權限才能配置防火牆" -ForegroundColor Red
            Write-Host "    請以管理員身份運行 PowerShell 並重新執行" -ForegroundColor Yellow
            return $false
        }
        
        # 創建防火牆規則
        $ruleName = "PythonLearn-Port-$Port"
        
        # 刪除舊規則（如果存在）
        try {
            Remove-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue
        }
        catch { }
        
        # 創建新規則
        New-NetFirewallRule -DisplayName $ruleName -Direction Inbound -Protocol TCP -LocalPort $Port -Action Allow
        
        Write-Host "    ✅ 防火牆規則已創建：允許端口 $Port" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "    ❌ 創建防火牆規則失敗：$($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Show-AccessInfo {
    param($IPAddresses)
    
    Write-Host "📱 筆電訪問地址：" -ForegroundColor Cyan
    
    if ($IPAddresses.Count -gt 0) {
        $primaryIP = $IPAddresses[0]
        Write-Host "    主要: http://$primaryIP:8080" -ForegroundColor White
        Write-Host "    測試: http://$primaryIP:8080/network-test.html" -ForegroundColor Green
        
        if ($IPAddresses.Count -gt 1) {
            for ($i = 1; $i -lt $IPAddresses.Count; $i++) {
                $ip = $IPAddresses[$i]
                Write-Host "    備用: http://$ip:8080" -ForegroundColor Gray
            }
        }
    }
    else {
        Write-Host "    ❌ 無可用IP地址" -ForegroundColor Red
    }
    
    Write-Host ""
    Write-Host "🔗 本地訪問地址：" -ForegroundColor Cyan
    Write-Host "    http://localhost:8080" -ForegroundColor White
    Write-Host ""
}

function Show-Instructions {
    Write-Host "📋 使用說明：" -ForegroundColor Cyan
    Write-Host "  1. 確保筆電與這台電腦在同一個WiFi網路下" -ForegroundColor White
    Write-Host "  2. 在筆電瀏覽器中輸入上方的IP地址" -ForegroundColor White
    Write-Host "  3. 如果無法訪問，請以管理員身份運行：" -ForegroundColor White
    Write-Host "     .\start-network-server.ps1 -CheckFirewall" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "🛑 按 Ctrl+C 停止服務器" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
}

function Start-NetworkServer {
    param($IPAddresses)
    
    Write-Host "🚀 啟動支援跨設備訪問的服務器..." -ForegroundColor Yellow
    Write-Host ""
    
    Show-AccessInfo -IPAddresses $IPAddresses
    Show-Instructions
    
    # 啟動PHP服務器
    try {
        & php -S 0.0.0.0:8080 -t public
    }
    catch {
        Write-Host "❌ 啟動服務器失敗：$($_.Exception.Message)" -ForegroundColor Red
        Write-Host "請確保已安裝PHP並添加到PATH環境變數" -ForegroundColor Yellow
    }
    
    Write-Host ""
    Write-Host "服務器已停止" -ForegroundColor Yellow
    Read-Host "按Enter鍵繼續..."
}

# 主程序
Clear-Host
Write-Header

$ipAddresses = Get-LocalIPAddresses

if ($CheckFirewall) {
    $firewallOK = Test-FirewallRule -Port 8080
    
    if (-not $firewallOK) {
        Write-Host "⚠️ 檢測到防火牆可能阻止外部訪問" -ForegroundColor Yellow
        $response = Read-Host "是否要配置防火牆規則？(y/N)"
        
        if ($response -eq "y" -or $response -eq "Y") {
            $success = Add-FirewallRule -Port 8080
            if ($success) {
                Write-Host "✅ 防火牆配置完成" -ForegroundColor Green
            }
        }
    }
    Write-Host ""
}

# 如果沒有IP地址，提供幫助信息
if ($ipAddresses.Count -eq 0) {
    Write-Host "❌ 無法啟動跨設備服務器：未找到可用的網路連接" -ForegroundColor Red
    Write-Host ""
    Write-Host "解決方案：" -ForegroundColor Yellow
    Write-Host "  1. 檢查WiFi連接" -ForegroundColor White
    Write-Host "  2. 檢查網路設定" -ForegroundColor White
    Write-Host "  3. 重新啟動網路介面卡" -ForegroundColor White
    Write-Host ""
    Read-Host "按Enter鍵退出..."
    exit 1
}

Start-NetworkServer -IPAddresses $ipAddresses