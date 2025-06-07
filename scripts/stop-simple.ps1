# PythonLearn-Zeabur-PHP Stop Script (Simple Version)
param(
    [switch]$Force,    # Force terminate all PHP processes
    [switch]$Verbose   # Verbose output
)

# Set UTF-8 encoding
chcp 65001 | Out-Null

Write-Host "=====================================" -ForegroundColor Red
Write-Host "PythonLearn-Zeabur-PHP Stop Script" -ForegroundColor Red
Write-Host "=====================================" -ForegroundColor Red
Write-Host ""

# Function: Check port occupation
function Test-PortOccupied {
    param([int]$Port)
    $result = netstat -ano | findstr ":$Port "
    return $result -ne $null -and $result.Count -gt 0
}

# Function: Terminate processes using port
function Stop-PortProcess {
    param([int]$Port, [string]$ServiceName)
    $connections = netstat -ano | findstr ":$Port "
    if ($connections) {
        Write-Host "Found $ServiceName process (port $Port)..." -ForegroundColor Yellow
        foreach ($line in $connections) {
            if ($line -match '\s+(\d+)$') {
                $processId = $matches[1]
                try {
                    $process = Get-Process -Id $processId -ErrorAction SilentlyContinue
                    if ($process) {
                        Write-Host "Terminating process: $($process.ProcessName) (PID: $processId)" -ForegroundColor Yellow
                        Stop-Process -Id $processId -Force -ErrorAction SilentlyContinue
                        Start-Sleep -Milliseconds 500
                        
                        if ($Verbose) {
                            Write-Host "   Process $processId terminated" -ForegroundColor Green
                        }
                    }
                } catch {
                    if ($Verbose) {
                        Write-Host "   Cannot terminate process PID: $processId" -ForegroundColor Yellow
                    }
                }
            }
        }
    } else {
        if ($Verbose) {
            Write-Host "Port $Port is not occupied" -ForegroundColor Green
        }
    }
}

# Check current service status
$webRunning = Test-PortOccupied -Port 8080
$wsRunning = Test-PortOccupied -Port 8081

if (-not $webRunning -and -not $wsRunning) {
    Write-Host "No running services found" -ForegroundColor Green
    Write-Host ""
    Write-Host "=====================================" -ForegroundColor Red
    exit 0
}

Write-Host "Detected running services..." -ForegroundColor Cyan
if ($webRunning) {
    Write-Host "   Web server (port 8080)" -ForegroundColor Blue
}
if ($wsRunning) {
    Write-Host "   WebSocket server (port 8081)" -ForegroundColor Blue
}

Write-Host ""

# Stop specific port services
if ($webRunning) {
    Write-Host "Stopping Web server..." -ForegroundColor Red
    Stop-PortProcess -Port 8080 -ServiceName "Web Server"
}

if ($wsRunning) {
    Write-Host "Stopping WebSocket server..." -ForegroundColor Red
    Stop-PortProcess -Port 8081 -ServiceName "WebSocket Server"
}

# Force mode: Stop all PHP processes
if ($Force) {
    Write-Host ""
    Write-Host "Force mode: Stopping all PHP processes..." -ForegroundColor Red
    $phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
    if ($phpProcesses) {
        Write-Host "   Found $($phpProcesses.Count) PHP processes" -ForegroundColor Yellow
        $phpProcesses | ForEach-Object {
            if ($Verbose) {
                Write-Host "   Terminating: $($_.ProcessName) (PID: $($_.Id))" -ForegroundColor Yellow
            }
            Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
        }
    } else {
        Write-Host "   No PHP processes found" -ForegroundColor Green
    }
}

# Wait for processes to fully terminate
Start-Sleep -Seconds 2

Write-Host ""
Write-Host "Verifying stop results..." -ForegroundColor Cyan

$webStillRunning = Test-PortOccupied -Port 8080
$wsStillRunning = Test-PortOccupied -Port 8081

if (-not $webStillRunning -and -not $wsStillRunning) {
    Write-Host "All services stopped successfully!" -ForegroundColor Green
} else {
    Write-Host "Some services may still be running:" -ForegroundColor Yellow
    if ($webStillRunning) {
        Write-Host "   Web server (port 8080) still running" -ForegroundColor Yellow
    }
    if ($wsStillRunning) {
        Write-Host "   WebSocket server (port 8081) still running" -ForegroundColor Yellow
    }
    Write-Host "   Suggestion: Use -Force parameter" -ForegroundColor Blue
}

Write-Host ""
Write-Host "Stop script completed!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Red 