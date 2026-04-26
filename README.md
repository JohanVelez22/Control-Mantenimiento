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
- **Seguridad**: Sistema de roles (Administrador/Técnico) y control de acceso por middleware.
- **Localización**: Configurado para el contexto de Colombia (moneda, formatos y datos de prueba locales).

## 🛠️ Tecnologías y Librerías

- **Backend**: Laravel 13 (PHP 8.3+)
- **Base de Datos**: MySQL / MariaDB
- **Frontend**: Blade, CSS Vanilla (Premium Design), FontAwesome
- **Generación de PDF**: `barryvdh/laravel-dompdf`
- **Gestión de Excel**: `maatwebsite/excel`
- **Datos de Prueba**: Datos de pruebas locales Colombia (es_CO)

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
   * Crea una base de datos vacía en tu gestor (ej. `mantenimiento_db`).
   * Copia el archivo `.env.example` a `.env`.
   * Abre `.env` y configura el nombre de tu base de datos, usuario y contraseña.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Inicialización Automática (Recomendado):**
   He creado un comando especial que se encarga de crear la base de datos, ejecutar migraciones y cargar datos de prueba para Colombia:
   ```bash
   php artisan db:setup
   ```

## 🔐 Credenciales de Acceso (Pruebas)

- **Administrador:** `admin@example.com`
- **Contraseña:** `Admin123*`
- **Técnico:** (Generados aleatoriamente por el comando setup)

## 📁 Estructura de Comandos Especiales

He incluido comandos personalizados para facilitar la puesta en marcha y mantenimiento:

- `php artisan db:setup`: Inicializa todo el sistema desde cero (Base de datos + Tablas + Datos de prueba realistas).
- `composer db:setup`: Alias rápido del comando anterior para una inicialización en un solo paso.

---
*Desarrollado por Johan Velez y Santiago Zapata*
