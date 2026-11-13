# Guía de instalación (equipo nuevo o entorno de pruebas)

## Requisitos verificados en el equipo actual

- Windows 11 con ServBay: PHP 8.4.27, MySQL 8.4.11 y Nginx.
- Composer y Node 20 o superior (Vite 8).
- Extensiones PHP: `pdo_mysql`, `pdo_sqlite`, `mbstring`, `zip`, `gd`, `intl`, `bcmath`, `fileinfo`.

## Pasos

1. Clonar el repositorio en `C:\ServBay\www\<carpeta>`.
2. `composer install --no-dev --optimize-autoloader`. En desarrollo, sin `--no-dev`.
3. `copy .env.example .env` y `php artisan key:generate`. Completar `DB_*`, `APP_URL`, `MYSQL_PATH`/`MYSQLDUMP_PATH` si no son las rutas de ServBay, y `GOOGLE_DRIVE_BACKUP_PATH` para la copia externa.
4. Crear la base de datos (utf8mb4_unicode_ci) y un usuario MySQL **propio** con privilegios solo sobre esa base. No usar `root` (D-07).
5. `php artisan app:verificar-migraciones`, luego `php artisan migrate --force` y `php artisan db:seed --force`.
6. **Inmediatamente:** `php artisan app:restablecer-password <correo>` para cada cuenta sembrada.
7. `npm ci`, `npm run vendor:sync` y `npm run build`.
8. `php artisan storage:link` y `php artisan optimize`.
9. Crear la tarea programada del scheduler (`GUIA_RESPALDOS.md`, 5(a)).
10. `php artisan app:backup-db` seguido de `php artisan app:verificar-respaldo`: debe terminar en ✅.
11. `php artisan app:preflight` sin bloqueantes.

## Entorno de pruebas aislado (sin tocar producción)

Esta carpeta es a la vez la de producción. Para probar sin riesgo se puede usar una base SQLite desechable y variables de entorno solo para ese proceso, como en la verificación en navegador de esta auditoría:

```bat
set DB_CONNECTION=sqlite
set DB_DATABASE=C:\ruta\temporal\db.sqlite
set SESSION_DRIVER=database
set CACHE_STORE=database
set LOG_CHANNEL=stderr
set VIEW_COMPILED_PATH=C:\ruta\temporal\views
php artisan migrate --force --seed
php artisan serve --host=127.0.0.1 --port=8765 --no-reload
```

`--no-reload` es necesario para que `artisan serve` respete estas variables. Las pruebas automáticas (`php artisan test`) ya usan SQLite en memoria y un storage temporal propio.

Para un staging permanente en la misma máquina, ver la sección 6(a) de `GUIA_DESPLIEGUE.md`.
