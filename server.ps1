param([string]$Action = "start")

function Test-Port($Port) {
    try {
        $result = @(Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue)
        return $result.Count -gt 0
    } catch {
        return $false
    }
}

function Stop-AllPHP {
    Write-Host "Stopping PHP servers..." -ForegroundColor Yellow
    Get-Process -Name "php" -ErrorAction SilentlyContinue | Stop-Process -Force
    Start-Sleep -Seconds 2
}

function Show-Status {
    Write-Host "`nPythonLearn Server Status" -ForegroundColor Cyan
    Write-Host "=========================" -ForegroundColor Cyan
    
    if (Test-Port 3306) {
        Write-Host "MySQL: Running (port 3306)" -ForegroundColor Green
    } else {
        Write-Host "MySQL: Not running" -ForegroundColor Red
    }
    
    if (Test-Port 8080) {
        Write-Host "Web Server: Running (http://localhost:8080)" -ForegroundColor Green
    } else {
        Write-Host "Web Server: Not running" -ForegroundColor Red
    }
    
    if (Test-Port 8081) {
        Write-Host "WebSocket: Running (ws://localhost:8081)" -ForegroundColor Green
    } else {
        Write-Host "WebSocket: Not running" -ForegroundColor Red
    }
    
    Write-Host "=========================`n" -ForegroundColor Cyan
}

switch ($Action) {
    "start" {
        Write-Host "Starting PythonLearn servers..." -ForegroundColor Cyan
        Stop-AllPHP
        
        if (Test-Port 3306) {
            Write-Host "MySQL is running" -ForegroundColor Green
        } else {
            Write-Host "WARNING: MySQL not running - start XAMPP MySQL" -ForegroundColor Yellow
        }
        
        Write-Host "Starting Web server..." -ForegroundColor Yellow
        Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8080", "router.php" -WindowStyle Hidden
        Start-Sleep -Seconds 2
        
        Write-Host "Starting WebSocket server..." -ForegroundColor Yellow
        Start-Process -FilePath "php" -ArgumentList "websocket/server.php" -WindowStyle Hidden
        Start-Sleep -Seconds 3
        
        Show-Status
        Write-Host "Use './server.ps1 stop' to stop all services" -ForegroundColor Yellow
    }
    
    "stop" {
        Write-Host "Stopping servers..." -ForegroundColor Cyan
        Stop-AllPHP
        Write-Host "All servers stopped!" -ForegroundColor Green
        Show-Status
    }
    
    "restart" {
        & $PSCommandPath "stop"
        Start-Sleep -Seconds 2
        & $PSCommandPath "start"
    }
    
    "status" {
        Show-Status
    }
    
    default {
        Write-Host "Usage: ./server.ps1 [start|stop|restart|status]" -ForegroundColor Yellow
    }
} 