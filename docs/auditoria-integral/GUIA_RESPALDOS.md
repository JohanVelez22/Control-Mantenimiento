# Guía de respaldos y recuperación

## Novedades de esta ronda (verificadas)

- `php artisan app:backup-db` usa ahora `--single-transaction --quick --no-tablespaces --routines --triggers`. Se generó un respaldo real con estas opciones (2026-11-13 02:54) y termina con `-- Dump completed`.
- Rotación y retención **por tipo** (`db_`, `backup_bd_multimedia_`, `backup_integral_`, `code_`), solo sobre nombres que genera el sistema y conservando **siempre las 3 copias más recientes** de cada tipo. Los archivos copiados a mano en la carpeta nunca se borran.
- `php artisan app:verificar-respaldo [--archivo=X]` restaura el último respaldo de BD en una base temporal (`verif_restauracion_*`), compara las filas por tabla y las sumas de control de dinero con la base viva, y borra la temporal. Rechaza archivos incompletos o con `USE`/`CREATE DATABASE`. **Ejecutado sobre el respaldo real: 31 tablas y 8 sumas de control idénticas.** Está programado los domingos a las 03:30.
- Las pruebas automáticas ya no tocan el `storage/` real. Antes, una prueba de rotación borró el respaldo `db_tecni_systemas_2026-11-05_14-39-48.sql`, que no se puede recuperar desde el sistema.
- **Nada de esto se ejecuta solo** hasta que exista la tarea programada de Windows (sección 5(a)).

## 2. Entregables de respaldos (GUIA_RESPALDOS.md)

### 5(a) Comando exacto para el Programador de tareas de Windows

**Forma rápida:** doble clic en `scripts\instalar-tarea-programada.bat`, aceptar el aviso de Administrador. Crea la tarea con la configuración de abajo (cuenta SYSTEM, también con batería), la ejecuta una vez y muestra si funcionó. Se puede repetir sin problema: actualiza la tarea existente.

Ejecutar en **PowerShell o CMD como Administrador** (no lo hizo la auditoría). Se usa la cuenta SYSTEM porque ServBay (y por tanto la web) también corre como LocalSystem: así los archivos de `storage` tienen el mismo propietario y la tarea corre sin sesión iniciada.

```bat
schtasks /Create /TN "TecniSystemas\Laravel Scheduler" /SC MINUTE /MO 1 /RU SYSTEM /RL HIGHEST /F /TR "\"C:\ServBay\packages\php\current\php.exe\" \"C:\ServBay\www\tecnisystemas\artisan\" schedule:run"
```

Si el equipo es portátil (las tareas creadas con `schtasks` no arrancan con batería), usar en su lugar PowerShell (Administrador):

```powershell
$accion   = New-ScheduledTaskAction -Execute 'C:\ServBay\packages\php\current\php.exe' -Argument '"C:\ServBay\www\tecnisystemas\artisan" schedule:run' -WorkingDirectory 'C:\ServBay\www\tecnisystemas'
$disparo  = New-ScheduledTaskTrigger -Once -At (Get-Date).Date -RepetitionInterval (New-TimeSpan -Minutes 1)
$ajustes  = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 30)
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskPath '\TecniSystemas\' -TaskName 'Laravel Scheduler' -Action $accion -Trigger $disparo -Settings $ajustes -Principal $principal -Force
```

Comprobación:
```bat
schtasks /Query /TN "TecniSystemas\Laravel Scheduler" /V /FO LIST
C:\ServBay\packages\php\current\php.exe C:\ServBay\www\tecnisystemas\artisan schedule:list
```
Al día siguiente: debe existir un archivo nuevo en `storage\app\backups` y un evento "backup" en Auditoría; `storage\logs\verificacion-respaldos.log` tras la primera verificación.
Nota: SYSTEM no ve unidades de red mapeadas ni la unidad virtual `G:` de Google Drive (es por usuario). Por eso `GOOGLE_DRIVE_BACKUP_PATH` debe ser una carpeta **física** (ver 5b). Para desactivar: `schtasks /Change /TN "TecniSystemas\Laravel Scheduler" /DISABLE`.

Antes de crear la tarea: cambiar la hora del respaldo en la aplicación a una hora con el equipo encendido (O-02).

### 5(b) Estrategia 3-2-1 viable para el taller

Volumen actual: ~200 KB por respaldo `.sql`, ~250 KB con fotos → un año de respaldos diarios < 100 MB. No hay problema de espacio.

