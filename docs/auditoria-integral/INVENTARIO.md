# Inventario del sistema

> Origen: agente 1, secciones 1 y 2 (conteo real de archivos). Ver también `ARQUITECTURA.md`.

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

