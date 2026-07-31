# 📘 DOCUMENTACIÓN TÉCNICA Y FUNCIONAL DEL PROYECTO
## System Name: Control Mantenimiento Equipos
## Stack: Laravel 12 / PHP 8.3 / MySQL / Tailwind CSS & Glassmorphism UI

---

## 1. RESUMEN DEL SISTEMA

Sistema web integral para la gestión de servicios técnicos, mantenimiento de equipos, reparación de tarjetas electrónicas, control de inventario/repuestos, cotizaciones, facturación POS y flujo de caja diario con control de acceso por roles.

**Caso de uso:** Empresa de servicios técnicos que repara equipos de cómputo, laptops y tarjetas electrónicas. Necesita controlar órdenes de servicio, manejar inventario de repuestos, facturar a clientes, registrar abonos/parcialidades y llevar el control de caja diario con cierre y auditoría.

---

## 2. ARQUITECTURA DEL SISTEMA

### 2.1 Patrón MVC + Service Layer

```
app/
├── Http/
│   ├── Controllers/          # Controladores (rutas → lógica → vistas)
│   └── Middleware/           # Permisos por rol
├── Models/                   # Eloquent Models (12 modelos)
├── Services/                 # Lógica de negocio reutilizable (3 servicios)
└── Traits/                   # Traits reutilizables (3 traits)
```

### 2.2 Roles de Usuario (RBAC)

| Rol | Descripción | Permisos |
|---|---|---|
| **Administrador (`admin`)** | Acceso total | Crear, Editar, Eliminar, Anular. Caja, usuarios, reportes. |
| **Técnico (`tecnico`)** | Operario de taller | Registrar/editar órdenes, repuestos, abonos. Anular con clave admin. |
| **Invitado (`invitado`)** | Solo lectura | Buscar, filtrar, ver detalles. Sin modificaciones. |

**Mecanismo de permisos:**
- Middleware `role:admin` / `role:tecnico` en rutas
- Métodos helper en `User` model: `isAdmin()`, `isTecnico()`, `isInvitado()`
- Anulación: el técnico debe confirmar con contraseña de administrador

### 2.3 Servicios (Service Layer Pattern)

#### `AnulacionService`
- **Responsabilidad:** Unifica la lógica de anulación/reactivación (antes duplicada ~280 líneas entre `MantenimientoController` y `ElectronicaController`)
- **Funcionalidades:**
  - `adminPasswordValida()` — verifica contraseña contra todos los admins
  - `passwordValida()` — verifica contra el usuario en sesión o cualquier admin
  - `revertirStockYAbonos()` — revierte stock y marca movimientos de caja como anulados

#### `OrdenService`
- **Responsabilidad:** Generación atómica y concurrente-safe de números de orden (`ORD-1`, `ELC-0001`)
- **Técnica:** `lockForUpdate()` dentro de transacción DB para evitar race conditions

#### `StockService`
- **Responsabilidad:** Movimientos atómicos de inventario (entrada/salida)
- **Técnica:** `lockForUpdate()` + validación de stock suficiente antes de decrementar
- **Prevención:** Lanza `DomainException` si no hay stock suficiente

### 2.4 Traits Reutilizables

#### `Auditable`
- **Responsabilidad:** Registro automático de eventos de auditoría (created, updated, deleted, anulado)
- **Característica:** Enmascara datos sensibles (email, teléfonos, identificación) en logs
- **Uso:** `use Auditable` en modelos: `Mantenimiento`, `Electronica`, `Stock`, `Factura`, `User`, etc.

#### `HandlesAbono`
- **Responsabilidad:** Lógica para registrar y eliminar abonos (pagos parciales)
- **Uso:** En `MantenimientoAbonoController` y `ElectronicaAbonoController` (elimina duplicación)

#### `HandlesStockAttach`
- **Responsabilidad:** Añadir/quitar repuestos a órdenes de trabajo
- **Uso:** En `MantenimientoStockController` y `ElectronicaStockController`

---

## 3. MODELOS Y RELACIONES

### Modelo Entidad-Relación (Resumen)

