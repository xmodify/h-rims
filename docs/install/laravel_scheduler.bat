@echo off
cd /d "C:\xampp8.2\htdocs\h-rims"
C:\xampp8.2\php\php.exe artisan schedule:run >> storage\logs\scheduler.log 2>&1
