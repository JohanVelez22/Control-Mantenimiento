# Análisis de reutilización

> Origen: agente 1, secciones 3 a 5 (conteo archivo por archivo). La conclusión del coordinador y las respuestas a las 12 preguntas están en `EVALUACION_FINAL.md`.

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

