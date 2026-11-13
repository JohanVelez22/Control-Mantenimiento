# Guía de despliegue

## Pasos obligatorios para desplegar ESTA versión

1. `php artisan app:backup-db --sin-rotacion` y después `php artisan app:verificar-respaldo` (debe terminar en ✅, o en ⚠ con diferencias explicables).
2. `php artisan migrate --force` (aplica `2026_11_13_120000_vincular_pagos_legacy_a_facturas`; en la base actual no cambia filas).
3. `npm run build` y `php artisan optimize:clear`.
4. `php artisan app:restablecer-password <correo>` para **cada** cuenta con contraseña predeterminada (administrador, técnico e invitado). Lo ejecuta el dueño en la consola del servidor; la contraseña no se muestra en pantalla.
5. `php artisan app:preflight` debe quedar sin bloqueantes.
6. Crear la tarea programada de Windows (ver `GUIA_RESPALDOS.md`, 5(a)).

El botón "Actualizar desde GitHub" genera ahora un respaldo antes de migrar. Si el respaldo o la migración fallan, lo informa como error; antes reportaba éxito.

## 3. Entregables de despliegue (GUIA_DESPLIEGUE.md / GUIA_INSTALACION.md)

### 6(a) Entorno de pruebas (staging) en la misma máquina

| Elemento | Producción | Pruebas |
|---|---|---|
| Carpeta | `C:\ServBay\www\tecnisystemas` | `C:\ServBay\www\tecnisystemas-pruebas` |
| Dominio (ServBay → Hosts, SSL) | `tecnisystemas.local` | `pruebas.tecnisystemas.local` (lo cubre el SAN `*.tecnisystemas.local` del certificado actual) |
| Base | `tecni_systemas` | `tecni_systemas_pruebas` (no usar "test" en el nombre: los `tearDown` de las pruebas borran respaldos cuyo nombre contenga "test") |
| `.env` | actual | copia con: `APP_NAME="Tecni Systemas PRUEBAS"` (cookie de sesión distinta), `APP_ENV=staging`, `APP_DEBUG=false` (true solo puntualmente), `APP_URL=https://pruebas.tecnisystemas.local`, `DB_DATABASE=tecni_systemas_pruebas`, `APP_KEY` propia (`key:generate`), `GOOGLE_DRIVE_BACKUP_PATH=` vacío, `MAIL_MAILER=log` |
| Scheduler | tarea de Windows | **sin** tarea (no generar respaldos ni copias a la nube) |

Montaje:
```powershell
cd C:\ServBay\www
git clone https://github.com/JohanVelez22/Control-Mantenimiento tecnisystemas-pruebas
cd tecnisystemas-pruebas
copy ..\tecnisystemas\.env .env      # y editar los valores de la tabla
$php = 'C:\ServBay\packages\php\current\php.exe'
C:\ServBay\bin\composer.cmd install          # aquí sí con dev: se pueden correr pruebas
npm ci; npm run build
& $php artisan key:generate
& $php artisan storage:link
```
Datos: crear la base `tecni_systemas_pruebas` y cargar el último respaldo de producción (CMD: `mysql -u root -p tecni_systemas_pruebas < ...\storage\app\backups\db_tecni_systemas_<fecha>.sql`), copiar `storage\app\public` de producción. Refrescar así antes de cada prueba de actualización. En el entorno de pruebas se pueden ejecutar `php artisan test`, `system:audit --deep`, `app:seed-demo-data`, etc.; **nunca** en producción.
En los equipos cliente añadir `pruebas.tecnisystemas.local` al `hosts` solo si deben probar.

### 6(b) Procedimiento de despliegue de actualizaciones (producción)

Precondiciones: la versión se probó en `tecnisystemas-pruebas` con datos recientes; nadie está facturando (fuera de horario); producción con `git status` limpio.

```powershell
$php = 'C:\ServBay\packages\php\current\php.exe'
cd C:\ServBay\www\tecnisystemas

# 1. Estado y punto de retorno
git status                                   # debe estar limpio
git fetch origin
git log --oneline HEAD..origin/main          # qué entra
$previo = git rev-parse HEAD
git tag ("prod-previo-" + (Get-Date -Format yyyyMMdd-HHmm))

# 2. Mantenimiento
& $php artisan down --retry=60

# 3. Respaldo verificado (sin rotación para no borrar nada)
& $php artisan app:backup-db --files --sin-rotacion
& $php artisan app:verificar-respaldo        # anotar el nombre del respaldo

# 4. Código y dependencias
git pull --ff-only
C:\ServBay\bin\composer.cmd install --no-dev --optimize-autoloader --no-interaction
npm ci; npm run build

# 5. Base de datos
& $php artisan app:verificar-migraciones     # si reporta problemas: DETENER y hacer rollback
& $php artisan migrate --force

# 6. Cachés y validación
& $php artisan optimize                      # config, events, routes, views
& $php artisan app:preflight                 # 0 bloqueantes
& $php artisan schedule:list

# 7. Abrir y probar
& $php artisan up
```
(Si OPcache se activa con `validate_timestamps=0`, reiniciar PHP 8.4 en ServBay después del paso 6.)

