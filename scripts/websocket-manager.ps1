# PythonLearn WebSocket服務管理器
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

# 檢查WebSocket服務器狀態
function Test-WebSocketServer {
    try {
        # 檢查端口8081是否被佔用
        $connection = Get-NetTCPConnection -LocalPort 8081 -ErrorAction SilentlyContinue
        if ($connection) {
            return @{
                IsRunning = $true
                ProcessId = $connection.OwningProcess
                Port = 8081
            }
        }
        
        # 檢查是否有WebSocket相關的PHP進程
        $processes = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object {
            $_.CommandLine -like "*websocket*" -or $_.CommandLine -like "*server.php*"
        }
        
        if ($processes) {
            return @{
                IsRunning = $true
                ProcessId = $processes[0].Id
                Port = 8081
            }
        }
        
        return @{
            IsRunning = $false
            ProcessId = $null
            Port = 8081
        }
    } catch {
        return @{
            IsRunning = $false
            ProcessId = $null
            Port = 8081
            Error = $_.Exception.Message
        }
    }
}

# 停止WebSocket服務器
function Stop-WebSocketServer {
    Write-ColorText "🛑 正在停止WebSocket服務器..." "Info"
    
    try {
        # 停止所有WebSocket相關的PHP進程
        $processes = Get-Process -Name "php" -ErrorAction SilentlyContinue
        $stoppedCount = 0
        
        foreach ($process in $processes) {
            try {
                # 檢查命令行是否包含websocket或server.php
                if ($process.CommandLine -like "*websocket*" -or $process.CommandLine -like "*server.php*") {
                    $process | Stop-Process -Force
                    $stoppedCount++
                    Write-ColorText "  ✅ 已停止進程 ID: $($process.Id)" "Success"
                }
            } catch {
                # 如果無法獲取CommandLine，嘗試停止所有PHP進程
                $process | Stop-Process -Force -ErrorAction SilentlyContinue
                $stoppedCount++
            }
        }
        
        # 清理端口8081
        $connections = Get-NetTCPConnection -LocalPort 8081 -ErrorAction SilentlyContinue
        if ($connections) {
            foreach ($conn in $connections) {
                Stop-Process -Id $conn.OwningProcess -Force -ErrorAction SilentlyContinue
            }
            Write-ColorText "  ✅ 端口8081已清理" "Success"
        }
        
        Start-Sleep -Seconds 2
        
        # 驗證停止
        $status = Test-WebSocketServer
        if (-not $status.IsRunning) {
            Write-ColorText "✅ WebSocket服務器已成功停止" "Success"
            return $true
        } else {
            Write-ColorText "⚠️ WebSocket服務器可能未完全停止" "Warning"
            return $false
        }
        
    } catch {
        Write-ColorText "❌ 停止WebSocket服務器時發生錯誤: $($_.Exception.Message)" "Error"
        return $false
    }
}

# 啟動WebSocket服務器
function Start-WebSocketServer {
    Write-ColorText "🚀 正在啟動WebSocket服務器..." "Info"
    
    try {
        # 檢查必要檔案
        if (-not (Test-Path "websocket/server.php")) {
            Write-ColorText "❌ 找不到WebSocket服務器檔案: websocket/server.php" "Error"
            return $false
        }
        
        # 檢查是否已經運行
        $status = Test-WebSocketServer
        if ($status.IsRunning) {
            Write-ColorText "⚠️ WebSocket服務器已經在運行中 (PID: $($status.ProcessId))" "Warning"
            return $true
        }
        
        # 啟動WebSocket服務器
        $process = Start-Process "php" -ArgumentList "websocket/server.php" -WindowStyle Hidden -PassThru
        Write-ColorText "  ✅ WebSocket服務器已啟動 (PID: $($process.Id))" "Success"
        
        # 等待服務器啟動
        Write-ColorText "  ⏳ 等待服務器就緒..." "Info"
        Start-Sleep -Seconds 3
        
        # 驗證啟動
        $status = Test-WebSocketServer
        if ($status.IsRunning) {
            Write-ColorText "✅ WebSocket服務器啟動成功，監聽端口8081" "Success"
            return $true
        } else {
            Write-ColorText "❌ WebSocket服務器啟動失敗" "Error"
            return $false
        }
        
    } catch {
        Write-ColorText "❌ 啟動WebSocket服務器時發生錯誤: $($_.Exception.Message)" "Error"
        return $false
    }
}