| Copia | Dónde | Cómo | Frecuencia / conservación |
|---|---|---|---|
| 1 (principal) | `C:\ServBay\www\tecnisystemas\storage\app\backups` (SSD) | Scheduler de la app, tipo **BD + Multimedia (`files`)**, hora laboral | Diario; rotación de la app (10 copias / 15 días) |
| 2 (otro medio) | **D:\Respaldos\TecniSystemas** (disco HDD físico distinto) | Tarea diaria con `robocopy` **sin borrar** (histórico largo) | Diario; conservar 12 meses (limpieza manual anual) |
| 3 (fuera del local) | Nube: carpeta física sincronizada (Google Drive para escritorio en modo **"Duplicar archivos"** o OneDrive, p. ej. `D:\GoogleDrive\Respaldos TecniSystemas`) vía `GOOGLE_DRIVE_BACKUP_PATH` | La app copia cada respaldo al terminarlo | Diario; ojo: la app borra ahí también los >15 días (O-07) |
| 3b (fuera de línea, contra ransomware) | USB `E:` cifrada con BitLocker To Go, la lleva el dueño a casa | Copia manual del respaldo del último día del mes + `.env` | Mensual; conservar 12 meses |

Configuración sugerida (`.env`, luego `php artisan optimize` si se usa caché de configuración):
```dotenv
GOOGLE_DRIVE_BACKUP_PATH="D:\GoogleDrive\Respaldos TecniSystemas"
BACKUP_RETENTION_DAYS=15
MYSQL_PATH=C:\ServBay\packages\mysql\8.4\bin\mysql.exe
```
Tarea de la copia 2 (Administrador; 30 min después de la hora del respaldo, p. ej. 19:00):
```bat
schtasks /Create /TN "TecniSystemas\Copia a D" /SC DAILY /ST 19:00 /RU SYSTEM /RL HIGHEST /F /TR "robocopy C:\ServBay\www\tecnisystemas\storage\app\backups D:\Respaldos\TecniSystemas *.sql *.zip /E /XO /R:2 /W:5 /NP /LOG+:D:\Respaldos\robocopy.log"
```
(`/E /XO` copia nuevos sin eliminar nada en destino; robocopy devuelve 1 cuando copió archivos, no es error.)

Reglas:
- Los respaldos contienen datos personales de clientes (Ley 1581 de 2012): la carpeta de nube debe ser de una cuenta del negocio con 2FA; la USB cifrada.
- El `.env` (credenciales de BD, `APP_KEY`) **no** va a la carpeta de nube en texto plano: guardarlo en la USB cifrada o en un gestor de contraseñas, y cada vez que cambie.
- El binlog de MySQL (30 días, en C:) permite recuperar "lo de esta tarde" a partir del último respaldo (`mysqlbinlog --start-datetime=...`), pero está en el mismo disco: no cuenta como copia.
- Mientras no se corrija O-07, si el equipo estuvo apagado más de 15 días, **copiar manualmente la carpeta de respaldos a D: antes de encender/usar el sistema**.

### 5(c) Recuperación ante desastre en un equipo nuevo (paso a paso)

Objetivo: tiempo de recuperación ~2 h. Necesita: último respaldo (`.zip` BD+multimedia o `.sql` + carpeta de fotos), el `.env` guardado, acceso al repositorio GitHub (o un respaldo integral `--all` que trae el código).

1. **Windows**: zona horaria "(UTC-05:00) Bogotá, Lima, Quito" (O-09). Nombre de equipo y IP fija (reserva en el router) iguales al anterior si es posible.
2. **Instalar ServBay** (mismo paquete de versiones): PHP 8.4, MySQL 8.4, Nginx, Node 22/24 LTS, Composer, Git. Iniciar servicios y fijar la contraseña de root de MySQL.
3. **Código**: en PowerShell
   ```powershell
   cd C:\ServBay\www
   git clone https://github.com/JohanVelez22/Control-Mantenimiento tecnisystemas
   cd tecnisystemas
   git checkout <tag o commit que estaba en producción>   # anotado en el último despliegue
   ```
   (Sin acceso a GitHub: descomprimir `codigo_fuente/` de un respaldo integral `backup_integral_*.zip`.)
4. **`.env`**: copiar el `.env` guardado a `C:\ServBay\www\tecnisystemas\.env`. Revisar `DB_*`, `APP_URL`, `MYSQLDUMP_PATH`, `MYSQL_PATH`, `GOOGLE_DRIVE_BACKUP_PATH`. Si se perdió: copiar `.env.example`, completar valores y `php artisan key:generate` (solo se pierden las sesiones abiertas).
5. **Dependencias** (usar siempre el PHP de ServBay: `$php = 'C:\ServBay\packages\php\current\php.exe'`):
   ```powershell
   C:\ServBay\bin\composer.cmd install --no-dev --optimize-autoloader --no-interaction
   npm ci; npm run build
   & $php artisan storage:link        # PowerShell como Administrador (enlace simbólico)
   ```
