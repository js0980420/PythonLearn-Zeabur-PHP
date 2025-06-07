# PythonLearn 服務管理工具
# 版本: v2.0
# 更新: 2025-06-07

# UTF-8 編碼設定
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
chcp 65001 > $null

# 顏色定義
$Colors = @{
    Success = "Green"
    Warning = "Yellow" 
    Error = "Red"
    Info = "Cyan"
    Header = "Magenta"
}

function Write-ColorText($Text, $Color) {
    Write-Host $Text -ForegroundColor $Colors[$Color]
}

# 服務定義
$Services = @{
    "PHP Web服務器" = @{
        Port = 8080
        Process = "php"
        Command = "php -S localhost:8080 -t public router.php"
        CheckPath = "public/index.html"
    }
    "WebSocket服務器" = @{
        Port = 8081
        Process = "php"
        Command = "php websocket/server.php"
        CheckPath = "websocket/server.php"
    }
    "XAMPP Apache" = @{
        Port = 8082
        Process = "httpd"
        Command = "C:\xampp\apache\bin\httpd.exe"
        CheckPath = "C:\xampp\apache\bin\httpd.exe"
    }
    "MySQL" = @{
        Port = 3306
        Process = "mysqld"
        Command = "C:\xampp\mysql\bin\mysqld.exe"
        CheckPath = "C:\xampp\mysql\bin\mysqld.exe"
    }
}

# 檢查服務狀態
function Get-ServiceStatus {
    param([string]$ServiceName = "")
    
    $results = @{}
    
    foreach ($service in $Services.GetEnumerator()) {
        $name = $service.Key
        $config = $service.Value
        
        # 如果指定了服務名稱，只檢查該服務
        if ($ServiceName -and $name -ne $ServiceName) {
            continue
        }
        
        $status = @{
            Name = $name
            Port = $config.Port
            Process = $config.Process
            IsRunning = $false
            ProcessId = $null
            PortInUse = $false
        }
        
        try {
            # 檢查端口是否被佔用
            $connection = Get-NetTCPConnection -LocalPort $config.Port -ErrorAction SilentlyContinue
            if ($connection) {
                $status.PortInUse = $true
                $status.ProcessId = $connection.OwningProcess
            }
            
            # 檢查進程是否運行
            $processes = Get-Process -Name $config.Process -ErrorAction SilentlyContinue
            if ($processes) {
                $status.IsRunning = $true
                if (-not $status.ProcessId) {
                    $status.ProcessId = $processes[0].Id
                }
            }
        } catch {
            # 忽略檢查錯誤
        }
        
        $results[$name] = $status
    }
    
    return $results
}

# 顯示服務狀態
function Show-ServiceStatus {
    Write-ColorText "📊 服務狀態檢查" "Header"
    Write-ColorText "==================" "Header"
    Write-Host ""
    
    $statusResults = Get-ServiceStatus
    
    foreach ($result in $statusResults.GetEnumerator()) {
        $name = $result.Key
        $status = $result.Value
        
        $statusText = if ($status.IsRunning) { "✅ 運行中" } else { "❌ 未運行" }
        $portText = if ($status.PortInUse) { "佔用" } else { "空閒" }
        
        Write-ColorText "$name" "Info"
        Write-ColorText "  狀態: $statusText" $(if ($status.IsRunning) { "Success" } else { "Error" })
        Write-ColorText "  端口: $($status.Port) ($portText)" $(if ($status.PortInUse) { "Warning" } else { "Info" })
        
        if ($status.ProcessId) {
            Write-ColorText "  進程ID: $($status.ProcessId)" "Info"
        }
        Write-Host ""
    }
}

# 停止服務
function Stop-Service {
    param([string]$ServiceName)
    
    if (-not $Services.ContainsKey($ServiceName)) {
        Write-ColorText "❌ 未知的服務: $ServiceName" "Error"
        return $false
    }
    
    $config = $Services[$ServiceName]
    Write-ColorText "🛑 正在停止 $ServiceName..." "Info"
    
    try {
        # 停止進程
        $processes = Get-Process -Name $config.Process -ErrorAction SilentlyContinue
        if ($processes) {
            $processes | Stop-Process -Force
            Write-ColorText "  ✅ 進程已停止" "Success"
        }
        
        # 清理端口
        $connections = Get-NetTCPConnection -LocalPort $config.Port -ErrorAction SilentlyContinue
        if ($connections) {
            foreach ($conn in $connections) {
                Stop-Process -Id $conn.OwningProcess -Force -ErrorAction SilentlyContinue
            }
            Write-ColorText "  ✅ 端口已清理" "Success"
        }
        
        Start-Sleep -Seconds 2
        
        # 驗證停止
        $status = Get-ServiceStatus -ServiceName $ServiceName
        if (-not $status[$ServiceName].IsRunning) {
            Write-ColorText "✅ $ServiceName 已成功停止" "Success"
            return $true
        } else {
            Write-ColorText "⚠️ $ServiceName 可能未完全停止" "Warning"
            return $false
        }
        
    } catch {
        Write-ColorText "❌ 停止 $ServiceName 時發生錯誤: $($_.Exception.Message)" "Error"
        return $false
    }
}

