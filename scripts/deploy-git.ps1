# PythonLearn Git一鍵部署腳本
# 版本: v2.0
# 更新: 2025-06-07

# UTF-8 編碼設定
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
chcp 65001 > $null

# 顏色輸出函數
function Write-ColorText {
    param(
        [string]$Text,
        [string]$Color = "White"
    )
    
    $colorMap = @{
        "Success" = "Green"
        "Error" = "Red"
        "Warning" = "Yellow"
        "Info" = "Cyan"
        "Header" = "Magenta"
        "White" = "White"
    }
    
    $consoleColor = $colorMap[$Color]
    if (-not $consoleColor) { $consoleColor = "White" }
    
    Write-Host $Text -ForegroundColor $consoleColor
}

# 檢查Git是否可用
function Test-GitAvailable {
    try {
        $null = git --version 2>$null
        if ($LASTEXITCODE -eq 0) {
            return $true
        }
        return $false
    } catch {
        Write-ColorText "❌ 錯誤: Git未安裝或無法執行" "Error"
        return $false
    }
}

# 檢查是否在Git倉庫中
function Test-GitRepository {
    try {
        $null = git rev-parse --git-dir 2>$null
        if ($LASTEXITCODE -eq 0) {
            return $true
        }
        Write-ColorText "❌ 錯誤: 當前目錄不是Git倉庫" "Error"
        return $false
    } catch {
        Write-ColorText "❌ 錯誤: 無法檢查Git倉庫狀態" "Error"
        return $false
    }
}

# 檢查是否有變更
function Test-GitChanges {
    $gitStatus = git status --porcelain 2>$null
    return -not [string]::IsNullOrWhiteSpace($gitStatus)
}

# 顯示變更摘要
function Show-GitChanges {
    Write-ColorText "📋 變更摘要:" "Info"
    
    # 顯示修改的檔案
    $modifiedFiles = git diff --name-only HEAD 2>$null
    if ($modifiedFiles) {
        Write-ColorText "  📝 已修改的檔案:" "Info"
        foreach ($file in $modifiedFiles) {
            Write-ColorText "    - $file" "Warning"
        }
    }
    
    # 顯示新增的檔案
    $newFiles = git ls-files --others --exclude-standard 2>$null
    if ($newFiles) {
        Write-ColorText "  ➕ 新增的檔案:" "Info"
        foreach ($file in $newFiles) {
            Write-ColorText "    + $file" "Success"
        }
    }
    
    # 顯示刪除的檔案
    $deletedFiles = git ls-files --deleted 2>$null
    if ($deletedFiles) {
        Write-ColorText "  ❌ 已刪除的檔案:" "Info"
        foreach ($file in $deletedFiles) {
            Write-ColorText "    - $file" "Error"
        }
    }
}

# 執行Git部署
function Deploy-ToGit {
    param(
        [string]$CommitMessage,
        [switch]$Force
    )
    
    try {
        Write-ColorText "📤 開始Git部署流程..." "Header"
        Write-Host ""
        
        # 檢查Git倉庫
        if (-not (Test-GitRepository)) {
            return $false
        }
        
        # 檢查是否有變更
        if (-not (Test-GitChanges)) {
            Write-ColorText "✅ 沒有變更需要提交" "Success"
            Write-ColorText "📊 當前分支狀態已是最新" "Info"
            return $true
        }
        
        # 顯示變更摘要
        Show-GitChanges
        Write-Host ""
        
        # 確認部署
        if (-not $Force) {
            $confirm = Read-Host "是否繼續部署? (y/N)"
            if ($confirm -ne "y" -and $confirm -ne "Y") {
                Write-ColorText "❌ 部署已取消" "Warning"
                return $false
            }
        }
        
        Write-ColorText "🔄 正在添加變更..." "Info"
        git add . 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Write-ColorText "❌ 添加變更失敗" "Error"
            return $false
        }
        Write-ColorText "  ✅ 變更已添加" "Success"
        
        Write-ColorText "📝 正在提交變更..." "Info"
        git commit -m $CommitMessage 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Write-ColorText "❌ 提交失敗" "Error"
            return $false
        }
        Write-ColorText "  ✅ 變更已提交" "Success"
        
        Write-ColorText "🚀 正在推送到遠端倉庫..." "Info"
        $pushResult = git push origin main 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-ColorText "❌ 推送失敗:" "Error"
            Write-ColorText "$pushResult" "Error"
            
            # 嘗試推送到其他分支
            $currentBranch = git branch --show-current 2>$null
            if ($currentBranch -and $currentBranch -ne "main") {
                Write-ColorText "🔄 嘗試推送到當前分支: $currentBranch" "Info"
                $pushResult = git push origin $currentBranch 2>&1
                if ($LASTEXITCODE -eq 0) {
                    Write-ColorText "  ✅ 推送到 $currentBranch 成功" "Success"
                    return $true
                }
            }
            return $false
        }
        Write-ColorText "  ✅ 推送成功" "Success"
        
        Write-Host ""
        Write-ColorText "🎉 Git部署完成！" "Success"
        Write-ColorText "📊 提交訊息: $CommitMessage" "Info"
        
        # 顯示遠端倉庫資訊
        $remoteUrl = git config --get remote.origin.url 2>$null
        if ($remoteUrl) {
            Write-ColorText "🌐 遠端倉庫: $remoteUrl" "Info"
        }
        
        return $true
        
    } catch {
        Write-ColorText "❌ 部署過程中發生錯誤: $($_.Exception.Message)" "Error"
        return $false
    }
}

# 生成自動提交訊息
function Get-AutoCommitMessage {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $changedFiles = git diff --name-only HEAD 2>$null
    $newFiles = git ls-files --others --exclude-standard 2>$null
    
    $fileCount = 0
    if ($changedFiles) { $fileCount += $changedFiles.Count }
    if ($newFiles) { $fileCount += $newFiles.Count }
    
    $message = "🚀 自動部署更新"
    
    if ($fileCount -gt 0) {
        $message += " - 更新 $fileCount 個檔案"
    }
    
    $message += " ($timestamp)"
    
    return $message
}

# 主程序
function Main {
    Clear-Host
    Write-ColorText "🚀 PythonLearn Git部署工具" "Header"
    Write-ColorText "================================" "Header"
    Write-Host ""
    
    # 檢查Git是否可用
    if (-not (Test-GitAvailable)) {
        Read-Host "按Enter鍵退出"
        exit 1
    }
    
    # 檢查專案目錄
    if (-not (Test-Path "public/index.html") -or -not (Test-Path "websocket/server.php")) {
        Write-ColorText "❌ 錯誤: 請在PythonLearn專案根目錄中執行此腳本" "Error"
        Read-Host "按Enter鍵退出"
        exit 1
    }
    
    # 獲取提交訊息
    Write-ColorText "📝 請輸入提交訊息:" "Info"
    Write-ColorText "   (按Enter使用自動生成的訊息)" "Info"
    Write-Host ""
    
    $message = Read-Host "提交訊息"
    
    if ([string]::IsNullOrWhiteSpace($message)) {
        $message = Get-AutoCommitMessage
        Write-ColorText "🤖 使用自動生成的訊息: $message" "Info"
    }
    
    Write-Host ""
    
    # 執行部署
    $success = Deploy-ToGit -CommitMessage $message
    
    Write-Host ""
    if ($success) {
        Write-ColorText "✅ 部署成功完成！" "Success"
    } else {
        Write-ColorText "❌ 部署失敗，請檢查錯誤訊息" "Error"
    }
    
    Read-Host "按Enter鍵退出"
}

# 執行主程序
Main 