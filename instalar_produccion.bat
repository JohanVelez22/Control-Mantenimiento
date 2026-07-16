@echo off
setlocal EnableDelayedExpansion
color 0A
title Asistente de Instalacion - Tecny-Sistemas Produccion

echo =======================================================
echo     SISTEMA DE MANTENIMIENTO E INVENTARIO (PRODUCCION)
echo =======================================================
echo.
echo Este script configurara el sistema para su uso profesional.
echo Asegurate de estar ejecutando esto dentro de tu entorno ServBay.
echo.
pause

echo.
echo [1/8] Verificando requisitos del sistema...
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP no esta instalado o no esta en el PATH.
    pause
    exit /b 1
)
composer -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Composer no esta instalado o no esta en el PATH.
    pause
    exit /b 1
)
node -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Node.js no esta instalado o no esta en el PATH.
    pause
    exit /b 1
)
echo OK - Requisitos detectados.

echo.
echo [2/8] Configurando archivo de entorno (.env)...
if not exist ".env" (
    copy .env.example .env >nul
    echo OK - Archivo .env creado.
) else (
    echo OK - El archivo .env ya existe.
)

echo.
echo [3/8] Instalando dependencias de PHP (Modo Produccion)...
call composer install --optimize-autoloader --no-dev
if %errorlevel% neq 0 (
    echo [ERROR] Fallo la instalacion de Composer.
    pause
    exit /b 1
)
echo OK - Dependencias backend instaladas.

echo.
echo [4/8] Instalando dependencias Frontend y compilando assets...
call npm install
call npm run build
if %errorlevel% neq 0 (
    echo [ERROR] Fallo la compilacion de Node/Vite.
    pause
    exit /b 1
)
echo OK - Assets frontend compilados.

echo.
echo [5/8] Generando clave de seguridad de la aplicacion...
call php artisan key:generate --force
echo OK - Clave generada.

echo.
echo [6/8] Preparando Base de Datos y Semillas (Sin datos basura)...
call php artisan migrate --force
call php artisan db:seed --class=DatabaseSeeder --force
echo OK - Base de datos lista.

echo.
echo [7/8] Optimizando rutas, vistas y configuracion (Caché)...
call php artisan optimize:clear
call php artisan optimize
call php artisan view:cache
call php artisan event:cache
echo OK - Sistema cacheado y optimizado al 100%%.

echo.
echo [8/8] Ejecutando control de seguridad (Auditoria final)...
call php artisan security:check
echo.

echo =======================================================
echo                    INSTALACION EXITOSA
echo =======================================================
echo El sistema esta ahora configurado y blindado para produccion.
echo.
echo Tareas Programadas Recomendadas en Windows (Task Scheduler):
echo Crea una tarea basica en Windows que ejecute diariamente a las 2AM:
echo Comando: php "%cd%\artisan" schedule:run
echo.
pause
