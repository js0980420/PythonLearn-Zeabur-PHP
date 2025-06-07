# PythonLearn 舊腳本清理工具
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

# 定義要清理的檔案模式
$CleanupPatterns = @{
    "測試HTML檔案" = @(
        "test-*.html",
        "*test*.html",
        "debug-*.html",
        "temp-*.html"
    )
    "測試PHP檔案" = @(
        "test-*.php",
        "debug-*.php", 
        "temp-*.php",
        "*-test.php"
    )
    "測試JavaScript檔案" = @(
        "test-*.js",
        "debug-*.js",
        "temp-*.js",
        "fix-*.js"
    )
    "舊腳本檔案" = @(
        "setup-xampp.ps1",
        "old-*.ps1",
        "backup-*.ps1"
    )
    "臨時檔案" = @(
        "temp_*.bat",
        "*.tmp",
        "*.temp",
        "~*"
    )
    "日誌檔案" = @(
        "*.log",
        "error.txt",
        "debug.txt"
    )
}

# 掃描要清理的檔案
function Get-FilesToClean {
    $filesToClean = @{}
    
    foreach ($category in $CleanupPatterns.GetEnumerator()) {
        $categoryName = $category.Key
        $patterns = $category.Value
        $foundFiles = @()
        
        foreach ($pattern in $patterns) {
            try {
                $files = Get-ChildItem -Path . -Name $pattern -Recurse -ErrorAction SilentlyContinue
                if ($files) {
                    $foundFiles += $files
                }
            } catch {
                # 忽略掃描錯誤
            }
        }
        
        if ($foundFiles.Count -gt 0) {
            $filesToClean[$categoryName] = $foundFiles | Sort-Object | Get-Unique
        }
    }
    
    return $filesToClean
}

# 顯示清理預覽
function Show-CleanupPreview {
    param([hashtable]$FilesToClean)
    
    if ($FilesToClean.Count -eq 0) {
        Write-ColorText "✅ 沒有找到需要清理的檔案" "Success"
        return $false
    }
    
    Write-ColorText "📋 發現以下檔案可以清理:" "Info"
    Write-Host ""
    
    $totalFiles = 0
    foreach ($category in $FilesToClean.GetEnumerator()) {
        $categoryName = $category.Key
        $files = $category.Value
        
        Write-ColorText "📁 $categoryName ($($files.Count) 個檔案):" "Warning"
        foreach ($file in $files) {
            Write-ColorText "   - $file" "Info"
        }
        Write-Host ""
        $totalFiles += $files.Count
    }
    
    Write-ColorText "📊 總計: $totalFiles 個檔案" "Header"
    return $true
}

# 執行清理
function Remove-OldFiles {
    param(
        [hashtable]$FilesToClean,
        [switch]$Force
    )
    
    if ($FilesToClean.Count -eq 0) {
        return $true
    }
    
    # 確認清理
    if (-not $Force) {
        Write-Host ""
        $confirm = Read-Host "是否確定要刪除這些檔案? (y/N)"
        if ($confirm -ne "y" -and $confirm -ne "Y") {
            Write-ColorText "❌ 清理已取消" "Warning"
            return $false
        }
    }
    
    Write-Host ""
    Write-ColorText "🧹 開始清理檔案..." "Info"
    
    $successCount = 0
    $errorCount = 0
    
    foreach ($category in $FilesToClean.GetEnumerator()) {
        $categoryName = $category.Key
        $files = $category.Value
        
        Write-ColorText "🔄 清理 $categoryName..." "Info"
        
        foreach ($file in $files) {
            try {
                if (Test-Path $file) {
                    Remove-Item $file -Force -Recurse
                    Write-ColorText "  ✅ 已刪除: $file" "Success"
                    $successCount++
                } else {
                    Write-ColorText "  ⚠️ 檔案不存在: $file" "Warning"
                }
            } catch {
                Write-ColorText "  ❌ 刪除失敗: $file - $($_.Exception.Message)" "Error"
                $errorCount++
            }
        }
    }
    
    Write-Host ""
    Write-ColorText "📊 清理完成統計:" "Header"
    Write-ColorText "  ✅ 成功刪除: $successCount 個檔案" "Success"
    if ($errorCount -gt 0) {
        Write-ColorText "  ❌ 刪除失敗: $errorCount 個檔案" "Error"
    }
    
    return $errorCount -eq 0
}

