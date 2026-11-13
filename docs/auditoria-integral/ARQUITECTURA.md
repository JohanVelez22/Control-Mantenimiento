# Arquitectura del sistema

> **Origen:** informe del agente 1 (arquitectura y reutilización) de la auditoría integral del 2026-11-13 (análisis con evidencia, sin modificar producción).
> **Coordinación:** el coordinador verificó los hallazgos ALTOS/CRÍTICOS antes de corregirlos y añadió este encabezado de estado.

## Estado tras las correcciones de esta ronda

| Hallazgo | Estado |
|---|---|
| ARQ-01 autorización incoherente (policies/gates sin usar, rutas con rol distinto al real) | **Pendiente** (`PLAN_MODERNIZACION.md`, fase 2) |
| ARQ-02 duplicación Mantenimiento/Electrónica | **Pendiente** (fase 4, riesgo alto) |
| ARQ-03 lógica de negocio en vistas de caja | **Parcial**: el vínculo de pagos a facturas ya no depende del texto (M-01/M-02); las vistas aún consultan Eloquent |
| ARQ-04 archivos gigantes | **Pendiente** |
| ARQ-05 identidad fija en el código | **Pendiente** (fase 1) |
| ARQ-06 a ARQ-12 | **Pendientes** (ver `PENDIENTES.md`) |
| DOC-01 documentación contradictoria | **Parcial**: esta carpeta es la referencia vigente; los documentos antiguos se listan en `PENDIENTES.md` |

Los identificadores (p. ej. `M-01`) se usan igual en `CAMBIOS_REALIZADOS.md`, `PENDIENTES.md` y en los mensajes de commit.

---

## Informe del agente 1 (arquitectura y reutilización)

**Proyecto:** `C:\ServBay\www\tecnisystemas` (rama `master`, commit `1a4982b`). **Fecha:** 2026-11-13.
**Modo:** solo lectura. Únicos comandos sobre la app: `artisan route:list --json` y `artisan list --raw`. No se tocó la base de datos.
**Destino:** INVENTARIO.md, ARQUITECTURA.md y ANALISIS_REUTILIZACION.md.
**Archivos auxiliares (carpeta de trabajo de la auditoría, no versionada):** `a1_files.txt` (inventario con líneas), `a1_classify.sh` (reglas de clasificación) y `a1_classified.txt` (clasificación A/B/C de cada archivo).

---

## 0. Resumen ejecutivo

