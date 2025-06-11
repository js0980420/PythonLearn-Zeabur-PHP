# Quick Dual Cursor Launcher
# Fast launch for two Cursor instances

Write-Host "Quick Dual Cursor Launcher" -ForegroundColor Cyan

# Check Cursor path
$cursorPath = "$env:LOCALAPPDATA\Programs\Cursor\Cursor.exe"
if (-not (Test-Path $cursorPath)) {
    Write-Host "ERROR: Cannot find Cursor, please check installation path" -ForegroundColor Red
    exit 1
}

# Get current project path
$currentPath = Get-Location

Write-Host "Current Project: $currentPath" -ForegroundColor Yellow

# Ask for second project
Write-Host ""
Write-Host "Choose second project:" -ForegroundColor Yellow
Write-Host "1. Same project (different window)" -ForegroundColor White
Write-Host "2. Choose other project" -ForegroundColor White
Write-Host "3. Create new project" -ForegroundColor White

$choice = Read-Host "Choose (1-3)"

switch ($choice) {
    "1" {
        $secondPath = $currentPath
        Write-Host "OK: Will open two windows of same project" -ForegroundColor Green
    }
    "2" {
        $secondPath = Read-Host "Enter second project path"
        if (-not (Test-Path $secondPath)) {
            Write-Host "WARNING: Path not exists, using current project" -ForegroundColor Yellow
            $secondPath = $currentPath
        }
    }
    "3" {
        $projectName = Read-Host "Enter new project name"
        $secondPath = Join-Path (Split-Path $currentPath -Parent) $projectName
        if (-not (Test-Path $secondPath)) {
            New-Item -ItemType Directory -Path $secondPath -Force | Out-Null
            Write-Host "OK: Created new project: $secondPath" -ForegroundColor Green
        }
    }
    default {
        $secondPath = $currentPath
    }
}

# Launch two Cursor instances
Write-Host ""
Write-Host "Launching Cursor instances..." -ForegroundColor Cyan

try {
    # First instance
    Start-Process -FilePath $cursorPath -ArgumentList $currentPath.Path -WindowStyle Normal
    Write-Host "OK: Cursor #1 launched: $(Split-Path $currentPath -Leaf)" -ForegroundColor Green
    
    # Wait to avoid conflicts
    Start-Sleep -Seconds 2
    
    # Second instance
    Start-Process -FilePath $cursorPath -ArgumentList $secondPath -WindowStyle Normal
    Write-Host "OK: Cursor #2 launched: $(Split-Path $secondPath -Leaf)" -ForegroundColor Green
    
    # Auto-start development server
    if (Test-Path "public\index.php") {
        Write-Host ""
        Write-Host "Detected PHP project, starting dev server..." -ForegroundColor Yellow
        Start-Sleep -Seconds 1
        Start-Process -FilePath "php" -ArgumentList "-S", "localhost:8080", "-t", "public" -WindowStyle Normal
        Write-Host "OK: PHP Server: http://localhost:8080" -ForegroundColor Green
    }
    
    # Start terminal
    Write-Host "Starting development terminal..." -ForegroundColor Yellow
    Start-Process -FilePath "wt" -ArgumentList "-d", $currentPath.Path -WindowStyle Normal
    
    Write-Host ""
    Write-Host "SUCCESS: Dual Cursor development environment ready!" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Tips:" -ForegroundColor Yellow
    Write-Host "* Two Cursor instances can edit different files simultaneously" -ForegroundColor White
    Write-Host "* Use Git branches for collaborative development" -ForegroundColor White
    Write-Host "* One for development, one for testing" -ForegroundColor White
    Write-Host "* Use Ctrl+E to start Background Agent" -ForegroundColor White
    
}
catch {
    Write-Host "ERROR: Launch failed: $($_.Exception.Message)" -ForegroundColor Red
} 