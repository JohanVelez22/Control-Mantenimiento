# 📘 DOCUMENTACIÓN TÉCNICA Y FUNCIONAL DEL PROYECTO
## System Name: Control Mantenimiento Equipos
## Stack: Laravel 12 / PHP 8.3 / MySQL / Tailwind CSS & Glassmorphism UI

---

## 1. RESUMEN DEL SISTEMA
Sistema web integral para la gestión de servicios técnicos, mantenimiento de equipos, reparación de tarjetas electrónicas, control de inventario/repuestos, cotizaciones, facturación POS y flujo de caja diario con control de acceso por roles.

---

## 2. ARQUITECTURA Y ROLES DE USUARIO (RBAC)

### Roles del Sistema:
1. **Administrador (`admin`)**:
   - Acceso total (Crear, Editar, Eliminar, Anular).
   - Apertura, monitoreo y cierre de caja chica.
   - Gestión de usuarios y contraseñas.
   - Anulación con confirmación de clave administrativa.

2. **Técnico (`tecnico`)**:
   - Registro y actualización de órdenes de servicio (Mantenimiento y Electrónica).
   - Asignación de repuestos e insumos desde inventario.
   - Generación de facturas de servicio.

3. **Invitado (`invitado`)**:
   - Acceso exclusivo de **Solo Lectura** (`👁️ Lectura`).
   - Búsqueda y filtrado de registros sin permisos de modificación ni anulación.

---

## 3. MÓDULOS PRINCIPALES Y LÓGICA DE NEGOCIO

### A. Mantenimientos & Reparación de Electrónica
- Registro por consecutivo automático de orden (`id_orden`).
- Seguimiento de estados: `Pendiente` (En Revisión/Taller) y `Terminado` (Listo para entrega/Facturación).
- Control de fechas (Entrada y Salida).
- Cálculo automático de días en servicio.

### B. Clientes & Clasificación de Tarifas
- Tipos de cliente: `Cliente Normal` vs `Técnico`.
- **Regla de Negocio de Precios**:
  - `Cliente Normal`: Aplica Precio Venta sugerido.
  - `Técnico`: Aplica automáticamente tarifa de **Precio Técnico** (descuento corporativo de insumos).

### C. Inventario & Control de Stock
- Fórmulas de Precio:
  - `P. Compra`: Precio base de adquisición del repuesto.
  - `Utilidad (%)`: Porcentaje de margen configurado (Ej: 30%).
  - `P. Venta`: $P. Compra \times (1 + \frac{Utilidad}{100})$
  - `P. Técnico`: $P. Compra \times (1 + \frac{Utilidad}{200})$

### D. Caja Chica & Movimientos Financieros
- Control de saldo inicial, ingresos por servicios/ventas y egresos.
- Cierre de caja con balance dinámico y auditoría.
- Anulación lógica (`anulado = true`) en lugar de borrado físico para garantizar trazabilidad contable.

---

## 4. ESTÁNDARES DE DISEÑO Y UI (Glassmorphism)
- Sistema visual basado en `public/css/glass.css`.
- Soporte para Modo Oscuro y Modo Claro.
- Resaltado dinámico por ancla URL (`#id`).
- Formulario modular mediante partials reutilizables `@include('[modulo]._form')`.

---

## 5. GUÍA PARA CARGAR EN NOTEBOOKLM
1. Descarga/Copia este archivo (`DOCUMENTACION_PROYECTO_NOTEBOOKLM.md`).
2. Entra a [notebooklm.google.com](https://notebooklm.google.com).
3. Crea un nuevo **Cuaderno** titulado "Control Mantenimiento Equipos".
4. En **Fuentes**, selecciona **Subir archivo** o **Copiar texto** y pega este documento.
5. ¡Listo! Ya puedes pedirle a NotebookLM que genere resúmenes, guías de estudio, episodios de audio o preguntas frecuentes sobre tu proyecto.