- **Escala:** 92 archivos PHP en `app/` (14.805 líneas), 132 archivos en `resources/` (38.861 líneas), 62 migraciones, 155 rutas, 71 archivos de pruebas (17.287 líneas). El código propio suma **60.031 líneas en 314 archivos**.
- **Arquitectura real:** MVC clásico de Laravel. Hay una capa de servicios incipiente (5 servicios y 4 traits), pero los controladores son gruesos (4 superan las 700 líneas) y hay lógica de negocio en vistas, en el layout y en `AppServiceProvider`. No existe ninguna separación entre núcleo y negocio: todo vive en `App\` plano.
- **Reutilización medida** (sección 3): en archivos, **18 % sin cambios / 17 % con adaptación / 65 % no reutilizable**; en líneas, **11 % / 34 % / 55 %**.
- **Conclusión: (c) necesita refactorización importante** para servir de base genérica. Como producto para este taller concreto está cerca de (b). Recomiendo **conservar Laravel y el modelo de datos, y re-extraer el núcleo**: auth, usuarios, roles, auditoría, configuración, respaldos y layout/UI se sacan a módulos limpios. No conviene reescribir desde cero.

### Hallazgos CRÍTICOS / ALTOS (arquitectura)

No hay hallazgos CRÍTICOS de arquitectura. Los ALTOS:

| ID | Sev. | Resumen |
|---|---|---|
| ARQ-01 | ALTA | El modelo de autorización tiene tres capas contradictorias. 7 de las 9 Policies y 7 de los 8 Gates registrados no se usan nunca en la app. Las rutas declaran `role:admin,tecnico` para módulos que en realidad son solo admin (el bloqueo está a mano en cada controlador). |
| ARQ-02 | ALTA | Duplicación estructural Mantenimiento/Electrónica: 2 tablas, 2 pivotes, 2 FKs nulables en `abonos`, y controladores, políticas, exports, vistas y rutas en espejo (~3.700 líneas duplicadas). |
| ARQ-03 | ALTA | Lógica de negocio y consultas en vistas. En las vistas de caja, la referencia a facturas y órdenes se reconstruye con `preg_match` sobre el texto de `descripcion` y consultas Eloquent dentro de Blade. |
| ARQ-04 | ALTA | Archivos gigantes: `layouts/app.blade.php` (2.265 líneas, 7 `@php`, 6 `<script>`), `configuracion/index.blade.php` (1.846), `_utilities.css` (4.225), `_buttons.css` (3.556, 1.681 `!important`), `MovimientoInventarioController` (1.069) y `BackupController` (899). |
| ARQ-05 | ALTA | La identidad está dispersa y fija en el código: tres nombres distintos ("WolfCore System" en 29 lugares, "Tecni Systemas" en datos y valores por defecto, `APP_NAME="Wolf Guard"` que nadie usa) y el dominio `@tecnisystemas.com` fijo en `AuthController`. |
| DOC-01 | ALTA | Documentación contradictoria o desactualizada en 8 de 16 `.md`. Hay versiones de Laravel distintas (12 / 13.19 / 13), conteos de pruebas distintos (51 / 554 / 584 / 604), relaciones y funcionalidades que no existen, y un comando inexistente (`db:backup`). |

---

## 1. INVENTARIO

### 1.1 Estructura de carpetas y capas

| Capa | Ruta | Archivos | Líneas | Comentario |
|---|---|---|---|---|
| Controladores | `app/Http/Controllers` | 28 | 7.574 | 4 superan las 700 líneas |
| Middleware | `app/Http/Middleware` | 4 | 138 | `CheckRole`, `SecurityHeaders`, `PreventBackHistory`, `RequirePasswordChange` |
| Modelos | `app/Models` | 20 | 1.977 | 18 usan `Auditable` |
| Policies | `app/Policies` | 9 | 508 | Solo 2 se invocan (ver ARQ-01) |
| Servicios | `app/Services` | 5 | 633 | `AnulacionService`, `OrdenService`, `StockService`, `PosTicketService`, `CierreCajaGuard` |
| Traits | `app/Traits` | 4 | 465 | `Auditable`, `HandlesAbono`, `HandlesStockAttach`, `CalculaCostoOrden` |
| Helpers | `app/Helpers` | 1 | 189 | `ColombiaHelper` (departamentos y municipios, tipos de ID, WhatsApp, TitleCase). No se carga por `autoload.files`, se usa como clase estática. |
| Support | `app/Support` | 2 | 99 | `PaginacionPdf`, `PasswordsConocidas` |
| Exports (Excel) | `app/Exports` | 6 | 734 | Incluye `SafeValueBinder` |
| Comandos | `app/Console/Commands` (+`Concerns`) | 12 | 2.365 | Ver 1.4 |
| Database | `app/Database` | 1 | 23 | `SqliteConnector` (GREATEST para SQLite) |
| Providers | `app/Providers` | 1 | 334 | Un único `AppServiceProvider` con gates, policies, composer de notificaciones, listeners de login y un DDL en tiempo de ejecución |
| Rutas | `routes/web.php`, `routes/console.php` | 2 | 297 | 155 rutas registradas. `console.php` programa los respaldos leyendo la BD. |
| Config | `config/*.php` | 13 | 1.621 | Estándar de Laravel + `backup.php` propio |
| Migraciones | `database/migrations` | 62 | 2.613 | |
| Seeders / factories | `database/seeders`, `database/factories` | 3 / 7 | 1.198 / 364 | `TestDataSeeder` = 1.092 líneas |
| Vistas Blade | `resources/views` | 114 | 27.522 | 60 extienden `layouts.app` |
| Componentes Blade | `resources/views/components` | 5 | 587 | `icon` (31 usos), `whatsapp-link` (11), `print-dropdown` (5), `modal` (4), `table` (2) |
| CSS modular | `resources/css/glass/*.css` + `app.css` | 17 | 11.188 | 4.026 `!important` (1.681 en `_buttons`, 1.428 en `_utilities`) |
| JS | `resources/js/app.js` | 1 | 151 | Solo utilidades (ojo de contraseña, etc.). El resto del JS está inline: 69 `<script>`, 167 `onclick=` y 618 `style="` en vistas. |
| Librerías locales | `public/vendor` | 6 | — | chart.js 4.6.1, flatpickr 4.6.13, tom-select 2.3.1, servidas con SRI y copiadas por `scripts/copy-vendor.mjs` |
| Scripts | `scripts/copy-vendor.mjs` | 1 | 22 | |
| Pruebas | `tests/{Feature,Unit,Integration,Browser}` | 71 | 17.287 | Feature 51, Unit 7, Integration 1, Browser (Dusk) 8+ |
| Docs | raíz (10 `.md`) + `docs/` (4) + `informes_viabilidad/` (3 `.md` + 3 `.pdf` + 6 `.mp4`) | | | Ver sección 6 |
| Otros en raíz | `start-ngrok.bat`, `.kilo/`, `phpunit-integration.xml`, `phpunit.dusk.xml`, `.env.backup` (ignorado) | | | `start-ngrok.bat` está versionado y tiene la ruta absoluta del equipo |

**Archivos grandes (≥ 400 líneas)**

- **PHP:**
  - `MovimientoInventarioController` 1.069
  - `BackupController` 899
  - `MovimientoCajaController` 724
  - `ReporteFinancieroController` 713
  - `BackupDatabase` 606
  - `SystemAuditCommand` 516
  - `CotizacionController` 471
  - `MantenimientoController` 437
  - `ElectronicaController` 423
  - `StockController` 409
  - `SeedDemoData` 407
- **Blade:**
  - `layouts/app` 2.265
  - `configuracion/index` 1.846
  - `dashboard` 1.092
  - `eventos/index` 788
  - `cierre/index` 678
  - `inventario/facturas/edit` 674
  - `reportes_financieros/acumulado` 662
  - `reportes_financieros/diario` 661
  - `reportes_financieros/operaciones` 619
  - `mantenimientos/reportes` 575
  - `electronicas/reportes` 559
  - `stocks/show` 543
  - `guest/dashboard` 522
  - `stocks/reportes` 515
  - `caja/_form` 499
  - `cotizaciones/_scripts` 475
  - `reportes/index` 469
  - `inventario/venta` 436
  - `usuarios/_form` 422
- **CSS:**
  - `_utilities` 4.225
  - `_buttons` 3.556
  - `_tables` 771
  - `_forms` 503

### 1.2 Dependencias (versión declarada → instalada según los lock)

**Composer (`composer.json` → `composer.lock`):**

| Paquete | Restricción | Instalada |
|---|---|---|
| php | ^8.3 | (servidor 8.4) |
| laravel/framework | ^13.30.0 | v13.40.0 |
| barryvdh/laravel-dompdf | ^3.1 | v3.1.2 (dompdf/dompdf v3.1.6) |
| maatwebsite/excel | ^3.1 | 3.1.70 (phpoffice/phpspreadsheet 1.30.7) |
| laravel/tinker | ^3.0 | v3.0.2 |
| *dev* laravel/dusk | ^8.7 | v8.7.2 |
| *dev* phpunit/phpunit | ^12.5.12 | 12.5.43 |
| *dev* laravel/pint | ^1.27 | v1.32.1 |
| *dev* laravel/pail | ^1.2.5 | v1.2.7 |
| *dev* mockery/mockery | ^1.6 | 1.6.15 |
| *dev* nunomaduro/collision | ^8.6 | v8.9.5 |
| *dev* fakerphp/faker | ^1.24 | v1.24.1 |

Notas:
- `composer.json` sigue con nombre `johanvelez22/control-mantenimiento`, autor `contacto@example.com` y keywords genéricas.
- El script `composer setup` ejecuta `migrate --force`.
- El script `dev` levanta `queue:listen`, pero la app no despacha jobs propios (no los encontré).

**npm (`package.json` → `package-lock.json`):**

| Paquete | Restricción | Instalada |
|---|---|---|
| chart.js | 4.6.1 (exacta) | 4.6.1 |
| flatpickr | 4.6.13 | 4.6.13 |
| tom-select | 2.3.1 | 2.3.1 |
| vite | ^8.0.0 | 8.3.6 |
| tailwindcss | ^3.4.17 | 3.4.17 |
| laravel-vite-plugin | ^3.0.0 | 3.0.1 |
| postcss | ^8.5.19 | 8.5.32 |
| autoprefixer | ^10.5.4 | 10.5.4 |
| concurrently | ^9.0.1 | 9.2.5 |

Notas:
- Las tres librerías de UI son `dependencies`, pero solo se usan como archivos copiados a `public/vendor`. Vite no las empaqueta.
- `tailwind.config.js` escanea `./app/Http/Controllers/**/*.php`, porque hay clases CSS dentro de los controladores.
- Hay fuentes de Google (Michroma/Orbitron) cargadas desde `fonts.googleapis.com` en `layouts/app.blade.php:68-70`, así que la app no es 100 % offline. La CSP las permite (`SecurityHeaders.php:30`).

### 1.3 Recursos de `public/`

| Recurso | Tamaño | ¿Usado? |
|---|---|---|
| `images/wolfcore.png` | 572 KB | Sí, 6 vistas: topbar, login, consulta ×2, guest, `layouts/consulta`. Se muestra a 28 px, así que pesa 572 KB sin necesidad. |
| `images/wolfcore_badge.png` | 1,0 MB | **No** (0 referencias) |
| `images/wolfguard.png` | 664 KB | **No** |
| `images/wolfguard.jpg` | 607 KB | **No** |
| `images/wolfcore.jpg` | 256 KB | **No** |
| `favicon-16/32/48`, `favicon.svg`, `favicon.ico`, `apple-touch-icon.png` | — | Sí |
| `favicon-64x64.png`, `favicon-512x512.png`, `favicon.png` | — | **No** (0 referencias) |
| `vendor/*` | 339 KB | Sí |
| `build/` | — | Generado. Ignorado por git (correcto). |

En total hay **≈2,5 MB de imágenes versionadas sin uso**, y `informes_viabilidad/videos/*.mp4` añade **81 MB versionados en git** (`git ls-files` los lista; `.git` = 99 MB). `AUDITORIA_EXHAUSTIVA.md:863` dice "No versionado", lo cual es falso.

### 1.4 Comandos Artisan propios

| Firma | Archivo | Tipo |
|---|---|---|
| `app:backup-db` | `BackupDatabase.php` (606) | Escritura (genera respaldo) |
| `app:verificar-respaldo` | `VerificarRespaldo.php` (201) | Lectura. **No documentado en ningún `.md`.** |
| `app:verificar-migraciones` | `VerificarMigraciones.php` | Lectura |
| `app:preflight` | `PreflightDespliegue.php` | Lectura |
| `security:check` | `SecurityCheck.php` | Lectura |
| `system:audit {--deep}` | `SystemAuditCommand.php` (516) | `--deep` escribe datos de prueba |
| `app:clean-data` | `CleanDatabase.php` | Destructivo |
| `app:reset-tests` | `ResetTests.php` | Destructivo |
| `app:seed-demo-data` | `SeedDemoData.php` (407) | Destructivo con `--force` |
| `db:setup` | `SetupDatabase.php` | Destructivo. Pisa el espacio de nombres `db:*` de Laravel. |
| `ResetMonthlyStats` | `ResetMonthlyStats.php` | Sin uso aparente en el scheduler |

---

## 2. INVENTARIO DE MÓDULOS

Los roles son tres strings fijos (`admin`, `tecnico`, `invitado`) en `users.role`. "Ruta" es el middleware `role:` declarado en `routes/web.php`; "Real" es la restricción efectiva en el controlador.

| Módulo | Qué hace | Archivos principales | Recibe / genera | Tablas | Dependencias | Rol (ruta → real) |
|---|---|---|---|---|---|---|
| **Auth / login** | Login por correo, alias (`admin`, `tecnico`…) o nombre. Bloqueos escalonados por IP y cuenta. Al entrar calcula alertas de pendientes. | `AuthController` (325), `auth/login.blade.php`, rutas `login`/`logout` | Credenciales → sesión, flash `alertas_pendientes`, evento login | `users`, `sessions`, `cache`, `mantenimientos`, `electronicas`, `eventos` | `Mantenimiento`, `Electronica`, `PasswordsConocidas`, listener en `AppServiceProvider:299-332` | guest |
| **Cambio de contraseña** | Fuerza el cambio si `must_change_password` | `PasswordController` (71), `RequirePasswordChange`, `auth/cambiar-password` | Contraseña → hash, invalida sesiones (`PasswordController:56`) | `users`, `sessions` | `PasswordsConocidas` | todos |
| **Usuarios** | CRUD de usuarios, foto y rol | `UserController` (177), `UserPolicy`, `usuarios/*` | Formulario + foto → usuario | `users` | `Storage public` | admin,tecnico → el índice es admin (`UserController:18`), el técnico solo edita su perfil |
| **Clientes** | CRUD, municipios (API JSON) y anulación | `ClienteController` (142), `ClientePolicy` (sin uso), `clientes/*`, `api/municipios` | Datos personales → cliente | `clientes` | `ColombiaHelper`, `AnulacionService` | admin,tecnico |
| **Proveedores** | CRUD y anulación | `ProveedorController` (156), `proveedores/*` | → proveedor | `proveedores` | `ColombiaHelper`, `AnulacionService` | admin,tecnico |
| **Técnicos** | Catálogo de técnicos (no son usuarios) | `TecnicoController` (123), `tecnicos/*` | → técnico | `tecnicos` | `AnulacionService` | admin,tecnico |
| **Equipos** | CRUD, baja y reactivación | `EquipoController` (249), `equipos/*` | → equipo (de un cliente o proveedor) | `equipos` | `AnulacionService` | admin,tecnico |
| **Stock / categorías / bajas** | Inventario de repuestos, fotos, utilidad, precio técnico, bajas y reversión, reportes PDF/Excel | `StockController` (409), `CategoriaStockController` (80), `StockService`, `StocksExport`, `StockPolicy` (sin uso), `stocks/*` | → stock, `bajas_stock`, PDF/Excel | `stocks`, `categoria_stocks`, `bajas_stock`, `factura_items` | `AnulacionService`, `StockService` | admin,tecnico |
| **Mantenimientos** | Órdenes `ORD-n`: CRUD, duplicar, anular, factura carta/POS, reportes, consulta del invitado | `MantenimientoController` (437), `MantenimientoPolicy`, `MantenimientoStockController`, `AbonoController`, `MantenimientosExport`, `mantenimientos/*` (9 vistas) | → orden, repuestos (pivote), abonos, movimientos de caja, PDF/Excel | `mantenimientos`, `mantenimiento_stock`, `abonos`, `movimiento_cajas`, `secuencias_documento` | `OrdenService`, `AnulacionService`, `PosTicketService`, `HandlesAbono`, `HandlesStockAttach`, `CalculaCostoOrden` | admin,tecnico (show/factura también invitado) |
| **Electrónicas** | Órdenes `ELC-n`: copia funcional de Mantenimientos | `ElectronicaController` (423), `ElectronicaPolicy`, `ElectronicaStockController`, `ElectronicaAbonoController`, `ElectronicasExport`, `electronicas/*` (9 vistas) | Igual que Mantenimientos | `electronicas`, `electronica_stock`, `abonos` (`electronica_id`), `movimiento_cajas` | Igual | Igual |
| **Abonos** | Pagos parciales a órdenes, con su espejo en caja | `AbonoController`, `ElectronicaAbonoController` (28 c/u, idénticos salvo 4 líneas), `HandlesAbono` (194) | Monto y medio → abono + movimiento | `abonos`, `movimiento_cajas` | `CierreCajaGuard` (vía trait) | admin,tecnico |
| **Caja / conceptos** | Ingresos y egresos, pagos hijos (`parent_id`), duplicar, anular, impresión carta/POS, conceptos | `MovimientoCajaController` (724), `ConceptoCajaController` (52), `MovimientoCajaPolicy` (sin uso), `caja/*` (7), `caja/conceptos/*` | → movimiento | `movimiento_cajas`, `concepto_cajas`, `facturas` | `CierreCajaGuard`, `AnulacionService`, `PosTicketService` | admin,tecnico |
| **Cierre de caja** | Arqueo diario, conciliación física, borrar solo el último cierre | `CierreCajaController` (235), `CierreCajaPolicy` (sin uso), `CierreCajaGuard`, `cierre/*` | Conteo → snapshot | `cierre_cajas`, `movimiento_cajas` | `AnulacionService` | admin,tecnico |
| **Inventario compra/venta/facturas** | Compras (`CP-`) y ventas (`VT-`), edición, anulación e impresión de facturas | `MovimientoInventarioController` (1.069), `FacturaPolicy` (sin uso), `inventario/*` (7) | Ítems → factura, ítems, stock, caja | `facturas` (polimórfica `facturable`), `factura_items`, `stocks`, `movimiento_cajas` | `StockService`, `OrdenService`, `CierreCajaGuard`, `AnulacionService`, `PosTicketService` | admin,tecnico |
| **Cotizaciones** | `COT-`: CRUD, convertir a factura, rechazar, anular, reactivar, PDF | `CotizacionController` (471), `CotizacionPolicy` (sin uso), `cotizaciones/*` (6, incl. `_scripts` 475) | → cotización y factura al convertir | `cotizaciones`, `cotizacion_items`, `facturas`, `factura_items`, `movimiento_cajas` | `OrdenService`, `CierreCajaGuard`, `AnulacionService` | admin,tecnico |
| **Reportes operativos** | Resumen con PDF/Excel y reportes por módulo | `ReporteController` (106), `reportes/*`, más los métodos `reportes()` de Mant/Elec/Stock | Filtros → vista/PDF/Excel | Mant, Elec, Stock, Caja | Exports | admin,tecnico |
| **Reportes financieros** | Diario, acumulado y operaciones, con PDF/Excel | `ReporteFinancieroController` (713), `AcumuladoExport`, `ReportesFinancierosExport`, `reportes_financieros/*` (6) | Rango → vista/PDF/Excel | `movimiento_cajas`, `abonos`, `facturas`, Mant, Elec | Exports | admin,tecnico. El gate `view-financial-reports` existe pero **no se aplica** (DEUDA #6). |
| **Configuración** | Datos de la empresa, logo, formato de factura, programación de respaldos | `ConfiguracionController` (86), `Configuracion` (106, fila única id=1, caché), `configuracion/index.blade.php` (1.846, incluye la UI de respaldos) | Formulario + logo → configuración | `configuraciones` | `Cache` | admin,tecnico → **admin** (`ConfiguracionController:15`) |
| **Eventos / auditoría** | Bitácora de cambios (trait `Auditable`), login/logout, filtros y detalle | `Evento` (140), `Auditable` (117), `EventoController` (114), `eventos/*` (1.128) | Hooks de modelo → evento | `eventos` | Todos los modelos | admin,tecnico → **admin** (`EventoController:23`) |
| **Respaldos** | Manual o programado, descarga, borrado, restauración (con respaldo previo), `git pull --ff-only`, abrir carpeta en Windows | `BackupController` (899), `BackupDatabase` (606), `VerificarRespaldo` (201), `routes/console.php` (scheduler), `config/backup.php` | → `.sql`/`.zip` en `storage/app/backups` | Todas | `mysqldump`/`mysql` (rutas de ServBay fijas), `git`, PowerShell/explorer | admin,tecnico → **admin** (`ensureAdmin` en los 8 métodos) |
| **Consulta de invitado** | Portal: búsqueda por identificación + orden, detalle y factura | `GuestController` (133), `MantenimientoController::consulta`, `ElectronicaController::consulta`, `guest/dashboard` (522), `consulta/*`, `layouts/consulta`, `layouts/guest` | ID/orden → estado | `clientes`, `equipos`, Mant, Elec | — | invitado (throttle 30/min) |
| **Dashboard** | KPIs, gráficos (chart.js) y listas recientes | `DashboardController` (213), `dashboard.blade.php` (1.092) | — → métricas | Casi todas | chart.js | admin,tecnico (el invitado se redirige) |
| **Notificaciones del topbar** | Modal y campana con pendientes (Mant, Elec, Cot, facturas con saldo, movimientos con saldo) | Composer en `AppServiceProvider:116-297`, `layouts/app.blade.php:1151-1417`, `partials/topbar.blade.php:48-110`, más las alertas de login en `AuthController:254-305` | — → listas cacheadas 30 s por usuario | Mant, Elec, `facturas`, `movimiento_cajas`, `cotizaciones` | — | todos los que usan `layouts.app` |
| **Preflight / comandos** | Verificaciones de despliegue y seguridad | Comandos de 1.4 | — → informe en consola | Lectura | — | CLI |
| **Design system** | Catálogo visual | Ruta-closure `routes/web.php:93`, `design_system/index.blade.php` (306) | — | — | — | admin,tecnico. Página de desarrollo expuesta en producción. |

---

## 3. REUTILIZACIÓN

### 3.1 Núcleo genérico frente a negocio, por componente

Acoplamiento: **Bajo** = se copia tal cual · **Medio** = se adapta en horas · **Alto** = hay que reescribirlo o extraerlo.

| Componente | Acoplamiento | Evidencia |
|---|---|---|
| **Autenticación (login)** | **Alto** | `AuthController.php:80-83` fija los alias `admin/tecnico/invitado` a `…@tecnisystemas.com`. Las líneas `:100` y `:110` usan el sufijo `@tecnisystemas.com`. En `:254-305` el login consulta `Electronica` y `Mantenimiento` para construir alertas. La lógica de bloqueo escalonado (`:60-75`) sí es genérica y valiosa. |
| **Cambio obligatorio de contraseña** | Bajo | `PasswordController`, `RequirePasswordChange`, `PasswordsConocidas` y la migración `must_change_password` son autocontenidos. La vista solo lleva el título "· WolfCore". |
| **Usuarios** | Medio | `UserController` usa `isAdmin()`. La vista `usuarios/_form` (422 líneas) tiene roles fijos. |
| **Roles / permisos** | **Alto** | Los roles son strings fijos en `User.php:53-67` (`isAdmin`/`isTecnico`/`isInvitado`). Hay 29 comparaciones en controladores y 90 en vistas. Los gates de `AppServiceProvider.php:97-104` no se usan, y de las policies solo se invocan Mantenimiento y Electrónica. `@can` no se usa nunca en vistas. Ver ARQ-01. |
| **Sesiones** | Bajo | Driver `database` estándar, `PreventBackHistory`. Cambiar la contraseña desde `UserController:133` solo rota `remember_token` y no cierra las sesiones abiertas del usuario afectado (falta `AuthenticateSession`). Lo mitiga `must_change_password`. |
| **Panel / menú / topbar** | **Alto** | `partials/sidebar.blade.php:17-74` tiene un menú fijo de 15 enlaces con `routeIs` a mano. `layouts/app.blade.php:22-43` tiene un mapa ruta→título fijo con "WolfCore". En `topbar.blade.php:15` el logo `images/wolfcore.png` está fijo; no usa el logo de `Configuracion`. |
| **Dashboard** | Alto (no reutilizable) | 100 % métricas del taller. `stock_bajo` usa un umbral fijo de 5 (`DashboardController:36`). |
| **Formularios** | Medio | No hay FormRequests (DEUDA #4) ni componentes de input. Los estilos `.ts-*` están en `_forms.css` (reutilizable). |
| **Tablas / filtros / búsqueda / paginación** | Medio | `vendor/pagination/*` y `_tables.css` son reutilizables. El componente `<x-table>` (9 líneas) solo se usa 2 veces. Cada index reimplementa filtros y búsqueda con `like "%…%"` inline, con una función JS `filterTable` global en el layout. |
| **Notificaciones** | **Alto** | Están en `AppServiceProvider.php:116-297` (182 líneas de consultas de negocio en el provider) y en `layouts/app.blade.php:1151-1417`. Hay un **segundo** sistema en `AuthController:254-305`, que construye 50 alertas solo para que el layout compruebe `count() > 0` (`app.blade.php:1414`); `alertas_totales` no lo lee nadie. La ruta muerta `electronicas.dismiss-alert` (`routes/web.php:66-70`) borra la clave `alertas_electronica`, que ya nadie escribe. |
| **Archivos (subidas)** | Medio | Usa el disco `public` estándar. Las rutas `configuracion/`, `stocks/`, fotos de usuario están fijas en cada controlador. |
| **PDF** | Medio | `PaginacionPdf` (genérico, Bajo). Las plantillas `layouts/print*.blade.php` toman los datos de `Configuracion` (bien), pero `print_pos.blade.php:195` firma "Software WolfCore System" de forma fija. |
| **Excel** | Medio | `SafeValueBinder` es genérico (Bajo). Los 5 exports son de negocio. |
| **Impresión** | Medio | `_print.css` y `<x-print-dropdown>` son reutilizables. La cabecera de impresión está incrustada en `layouts/app.blade.php:119-139`. |
| **Auditoría (Evento / Auditable)** | Medio | Es genérico en esencia: hooks `created/updated/deleted` + `Evento::registrar`. Hay que adaptar: la semántica de negocio fija (`anulado`, `estado === 'anulada'`, `active`) en `Auditable.php:61-71`; la lista de campos enmascarados duplicada en `:43` y `:102`; la función global `clone_model_data` declarada dentro del archivo del trait (`:99`); y `EventoController::getEntityLookups` (`:80-97`), que conoce 8 modelos de negocio. |
| **Configuración** | Medio | `Configuracion::getGlobal()` (fila única, caché) es un buen patrón. Pero mezcla la identidad de la empresa con la programación de respaldos y `formato_factura`. Los valores por defecto "Tecni Systemas", el NIT y `contacto@tecnisystemas.com` están fijos (`Configuracion.php:72-80`). La vista hace 22 llamadas a `getGlobal/first` repartidas en vistas y controladores. |
| **Manejo de errores** | Bajo | `errors/*` son minimalistas. `minimal.blade.php:8` lleva "WolfCore System" fijo. `bootstrap/app.php` tiene `withExceptions` vacío. |
| **Validación** | Medio-Alto | Toda la validación es inline en los controladores, sin FormRequests. Los mensajes están en español y fijos (aceptable). |
| **Componentes UI / CSS** | Medio | Los tokens (`_tokens.css`), cards, forms, tables, modals, pills, animations y responsive son reutilizables. `_buttons.css` (3.556 líneas) contiene 53 selectores `.backup-*` y 33 `.reportes-*`, más `btn-venta`/`btn-compra`: es CSS de módulos metido en un archivo "de botones". `_utilities.css` (4.225) contiene "reportes, cierres y componentes específicos" según `docs/GUIA_ARQUITECTURA_CSS_MODULAR.md:36`. |
| **Servicios compartidos** | Medio | `OrdenService::siguiente()` (secuencias con bloqueo de fila) es genérico. `AnulacionService::adminPasswordValida/autorizarOperacionSensible` (contraseña de supervisor con rate limit) también es genérico, pero vive junto a la reversión de stock y caja. `PosTicketService` es medio. `StockService` y `CierreCajaGuard` son de negocio. |
| **Respaldos / restauración / preflight** | Medio-Alto | La funcionalidad tiene mucho valor, pero tiene rutas de ServBay para Windows fijas y **triplicadas** (`BackupController.php:405-420`, `BackupDatabase.php:464-479`, `VerificarRespaldo.php:187-188`), PowerShell/`explorer.exe` en `BackupController.php:352-384` y `git pull` desde HTTP (`:773-865`). `restore` (`:519-…`) re-inserta `eventos` con lógica propia. `PreflightDespliegue.php:30` lleva la marca fija. La programación vive en `routes/console.php`, que lee la BD en cada arranque de consola. |
| **Middleware de seguridad** | Bajo | `SecurityHeaders`, `PreventBackHistory` y `CheckRole` (genérico por lista de roles). |
| **Helpers país** | Medio | `ColombiaHelper` es reutilizable para proyectos en Colombia y debería ser un módulo de localización. |

**Otras cadenas fijas de identidad:**
- "WolfCore" aparece en 29 líneas fuera del CSS: layouts, login, consulta, guest, errores, `print_pos`, configuración, preflight y la función JS `window.toggleWolfcoreTheme`.
- `@tecnisystemas.com` está en código de producción en `AuthController` y `Configuracion`, y además en seeders y en una migración de datos (`2026_10_01_143000_backfill_tecnicos_email.php:19-38`, con nombres y correos de técnicos de demo).
- `.env.example:1` define `APP_NAME="Wolf Guard"`, pero `config('app.name')` no se usa en ninguna vista ni controlador.

### 3.2 Estimación cuantitativa (conteo real)

**Método:**
- Universo: 314 archivos y 60.031 líneas de `app/`, `resources/`, `routes/`, `bootstrap/app.php`, `config/`, `database/` y `scripts/`. Se excluyen `tests/`, `vendor/`, `node_modules/` y `public/`.
- Cada archivo se clasificó con las reglas de `a1_classify.sh`; el resultado está en `a1_classified.txt`.
- Categorías: **A** = reutilizable sin cambios, **B** = con adaptación, **C** = no reutilizable (negocio).

| Categoría | Archivos | % archivos | Líneas | % líneas |
|---|---|---|---|---|
| A: sin cambios | 57 | 18,2 % | 6.382 | 10,6 % |
| B: con adaptación | 54 | 17,2 % | 20.468 | 34,1 % |
| C: no reutilizable | 203 | 64,6 % | 33.181 | 55,3 % |
| **Total** | **314** | | **60.031** | |

Si se excluyen los 13 archivos de `config/` (1.621 líneas, casi todos estándar de Laravel), **A cae a 44 archivos y 4.761 líneas (≈8 % del código propio)**.

**Desglose por capa:**

| Capa | A (arch/lín) | B | C |
|---|---|---|---|
| `app/Http` | 6 / 217 | 5 / 1.601 | 21 / 5.861 |
| `app/Models` | — | 3 / 315 | 17 / 1.662 |
| `app/Policies` | — | 1 / 48 | 8 / 460 |
| `app/Services` | — | 3 / 452 | 2 / 181 |
| `app/Traits` | — | 1 / 117 | 3 / 348 |
| `app/Console` | 3 / 202 | 5 / 1.103 | 4 / 1.060 |
| `app/Exports` | 1 / 29 | — | 5 / 705 |
| `app/Support`, `Database`, `Helpers`, `Providers` | 3 / 122 | 2 / 523 | — |
| `resources/views` | 9 / 487 | 22 / 7.737 | 83 / 19.298 |
| `resources/css` + `js` | 15 / 3.407 | 3 / 7.932 | — |
| `database/*` | 5 / 233 | 7 / 336 | 60 / 3.606 |
| `routes`, `bootstrap`, `scripts`, `config` | 15 / 1.685 | 2 / 304 | — |

**Archivos A (sin cambios):**
- PHP: `Controller`, `PasswordController`, los 4 middleware, `PaginacionPdf`, `PasswordsConocidas`, `SqliteConnector`, `SafeValueBinder`, `ConfirmaBaseDeDatos`, `VerificarMigraciones`, `SecurityCheck`.
- Base de datos: las 3 migraciones base de Laravel + `must_change_password`, y `UserFactory`.
- Vistas: errores 403/404/419/429/500/503, componentes `icon`, `modal` y `table`.
- CSS y otros: 13 parciales CSS + `app.css`, `bootstrap/app.php`, `copy-vendor.mjs`, `config/*`.

**Archivos B más pesados** (concentran casi todo el trabajo de adaptación):
- CSS: `_utilities.css` (4.225), `_buttons.css` (3.556)
- Vistas: `layouts/app` (2.265), `configuracion/index` (1.846), `eventos/index` (788), `usuarios/_form` (422)
- PHP: `BackupController` (899), `BackupDatabase` (606), `AppServiceProvider` (334), `AuthController` (325)

**Lectura:** el "núcleo" aprovechable existe (auth endurecida, cambio de contraseña, auditoría, respaldos, secuencias, contraseña de supervisor, CSS glass, PDF/Excel seguros). Pero ≈75 % de esas líneas están en archivos que mezclan núcleo y negocio y hay que **extraerlas**, no copiarlas.

---

## 4. ARQUITECTURA OBJETIVO (modular, sin sobre-ingeniería)

### 4.1 Principios

- Un solo proyecto Laravel con módulos internos en `app/Modules/*`. No hace falta un paquete Composer por módulo: con un `ServiceProvider` por módulo basta para registrar y desactivar.
- El núcleo no conoce a ningún módulo de negocio. Los módulos se registran en el núcleo: menú, permisos, notificaciones, widgets del dashboard y entidades auditables.
- La configuración de cada proyecto (nombre, logo, colores, módulos habilitados) vive en `config/wolfcore.php` + `.env`. Lo editable por el cliente sigue en la tabla `configuraciones`.

### 4.2 Estructura propuesta

```
app/
  Core/                         # NÚCLEO genérico (reutilizable)
    Auth/                       # LoginController (sin alias), PasswordController, LoginThrottle
    Users/                      # UserController, User, UserPolicy
    Authorization/              # Permission registry, Role (tabla o config), middleware 'permission:'
    Audit/                      # Evento, Auditable (config de enmascarado), EventoController
    Settings/                   # Configuracion (identidad), SettingsController
    Backups/                    # BackupController, BackupDatabase, VerificarRespaldo, MysqlBinaryResolver (único)
    Numbering/                  # SecuenciaService (OrdenService::siguiente)
    Supervisor/                 # AdminPasswordGate (de AnulacionService::adminPasswordValida)
    Notifications/              # NotificationRegistry + composer genérico del topbar
    Menu/                       # MenuRegistry (items declarativos con permiso)
    Pdf/ Excel/                 # PaginacionPdf, SafeValueBinder, BasePdfLayout
    Console/                    # Preflight, VerificarMigraciones, SecurityCheck, ConfirmaBaseDeDatos
    Http/Middleware/            # SecurityHeaders, PreventBackHistory, RequirePasswordChange
    CoreServiceProvider.php
  Modules/                      # MÓDULOS EMPRESARIALES (por proyecto)
    Clientes/  Proveedores/  Equipos/  Inventario/ (Stock, Categorías, Bajas, Compra/Venta, Facturas)
    Ordenes/   (Mantenimiento + Electrónica unificados como OrdenServicio con 'tipo')
    Caja/      (Movimientos, Conceptos, Cierre, Abonos)
    Cotizaciones/  Reportes/  PortalInvitado/
      ├─ Http/Controllers, Models, Policies, Services, Exports
      ├─ routes.php            # rutas del módulo
      ├─ resources/views       # vistas del módulo (namespace 'caja::')
      ├─ menu.php              # items de menú declarativos
      ├─ permissions.php       # permisos declarativos ('caja.ver', 'caja.anular'…)
      └─ CajaServiceProvider.php
  Localization/Colombia/        # ColombiaHelper, catálogos DANE, tipos de ID
resources/
  views/core/                   # layouts (app, guest, print, print_pos), partials, componentes <x-core::*>
  css/core/                     # tokens, cards, forms, tables, modals, pills, print, responsive
  css/modules/                  # backup.css, reportes.css, caja.css (sale de _buttons/_utilities)
  js/core/  js/modules/         # JS de los <script> inline, como módulos Vite
config/
  wolfcore.php                  # nombre, logo por defecto, colores, módulos habilitados, roles base
```

### 4.3 Mecanismos (mínimos)

1. **Registro de módulos:** `config/wolfcore.php` → `'modules' => ['clientes' => true, 'ordenes' => true, ...]`. `CoreServiceProvider` registra el `ServiceProvider` de cada módulo habilitado.
2. **Rutas por módulo:** cada provider llama `Route::middleware(['web','auth','password.cambio'])->group(__DIR__.'/routes.php')` con su prefijo y nombre. `routes/web.php` queda solo con lo del núcleo.
3. **Menú declarativo:** `menu.php` devuelve `[['label'=>'Caja','route'=>'caja.index','icon'=>'cash','permission'=>'caja.ver','order'=>50]]`. El sidebar itera el `MenuRegistry` filtrando con `@can`. Así desaparecen los 15 enlaces fijos y los `routeIs` manuales.
4. **Permisos declarativos:** `permissions.php` lista los permisos del módulo. Los roles base se mapean en config (`admin => *`, `tecnico => [...]`, `invitado => [...]`) o en una tabla `role_permissions` si el cliente los necesita editables. `Gate::before` concede todo al admin. Se reemplazan `isAdmin()` y `role ===` por `can('modulo.accion')`. No hace falta Spatie mientras haya 3 roles.
5. **Notificaciones registrables:** cada módulo aporta un `PendingProvider` (`fn(User) => Collection`). El composer del topbar los agrega. Esto saca 180 líneas del `AppServiceProvider` y elimina las alertas duplicadas del login.
6. **Identidad visual:** `config('wolfcore.brand.name')`, `brand.logo` (con fallback al logo de `Configuracion`) y colores como variables CSS en `_tokens.css`, inyectadas desde config. La firma del ticket POS, el título de pestaña y los errores leen de ahí.
7. **Auditoría configurable:** `Auditable` lee `protected array $auditMask` y `$auditAnulacionFields` del modelo, con valores por defecto desde config.

### 4.4 Pasos de extracción, en orden

| # | Paso | Esfuerzo | Riesgo | Nota |
|---|---|---|---|---|
| 1 | Congelar el comportamiento: arreglar la prueba que falla y añadir pruebas de humo de menú, notificaciones y login por alias | S | Bajo | Red de seguridad para lo demás |
| 2 | Centralizar la identidad: `config/wolfcore.php` con brand y dominio; quitar las 29 apariciones de "WolfCore" y `@tecnisystemas.com` del código de producción; usar `config('app.name')` | S | Bajo | |
| 3 | Unificar la resolución de binarios MySQL en una sola clase y quitar las rutas fijas de ServBay (dejarlas como fallback configurable) | S | Bajo | |
| 4 | Sacar el composer de notificaciones y los listeners de login del `AppServiceProvider` a clases propias; eliminar las alertas de `AuthController:254-305`, la ruta muerta `dismiss-alert` y el DDL en tiempo de ejecución (`AppServiceProvider:81-92`, redundante con la migración `2026_09_09_165000`) | S-M | Bajo | |
| 5 | Autorización: definir permisos por acción, usar `Gate::before` para admin, mover los checks manuales a Policies o `can:`, y alinear el middleware de ruta con la restricción real (configuración, eventos, backups y usuarios → `can:`). Borrar los gates y policies muertos o empezar a usarlos. | M | Medio | Ya hay matriz de permisos en las pruebas |
| 6 | Dividir `layouts/app.blade.php` en parciales/componentes (modales globales, notificaciones, cabecera de impresión, scripts) y mover el JS a módulos Vite | M-L | Medio (visual) | Habilita quitar `'unsafe-inline'` |
| 7 | Mover las consultas de las vistas de caja a un `MovimientoCajaPresenter`/servicio que use `factura_id`/`abono_id` en lugar de `preg_match` sobre `descripcion` | M | Medio | Requiere migrar los datos con referencia solo textual |
| 8 | Crear `Core/` y `Modules/` con namespaces de vistas; mover archivos sin cambiar lógica (`git mv`) | M | Bajo-Medio | |
| 9 | Menú y permisos declarativos por módulo, y habilitación de módulos por config | M | Bajo | |
| 10 | Separar el CSS de módulo (`.backup-*`, `.reportes-*`, venta/compra) de `_buttons`/`_utilities`; reducir `!important` | L | Alto (visual) | Incremental |
| 11 | Unificar Mantenimiento/Electrónica en `OrdenServicio` + `tipo` (tabla única o STI): pivote único, `abonos.orden_id`, un solo controlador, policy, export y juego de vistas | L | **Alto** | Migración de datos e IDs; conservar los prefijos `ORD-`/`ELC-` |
| 12 | FormRequests y dividir `MovimientoInventarioController` (compra/venta/factura) y `BackupController` (respaldo/restauración/git) | M | Bajo | DEUDA #4 |

---

## 5. CALIFICACIONES (visión de arquitectura)

| Criterio | Nota | Justificación |
|---|---|---|
| Organización | **5/10** | Usa bien las convenciones de Laravel (resource routes, carpetas estándar, servicios y traits con nombre claro, CSS partido en parciales). En contra: estructura plana sin límites entre núcleo y negocio, un solo provider con negocio dentro, lógica en vistas, 4 controladores de más de 700 líneas, CSS "de botones" con estilos de módulos, rutas que declaran un rol distinto al real y docs repartidas en 3 lugares. |
| Mantenibilidad | **4/10** | Tiene a favor 604 pruebas, transacciones, bloqueos y `CHANGELOG`. Pero un cambio en las órdenes hay que hacerlo dos veces (Mant/Elec). El layout de 2.265 líneas y los 4.026 `!important` hacen frágil cualquier cambio visual. Hay 69 `<script>` inline, la referencia entre caja y documentos va por texto y la autorización está repartida en tres mecanismos. |
| Escalabilidad | **5/10** | Funcional para un taller con pocos usuarios. Limitan: el composer de notificaciones hace ~6 consultas cada 30 s por usuario sobre datos que no dependen del usuario (la caché por usuario multiplica el costo), `EventoController::getEntityLookups` carga catálogos completos, hay búsquedas `like "%…%"` y una sola instancia en Windows/ServBay con `exec`. Agregar un tercer tipo de orden implicaría una tercera copia. |
| Reutilización | **3/10** | Solo ≈11 % de las líneas se reutilizan sin cambios. La identidad, el dominio, los roles, el menú y las notificaciones están fijos. Hay piezas de núcleo valiosas pero incrustadas. |
| Documentación | **4/10** | Abundante (16 `.md`, `CHANGELOG` y `DEUDA_TECNICA` útiles y al día), pero contradictoria y desactualizada en 8 documentos (sección 6.4). Faltan una guía de arquitectura real y la documentación de `app:verificar-respaldo`. |
| Calidad backend | **6/10** | Hay `DB::transaction`, `lockForUpdate`, secuencias atómicas, FKs `RESTRICT`, rate limits, `SafeValueBinder`, PDF sin PHP y respaldo antes de restaurar: es mejor que la media de un proyecto de este tamaño. Restan: sin FormRequests, policies muertas, DDL en el boot, duplicación, lógica en vistas y binarios de ServBay fijos. |

**Conclusión: (c) necesita refactorización importante** para ser una base reutilizable entre proyectos. No es (d): el dominio está bien modelado en su mayor parte, las protecciones financieras y de seguridad recientes tienen valor y hay buena cobertura de pruebas. Tampoco es (b): reutilizarlo hoy obligaría a buscar y reemplazar marca, dominio, menú, roles y notificaciones en decenas de archivos, y a arrastrar la duplicación de Mant/Elec.

**¿Conservar la arquitectura o reconstruir el núcleo?** Conservar el stack, el modelo de datos y los módulos de negocio, y **reconstruir el núcleo por extracción**: auth sin alias, permisos declarativos, menú y notificaciones registrables, identidad configurable, respaldos con un solo resolvedor y layout partido. Los pasos 1-5 y 8-9 de 4.4 (≈2-3 semanas) ya permiten iniciar un segundo proyecto. La unificación de órdenes (paso 11) es la mayor ganancia, pero también el mayor riesgo, y puede ir después.

---

## 6. PROBLEMAS DE ORGANIZACIÓN (hallazgos)

### ARQ-01 · ALTA · Autorización en tres capas incoherentes

**Evidencia:**
- `AppServiceProvider.php:97-104`: 8 gates. Fuera del provider, solo `promote-admin` se usa, y únicamente en `SystemAuditCommand.php:103-109`. `manage-users`, `manage-backups`, `view-financial-reports`, `anular-operacion`, `manage-caja`, `close-caja` y `promote-tecnico` tienen 0 usos.
- `AppServiceProvider.php:106-114`: 9 policies. En controladores solo hay `Gate::authorize` en `MantenimientoController` (4) y `ElectronicaController` (3). Las de Factura, Cotizacion, CierreCaja, MovimientoCaja, Stock, Cliente y User solo las ejercita `tests/Feature/PolicyAndAuthorizationAuditTest.php`.
- `routes/web.php:86`: el grupo `role:admin,tecnico` incluye `configuracion` (`:173`), `eventos` (`:177`), `backups` (`:182`) y `usuarios` (`:196`), que en realidad son solo admin por chequeos manuales (`ConfiguracionController:15,27`, `EventoController:23,100`, `BackupController::ensureAdmin` ×8, `UserController:18`).

**Escenario:** quien añada un método a `BackupController` sin `ensureAdmin()`, o lea `routes/web.php` para auditar permisos, concluirá que el técnico tiene acceso. Las policies dan una falsa sensación de control.

**Corrección:** en el paso 5 de 4.4, mover las rutas solo-admin a `Route::middleware('role:admin')` o `can:manage-backups`; aplicar `$this->authorize()` con las policies existentes (o borrarlas); y reemplazar las 29 comparaciones de rol en controladores por `can`.

### ARQ-02 · ALTA · Duplicación Mantenimiento / Electrónica

**Evidencia:**
- **Esquema:** columnas idénticas salvo `descripcion` frente a `descripcion_problema` (`Mantenimiento.php:15-26`, `Electronica.php:16-27`). Hay dos pivotes (`mantenimiento_stock`, `electronica_stock`) y `abonos` tiene dos FKs nulables (migración `2026_06_16_133122:13-14`).
- **Código en espejo:**
  - Controladores con los mismos 10 métodos en distinto orden: 437 y 423 líneas.
  - `AbonoController` y `ElectronicaAbonoController`: 4 líneas distintas de 28.
  - `*StockController`: 2 líneas distintas de 35.
  - Policies: 10 líneas distintas de 63.
  - Exports: 37 de 141.
  - 9 vistas en espejo: `factura` 8 líneas distintas de 123, `factura_pos` 10 de 115, `edit` 4 de 21. Otras ya divergen (`reportes` 412 de 575, `index` 153 de 220), lo que confirma la deriva entre copias.
  - Rutas duplicadas: 13 + 12. El composer de notificaciones, `AuthController`, `GuestController` y las vistas de caja tratan los dos tipos por separado.

**Escenario:** una corrección hecha en una copia y no en la otra (como ya ocurrió con `reportes`, que difiere en 412 líneas). Un tercer tipo de servicio (por ejemplo "Celulares") obligaría a una tercera copia.

**Corrección:** paso 11 de 4.4. Como mínimo inmediato, un `OrdenController` abstracto con `modelClass()`/`prefijo()`/`vista()` y vistas parametrizadas, que ya cubre el ~80 %.

### ARQ-03 · ALTA · Lógica de negocio y consultas en vistas

**Evidencia:**
- `caja/print.blade.php:28-58` y `caja/print_pos.blade.php:31-60`: `preg_match('/#([A-Za-z0-9-]+)/', $desc)` → `Factura::where('numero_factura')`; `preg_match('/Orden\s+#?…/')` → `Mantenimiento::where('id_orden')`; `ELC` → `Electronica`. Luego recalculan el monto total y el historial de pagos.
- La misma lógica aparece en `caja/show.blade.php:45,154`, `caja/edit.blade.php:10` y `caja/_form.blade.php:77`.
- `stocks/_form.blade.php:4-5` consulta `CategoriaStock`.
- `layouts/app.blade.php:1-12` consulta `Configuracion` y lee el logo en base64 desde el layout.

**Escenario:** una descripción editada a mano o con otro formato ("Orden: 15") rompe el vínculo en la impresión, y el total o el historial impreso no coincide con la pantalla. Además, cada impresión dispara de 3 a 5 consultas desde la vista. La lógica no tiene pruebas unitarias directas.

**Corrección:** un servicio `ReferenciaDocumentoResolver` usado por el controlador, que priorice `factura_id`/`abono_id` y deje el fallback textual en un solo lugar. Las vistas reciben datos ya resueltos.

### ARQ-04 · ALTA · Archivos gigantes

**Evidencia** (ver 1.1):
- `layouts/app.blade.php` (2.265 líneas): contiene el FOUC script, 3 modales globales (anular, rechazar, ts-modal), el modal de notificaciones (≈270 líneas), la cabecera de impresión, el mapa de títulos y ≈1.300 líneas de JS inline (`:238-1149`, `:1340-1417`, `:1420-1964`, `:1991-2260`).
- `configuracion/index.blade.php` (1.846): mezcla la configuración de la empresa con toda la UI de respaldos (9 `fetch`).

**Corrección:** pasos 6 y 10 de 4.4.

### ARQ-05 · ALTA · Identidad y dominio fijos en el código

**Evidencia:** ver 3.1 (`AuthController.php:80-83,100,110`; `Configuracion.php:72-80`; `topbar.blade.php:15`; `print_pos.blade.php:195`; `errors/minimal.blade.php:8`; `.env.example:1` "Wolf Guard" sin uso).

**Escenario:** un segundo cliente vería "WolfCore", tendría alias de login que resuelven a `@tecnisystemas.com` y un ticket POS firmado con otra marca.

**Corrección:** paso 2 de 4.4.

### ARQ-06 · MEDIA · Negocio y DDL dentro de `AppServiceProvider`

**Evidencia:**
- `AppServiceProvider.php:81-92` ejecuta `Schema::table(...)->string('formato_factura')` en el boot si falta la columna. Es redundante con la migración `2026_09_09_165000_add_formato_factura_to_configuraciones_table.php`, y lo silencia un `catch` vacío.
- `:116-297`: 182 líneas de consultas de negocio para el topbar.

**Escenario:** en un despliegue con migraciones pendientes, el primer request altera el esquema fuera del control de `migrate`. Después, la migración puede fallar por columna duplicada (depende de sus guardas) y `app:verificar-migraciones` no lo detecta.

**Corrección:** eliminar el bloque DDL y confiar en la migración. Mover el composer a `App\Core\Notifications\TopbarComposer`.

### ARQ-07 · MEDIA · Notificaciones duplicadas y código muerto asociado

**Evidencia:**
- `AuthController.php:254-305` hace 4 consultas (con `with('equipo.cliente')`) y guarda 50 alertas en flash.
- La única lectura es `app.blade.php:1414` (`count(session('alertas_pendientes')) > 0`) para abrir el modal, que a su vez se llena desde el composer.
- `alertas_totales` no se lee en ningún sitio.
- La ruta `electronicas.dismiss-alert` (`routes/web.php:66-70`) borra la clave `alertas_electronica`, que ya no se escribe en ninguna parte, y no la llama ninguna vista ni JS.

**Escenario:** consultas innecesarias en cada login; dos fuentes de verdad para "pendientes".

**Corrección:** que el login haga solo `session()->flash('abrir_notificaciones', true)`; borrar la ruta muerta.

### ARQ-08 · MEDIA · Código muerto

**Evidencia:**
- Directiva `@money` (`AppServiceProvider.php:76-78`) con **0 usos**, frente a 341 `number_format` en vistas (la deuda #12 "5 formas de formatear montos" sigue abierta).
- Gates y policies sin uso (ARQ-01).
- `public/images` sin uso: ≈2,5 MB.
- `SystemAuditCommand.php:240` crea un `Stock` con `stock_minimo`, una columna que no existe y que `$fillable` descarta en silencio.
- `ResetMonthlyStats` no está en el scheduler.

**Corrección:** eliminar, o usar `@money` en lugar de los `number_format` sueltos.

### ARQ-09 · MEDIA · Resolución de binarios y dependencias de plataforma triplicadas

**Evidencia:**
- `BackupController.php:405-420`, `BackupDatabase.php:464-479` y `VerificarRespaldo.php:187-188` repiten las rutas `C:\ServBay\packages\...`.
- `BackupController.php:374-384` usa PowerShell `WScript.Shell`/`explorer.exe`.

**Escenario:** en Linux o en otra instalación hay que tocar 3 archivos. Si una copia diverge, el respaldo funciona y la restauración no, o al revés.

**Corrección:** una clase `MysqlBinaryResolver` que lea `config('backup.*_path')`, y `openFolder` solo si `PHP_OS_FAMILY === 'Windows'`.

### ARQ-10 · BAJA · Auditable con detalles frágiles

**Evidencia:**
- Lista de enmascarado duplicada (`Auditable.php:43` y `:102`).
- Función global `clone_model_data` declarada en el archivo del trait (`:99`), que solo existe si el trait se cargó antes.
- La semántica de anulación está fija (`:61-71`).

**Corrección:** un método estático privado en el trait y la configuración por modelo (4.3.7).

### ARQ-11 · BAJA · Página de desarrollo y scripts locales versionados

**Evidencia:**
- `routes/web.php:93-95`: `/design-system` está accesible en producción para admin y técnico.
- `start-ngrok.bat` está versionado con la ruta absoluta del equipo.
- 81 MB de `.mp4` versionados en `informes_viabilidad/videos`.

**Corrección:** registrar la ruta solo si `app()->isLocal()`, y mover los videos fuera del repositorio (o a git-lfs).

### ARQ-12 · BAJA · Umbral de "stock bajo" fijo y sin campo de stock mínimo

**Evidencia:** `DashboardController.php:36` usa `cantidad <= 5`. No existe una columna `stock_minimo`, aunque el README la anuncia ("stock mínimo").

**Corrección:** añadir `stocks.stock_minimo` o una configuración global.

### 6.4 DOC-01 · ALTA · Documentación desactualizada o contradictoria (16 `.md` revisados)

| Documento | Problema | Evidencia |
|---|---|---|
| `README.md` | Badge "554 Passed" y "100 % Passing"; el `CHANGELOG` dice 604 y hoy falla 1 | `README.md:8,127` frente a `CHANGELOG.md:16` |
| `README.md` | "Precisión: `DECIMAL(12,2)` formateados con `ColombiaHelper`". `ColombiaHelper` no tiene formato monetario: sus funciones son departamentos, municipios, tipos de ID, WhatsApp y TitleCase. | `README.md` sección "Lógica Financiera", `ColombiaHelper.php:14-138` |
| `README.md` | Estructura con `public/ … css/glass.css`; en realidad está en `resources/css/glass/`. Dice "19 Modelos" cuando hay 20. Árbol `tecni-systemas/` frente a la carpeta real `tecnisystemas`. | `README.md` sección Estructura |
| `README.md` | Anuncia "stock mínimo", que no existe | ver ARQ-12 |
| `README.md` | Instalación con `migrate:fresh --seed` sin advertir que borra todo | `README.md:88`, `DOCUMENTACION_1:104` |
| `README.md` | El índice de documentación omite `docs/DEUDA_TECNICA.md` y `docs/GUIA_ARQUITECTURA_CSS_MODULAR.md` | `README.md:141-152` |
| `DOCUMENTACION_1_GUIA_INSTALACION_SERVBAY.md` | Recomienda `php artisan db:backup`, que **no existe** (el comando es `app:backup-db`) | `:153` frente a `artisan list` |
| `DOCUMENTACION_2_MANUAL_ARQUITECTURA_Y_NEGOCIO.md` (sin cambios desde sep-2026) | El diagrama ER declara relaciones inexistentes: `CIERRE_CAJAS→MOVIMIENTO_CAJAS` (no hay `cierre_caja_id`), `CATEGORIA_STOCKS→STOCKS` (no hay FK, es texto), `CLIENTES→MANTENIMIENTOS/ELECTRONICAS` (es vía `equipos`). Dice "ColombiaHelper formatea `$ 1.500.000,00`" (falso) y "19 Modelos". | `:52-75` frente a `grep cierre_caja_id/categoria_id` = 0 resultados |
| `DOCUMENTACION_3_PRESENTACION_GRADO_Y_EMPRESA.md` | "Laravel 12 / PHP 8.3" (instalado: 13.40 / 8.4) | `:37` |
| `DOCUMENTACION_PROYECTO_NOTEBOOKLM.md` | "Laravel 12", "51 pruebas / 166 aserciones", "100 % PASSING" | `:3,109-114` |
| `docs/ARQUITECTURA_Y_MODELO_DATOS.md` | Habla de "Controladores Delgados" (hay 4 de más de 700 líneas). La secuencia se documenta como `sprintf('%s%06d')`, pero el código genera `VT-1`/`ORD-12` sin relleno salvo `$pad` (`OrdenService.php:77`). Dice `X-Frame-Options: SAMEORIGIN`, pero el código usa `DENY` (`SecurityHeaders.php:25`). Enlaces `file:///c:/ServBay/www/tecni-systemas/...` rotos (ruta inexistente). | `:9-20,35-55` |
| `docs/MANUAL_OPERACIONES_Y_MANTENIMIENTO.md` | "Al modificar la contraseña de cualquier cuenta… invalida todas las sesiones activas rotando `remember_token`". Rotar el token no cierra sesiones (no hay `AuthenticateSession`). Solo `PasswordController:56` borra filas de `sessions`; `UserController:133` no lo hace. | `:78` |
| `SECURITY_DEBT.md` | "584 pruebas"; menciona `script-src … https://cdn.jsdelivr.net` y "librerías CDN con SRI", pero la CSP actual no tiene CDN (las librerías son locales) | `:10,42` frente a `SecurityHeaders.php:30` |
| `AUDITORIA_EXHAUSTIVA.md` (118 KB, 21-oct) | Instantánea antigua sin estado de resolución: Laravel 13.19, ruta `tecni-systemas`, P0 ya corregidos (Dusk, git-update…) presentados como abiertos, "`informes_viabilidad/` no versionado" (sí lo está). En la raíz, compite con `CHANGELOG`/`DEUDA_TECNICA` como fuente de verdad. | `:3-5,103-181,863` |
| `docs/DEUDA_TECNICA.md` | Al día y coherente con el código (DEUDA #3-#6, #8, #12 confirmadas) | Correcto |
| `CHANGELOG.md` | Coherente (604 pruebas) | Correcto |
| `DEPLOY_CHECKLIST.md`, `docs/GUIA_ARQUITECTURA_CSS_MODULAR.md` | Sin contradicciones relevantes en lo revisado. La guía CSS reconoce que `_utilities` aloja componentes específicos. | — |
| `DOCUMENTACION_4_ACTA…`, `informes_viabilidad/*.md` | Documentos académicos y legales: no técnicos, de sep-2026, sin verificar | No revisados a fondo |

Hay además **tres nombres de producto** sin documentar cuál es el oficial: "WolfCore System" (UI, README), "Tecni-Systemas / TECNI SYSTEMAS" (docs, datos) y "Wolf Guard" (`.env.example`).

**Corrección:**
- Archivar en `docs/historico/`: `AUDITORIA_EXHAUSTIVA.md`, `DOCUMENTACION_3`, `DOCUMENTACION_4`, `NOTEBOOKLM` e `informes_viabilidad`.
- Dejar como fuente viva README + `docs/ARQUITECTURA` (rehecha) + `DEUDA_TECNICA` + `CHANGELOG` + `DEPLOY_CHECKLIST`.
- Quitar del README los conteos de pruebas (enlazar a CI o al `CHANGELOG`).
- Corregir `db:backup`, el ER, `X-Frame-Options`, la afirmación sobre sesiones y los enlaces `file:///`.
- Documentar `app:verificar-respaldo`.

---

## 7. Qué revisé y qué no

**Revisado (lectura directa o búsqueda dirigida):**
- `routes/web.php`, `routes/console.php`, `bootstrap/app.php` y `AppServiceProvider` completos.
- `layouts/app.blade.php` (estructura, cabecera, notificaciones), `partials/sidebar` y `topbar`.
- `AuthController` (60-125, 254-313), `CheckRole`, `User` (roles), `Auditable`, `Evento::registrar` y `Configuracion`.
- `OrdenService::siguiente`, `AnulacionService` (verificación de admin), `BackupController` (estructura y binarios), `BackupDatabase` (estructura), `config/backup.php`.
- `caja/print.blade.php:20-62` y las búsquedas de consultas en vistas.
- Inicio de `ConfiguracionController`, `EventoController` y `DashboardController`.
- Comparación automatizada Mantenimiento/Electrónica (controladores, modelos, policies, exports, abonos, stock y 9 vistas).
- `composer.json`, `package.json`, los dos lock, `vite.config.js`, `tailwind.config.js`, `glass.css`, `copy-vendor.mjs` y `.gitignore`.
- Uso de imágenes públicas.
- Los 16 `.md` (los técnicos con contraste contra el código; los académicos y legales solo por encabezados y versiones).
- `artisan route:list` y `artisan list`.

**Verificado como correcto:**
- `CHANGELOG` y `DEUDA_TECNICA` coinciden con el código.
- Las librerías JS son locales y llevan SRI.
- `DatabaseSeeder` ya no llama `TestDataSeeder`.
- Los 8 métodos públicos de `BackupController` llaman `ensureAdmin`.
- `ConfiguracionController` y `EventoController` restringen a admin.
- `public/build` no está versionado.
- `PasswordController` borra las sesiones del usuario.

**No revisado (fuera de alcance o sin tiempo):**
- Lógica interna de `MovimientoInventarioController`, `MovimientoCajaController`, `ReporteFinancieroController`, `CotizacionController`, `CierreCajaController`, `StockController`, `StockService`, `HandlesAbono` y `CierreCajaGuard` (corresponden a la auditoría técnica y financiera).
- Contenido de migraciones individuales (salvo abonos y la lista).
- Pruebas (no ejecuté la suite; el contexto indica 604 con 1 fallo conocido).
- Vistas de reportes, PDF y dashboard en detalle.
- `resources/js/app.js` completo.
- `SystemAuditCommand`, `SeedDemoData`, `TestDataSeeder` y los comandos destructivos (solo sus firmas).
- Seguridad de `restore`/`gitUpdate` (agente de seguridad).
- Rendimiento medido con datos reales (solo estimación por lectura).
