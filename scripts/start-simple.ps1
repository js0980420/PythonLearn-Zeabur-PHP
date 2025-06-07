# PythonLearn-Zeabur-PHP Startup Script (Simple Version)
param(
    [switch]$Clean,
    [switch]$OpenBrowser,
    [switch]$Verbose
)

# Set UTF-8 encoding
chcp 65001 | Out-Null

Clear-Host
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "PythonLearn-Zeabur-PHP Startup Script" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Get project root directory (parent of scripts)
$projectRoot = Split-Path -Parent $PSScriptRoot
$originalLocation = Get-Location
Set-Location $projectRoot

Write-Host "Project Directory: $projectRoot" -ForegroundColor Green

# Check necessary files
if (-not (Test-Path "router.php")) {
    Write-Host "ERROR: router.php not found" -ForegroundColor Red
    Write-Host "Please run script from correct directory" -ForegroundColor Yellow
    Set-Location $originalLocation
    Read-Host "Press any key to exit"
    exit 1
}

if (-not (Test-Path "websocket/server.php")) {
    Write-Host "ERROR: websocket/server.php not found" -ForegroundColor Red
    Set-Location $originalLocation
    Read-Host "Press any key to exit"
    exit 1
}

Write-Host "File check passed" -ForegroundColor Green
Write-Host ""

# Check if XAMPP is available and preferred
$xamppPath = "C:\xampp"
$useXAMPP = Test-Path $xamppPath

if ($useXAMPP) {
    Write-Host "XAMPP detected - checking services..." -ForegroundColor Cyan
    
    # Check if XAMPP MySQL is running
    $xamppMysqlRunning = $false
    $xamppApacheRunning = $false
    
    try {
        $mysqlProcess = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue | Where-Object {$_.Path -like "*xampp*"}
        $xamppMysqlRunning = $mysqlProcess -ne $null
        
        $apacheProcess = Get-Process -Name "httpd" -ErrorAction SilentlyContinue | Where-Object {$_.Path -like "*xampp*"}
        $xamppApacheRunning = $apacheProcess -ne $null
    } catch {}
    
    Write-Host "  XAMPP MySQL: " -NoNewline
    if ($xamppMysqlRunning) {
        Write-Host "✅ Running" -ForegroundColor Green
    } else {
        Write-Host "❌ Not running" -ForegroundColor Red
        Write-Host "  💡 Run scripts\setup-xampp.ps1 to configure XAMPP" -ForegroundColor Yellow
    }
    
    Write-Host "  XAMPP Apache: " -NoNewline
    if ($xamppApacheRunning) {
        Write-Host "✅ Running" -ForegroundColor Green
        Write-Host "  ℹ️  Project will use ports 8080/8081 to avoid conflict" -ForegroundColor Blue
    } else {
        Write-Host "⏹️ Not running (optional)" -ForegroundColor Gray
    }
    
    Write-Host ""
}

# Function to stop PHP processes
function Stop-PHPProcesses {
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        Write-Host "Cleaning $($phpProcesses.Count) PHP processes..." -ForegroundColor Yellow
        $phpProcesses | ForEach-Object {
            if ($Verbose) {
                Write-Host "   Terminating process: PID $($_.Id)" -ForegroundColor Gray
            }
            Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
        }
        Start-Sleep -Seconds 2
        Write-Host "PHP processes cleaned" -ForegroundColor Green
    } else {
        Write-Host "No running PHP processes found" -ForegroundColor Green
    }
}

# Function to check port occupation
function Test-PortOccupied {
    param([int]$Port)
    $result = netstat -ano | findstr ":$Port "
    return $result -ne $null -and $result.Count -gt 0
}

# Clean old processes
if ($Clean -or (Test-PortOccupied -Port 8080) -or (Test-PortOccupied -Port 8081)) {
    Stop-PHPProcesses
} else {
    Write-Host "Ports 8080 and 8081 are available" -ForegroundColor Green
}

Write-Host ""
Write-Host "Starting services..." -ForegroundColor Cyan
Write-Host ""

# Start Web server
Write-Host "Starting Web server (port 8080)..." -ForegroundColor Blue
$webProcess = Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8080", "router.php" -WorkingDirectory $projectRoot -PassThru -WindowStyle Normal

Start-Sleep -Seconds 3

# Start WebSocket server
Write-Host "Starting WebSocket server (port 8081)..." -ForegroundColor Blue
$wsProcess = Start-Process -FilePath "php" -ArgumentList "server.php" -WorkingDirectory "$projectRoot\websocket" -PassThru -WindowStyle Normal

Start-Sleep -Seconds 3

# Check service status
Write-Host ""
Write-Host "Checking service status..." -ForegroundColor Cyan

$webRunning = Test-PortOccupied -Port 8080
$wsRunning = Test-PortOccupied -Port 8081

if ($webRunning) {
    Write-Host "   Web server running (port 8080)" -ForegroundColor Green
} else {
    Write-Host "   Web server failed to start" -ForegroundColor Red
}

if ($wsRunning) {
    Write-Host "   WebSocket server running (port 8081)" -ForegroundColor Green
} else {
    Write-Host "   WebSocket server failed to start" -ForegroundColor Red
}

Write-Host ""

if ($webRunning -and $wsRunning) {
    Write-Host "All services started successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Service URLs:" -ForegroundColor Cyan
    Write-Host "   Web server: http://localhost:8080" -ForegroundColor White
    Write-Host "   WebSocket server: ws://localhost:8081" -ForegroundColor White
    Write-Host ""
    
    if ($OpenBrowser) {
        Write-Host "Opening browser..." -ForegroundColor Blue
        Start-Process "http://localhost:8080"
        Start-Sleep -Seconds 2
    }
    
    Write-Host "Tips:" -ForegroundColor Yellow
    Write-Host "   Web server process ID: $($webProcess.Id)" -ForegroundColor Gray
    Write-Host "   WebSocket server process ID: $($wsProcess.Id)" -ForegroundColor Gray
    Write-Host "   Run scripts\stop-simple.ps1 to stop services" -ForegroundColor Gray
    Write-Host ""
    
} else {
    Write-Host "Some services failed to start" -ForegroundColor Red
    Write-Host "Suggestion: scripts\start-simple.ps1 -Clean -Verbose" -ForegroundColor Yellow
}

# Show process info
if ($Verbose) {
    Write-Host ""
    Write-Host "Process Information:" -ForegroundColor Cyan
    if ($webProcess) {
        Write-Host "   Web server process ID: $($webProcess.Id)" -ForegroundColor Gray
    }
    if ($wsProcess) {
        Write-Host "   WebSocket server process ID: $($wsProcess.Id)" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan

# Restore original location
Set-Location $originalLocation 