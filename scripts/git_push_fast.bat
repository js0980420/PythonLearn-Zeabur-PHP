@echo off
chcp 65001 > nul
setlocal EnableDelayedExpansion

:: 獲取當前日期時間用於日誌
FOR /F "usebackq tokens=1-4 delims=: " %%i IN (`echo %time%`) DO SET "CurrentTime=%%i_%%j_%%k_%%l"
SET "LogFile=github_upload_record_%DATE:~10,4%%DATE:~4,2%%DATE:~7,2%_%CurrentTime%.log"

ECHO 🚀 正在準備智慧型 Git 上傳...
ECHO.

:: 1. 檢查是否有未提交的變更
ECHO 🔍 檢查檔案狀態...
git status --porcelain > NUL
IF %ERRORLEVEL% NEQ 0 (
    ECHO ❌ 無法執行 Git status。請確保您在 Git 倉庫中。
    GOTO :END
)

FOR /F "usebackq tokens=*" %%a IN (`git status --porcelain`) DO (
    IF NOT "%%a"=="" (
        SET "HasChanges=true"
    )
)

IF NOT DEFINED HasChanges (
    ECHO ✅ 沒有偵測到任何變更，無需提交。
    GOTO :END
)

ECHO ----------------------------------------------------
ECHO ℹ️ 以下是將要提交的變更:
git status --short
ECHO ----------------------------------------------------
ECHO.

:: 2. 添加所有變更
ECHO ➕ 正在添加所有變更...
git add -A
IF %ERRORLEVEL% NEQ 0 (
    ECHO ❌ Git add 失敗。
    GOTO :END
)
ECHO ✅ 所有變更已暫存。
ECHO.

:: 3. 提示用戶輸入提交訊息 (預設值)
SET "CommitMessage="
ECHO 💡 請輸入本次提交的簡短描述 (例如: 修正WebSocket連線, 新增AI功能):
SET /P "CommitMessage=>> "

IF "%CommitMessage%"=="" (
    ECHO ⚠️ 未輸入描述。將使用預設訊息。
    SET "CommitMessage=chore: 自動提交 - %DATE% %TIME%"
) ELSE (
    :: 嘗試根據描述生成語義化前綴
    ECHO 正在根據您的描述生成語義化提交訊息...
    CALL :GENERATE_SEMANTIC_MESSAGE "!CommitMessage!"
    SET "CommitMessage=!SemanticMessage!"
)

ECHO ----------------------------------------------------
ECHO ✅ 最終提交訊息: !CommitMessage!
ECHO ----------------------------------------------------
ECHO.

:: 4. 提交變更
ECHO 💾 正在提交變更...
git commit -m "!CommitMessage!"
IF %ERRORLEVEL% NEQ 0 (
    ECHO ❌ Git commit 失敗。請檢查是否有衝突或錯誤。
    GOTO :END
)
ECHO ✅ 變更已提交。
ECHO.

:: 5. 推送到遠端倉庫
ECHO ⬆️ 正在推送到 remote 'origin' 的 'main' 分支...
git push origin main
IF %ERRORLEVEL% NEQ 0 (
    ECHO ❌ Git push 失敗。請檢查您的網路連線或權限。
    GOTO :END
)
ECHO ✅ 推送成功！
ECHO.

ECHO ----------------------------------------------------
ECHO 🎉 GitHub 上傳完成！
ECHO ----------------------------------------------------

:: 記錄上傳歷史 (可選)
ECHO 正在記錄本次上傳到 %LogFile%...
ECHO ---------------------------------------------------- >> %LogFile%
ECHO 上傳時間: %DATE% %TIME% >> %LogFile%
ECHO 提交訊息: !CommitMessage! >> %LogFile%
ECHO ---------------------------------------------------- >> %LogFile%
ECHO. >> %LogFile%
git log -1 >> %LogFile%
ECHO. >> %LogFile%
ECHO. >> %LogFile%
ECHO ✅ 上傳記錄已更新。

GOTO :END

:GENERATE_SEMANTIC_MESSAGE
SET "InputDesc=%~1"
SET "SemanticMessage="

:: 簡化判斷邏輯，通常是新功能(feat)或修正(fix)
IF "!InputDesc:新增=!" NEQ "!InputDesc!" (
    SET "SemanticMessage=feat: !InputDesc!"
) ELSE IF "!InputDesc:修復=!" NEQ "!InputDesc!" (
    SET "SemanticMessage=fix: !InputDesc!"
) ELSE IF "!InputDesc:優化=!" NEQ "!InputDesc!" (
    SET "SemanticMessage=perf: !InputDesc!"
) ELSE IF "!InputDesc:更新=!" NEQ "!InputDesc!" (
    SET "SemanticMessage=docs: !InputDesc!"
) ELSE IF "!InputDesc:重構=!" NEQ "!InputDesc!" (
    SET "SemanticMessage=refactor: !InputDesc!"
) ELSE IF "!InputDesc:測試=!" NEQ "!InputDesc!" (
    SET "SemanticMessage=test: !InputDesc!"
) ELSE (
    SET "SemanticMessage=chore: !InputDesc!"
)
GOTO :EOF

:END
ECHO 按任意鍵結束...
PAUSE > NUL
ENDLOCAL 