# 清理空目錄
function Remove-EmptyDirectories {
    Write-ColorText "🔍 檢查空目錄..." "Info"
    
    $emptyDirs = @()
    
    # 檢查常見的可能為空的目錄
    $checkDirs = @("temp", "logs", "cache", "tmp", "test", "tests")
    
    foreach ($dir in $checkDirs) {
        if (Test-Path $dir) {
            try {
                $items = Get-ChildItem $dir -Recurse -ErrorAction SilentlyContinue
                if (-not $items) {
                    $emptyDirs += $dir
                }
            } catch {
                # 忽略檢查錯誤
            }
        }
    }
    
    if ($emptyDirs.Count -gt 0) {
        Write-ColorText "📁 發現空目錄:" "Warning"
        foreach ($dir in $emptyDirs) {
            Write-ColorText "   - $dir" "Info"
        }
        
        $confirm = Read-Host "是否刪除這些空目錄? (y/N)"
        if ($confirm -eq "y" -or $confirm -eq "Y") {
            foreach ($dir in $emptyDirs) {
                try {
                    Remove-Item $dir -Force -Recurse
                    Write-ColorText "  ✅ 已刪除空目錄: $dir" "Success"
                } catch {
                    Write-ColorText "  ❌ 刪除失敗: $dir" "Error"
                }
            }
        }
    } else {
        Write-ColorText "✅ 沒有發現空目錄" "Success"
    }
}

# 生成清理報告
function New-CleanupReport {
    param([hashtable]$FilesToClean)
    
    $reportPath = "cleanup-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').txt"
    $report = @()
    
    $report += "PythonLearn 專案清理報告"
    $report += "=========================="
    $report += "清理時間: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
    $report += ""
    
    if ($FilesToClean.Count -eq 0) {
        $report += "結果: 沒有找到需要清理的檔案"
    } else {
        foreach ($category in $FilesToClean.GetEnumerator()) {
            $report += "[$($category.Key)]"
            foreach ($file in $category.Value) {
                $report += "  - $file"
            }
            $report += ""
        }
    }
    
    try {
        $report | Out-File -FilePath $reportPath -Encoding UTF8
        Write-ColorText "📄 清理報告已保存: $reportPath" "Info"
    } catch {
        Write-ColorText "⚠️ 無法保存清理報告" "Warning"
    }
}

# 主程序
function Main {
    Clear-Host
    Write-ColorText "🧹 PythonLearn 舊腳本清理工具" "Header"
    Write-ColorText "====================================" "Header"
    Write-Host ""
    
    # 檢查專案目錄
    if (-not (Test-Path "public/index.html") -or -not (Test-Path "websocket/server.php")) {
        Write-ColorText "❌ 錯誤: 請在PythonLearn專案根目錄中執行此腳本" "Error"
        Read-Host "按Enter鍵退出"
        exit 1
    }
    
    Write-ColorText "🔍 掃描專案目錄中的舊檔案..." "Info"
    Write-Host ""
    
    # 掃描要清理的檔案
    $filesToClean = Get-FilesToClean
    
    # 顯示清理預覽
    $hasFiles = Show-CleanupPreview -FilesToClean $filesToClean
    
    if ($hasFiles) {
        # 執行清理
        $success = Remove-OldFiles -FilesToClean $filesToClean
        
        # 清理空目錄
        Write-Host ""
        Remove-EmptyDirectories
        
        # 生成報告
        Write-Host ""
        New-CleanupReport -FilesToClean $filesToClean
        
        Write-Host ""
        if ($success) {
            Write-ColorText "🎉 清理完成！專案目錄已整理乾淨" "Success"
        } else {
            Write-ColorText "⚠️ 清理完成，但有部分檔案清理失敗" "Warning"
        }
    }
    
    Write-Host ""
    Read-Host "按Enter鍵退出"
}

# 執行主程序
Main 