6. **Base de datos vacía** (consola MySQL de ServBay como root):
   ```sql
   CREATE DATABASE tecni_systemas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   (y el usuario dedicado de O-10 si se usa).
7. **Restaurar datos** (desde CMD, no PowerShell, por la redirección `<`):
   ```bat
   rem Si es .zip: extraer database.sql y la carpeta archivos_publicos\
   "C:\ServBay\packages\mysql\8.4\bin\mysql.exe" -u root -p --default-character-set=utf8mb4 tecni_systemas < C:\ruta\database.sql
   ```
   Copiar el contenido de `archivos_publicos\` a `C:\ServBay\www\tecnisystemas\storage\app\public\`.
8. **Esquema al día**: `& $php artisan migrate:status` (todas "Ran"). Si el código es más nuevo que el respaldo: `& $php artisan app:verificar-migraciones` y luego `& $php artisan migrate --force`. **Nunca** `migrate:fresh` ni `db:seed`.
9. **Sitio web**: en ServBay → Hosts → `+`: dominio `tecnisystemas.local`, PHP 8.4, raíz `C:\ServBay\www\tecnisystemas\public`, SSL activado (certificado de ServBay). En los demás equipos de la red: entrada en `hosts` con la IP del servidor e importar la CA de ServBay.
10. **Optimizar y validar**:
    ```powershell
    & $php artisan optimize
    & $php artisan app:preflight
    & $php artisan app:verificar-respaldo --archivo=<nombre del respaldo usado>   # si se copió a storage\app\backups
    ```
11. **Programador de tareas**: crear las tareas de 5(a) y 5(b).
12. **Prueba de humo** (ver 6): login, una orden, caja del día, un PDF, una exportación Excel, las fotos se ven.
13. **Comunicar** la fecha/hora del respaldo restaurado: lo registrado después se debe volver a digitar (o recuperar del binlog si el disco antiguo es legible).

### 5(d) Verificación periódica de que un respaldo se puede restaurar

- **Automática semanal**: `app:verificar-respaldo` (nuevo, commit 33a1fc2) restaura el último respaldo en una base temporal `verif_restauracion_<fecha>`, compara filas por tabla y sumas de control (caja, abonos, facturas, órdenes, cierres, stock) con la base viva y borra la temporal. Resultado en `storage\logs\verificacion-respaldos.log`. Requisitos: scheduler activo, hora con equipo encendido (O-02), cliente `mysql` (definir `MYSQL_PATH`) y, con usuario dedicado, privilegios sobre `verif\_restauracion\_%` (O-10).
- **Manual mensual** (5 minutos, el primer lunes del mes, anotado en una bitácora):
  ```powershell
  cd C:\ServBay\www\tecnisystemas
  & $php artisan app:backup-db --files --sin-rotacion
  & $php artisan app:verificar-respaldo          # justo después: no deben aparecer diferencias
  ```
  Revisar también que el archivo esté en D:\Respaldos y en la carpeta de nube, y abrir el ZIP para confirmar que contiene `database.sql` y `archivos_publicos/`.
- **Simulacro semestral**: restaurar el respaldo de la USB en el entorno de pruebas (§6) o en otro PC siguiendo 5(c) completo, entrar con un usuario y comprobar un cierre de caja conocido. Esto prueba también el `.env` guardado y el procedimiento, no solo el archivo.
- Señales de alarma: el log de verificación no cambia en más de 8 días; "Respaldo reciente" en `app:preflight` en amarillo; tamaño del `.sql` muy distinto al habitual.

---



## 7. Cambios de la cuarta ronda (2026-11-16)

- **Cifrado (S-08).** Con `BACKUP_PASSWORD` en `.env`, cada respaldo se guarda como ZIP cifrado con AES-256. Restaurar y `app:verificar-respaldo` lo descifran solos. Para abrirlo a mano en otro equipo, use 7-Zip con esa clave.
  - **Si la clave se pierde, el respaldo no se puede recuperar.** Escríbala en papel y guárdela fuera del local.
  - Si cambia la clave, los respaldos anteriores siguen necesitando la clave anterior.
- **Huella de cada respaldo (D-03).** `storage/app/backups/manifiesto.json` guarda el SHA-256 y el tamaño de cada archivo, y se copia a la carpeta externa. Un archivo que cambió desde que se creó no se verifica ni se restaura.
- **Contraseña para operaciones sensibles.** Descargar, restaurar, borrar un respaldo o actualizar desde GitHub pide la contraseña de la sesión. Tras 5 intentos fallidos se bloquea 15 minutos. Cada descarga queda en el historial.
- **Mantenimiento al restaurar (D-01).** Mientras se restaura, los demás equipos ven "Mantenimiento en curso".
  - El aviso se quita al terminar.
  - Si el proceso se interrumpe, vence solo a los 30 minutos. También se puede borrar `storage/framework/restaurando.json`.
- **Historial conservado (D-16).** Los eventos posteriores al respaldo se reintegran con SQL, sin cargar todo en memoria.
- **Credenciales (D-10).** `mysqldump` y `mysql` reciben la contraseña en un archivo temporal que se borra al terminar, ya no en la variable `MYSQL_PWD`.
- **Programador de tareas.** `app:preflight` avisa si la tarea programada no se ha ejecutado en las últimas 2 horas (latido cada 10 minutos).
