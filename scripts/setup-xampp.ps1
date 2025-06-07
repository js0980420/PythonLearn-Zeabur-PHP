# XAMPP Environment Setup Script
# 優先使用 XAMPP MySQL 和 Apache，處理衝突

param(
    [switch]$Force,           # 強制停止衝突服務
    [switch]$ConfigureOnly,   # 只配置不啟動
    [switch]$Verbose          # 詳細輸出
)

# Set UTF-8 encoding
chcp 65001 | Out-Null

Clear-Host
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "XAMPP Environment Setup Script" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Get project root directory
$projectRoot = Split-Path -Parent $PSScriptRoot
$originalLocation = Get-Location

# XAMPP 路徑
$xamppPath = "C:\xampp"
$xamppControl = "$xamppPath\xampp-control.exe"

# 檢查 XAMPP 安裝
if (-not (Test-Path $xamppPath)) {
    Write-Host "ERROR: XAMPP not found at $xamppPath" -ForegroundColor Red
    Write-Host "Please install XAMPP first" -ForegroundColor Yellow
    exit 1
}

Write-Host "XAMPP installation found at: $xamppPath" -ForegroundColor Green

# 函數：檢查端口占用
function Test-PortOccupied {
    param([int]$Port)
    $result = netstat -ano | findstr ":$Port "
    return $result -ne $null -and $result.Count -gt 0
}

# 函數：獲取端口占用的進程
function Get-PortProcess {
    param([int]$Port)
    $connections = netstat -ano | findstr ":$Port "
    $processes = @()
    foreach ($line in $connections) {
        if ($line -match '\s+(\d+)$') {
            $processId = $matches[1]
            try {
                $process = Get-Process -Id $processId -ErrorAction SilentlyContinue
                if ($process) {
                    $processes += @{
                        PID = $processId
                        Name = $process.ProcessName
                        Path = $process.Path
                    }
                }
            } catch {}
        }
    }
    return $processes
}

# 函數：停止系統 MySQL 服務
function Stop-SystemMySQL {
    Write-Host "Checking system MySQL services..." -ForegroundColor Yellow
    
    # 檢查 Windows 服務
    $mysqlServices = Get-Service | Where-Object {$_.Name -match "mysql|MySQL"}
    foreach ($service in $mysqlServices) {
        if ($service.Status -eq "Running") {
            Write-Host "Found running MySQL service: $($service.Name)" -ForegroundColor Yellow
            if ($Force) {
                Write-Host "Stopping MySQL service: $($service.Name)" -ForegroundColor Red
                Stop-Service -Name $service.Name -Force -ErrorAction SilentlyContinue
                Start-Sleep -Seconds 3
            } else {
                Write-Host "Use -Force to stop system MySQL service" -ForegroundColor Blue
            }
        }
    }
    
    # 檢查 MySQL 進程
    $port3306Processes = Get-PortProcess -Port 3306
    foreach ($proc in $port3306Processes) {
        Write-Host "Port 3306 occupied by: $($proc.Name) (PID: $($proc.PID))" -ForegroundColor Yellow
        if ($proc.Path -and $proc.Path -notmatch "xampp") {
            Write-Host "  Path: $($proc.Path)" -ForegroundColor Gray
            if ($Force) {
                Write-Host "Terminating system MySQL process: $($proc.PID)" -ForegroundColor Red
                Stop-Process -Id $proc.PID -Force -ErrorAction SilentlyContinue
                Start-Sleep -Seconds 2
            }
        }
    }
}

# 函數：停止衝突的 Apache/Web 服務
function Stop-ConflictingWebServices {
    Write-Host "Checking conflicting web services..." -ForegroundColor Yellow
    
    # 檢查端口 80
    $port80Processes = Get-PortProcess -Port 80
    foreach ($proc in $port80Processes) {
        Write-Host "Port 80 occupied by: $($proc.Name) (PID: $($proc.PID))" -ForegroundColor Yellow
        if ($proc.Path) {
            Write-Host "  Path: $($proc.Path)" -ForegroundColor Gray
        }
        
        # 如果不是 XAMPP Apache，提示停止
        if ($proc.Path -and $proc.Path -notmatch "xampp" -and $proc.Name -match "nginx|httpd|apache") {
            if ($Force) {
                Write-Host "Terminating conflicting web service: $($proc.PID)" -ForegroundColor Red
                Stop-Process -Id $proc.PID -Force -ErrorAction SilentlyContinue
                Start-Sleep -Seconds 2
            } else {
                Write-Host "Use -Force to stop conflicting web services" -ForegroundColor Blue
            }
        }
    }
}

