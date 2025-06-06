# PythonLearn 服務器管理腳本
# 用於統一管理 Web 服務器、WebSocket 服務器和 MySQL

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("start", "stop", "restart", "status")]
    [string]$Action
)

# 配置
$WEB_PORT = 8080
$WEBSOCKET_PORT = 8081
$PROJECT_DIR = Get-Location

# 顏色函數
function Write-ColorOutput([string]$Message, [string]$ForegroundColor = "White") {
    Write-Host $Message -ForegroundColor $ForegroundColor
}

# 檢查端口是否被佔用
function Test-Port([int]$Port) {
    $connection = @(Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue)
    return $connection.Count -gt 0
}

# 獲取占用端口的進程
function Get-PortProcess([int]$Port) {
    $connection = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($connection) {
        return Get-Process -Id $connection.OwningProcess -ErrorAction SilentlyContinue
    }
    return $null
}

# 停止指定端口的進程
function Stop-PortProcess([int]$Port, [string]$ServiceName) {
    $process = Get-PortProcess -Port $Port
    if ($process) {
        Write-ColorOutput "🔄 停止 $ServiceName (PID: $($process.Id), 端口: $Port)" "Yellow"
        try {
            Stop-Process -Id $process.Id -Force -ErrorAction Stop
            Start-Sleep -Seconds 2
            Write-ColorOutput "✅ $ServiceName 已停止" "Green"
            return $true
        } catch {
            Write-ColorOutput "❌ 無法停止 $ServiceName : $_" "Red"
            return $false
        }
    } else {
        Write-ColorOutput "ℹ️  $ServiceName 未運行 (端口 $Port 空閒)" "Gray"
        return $true
    }
}

# 顯示狀態
function Show-Status {
    Write-ColorOutput "`n🎯 PythonLearn 服務器狀態" "Cyan"
    Write-ColorOutput "================================" "Cyan"
    
    # MySQL 狀態
    if (Test-Port -Port 3306) {
        Write-ColorOutput "🗄️  MySQL: ✅ 運行中 (端口 3306)" "Green"
    } else {
        Write-ColorOutput "🗄️  MySQL: ❌ 未運行" "Red"
    }
    
    # Web 服務器狀態
    if (Test-Port -Port $WEB_PORT) {
        Write-ColorOutput "🌐 Web 服務器: ✅ 運行中 (http://localhost:$WEB_PORT)" "Green"
    } else {
        Write-ColorOutput "🌐 Web 服務器: ❌ 未運行" "Red"
    }
    
    # WebSocket 服務器狀態
    if (Test-Port -Port $WEBSOCKET_PORT) {
        Write-ColorOutput "🔌 WebSocket: ✅ 運行中 (ws://localhost:$WEBSOCKET_PORT)" "Green"
    } else {
        Write-ColorOutput "🔌 WebSocket: ❌ 未運行" "Red"
    }
    
    Write-ColorOutput "================================`n" "Cyan"
}

# 主邏輯
switch ($Action) {
    "start" {
        Write-ColorOutput "🚀 啟動 PythonLearn 服務器..." "Cyan"
        
        # 先停止現有服務避免衝突
        Write-ColorOutput "🧹 清理現有服務..." "Yellow"
        Stop-PortProcess -Port $WEB_PORT -ServiceName "Web 服務器"
        Stop-PortProcess -Port $WEBSOCKET_PORT -ServiceName "WebSocket 服務器"
        
        # 啟動 Web 服務器
        Write-ColorOutput "🌐 啟動 Web 服務器..." "Yellow"
        Start-Process -FilePath "php" -ArgumentList "-S", "localhost:$WEB_PORT", "router.php" -WindowStyle Hidden
        Start-Sleep -Seconds 2
        
        # 啟動 WebSocket 服務器
        Write-ColorOutput "🔌 啟動 WebSocket 服務器..." "Yellow"
        Start-Process -FilePath "php" -ArgumentList "websocket/server.php" -WindowStyle Hidden
        Start-Sleep -Seconds 3
        
        # 顯示最終狀態
        Show-Status
        
        Write-ColorOutput "💡 提示: 使用 './manage-servers.ps1 stop' 來停止所有服務" "Yellow"
    }
    
    "stop" {
        Write-ColorOutput "🛑 停止 PythonLearn 服務器..." "Cyan"
        
        # 停止 WebSocket 服務器
        Stop-PortProcess -Port $WEBSOCKET_PORT -ServiceName "WebSocket 服務器"
        
        # 停止 Web 服務器
        Stop-PortProcess -Port $WEB_PORT -ServiceName "Web 服務器"
        
        Write-ColorOutput "🧹 清理完成" "Green"
        
        # 顯示最終狀態
        Show-Status
    }
    
    "restart" {
        Write-ColorOutput "🔄 重啟 PythonLearn 服務器..." "Cyan"
        & $PSCommandPath -Action "stop"
        Start-Sleep -Seconds 2
        & $PSCommandPath -Action "start"
    }
    
    "status" {
        Show-Status
    }
} 