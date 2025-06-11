# Cursor Background Agent Setup Script for PythonLearn-Zeabur-PHP
# Resolves "Failed to create default environment: No remote ref found" error

Write-Host "🔧 Cursor Background Agent Setup for PythonLearn-Zeabur-PHP" -ForegroundColor Cyan
Write-Host "=============================================================" -ForegroundColor Cyan
Write-Host ""

# Function to check if command exists
function Test-Command($cmdname) {
    return [bool](Get-Command -Name $cmdname -ErrorAction SilentlyContinue)
}

# Step 1: Prerequisites Check
Write-Host "📋 Step 1: Checking Prerequisites..." -ForegroundColor Yellow

if (-not (Test-Command "git")) {
    Write-Host "❌ Git is not installed or not in PATH" -ForegroundColor Red
    Write-Host "   Please install Git for Windows from https://git-scm.com/" -ForegroundColor White
    exit 1
}

$gitVersion = git --version
Write-Host "✅ Git available: $gitVersion" -ForegroundColor Green

# Step 2: Git Repository Status
Write-Host "`n📦 Step 2: Checking Git Repository Status..." -ForegroundColor Yellow

try {
    $gitStatus = git status --porcelain 2>&1
    Write-Host "✅ Git repository detected" -ForegroundColor Green
    
    # Check remote repository
    $remotes = git remote -v 2>&1
    if ($remotes -match "origin.*github.com") {
        Write-Host "✅ GitHub remote repository configured" -ForegroundColor Green
        Write-Host "   Repository: https://github.com/js0980420/PythonLearn-Zeabur-PHP.git" -ForegroundColor White
    }
    else {
        Write-Host "❌ No GitHub remote repository found" -ForegroundColor Red
        exit 1
    }
    
    # Check commit history
    $commits = git log --oneline -5 2>&1
    if ($commits) {
        Write-Host "✅ Commit history exists" -ForegroundColor Green
        Write-Host "   Latest commits:" -ForegroundColor White
        git log --oneline -3 | ForEach-Object { Write-Host "     $_" -ForegroundColor Gray }
    }
    
}
catch {
    Write-Host "❌ Not a Git repository or Git error: $_" -ForegroundColor Red
    exit 1
}

# Step 3: Ensure everything is pushed
Write-Host "`n🚀 Step 3: Ensuring Repository is Up-to-Date..." -ForegroundColor Yellow

try {
    # Check if there are unpushed commits
    $unpushed = git log origin/main..HEAD --oneline 2>&1
    if ($unpushed -and $unpushed -notmatch "fatal") {
        Write-Host "📤 Found unpushed commits, pushing to GitHub..." -ForegroundColor Yellow
        git push origin main
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Successfully pushed to GitHub" -ForegroundColor Green
        }
        else {
            Write-Host "❌ Failed to push to GitHub" -ForegroundColor Red
            Write-Host "   Please resolve any push issues before continuing" -ForegroundColor White
            exit 1
        }
    }
    else {
        Write-Host "✅ Repository is up-to-date with GitHub" -ForegroundColor Green
    }
}
catch {
    Write-Host "⚠️  Warning: Could not check push status: $_" -ForegroundColor Yellow
}

# Step 4: Create Cursor Configuration
Write-Host "`n⚙️  Step 4: Creating Cursor Configuration..." -ForegroundColor Yellow

# Create .cursor directory if it doesn't exist
if (-not (Test-Path ".cursor")) {
    New-Item -ItemType Directory -Path ".cursor" -Force | Out-Null
    Write-Host "✅ Created .cursor directory" -ForegroundColor Green
}
else {
    Write-Host "✅ .cursor directory already exists" -ForegroundColor Green
}

# Create environment.json for Background Agent
$cursorConfig = @{
    name        = "default"
    runtime     = @{
        type    = "node"
        version = "18"
    }
    setup       = @{
        install = @("npm install")
        start   = @("npm start")
    }
    environment = @{
        PHP_VERSION = "8.4"
        SERVER_PORT = "8000"
    }
} | ConvertTo-Json -Depth 3

Set-Content -Path ".cursor/environment.json" -Value $cursorConfig -Encoding UTF8
Write-Host "✅ Created .cursor/environment.json" -ForegroundColor Green

# Step 5: Verify PHP environment
Write-Host "`n🐘 Step 5: Verifying PHP Environment..." -ForegroundColor Yellow

if (Test-Command "php") {
    $phpVersion = php --version | Select-Object -First 1
    Write-Host "✅ PHP available: $phpVersion" -ForegroundColor Green
}
else {
    Write-Host "⚠️  PHP not found in PATH (this is okay for Background Agent)" -ForegroundColor Yellow
}

# Step 6: Instructions for Cursor
Write-Host "`n🎯 Step 6: Next Steps in Cursor..." -ForegroundColor Yellow
Write-Host "Now perform these steps in Cursor:" -ForegroundColor White
Write-Host ""
Write-Host "1. Open Cursor and load this project" -ForegroundColor Cyan
Write-Host "2. Press Ctrl+Shift+P (or Cmd+Shift+P on Mac)" -ForegroundColor Cyan
Write-Host "3. Type 'Background Agent' and select 'Setup Background Agent'" -ForegroundColor Cyan
Write-Host "4. OR click the cloud icon in the chat interface" -ForegroundColor Cyan
Write-Host "5. Make sure you're signed into GitHub in Cursor" -ForegroundColor Cyan
Write-Host "6. Follow the setup wizard" -ForegroundColor Cyan

Write-Host "`n📝 Troubleshooting Tips:" -ForegroundColor Yellow
Write-Host "• If it still fails, try disconnecting and reconnecting GitHub in Cursor Settings" -ForegroundColor White
Write-Host "• Ensure your GitHub account has access to this repository" -ForegroundColor White
Write-Host "• Try using a Personal Access Token if organization repo causes issues" -ForegroundColor White
Write-Host "• Check Cursor logs for more detailed error messages" -ForegroundColor White

Write-Host "`n💰 Cost Warning:" -ForegroundColor Yellow
Write-Host "Background Agent uses 'Max Mode' which can be expensive!" -ForegroundColor Red
Write-Host "Monitor your usage in Cursor Settings > Billing" -ForegroundColor White

Write-Host "`n✅ Setup Complete!" -ForegroundColor Green
Write-Host "Your repository is now properly configured for Cursor Background Agent." -ForegroundColor White
Write-Host ""

# Optional: Open GitHub repository
$openGitHub = Read-Host "Open GitHub repository to verify? (y/N)"
if ($openGitHub -eq "y" -or $openGitHub -eq "Y") {
    Start-Process "https://github.com/js0980420/PythonLearn-Zeabur-PHP"
}

Write-Host "Script completed! 🎉" -ForegroundColor Green 