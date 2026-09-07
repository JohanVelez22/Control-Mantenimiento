# 🚀 GUÍA DEFINITIVA DE INSTALACIÓN Y DESPLIEGUE EN SERVBAY (DESDE CERO)
## Proyecto: TECNI SYSTEMAS - Sistema de Control de Mantenimiento, Taller e Integridad Financiera
## Diseñado para: Usuarios sin conocimientos previos de programación y despliegue en Producción

---

## 🌟 1. ¿Qué es ServBay y por qué se utiliza?
**ServBay** es un entorno de desarrollo todo-en-uno que instala en tu computador Servidor Web (Nginx/Apache), PHP (versiones 8.2, 8.3, 8.4), Gestor de Bases de Datos (MySQL/MariaDB) y phpMyAdmin sin necesidad de configurar manualmente servidores complicados.

---

## 📋 2. Requisitos Previos (Descargas Gratuitas)
Antes de empezar, asegúrate de tener instalado en tu computador:
1. **ServBay**: [https://www.servbay.com/](https://www.servbay.com/) (Instalador para Windows o macOS).
2. **Node.js LTS**: [https://nodejs.org/](https://nodejs.org/) (Para compilar los estilos visuales Liquid Glass).
3. **Composer**: [https://getcomposer.org/](https://getcomposer.org/) (Gestor de dependencias de PHP).
4. **Git** (Opcional, si clonas el repositorio): [https://git-scm.com/](https://git-scm.com/).

---

## 🛠️ 3. Instalación Paso a Paso en ServBay (Guía para Principiantes)

### 🔹 Paso 1: Ubicación del Proyecto
1. Abre tu explorador de archivos y dirígete a la carpeta raíz de ServBay:
   - **En Windows:** `C:\ServBay\www\`
   - **En macOS:** `/Applications/ServBay/db/www/`
2. Pega o clona la carpeta del proyecto en esta ruta para que quede así:
   `C:\ServBay\www\tecni-systemas`

---

### 🔹 Paso 2: Crear el Host / Dominio Local en la App de ServBay
1. Abre el panel visual de **ServBay**.
2. Ve a la pestaña **Hosts** (o **Sitios Web**) y haz clic en el botón **`+` (Añadir Host)**.
3. Configura los siguientes campos:
   - **Nombre / Dominio**: `tecni-systemas.local` (o `tecni-systemas.servbay.demo`)
   - **Tipo**: `PHP`
   - **Versión de PHP**: Selecciona `PHP 8.3` o `PHP 8.4`
   - **Directorio Raíz (Document Root)**: ⚠️ **MUY IMPORTANTE**: Selecciona la subcarpeta `public`:
     `C:\ServBay\www\tecni-systemas\public`
   - **SSL**: Activa la casilla de certificado SSL local si deseas `https://`.
4. Haz clic en **Guardar**. ServBay creará el sitio automáticamente.

---

### 🔹 Paso 3: Crear la Base de Datos en MySQL de ServBay
1. En el panel de ServBay, asegúrate de que el servicio **MySQL** esté iniciado (en color verde).
2. Abre tu navegador e ingresa a phpMyAdmin o Adminer (incluido en ServBay) o abre la consola MySQL.
3. Crea una base de datos nueva:
   - **Nombre de la base de datos**: `tecni_systemas`
   - **Cotejamiento (Collation)**: `utf8mb4_unicode_ci`

---

### 🔹 Paso 4: Configurar el archivo de entorno `.env`
1. Dentro de `C:\ServBay\www\tecni-systemas`, busca el archivo `.env.example`.
2. Haz una copia de ese archivo y renómbralo exactamente a: `.env`
3. Abre el archivo `.env` con un editor de texto (Bloc de Notas, VS Code, etc.) y ajusta estos valores:
   ```ini
   APP_NAME="Tecni Systemas"
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://tecni-systemas.local

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tecni_systemas
   DB_USERNAME=root
   DB_PASSWORD=ServBay.dev
   ```
   *(Nota: Por defecto en ServBay la contraseña de `root` en MySQL suele ser `ServBay.dev` o `root` o en blanco. Usa la que corresponda a tu instalación de ServBay).*

---

### 🔹 Paso 5: Instalar Dependencias y Generar la Clave de Seguridad
Abre la terminal (PowerShell en Windows o Terminal en Mac) y ubícate en la carpeta del proyecto:
```bash
cd C:\ServBay\www\tecni-systemas
```

Ejecuta los siguientes comandos uno por uno:

1. **Instalar paquetes backend de PHP:**
   ```bash
   composer install
   ```
2. **Generar la clave secreta de la aplicación:**
   ```bash
   php artisan key:generate
   ```
3. **Instalar y compilar los estilos visuales (CSS / JavaScript):**
   ```bash
   npm install
   npm run build
   ```

---

### 🔹 Paso 6: Construir la Base de Datos y Crear Usuarios Iniciales
Ejecuta el siguiente comando para crear automáticamente todas las tablas, índices financieros y usuarios base:
```bash
php artisan migrate:fresh --seed
```

*(Opcional para pruebas y auditoría manual)* Si deseas poblar la base de datos con **5 registros reales y coherentes por cada módulo** (Clientes, Proveedores, Técnicos, Equipos, Stock, Cotizaciones, Facturas, Mantenimientos, Electrónica y Caja):
```bash
php artisan app:seed-demo-data --force
```

---

### 🔹 Paso 7: Activar Acceso a Fotos de Repuestos y Perfiles
Para que las imágenes subidas se vean correctamente en el navegador:
```bash
php artisan storage:link
```

---

### 🔹 Paso 8: Limpiar Caché del Sistema
```bash
php artisan optimize:clear
```

---

## 🔑 4. Credenciales de Acceso al Sistema

Una vez finalizado el Paso 8, abre tu navegador e ingresa a `http://tecni-systemas.local` (o `http://localhost:8000` si usas `php artisan serve`):

| Rol / Perfil | Correo Electrónico | Contraseña Inicial | Acceso y Permisos |
| :--- | :--- | :--- | :--- |
| **👑 Administrador** | `administrador@tecnisystemas.com` | `Admin123*` | Control total, finanzas, caja, usuarios, anulaciones y reportes. |
| **🛠️ Técnico** | `tecnico@tecnisystemas.com` | `Tecni123*` | Gestión de órdenes, reparación electrónica, abonos y repuestos. |
| **👁️ Invitado / Consulta** | `invitado@tecnisystemas.com` | `Invit123*` | Portal público y de clientes para consultar estado de órdenes. |

---

## 🛡️ 5. Comandos de Mantenimiento y Auditoría del Sistema

El proyecto cuenta con comandos artesanales de autodiagnóstico:

* **Auditoría profunda de integridad financiera y seguridad:**
  ```bash
  php artisan system:audit --deep
  ```
  *(Verifica en segundos 28 reglas críticas de negocio: caja, stock, compras, ventas, abonos y contraseñas).*

* **Copia de seguridad automática de la base de datos:**
  ```bash
  php artisan db:backup
  ```

* **Ejecución de la suite completa de pruebas unitarias y de integración:**
  ```bash
  php artisan test
  ```

---

## ☁️ 6. Despliegue en Servidor de Producción (VPS / cPanel)

Cuando vayas a subir este proyecto a un servidor web en internet real:
1. Configura en el archivo `.env`:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tudominio.com
   ```
2. Ejecuta los comandos de optimización en memoria:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   ```
3. Otorga permisos de escritura (`chmod -R 775`) a las carpetas `storage/` y `bootstrap/cache/`.
