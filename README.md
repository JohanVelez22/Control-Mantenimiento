# Sistema de Control de Mantenimiento de Equipos

![Dashboard del Sistema](dashboard.png)

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)

Este es un sistema web robusto desarrollado en Laravel para la gestión integral de mantenimientos técnicos, equipos y clientes. Ideal para talleres de soporte técnico o departamentos de TI, con una interfaz moderna y optimizada.

## 🚀 Características Principales

- **Gestión de Clientes**: Registro detallado de clientes con múltiples equipos asociados.
- **Control de Inventario**: Seguimiento de equipos por marca, modelo y número de serie.
- **Mantenimientos Técnicos**: Registro de servicios (Preventivo/Correctivo), tipo de reparación (Hardware/Software) y costos redondeados.
- **Dashboard Estadístico**: Métricas en tiempo real sobre costos totales, estados de órdenes y navegación inteligente.
- **Reportes Avanzados**: Filtros dinámicos por fechas, clientes y técnicos con exportación a **PDF** y **Excel**.
- **Localización**: Configurado para el contexto de Colombia (moneda, formatos y datos de prueba locales).

## 🛡️ Seguridad y Control de Acceso (NUEVO)

El sistema cuenta con medidas de seguridad de nivel empresarial:

- **Sistema de 3 Roles**: 
  - `Administrador`: Acceso total, incluyendo eliminación de registros y gestión de usuarios.
  - `Técnico`: Capacidad para crear y editar registros, pero sin permisos de eliminación.
  - `Invitado`: Acceso exclusivamente de visualización (Solo lectura).
- **Protección BFCache**: Previene que usuarios deslogueados puedan usar el botón "Atrás" del navegador para ver páginas cacheadas.
- **Cierre por Inactividad**: El sistema expulsa automáticamente a los usuarios tras 3 minutos de inactividad por seguridad.

## 🛠️ Tecnologías y Librerías

- **Backend**: Laravel 13 (PHP 8.3+)
- **Base de Datos**: MySQL / MariaDB
- **Frontend**: Blade, CSS Vanilla (Premium Design), FontAwesome
- **Generación de PDF**: `barryvdh/laravel-dompdf`
- **Gestión de Excel**: `maatwebsite/excel`

## 📋 Requisitos del Sistema

Antes de instalar, asegúrate de tener:
- **Servidor Local**: XAMPP, WAMP o Laragon (con MySQL corriendo).
- **PHP**: Versión 8.2 o superior.
- **Composer**: Para la gestión de dependencias de PHP.
- **Node.js & NPM**: Para compilar los estilos y scripts.

## 📦 Guía de Instalación

Sigue estos pasos detallados para poner el proyecto en marcha:

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/JohanVelez22/Control-Mantenimiento.git
   cd Control-Mantenimiento
   ```

2. **Instalar dependencias de PHP:**
   ```bash
   composer install
   ```

3. **Instalar dependencias de Frontend:**
   ```bash
   npm install && npm run build
   ```

4. **Configurar el entorno:**
   * Crea una base de datos vacía en tu gestor MySQL (ej. `mantenimiento_equipos`).
   * Copia el archivo `.env.example` a `.env`.
   * Abre `.env` y verifica/configura tus credenciales de base de datos.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Inicialización Automática (Recomendado):**
   He creado un comando especial que se encarga de ejecutar migraciones y cargar datos de prueba reales:
   ```bash
   php artisan db:setup
   # (O alternativamente: composer db:setup)
   ```

## 🔐 Credenciales de Acceso (Pruebas)

Al ejecutar la instalación automática, se creará un usuario administrador base:

- **Administrador:** `admin@example.com`
- **Contraseña:** `Admin123*`

> **Nota:** Si un usuario intenta registrarse como Administrador desde la pantalla pública, deberá conocer la clave de autorización definida en la variable de entorno `ADMIN_REGISTRATION_PASSWORD`. De lo contrario, se le asignará el rol de Técnico.

---
*Desarrollado por Johan Velez y Santiago Zapata*