# 重啟WebSocket服務器
function Restart-WebSocketServer {
    Write-ColorText "🔄 正在重啟WebSocket服務器..." "Header"
    Write-Host ""
    
    $stopResult = Stop-WebSocketServer
    Start-Sleep -Seconds 2
    $startResult = Start-WebSocketServer
    
    if ($stopResult -and $startResult) {
        Write-ColorText "✅ WebSocket服務器重啟成功" "Success"
        return $true
    } else {
        Write-ColorText "❌ WebSocket服務器重啟失敗" "Error"
        return $false
    }
}

# 顯示WebSocket服務器狀態
function Show-WebSocketStatus {
    Write-ColorText "📊 WebSocket服務器狀態" "Header"
    Write-ColorText "========================" "Header"
    Write-Host ""
    
    $status = Test-WebSocketServer
    
    if ($status.IsRunning) {
        Write-ColorText "狀態: ✅ 運行中" "Success"
        Write-ColorText "端口: 8081 (佔用)" "Warning"
        if ($status.ProcessId) {
            Write-ColorText "進程ID: $($status.ProcessId)" "Info"
        }
        Write-ColorText "WebSocket地址: ws://localhost:8081" "Info"
    } else {
        Write-ColorText "狀態: ❌ 未運行" "Error"
        Write-ColorText "端口: 8081 (空閒)" "Info"
        if ($status.Error) {
            Write-ColorText "錯誤: $($status.Error)" "Error"
        }
    }
    
    Write-Host ""
}

# 測試WebSocket連接
function Test-WebSocketConnection {
    Write-ColorText "🔍 測試WebSocket連接..." "Info"
    
    $status = Test-WebSocketServer
    if (-not $status.IsRunning) {
        Write-ColorText "❌ WebSocket服務器未運行，無法測試連接" "Error"
        return $false
    }
    
    try {
        # 簡單的端口連接測試
        $tcpClient = New-Object System.Net.Sockets.TcpClient
        $tcpClient.Connect("localhost", 8081)
        $tcpClient.Close()
        
        Write-ColorText "✅ WebSocket服務器響應正常" "Success"
        return $true
    } catch {
        Write-ColorText "❌ WebSocket連接測試失敗: $($_.Exception.Message)" "Error"
        return $false
    }
}

# 顯示菜單
function Show-Menu {
    Clear-Host
    Write-ColorText "🔌 PythonLearn WebSocket管理器" "Header"
    Write-ColorText "===============================" "Header"
    Write-Host ""
    
    Write-ColorText "請選擇操作:" "Info"
    Write-ColorText "1. 檢查WebSocket狀態" "Info"
    Write-ColorText "2. 啟動WebSocket服務器" "Info"
    Write-ColorText "3. 停止WebSocket服務器" "Info"
    Write-ColorText "4. 重啟WebSocket服務器" "Info"
    Write-ColorText "5. 測試WebSocket連接" "Info"
    Write-ColorText "0. 退出" "Info"
    Write-Host ""
}

# 主程序
function Main {
    # 檢查專案目錄
    if (-not (Test-Path "websocket/server.php")) {
        Write-ColorText "❌ 錯誤: 請在PythonLearn專案根目錄中執行此腳本" "Error"
        Write-ColorText "📍 找不到檔案: websocket/server.php" "Info"
        Read-Host "按Enter鍵退出"
        exit 1
    }
    
    while ($true) {
        Show-Menu
        $choice = Read-Host "請選擇操作"
        
        switch ($choice) {
            "1" {
                Clear-Host
                Show-WebSocketStatus
                Read-Host "按Enter鍵繼續"
            }
            "2" {
                Clear-Host
                Start-WebSocketServer
                Write-Host ""
                Read-Host "按Enter鍵繼續"
            }
            "3" {
                Clear-Host
                Stop-WebSocketServer
                Write-Host ""
                Read-Host "按Enter鍵繼續"
            }
            "4" {
                Clear-Host
                Restart-WebSocketServer
                Write-Host ""
                Read-Host "按Enter鍵繼續"
            }
            "5" {
                Clear-Host
                Test-WebSocketConnection
                Write-Host ""
                Read-Host "按Enter鍵繼續"
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

# 如果直接執行此腳本，運行主程序
if ($MyInvocation.InvocationName -ne '.') {
    Main
} 