# 📑 Product Requirements Document (PRD) - Cero a Cien 🇨🇴

## 1. Información General
- **Nombre del Proyecto:** Cero a Cien - Control de Mantenimiento de Equipos
- **Versión:** 1.0.0
- **Objetivo:** Proporcionar una plataforma web integral para gestionar el mantenimiento técnico de equipos, controlando clientes, inventario de dispositivos, personal técnico y costos de servicio.

## 2. Roles de Usuario y Permisos
### 2.1 Administrador (Admin)
- Control total sobre el sistema.
- Gestión de usuarios (Activar/Desactivar, cambiar roles, resetear contraseñas).
- CRUD completo de Clientes, Equipos, Técnicos y Mantenimientos.
- Acceso a reportes detallados y exportación de datos (Excel/PDF).
- Visualización de estadísticas globales en el Dashboard.

### 2.2 Técnico
- Acceso limitado al sistema.
- Visualización de Dashboard (estadísticas básicas y mantenimientos recientes).
- Gestión de clientes y equipos.
- Registro y visualización de mantenimientos.
- **Restricción:** No puede eliminar registros críticos ni gestionar usuarios del sistema.

## 3. Requisitos Funcionales

### 3.1 Autenticación y Seguridad
- **Registro de Usuarios:** Permite elegir rol (Técnico/Admin). El rol Admin requiere una clave de autorización secreta (`ADMIN_REGISTRATION_PASSWORD`). (la contraseña es: Control2026*)
- **Validación de Contraseñas:** Mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número.
- **Estado de Usuario:** Los usuarios pueden ser desactivados por un Admin, impidiéndoles el acceso al sistema.
- **Middleware:** Protección de rutas según el rol y estado de autenticación.
- **Revisar el tema claro y oscuro** Tanto en el login, registro y dashboard, que funcione y que se mantenga el estado al navegar entre páginas y/o recargar la página.

### 3.2 Gestión de Clientes y Equipos
- **Clientes:** Registro de nombre, identificación (cédula/NIT), celular y correo.
- **Equipos:** Vinculados a un cliente. Registro de nombre (tipo), marca, modelo, número de serie y observaciones iniciales.

### 3.3 Gestión de Mantenimientos (Núcleo del Negocio)
- **Orden de Trabajo:** Generación automática de IDs de orden secuenciales (ej: `ORD-1`, `ORD-2`).
- **Datos de Orden:** Tipo de mantenimiento (Preventivo/Correctivo), tipo de reparación (Software/Hardware), descripción del problema/solución, y técnico asignado.
- **Costos:** Cálculo y registro de costos en pesos colombianos, redondeados a múltiplos de 5,000 para facilitar transacciones comerciales.
- **Tiempos:** Registro automático de fecha de entrada y fecha de salida (al terminar el mantenimiento).

### 3.4 Dashboard y Navegación
- **Métricas:** Conteo total de equipos, mantenimientos totales, órdenes pendientes y terminadas, y costo total acumulado.
- **Navegación Contextual:** Los IDs de orden en las tablas son enlaces que llevan a la lista principal, centrando y resaltando la fila seleccionada.

### 3.5 Reportes y Exportación
- **Filtros Avanzados:** Filtrado por cliente, equipo, técnico, rango de fechas y estado del servicio.
- **Exportación:** Generación de archivos Excel (`maatwebsite/excel`) y documentos PDF (`dompdf`) listos para impresión.

## 4. Requisitos No Funcionales
- **Interfaz de Usuario (UI):** Diseño premium basado en CSS Vanilla con soporte nativo para **Modo Oscuro** persistente.
- **Localización:** Configuración de idioma español (`es`) y zona horaria de Colombia.
- **Rendimiento:** Optimización de consultas de base de datos mediante Eloquent ORM.
- **Portabilidad:** Comando de inicialización rápida `php artisan db:setup` para despliegue inmediato.

## 5. Reglas de Negocio Específicas
- Un mantenimiento no puede ser marcado como "Terminado" sin asignar una fecha de salida.
- Los nombres y direcciones en los datos de prueba deben ser coherentes con la geografía colombiana (Pereira, Dosquebradas, etc.).
- Las contraseñas deben cumplir los criterios de seguridad antes de permitir el guardado tanto en registro como en edición.

---
*Este documento define el alcance y comportamiento esperado del sistema "Cero a Cien".*
