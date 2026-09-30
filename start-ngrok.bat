@echo off
cd /d "C:\ServBay\www\tecnisystemas"
echo Iniciando servidor Laravel en puerto 8000...
start "Laravel" php artisan serve --host=0.0.0.0 --port=8000
timeout /t 2 /nobreak >nul
echo Iniciando tunel ngrok...
start "ngrok" ngrok http 8000
timeout /t 3 /nobreak >nul
echo.
echo ========================================
echo URL de acceso remoto:
curl -s http://localhost:4040/api/tunnels
echo.
echo ========================================
echo Copia la URL public_url de arriba
echo.
echo Para detener: taskkill /F /IM ngrok.exe ^&^& taskkill /F /IM php.exe
pause
