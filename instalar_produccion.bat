@echo off
color 0B
echo ==============================================================
echo       INSTALADOR AUTOMATIZADO - ENTORNO DE PRODUCCION
echo            CONTROL DE MANTENIMIENTO Y EQUIPOS
echo ==============================================================
echo.
echo Este script configurara el sistema para su uso oficial en Windows 10.
echo Asegurese de tener ServBay, Composer y Node.js en ejecucion.
echo.
pause

echo.
echo [1/8] Instalando dependencias de PHP (Optimizadas)...
call composer install --no-dev --optimize-autoloader

echo.
echo [2/8] Configurando archivo de entorno (.env)...
if not exist ".env" (
    copy .env.example .env
    echo Archivo .env creado. Por favor, asegurese de tener la base de datos creada en ServBay (mantenimiento-equipos).
) else (
    echo Archivo .env ya existe.
)

echo.
echo [3/8] Generando llave criptografica...
call php artisan key:generate --force

echo.
echo [4/8] Instalando dependencias de interfaz (Node.js)...
call npm install

echo.
echo [5/8] Compilando diseño (Glassmorphism) para Produccion...
call npm run build

echo.
echo [6/8] Estructurando Base de Datos...
call php artisan migrate --force

echo.
echo [7/8] Gestion de Datos Iniciales
echo ==============================================================
echo 1. Llenar con datos de PRUEBA (5 registros demostrativos)
echo 2. Migrar historial REAL desde base de datos de Access (.mdb)
echo 3. Dejar el sistema completamente en BLANCO (Solo usuarios admin)
echo ==============================================================
set /p opcion="Elija una opcion (1, 2 o 3): "

if "%opcion%"=="1" (
    echo Limpiando base de datos por seguridad...
    call php artisan app:clean-data < NUL
    echo Sembrando datos demostrativos...
    call php artisan app:seed-demo-data
) else if "%opcion%"=="2" (
    echo Asegurese de tener el archivo AccessDB.mdb configurado.
    pause
    echo Limpiando base de datos por seguridad...
    call php artisan app:clean-data < NUL
    echo Iniciando migracion real...
    call php artisan app:migrate-access
) else (
    echo Dejando el sistema en blanco.
)

echo.
echo [8/8] Optimizando Cache para Maxima Velocidad...
call php artisan config:cache
call php artisan route:cache
call php artisan view:cache

echo.
echo ==============================================================
echo   ¡INSTALACION FINALIZADA CON EXITO! EL SISTEMA ESTA LISTO.
echo   Puede acceder a traves de su dominio local en ServBay.
echo ==============================================================
pause