```
User (admin/tecnico/invitado)
  └── hasMany → Mantenimiento, Electronica, Stock, Factura, MovimientoCaja

Cliente
  └── hasMany → Equipo, Factura (polimórfica)

Proveedor
  └── hasMany → Stock, Factura (polimórfica)

Equipo
  └── belongsTo → Cliente
  └── hasMany → Mantenimiento, Electronica

Mantenimiento / Electronica (entidades similares)
  ├── belongsTo → Equipo, Tecnico, User
  ├── hasMany → Abono
  └── belongsToMany → Stock (pivot: cantidad, precio_unitario)

Stock
  └── belongsTo → Proveedor
  └── belongsToMany → Mantenimiento/Electronica (pivot)

Factura
  ├── morphTo → facturable (Cliente o Proveedor)
  └── hasMany → FacturaItem

Abono
  └── belongsTo → Mantenimiento/Electronica, User

MovimientoCaja
  ├── belongsTo → ConceptoCaja, User
  └── belongsTo → parent (auto-referencia para abonos)

ConceptoCaja
  └── hasMany → MovimientoCaja

CierreCaja
  └── (cierre diario con snapshot de saldos)
```

### Polimorfismo
- `Factura` usa `facturable_type` / `facturable_id` para asociar con `Cliente` o `Proveedor`
- `Abono` usa `mantenimiento_id` o `electronica_id` (FK dinámica según modelo)

---

## 4. MÓDULOS PRINCIPALES Y LÓGICA DE NEGOCIO

### A. Mantenimientos & Reparación de Electrónica
- **Consecutivo automático:** `ORD-` (Mantenimiento) / `ELC-` (Electrónica) generado por `OrdenService`
- **Estados:** `Pendiente` → `Terminado` → `Facturado`
- **Tipos:** Preventivo/Correctivo | Software/Hardware
- **Repuestos:** Añadir/quitar con ajuste automático de stock y costo
- **Abonos:** Pagos parciales con registro en caja
- **Anulación:** Reversa de stock + caja (usando `AnulacionService`)

### B. Clientes & Clasificación de Tarifas
- **Tipos de cliente:** `Cliente Normal` vs `Técnico`
- **Regla de precios:**
  - Cliente Normal → Precio Venta
  - Técnico → Precio Técnico (margen reducido)

### C. Inventario & Control de Stock
- **Fórmulas de precios:**
  - `P. Venta = P. Compra × (1 + Utilidad%)`
  - `P. Técnico = P. Compra × (1 + Utilidad%/2)`
- **Categorías/Subcategorías:** Gestión con `CategoriaStock`
- **Fotos de productos:** Upload a `storage/stocks`
- **Historial:** Trazabilidad de todas las entradas/salidas

### D. Facturación POS & Caja Chica
- **Tipos de movimiento:** Ingreso / Egreso
- **Tipos de pago:** Efectivo / Consignación (banco)
- **Saldos:** `monto` (pagado hoy) vs `monto_total` (deuda total)
- **Abonos:** Movimientos hijos que reducen saldo pendiente
- **Cierre diario:** Snapshot con cálculo automático de ingresos/egresos/efectivo/consignación
- **Anulación lógica:** `anulado = true` (nunca borrado físico)

### E. Cotizaciones
- **Flujo:** Pendiente → Aprobar y Facturar (crea Factura) / Rechazar / Reactivar
- **Items dinámicos:** Tabla con filas agregables vía JavaScript
- **Validez:** Fecha límite configurable

---

## 5. ESTÁNDARES DE DISEÑO UI/UX (Glassmorphism)

### 5.1 Sistema CSS Global
- **Archivo maestro:** `public/css/glass.css` (~86 KB)
- **Variables CSS:** Colores de acento por módulo (blue, emerald, orange, purple, teal, amber, indigo, red)
- **Modo claro/oscuro:** Media query `prefers-color-scheme` + toggle manual en topbar

### 5.2 Componentes UI Estandarizados

| Clase CSS | Uso |
|---|---|
| `glass-card` | Contenedor principal con efecto vidrio |
| `glass-input` | Inputs y selects con estilo vidrio |
| `glass-table` / `ts-table` | Tablas con resaltado de filas |
| `btn-save` | Botón submit principal (💾 Guardar / 🔄 Actualizar) |
| `btn-cancel` | Botón cancelar (↩️) |
| `btn-danger` | Botón acción destructiva (🚫 Anular / 🗑️ Eliminar) |
| `btn-ghost` | Botón acción secundaria (iconos) |
| `btn-primary` | Botón acción destacada (usado en contextos específicos) |
| `btn-compra` / `btn-venta` | Botones contextuales de inventario |
| `pill`, `pill-done`, `pill-pending`, `pill-anulado` | Badges de estado |
| `highlight-row` | Animación de resaltado al navegar por ancla `#id` |

