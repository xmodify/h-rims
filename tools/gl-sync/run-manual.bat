@echo off
chcp 65001 > nul
echo ==========================================================
echo    ทดสอบการทำงานของ H-RIMS GL Sync Agent (แบบทันที)
echo ==========================================================
echo.

"%~dp0gl-sync.exe" -once

echo.
pause
