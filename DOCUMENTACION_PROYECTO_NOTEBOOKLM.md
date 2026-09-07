# 📘 DOCUMENTACIÓN TÉCNICA Y FUNCIONAL DEL PROYECTO
## System Name: TECNI SYSTEMAS - Sistema de Control de Mantenimiento e Integridad Financiera
## Stack: Laravel 12 / PHP 8.3 / MySQL / Tailwind CSS & Liquid Glass UI (Glassmorphism)

---

## 1. RESUMEN DEL SISTEMA

Sistema web integral de alta gama diseñado para centros de soporte técnico, talleres de reparación de cómputo y electrónica, gestión de repuestos/inventario físico, facturación POS, cotizaciones, control de caja chica y portal de consulta pública para clientes con arquitectura de permisos por roles (RBAC).

**Caso de Uso Empresarial:** Taller técnico que atiende equipos de cómputo, laptops y microelectrónica. Controla desde la recepción del equipo, asignación de técnico, diagnóstico, consumo de repuestos en tiempo real, abonos parciales, cotizaciones aprobables, facturación final y arqueo diario de caja con auditoría estricta.

---

## 2. ARQUITECTURA DEL SISTEMA

### 2.1 Patrón MVC + Service Layer + Traits

```
app/
├── Helpers/                  # ColombiaHelper (Formateo monetario y geolocalización DANE)
├── Http/
│   ├── Controllers/          # Controladores (Auth, Mantenimiento, Electrónica, Caja, etc.)
│   └── Middleware/           # CheckRole, PreventBackHistory
├── Models/                   # 19 Modelos Eloquent con relaciones fuertemente tipadas
├── Services/                 # StockService, AnulacionService, OrdenService
└── Traits/                   # Auditable, HandlesAbono, HandlesStockAttach
```

### 2.2 Roles de Usuario (RBAC)

| Rol | Descripción | Permisos Clave |
|---|---|---|
| **👑 Administrador (`admin`)** | Dueño / Gerente | Acceso total: creación/edición/eliminación, caja, usuarios, auditoría, anulación y reportes. |
| **🛠️ Técnico (`tecnico`)** | Operario de Taller | Registrar/editar órdenes, asignar repuestos, registrar abonos. Anulaciones requieren clave admin. |
| **👁️ Invitado (`invitado`)** | Cliente / Consulta | Consulta de órdenes por número de ticket o documento en portal exclusivo. Solo lectura. |

---

## 3. SERVICIOS Y HELPERS DE ALTO NIVEL

### 3.1 `StockService`
- **Movimientos Atómicos de Inventario:** Descuento y adición de existencias protegidas por `lockForUpdate()` dentro de `DB::transaction()`.
- **Protección contra Sobre-venta:** Lanza excepciones de dominio si el stock solicitado supera el disponible, previniendo stock negativo.

### 3.2 `AnulacionService`
- **Trazabilidad Segura:** Reversión limpia de inventario y caja sin eliminar datos físicamente (`anulado = true`).
- **Autenticación en Cascada:** Validación de contraseña de administrador en operaciones críticas.

### 3.3 `ColombiaHelper`
- **Manejo Financiero Exacto:** Parser de moneda que interpreta correctamente puntos de miles y comas decimales (`$ 1.500.000,00` → `1500000.00`).
- **Geolocalización DANE:** Catálogo precargado de Departamentos y Municipios de Colombia para autocompletado instantáneo y dropdowns sin parpadeo.

---

## 4. MODELOS Y RELACIONES PRINCIPALES

```
User (admin / tecnico / invitado)
  └── hasMany → Mantenimiento, Electronica, Factura, MovimientoCaja, Evento

Cliente
  ├── hasMany → Equipo
  ├── hasMany → Mantenimiento, Electronica
  └── hasMany → Factura, Cotizacion

Proveedor
  ├── hasMany → Stock
  └── hasMany → Factura

Equipo
  ├── belongsTo → Cliente
  └── hasMany → Mantenimiento, Electronica

Mantenimiento / Electronica
  ├── belongsTo → Equipo, Tecnico, User
  ├── hasMany → Abono
  └── belongsToMany → Stock (pivot: cantidad, precio_unitario)

Stock
  ├── belongsTo → CategoriaStock
  ├── belongsTo → Proveedor
  └── belongsToMany → Mantenimiento, Electronica, FacturaItem

Factura
  ├── morphTo → facturable (Cliente o Proveedor)
  ├── hasMany → FacturaItem
  └── hasMany → MovimientoCaja

MovimientoCaja
  ├── belongsTo → ConceptoCaja, User
  └── belongsTo → parent (auto-referencia para abonos)

CierreCaja
  └── (snapshot diario con arqueo de caja)
```

---

## 5. EXPERIENCIA DE USUARIO: LIQUID GLASS UI

* **CSS Maestro:** `public/css/glass.css` (~89 KB de reglas optimizadas con Glassmorphism translúcido y aceleración por GPU).
* **Modo Oscuro Integrado:** Detección de preferencia del sistema y selector persistente (`localStorage`).
* **Modales Asíncronos (`ts-modal`):** Cuadros de confirmación y eliminación con estilo *Liquid Glass* sin bloqueos nativos feos del navegador.
* **Actualización en Vivo (AJAX):** Inserción y eliminación de repuestos y abonos en órdenes de trabajo en tiempo real sin recarga de página ni parpadeo visual.

---

## 6. VALIDACIÓN, PRUEBAS Y CALIDAD DE CÓDIGO (100% PASSING)

1. **Pruebas de Feature y Unitarias (`tests/Feature`, `tests/Unit`):**
   - **51 pruebas automatizadas** / **166 aserciones** cubriendo autenticación, roles, matemáticas de facturas, flujo de abonos, transacciones y permisos.
2. **Pruebas de Integración con Base de Datos (`tests/Integration`):**
   - **11 pruebas de integración profunda** con base de datos real cubriendo ciclos de compra/venta/anulación/caja.
3. **Auditoría Integral de Producción (`php artisan system:audit --deep`):**
   - **28 comprobaciones automáticas** de integridad financiera, seguridad RBAC, stocks >= 0, saldos exactos y arqueos de caja.

---

## 7. GUÍA PARA CARGAR ESTE DOCUMENTO EN NOTEBOOKLM

1. Abre tu navegador y dirígete a: [https://notebooklm.google.com](https://notebooklm.google.com).
2. Crea un nuevo **Cuaderno** con el nombre **"Tecni Systemas - Control de Mantenimiento"**.
3. Haz clic en **Añadir Fuentes** y sube este archivo (`DOCUMENTACION_PROYECTO_NOTEBOOKLM.md`) o copia y pega su contenido.
4. Podrás formular preguntas como:
   - *"¿Cómo se calcula el arqueo de caja chica?"*
   - *"¿Cómo funciona el portal de seguimiento de clientes?"*
   - *"¿Qué reglas de seguridad aplican a los técnicos y administradores?"*