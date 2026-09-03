@echo off
chcp 65001 > nul
echo ==========================================================
echo    ยกเลิก Task Scheduler สำหรับ H-RIMS GL Sync Agent
echo ==========================================================
echo.

set TASK_NAME=HRIMS_GL_Sync_Agent
schtasks /delete /tn "%TASK_NAME%" /f

if %ERRORLEVEL% equ 0 (
    echo [SUCCESS] ยกเลิก Task Scheduler เรียบร้อยแล้ว
) else (
    echo [INFO] ไม่พบงานในระบบ หรือจำเป็นต้อง Run as Administrator
)

echo.
pause