# 啟動服務
function Start-Service {
    param([string]$ServiceName)
    
    if (-not $Services.ContainsKey($ServiceName)) {
        Write-ColorText "❌ 未知的服務: $ServiceName" "Error"
        return $false
    }
    
    $config = $Services[$ServiceName]
    Write-ColorText "🚀 正在啟動 $ServiceName..." "Info"
    
    try {
        # 檢查依賴檔案
        if ($config.CheckPath -and -not (Test-Path $config.CheckPath)) {
            Write-ColorText "❌ 找不到必要檔案: $($config.CheckPath)" "Error"
            return $false
        }
        
        # 檢查是否已經運行
        $status = Get-ServiceStatus -ServiceName $ServiceName
        if ($status[$ServiceName].IsRunning) {
            Write-ColorText "⚠️ $ServiceName 已經在運行中" "Warning"
            return $true
        }
        
        # 啟動服務
        if ($ServiceName -eq "PHP Web服務器") {
            Start-Process "php" -ArgumentList "-S", "localhost:8080", "-t", "public", "router.php" -WindowStyle Hidden
        } elseif ($ServiceName -eq "WebSocket服務器") {
            Start-Process "php" -ArgumentList "websocket/server.php" -WindowStyle Hidden
        } elseif ($ServiceName -eq "XAMPP Apache") {
            if (Test-Path "C:\xampp\apache\bin\httpd.exe") {
                Start-Process "C:\xampp\apache\bin\httpd.exe" -WindowStyle Hidden
            } else {
                Write-ColorText "❌ 找不到XAMPP Apache" "Error"
                return $false
            }
        } elseif ($ServiceName -eq "MySQL") {
            if (Test-Path "C:\xampp\mysql\bin\mysqld.exe") {
                $configPath = "C:\xampp\mysql\bin\my.ini"
                if (Test-Path $configPath) {
                    Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=$configPath" -WindowStyle Hidden
                } else {
                    Start-Process "C:\xampp\mysql\bin\mysqld.exe" -WindowStyle Hidden
                }
            } else {
                Write-ColorText "❌ 找不到MySQL" "Error"
                return $false
            }
        }
        
        # 等待服務啟動
        Write-ColorText "  ⏳ 等待服務啟動..." "Info"
        Start-Sleep -Seconds 3
        
        # 驗證啟動
        $status = Get-ServiceStatus -ServiceName $ServiceName
        if ($status[$ServiceName].IsRunning) {
            Write-ColorText "✅ $ServiceName 已成功啟動" "Success"
            return $true
        } else {
            Write-ColorText "❌ $ServiceName 啟動失敗" "Error"
            return $false
        }
        
    } catch {
        Write-ColorText "❌ 啟動 $ServiceName 時發生錯誤: $($_.Exception.Message)" "Error"
        return $false
    }
}

# 重啟服務
function Restart-Service {
    param([string]$ServiceName)
    
    Write-ColorText "🔄 正在重啟 $ServiceName..." "Info"
    
    $stopResult = Stop-Service -ServiceName $ServiceName
    Start-Sleep -Seconds 2
    $startResult = Start-Service -ServiceName $ServiceName
    
    if ($stopResult -and $startResult) {
        Write-ColorText "✅ $ServiceName 重啟成功" "Success"
        return $true
    } else {
        Write-ColorText "❌ $ServiceName 重啟失敗" "Error"
        return $false
    }
}

# 停止所有服務
function Stop-AllServices {
    Write-ColorText "🛑 正在停止所有服務..." "Header"
    Write-Host ""
    
    foreach ($serviceName in $Services.Keys) {
        Stop-Service -ServiceName $serviceName
        Write-Host ""
    }
}

# 啟動所有服務
function Start-AllServices {
    Write-ColorText "🚀 正在啟動所有服務..." "Header"
    Write-Host ""
    
    # 按順序啟動服務
    $startOrder = @("MySQL", "XAMPP Apache", "PHP Web服務器", "WebSocket服務器")
    
    foreach ($serviceName in $startOrder) {
        if ($Services.ContainsKey($serviceName)) {
            Start-Service -ServiceName $serviceName
            Write-Host ""
            Start-Sleep -Seconds 2
        }
    }
}