**Prueba de humo (5 min)**: `https://tecnisystemas.local/login` carga estilos; iniciar sesión como administrador; abrir Órdenes, Caja del día, Inventario; generar un PDF de factura y una exportación Excel; ver una foto de producto/logo; consultar una orden desde el portal de invitados; `& $php artisan route:list --path=_dusk` → "No routes found"; revisar `storage\logs\laravel-<hoy>.log` sin errores nuevos.

**Rollback** (si falla algo en 4–7):
```powershell
& $php artisan down
git checkout $previo                         # o el tag prod-previo-...
C:\ServBay\bin\composer.cmd install --no-dev --optimize-autoloader --no-interaction
npm ci; npm run build
& $php artisan optimize:clear
```
- Si **no** se ejecutó ninguna migración: basta lo anterior + `optimize` + `up`.
- Si se ejecutaron migraciones: restaurar la base del respaldo del paso 3 (desde la web: Configuración → Respaldos → Restaurar, que antes crea un respaldo de seguridad; o en CMD sobre la base vacía: `DROP DATABASE tecni_systemas; CREATE DATABASE tecni_systemas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;` y `mysql ... tecni_systemas < respaldo.sql`). No usar `migrate:rollback` en producción salvo que la migración esté probada en staging.
- Luego `& $php artisan optimize`, `& $php artisan app:preflight`, `& $php artisan up`, y volver a `git checkout master` en cuanto se corrija (no dejar el repositorio en HEAD separado).

### 6(c) Errores y faltantes en la documentación existente

**DEPLOY_CHECKLIST.md**
1. §8 rollback usa `php artisan app:backup-db` como "restaurar" (O-06) — es un error grave.
2. No incluye `git pull --ff-only`, `artisan down/up`, ni la etiqueta del commit previo.
3. §3 ejecuta `app:backup-db` sin `--sin-rotacion` (la rotación puede borrar copias anteriores justo antes de un cambio riesgoso).
4. §4 lista cuatro comandos de caché; basta `php artisan optimize` (incluye config, events, routes, views). Falta advertir el efecto sobre `security:check` y `AdminUserSeeder` (O-14).
5. §6 Windows: comando con `powershell.exe -Command "cd ...; php ..."` sin cuenta, sin "ejecutar aunque el usuario no haya iniciado sesión" (abre una ventana cada minuto o no corre sin sesión). Sustituir por 5(a).
6. §1: Node mínimo 20 → realmente ^20.19 o >=22.12 (Vite 8); `bcmath` no es requerido; Let's Encrypt no aplica a `.local` (usar el certificado de ServBay).
7. §2: `APP_TIMEZONE` y `ADMIN_REGISTRATION_PASSWORD` no tienen efecto real (O-13); `BACKUP_RETENTION_DAYS=30` difiere del `.env` real (15); falta `MYSQL_PATH`; usa `LOG_CHANNEL=stack`+`LOG_STACK=daily` mientras producción usa `LOG_CHANNEL=daily`.
8. §5 Windows: no aclara que ServBay corre como LocalSystem.
9. §7: probar `/.env` está bien; añadir la prueba de humo funcional de 6(b) y `app:preflight`.
10. Falta: OPcache, usuario MySQL dedicado (O-10), staging, "no ejecutar tests en producción" (O-03).

**README.md**
1. Carpeta/dominio `tecni-systemas` / `tecni-systemas.local` ≠ instalación real `tecnisystemas` / `tecnisystemas.local`.
2. `php artisan migrate:fresh --seed` como paso de instalación: destructivo si se repite; en producción sin `ADMIN_DEFAULT_PASSWORD` el seeder lanza excepción. Usar `php artisan migrate --force` + `php artisan db:seed --force` (o `db:setup`, que pide confirmación).
3. `composer install` sin `--no-dev`; `npm install` en vez de `npm ci`.
4. Acceso por `http://` / `localhost:8000` (producción es HTTPS y `SESSION_SECURE_COOKIE=true`: por HTTP no se mantiene la sesión).
5. Insignia "554 tests" desactualizada (604 hoy).
6. No menciona el programador de tareas ni respaldos externos.
7. No advierte que `php` en el PATH de este equipo es Herd Lite 8.4.0; usar `C:\ServBay\bin\php.cmd` o la ruta completa.

**DOCUMENTACION_1_GUIA_INSTALACION_SERVBAY.md**
1. Mismos errores de carpeta/dominio (`tecni-systemas`), PHP 8.3 opcional (producción es 8.4).
2. Paso 4 configura `APP_ENV=local`, `APP_DEBUG=true`, `http://` y nunca vuelve a producción para el escenario ServBay (la sección 6 habla de VPS/cPanel). Falta `ADMIN_DEFAULT_PASSWORD`/`TECNICO_…`/`INVITADO_…` antes del seeder y el aviso de cambio obligatorio de contraseña al primer ingreso.
3. Paso 6 `migrate:fresh --seed` (destructivo) y `app:seed-demo-data --force` sin advertir que no se use en producción.
4. Sección 5: `php artisan db:backup` **no existe** (es `app:backup-db`); `system:audit --deep` escribe en la base (O-19); `php artisan test` en producción es peligroso (O-03).
5. Sección 6: `chmod -R 775` no aplica en Windows; `npm run build` después de `view:cache` (orden irrelevante pero confuso); no menciona `storage:link` como Administrador, programador de tareas, respaldos externos, OPcache, `app:preflight`, hosts/CA para otros equipos de la red.

---

