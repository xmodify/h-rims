@echo off
title Smart Money Transfer - ThaiD Login & Sync
cd /d "d:\Project Laravel\h-rims"
echo ===================================================
echo   Smart Money Transfer - ThaiD Login & Sync Tool
echo ===================================================
echo.
echo กำลังเปิดหน้าต่างเบราว์เซอร์สำหรับสแกน ThaiD...
echo.
node scratch/smt_browser_login.js
echo.
pause