# 顯示菜單
function Show-Menu {
    Clear-Host
    Write-ColorText "🔧 PythonLearn 服務管理工具" "Header"
    Write-ColorText "==============================" "Header"
    Write-Host ""
    
    Write-ColorText "請選擇操作:" "Info"
    Write-ColorText "1. 檢查服務狀態" "Info"
    Write-ColorText "2. 啟動所有服務" "Info"
    Write-ColorText "3. 停止所有服務" "Info"
    Write-ColorText "4. 重啟所有服務" "Info"
    Write-ColorText "5. 管理單個服務" "Info"
    Write-ColorText "0. 退出" "Info"
    Write-Host ""
}

# 管理單個服務
function Manage-IndividualService {
    Clear-Host
    Write-ColorText "🔧 單個服務管理" "Header"
    Write-ColorText "=================" "Header"
    Write-Host ""
    
    # 顯示可用服務
    Write-ColorText "可用服務:" "Info"
    $serviceList = @($Services.Keys)
    for ($i = 0; $i -lt $serviceList.Count; $i++) {
        Write-ColorText "$($i + 1). $($serviceList[$i])" "Info"
    }
    Write-Host ""
    
    $choice = Read-Host "請選擇服務編號"
    
    try {
        $serviceIndex = [int]$choice - 1
        if ($serviceIndex -ge 0 -and $serviceIndex -lt $serviceList.Count) {
            $serviceName = $serviceList[$serviceIndex]
            
            Write-Host ""
            Write-ColorText "選擇操作:" "Info"
            Write-ColorText "1. 啟動" "Info"
            Write-ColorText "2. 停止" "Info"
            Write-ColorText "3. 重啟" "Info"
            Write-ColorText "4. 檢查狀態" "Info"
            Write-Host ""
            
            $action = Read-Host "請選擇操作"
            Write-Host ""
            
            switch ($action) {
                "1" { Start-Service -ServiceName $serviceName }
                "2" { Stop-Service -ServiceName $serviceName }
                "3" { Restart-Service -ServiceName $serviceName }
                "4" { 
                    $status = Get-ServiceStatus -ServiceName $serviceName
                    $serviceStatus = $status[$serviceName]
                    Write-ColorText "$serviceName 狀態:" "Info"
                    Write-ColorText "  運行狀態: $(if ($serviceStatus.IsRunning) { '✅ 運行中' } else { '❌ 未運行' })" $(if ($serviceStatus.IsRunning) { "Success" } else { "Error" })
                    Write-ColorText "  端口: $($serviceStatus.Port)" "Info"
                    if ($serviceStatus.ProcessId) {
                        Write-ColorText "  進程ID: $($serviceStatus.ProcessId)" "Info"
                    }
                }
                default { Write-ColorText "❌ 無效的選擇" "Error" }
            }
        } else {
            Write-ColorText "❌ 無效的服務編號" "Error"
        }
    } catch {
        Write-ColorText "❌ 無效的輸入" "Error"
    }
    
    Write-Host ""
    Read-Host "按Enter鍵繼續"
}

# 主程序
function Main {
    # 檢查專案目錄
    if (-not (Test-Path "public/index.html") -or -not (Test-Path "websocket/server.php")) {
        Write-ColorText "❌ 錯誤: 請在PythonLearn專案根目錄中執行此腳本" "Error"
        Read-Host "按Enter鍵退出"
        exit 1
    }
    
    while ($true) {
        Show-Menu
        $choice = Read-Host "請選擇操作"
        
        switch ($choice) {
            "1" {
                Clear-Host
                Show-ServiceStatus
                Read-Host "按Enter鍵繼續"
            }
            "2" {
                Clear-Host
                Start-AllServices
                Read-Host "按Enter鍵繼續"
            }
            "3" {
                Clear-Host
                Stop-AllServices
                Read-Host "按Enter鍵繼續"
            }
            "4" {
                Clear-Host
                Write-ColorText "🔄 正在重啟所有服務..." "Header"
                Write-Host ""
                Stop-AllServices
                Start-Sleep -Seconds 3
                Start-AllServices
                Read-Host "按Enter鍵繼續"
            }
            "5" {
                Manage-IndividualService
            }
            "0" {
                Write-ColorText "👋 再見！" "Success"
                exit 0
            }
            default {
                Write-ColorText "❌ 無效的選擇，請重新輸入" "Error"
                Start-Sleep -Seconds 2
            }
        }
    }
}

# 執行主程序
Main 