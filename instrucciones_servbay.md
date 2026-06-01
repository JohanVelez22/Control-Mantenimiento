# Guía de Instalación del Proyecto en ServBay

Si necesitas montar este proyecto desde cero en otra computadora usando **ServBay**, sigue estos pasos en orden para que todo funcione a la primera:

## 1. Preparar el Proyecto
1. Descarga o clona el proyecto dentro de la carpeta de ServBay (recomendado: `C:\ServBay\www\control-mantenimiento-equipos`).
2. Abre tu terminal en esa carpeta y ejecuta:
   ```bash
   composer install
   npm install
   ```
3. Si no tienes un archivo `.env`, haz una copia del archivo `.env.example` y renómbralo a `.env`.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

## 2. Configurar la Base de Datos en ServBay
1. Abre el panel de **ServBay**.
2. Fíjate en el menú lateral izquierdo qué base de datos tiene el **check verde ✅** (usualmente es **MariaDB 11.x** o MySQL).
3. Entra a las opciones de esa base de datos para ver cuál es la contraseña del usuario `root`. Por defecto en ServBay suele ser: `ServBay.dev`.

## 3. Actualizar el archivo `.env`
Abre el archivo `.env` de tu proyecto y asegúrate de tener estos datos en la sección de base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mantenimiento-equipos
DB_USERNAME=root
DB_PASSWORD=ServBay.dev  # (O la contraseña que te mostró ServBay)
```

## 4. Crear la base de datos y migrar
Como cuentas con un comando personalizado (`db:setup`), este paso es el más fácil de todos. Este comando leerá tu `.env`, se conectará a MariaDB de ServBay, creará la base de datos automáticamente y ejecutará las migraciones.

1. Limpia la caché de configuración para que lea tu nuevo `.env`:
   ```bash
   php artisan config:clear
   ```
2. Ejecuta tu comando automatizado:
   ```bash
   php artisan db:setup
   ```
¡Listo! La consola te confirmará que la base de datos fue creada y las tablas importadas.

## 5. Configurar el Sitio Web en ServBay
1. Ve a la pestaña **Sitio Web** (o Hosts) en el panel de ServBay.
2. Añade un nuevo sitio:
   - **Dominio:** ponle el que quieras, por ejemplo `mantenimiento.local`
   - **Directorio Raíz (Document Root):** Es súper importante que selecciones la carpeta `public` de tu proyecto. (Ejemplo: `C:/ServBay/www/control-mantenimiento-equipos/public`).
   - **Versión PHP:** Selecciona la versión de PHP que tengas instalada.
3. Guarda los cambios.

## 6. Compilar el Frontend
Por último, para que se vean bien los estilos y el diseño, ejecuta:
```bash
npm run build
```

¡Listo! Ya puedes entrar al dominio que configuraste (ej. `http://mantenimiento.local`) y el sistema estará 100% funcional en la nueva computadora.

---

## Posibles Errores y Soluciones

### Error "419 Page Expired" al intentar iniciar sesión
Este es un error de seguridad de Laravel relacionado con las sesiones o el token CSRF. Si te ocurre justo después de instalar, revisa lo siguiente:

1. **Tu sesión expiró por inactividad (Lo más común):**
   Si dejaste la página abierta un rato, la sesión expiró.
   * **Solución:** Recarga la página (`F5` o `Ctrl+F5`) e intenta iniciar sesión nuevamente.

2. **Borrado de base de datos:**
   Si el `.env` dice `SESSION_DRIVER=database` y acabas de recrear la base de datos, tu navegador web tiene una "cookie" vieja que no coincide con la nueva base de datos.
   * **Solución:** Borra las cookies del navegador para esa página, o abre el proyecto en una pestaña de Incógnito.

3. **Revisa la configuración en `.env`:**
   Asegúrate de que el tiempo de sesión sea suficientemente largo. En `.env`, cambia `SESSION_LIFETIME=5` a `SESSION_LIFETIME=120` (2 horas).
   Luego corre en la terminal:
   ```bash
   php artisan config:clear
   ```

4. **El APP_URL no coincide:**
   Si entras a `http://mantenimiento.local` pero en tu `.env` dice `APP_URL=http://localhost`, el navegador puede bloquear la sesión.
   * **Solución:** Ajusta el `APP_URL` a la URL real y corre `php artisan config:clear`.
