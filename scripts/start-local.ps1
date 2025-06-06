# PythonLearn 本地開發環境一鍵啟動腳本
# 版本: v3.1
# 編碼: UTF-8

# 設定編碼
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$Host.UI.RawUI.WindowTitle = "PythonLearn 本地開發環境"

# 顏色輸出函數
function Write-ColorText {
    param(
        [string]$Text,
        [string]$Type = "Info"
    )
    
    switch ($Type) {
        "Success" { Write-Host $Text -ForegroundColor Green }
        "Warning" { Write-Host $Text -ForegroundColor Yellow }
        "Error" { Write-Host $Text -ForegroundColor Red }
        "Info" { Write-Host $Text -ForegroundColor Cyan }
        "Header" { Write-Host $Text -ForegroundColor Magenta }
        default { Write-Host $Text }
    }
}

# 檢查端口是否被佔用
function Test-Port {
    param([int]$Port)
    
    try {
        $connection = New-Object System.Net.Sockets.TcpClient
        $connection.Connect("localhost", $Port)
        $connection.Close()
        return $true
    } catch {
        return $false
    }
}

# 清理佔用的進程
function Stop-ProcessByPort {
    param([int]$Port)
    
    try {
        $processes = netstat -ano | findstr ":$Port"
        if ($processes) {
            Write-ColorText "發現端口 $Port 被佔用，正在清理..." "Warning"
            
            $pids = $processes | ForEach-Object {
                if ($_ -match '\s+(\d+)$') { $matches[1] }
            } | Select-Object -Unique
            
            foreach ($pid in $pids) {
                try {
                    Stop-Process -Id $pid -Force -ErrorAction SilentlyContinue
                    Write-ColorText "已終止進程 PID: $pid" "Success"
                } catch {
                    Write-ColorText "無法終止進程 PID: $pid" "Warning"
                }
            }
        }
    } catch {
        Write-ColorText "清理端口 $Port 時發生錯誤" "Warning"
    }
}

# 檢查 XAMPP 安裝路徑
function Get-XamppPath {
    $possiblePaths = @(
        "C:\xampp",
        "C:\XAMPP",
        "D:\xampp",
        "D:\XAMPP"
    )
    
    foreach ($path in $possiblePaths) {
        if (Test-Path "$path\apache\bin\httpd.exe") {
            return $path
        }
    }
    
    return $null
}

# 檢查專案目錄
function Test-ProjectDirectory {
    $requiredFiles = @("public/index.html", "websocket/server.php", "router.php")
    
    foreach ($file in $requiredFiles) {
        if (-not (Test-Path $file)) {
            Write-ColorText "錯誤: 未在PythonLearn專案根目錄中執行" "Error"
            Write-ColorText "請確保在包含以下檔案的目錄中執行此腳本:" "Info"
            foreach ($f in $requiredFiles) {
                Write-ColorText "   - $f" "Info"
            }
            Write-Host ""
            Write-ColorText "提示: 請切換到專案根目錄後重新執行" "Warning"
            Read-Host "按Enter鍵退出"
            exit 1
        }
    }
}

# 主要啟動流程
function Start-LocalEnvironment {
    Write-ColorText "PythonLearn 本地開發環境啟動中..." "Header"
    Write-Host ""
    
    try {
        # 1. 清理佔用的端口
        Write-ColorText "清理佔用的端口..." "Info"
        Stop-ProcessByPort 8080
        Stop-ProcessByPort 8081
        Stop-ProcessByPort 8082
        Stop-ProcessByPort 3306
        
        Start-Sleep -Seconds 2
        
        # 2. 檢查 XAMPP
        Write-ColorText "檢查 XAMPP 安裝..." "Info"
        $xamppPath = Get-XamppPath
        
        if ($xamppPath) {
            Write-ColorText "找到 XAMPP 安裝路徑: $xamppPath" "Success"
            
            # 啟動 Apache
            Write-ColorText "啟動 Apache 服務器 (端口 8082)..." "Info"
            Start-Process -FilePath "$xamppPath\apache\bin\httpd.exe" -WindowStyle Hidden
            
            # 啟動 MySQL
            Write-ColorText "啟動 MySQL 服務器 (端口 3306)..." "Info"
            Start-Process -FilePath "$xamppPath\mysql\bin\mysqld.exe" -WindowStyle Hidden
            
        } else {
            Write-ColorText "未找到 XAMPP 安裝，跳過 Apache 和 MySQL 啟動" "Warning"
        }
        
        # 3. 啟動 PHP 內建服務器
        Write-ColorText "啟動 PHP 內建服務器 (端口 8080)..." "Info"
        Start-Process -FilePath "php" -ArgumentList "-S localhost:8080 router.php" -WindowStyle Minimized
        
        # 4. 啟動 WebSocket 服務器
        Write-ColorText "啟動 WebSocket 服務器 (端口 8081)..." "Info"
        Start-Process -FilePath "php" -ArgumentList "websocket/server.php" -WindowStyle Minimized
        
        # 5. 等待服務啟動
        Write-ColorText "等待服務啟動..." "Info"
        Start-Sleep -Seconds 5
        
        # 6. 檢查服務狀態
        Write-ColorText "檢查服務狀態..." "Info"
        
        if (Test-Port 8080) {
            Write-ColorText "PHP 服務器 (8080) 運行正常" "Success"
        } else {
            Write-ColorText "PHP 服務器 (8080) 啟動失敗" "Error"
        }
        
        if (Test-Port 8081) {
            Write-ColorText "WebSocket 服務器 (8081) 運行正常" "Success"
        } else {
            Write-ColorText "WebSocket 服務器 (8081) 啟動失敗" "Error"
        }
        
        if ($xamppPath) {
            if (Test-Port 8082) {
                Write-ColorText "Apache 服務器 (8082) 運行正常" "Success"
            } else {
                Write-ColorText "Apache 服務器 (8082) 啟動失敗" "Error"
            }
            
            if (Test-Port 3306) {
                Write-ColorText "MySQL 服務器 (3306) 運行正常" "Success"
            } else {
                Write-ColorText "MySQL 服務器 (3306) 啟動失敗" "Error"
            }
        }
        
        # 7. 開啟瀏覽器
        Write-ColorText "開啟瀏覽器..." "Info"
        Start-Process "http://localhost:8080"
        
        Write-Host ""
        Write-ColorText "本地開發環境啟動完成！" "Success"
        Write-ColorText "主應用: http://localhost:8080" "Info"
        if ($xamppPath) {
            Write-ColorText "XAMPP 控制台: http://localhost:8082" "Info"
        }
        Write-ColorText "WebSocket: ws://localhost:8081" "Info"
        
        Write-Host ""
        Write-ColorText "按任意鍵退出..." "Info"
        $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
        
    } catch {
        Write-ColorText "啟動過程中發生錯誤: $($_.Exception.Message)" "Error"
        Write-Host ""
        Write-ColorText "按任意鍵退出..." "Info"
        $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    }
}

# 主程序入口
try {
    # 檢查專案目錄
    Test-ProjectDirectory
    
    # 執行啟動流程
    Start-LocalEnvironment
    
} catch {
    Write-ColorText "啟動失敗: $($_.Exception.Message)" "Error"
    Read-Host "按Enter鍵退出"
    exit 1
} 