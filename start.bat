@echo off
title Tracer Study - PTI UMS
echo ============================================
echo   TRACER STUDY - PTI UMS
echo   Memulai server lokal...
echo ============================================
echo.

:: Kill any existing processes on port 3000 and 5000
echo Membersihkan port yang masih digunakan...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":3000 " ^| findstr "LISTENING"') do taskkill /F /PID %%a >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":5000 " ^| findstr "LISTENING"') do taskkill /F /PID %%a >nul 2>&1
timeout /t 1 /nobreak >nul

:: Start PHP Backend server in a separate window
echo [1/2] Menyalakan Backend API (PHP) di port 5000...
start "PHP Backend" /min C:\xampp\php\php.exe -S localhost:5000 -t "%~dp0apps\api" "%~dp0apps\api\index.php"

:: Wait for PHP server to start
timeout /t 2 /nobreak >nul

:: Start Next.js Frontend
echo [2/2] Menyalakan Frontend (Next.js) di port 3000...
echo.
echo ============================================
echo   Tunggu beberapa detik...
echo   Lalu buka browser: http://localhost:3000
echo.
echo   Tekan Ctrl+C untuk menghentikan.
echo ============================================
echo.

cd /d "%~dp0"
npm run dev
