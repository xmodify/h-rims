@echo off
cd /d "d:\Project Laravel\h-rims"
C:\xampp\php\php.exe artisan schedule:run >> storage\logs\scheduler.log 2>&1
