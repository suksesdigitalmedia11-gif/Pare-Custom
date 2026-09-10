@echo off
title Pare Custom - Local Development Server
echo ===================================================
echo   Menjalankan Pare Custom di Lingkungan Lokal
echo   URL: http://127.0.0.1:8000
echo   Tekan CTRL + C untuk menghentikan server.
echo ===================================================
cd /d D:\jhe\parecustom
C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe -S 127.0.0.1:8000 -t public
pause
