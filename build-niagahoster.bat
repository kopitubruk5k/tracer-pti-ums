@echo off
echo ===================================================
echo   MEMBANGUN APLIKASI UNTUK DEPLOY NIAGAHOSTER
echo   Subdomain Target: ptiums.id/tracer
echo ===================================================
echo.

:: 1. Atur variabel environment untuk Next.js statis
set NEXT_PUBLIC_BASE_PATH=/tracer
set NEXT_PUBLIC_API_URL=https://ptiums.id/tracer/api/v1

echo [1/3] Menyiapkan dan mengkompilasi file Frontend Next.js...
echo Mohon tunggu, proses ini butuh waktu sekitar 15-30 detik.
call npm run build --workspace=web

echo.
echo [2/3] Membuat folder niagahoster_deploy...
if exist niagahoster_deploy rmdir /s /q niagahoster_deploy
mkdir niagahoster_deploy

echo.
echo [3/3] Menyalin file Frontend dan Backend...
:: Salin hasil build Next.js (dari apps/web/out)
xcopy apps\web\out\* niagahoster_deploy\ /E /I /H /Y /Q

:: Buat folder api di dalam deploy
mkdir niagahoster_deploy\api

:: Salin backend PHP API (dari apps/api)
xcopy apps\api\* niagahoster_deploy\api\ /E /I /H /Y /Q /EXCLUDE:exclude_build.txt

echo.
echo ===================================================
echo SELESAI!
echo Seluruh file untuk diunggah telah dikumpulkan di dalam folder:
echo -^> C:\xampp\htdocs\tracer\niagahoster_deploy
echo.
echo Silakan kompres seluruh isi folder "niagahoster_deploy" menjadi ZIP
echo dan unggah melalui File Manager cPanel Niagahoster.
echo Baca dokumen "panduan_deploy_niagahoster.md" untuk instruksi lengkapnya!
echo ===================================================
pause
