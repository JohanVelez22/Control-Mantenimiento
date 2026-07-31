# 🚀 CUADERNO 1: GUÍA DE INSTALACIÓN Y DESPLIEGUE PASO A PASO
## Proyecto: Sistema de Control de Mantenimiento de Equipos
## Entorno objetivo: ServBay (Local) / Servidor Producción (Nube)

---

## 1. REQUISITOS DEL SISTEMA
Para ejecutar este proyecto de manera óptima, el servidor o entorno local debe contar con:
- **Servidor Web / Entorno Local**: ServBay (Windows/macOS) o Nginx / Apache.
- **Versión de PHP**: PHP 8.2 o PHP 8.3 (Extensiones requeridas: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `gd`).
- **Base de Datos**: MySQL 8.0+ o MariaDB 10.4+.
- **Gestor de Paquetes**: Composer 2.x.
- **Navegador Recomendado**: Google Chrome, Microsoft Edge, Brave o Firefox.

---

## 2. GUÍA DE INSTALACIÓN PASO A PASO EN SERVBAY (LOCAL)

### Paso 1: Ubicación del Código Fuente
1. Descarga o clona el proyecto dentro del directorio raíz de ServBay:
   `C:\ServBay\www\control-mantenimiento-equipos`

### Paso 2: Configuración del Archivo de Entorno (`.env`)
1. Copia el archivo de plantilla `.env.example` y renómbralo a `.env`:
   ```bash
   cp .env.example .env
   ```
2. Abre el archivo `.env` y verifica los siguientes parámetros clave:
   ```ini
   APP_NAME="Control Mantenimiento Equipos"
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://control-mantenimiento-equipos.servbay.demo

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=control_mantenimiento
   DB_USERNAME=root
   DB_PASSWORD=root
   ```

### Paso 3: Generación de la Clave de Aplicación
Ejecuta en la consola de comandos dentro de la carpeta del proyecto:
```bash
php artisan key:generate
```

### Paso 4: Creación e Inicialización de la Base de Datos
1. En la consola de ServBay o MySQL Workbench / phpMyAdmin, crea una base de datos vacía llamada `control_mantenimiento` con cotejamiento `utf8mb4_unicode_ci`.
2. Ejecuta las migraciones y sembradores de datos de prueba:
   ```bash
   php artisan migrate:fresh --seed
   ```
   *Nota: Este comando creará la estructura completa de tablas y sembrará los 3 usuarios base:*
   - **Administrador**: `admin@admin.com` / Clave: `admin123`
   - **Técnico**: `tecnico@tecnico.com` / Clave: `tecnico123`
   - **Invitado**: `invitado@invitado.com` / Clave: `invitado123`

### Paso 5: Creación del Enlace Simbólico de Almacenamiento
Para permitir la visualización de fotos de repuestos, logotipos y fotos de perfil:
```bash
php artisan storage:link
```

### Paso 6: Limpieza y Optimización de Caché
Ejecuta el script de optimización:
```bash
php artisan optimize:clear
```

---

## 3. CHECKLIST PARA DESPLIEGUE A PRODUCCIÓN / NUBE (CPANEL / VPS)
1. **Cambio de Entorno**: Cambia `APP_ENV=production` y `APP_DEBUG=false` en el `.env` de producción.
2. **Generación de Caché de Alto Rendimiento**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. **Permisos de Archivos**:
   - Asegura permisos de escritura (`775` o `777`) en la carpeta `storage/` y `bootstrap/cache/`.
4. **SSL / HTTPS**: Configura certificado SSL de Let's Encrypt para garantizar navegación cifrada segura.
