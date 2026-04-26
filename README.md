# Sistema de Control de Mantenimiento de Equipos 🇨🇴

![Dashboard del Sistema](dashboard.png)

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Colombia](https://img.shields.io/badge/Localización-Colombia-yellow?style=for-the-badge)](https://fakerphp.github.io)

Este es un sistema web robusto desarrollado en Laravel para la gestión integral de mantenimientos técnicos, equipos y clientes. Ideal para talleres de soporte técnico o departamentos de TI, con una interfaz moderna y optimizada.

## 🚀 Características Principales

- **Gestión de Clientes**: Registro detallado de clientes con múltiples equipos asociados.
- **Control de Inventario**: Seguimiento de equipos por marca, modelo y número de serie.
- **Mantenimientos Técnicos**: Registro de servicios (Preventivo/Correctivo), tipo de reparación (Hardware/Software) y costos redondeados.
- **Dashboard Estadístico**: Métricas en tiempo real sobre costos totales, estados de órdenes y navegación inteligente.
- **Reportes Avanzados**: Filtros dinámicos por fechas, clientes y técnicos con exportación a **PDF** y **Excel**.
- **Seguridad**: Sistema de roles (Administrador/Técnico) y control de acceso por middleware.
- **Localización**: Configurado para el contexto de Colombia (moneda, formatos y datos de prueba locales).

## 🛠️ Tecnologías Utilizadas

- **Backend**: Laravel 13 (PHP 8.3+)
- **Base de Datos**: MySQL / MariaDB
- **Frontend**: Blade, CSS Vanilla (Premium Design), FontAwesome
- **Reportes**: Laravel Excel, DomPDF
- **Datos de Prueba**: Faker (es_CO)

## 📦 Instalación

Sigue estos pasos para poner el proyecto en marcha:

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/tu-usuario/control-mantenimiento-equipos.git
   cd control-mantenimiento-equipos
   ```

2. **Instalar dependencias:**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Configurar el entorno:**
   Copia el archivo `.env.example` a `.env` y configura tus credenciales de base de datos.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Inicialización Automática (Recomendado):**
   He creado un comando especial que se encarga de crear la base de datos, ejecutar migraciones y cargar datos de prueba localizados para Colombia:
   ```bash
   php artisan db:setup
   ```

## 🔐 Credenciales de Acceso (Pruebas)

- **Administrador:** `admin@example.com` / `Admin123*`
- **Técnico:** (Generados aleatoriamente por el comando setup)

## 📁 Estructura de Comandos Especiales

He incluido comandos personalizados para facilitar la puesta en marcha y mantenimiento:

- `php artisan db:setup`: Inicializa todo el sistema desde cero (Base de datos + Tablas + Datos de prueba realistas).
- `composer db:setup`: Alias rápido del comando anterior para una inicialización en un solo paso.

---
*Desarrollado con ❤️ para la gestión técnica eficiente.*
