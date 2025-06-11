# 🔑 OpenAI API Key 快速修復腳本
# 適用於 Windows PowerShell

param(
    [Parameter(Mandatory = $true)]
    [string]$ApiKey
)

Write-Host "🔧 開始修復 OpenAI API Key..." -ForegroundColor Green

# 檢查API Key格式
if (-not $ApiKey.StartsWith("sk-")) {
    Write-Host "❌ 錯誤: API Key 格式不正確，應以 'sk-' 開頭" -ForegroundColor Red
    exit 1
}

# 1. 更新本地配置文件
Write-Host "📝 更新本地配置文件..." -ForegroundColor Yellow

$configPath = "ai_config.json"
if (Test-Path $configPath) {
    $config = Get-Content $configPath | ConvertFrom-Json
    $config.openai_api_key = $ApiKey
    $config | ConvertTo-Json -Depth 4 | Set-Content $configPath
    Write-Host "✅ 已更新 $configPath" -ForegroundColor Green
}
else {
    # 創建新配置文件
    $newConfig = @{
        openai_api_key = $ApiKey
        model          = "gpt-3.5-turbo"
        max_tokens     = 1000
        temperature    = 0.7
        timeout        = 30000
        enabled        = $true
    }
    $newConfig | ConvertTo-Json -Depth 4 | Set-Content $configPath
    Write-Host "✅ 已創建 $configPath" -ForegroundColor Green
}

# 2. 設置環境變數（當前會話）
Write-Host "🌍 設置環境變數..." -ForegroundColor Yellow
$env:OPENAI_API_KEY = $ApiKey
Write-Host "✅ 已設置環境變數 OPENAI_API_KEY" -ForegroundColor Green

# 3. 測試API連接
Write-Host "🧪 測試API連接..." -ForegroundColor Yellow

try {
    $testResult = php test_real_api.php 2>&1
    if ($LASTEXITCODE -eq 0) {
        if ($testResult -match "測試成功") {
            Write-Host "✅ API 測試成功！" -ForegroundColor Green
        }
        else {
            Write-Host "⚠️  API 測試可能有問題，請檢查輸出：" -ForegroundColor Yellow
            Write-Host $testResult
        }
    }
    else {
        Write-Host "❌ API 測試失敗" -ForegroundColor Red
        Write-Host $testResult
    }
}
catch {
    Write-Host "⚠️  無法運行API測試，請手動檢查" -ForegroundColor Yellow
}

# 4. 顯示Zeabur設置指引
Write-Host "`n🚀 Zeabur 環境設置指引:" -ForegroundColor Cyan
Write-Host "1. 登入 Zeabur 控制台: https://zeabur.com/dashboard" -ForegroundColor White
Write-Host "2. 選擇您的 PythonLearn 專案" -ForegroundColor White
Write-Host "3. 進入 Project Settings → Environment Variables" -ForegroundColor White
Write-Host "4. 添加變數:" -ForegroundColor White
Write-Host "   Key: OPENAI_API_KEY" -ForegroundColor Gray
Write-Host "   Value: $ApiKey" -ForegroundColor Gray
Write-Host "5. 重新部署應用" -ForegroundColor White

Write-Host "`n✅ 本地修復完成！" -ForegroundColor Green
Write-Host "🔍 您可以使用以下命令測試:" -ForegroundColor Cyan
Write-Host "   php test_real_api.php" -ForegroundColor Gray
Write-Host "   php public/test_ai_config.php" -ForegroundColor Gray

Write-Host "`n📋 完成後請測試5個AI助教按鈕:" -ForegroundColor Cyan
Write-Host "   • 解釋程式" -ForegroundColor Gray
Write-Host "   • 檢查錯誤" -ForegroundColor Gray  
Write-Host "   • 改進建議" -ForegroundColor Gray
Write-Host "   • 衝突分析" -ForegroundColor Gray
Write-Host "   • AI運行代碼" -ForegroundColor Gray 