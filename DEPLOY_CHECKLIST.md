# 🚀 Checklist de Despliegue a Producción — Tecni-Systemas

Esta guía contiene los pasos exactos y seguros para desplegar **Tecni-Systemas** en un servidor de producción (VPS, Cloud Server, Hostinger, AWS, DigitalOcean, etc.) garantizando alta velocidad, aislamiento de datos y máxima seguridad.

---

## 📋 1. Configuración de Variables de Entorno (`.env`)

En tu servidor de producción, crea el archivo `.env` basándote en `.env.example` y define las siguientes directivas críticas:

```dotenv
# Identidad y Modo de Ejecución
APP_NAME="Tecni-Systemas"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

# Clave de Cifrado (Generar con: php artisan key:generate)
APP_KEY=base64:...

# Base de Datos de Producción
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tecni_systemas_prod
DB_USERNAME=usuario_seguro
DB_PASSWORD=ContraseñaUltraSegura123!

# Sesiones y Cookies Blindadas (Requiere HTTPS activo)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Cache y Colas
CACHE_STORE=database
QUEUE_CONNECTION=database

# Configuración de Correo Real (Ejemplo SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@tudominio.com
MAIL_PASSWORD=tu_password_smtp
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@tudominio.com"
MAIL_FROM_NAME="${APP_NAME}"

# Claves de Registro y Administración
ADMIN_DEFAULT_PASSWORD="ContraseñaAdminProduccion2026!"
ADMIN_REGISTRATION_PASSWORD="ClaveExclusivaRegistroTecnicos2026!"
```

> [!CAUTION]
> **Nunca** establezcas `APP_DEBUG=true` en un servidor público. Si ocurre un error con `APP_DEBUG=true`, Laravel expondrá tus contraseñas de base de datos y llaves secretas en pantalla.

---

## 🛠️ 2. Comandos de Instalación y Construcción

Ejecuta en la terminal del servidor (dentro del directorio del proyecto):

### 2.1. Dependencias Backend (PHP / Composer)
```bash
composer install --no-dev --optimize-autoloader
```

### 2.2. Dependencias Frontend (Assets / Vite)
```bash
npm ci
npm run build
```

### 2.3. Enlace Simbólico de Almacenamiento
```bash
php artisan storage:link
```

### 2.4. Migraciones de Base de Datos
```bash
php artisan migrate --force
```

---

## ⚡ 3. Optimización de Caché para Rendimiento Extremo

Laravel compila rutas, vistas y configuración para no leer archivos de disco en cada petición HTTP:

```bash
# Cachear configuración del .env
php artisan config:cache

# Cachear catálogo de rutas
php artisan route:cache

# Pre-compilar plantillas Blade
php artisan view:cache

# Cachear eventos (si aplica)
php artisan event:cache
```

> [!TIP]
> Si en algún momento modificas el archivo `.env` o rutas en producción, recuerda ejecutar:
> `php artisan optimize:clear && php artisan config:cache && php artisan route:cache`

---

## 🔐 4. Permisos de Directorios en Linux (Nginx / Apache)

Asegura que el servidor web (`www-data`) tenga permisos de escritura únicamente sobre las carpetas necesarias:

```bash
sudo chown -R www-data:www-data /var/www/tecni-systemas
sudo find /var/www/tecni-systemas -type f -exec chmod 644 {} \;
sudo find /var/www/tecni-systemas -type d -exec chmod 755 {} \;

# Permisos de escritura para almacenamiento y caché
sudo chmod -R 775 /var/www/tecni-systemas/storage
sudo chmod -R 775 /var/www/tecni-systemas/bootstrap/cache
```

---

## ⏰ 5. Cron Job para Tareas Programadas de Laravel

Para que Laravel ejecute backups, mantenimiento de sesiones y tareas automáticas, agrega la siguiente línea al crontab del servidor (`crontab -e`):

```bash
* * * * * cd /var/www/tecni-systemas && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🛡️ 6. Verificación Final de Seguridad y Salud

Antes de entregar a los usuarios finales, verifica:

1. ✅ Que el certificado **SSL / HTTPS** esté activo y redirigiendo desde HTTP.
2. ✅ Que acceder a `https://tudominio.com/.env` devuelva un error **403 Forbidden** o **404 Not Found**.
3. ✅ Probar un flujo completo:
   - Ingresar con un usuario Administrador.
   - Crear un cliente, equipo y orden de servicio.
   - Generar un abono a caja.
   - Generar reporte en PDF.
   - Anular y verificar la restitución de saldo y stock.
