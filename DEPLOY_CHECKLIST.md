# 🚀 Checklist y Guía de Despliegue a Producción — Tecni-Systemas

Esta guía contiene el protocolo exhaustivo y seguro para el despliegue de **Tecni-Systemas** en servidores de producción (Linux VPS/Ubuntu, ServBay en Windows Server, Docker o Cloud Hosting).

---

## 📋 1. Requisitos del Servidor

| Componente | Requisito Mínimo | Recomendado |
|---|---|---|
| **PHP** | 8.3.0+ (compatible con 8.4) | 8.4 con extensiones `pdo_mysql`, `mbstring`, `zip`, `gd`, `bcmath`, `fileinfo` |
| **MySQL / MariaDB** | MySQL 8.0+ / MariaDB 10.6+ | MySQL 8.4 (motor InnoDB por defecto) |
| **Node.js** | Node 20 LTS | Node 24 LTS + NPM 10+ |
| **Servidor Web** | Nginx 1.24+ o Apache 2.4+ | Nginx con SSL / HTTP2 |
| **SSL / TLS** | Certificado válido | Let's Encrypt / Certbot con renovación automática |

---

## 🛠️ 2. Preparación de Variables de Entorno (`.env`)

Copia `.env.example` a `.env` y configura las variables críticas para producción:

```dotenv
APP_NAME="Tecni-Systemas"
APP_ENV=production
APP_KEY=base64:GENERA_CON_PHP_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://tu-dominio-taller.com

APP_TIMEZONE=America/Bogota
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CO

# Clave administrativa única para autorizar nuevos técnicos
ADMIN_REGISTRATION_PASSWORD="ClaveExclusivaSegura2026!"

# Conexión a Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tecni_systemas
DB_USERNAME=tecni_app
DB_PASSWORD=ContraseñaGeneradaDeAltaComplejidad123!

# Sesiones Blindadas
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Logs Diarios con Rotación
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=14
LOG_LEVEL=warning

# Rutas de Respaldo Físico
MYSQLDUMP_PATH=/usr/bin/mysqldump
# En Windows / ServBay:
# MYSQLDUMP_PATH=C:\ServBay\packages\mysql\8.4\bin\mysqldump.exe
GOOGLE_DRIVE_BACKUP_PATH=
BACKUP_RETENTION_DAYS=30
```

> [!CAUTION]
> **NUNCA** configures `APP_DEBUG=true` en un entorno accesible por red pública. Si ocurre una excepción no controlada, se expondrían variables de entorno, hashes y rutas internas.

---

## 📦 3. Comandos de Instalación y Compilación

Ejecuta en la raíz del proyecto:

```bash
# 1. Instalar dependencias PHP de producción sin paquetes de testing/dev
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Compilar activos de frontend con Vite
npm ci
npm run build

# 3. Crear enlace simbólico de almacenamiento público
php artisan storage:link

# 4. Ejecutar migraciones pendientes de manera segura
php artisan migrate --force
```

---

## ⚡ 4. Optimización de Caché para Máximo Rendimiento

Para evitar lecturas innecesarias en disco y optimizar el tiempo de respuesta:

```bash
# Compilar configuración y variables de entorno
php artisan config:cache

# Compilar árbol de rutas
php artisan route:cache

# Pre-compilar vistas Blade
php artisan view:cache

# Cachear listeners de eventos
php artisan event:cache
```

> [!TIP]
> Si actualizas el archivo `.env` o el código de rutas, recuerda limpiar y refrescar con:
> `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`

---

## 🔐 5. Permisos de Archivos y Carpetas

### En Linux (Nginx / Apache con usuario `www-data`):
```bash
sudo chown -R www-data:www-data /var/www/tecni-systemas
sudo find /var/www/tecni-systemas -type f -exec chmod 644 {} \;
sudo find /var/www/tecni-systemas -type d -exec chmod 755 {} \;

# Carpetas con permisos de escritura para la aplicación:
sudo chmod -R 775 /var/www/tecni-systemas/storage
sudo chmod -R 775 /var/www/tecni-systemas/bootstrap/cache
```

### En Windows (ServBay o IIS):
Asegúrate de que el usuario de servicio de PHP/ServBay tenga permisos de Lectura y Escritura sobre `storage\` y `bootstrap\cache\`.

---

## ⏰ 6. Programador de Tareas (Cron / Windows Task Scheduler)

El sistema cuenta con respaldos automáticos programables y purga de eventos.

### Linux (Crontab):
Agrega la siguiente línea al crontab del usuario del servidor (`crontab -e`):
```cron
* * * * * cd /var/www/tecni-systemas && php artisan schedule:run >> /dev/null 2>&1
```

### Windows (Programador de Tareas):
Crea una tarea programada repetida cada minuto que ejecute:
```powershell
powershell.exe -ExecutionPolicy Bypass -Command "cd C:\ServBay\www\tecni-systemas; php artisan schedule:run"
```

---

## 🧪 7. Verificación Post-Despliegue (Smoke Test)

Ejecuta las siguientes comprobaciones para validar el correcto funcionamiento:

1. **Estado de rutas y seguridad:**
   ```bash
   php artisan route:list --path=_dusk
   # Debe retornar: "No routes found."
   ```

2. **Verificación de caché activa:**
   ```bash
   php artisan about
   # Verificar que Configuration, Routes y Events indiquen CACHED.
   ```

3. **Prueba de acceso web:**
   - Navega a `https://tu-dominio/login` y verifica que carguen los estilos y fuentes correctamente.
   - Accede a `https://tu-dominio/.env` y confirma que devuelva **403 Forbidden** o **404 Not Found**.
   - Ingresa como Administrador y verifica el módulo de Configuración de Empresa y Caja.

---

## 🔄 8. Procedimiento de Rollback (Reversión de Emergencia)

Si se presenta un inconveniente crítico tras un despliegue:

1. **Restaurar base de datos desde respaldo previo:**
   ```bash
   # Vía comando Artisan
   php artisan app:backup-db
   ```
2. **Revertir código fuente:**
   ```bash
   git checkout <tag-o-commit-anterior-estable>
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   ```