# 函數：配置 XAMPP MySQL 端口（如果需要）
function Configure-XAMPPMySQL {
    param([int]$NewPort = 3306)
    
    $mysqlConfigFile = "$xamppPath\mysql\bin\my.ini"
    
    if (Test-Path $mysqlConfigFile) {
        Write-Host "Configuring XAMPP MySQL..." -ForegroundColor Cyan
        
        # 備份原配置
        $backupFile = "$mysqlConfigFile.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
        Copy-Item $mysqlConfigFile $backupFile
        Write-Host "Backup created: $backupFile" -ForegroundColor Gray
        
        # 讀取配置文件
        $config = Get-Content $mysqlConfigFile
        $newConfig = @()
        
        foreach ($line in $config) {
            if ($line -match "^port\s*=\s*(\d+)") {
                $newConfig += "port=$NewPort"
                Write-Host "Updated MySQL port to: $NewPort" -ForegroundColor Green
            } else {
                $newConfig += $line
            }
        }
        
        # 寫入新配置
        $newConfig | Set-Content $mysqlConfigFile -Encoding UTF8
        
        return $NewPort
    } else {
        Write-Host "XAMPP MySQL config file not found" -ForegroundColor Red
        return $null
    }
}

# 函數：更新專案數據庫配置
function Update-ProjectDatabaseConfig {
    param([int]$MySQLPort)
    
    Set-Location $projectRoot
    
    Write-Host "Updating project database configuration..." -ForegroundColor Cyan
    
    # 更新 Database 類
    $databaseFile = "classes/Database.php"
    if (Test-Path $databaseFile) {
        $content = Get-Content $databaseFile -Raw
        
        # 更新 XAMPP 配置
        if ($content -match "('xampp_host'\s*=>\s*)'[^']*'") {
            $content = $content -replace "('xampp_host'\s*=>\s*)'[^']*'", "`$1'localhost:$MySQLPort'"
        }
        
        if ($content -match "('xampp_database'\s*=>\s*)'[^']*'") {
            $content = $content -replace "('xampp_database'\s*=>\s*)'[^']*'", "`$1'pythonlearn_collaboration'"
        }
        
        $content | Set-Content $databaseFile -Encoding UTF8
        Write-Host "Updated Database.php configuration" -ForegroundColor Green
    }
    
    # 創建 XAMPP 專用配置文件
    $xamppConfig = @"
<?php
// XAMPP Database Configuration
// Generated by setup-xampp.ps1

return [
    'host' => 'localhost:$MySQLPort',
    'database' => 'pythonlearn_collaboration',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
"@
    
    $xamppConfig | Set-Content "config/xampp-database.php" -Encoding UTF8
    Write-Host "Created config/xampp-database.php" -ForegroundColor Green
}

# 函數：創建數據庫和表
function Setup-Database {
    param([int]$MySQLPort)
    
    Write-Host "Setting up database schema..." -ForegroundColor Cyan
    
    $mysqlCmd = "$xamppPath\mysql\bin\mysql.exe"
    
    if (Test-Path $mysqlCmd) {
        # 創建數據庫
        $createDbSql = "CREATE DATABASE IF NOT EXISTS pythonlearn_collaboration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        
        # 創建表的 SQL
        $createTablesSql = @"
USE pythonlearn_collaboration;

-- 用戶會話表
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    room_id VARCHAR(255) NOT NULL,
    join_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_room_user (room_id, user_id),
    INDEX idx_user_active (user_id, is_active)
);

-- 房間信息表
CREATE TABLE IF NOT EXISTS rooms (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    user_count INT DEFAULT 0,
    current_code LONGTEXT,
    INDEX idx_activity (last_activity)
);

-- 代碼保存記錄表
CREATE TABLE IF NOT EXISTS code_saves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    code_content LONGTEXT NOT NULL,
    save_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    version_number INT DEFAULT 1,
    description VARCHAR(500),
    INDEX idx_room_time (room_id, save_time),
    INDEX idx_user_time (user_id, save_time)
);

-- 代碼編輯歷史表
CREATE TABLE IF NOT EXISTS code_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    change_type ENUM('edit', 'save', 'load', 'conflict_resolve') NOT NULL,
    old_content LONGTEXT,
    new_content LONGTEXT,
    change_position JSON,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_time (room_id, timestamp),
    INDEX idx_user_type (user_id, change_type)
);

