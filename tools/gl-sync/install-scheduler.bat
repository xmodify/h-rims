@echo off
chcp 65001 > nul
echo ==========================================================
echo    ติดตั้ง Task Scheduler สำหรับ H-RIMS GL Sync Agent
echo ==========================================================
echo.

set TASK_NAME=HRIMS_GL_Sync_Agent
set AGENT_PATH=%~dp0gl-sync.exe

if not exist "%AGENT_PATH%" (
    echo [ERROR] ไม่พบไฟล์ gl-sync.exe ในโฟลเดอร์นี้!
    pause
    exit /b 1
)

echo สร้างงานตั้งเวลาให้ซิงค์ข้อมูลทุก 30 นาที อัตโนมัติ...
schtasks /create /tn "%TASK_NAME%" /tr "\"%AGENT_PATH%\" -once" /sc minute /mo 30 /f

if %ERRORLEVEL% equ 0 (
    echo.
    echo [SUCCESS] ติดตั้ง Task Scheduler สำเร็จเรียบร้อย!
    echo ระบบจะดึงข้อมูลจาก GL ส่งไปยัง H-RIMS ทุกๆ 30 นาที
) else (
    echo.
    echo [ERROR] ไม่สามารถสร้าง Task Scheduler ได้ (กรุณาคลิกขวาแล้วเลือก Run as Administrator)
)

echo.
pause