### 5.3 Patrón de Navegación por Anclas (Hash Highlighting)
- Todas las filas de tabla tienen: `<tr id="modulo-{id}" class="scroll-mt-[6.5rem]">`
- Al hacer clic en un enlace como `#mantenimiento-42`, la fila se resalta con animación
- Implementación dual: CSS `tr:target` + JS `.active-target` (fallback para navegación)
- **10 vistas index** implementan este patrón consistentemente

### 5.4 Patrón de Formularios (DRY con Partials)
- Todos los formularios CRUD usan `@include('modulo._form')`
- Los botones de submit/cancel van en el partial `_form`, no en create/edit
- **7 partials `_form` creados:** tecnicos, equipos, mantenimientos, usuarios, stocks, clientes, proveedores, electronicas
- **Campo de admin password:** Se muestra condicionalmente en `_form` para técnicos (edit only)

### 5.5 Estado Visual de Anulación
- **Filas anuladas:** `opacity-60 grayscale` + texto gris
- **Botones anular:** `text-red-600` (anular) / `text-emerald-600` (reactivar)
- **Badges:** `pill-anulado` (rojo) / `pill-done` (verde) / `pill-pending` (amarillo)

---

## 6. BASE DE DATOS Y MIGRACIONES

### 6.1 Estructura de Migraciones
- **Migraciones base:** 2026_04_19 (create tables iniciales)
- **Migraciones de evolución:** 2026_06_xx, 2026_07_xx (añaden campos, índices, estados)
- **Migraciones de performance:** 2026_07_13 (índices compuestos para auditoría)
- **Migraciones de schema:** 2026_07_15 (estandarización de schema)

### 6.2 Índices de Performance
- Índices compuestos en `movimientos_cajas` para filtrado por fecha/tipo/estado
- Índices en `eventos` para auditoría por usuario/fecha/accion
- Índice `anulado` en tablas principales para filtrado rápido

### 6.3 Columnas Generadas (Computed)
- `Factura`: `saldo_pendiente`, `saldo_a_favor` (columnas virtuales)
- `MovimientoCaja`: `saldo_pendiente` (monto_total - monto - abonos)

---

## 7. TESTS Y CALIDAD DE CÓDIGO

### 7.1 Tests de Integración (`tests/Integration/IntegracionCompletaTest.php`)
- **18 tests** cubriendo:
  - Atomicidad de StockService (entrada/salida/concurrencia)
  - Flujos completos: Compra → Venta → Anulación
  - Saldos financieros (pendiente_pago, saldo_a_favor)
  - Anulación de facturas con reversa de stock
  - Formato de números (puntos de miles, ceros iniciales)
  - Scopes y roles

### 7.2 Tests de Auditoría (`tests/Feature/AuditFindingsTest.php`)
- **15 tests** usando `RefreshDatabase`
- Validación de parsing de números con separador de miles
- Flujos de compra/venta con validaciones de stock
- Anulación/reactivación con reversa de stock
- Edición de facturas con recalculo de totales
- Casos edge: múltiples puntos, ceros iniciales, valores vacíos

---

## 8. ARCHIVOS DE SOPORTE (No críticos para producción)

| Archivo | Propósito | Ubicación |
|---|---|---|
| `scratch/clean_db.php` | Limpieza de BD para pruebas | Desarrollo |
| `scratch/check_cols.php` | Verificación de columnas | Desarrollo |
| `scratch/mcp_server.py` | Servidor MCP para NotebookLM | Integración docs |
| `DOCUMENTACION_PROYECTO_NOTEBOOKLM.md` | Docs para NotebookLM | Raíz |

---

## 9. GUÍA PARA CARGAR EN NOTEBOOKLM
1. Descarga/Copia este archivo (`DOCUMENTACION_PROYECTO_NOTEBOOKLM.md`).
2. Entra a [notebooklm.google.com](https://notebooklm.google.com).
3. Crea un nuevo **Cuaderno** titulado "Control Mantenimiento Equipos".
4. En **Fuentes**, selecciona **Subir archivo** o **Copiar texto** y pega este documento.
5. ¡Listo! Ya puedes pedirle a NotebookLM que genere resúmenes, guías de estudio, episodios de audio o preguntas frecuentes sobre tu proyecto.