-- 衝突解決記錄表
CREATE TABLE IF NOT EXISTS conflict_resolutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(255) NOT NULL,
    conflict_type VARCHAR(100) NOT NULL,
    main_user_id VARCHAR(255) NOT NULL,
    other_user_id VARCHAR(255) NOT NULL,
    resolution_action VARCHAR(100) NOT NULL,
    final_code LONGTEXT,
    resolved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_time (room_id, resolved_at)
);
"@
        
        # 執行 SQL
        try {
            Write-Host "Creating database..." -ForegroundColor Blue
            echo $createDbSql | & $mysqlCmd -u root -P $MySQLPort
            
            Write-Host "Creating tables..." -ForegroundColor Blue
            echo $createTablesSql | & $mysqlCmd -u root -P $MySQLPort
            
            Write-Host "Database setup completed successfully!" -ForegroundColor Green
            return $true
        } catch {
            Write-Host "Database setup failed: $($_.Exception.Message)" -ForegroundColor Red
            return $false
        }
    } else {
        Write-Host "MySQL command not found at: $mysqlCmd" -ForegroundColor Red
        return $false
    }
}

# 主程序開始
Write-Host "Current port status:" -ForegroundColor Cyan

# 檢查端口 3306
if (Test-PortOccupied -Port 3306) {
    Write-Host "  Port 3306 (MySQL): OCCUPIED" -ForegroundColor Red
    Stop-SystemMySQL
} else {
    Write-Host "  Port 3306 (MySQL): AVAILABLE" -ForegroundColor Green
}

# 檢查端口 80
if (Test-PortOccupied -Port 80) {
    Write-Host "  Port 80 (Apache): OCCUPIED" -ForegroundColor Red
    Stop-ConflictingWebServices
} else {
    Write-Host "  Port 80 (Apache): AVAILABLE" -ForegroundColor Green
}

Write-Host ""

# 配置 MySQL 端口
$mysqlPort = 3306
if (Test-PortOccupied -Port 3306) {
    Write-Host "Port 3306 still occupied, using alternative port 3307" -ForegroundColor Yellow
    $mysqlPort = 3307
    Configure-XAMPPMySQL -NewPort $mysqlPort
} else {
    Write-Host "Using default MySQL port 3306" -ForegroundColor Green
}

# 更新專案配置
Update-ProjectDatabaseConfig -MySQLPort $mysqlPort

if (-not $ConfigureOnly) {
    Write-Host "Starting XAMPP Control Panel..." -ForegroundColor Cyan
    
    # 啟動 XAMPP 控制面板
    if (Test-Path $xamppControl) {
        Start-Process $xamppControl
        Start-Sleep -Seconds 3
        Write-Host "XAMPP Control Panel started" -ForegroundColor Green
        
        Write-Host ""
        Write-Host "Please follow these steps:" -ForegroundColor Yellow
        Write-Host "1. Start Apache and MySQL from XAMPP Control Panel" -ForegroundColor White
        Write-Host "2. Wait for both services to show 'Running' status" -ForegroundColor White
        Write-Host "3. Test database connection at: http://localhost/phpmyadmin" -ForegroundColor White
        Write-Host "4. Run project startup script: .\scripts\start-simple.ps1" -ForegroundColor White
        
    } else {
        Write-Host "XAMPP Control Panel not found" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Database Information:" -ForegroundColor Yellow
Write-Host "  Host: localhost" -ForegroundColor White
Write-Host "  Port: 3306" -ForegroundColor White
Write-Host "  Database: pythonlearn_collaboration" -ForegroundColor White
Write-Host "  Username: root" -ForegroundColor White
Write-Host "  Password: (empty)" -ForegroundColor White

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan

# 恢復原始位置
Set-Location $originalLocation 