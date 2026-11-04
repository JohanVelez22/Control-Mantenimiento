# AUDITORÍA EXHAUSTIVA — Sistema "Tecni-Systemas" (Mantenimiento de Equipos)

**Proyecto:** `C:\ServBay\www\tecni-systemas`
**Stack:** Laravel 13.19.0 · PHP 8.4.25 · MySQL 8.4.11 (ServBay, Windows) · Blade + Vite 8 + Tailwind 3.4 · dompdf 3.1.5 · maatwebsite/excel 3.1.69
**Fecha de auditoría:** 21 de octubre de 2026
**Modo:** 100 % solo lectura. **No se modificó ningún archivo de código, configuración ni dato existente.** Este documento es el único archivo agregado.

---

## PARTE 0 · Metodología, alcance y cómo leer este documento

### 0.1 Alcance real auditado

| Capa | Volumen | Cobertura |
|---|---|---|
| Backend (PHP) | 75 archivos · 530 KB | Controllers, Models, Services, Traits, Exports, Helpers, Console Commands, Middleware, Policies, Providers |
| Frontend (Blade/CSS/JS) | 106 vistas · 1,38 MB | Todos los templates, layout, CSS propio, JS, Vite/Tailwind |
| Base de datos | 66 archivos · 30 tablas | Migraciones, seeders, factories, esquema real vía `information_schema` |
| Tests | 58 archivos · 514 KB | Unit, Feature, Integration, Browser (Dusk) — **instrucción completa** |
| Configuración | 26 archivos | `config/`, `.env`, `.env.example`, `composer.json`, `package.json`, `phpunit*.xml`, Vite/Tailwind |
| Documentación | 7 archivos `.md` | README, DEPLOY_CHECKLIST, SECURITY_DEBT, DOCUMENTACION_1..4, NOTEBOOKLM |
| Git | 380 archivos versionados | `git ls-files`, `.gitignore`, estado del árbol |

### 0.2 Verificaciones ejecutadas (evidencia, no opinión)

- `php artisan route:list --path=_dusk` → **3 rutas Dusk vivas**
- `php artisan route:list -v` (217 rutas con middleware)
- `php artisan security:check` → **2 advertencias**
- `php artisan migrate:status` → 56/56 aplicadas
- `php artisan about` → Config/Routes/Events **NOT CACHED**
- `php artisan schedule:list` → solo `app:backup-db` diario 02:00
- `EXPLAIN` de 13 consultas (con y sin `whereDate`) sobre la BD real
- `information_schema.COLUMNS / STATISTICS / REFERENTIAL_CONSTRAINTS` (esquema, índices, FKs reales)
- `SHOW CREATE TABLE` en tablas clave
- Conteos reales por tabla
- Suite completa: **501 tests, 1507 assertions, 43 s, OK**
- `git ls-files` (filtrado de basura versionada) · `git status` (árbol limpio)
- Lectura íntegra de los 5 informes especializados (seguridad, lógica, frontend, calidad, datos)

### 0.3 Convenciones de este documento

| Marca | Significado |
|---|---|
| ✅ | Verificado por ejecución o lectura inequívoca del código |
| ⚠️ | Sospecha fundada, no verificada en ejecución (se indica explícitamente) |
| ⛔ | Bug confirmado que **produce datos incorrectos o pérdida de información** |
| 🔴 / 🟠 / 🟡 / ⚪ | Severidad: crítico / alto / medio / bajo-info |
| `archivo:línea` | Ubicación exacta para actuar |

### 0.4 Veredicto global

| Dimensión | Puntuación | Estado |
|---|---|---|
| Seguridad / ciberseguridad | **78/100 riesgo** | 🔴 ALTO |
| Lógica de negocio / matemáticas | **55/100** | 🔴 |
| Datos / esquema / rendimiento | **58/100** | 🟠 |
| Operación y despliegue | **55/100** | 🟠 |
| Mantenibilidad / calidad de código | **62/100** | 🟠 |
| Frontend / UX / estética | **78/100** | 🟢 |
| Documentación | **55/100** | 🟠 |
| Cobertura de tests | **40/100** | 🔴 |
| **GLOBAL** | **≈ 62/100** | 🟠 "Funcional, no production-ready" |

---

## PARTE 1 · Lo que está bien (no romper al corregir)

Esta sección es deliberada: gran parte del sistema **sí** está bien diseñado, y una corrección ingenua podría romperlo.

| Área | Verificación | Estado |
|---|---|---|
| **Motor de stock atómico** | `app/Services/StockService.php:27-71` — `DB::transaction` + `lockForUpdate()` + re-lectura del saldo + validación de disponibilidad | ✅ Correcto |
| **Saldos de factura imposibles de negativizar** | `app/Models/Factura.php` — columnas generadas `STORED` con `GREATEST(0, ...)` | ✅ |
| **Cierre de caja único por fecha** | `cierre_cajas.fecha` con índice UNIQUE → el cierre doble se impide en el motor, no en la vista | ✅ |
| **Reglas de abono** | `app/Traits/HandlesAbono.php` — bloquea abonos sobre órdenes anuladas, excedentes y días cerrados, todo en transacción con rollback | ✅ |
| **Guarda de día cerrado** | `app/Services/CierreCajaGuard.php` — re-verificada **dentro** de la transacción en el camino de inventario | ✅ |
| **Auditoría fail-safe** | `app/Traits/Auditable.php` — un fallo al auditar **no** revierte la operación de negocio | ✅ |
| **SQL injection** | 15 coincidencias de `DB::raw`/`whereRaw`/`unprepared` revisadas una a una: **todo el SQL crudo es estático** y la entrada de usuario viaja siempre en bindings | ✅ Cero SQLi |
| **XSS Blade** | 21 usos de `{!! !!}` → todos literales estáticos o `nl2br(e($x))` | ✅ Cero XSS |
| **XSS JavaScript** | `innerHTML` solo con constantes SVG; el render dinámico de auditoría usa `escapeHtml()` en todos los valores; `equipos/index` usa `textContent` | ✅ |
| **Command injection** | 9 coincidencias de `exec`/`proc_open`/`popen` — ninguna concatena input; `git` usa `escapeshellarg()` | ✅ |
| **Path traversal (backups)** | `BackupController.php:283, 308, 513` — regex estricta `/^[a-zA-Z0-9_\-\.]+$/` + bloqueo de `..` | ✅ |
| **Subida de archivos** | `mimes:jpeg,png,jpg,webp` (valida MIME real, no extensión) + `max:5120` + nombre aleatorio | ✅ |
| **CSRF** | `PreventRequestForgery` en el grupo `web`, sin exclusiones | ✅ 217/217 rutas POST/PUT/DELETE |
| **Session fixation** | `Auth::login()` + `session()->regenerate()` explícito; logout con `invalidate()` + `regenerateToken()` | ✅ |
| **Cookies** | `http_only=true`, `same_site=lax`, `serialization=json`, `SESSION_ENCRYPT=true` | ✅ (falta `secure`, ver A-6) |
| **Hash de contraseñas** | Cast `hashed` + `BCRYPT_ROUNDS=12` + sin doble-hash (Laravel detecta `Hash::isHashed`) | ✅ |
| **Mass assignment** | Cero usos de `$request->all()` / `->fill($request…)`; todos los controllers construyen arrays desde `$request->validate()` | ✅ |
| **RBAC de rutas** | Matriz correcta: invitado → 4 GET de consulta; admin/tecnico → mutaciones; 7 endpoints de backup con doble barrera (`role` + `ensureAdmin()`) | ✅ |
| **Enumeración de usuarios** | Mensaje de login **idéntico** exista o no el usuario; la deactivated solo se emite con contraseña ya validada | ✅ |
| **Secretos en Git** | `.env` ignorado y no versionado; `.env.example` sin secretos; los ZIP de backup excluyen `.env*` | ✅ |
| **Enmascarado de auditoría** | `password` y `remember_token` excluidos del diff y enmascarados; `Evento` sin el trait `Auditable` (sin recursión) | ✅ |
| **Dependencias** | Laravel fijado exacto (13.19.0), Symfony 8.1.1, dompdf 3.1.5, Excel 3.1.69, Carbon 3.13.1, Guzzle 7.15.1 — sin CVEs obvias | ✅ |
| **Zona horaria** | `America/Bogota` consistente en `config/app.php` y `.env`; cortes de caja coherentes | ✅ |
| **Numeración de órdenes (parcial)** | `OrdenService` usa `lockForUpdate()` en transacción — correcto salvo el caso de rango vacío (P1-6) | ⚠️ |

---

## PARTE 2 · BLOQUEANTES P0 — Impiden publicar en producción (7)

> Tiempo estimado: 1 día de trabajo. **Ninguno de estos requiere refactor de lógica de negocio** — son configuración, superficie HTTP y datos.

### P0-1 ⛔ Bypass de autenticación ACTIVO sin credenciales (Laravel Dusk)

**Verificado:**
```
$ php artisan route:list --path=_dusk
GET|HEAD  _dusk/login/{userId}/{guard?}   dusk.login › Laravel\Dusk › UserController@login
GET|HEAD  _dusk/logout/{guard?}            dusk.logout
GET|HEAD  _dusk/user/{guard?}              dusk.user
```

- **Causa raíz:** `app/Providers/AppServiceProvider.php:35-37` registra el provider de Dusk si `environment('local','testing')`, y `.env:2` tiene `APP_ENV=local`.
- **Vulnerabilidad:** `vendor/laravel/dusk/src/Http/Controllers/UserController.php:39-52` ejecuta `Auth::guard($guard)->login($user)` **sin contraseña y sin autorización**. Es método **GET** → el token CSRF no aplica. `config/dusk.php` no existe → prefijo `_dusk`, middleware `web` (sin `auth`).
- **Explotación:** `GET /_dusk/login/administrador@tecnisystemas.com` → sesión de administrador completa. Con ella: descarga de backups SQL (hashes bcrypt + PII de clientes), restauración de BD, `git-update`, gestión de usuarios, panel de auditoría.
- **Corrección:**
  1. `.env` → `APP_ENV=production`
  2. `AppServiceProvider.php:35-37` → eliminar el bloque, o exigir bandera explícita: `if (app()->environment('local') && env('DUSK_ENABLED') === 'true')`
  3. Bloqueo en el servidor web — Nginx: `location ~ ^/_dusk { deny all; }` · Apache: `RewriteRule ^_dusk - [F,L]`
  4. **Criterio de aceptación:** `php artisan route:list --path=_dusk` → *"No routes found"*

### P0-2 ⛔ Zip Slip + escritura en el directorio web-servible → RCE

- **Ubicación:** `app/Http/Controllers/BackupController.php:558-561` (extracción) y `:577-586` (copia)
- **Código:**
```php
$zip->extractTo($tempExtractDir);                          // ← sin validar nombres de entrada
File::copyDirectory($publicMediaDir, storage_path('app/public'));  // ← destino web-servible
```
- **Cadena:** `public/storage` es una **Junction** verificada hacia `storage/app/public`. Un ZIP con la entrada `archivos_publicos/cmd.php` se escribe en el webroot → ejecutable en `/storage/cmd.php` → **ejecución remota de código**. Además el `.sql` interno del ZIP se ejecuta con `DB::unprepared()` (`:441`, `:497`).
- **Corrección:**
  - Iterar `ZipArchive::numOfIndex`/`getNameIndex` y rechazar nombres con `..`, rutas absolutas o separadores escapados
  - Extraer entrada por entrada con `getStream()` + `file_put_contents` validando `realpath()` con `str_starts_with($real, $realTempDir)`
  - Al copiar a `storage/app/public`: lista blanca estricta `jpg|jpeg|png|webp|gif|pdf`, **excluyendo siempre** `.php|.phtml|.phar|.htaccess|.svg`
  - `throttle:2,1` + step-up (contraseña de admin) en `/backups/restore`

### P0-3 ⛔ `POST /backups/git-update` = ejecución remota de código desde el navegador

- **Ubicación:** `BackupController.php:734` (`git fetch`), `:749` (`git pull`), `:773` (`Artisan::call('migrate', ['--force' => true])`)
- Remoto verificado: `https://github.com/JohanVelez22/Control-Mantenimiento.git` (rama `main`)
- El path **sí** está escapado (`escapeshellarg`) → no hay command injection. El problema es de diseño: supply-chain RCE. Si la cuenta de GitHub se compromete, el siguiente POST despliega código arbitrario **y aplica migraciones sobre producción**. Sin allowlist de rama, sin hash esperado, sin rollback, sin ventana de mantenimiento.
- **Corrección:** sacarlo a consola → `php artisan app:git-update --expected-sha=<sha>` y **eliminar la ruta HTTP**. Si se conserva: exigir `expected_sha`, verificar `git rev-parse origin/main` contra él antes del pull, `git pull --ff-only origin <branch>`, rechazar árbol con cambios locales, `throttle:1,1` + step-up.

### P0-4 ⛔ Credenciales por defecto débiles en `.env` **y** en el código

```
.env:12   ADMIN_DEFAULT_PASSWORD="Admin123*"
.env:13   TECNICO_DEFAULT_PASSWORD="Tecni123*"
.env:14   INVITADO_DEFAULT_PASSWORD="Invit123*"
```
Fallbacks **hardcodeados en el código** (se ejecutan aunque el `.env` no los defina):
- `database/seeders/AdminUserSeeder.php:18-20` → `env('ADMIN_DEFAULT_PASSWORD', 'Admin123*')`
- `app/Console/Commands/SeedDemoData.php:85, 90, 95` → `Hash::make('Admin123*')` **sin guarda de entorno**
- `app/Console/Commands/SystemAuditCommand.php:206` → crea admin con contraseña `secret`

La contraseña del admin es además el **segundo factor** que autoriza anulaciones y bajas. Confirmado por el propio comando del proyecto: `php artisan security:check` → ❌ *ADMIN_DEFAULT_PASSWORD … valor inseguro por defecto*.

- **Corrección:** rotar las 3 cuentas en BD; eliminar los fallbacks (`env('X')` sin default, abortar si vacío); guarda `environment('local','testing')` en `SeedDemoData`; **bloquear el deploy si `security:check` ≠ 0**; forzar cambio de contraseña en el primer login (`must_change_password`).

### P0-5 ⛔ Credencial MySQL `root` versionada en Git

- **Ubicación:** `phpunit-integration.xml:23-28` — **confirmado por `git ls-files` como trackeado**:
```xml
<env name="DB_USERNAME" value="root"/>
<env name="DB_PASSWORD" value="ServBay.dev"/>
```
- Misma credencial en `.env:33`. Cualquiera con lectura del repo tiene **root en el MySQL de la máquina**.
- **Corrección:** rotar `root`; crear `tecni_app` con permisos mínimos (`SELECT, INSERT, UPDATE, DELETE` sobre `tecni_systemas`); mover la credencial del XML a `.env.testing` no versionado; `git log -p -S "ServBay.dev"` para auditar el historial.

### P0-6 ⛔ Build del frontend desfasado → la interfaz se renderiza sin estilos

- **Verificado:**
```
public/build/manifest.json              → 16/10/2026 13:03
resources/views/layouts/app.blade.php    → 17/10/2026 01:15
resources/views/stocks/_form.blade.php  → 17/10/2026 01:22
```
`public/build` está en `.gitignore` (no versionado) → **cada despliegue pierde el CSS compilado** y las clases Tailwind nuevas no existen en el bundle. Es un fallo visible, no teórico.
- **Corrección:** `npm ci && npm run build` **obligatorio** en el pipeline; verificar que el hash del manifest sea posterior al último `.blade.php`; idealmente versionar el manifest como artefacto de despliegue.

### P0-7 ⛔ Sin backups fuera del disco de datos + sin worker de colas + sin cachés compiladas

- `php artisan about` → **Config NOT CACHED, Routes NOT CACHED, Events NOT CACHED** (Views sí) → cada request reparsea `config/` y recompila el mapa de rutas.
- `php artisan schedule:list` → solo `app:backup-db` (02:00). **No hay `queue:work` en el schedule** y no es verificable el Programador de Tareas de Windows → cualquier job encolado queda en la tabla `jobs` indefinidamente.
- `GOOGLE_DRIVE_BACKUP_PATH` ausente → la sincronización a Drive está desactivada y **todos los backups viven en el mismo volumen que los datos** (un fallo de disco los pierde).
- **Corrección:** `php artisan config:cache && route:cache && view:cache && event:cache` en el deploy; registrar `schedule:run` (cada minuto) y `queue:work` en el Programador de Tareas de Windows; configurar destino off-site (Drive/NAS/S3) y **verificar que se genera un `.sql` real**.

---

## PARTE 3 · P1 — Bugs críticos de lógica, matemáticas y datos (12)

> Estos **sí** producen números incorrectos o pérdida de información. Es el corazón del sistema.

### P1-1 ⛔ El cierre de caja compara el flujo del día contra el conteo físico acumulado

- **Ubicación:** `app/Http/Controllers/CierreCajaController.php:175-196` (usado en `:83-89`)
```php
$movs = MovimientoCaja::whereDate('fecha',$fecha)->where('estado','activo')->where('anulado',false)->get();
$efectivoNeto = $efectivoIngresos - $efectivoEgresos;   // solo ese día
'diferencia' => round($efectivoReal - $efectivoSistema, 2);  // contra el conteo FÍSICO
```
- **Impacto:** un cajón con $5.000.000 de fondo fijo y $200.000 de movimiento reporta "esperado $200.000" y **diferencia −$4.800.000**, aunque el conteo físico sea correcto. El cierre diario es inservible como control de caja; se erosiona la confianza y los usuarios "cuadran a mano" o ignoran el módulo.
- **Corrección:** el efectivo esperado = `saldo_anterior + ingresos_día − egresos_día`. Separar en el modelo `saldo_inicial_dia` / `flujo_dia` / `saldo_final_acumulado` y mostrar los tres en `resources/views/cierre/index.blade.php:215-231`. Incorporar el saldo acumulado de los días sin cierre.

### P1-2 ⛔ El balance del Excel y del PDF cuenta dos veces la misma venta

- **Ubicación:** `app/Exports/ReportesFinancierosExport.php:51-65` y `resources/views/reportes_financieros/pdf_diario.blade.php:251-252`
```php
$ingresos = $this->transacciones->filter(fn($tx) => in_array($tx['tipo'],['ingreso','venta','mantenimiento','electronica']));
```
`$movimientos` fusiona 4 fuentes: órdenes (`mantenimiento`), electrónicas (`electronica`), facturas (`venta`/`compra`) y caja (`ingreso`/`egreso`). Una venta pagada en efectivo aparece **dos veces**: como factura `venta` (por `total_documento`) y como movimiento de caja `ingreso` (por `monto`) → el "Balance Neto" del Excel es ≈2× el real. El HTML del mismo día da una **tercera** cifra (usa las sumas SQL de `:129-149`).
- **Corrección:** una **única fuente de verdad** para el balance (la caja, donde se materializa el dinero) o deduplicar lo ya representado por un movimiento de caja (mismo criterio de deduplicación que ya existe en `ReporteFinancieroController.php:176-196`). Extraer a un `ReporteFinancieroService` consumido por HTML + Excel + PDF, con una sola fórmula y tests de regresión.

### P1-3 ⛔ "Utilidad Neta" del dashboard es el flujo de caja, no utilidad

- **Ubicación:** `app/Http/Controllers/ReporteController.php:73` → `resources/views/reportes/index.blade.php:67-69` y `reportes/pdf.blade.php:140`
```php
'utilidad_neta' => $saldo_mes,   // = ingresos de caja − egresos de caja
```
La utilidad real se calcula en otro sitio (`inventario_utilidad_esperada`, `:76-80`) con otra fórmula y **no se reconcilia**. Un mes con $50.000.000 de compra de inventario muestra "Utilidad Neta −$50.000.000" aunque todas las ventas sean rentables. **Error de comunicación financiera al tomador de decisiones.**
- **Corrección:** renombrar a "Flujo de caja del mes" (`flujo_caja_mes`) y calcular la utilidad contable real (ventas − costo de lo vendido, incluyendo órdenes y electrónicas) como métrica separada y reconciliada con el acumulado.

### P1-4 ⛔ La edición de factura puede mutar ítems y stock de otra factura

- **Ubicación:** `app/Http/Controllers/MovimientoInventarioController.php:601` y `:668`
```php
'existing_items.*.id' => 'required|exists:factura_items,id',   // ← no acotado a la factura
$item = FacturaItem::findOrFail($itemData['id']);              // ← búsqueda GLOBAL
```
El borrado de ítems **sí** está acotado (`:649` usa `$factura->items()->whereNotIn(...)`) → la intención era acotar y se omitió en el update. Con un `id` manipulado se reescribe un ítem de **otra** factura y el bloque de ajuste (`:677-731`) **mueve stock real** entre dos documentos.
- **Corrección:** `FacturaItem::where('factura_id',$factura->id)->findOrFail($itemData['id'])` + `Rule::exists('factura_items','id')->where('factura_id',$factura->id)` + rechazar IDs duplicados en el payload.

### P1-5 ⛔ Una cotización dirigida a Proveedor se convierte en Factura de Venta y descuenta stock

- **Ubicación:** `app/Http/Controllers/CotizacionController.php:357-364, 385-390`
```php
$facturableType = $cotizacion->proveedor_id ? Proveedor::class : Cliente::class;  // detecta proveedor
'tipo_movimiento' => 'venta',     // ← pero el tipo es FIJO
$stock->decrementarStock((int) $item->cantidad);   // ← descuenta inventario
```
La conversión de una cotización de **compra** produce: factura de venta al proveedor + egreso de caja + salida de stock. La dirección contable es exactamente la inversa.
- **Corrección:** `'tipo_movimiento' => $cotizacion->proveedor_id ? 'compra' : 'venta'`, con signo de caja y ajuste de stock invertidos. Test unitario que cubra ambos sentidos.

### P1-6 ⛔ Carrera en la numeración de facturas → colisión garantizada en el primer uso

- **Ubicación:** `app/Services/OrdenService.php:35-52` (verificado con EXPLAIN sobre la BD real)
```php
$query->where($columna,'like',"{$prefijo}%")->orderByDesc('id');
if ($lock) { $query->lockForUpdate(); }
$ultimo = $query->first();
$numero = $ultimo ? ((int) preg_replace('/[^0-9]/','',$ultimo->{$columna})) + 1 : 1;
```
`lockForUpdate()` sobre un rango **vacío no bloquea ninguna fila**. Hoy hay **0 facturas `VT-%`** en la BD → dos ventas simultáneas calcularían ambas `VT-1` → `facturas_numero_factura_unique` (confirmado `NON_UNIQUE=0`) lanza `SQLSTATE 23000 duplicate entry` → **error 500**. El docblock de la clase afirma que el bloqueo "garantiza unicidad": **es falso**. Además ordena por `id DESC`, no por valor numérico → la secuencia retrocede y vuelve a colisionar.
- **Corrección:** tabla de contadores dedicada:
```sql
CREATE TABLE secuencias_documento (prefijo VARCHAR(10) PRIMARY KEY, valor INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB;
```
  y obtener el siguiente número con una sentencia atómica dentro de la transacción:
`UPDATE secuencias_documento SET valor = LAST_INSERT_ID(valor + 1) WHERE prefijo = 'VT-'` + `SELECT LAST_INSERT_ID();`

### P1-7 ⛔ `whereDate()` anula el uso de todos los índices de fecha

**Evidencia EXPLAIN sobre la BD real:**

| Consulta | type | key | rows |
|---|---|---|---|
| `movimiento_cajas WHERE date(fecha)='2026-10-01' AND estado='activo' AND anulado=0` | **ALL** | **NULL** | 259 |
| `movimiento_cajas WHERE fecha>='2026-10-01' AND …` | range | `idx_movcaja_fecha` | 62 |
| `cierre_cajas WHERE date(fecha)='2026-10-01'` | **ALL** | **NULL** | 25 |
| `cierre_cajas WHERE fecha='2026-10-01'` | const | `cierre_cajas_fecha_unique` | 1 |
| `mantenimientos WHERE date(fecha_entrada)='2026-10-01'` | **ALL** | **NULL** | 100 |
| `mantenimientos WHERE fecha_entrada='2026-10-01'` | ref | `idx_mant_fecha_entrada` | 2 |

`DATE(fecha)` envuelve la columna y el optimizador no puede aplicar el índice. Afecta: `MovimientoCajaController.php:43-44`, `MovimientoInventarioController.php:340-341`, `ReporteFinancieroController.php:129-149` (~15 agregados), `EventoController`, `MantenimientoController` y el cierre de caja.
- **Corrección:** como las columnas son tipo `DATE`, `where('fecha', $d)` es **exacto equivalente** y usa índice. Si alguna vez son `DATETIME`, usar rangos `>= $d 00:00:00 AND < $d+1 00:00:00`.

### P1-8 ⛔ Cascadas `ON DELETE CASCADE` destructivas sobre la información contable

**Verificado en `information_schema.REFERENTIAL_CONSTRAINTS`:**

| Padre | Hijos en CASCADE |
|---|---|
| `users` | `equipos`, `mantenimientos`, `electronicas`, `abonos`, **`movimiento_cajas`**, **`cierre_cajas`** |
| `clientes` | `equipos` → `mantenimientos`/`electronicas` → `abonos` |
| `tecnicos` | `mantenimientos`/`electronicas` → `abonos` |
| `stocks` | `mantenimiento_stock`, `electronica_stock`, `bajas_stock` |
| `facturas` | `factura_items` |

- **Impacto:** borrar un usuario borra **toda la caja y todos los cierres**; borrar un cliente borra su historial de equipos, órdenes y pagos. Irreversible y sin auditoría.
- **Corrección:** `RESTRICT` o `SET NULL` en toda la cadena + borrado lógico (`softDeletes`) en `mantenimientos`, `abonos`, `movimiento_cajas`, `cotizaciones`, `equipos`, `clientes`, `tecnicos` (hoy solo 2 de 30 tablas lo tienen).

### P1-9 ⛔ Búsqueda de facturas por `LIKE` sin anclaje: `#VT-1` captura `#VT-10`

- **Ubicación:** `app/Models/Factura.php:138` · `app/Models/MovimientoCaja.php:112` · `app/Providers/AppServiceProvider.php:150-157, 183-189` · `app/Http/Controllers/ReporteFinancieroController.php:180`
```php
->where('descripcion','like',"%#{$this->numero_factura}%");
```
- **Cascada confirmada:** (1) `Factura::recalcularPagos()` (`:123-138`) suma pagos de **facturas ajenas** → `saldo_pendiente = 0` en la factura #1; (2) `MovimientoCaja::getTotalPagadoAttribute()` (`:86`) infla el pagado; (3) `ReporteFinancieroController.php:180` marca un movimiento como "vinculado a factura" y **excluye ese importe de la cartera** (`:194-196`) → el importe **desaparece de ambos lados**; (4) el topbar enlaza a la factura equivocada.
- **Corrección:** relacionar por `factura_id` — la columna **ya existe** en `movimientos_cajas` — en lugar de texto. Es determinista y elimina la ambigüedad de raíz.

### P1-10 ⛔ El dashboard y los listados cuentan abonos anulados como pagados

- **Ubicación:** `app/Http/Controllers/DashboardController.php:57` + accessor `app/Models/Mantenimiento.php:77-86` y equivalente en `Electronica.php`
```php
->withSum('abonos as total_abonado','monto')   // ← SIN filtro de anulado
// accessor:
if (isset($this->attributes['total_abonado'])) return (float) $this->attributes['total_abonado'];  // ← usa el alias sin filtrar
```
La rama del accessor **ignora** el filtro que sí aplica en la otra rama. Un abono anulado de $3.000.000 deja la orden con saldo 0 → **desaparece de la cartera por cobrar**.
- **Corrección:** `->withSum(['abonos' => fn($q) => $q->where('anulado', false)],'monto')` y eliminar la rama intermedia del accessor (o que el alias nunca Dialogue sobre un filtro ausente).

### P1-11 ⛔ La anulación no revierte el costo de la orden ni marca los abonos

- **Ubicación:** `app/Services/AnulacionService.php:113-160`
- `revertirStockYAbonos()` revierte el stock (`:118-128`) y marca los **movimientos de caja** como anulados (`:130-140`), pero **no** toca el campo `costo` del documento ni marca los registros `Abono` con `anulado = true`.
- **Impacto:** tras anular un mantenimiento con repuestos, la orden conserva el costo de repuestos ya devueltos a bodega → costo y rentabilidad inflados. Los `Abono` siguen activos → el accessor `total_abonado` los cuenta → el `saldo_pendiente` de una orden anulada aparenta estar pagada.
- **Corrección:** decrementar `costo` por el costo de los repuestos revertidos y marcar `abonos.anulado = true` (usar ese flag como única fuente de verdad).

### P1-12 ⛔ El costo de los repuestos se desincroniza del pivote (deriva de precio)

- **Ubicación:** `app/Traits/HandlesStockAttach.php:40-52` (agregar) vs `:91-98` (desvincular)
```php
$model->stocks()->updateExistingPivot($stock->id, ['cantidad' => $newCantidad]);  // NO actualiza precio_unitario
$model->increment('costo', $precioUnitario * $validated['cantidad']);             // usa precio actual P2
// al desvincular:
$precio = $repuesto->pivot->precio_unitario;   // precio viejo P1
$model->decrement('costo', $precio * $cantidad); // P1 × (Q1+Q2)
```
- **Ejemplo numérico:** repuesto a P1=$100 (2 uds, costo $200). Se agrega 1 uds cuando el precio ya es P2=$150 → costo=$350. Al quitarlo → se resta $100×3=$300 → **queda $50 de costo residual** en una orden sin repuestos. El error se acumula con cada re-precio.
- **Corrección:** actualizar también `precio_unitario` en el pivote, o calcular `costo = Σ(pivot.precio_unitario × pivot.cantidad)`. Ideal: costo unitario congelado por línea.

---

## PARTE 4 · Seguridad — resto de hallazgos (ALTO / MEDIO / BAJO / INFO)

### 4.1 Seguridad ALTA

| # | Ubicación | Hallazgo | Escenario | Corrección |
|---|---|---|---|---|
| A-4 | `app/Http/Middleware/SecurityHeaders.php:30` · `resources/views/layouts/app.blade.php:31-37` | CSP con `'unsafe-inline'` **y** `'unsafe-eval'` → deja de ser un control de XSS. Scripts de CDN (flatpickr, tom-select) **sin `integrity`/SRI**. `npmcdn.com` en la allowlist (mirror con historial de paquetes maliciosos) | Cualquier XSS se ejecuta sin restricción de origen; un paquete CDN comprometido corre en el contexto de la app con acceso al DOM y al token CSRF | Migrar los `<script>` inline a módulos Vite; autoalojar flatpickr/tom-select por npm con versión fija; CSP `script-src 'self'`; eliminar `npmcdn.com` |
| A-5 | `app/Services/AnulacionService.php:41-51` · `MantenimientoController.php:279-288` · `ElectronicaController.php:164-173` | **Fuerza bruta de la contraseña de admin sin límite**: `PUT/PATCH mantenimientos|electronicas` **no tienen `throttle`** (el grupo de anulaciones sí lo tiene, `routes/web.php:201`). `Hash::check` bcrypt coste 12 por intento | Un técnico automatiza POST/PUT con `admin_password` a ~10-20 req/s → cientos de miles de intentos diarios. Con la password por defecto (`Admin123*`) es viable | `throttle:5,1` en ambas rutas + contador en `Cache` por (admin, IP) con bloqueo progresivo (1 min → 5 min → 30 min) tras 3-5 fallos, notificando al admin y registrando en `eventos` |
| A-6 | `.env:40` vs `.env:6` · `config/session.php:172` · `AuthController.php:127` | `APP_URL=https://` pero `SESSION_SECURE_COOKIE=false` → la cookie de sesión **y** la cookie "recordarme" se emiten **sin `Secure`** y viajan en claro ante cualquier HTTP | Secuestro de sesión por atacante en posición de red (WiFi de oficina, ISP, router comprometido). `SESSION_ENCRYPT=true` protege el contenido, no el transporte | `SESSION_SECURE_COOKIE=true` + redirección 301 de `http://` a `https://` + `SESSION_SAME_SITE=strict` (o `lax` si hay redirecciones externas) |

### 4.2 Seguridad MEDIA

| # | Ubicación | Hallazgo | Impacto | Corrección |
|---|---|---|---|---|
| M-1 | `app/Exports/StocksExport.php:120-135` · `MantenimientosExport.php:121-140` · `ElectronicasExport.php` · `AcumuladoExport.php` · `ReportesFinancierosExport.php` | **CSV Injection**: valores de usuario (producto, código, nombre de cliente/técnico) se escriben sin neutralizar prefijos de fórmula `=`, `+`, `-`, `@` | Al abrir el `.xlsx` y hacer clic, se ejecuta la fórmula: exfiltración de la fila (NIT, precios, saldos) o DDE en Excel | Prefijar con `'` o espacio; centralizar en un helper `ExcelSafe::cell($valor)`; restringir la exportación a rol admin |
| M-2 | `app/Http/Controllers/UserController.php:112-123` · `app/Models/User.php:24-27` | El cambio de contraseña **no** regenera `remember_token` **ni** invalida sesiones activas | Si un token "recordarme" o una sesión fue filtrada, cambiar la contraseña **no corta el acceso** | `$usuario->setRememberToken(Str::random(60))` + `Auth::logoutOtherDevices($request->password)` + registrar en `eventos` |
| M-3 | `app/Http/Controllers/GuestController.php:88-124` · `MantenimientoController.php:377-418` · `ElectronicaController.php:369-410` | Doble factor del invitado = (cédula adivinable + nº de orden **secuencial** `ORD-1`, generado por `OrdenService`); throttle 30/min | Un invitado que conoce la cédula itera `ORD-1 … ORD-500` en ~17 min; cada acierto devuelve la orden completa **y** añade el ID a `session('consultas_autorizadas')`, habilitando la factura PDF | Token opaco de un solo uso entregado al cliente (SMS/WhatsApp/email), con expiración en minutos e invalidación tras el uso; límite de intentos por cédula; auditoría de consultas de invitado |
| M-4 | `app/Policies/MantenimientoPolicy.php:22-38` · `app/Policies/ElectronicaPolicy.php:22-38` | La policy da acceso a **todas** las órdenes del cliente que coincida **solo por email** (`Cliente::where('email',$user->email)->first()`) | Un correo compartido por varios clientes concede el histórico completo de órdenes y facturas de ese cliente | Tabla explícita de vinculación `cliente_usuarios` / `orden_accesos` con token por cliente; como mínimo validar email verificado |
| M-5 | `app/Http/Controllers/StockController.php:62-86` (store) y `:113-155` (update) | El técnico escribe `cantidad` **directamente** (`Stock::update($validated)`), evadiendo `StockService` (sin lock, sin movimiento, sin rastro) | El inventario se puede inflar o reducir sin ningún asiento; los reportes financieros calculan la valorización sobre `Stock.cantidad` → descuadre silencioso | Quitar `cantidad` del formulario y del `$fillable`; todo por `StockService`; flujo específico de "ajuste de inventario" con motivo, doble aprobación y asiento |
| M-6 | `MantenimientoController.php:190-201, 207-241, 243-258, 260-303, 59-75` · `ElectronicaController.php:65-76, 78-117, 128-142, 144-189` | `Gate::authorize` se invoca en `index`/`show`/`factura` pero **no** en `create`/`store`/`edit`/`update`/`duplicate` | Un refactor futuro (ruta movida, middleware desactivado) deja la mutación sin control de rol. Hoy no explotable, pero elimina la segunda barrera | Añadir `Gate::authorize('create',…)` / `Gate::authorize('update',…)` en cada mutación |
| M-7 | `app/Http/Controllers/CierreCajaController.php:156-171` vs `:125-134` | `update` permite a un técnico editar `observaciones` y `motivo_diferencia` de un cierre **sin** la contraseña de admin (el `destroy` sí la exige) | Alteración retroactiva de la justificación de un descuadre de caja sin constancia de que requirió autorización superior | Exigir `autorizarOperacionSensible()` cuando `diferencia != 0`, o hacer esos campos inmutables una vez creado el cierre |
| M-8 | `routes/web.php` (completo) | `throttle` ausente en: `POST /login` (tiene throttle propio en caché, ver §4.3), los 7 endpoints `/backups/*`, `/usuarios`, `/configuracion`, `/caja`, `/inventario/compra|venta`, `/cierre`, `/eventos/backup` y **todos** los PUT/PATCH | DoS autenticado: generar miles de backups (llenan disco), provocar `migrate` repetidos vía `git-update`, enumerar el sistema | `RateLimiter::for('sensitivo')` por rol (admin 60/min, técnico 30/min); `/backups/*` 5/min, `/usuarios` 10/min, `/configuracion` 5/min; aplicar throttle a reportes y exportaciones (las rutas más costosas) |
| M-9 | `BackupController.php:176-181` · `CotizacionController.php:119, 233, 279, 419` · `MovimientoInventarioController.php:419` · `EventoController.php:108` · `StockController.php:312` | `$e->getMessage()` devuelto al cliente → los mensajes de PDO/MySQL incluyen **nombres de tabla, columnas, fragmentos SQL y rutas del sistema** | Fuga del esquema de BD y topología del servidor, que facilita la explotación de P0-2 | Mensaje genérico al cliente + `report($e)` / `Log::error()` con el detalle completo |
| M-10 | `BackupController.php:348-387` (`openFolder`), `:392-428` (`resolveMysqlBinary` con `exec`), `:454-499` (`restoreSqlFile` con `proc_open`) | `openFolder` ejecuta un script **PowerShell** en el servidor desde HTTP y devuelve la ruta absoluta; `restoreSqlFile` ejecuta SQL arbitrario si el `.sql` está manipulado | Un admin puede lanzar procesos en el escritorio del servidor (o ejecutar SQL arbitrario) | Eliminar `openFolder` del código web; mover la resolución del binario MySQL a configuración validada; la restauración desde comando de consola con step-up |
| M-11 | `app/Http/Controllers/AuthController.php` (login) | El login acepta localizar la cuenta por **nombre** y tiene un fallback que instancia `User::all()` | En una organización con nombres similares, la contraseña puede validarse contra la cuenta equivocada; `User::all()` carga el padrón completo en memoria en cada intento | Unificar a correo (o documento) como identidad única, con normalización; nunca un fallback a `User::all()` |
| M-12 | `app/Http/Controllers/AuthController.php:55, 167, 209` | Bloqueo por IP **compartida**: 5 fallos bloquean toda la oficina (NAT); y un login exitoso de **cualquier** usuario libera el bloqueo global | DoS trivial (bloquear la red de la empresa 30 min) | Bloquear por (IP + correo) con umbral por cuenta mayor que por IP; no limpiar el bloqueo global desde otro login; vía de desbloqueo administrativo |

### 4.3 Seguridad BAJO / INFO

| # | Ubicación | Hallazgo | Corrección |
|---|---|---|---|
| B-1 | `usuarios/index.blade.php:45,76` · `usuarios/_form.blade.php:43` · `tecnicos/index.blade.php:45` · `tecnicos/_form.blade.php:58` · `stocks/index.blade.php:54-55` · `stocks/show.blade.php:14-15` · `stocks/_form.blade.php:79-80` | Patrón frágil `onclick="openImageLightbox('…','{{ addslashes($x->name) }}',this)"` — concatena escape HTML y escape JS en el mismo contexto. **Hoy no es explotable** (verificado: `addslashes` neutraliza `'` y `\`), pero es un anti-patrón latente: si se quita `addslashes` o se cambian a comillas dobles → XSS almacenado | `@js($x->name)` / `Illuminate\Support\Js::from()` + `addEventListener` + `data-*` |
| B-2 | `SecurityHeaders.php:30` | `https://npmcdn.com` en la allowlist de `script-src` (mirror de npm con historial de paquetes maliciosos) | Eliminar de la CSP |
| B-3 | `app/Providers/AppServiceProvider.php:48-50` | `request()->header('X-Forwarded-Proto')==='https'` se lee sin validar proxy de confianza y fuerza `URL::forceScheme('https')` | Usar solo `request()->isSecure()` (Laravel ya aplica `trustProxies` en `bootstrap/app.php:22-27`) |
| B-4 | `app/Console/Commands/BackupDatabase.php:302-317` | La contraseña de MySQL viaja en la línea de comandos (`--password="…"`) → visible en la lista de procesos | Usar solo `MYSQL_PWD` (o `--defaults-extra-file` con permisos 600) |
| B-5 | `app/Http/Controllers/ElectronicaController.php:81, 152` | `id_orden` de libre entrada del usuario (`nullable|string|unique`), a diferencia de Mantenimiento que lo genera con `OrdenService`; se usa en el nombre del PDF | Generar siempre con `OrdenService`; si debe editarse, validar `^ELC-\d{4,6}$` |
| B-6 | `app/Services/AnulacionService.php:41-51` | `adminPasswordValida` prueba contra **todos** los admins sin registrar cuál autorizó | Registrar el admin autorizador en `eventos`; considerar un secreto por admin |
| B-8 | `app/Traits/Auditable.php:102` y `:43-45` | La máscara cubre `password`, `remember_token`, `email`, `identificacion`, `telefono`, `movil`, `direccion` — pero **no** `nombres`, `apellidos`, `nombre_razon_social`, `contacto_nombre`, `notas`, `descripcion` | Ampliar la lista; considerar TTL/retención para `eventos` |
| B-9 | `GuestController.php:110-112, 121-123` · `MantenimientoController.php:410-412` | Los IDs de orden "autorizados" viven en `session('consultas_autorizadas')` **sin caducidad** (180 min) y se acumulan | `Cache::put` con TTL corto (15 min) por orden |
| B-10 | `BackupController.php:130` | `getConfig` devuelve `'storage_path' => $this->getBackupDir()` → ruta física absoluta del servidor en el JSON | Devolver ruta relativa o eliminarla |
| B-11 | `SetupDatabase.php:57` · `SeedDemoData.php:41-56` · `ResetTests.php:38-72` | Comandos destructivos (`migrate:fresh --seed`, `TRUNCATE` de 17 tablas, `SET FOREIGN_KEY_CHECKS=0`) **sin guarda de entorno** | `if (!app()->environment('local','testing')) { $this->error(…); return 1; }` en los tres |
| B-12 | `composer.json:18` | `laravel/tinker` (y `psy/psysh`) en `require` → se instalan en producción | Mover a `require-dev` |
| B-13 | `.env:23-26` | `LOG_CHANNEL=stack` + `LOG_STACK=single` + `LOG_LEVEL=debug` → **un único archivo sin rotación** + trazas y SQL en disco | `LOG_STACK=daily`, `LOG_DAILY_DAYS=14`, `LOG_LEVEL=warning` |
| B-14 | `app/Console/Commands/SystemAuditCommand.php:206` | Crea un usuario `'password' => Hash::make('secret')` | Contraseña aleatoria efímera |
| B-15 | `app/Http/Middleware/SecurityHeaders.php:29` | `X-XSS-Protection: 1; mode=block` está obsoleto y en navegadores antiguos **introduce** vulnerabilidades | Eliminar |
| B-16 | `SecurityHeaders.php:31` | HSTS se emite **también en respuestas HTTP** (quema el dominio antes de tener TLS válido) | Emitir solo con `request()->isSecure()` |
| B-17 | `SecurityHeaders.php:30` | La CSP no incluye `frame-ancestors` (no hereda de `default-src`); cubierto solo por `X-Frame-Options: DENY` | Añadir `frame-ancestors 'none'` |
| I-1 | `routes/web.php` (ausencia) · `config/auth.php:95-102` | **No existe flujo de "olvidé mi contraseña"** (ni reset por token ni cambio forzado). El broker `users` está definido con `expire: 60` y `throttle: 60` pero ninguna ruta lo consume | Implementar reset por token de un solo uso, o documentar que la recuperación es vía admin (lo que concentra el uso del segundo factor A-5) |
| I-2 | `config/cors.php` **no existe** | `HandleCors` es middleware global y sin config publicada → `config('cors',[])` vacío → CORS cerrado de hecho. Correcto pero implícito | Publicar `config/cors.php` con `allowed_origins => []` explícito |
| I-5 | `.env:11` · `.env.example:11-12` | Configuración muerta: `ADMIN_REGISTRATION_PASSWORD="Control2026*"` no se usa en ningún archivo; `ROLE_PROMOTE_ADMIN_SECRET` / `ROLE_PROMOTE_TECNICO_SECRET` solo existen en el ejemplo y **no hay código que las lea** (las Gates `promote-admin`/`promote-tecnico` de `AppServiceProvider.php:74-75` solo comprueban `role === 'admin'`) | Eliminar o implementar — mantener secretos que nadie lee hace creer que hay un control inexistente |
| I-6 | `.env:3` · `config/app.php:100-107` | `APP_KEY` válido y `config/app.php` soporta `APP_PREVIOUS_KEYS` → la rotación de clave es viable sin invalidar sesiones, pero **no hay procedimiento documentado** | Fijar en `DEPLOY_CHECKLIST.md`: rotar → desplegar → esperar TTL de sesión → retirar clave antigua |
| I-7 | `SECURITY_DEBT.md:25-37` | La deuda de `'unsafe-inline'`/`'unsafe-eval'` y la migración de CDN **ya está documentada** (buena práctica) → el riesgo es que se archive sin fecha ni responsable | Convertirla en issue con criterio de aceptación |

---

## PARTE 5 · Lógica de negocio — hallazgos MEDIO y BAJO (completos)

| # | Ubicación | Hallazgo | Impacto | Corrección |
|---|---|---|---|---|
| CAJA-02 | `CierreCajaController.php:70-111` | El cierre bloquea la fila `CierreCaja` (`:72`) pero **no** los `movimiento_cajas` del rango → un movimiento concurrente queda fuera de todos los cierres | `SELECT … FOR UPDATE` sobre el rango, o re-calcular el snapshot dentro de la transacción y re-verificar antes del commit |
| CAJA-03 | `cierre/show.blade.php:98` vs `CierreCajaController.php:177` | El detalle muestra movimientos **anulados** bajo el rótulo "transacciones activas" | Aplicar el mismo filtro o separar en bloques "Activas" / "Anuladas (informativo)" |
| CAJA-04 | `MovimientoCaja.php:30-36` · `CierreCaja.php:19-27` | **Sin casts monetarios**: BD `decimal(12,2)` llega a PHP como `float` | Errores de coma flotante en sumas acumuladas; `$diferencia === 0` frágil | `protected $casts = ['monto'=>'decimal:2','monto_total'=>'decimal:2', …]` en ambos modelos (y `Abono::monto`, `Cotizacion::total`) |
| CAJA-05 | `MovimientoInventarioController.php:972` · `CotizacionController.php:398` | `registrarMovimientoCaja()` fija siempre `'tipo_pago' => 'efectivo'` | Los subtotales de **consignación son estructuralmente cero** para inventario (`ReporteFinancieroController.php:131-132, 186-188`); una venta a crédito queda clasificada como efectivo | Derivar `tipo_pago` del estado de la factura (`pendiente_pago` → consignación, `pagada` → efectivo) o exponerlo en el formulario |
| FAC-03 | `MovimientoInventarioController.php:110` vs `Factura.php:94` | La compra **no** escribe `precio_compra`; `costo_total` se calcula contra el precio **actual** del catálogo | Si el precio sube, la utilidad de facturas de compra anteriores **se reduce** y el costo de las de venta se infla. Un artículo comprado a $1.000 y revendido a $2.000 reporta $500 de utilidad en vez de $1.000 | Congelar `costo_unitario` en la línea de factura; actualizar `precio_compra` con promedio ponderado o último precio según política |
| FAC-04 | `MovimientoInventarioController.php:514` · `Factura.php:162, 179` | Anular fuerza `total_pagado = 0` pero **no** reescribe `observaciones` (conserva "⚠️ SALDO PENDIENTE: $X") | La factura anulada muestra una deuda que ya no existe; en la cartera una factura anulada por $2.000.000 aparece como debible | Reescribir `observaciones` con la nota de anulación y forzar saldos coherentes con el estado `anulada` |
| FAC-05 | `MovimientoInventarioController.php:597, 632` · `Factura.php:123-138` | `updateFactura` valida solo el `total_pagado` **enviado**, sin contrastar los abonos ya existentes | Sobrepago → `saldo_a_favor` fantasma con estado `emitida`; abonos que no se pueden aplicar a ninguna otra factura | Validar `total_pagado + abonos_no_anulados <= total_documento`; aplicar el excedente como crédito o rechazarlo |
| FAC-06 | `AnulacionService.php:113-143` | La anulación devuelve el stock pero **no** revierte el `costo` del documento ni marca los `Abono` | Ver P1-11 | Ídem P1-11 |
| STK-02 | `StockController.php:113-152` | La edición de stock escribe `cantidad` sin movimiento ni trazabilidad (contrapartida de M-5) | Los ajustes por conteo físico, merma o corrección quedan invisibles; `BajaStock` queda como único mecanismo documentado — evitable por esta vía | Quitar `cantidad` del formulario; o exigir motivo y escribir asiento de ajuste |
| STK-03 | `StockService.php:122-136` | `revertirBaja()` hace `$baja->delete()` → la merma **desaparece** del histórico | El historial de pérdidas es falsable; el reporte refleja solo bajas vigentes | Estado `revertido` (+ `revertido_por`, `revertido_at`); excluir de reportes de pérdida vigente |
| STK-04 | `app/Models/Stock.php:44-46` | `precio_tecnico = precio_compra + precio_compra * ((utilidad/2)/100)` → el técnico se lleva **la mitad del margen descontada del precio de venta** (utilidad 40 → venta $140, técnico $120) | Si la intención era bonificación, reduce la rentabilidad frente a lo que el usuario espera del campo "utilidad" | Confirmar la regla con el dueño del sistema; si es bonificación, campo independiente `bonificacion_tecnico` |
| COT-02 | `CotizacionController.php:254-281` | `anular()` **solo alterna** `anulado`; no revierte factura, stock ni caja. Y es un **toggle**: anular 2 veces = reactivar, dejando la factura vigente con el stock descontado | El estado de la cotización deja de reflejar el negocio; no hay reconciliación posible desde la UI | Si hay factura vinculada → anular vía `AnulacionService` + revertir stock + anular movimiento de caja, en transacción. Separar `anular`/`reactivar` explícitos (como ya existe en Mantenimiento y Electrónica) |
| COT-03 | `CotizacionController.php:318` vs `:321` | El guard de día cerrado se evalúa **fuera** de la transacción | Diseño inconsistente entre caminos | Mover `CierreCajaGuard::asegurarAborta()` dentro de la transacción, junto al `lockForUpdate` de `:324` |
| COT-04 | `CotizacionController.php:342, 376-390` | Una línea `tipo=stock` **sin `item_id`** (permitido por la validación) crea `FacturaItem` con `stock_id=null` y **no toca inventario**, pero sí suma al total | El usuario cree que está vendiendo inventario reservado; la venta sale sin existencia y la pérdida no queda como merma | `required_if:tipo,stock` para `item_id`; rechazar la conversión si falta |
| COT-05 | `app/Models/CotizacionItem.php:22-38` | El subtotal solo se recalcula **cuando es 0** → queda desactualizado; además castea `cantidad` a `int` (trunca decimales). El PDF lo parchea visiblemente con `?:` (`cotizaciones/pdf.blade.php:47`) | El total de la cotización puede no coincidir con la suma de las líneas; el PDF muestra el valor viejo | Recalcular siempre en `saving`; eliminar el parche `?:` del PDF |
| COT-06 | `CotizacionController.php:254-258` vs `:283-291` | "Anular" exige contraseña de admin; "Rechazar" no (comentario: *"sin contraseña"*) | `rechazada` tiene el mismo efecto operativo que `anulada` con menor barrera | Uniformar la política de autorización de transiciones de estado |
| ORD-03 | `AnulacionService.php:146-160` | El fallback de emparejamiento usa `orWhere('estado','activo')->orWhere('anulado',false)` → condición **verdadera para casi todo movimiento activo** | Riesgo de anular/reactivar el movimiento de caja equivocado (frecuente: dos abonos del mismo monto y día) | Exigir siempre la relación por `abono_id` (ya existe en `movimientos_cajas`); `andWhere` en vez de `orWhere` |
| ORD-04 | `HandlesStockAttach.php:52` · `ElectronicaController.php:86, 157` | `costo` es automático (repuestos) **y** editable a mano (`'costo' => 'required|numeric|min:0'`) → se pisan | El costo de la orden no es auditable ni reproducible; dos usuarios obtienen costos distintos | Separar `costo_repuestos` (derivado de pivotes) y `costo_mano_obra` (editable); `costo_total` = suma |
| REP-02 | `ReporteController.php:65-66` | La valorización de inventario **incluye artículos dados de baja** (`Stock::sum(DB::raw('cantidad * precio_compra'))` sin `activos()`) | El activo y la proyección de ganancia quedan inflados | `Stock::activos()->sum(…)` y, si el negocio lo requiere, exponer el valor inactivo por separado |
| REP-03 | `ReporteFinancieroController.php:562-572` y `:607-611` | El informe "por operaciones" **no** excluye facturas anuladas (el diario sí lo hace en `:138-139`) | Las anuladas por error o devolución aparecen en el histórico de operaciones | Scope único `Factura::scopeNoAnuladas()` aplicado en todos los reportes |
| REP-04 | `ReporteFinancieroController.php:548-549` vs `:595-597` | La **tabla y el Excel del mismo reporte usan filtros distintos** (`estado='activo'` solo en el export) | Lo que el usuario ve ≠ lo que descarga: la misma consulta, dos verdades | Un único método de construcción de query usado por ambas ramas (patrón `clone` ya usado en el mismo controlador) |
| REP-05 | `ReporteFinancieroController.php:225, 406-411` | "Ingresos", "Egresos", "Facturación Total" y "Utilidad Neta" (flujo de caja) conviven en la misma tarjeta, sin indicar que son magnitudes distintas | El lector interpreta "Ingresos $80M / Facturación $120M" como inconsistencia del sistema, cuando son bases distintas (cartera por cobrar) | Separar visualmente "Flujo de caja" y "Cartera/facturación", indicando el origen de cada cifra |
| REP-06 | `ReporteFinancieroController.php:117-124` vs `:129-149` | El detalle fusiona mantenimientos/electrónicas/facturas/caja **sin filtro de anulación**; los agregados sí filtran | La suma de las filas visibles no cuadra con el total mostrado | Filtrar el detalle o marcar las anuladas con distintivo visual y excluidas del total |
| REP-07 | `ReporteFinancieroController.php:543, 592` · `Factura.php:94` | N+1: `Electronica::with(['tecnico','user'])` omite `equipo.cliente`; `$item->stock->precio_compra` es lazy por ítem | Con cientos de órdenes el reporte se degrada de forma notable | `with(['tecnico','user','equipo.cliente'])` y `with('items.stock')` |
| REP-08 | `ReporteController.php` (bloque de transacciones) · `reportes/index.blade.php:20-30` | El detalle transaccional solo filtra por mes/año si el parámetro viene presente; el acumulado siempre | Un enlace sin `mes` devuelve detalle anual con acumulado mensual | Normalizar parámetros con valores por defecto explícitos al inicio del controlador |
| EXP-03 | `ReportesFinancierosExport.php:151` · `ReporteFinancieroController.php:659, 671` | El export lee `$tx->anulado` en **Factura**, que **no tiene** ese atributo (usa `estado='anulada'`) → `null` → "No anulada" y `total_anulados` no las cuenta | El Excel muestra anuladas como activas; el contador de anulados del reporte por operaciones las ignora | Normalizar en el `map()`; añadir accessor `getAnuladoAttribute()` en `Factura` |
| EXP-04 | `app/Exports/StocksExport.php:47` | El pie del Excel de stock acumula `precio_compra` **unitario** como si fuera total | Con 100 uds de un artículo de $10 totaliza $10 en vez de $1.000 → subestima el inventario × existencias | `SUMPRODUCT(cantidad, precio_compra)` o acumular `cantidad * precio_compra` en PHP; aplicar el mismo criterio de la vista (incluido el filtro de activos) |
| EXP-05 | `app/Exports/AcumuladoExport.php` vs `reportes/index.blade.php:63-80` | El Excel acumulado omite `facturado_total` y `balance_operativo` presentes en la vista | El archivo descargado no permite reproducir el análisis de pantalla | Igualar el conjunto de métricas del export con el de la vista |
| FMT-01 | `AppServiceProvider.php:53-54` + todas las vistas | La directiva `@money` redondea a **0 decimales** (`number_format($x, 0, ',', '.')`) mientras la BD es `decimal(12,2)` | Un total de $1.500,50 se imprime como $1.500/$1.501 → **el total visible ≠ el almacenado**; la suma de las líneas ≠ el total; en POS el total cobrado puede no ser el cobrado | `number_format($x, 2, ',', '.')` en una **única función Blade** reutilizada por HTML, Excel y PDF |
| FMT-02 | `inventario/facturas/print.blade.php:15-16` · `print_pos.blade.php:17-19` | Las plantillas referencian `nit_documento`, `documento` y `telefono`; los modelos exponen `identificacion` y `movil` | **La factura y el ticket POS se imprimen sin NIT ni teléfono** → documento comercial incompleto | Usar los atributos reales; verificar el resto de campos de ambas plantillas contra las migraciones |
| FMT-03 | Todo el sistema | **No existe IVA en ninguna parte** (ninguna tabla, modelo, servicio o vista) | Si el negocio factura con impuesto, todos los importes mostrados y cobrados están subestimados y no hay base para desagregar | Decisión explícita del negocio. Si aplica: módulo centralizado (porcentaje por tipo de producto) con desagregación en factura, POS, órdenes y reportes + migración de precios existentes |
| FMT-04 | `app/Models/Factura.php:190` | `siguienteNumero(string $prefijo = 'F')` — default `'F'` incompatible con los prefijos reales `VT-`/`CP-` | Cualquier llamada sin prefijo genera numeración fuera de esquema | Prefijo obligatorio o alineado con el esquema real |
| EQP-01 | `EquipoController.php:228-234` · migración `2026_04_19_201811_create_mantenimientos_table.php:23` | `destroy()` hace borrado físico; las FKs en CASCADE eliminan órdenes, electrónicas y pivotes **sin reponer stock ni revertir abonos** | Un borrado accidental de un equipo con historial borra órdenes, abonos cobrados y descuadra el inventario. Irrecuperable | Borrado lógico (`active=false` / `estado='dado_de_baja'`, ya existe en `anular()`/`darDeBaja()`) + `restrict`/`set null` en las FKs |
| PRV-01 | `ProveedorController.php:88-90` | El saldo del proveedor **incluye facturas anuladas** (falta `estado != 'anulada'`) | La ficha del proveedor muestra un saldo a pagar mayor al real | Añadir el filtro de estado |
| BKP-01 | `BackupController.php:506-560` | `restore()` **no genera un respaldo previo** al sobrescribir la BD | Si se restaura un archivo viejo o incompleto, la pérdida es total e irreversible | Auto-respaldo del estado actual antes de restaurar; exigir confirmación explícita con nombre y fecha del archivo |
| BKP-02 | `BackupController.php:698-775` (`:773`) | `gitUpdate` aplica `migrate --force` desde la web | Una migración publicada por error se aplica de inmediato en producción, sin revisión ni reversión | Sacar las migraciones del flujo web (ver P0-3) |
| BKP-03 | `BackupController.php:433-441, 494-497` | El fallback `DB::unprepared($sql)` ejecuta **una sola sentencia**: un dump multi-sentencia no restaura | En máquinas sin el cliente `mysql` en el PATH, la restauración falla o restaura solo la primera sentencia, dejando la BD vacía o a medias, sin mensaje claro | Verificar la presencia del binario antes de permitir el restore; o ejecutar por sentencias (parsear y lote dentro de una transacción) |
| USR-03 | `AnulacionService.php:41-56` | `adminPasswordValida()` itera todos los admins con `Hash::check` | El tiempo de respuesta varía con el número de admins (marginal con pocos) | Registrar el admin autorizador; comparar en tiempo constante |
| H-01 | `app/Http/Controllers/EquipoController.php` / `ClienteController.php` etc. | `Route::resource(...)->except(['destroy'])` en clientes/equipos/técnicos/stocks/proveedores, **pero** `EquipoController` sí implementa `destroy` y está registrado como recurso **sin** `except` en algunas rutas | Verificar que ningún `destroy` quede expuesto por rutas resource | Auditar `route:list` de todos los `destroy` (verificado: los de recursos destructivos están en `except`) |

---

## PARTE 6 · Concurrencia y multi-usuario (el bloque que faltaba en la primera pasada)

> Esta sección responde directamente a la pregunta: *"¿qué pasa cuando hago una cotización y otra persona está usando los mismos ítems de inventario?"*
> **Verificado por lectura de código sobre la implementación actual.**

### 6.1 Lo que el sistema SÍ protege

| Mecanismo | Ubicación | Estado |
|---|---|---|
| Salida de stock atómica con re-lectura bajo lock | `app/Services/StockService.php:50-71` (`salida()`) | ✅ Correcto: `DB::transaction` + `lockForUpdate()` + re-lectura + validación + `decrement` |
| Entrada de stock atómica | `StockService.php:27-41` | ✅ |
| Baja de stock atómica | `StockService.php:83-115` | ✅ |
| Bloqueo de la cotización en la conversión | `CotizacionController.php:324` (`lockForUpdate()` sobre la cotización) | ✅ Evita doble conversión |
| Verificación de unicidad de cierre de caja | `cierre_cajas.fecha` UNIQUE | ✅ A nivel de motor |
| Bloqueo de días cerrados | `app/Services/CierreCajaGuard.php` | ✅ Re-verificado dentro de la transacción en inventario |

### 6.2 Lo que el sistema NO protege (huecos reales)

#### 6.2.1 ⛔ No existe reserva de inventario — cero

Búsqueda de `reserv` en `app/`, `database/`, `resources/` → **0 coincidencias**.
No hay tabla, columna, servicio, scope ni concepto de "unidades comprometidas".

- `CotizacionController::store()` (`:41-121`) y `update()` (`:154-235`) **no tocan el stock en absoluto**: no bloquean, no verifican disponibilidad, no dejan rastro.
- La validación solo exige `items.*.item_id => nullable|exists:stocks,id` y `items.*.cantidad => required|integer|min:1`.
- **Consecuencia:** se puede cotizar **999 unidades de un producto con 3 en stock** y el sistema lo acepta sin objeción. El error aflora recién en `convertir()` — **cuando el cliente ya tiene la cotización en la mano**.

#### 6.2.2 ⛔ El selector de artículos muestra stock fantasma

- `CotizacionController::create():36` y `edit():143` cargan `Stock::activos()->where('cantidad','>',0)->orderBy('producto')->get()`
- **No** descuentan lo ya comprometido en cotizaciones abiertas.
- El técnico ve "disponible: 3" cuando esas 3 unidades ya están prometidas en 2 cotizaciones distintas.

#### 6.2.3 ⛔ `tieneDisponible()` es puramente físico

- `app/Models/Stock.php:90-93` → `return $this->cantidad >= $cantidad;`
- Ciego a compromisos ajenos (cotizaciones abiertas, órdenes con repuestos apartados, ventas no facturadas).

#### 6.2.4 ⛔ El escenario real, paso a paso

| Momento | Técnico A | Técnico B | Estado del sistema |
|---|---|---|---|
| 10:00 | Cotiza las últimas **5** uds de "Refrigerante R-134" | — | ✅ Aceptado. El cliente A ya recibió su cotización |
| 10:05 | — | Cotiza las mismas **5** uds | ✅ Aceptado. **Mismo stock, dos compromisos** |
| 10:20 | — | Convierte su cotización a factura | ✅ Descuenta 5 → stock = 0 |
| 10:45 | Convierte su cotización | — | ❌ **"Stock insuficiente. Disponible: 0, requerido: 5"** |

**No hay aviso, ni bloqueo, ni reserva, ni cola de espera, ni registro del conflicto.** El conflicto solo se descubre tarde y en el peor momento: el cliente ya está esperando y la cotización ya fue emitida.

#### 6.2.5 🟠 Deadlock por orden de bloqueo

- `CotizacionController.php:343` — los locks se toman **uno a uno en el orden de los items de la cotización**:
```php
foreach ($cotizacion->items as $item) {
    $stock = Stock::where('id',$item->item_id)->lockForUpdate()->first();
}
```
- Cotización A con stocks `[X, Y]` y cotización B con `[Y, X]` → A bloquea X, B bloquea Y, y cada una espera la fila que la otra tiene → **deadlock**.
- El motor lo resuelve abortando una transacción (error 1213/1205) → 500 al usuario.
- **Corrección:** ordenar los `stock_id` **ascendente** antes de bloquear (`->sortBy('item_id')`).

#### 6.2.6 🟠 Re-lectura sin lock dentro de la conversión (TOCTOU latente)

- `:343` bloquea la fila; `:386` hace `Stock::find($item->item_id)` **sin lock** (nuevo modelo) y luego `:388` llama `decrementarStock()` → `StockService::salida()` → **transacción anidada + segundo `lockForUpdate()`** sobre la misma fila.
- Hoy es correcto (InnoDB devuelve la versión bloqueada dentro de la misma transacción), pero es frágil: si alguien mueve ese `find` fuera de la transacción, aparece una lectura sucia.
- **Corrección:** reutilizar el objeto ya bloqueado (pasar el modelo a `decrementarStock()`), no releer.

#### 6.2.7 🟠 Cierre de caja: snapshot no protegido contra movimientos concurrentes

- `CierreCajaController.php:70-111` — bloquea la fila `CierreCaja` de la fecha (evita cierres duplicados) pero **no** los `movimiento_cajas` del rango.
- Un movimiento de caja registrado en paralelo queda fuera del cierre y **"atascado"** en un día cerrado (no aparecerá en los totales de cierres posteriores, que solo suman por día).
- **Corrección:** `SELECT … FOR UPDATE` sobre los movimientos del rango dentro de la misma transacción, o re-calcular el snapshot y re-verificar el conteo antes del commit.

#### 6.2.8 🟠 Numeración de facturas (ver P1-6)

`lockForUpdate()` sobre un rango vacío no bloquea nada → la primera venta concurrente de `VT-` choca con el índice UNIQUE.

#### 6.2.9 🟠 Las compras/ventas de inventario no bloquean el stock en orden determinista

- `MovimientoInventarioController` fija `lockForUpdate()` sobre stocks uno a uno en el orden de los items → mismo riesgo de deadlock que 6.2.5.
- **Corrección:** ordenar `stock_id` ascendente.

#### 6.2.10 🟠 El técnico puede alterar el stock sin pasar por el motor atómico

`StockController::update()` (`:113-152`) escribe `cantidad` directamente (ver M-5) → **evita** el `lockForUpdate` de `StockService` y puede sobrescribir un descuento concurrente (lectura sucia de la cantidad). Es la vía más probable de corromper existencias con dos usuarios a la vez.

#### 6.2.11 ⚪ `mantenimientos` / `electronicas` — repuestos y `costo`

`HandlesStockAttach.php` **no** usa `lockForUpdate` al sumar repuestos: lee `$model->costo` y hace `increment`. Dos técnicos que añaden repuestos a la misma orden a la vez pueden perder una actualización (last-write-wins en lugar de suma atómica).

### 6.3 Recomendación de diseño: modelo de reserva

Para que una cotización **no interfiera** con otra persona, hay que decidir la política de negocio. Tres opciones (de menor a mayor esfuerzo):

| Opción | Comportamiento | Esfuerzo | Recomendación |
|---|---|---|---|
| **A. Solo alertar** | Al cotizar, mostrar "3 disponibles · 5 comprometidas en cotizaciones abiertas" (sin bloquear). Al convertir, seguir bloqueando como hoy | Bajo (1 vista + 1 consulta) | ✅ **Recomendada como mínimo** |
| **B. Reserva blanda** | Registrar la reserva al cotizar (`stock_reservado` o tabla `reservas_stock`) y(validar `cantidad - reservado >= requerido`) al cotizar; liberar al convertir/anular/rechazar | Medio (migración + modelo + servicio + UI) | ✅ **Recomendada si el taller necesita compromisos firmes** |
| **C. Reserva dura** | además de B: la reserva bloquea la unidad y expira por tiempo (TTL) | Alto (scheduler, expiración, notificaciones) | Solo si hayconflictos frecuentes en operación real |

En cualquiera de las opciones, **el motor de descuento al convertir se mantiene** (`lockForUpdate` + re-lectura), porque es la única forma de garantizar que el stock nunca quede negativo.

### 6.4 Pruebas de concurrencia que deben existir (hoy: 0)

| # | Prueba | Técnica | Resultado esperado hoy |
|---|---|---|---|
| T-01 | **Stock = 1, dos conversiones simultáneas** | 2 conexiones PDO + barrera | Debe ganar **exactamente 1**; el otro recibe "Stock insuficiente"; `cantidad` nunca negativa. **Hoy no está garantizado en la primera venta (P1-6)** |
| T-02 | **Deadlock de orden** | 2 cotizaciones con `[X,Y]` y `[Y,X]` en paralelo | Sin error 1213/1205 tras ordenar `stock_id` |
| T-03 | **Numeración concurrente** | 2 conversiones simultáneas desde 0 facturas `VT-` | Sin `SQLSTATE 23000`; numeración `VT-1`, `VT-2` |
| T-04 | **Cierre de caja concurrente** | 2 `cierre.store` simultáneos para la misma fecha | Exactamente 1 cierre; el otro recibe error de duplicado (el actual test es **secuencial**, no concurrente) |
| T-05 | **Reserva de stock** | 2 cotizaciones sobre el mismo artículo scarce | Con la Opción B: la segunda ve el stock ya comprometido; sin ella: ambas se aceptan (comportamiento actual) |
| T-06 | **Stock directo vs StockService** | `StockController::update` concurrente con una venta | La cantidad final debe ser consistente (hoy la vía directa puede pisar el descuento) |

**Nota metodológica:** el test existente `tests/Feature/ProductionIntegrityTest.php:225` comenta *"Intento concurrente"* pero **es secuencial** (dos POST seguidos). Da falsa sensación de cobertura.

---

## PARTE 7 · Datos, performance e infraestructura

### 7.1 Esquema e integridad

| # | Sev | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|
| F-01 | ALTO | `facturas.fecha` (verificado en `information_schema`) | `DEFAULT '2026-10-16'` **congelado** en la migración (`now()->toDateString()`); 0 filas lo usan hoy, pero cualquier `Factura::create()` futuro sin `fecha` fecha el documento en 2026-10-16 | `ALTER TABLE facturas ALTER COLUMN fecha DROP DEFAULT;` + `fecha` obligatoria en la capa de aplicación (`required|date`). Alternativa: `DEFAULT (CURRENT_DATE)` (MySQL 8.0.13+) |
| F-02 | ALTO | `proveedores` | Columna **huérfana `activo`** convive con `active` (la que usa el modelo `Proveedor.php`); la migración `2026_07_15_070000_standardize_schema_sprint3` omitió el rename "porque `active` ya existía" | `ALTER TABLE proveedores DROP COLUMN activo;` tras verificar que nada la escribe |
| F-03 | ALTO | tabla `configuraciones` | **VACÍA (0 filas)** y se lee con `Configuracion::first()` en ~15 sitios (layout, PDFs, `routes/console.php`); `ConfiguracionController` hace `first() ?? new Configuracion` → puede materializar una fila vacía | Insertar la fila real `id=1`; blindar con `firstOrCreate(['id'=>1])` + `Cache::rememberForever('configuracion_global')`; `UNIQUE(nombre)` |
| F-04 | MEDIA | `cotizacion_items` | 5 grupos con la misma terna `(cotizacion_id, item_id, tipo)`; sin índice UNIQUE | Deduplicar (conservando cantidad) y añadir `UNIQUE (cotizacion_id, item_id, tipo)`; si se permiten renglones repetidos legítimos, agregar columna `linea` |
| F-05 | MEDIA | pivotes y catálogos | Sin UNIQUE en `mantenimiento_stock`, `electronica_stock`, `factura_items`, `configuraciones(nombre)`, `categoria_stocks(nombre,tipo)` | `UNIQUE (mantenimiento_id, stock_id)`, `UNIQUE (electronica_id, stock_id)`, `UNIQUE (factura_id, stock_id)`, `UNIQUE (nombre)`, `UNIQUE (nombre, tipo)` |
| F-06 | MEDIA | 28 de 30 tablas | Sin borrado lógico (solo `facturas` y `proveedores` tienen `deleted_at`) | `softDeletes` en `mantenimientos`, `abonos`, `movimiento_cajas`, `cotizaciones`, `equipos`, `clientes`, `tecnicos`; la bandera `anulado` de negocio se conserva |
| F-08 | MEDIA | FKs | Cascadas **asimétricas**: `facturas.user_id` = RESTRICT pero `movimiento_cajas.user_id` y `cierre_cajas.user_id` = CASCADE; `stocks.proveedor_id` = RESTRICT pero `stocks` se borra en cascada desde otros puntos | Matriz explícita de políticas FK, aplicada uniformemente |
| F-09 | BAJA | `facturas.saldo_pendiente` / `saldo_a_favor` | Columnas generadas `STORED` **nullable** | `NOT NULL` con `DEFAULT 0` tras purgar nulos |

### 7.2 Migraciones

| # | Sev | Hallazgo | Corrección |
|---|---|---|---|
| F-10 | ALTO | `database/migrations/2026_06_12_130509_*.php` ejecuta un **TRUNCATE/DELETE masivo** dentro de `up()` → `php artisan migrate` **borra producción** | Reescribir idempotente y no destructiva; mover la limpieza a un comando explícito `db:reset-demo`; documentar "solo para base vacía" |
| F-11 | ALTO | `2026_10_01_143000_backfill_tecnicos_email` inserta correos de ejemplo; `2026_06_09_154817_create_concepto_cajas_table` inserta catálogos seed **dentro de la migración** y usa `DB::table(...)` **sin importar `Illuminate\Support\Facades\DB`** (funciona por el alias de clase global: fragilidad latente) | Mover los catálogos a seeders; añadir el `use` explícito |
| F-12 | MEDIA | `2026_10_01_133607` (backfill) recorre facturas y hace un `UPDATE … WHERE <LIKE> AND factura_id IS NULL` **por cada una** → O(n²), no re-ejecutable | Un solo `UPDATE … JOIN` / `CASE` con condición de guarda en una pasada |
| F-13 | MEDIA | `2026_06_16_133122_add_electronica_to_abonos…` en `down()` altera `mantenimiento_id` a `NOT NULL`; hay migraciones de datos no reversibles | `down()` no destructivos (`nullable()` en vez de `NOT NULL`); marcar explícitamente las irreversibles |
| F-14 | BAJA | — | Las 56 migraciones están en estado `Ran`, sin divergencia de batches ✅ |

### 7.3 Rendimiento de consultas

| # | Sev | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|
| F-19 | ALTO | `ReporteFinancieroController.php:358-359`, `:366-380`, `:187`, `:335`, `:129-149`, `:156-159` | Carga **todos** los mantenimientos y abonos en memoria para sumar en PHP; ~15 `count()` secuenciales; N+1 resolviendo la factura dentro de bucles | Mover toda la agregación a SQL (`SUM`/`GROUP BY` sobre `whereBetween`); `withSum()`/`withCount()`; precalcular con consultas agrupadas |
| F-20 | MEDIA | `DashboardController.php:76-113, 144` · `MovimientoCajaController.php:74` | `selectRaw`/`DB::selectOne` y `whereIn(DB::raw("TRIM(CONCAT(nombres,' ',COALESCE(apellidos,'')))"), $nombres)` → expresión sobre la columna, índice no aplicable | Unir por `cliente_id`; índice generado (`GENERATED ALWAYS AS (...) STORED`) o filtrar por columnas reales |
| F-21 | BAJA | `movimiento_cajas.descripcion` | `LIKE '%…%'` → `type=ALL` (inevitable sin fulltext). Hoy 259 filas | Anotar como limitación conocida; `FULLTEXT` si el volumen crece |
| F-24 | MEDIA | `app/Models/CierreCaja.php` | `whereDate('fecha')` + `lockForUpdate()` → `type=ALL` con **25 filas bloqueadas** bajo REPEATABLE-READ → gap locks y riesgo de deadlock entre dos cierres simultáneos | `where('fecha',$d)` → `type=const`, 1 fila, con `SELECT … FOR UPDATE` |
| F-25 | MEDIA | `CotizacionController.php:343` · `MovimientoInventarioController` | Locks de stock **uno a uno en el orden de los items** → deadlock si dos transacciones toman los mismos stocks en orden inverso | Ordenar `stock_id` ascendente antes de bloquear |

### 7.4 Índices — verificados en `information_schema.STATISTICS`

**F-16 (ALTO) · `eventos` sin índice temporal:**
```
EXPLAIN SELECT * FROM eventos ORDER BY created_at DESC LIMIT 30
  → type=ALL, key=NULL, Using filesort, rows=1971
```
La tabla tiene **2.020 filas**, de las cuales **1.982 se crearon el 2026-10-16** (carga masiva de prueba). El índice actual es solo `(modelo_tipo, modelo_id)` y `(accion)`. El panel de auditoría degrada linealmente y la tabla crece sin política de retención (una escritura por cada `created`/`updated` vía `Auditable`).

**F-17 (ALTO) · 7 índices redundantes (amplifican la escritura sin beneficio):**

| Índice a eliminar | Motivo |
|---|---|
| `movimiento_cajas.idx_movcaja_tipo` | prefijo exacto de `idx_movcaja_tipo_fecha` |
| `abonos.idx_abonos_mantenimiento` | prefijo de `idx_abonos_mant_fecha` |
| `abonos.idx_abonos_electronica` | prefijo de `idx_abonos_elec_fecha` |
| `mantenimientos.idx_mant_fecha_entrada` | prefijo de `idx_mant_fecha_anulado` |
| `cotizaciones.cotizaciones_estado_index` | prefijo de `cotizaciones_estado_user_id_index` |
| `cierre_cajas.idx_cierrecaja_fecha` | prefijo de `cierre_cajas_fecha_unique` (además duplica un UNIQUE) |
| `facturas.idx_facturas_estado_tipo` / `mantenimientos.idx_mant_estado_anulado` / `electronicas.idx_elec_estado_anulado` | pares inversos: mismo conjunto de columnas en orden invertido |

**F-18 (MEDIO) · Índices faltantes:**
- `cotizaciones.fecha` → `EXPLAIN` da `type=ALL, rows=100`
- `facturas(estado, saldo_pendiente)` → el `WHERE estado <> 'anulada'` **no es sargable** y hace descartar `idx_facturas_saldo_pendiente`; reescribir como `estado IN ('emitida','pendiente_pago')`
- Búsqueda textual: `stocks.producto` y `clientes.nombres/apellidos` se buscan con `LIKE '%…%'` → requieren `FULLTEXT`

### 7.5 Caché, colas, sesión, scheduler y backups

| # | Sev | Hallazgo | Corrección |
|---|---|---|---|
| F-40 | ALTO | `php artisan about` → **Config NOT CACHED, Routes NOT CACHED, Events NOT CACHED** (Views sí) | `php artisan config:cache && route:cache && view:cache && event:cache` en el deploy (ya está en `DEPLOY_CHECKLIST.md:93-102`, simplemente no se ha ejecutado) |
| F-41 | ALTO | `QUEUE_CONNECTION=database` sin `queue:work` en el schedule; no verificable el Programador de Tareas de Windows → jobs encolados nunca se procesan | Registrar `schedule:run` (cada minuto) y `queue:work` en Tareas de Windows; verificar que `jobs` no acumula filas |
| F-42 | MEDIA | `GOOGLE_DRIVE_BACKUP_PATH` ausente → **backups solo locales, en el mismo volumen que los datos** | Configurar destino off-site (Drive/NAS/S3) y **verificar la ejecución real** de `app:backup-db` una vez |
| F-43 | MEDIA | `routes/console.php` condiciona el schedule de backup a `Configuracion::first()` (hoy vacío → entra el fallback diario) | Que la condición sea "backup activo" y no "existe configuración"; verificar el `.sql` generado |
| F-44 | BAJA | `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_PREFIX` comentado | `config/cache.php` ya aplica prefijo derivado de `APP_NAME` → sin colisión real al migrar a Redis. `SESSION_LIFETIME=180` y `SESSION_ENCRYPT=true` ✅ |
| F-45 | BAJA | `ConfiguracionController` limpia la clave de caché `empresa_global_data` que **nunca se escribe** → cada render re-consulta la BD | `Cache::rememberForever('configuracion_global', fn() => Configuracion::first())` e invalidarlo en el update |

### 7.6 Configuración, entorno y despliegue

| # | Sev | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|
| F-31 | ALTO | `.env:2, 4, 23-26` | `APP_ENV=local` (habilita Dusk → P0-1), `LOG_STACK=single` (un archivo sin rotación), `LOG_LEVEL=debug` | `APP_ENV=production`, `LOG_STACK=daily`, `LOG_LEVEL=warning` |
| F-32 | ALTO | `.env:32-33` | `DB_USERNAME=root` / `DB_PASSWORD=ServBay.dev` (contraseña de distro por defecto de ServBay) + versionada en `phpunit-integration.xml` | Ver P0-5 |
| F-33 | MEDIA | `.env` vs `.env.example` | Solo en `.env`: `APP_TIMEZONE`, `ADMIN_REGISTRATION_PASSWORD`, `LOG_STACK`. Solo en `.env.example`: `ROLE_PROMOTE_ADMIN_SECRET`, `ROLE_PROMOTE_TECNICO_SECRET`, `LOG_DAILY_MAX_FILES`, `MYSQLDUMP_PATH`, `GOOGLE_DRIVE_BACKUP_PATH`, `BACKUP_RETENTION_DAYS`. **Además `config/logging.php:72` lee `LOG_DAILY_DAYS` (default 14) pero el ejemplo documenta `LOG_DAILY_MAX_FILES`** → la rotación usaría el default silenciosamente | Sincronizar ambos archivos; renombrar la variable a `LOG_DAILY_DAYS`; eliminar del ejemplo las variables no usadas |
| F-34 | MEDIA | `.env:40` | Cookie de sesión sin `Secure` en URL HTTPS | Ver A-6 |
| F-35 | MEDIA | `.env:58-66` | `MAIL_MAILER=log` + `MAIL_FROM_ADDRESS="hello@example.com"` → los correos **nunca llegan** (se escriben al log) | Configurar proveedor real y remitente del dominio del cliente |
| F-36 | BAJA | `config/app.php` vs `.env:5` | `config/app.php` fija el timezone estático; con `config:cache` el `.env` se ignora | `'timezone' => env('APP_TIMEZONE','America/Bogota')` |
| F-37 | BAJA | `php.ini` | `memory_limit=128M` (ajustado para el reporte financiero y los PDFs), `max_execution_time=0`, `date.timezone=UTC`, `max_input_vars=1000` | `memory_limit=256M`, `max_execution_time=120`, `date.timezone` coherente con `America/Bogota` |
| F-38 | BAJA | Extensiones PHP | `zip`, `mbstring`, `pdo_mysql`, `gd`, `imagick`, `exif`, `bcmath`, `intl`, `fileinfo`, `curl`, `dom`, `openssl` — todas presentes ✅ | Sin acción |
| F-39 | BAJA | `config/cors.php` y `config/sanctum.php` | No existen en el repo | Publicar `config/cors.php` con `allowed_origins => []` explícito (ver I-2) |
| F-46 | ALTO | `SecurityHeaders.php:29-31` | HSTS en HTTP, CSP con `unsafe-eval`, `X-XSS-Protection` obsoleto | Ver B-15/B-16/B-17 y A-4 |
| F-47 | ALTO | `public/build/manifest.json` | Desfasado respecto a los `.blade.php` | Ver P0-6 |
| F-48 | MEDIA | `AppServiceProvider.php:35-37` | `DuskServiceProvider` registrado en `local`/`testing` | Ver P0-1 |
| F-49 | MEDIA | `DEPLOY_CHECKLIST.md` | Describe **Linux** (`/var/www/tecni-systemas`, `sudo chmod -R 775`, crontab) pero el entorno es **Windows/ServBay** → inaplicable | Reescribir con Programador de Tareas de Windows, ACL, `schedule:run`/`queue:work` |
| F-50 | MEDIA | raíz del proyecto | **`producto`** (82 bytes) versionado en Git con un error de parseo PHP de un `php -r` fallido | `git rm --cached producto` + borrar |
| F-51 | MEDIA | `phpunit-integration.xml` | Credencial de BD en claro versionada | Ver P0-5 |
| F-52 | BAJA | `config/database.php` / servidor | `DB_USERNAME=root`; `max_connections=9999` en el MySQL (con `innodb_buffer_pool_size=4.4 GB` es excesivo) | Usuario dedicado; `max_connections` 150-300 |
| F-53 | BAJA | `BackupController.php:433-460` | `DB::unprepared($sql)` → sink de ejecución de SQL arbitrario | Restringir (solo dumps de este esquema), auditar y exigir step-up |
| F-27 | ALTO | `database/seeders/DatabaseSeeder.php` | Invoca `AdminUserSeeder` + `TestDataSeeder` (100 clientes/equipos/facturas + **2.020 eventos**) + `SeedDemoData`; y `DOCUMENTACION_1_GUIA_INSTALACION_SERVBAY.md` sugiere `migrate:fresh --seed` → **producción con datos ficticios** | Separar `DatabaseSeeder` (base) de `DemoDataSeeder`/`TestDataSeeder`; en producción ejecutar solo `--class=DatabaseSeeder`; flag `--force` para demo |
| F-23 | ALTA | `database/seeders/TestDataSeeder.php:789` | El seed genera `'F-' . str_pad(...)` mientras la app genera `VT-`/`CP-` → el corpus de pruebas **no ejercita las secuencias reales** → P1-6 no se manifiesta en QA | Unificar el formato en un único servicio de numeración y usarlo también en el seeder |
| F-28 | MEDIA | `phpunit-integration.xml:23-28` | Credenciales reales versionadas | Ver P0-5 |
| F-29 | MEDIA | `AdminUserSeeder.php` | Credenciales de administración por defecto; `ADMIN_REGISTRATION_PASSWORD` existe en `.env` pero no en `.env.example` | Forzar cambio en el primer login; documentar la variable en `.env.example` |
| F-30 | BAJA | `database/database.sqlite` (94 KB) | Archivo residual (el proyecto usa MySQL; `phpunit.xml` usa `:memory:`) | Eliminar del árbol de trabajo |

---

## PARTE 8 · Frontend, UX, estética, accesibilidad

**Puntuación 78/100.** El diseño visual (sistema "Liquid Glass") es coherente y atractivo; las debilidades son de **mantenibilidad** y **accesibilidad**, no de gusto.

### 8.1 Estructura y organización

| # | Sev | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|
| F-01 | ALTO | `resources/views/layouts/app.blade.php:1-1826` | **Layout monolítico de 1826 líneas**: lógica PHP (`@php` con consultas/Cache), modales globales, toasts, y bloques JS extensos (flatpickr, tema, notificaciones, modales) | Extraer: logo/empresa a un View Composer; modales a `layouts/partials/modals/*`; JS a `resources/js/layout/` vía Vite |
| F-02 | ALTO | `configuracion/index.blade.php` (91 KB) · `dashboard.blade.php` (46 KB) · `eventos/index.blade.php` (45 KB) · `cierre/index.blade.php` (39 KB) · `guest/dashboard.blade.php` (34 KB) · `stocks/show.blade.php` (29 KB) · `inventario/facturas/edit.blade.php` (35 KB) | Vistas excesivamente grandes con HTML + CSS inline + JS inline | Componentes Blade (`<x-…>`) + partials; JS a módulos Vite; CSS a clases/utilidades |
| F-03 | MEDIO | `layouts/app.blade.php:2-24` | Lógica de negocio/infra en la vista: `Configuracion::first()`, Storage y Cache accedidos directamente | View Composer (`ViewServiceProvider`) o `View::share()` |
| F-04 | MEDIO | `resources/views/components/` (4 archivos) · `partials/` (vacío) | PocaABSTRACT slider de componentes; se repiten tarjetas, modales y botones | Sistema de componentes (`<x-card>`, `<x-modal>`, `<x-table>`, `<x-button>`) |
| F-05 | BAJO | Varias vistas | `@php` inline con cálculos y selecciones | Pasar los datos preparados desde el controlador |

### 8.2 Consistencia de diseño

| # | Sev | Hallazgo | Corrección |
|---|---|---|---|
| F-06 | ALTO | **Doble sistema de estilos**: Tailwind (build Vite) + `public/css/glass.css` **305 KB** servido estático, con muchos `!important` | Tokens en `tailwind.config.js`; purgar `glass.css`; migrar a `@layer components`; CSS único |
| F-07 | MEDIO | `tailwind.config.js:10-26` — paleta limitada (`ts.blue`, `ts.bluedark`, `ts.cyan`, `ts.slate`) mientras `glass.css` usa muchas variables CSS hardcodeadas | Sistema unificado de design tokens (`theme()` + CSS vars) |
| F-08 | MEDIO | `layouts/app.blade.php:30-38` — Flatpickr y TomSelect por CDN con versión fija (`tom-select@2.3.1`) | npm + Vite con versión fijada en `package.json` |
| F-09 | BAJO | `glass.css` — dark mode con `.dark` + variables CSS, funciona pero extenso y con `!important` frecuente | Reducir `!important`, usar `@layer` y especificidad controlada |

### 8.3 UX / intuitividad

| # | Sev | Hallazgo | Corrección |
|---|---|---|---|
| F-10 | ALTO | `layouts/app.blade.php:523-630` — modales globales (confirmación, anulación con contraseña de admin, rechazar cotización) con lógica compleja | Patrón accesible único reutilizable (ver F-12) |
| F-11 | MEDIO | `clientes/_form.blade.php:220-280` · `stocks/_form.blade.php:250-350` — validación casi exclusivamente server-side; `oninput` restrictivo en algunos campos | Atributos HTML5 (`pattern`, `minlength`), mensajes de error consistentes, validación cliente no intrusiva |
| F-12 | MEDIO | Listados — sin patrón de estado vacío ni skeletons de carga; `fetch` sin `try/catch` ni feedback de error de red | Componente `<x-empty-state>`, `<x-skeleton>`, botones con `disabled` + spinner en acciones async |
| F-13 | MEDIO | Filtros/búsquedas sin evidencia de `aria-label`, `aria-live` ni estado anunciado | Estandarizar el patrón de búsqueda en todos los listados |
| F-14 | BAJO | Paginación y filtros presentes ✅ | Verificar accesibilidad de la paginación |

### 8.4 Accesibilidad

| # | Sev | Hallazgo | Corrección |
|---|---|---|---|
| F-15 | ALTO | `layouts/app.blade.php:400-520` — modales sin `role="dialog"`, `aria-modal`, `aria-labelledby`, sin focus trap, sin `Esc`, sin restaurar foco | Patrón único: foco al abrir, trap, `Esc` cierra, `role="dialog"`, `aria-modal="true"` |
| F-16 | ALTO | `usuarios/_form.blade.php:115, 133` — `{!! !!}` en labels (HTML estático, riesgo bajo) | Evitar `{!! !!}`; usar condicionales Blade |
| F-17 | MEDIO | `configuracion/index.blade.php:28, 61` — `onclick` en elementos no semánticos (`div`) | `<button>`/`<label for>`, `tabindex`, `role`, `onkeydown` |
| F-18 | MEDIO | Iconos/emoji como único indicador en botones (frecuente) | `aria-label` o texto `.sr-only` |
| F-19 | MEDIO | Sidebar `layouts/app.blade.php:154-200` | `aria-expanded` en móvil, skip links, foco visible |
| F-20 | MEDIO | Glassmorphism puede reducir contraste | Auditar contraste WCAG AA en textos sobre fondos glass/dark |
| F-21 | BAJO | `alt` presente en algunos sitios (logo) | Revisar sistemáticamente todas las imágenes |

### 8.5 JavaScript

| # | Sev | Hallazgo | Corrección |
|---|---|---|---|
| F-22 | ALTO | JS embebido en ~10 vistas + `resources/js/app.js` con solo 148 líneas (password toggles) | Migrar a módulos ES6 en `resources/js/` importados por Vite |
| F-23 | ALTO | **153 `onclick` inline** + 166 `addEventListener` + 20 `fetch` en Blade — mezcla de estilos | `addEventListener` + delegación de eventos + `data-*` (habilita CSP estricta) |
| F-24 | ALTO | `configuracion/index.blade.php:295, 303, 345` — `fetch` sin manejo de errores robusto y `innerHTML` con strings construidos | `try/catch`, validar respuesta, `textContent`/`createElement` en vez de `innerHTML` |
| F-25 | MEDIO | CDNs sin `integrity`/`crossorigin` | SRI o autoalojamiento (ver A-4) |
| F-26 | BAJO | `console.log` residuales: **0** detectados ✅ | — |
| F-27 | BAJO | Password toggles con `MutationObserver` bien implementados ✅ | — |

### 8.6 Rendimiento frontend

| # | Sev | Hallazgo | Corrección |
|---|---|---|---|
| F-28 | ALTO | `public/css/glass.css` (305 KB) servido estático + scripts CDN síncronos en `<head>` (bloqueo de render / FOUC) | Integrar `glass.css` al build de Vite; `defer`; autoalojar CDNs |
| F-29 | MEDIO | Google Fonts (Inter, Michroma, Orbitron) desde CDN en `app.blade.php:45` y `glass.css:7` | Autoalojar o `preconnect` + `font-display: swap` |
| F-30 | MEDIO | Imágenes de producto sin `loading="lazy"` ni dimensiones explícitas | Formatos modernos, lazy loading, `width`/`height` |
| F-31 | BAJO | Listados largos sin virtualización (hay paginación server-side ✅) | Virtualización solo si el volumen lo exige |

### 8.7 Seguridad frontend

| # | Sev | Hallazgo | Corrección |
|---|---|---|---|
| F-32 | ALTO | 9 usos de `{!! !!}`: `caja/print.blade.php:103`, `cotizaciones/pdf.blade.php:65`, `reportes_financieros/operaciones.blade.php:113, 162, 211, 261`, `usuarios/_form.blade.php:115, 133` | Preferir `{{ }}`; si hay HTML, purificar. Revisar una por una |
| F-33 | MEDIO | `configuracion/index.blade.php:303` — `fetch` POST: verificar envío de `X-CSRF-TOKEN` | Estandarizar un helper `apiFetch()` con el token del `<meta name="csrf-token">` |
| F-34 | BAJO | `target="_blank"` no detectado ✅ | Si se usa, añadir `rel="noopener noreferrer"` |

### 8.8 Sintonía frontend ↔ backend (verificada)

- **248 usos de `route()` en Blade vs 161 rutas registradas — sin typos detectados.** ✅ La nomenclatura de rutas está en sintonía con el backend.
- Los formularios `_form` de cliente/proveedor/técnico/usuario/stock/mantenimiento/electrónica siguen patrones similares; conviene unificar en componentes.
- `stocks/_form.blade.php:50-54` **expone el campo `cantidad`** → coherente con el backend, pero ambos deben eliminarlo (ver M-5 / STK-02).
- `clientes/_form.blade.php:270` usa `route('api.municipios')` → existe ✅

### 8.9 Inventario de archivos innecesarios / duplicados (frontend)

| Ruta | Tipo | Motivo | Acción |
|---|---|---|---|
| `public/css/glass.css` (305 KB) | CSS duplicado de Tailwind | Coexistencia de 2 sistemas; no purgado | Integrar al build y purgar |
| `tests/Browser/screenshots/tomselect_native_tooltip_test.png` | Artefacto de prueba | Screenshot de Dusk versionado | Ignorar / eliminar |
| `public/test-tomselect.html` | — | **Ya no existe** (verificado) | Sin acción |

---

## PARTE 9 · Calidad de código, arquitectura y mantenibilidad

| # | Sev | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|
| Q-01 | ALTO | `MovimientoInventarioController.php` (45 KB) · `ReporteFinancieroController.php` (36 KB) · `BackupController.php` (32 KB) · `AuthController.php` (12 KB) | Controllers gigantes con lógica de negocio | Extraer a Services; un `ReporteFinancieroService` único (HTML+Excel+PDF) |
| Q-02 | ALTO | `MantenimientoController.php` vs `ElectronicaController.php` | **Duplicación de la lógica de anulación** (bloques de 30-50 líneas) | `AnulacionService` ya existe: unificar ambas rutas en él |
| Q-03 | MEDIO | `app/Exports/` (5 archivos, 4.7-6.5 KB) | Exports casi idénticos | `ExportBase` abstracto + traits por tipo |
| Q-04 | MEDIO | `ClienteController` · `ProveedorController` · `TecnicoController` · `UserController` | CRUD repetido | Controlador base genérico o traits por recurso |
| Q-05 | MEDIO | 19 archivos | Violaciones de Pint: `fully_qualified_strict_types`, `ordered_imports`, `concat_space`, `single_blank_line_at_eof`; `declare(strict_types=1)` ausente en casi todo | `vendor\bin\pint` + `declare(strict_types=1)` |
| Q-06 | MEDIO | `layouts/app.blade.php:2-24` y varias vistas | Lógica de negocio dentro de Blade | View Composers |
| Q-07 | MEDIO | `AppServiceProvider.php` (≈150+ líneas) | Provider grande con Too Many Responsibilities (notificaciones, numeración, forceScheme, Gates, config Blade) | Extraer a clases/servicios dedicados |
| Q-08 | MEDIO | `app/Traits/` (3 traits) | Traits subutilizados: `Auditable` bien usado; `HandlesAbono` y `HandlesStockAttach` mezclan cálculo de negocio con persistencia | Separar cálculo (servicio) de persistencia (trait) |
| Q-09 | MEDIO | `composer.json` | Sin PHPStan/Psalm/Larastan; sin `.github/workflows`; sin hooks de git; scripts mínimos | CI con `pint --test` + `phpstan` + `php artisan test`; `composer audit` y `npm audit` |
| Q-10 | MEDIO | Nomenclatura | Mezcla español/inglés (modelos y métodos en español; `handlers`, `scopes`, traits en inglés) | Convenciones documentadas en `CONTRIBUTING.md` |
| Q-11 | BAJO | Tipado | Variables sin tipar en controllers; typed properties parciales | Tipado gradual |
| Q-12 | BAJO | `composer.json:63` | `test:e2e` referencia `phpunit.dusk.xml` (existe ✅) pero `composer test` no usa `--parallel` → el error de **Collision 8.x requiere ParaTest 7.x** que aparece en los logs | `composer require brianium/paratest:^7.0 --dev` o `--no-parallel` explícito |
| Q-13 | BAJO | `app/Console/Commands/*` | 8 comandos; algunos destructivos sin guarda (ver B-11) | Guardas de entorno + descripción con ejemplos |
| Q-14 | BAJO | `app/Helpers/ColombiaHelper.php` (17.9 KB) | Verificar usos reales (el test unitario existe: `ColombiaHelperTest` con 5 tests ✅) | Confirmar que todo el helper se usa |
| Q-15 | BAJO | `app/Models/ConceptoCaja.php` (308 B) | Verificar uso | Unificar o eliminar |

---

## PARTE 10 · Tests — cobertura real y plan de la nueva suite

### 10.1 Inventario (58 archivos)

| Suite | Archivos | Tests | Estado |
|---|---|---|---|
| `tests/Unit` | 7 | 63 | 🟢 Buena cobertura de `StockService`, `AnulacionService`, `CierreCajaGuard`, `OrdenService`, `PosTicketService`, `Stock`, `ColombiaHelper` |
| `tests/Feature` | 43 | ~430 | 🟡 Buena en controllers; **0** en servicios de cálculo |
| `tests/Integration` | 1 | 11 | ⚪ **No ejecutado** en esta auditoría (exige MySQL `tecni_systemas_test`) |
| `tests/Browser` (Dusk) | 8 + 2 Pages | ~12 | ⚪ **No ejecutados** (requieren ChromeDriver) |

**Resultado verificado:** `501 tests, 1507 assertions, OK (43 s)` — **solo Unit + Feature**.

### 10.2 La verdad sobre la "suite verde"

| # | Hecho verificado | Consecuencia |
|---|---|---|
| 1 | **No se midió cobertura de código** (no hay Xdebug/pcov, no se corrió `--coverage`) | Los "% de cobertura" de esta auditoría son estimaciones cualitativas |
| 2 | **Los ~15 bugs críticos/altos reportados NO están cubiertos por ningún test** | ⚠️ **La suite verde NO significa que el sistema sea correcto**; significa que nadie escribió el test que detectaría esos bugs. Esta es la conclusión más importante de la auditoría de tests |
| 3 | `ReporteFinancieroControllerTest` tiene **4 tests** y 3 solo comprueban que la vista renderiza (status 200) — **ninguno verifica una cifra** | P1-2 (balance duplicado) y P1-3 (utilidad = flujo) son invisibles para la suite |
| 4 | `ProductionIntegrityTest.php:225` comenta *"Intento concurrente"* pero **es secuencial** (dos POST seguidos) | Falsa sensación de cobertura de concurrencia |
| 5 | **Cero tests de concurrencia real** (procesos paralelos, dos conexiones PDO, barreras) | Toda la Parte 6 queda sin verificación automática |
| 6 | `CierreCajaConciliacionTest` (7 tests) verifica el **diff** pero **no el saldo acumulado** | P1-1 (flujo del día vs conteo físico) no lo detecta |
| 7 | `CotizacionLifecycleTest` (5) + `CotizacionProveedorTest` (8) no cubren stock, reserva ni concurrencia | El módulo de cotización es el de menor cobertura relativa |
| 8 | No existe mapa **regla de negocio → test que la cubre** | No hay trazabilidad entre requisitos y cobertura |
| 9 | `tests/Integration` y `tests/Browser` no se ejecutaron | El estado real de la suite completa es **desconocido** |

### 10.3 Cobertura por módulo (valorada)

| Módulo | Tests | Cobertura real | Hueco principal |
|---|---|---|---|
| Stock atómico | `StockServiceTest` (17), `StockModelTest` (12) | 🟢 | Sin concurrencia real; sin reserva |
| Facturas / inventario | `AuditFindingsTest` (36), `MovimientoInventarioControllerTest` (31) | 🟢 | Sin ítems cruzados entre facturas (P1-4); sin costo histórico (FAC-03) |
| Órdenes | `MantenimientoControllerTest` (29), `ElectronicaControllerTest` (26), `MantenimientoStockControllerTest` (13), `ElectronicaStockControllerTest` (12) | 🟢 | Sin costo de repuestos (P1-12); sin step-up (A-5) |
| Caja | `MovimientoCajaControllerTest` (22) | 🟡 | Sin casts (CAJA-04); sin consignación (CAJA-05) |
| Cierre de caja | `CierreCajaConciliacionTest` (7), `CierreCajaBloqueoTest` (7) | 🟡 | **No cubre el saldo acumulado (P1-1)** |
| Cotizaciones | 13 tests | 🔴 | Sin stock, sin reserva, sin concurrencia, sin proveedor→compra (P1-5) |
| Reportes financieros | **4 tests** | 🔴 | **Ninguna cifra verificada** |
| Backups | `BackupManagementTest` (21), `BackupEventTest` (5) | 🟡 | Sin Zip Slip, sin restore, sin `git-update` |
| Autorización / roles | `AnuladoSecurityTest` (11), `GuestDashboardTest` (17), `LoginLockoutTest` (7), `LoginFlexibilityTest` (10) | 🟡 | Falta la matriz completa rol × recurso |
| Cálculo de totales / IVA / reportes | — | 🔴 | **0 tests** (y el IVA no existe, FMT-03) |
| Concurrencia | — | 🔴 | **0 tests** |

### 10.4 Plan de la nueva suite (15 archivos nuevos, cada uno **falla hoy**)

| # | Archivo nuevo | Qué debe verificar | Bug que detecta |
|---|---|---|---|
| T-01 | `tests/Feature/Concurrency/StockConcurrentSaleTest.php` | 2 conexiones PDO, stock=1, 2 conversiones simultáneas → 1 éxito, 0 stock negativo | P1-6 |
| T-02 | `tests/Feature/Concurrency/StockDeadlockOrderTest.php` | 2 cotizaciones con stocks en orden inverso → sin 1213/1205 | 6.2.5 |
| T-03 | `tests/Feature/Concurrency/CotizacionReservaTest.php` | La cotización informa del stock ya comprometido | 6.2.1-6.2.4 |
| T-04 | `tests/Feature/Concurrency/CierreConcurrenteTest.php` | 2 cierres simultáneos → exactamente 1 | CAJA-02 |
| T-05 | `tests/Unit/ReporteFinancieroServiceTest.php` | Una sola fórmula de balance para HTML/Excel/PDF; sin doble conteo | P1-2 |
| T-06 | `tests/Feature/ReporteBalanceCoherenciaTest.php` | El balance del Excel y del PDF **coinciden** con el HTML del mismo día | P1-2, EXP-03 |
| T-07 | `tests/Feature/UtilidadNetaTest.php` | "Utilidad Neta" ≠ flujo de caja; la utilidad real cuadra con el acumulado | P1-3 |
| T-08 | `tests/Feature/CierreSaldoAcumuladoTest.php` | El efectivo esperado = saldo anterior + flujo del día | P1-1 |
| T-09 | `tests/Feature/FacturaItemAislamientoTest.php` | Editar una factura **no** puede tocar ítems ni stock de otra | P1-4 |
| T-10 | `tests/Feature/CotizacionProveedorCompraTest.php` | Cotización a proveedor → factura de **compra**, stock suma, caja ingressa | P1-5 |
| T-11 | `tests/Feature/ReferenciaFacturaUnicaTest.php` | `#VT-1` no captura `#VT-10`; saldos por `factura_id` | P1-9 |
| T-12 | `tests/Feature/CostoRepuestosTest.php` | Agregar/quitar repuestos no deja costo residual | P1-12 |
| T-13 | `tests/Feature/BackupZipSlipTest.php` | Un ZIP con `../` o `.php` es rechazado; no se escribe en el webroot | P0-2 |
| T-14 | `tests/Feature/ExcelFormulaInjectionTest.php` | Los valores con `=`/`+`/`-`/`@` se neutralizan en los 5 exports | M-1 |
| T-15 | `tests/Feature/DuskRoutesDisabledTest.php` | `/_dusk/*` no está registrado en `production` | P0-1 |

**Meta de cobertura:** ≥ 80 % de líneas en `app/Services`, `app/Models` y los 5 Exports; 100 % de las rutas con `role:` middleware cubiertas por un test de autorización; 0 tests de concurrencia pendientes.

---

## PARTE 11 · Documentación

| Hallazgo | Detalle | Corrección |
|---|---|---|
| **README sin instalación real** | 8 KB; sin pasos verificables, sin troubleshooting de ServBay, sin credenciales de ejemplo seguras | Reescribir: requisitos, instalación, configuración, BD, seeding, deploy, troubleshooting, estructura del proyecto |
| **`DEPLOY_CHECKLIST.md` para otra plataforma** | Usa `/var/www`, `sudo chmod -R 775`, crontab; el entorno es **Windows/ServBay** → inaplicable. Los puntos críticos (`migrate --force` sin `fresh`, `config:cache`, `route:cache`, `storage:link`) quedan sin aplicar | Reescribir con Programador de Tareas de Windows, ACL, `schedule:run`/`queue:work`, y **añadir el paso de `npm run build` y la verificación del manifest** |
| **`SECURITY_DEBT.md` desactualizado** | Documenta la deuda de CSP/CDN (ya consciente ✅) pero sin fecha, responsable ni criterio de aceptación | Convertirla en issue con criterio de aceptación y fecha objetivo |
| **4 archivos `DOCUMENTACION_*.md` solapados** | `DOCUMENTACION_2_MANUAL_ARQUITECTURA_Y_NEGOCIO.md`, `_3_PRESENTACION_GRADO_Y_EMPRESA.md`, `_4_ACTA_ENTREGA_TERMINOS_LEGALES.md`, `DOCUMENTACION_PROYECTO_NOTEBOOKLM.md` — duplican el README y contienen info desactualizada (credenciales, rutas) | Consolidar en `README.md` + `docs/`; los de grado/empresa/entrega son de otra naturaleza (no son documentación técnica) → mover a `docs/institucional/` |
| **Falta: modelo de datos** | No hay diagrama ER ni diccionario de datos (30 tablas, FKs, columnas generadas, ENUMs) | `docs/modelo-de-datos.md` + diagrama mermaid/DBML |
| **Falta: glosario de negocio** | "Utilidad Neta", "saldo pendiente", "abono", "cierre", "consignación" sin definir — origen directo de P1-3 | `docs/glosario.md` |
| **Falta: manual de usuario** | Sin guía por rol (admin / técnico / invitado) | `docs/manual/` con captura por flujo |
| **Falta: arquitectura** | Sin diagrama de capas ni del flujo caja→factura→abono→cierre | `docs/arquitectura.md` |
| **Falta: runbook operativo** | Qué hacer si falla el backup, si se corrompe la BD, si se bloquea una IP, cómo restaurar | `docs/runbook.md` |
| **Config muerta en `.env.example`** | `ROLE_PROMOTE_ADMIN_SECRET` / `ROLE_PROMOTE_TECNICO_SECRET` no existen en el código → prometen un control inexistente | Eliminar o implementar |
| **Sin `CONTRIBUTING.md`** | Convenciones de nombres, estilo, flujo de PR, cómo correr los tests | Crear |

---

## PARTE 12 · Inventario consolidado de archivos a eliminar / consolidar

| Archivo / ruta | Tipo | Motivo | Acción | Prioridad |
|---|---|---|---|---|
| `producto` (82 B) | Basura versionada | Error de parseo PHP de un `php -r` fallido | `git rm --cached producto` + borrar | 🔴 P0 |
| `phpunit-integration.xml` (credencial) | Secreto versionado | `DB_PASSWORD` de `root` en claro, trackeado | Mover la credencial a `.env.testing` + rotar `root` | 🔴 P0 |
| `.phpunit.result.cache` (46 KB) | Caché en el árbol | Debe estar ignorado | Borrar del árbol | 🟡 |
| `storage/logs/laravel.log` (446 KB) | Log sin rotación | `LOG_STACK=single` + nivel `debug` | `LOG_STACK=daily` + purgar | 🟡 |
| `scratch/` (`generate_test_nav.php`, `test_nav_render.php`) | Temporal | Código de depuración | Ya en `.gitignore` ✅ → borrar del árbol | 🟡 |
| `database/database.sqlite` (94 KB) | Residuo | El proyecto usa MySQL; `phpunit.xml` usa `:memory:` | Borrar del árbol | 🟡 |
| `tests/Browser/screenshots/*.png` | Artefactos | Screenshots de Dusk versionados | Ignorar/eliminar | 🟡 |
| `public/css/glass.css` (305 KB) | Duplicado funcional | Coexiste con Tailwind sin purgar | Integrar al build de Vite y purgar | 🟠 |
| `DOCUMENTACION_2/3/4_*.md`, `DOCUMENTACION_PROYECTO_NOTEBOOKLM.md` | Doc duplicada | Solapan el README | Consolidar en `docs/` | 🟠 |
| `informes_viabilidad/` | No versionado | Videos excluidos de los backups por diseño | Decidir: conservar y documentar, o archivar | ⚪ |
| `.kilo/` | Config de editor | Ya en `.gitignore` ✅ | Sin acción | — |
| `public/test-tomselect.html` | — | **Ya no existe** (verificado) | Sin acción | — |

> **Verificado y correcto:** el `.gitignore` está **completo** (cubre `.env`, `/vendor`, `/node_modules`, `/public/build`, `/public/storage`, `/storage/*.key`, `/storage/framework/`, `/storage/logs/`, `/.kilo/`, `/scratch/`, `.phpunit.result.cache`, `.env.backup`, `.env.production`). La única fuga real es `phpunit-integration.xml` y `producto`.

---

## PARTE 13 · Plan de ejecución por fases (backlog numerado)

### 🔴 FASE 0 — Publicación segura (1 día · 8 tareas)

| # | Tarea | Archivo(s) | Criterio de aceptación |
|---|---|---|---|
| 0.1 | `APP_ENV=production` + eliminar el registro de Dusk + bloquear `/_dusk` en el servidor web | `.env`, `AppServiceProvider.php:35-37`, config del server | `route:list --path=_dusk` → "No routes found" |
| 0.2 | `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=strict`, `LOG_STACK=daily`, `LOG_LEVEL=warning` | `.env` | Cookies con `Secure`; `storage/logs/` con rotación y 14 archivos |
| 0.3 | Rotar contraseñas de `administrador@`, `tecnico@`, `invitado@`; eliminar los fallbacks hardcodeados; guarda de entorno en `SeedDemoData` | `.env`, `AdminUserSeeder.php:18-20`, `SeedDemoData.php:85-95`, `SystemAuditCommand.php:206` | `security:check` → 0 advertencias; ningún `env('…', 'Admin123*')` en el código |
| 0.4 | Rotar `root` de MySQL; crear `tecni_app` con permisos mínimos; sacar la credencial de `phpunit-integration.xml`; `git rm --cached producto` | `.env`, `phpunit-integration.xml`, `.env.testing` | El XML no contiene contraseñas; `producto` fuera del repo |
| 0.5 | Blindar `restore()` (validación del ZIP + lista blanca de extensiones + no copiar al webroot) | `BackupController.php:551-586` | T-13 verde |
| 0.6 | Sacar `git-update` de HTTP → comando de consola con `--expected-sha`; eliminar `openFolder` | `BackupController.php:698-809, 348-387` | No existe `POST /backups/git-update` |
| 0.7 | `npm ci && npm run build`; verificar el hash del manifest vs el último `.blade.php`; `config:cache && route:cache && view:cache && event:cache` | `package.json`, deploy | `about` muestra todas las cachés "CACHED"; manifest más reciente que las vistas |
| 0.8 | Registrar `schedule:run` (cada minuto) y `queue:work` en el Programador de Tareas de Windows; configurar `GOOGLE_DRIVE_BACKUP_PATH`; verificar un `.sql` real | SO, `.env` | Un `.sql` nuevo en el destino off-site; `jobs` sin filas acumuladas |

### 🟠 FASE 1 — Integridad del dato y del reporting (3-4 días · 18 tareas)

| # | Tarea | Hallazgo que cierra | Criterio de aceptación |
|---|---|---|---|
| 1.1 | Tabla `secuencias_documento` + `LAST_INSERT_ID` en `OrdenService` | P1-6 | T-01 y T-03 verdes; dos ventas concurrentes → `VT-1`, `VT-2` |
| 1.2 | Migración de cascadas `CASCADE → RESTRICT/SET NULL` | P1-8 | Borrar un usuario con historial falla con error explícito, no borra la caja |
| 1.3 | `whereDate()` → `where()` en `MovimientoCajaController`, `MovimientoInventarioController`, `ReporteFinancieroController`, `EventoController`, `MantenimientoController`, `CierreCaja` | P1-7 | `EXPLAIN` pasa de `type=ALL` a `range`/`ref`/`const` |
| 1.4 | `CierreCajaController::calcularDia()` con saldo acumulado (saldo anterior + flujo del día) | P1-1 | T-08 verde |
| 1.5 | `ReporteFinancieroService` único (HTML + Excel + PDF) con una sola fórmula de balance | P1-2, EXP-03 | T-05 y T-06 verdes: las tres salidas coinciden |
| 1.6 | Acotar `FacturaItem` a la factura en `updateFactura` | P1-4 | T-09 verde |
| 1.7 | `tipo_movimiento` derivado del destinatario en `convertir()` | P1-5 | T-10 verde |
| 1.8 | Ordenar `stock_id` ascendente antes de cada `lockForUpdate` | 6.2.5, F-25 | T-02 verde (sin 1213/1205) |
| 1.9 | Reutilizar el modelo ya bloqueado en `convertir()` (sin releer) | 6.2.6 | Sin segunda lectura sin lock |
| 1.10 | `Factura::recalcularPagos()` y el reporte por `factura_id` (no por `LIKE` de texto) | P1-9 | T-11 verde |
| 1.11 | `withSum` de abonos con filtro `anulado=false` y corregir el accessor | P1-10 | Un abono anulado no reduce el saldo pendiente |
| 1.12 | Anulación: marcar `abonos.anulado`, revertir `costo`, `costo` = Σ(pivote) | P1-11, P1-12 | T-12 verde; sin costo residual |
| 1.13 | `ALTER TABLE facturas ALTER COLUMN fecha DROP DEFAULT` + `DROP COLUMN proveedores.activo` | F-01, F-02 | Sin columnas huérfanas |
| 1.14 | Fila real de `configuraciones` (id=1) + `Cache::rememberForever` | F-03 | Logo/NIT/PDF con datos reales |
| 1.15 | Crear los 10 índices y eliminar los 7 redundantes | F-16, F-17, F-18 | `information_schema.STATISTICS` limpio |
| 1.16 | Purgar las 2.020 filas de `eventos` de la carga de prueba + job `eventos:purge --days=90` en el schedule | F-16, F-27 | Tabla `eventos` con el volumen real y purga automática |
| 1.17 | Separar `DatabaseSeeder` de `DemoDataSeeder`/`TestDataSeeder`; unificar el formato de numeración (`VT-`/`CP-`) en el seeder | F-27, F-23 | `migrate:fresh --seed` en staging no crea 2.020 eventos ficticios |
| 1.18 | Reescribir la migración destructiva `2026_06_12_130509_*`; mover catálogos a seeders; backfill O(n) | F-10, F-11, F-12 | `migrate` sobre una BD con datos no borra nada |

### 🟡 FASE 2 — Seguridad en profundidad (2 días · 14 tareas)

| # | Tarea | Hallazgo |
|---|---|---|
| 2.1 | CSP real (`script-src 'self'`), autoalojar flatpickr/tom-select, SRI, quitar `npmcdn.com`, quitar `X-XSS-Protection`, HSTS solo en HTTPS, `frame-ancestors 'none'` | A-4, B-2, B-15, B-16, B-17 |
| 2.2 | `throttle:5,1` en `PUT/PATCH mantenimientos|electronicas` + rate-limit en `AnulacionService::adminPasswordValida()` con bloqueo progresivo y registro en `eventos` | A-5 |
| 2.3 | `RateLimiter::for('sensitivo')` por rol aplicado a `/backups/*`, `/usuarios`, `/configuracion`, `/caja`, `/inventario/*`, `/cierre`, reportes y exportaciones | M-8 |
| 2.4 | Neutralizar CSV injection en los 5 exports (helper central) | M-1, T-14 |
| 2.5 | `setRememberToken` + `logoutOtherDevices` al cambiar contraseña | M-2 |
| 2.6 | Token opaco de un solo uso para el acceso de invitado + tabla `cliente_usuarios` | M-3, M-4 |
| 2.7 | Quitar `cantidad` del formulario y del `$fillable` de `Stock`; flujo de ajuste con motivo y asiento | M-5, STK-02, 6.2.10 |
| 2.8 | `Gate::authorize` en todas las mutaciones de órdenes | M-6 |
| 2.9 | `autorizarOperacionSensible()` en `CierreCaja::update` cuando hay diferencia | M-7 |
| 2.10 | Mensajes genéricos al cliente + `report($e)` / `Log::error()` en 6 controladores | M-9 |
| 2.11 | Guardas de entorno en `SetupDatabase`, `SeedDemoData`, `ResetTests`; mover `laravel/tinker` a `require-dev` | B-11, B-12 |
| 2.12 | Login solo por correo; bloqueo por (IP+correo); no limpiar el bloqueo global desde otro login | M-11, M-12 |
| 2.13 | Rotación de `APP_KEY` documentada con `APP_PREVIOUS_KEYS`; flujo de recuperación de contraseña | I-1, I-6 |
| 2.14 | Mover `resolveMysqlBinary` a configuración; borrar `--password=` de la línea de comandos de `mysqldump` | M-10, B-4 |

### 🟢 FASE 3 — Concurrencia, UX, mantenibilidad y documentación (1-2 semanas · 26 tareas)

| # | Tarea |
|---|---|
| 3.1 | Implementar la política de reserva elegida (Opción A, B o C — ver 6.3) + `stock_reservado` o tabla `reservas_stock` + UI de "disponible vs comprometido" |
| 3.2 | Los 15 archivos de test nuevos (T-01 … T-15) |
| 3.3 | `vendor\bin\pint` (19 archivos) + `declare(strict_types=1)` + PHPStan nivel 5 en CI |
| 3.4 | Extraer `layouts/app.blade.php` (1826 líneas) a partials/componentes; mover el JS inline a módulos Vite |
| 3.5 | Componente `<x-modal>` accesible (ARIA + focus trap + `Esc`) reutilizado en las 3 modales globales |
| 3.6 | Purgar `glass.css` (305 KB) → tokens en `tailwind.config.js` + `@layer components` |
| 3.7 | Unificar `@money` a 2 decimales en una función Blade única (HTML + Excel + PDF) |
| 3.8 | Corregir `inventario/facturas/print.blade.php` y `print_pos.blade.php` (NIT y teléfono vacíos) + auditar todos los campos de las plantillas contra las migraciones |
| 3.9 | Estados vacíos + skeletons + `try/catch` en todos los `fetch` + helper `apiFetch()` con CSRF |
| 3.10 | Componentes de formulario `<x-field>` reutilizables para los 7 recursos |
| 3.11 | Unificar la lógica de anulación (Mantenimiento + Electrónica) en `AnulacionService`; `ExportBase` para los 5 exports; `CrudController` base |
| 3.12 | Mover la agregación del reporte financiero a SQL; corregir N+1 |
| 3.13 | Módulo de IVA (si el negocio lo requiere — decisión explícita) |
| 3.14 | `Gate::authorize` + `Model::preventLazyLoading` en desarrollo |
| 3.15 | `softDeletes` en las 7 tablas históricas |
| 3.16 | Documentación: README, `docs/modelo-de-datos.md`, `docs/glosario.md`, `docs/arquitectura.md`, `docs/runbook.md`, `docs/manual/`, `CONTRIBUTING.md` |
| 3.17 | Reescribir `DEPLOY_CHECKLIST.md` para Windows/ServBay (incluir `npm run build` y verificación del manifest) |
| 3.18 | Consolidar `DOCUMENTACION_*`; mover lo institucional a `docs/institucional/` |
| 3.19 | Actualizar `SECURITY_DEBT.md` con fechas y criterios de aceptación |
| 3.20 | Limpieza de archivos (ver Parte 12) |
| 3.21 | CI: `pint --test` + `phpstan` + `php artisan test` + `composer audit` + `npm audit` |
| 3.22 | `composer.json`: mover `laravel/tinker` a `require-dev`, agregar `paratest`, scripts `format`/`analyse`/`check` |
| 3.23 | `php.ini` objetivo: `memory_limit=256M`, `max_execution_time=120`, `date.timezone` coherente |
| 3.24 | `max_connections` MySQL a 150-300 |
| 3.25 | Badge de "estado del sistema" en el dashboard (versión, última migración, estado del backup) |
| 3.26 | Plan de rotación de `APP_KEY` + procedimiento de restauración probado en staging |

---

## PARTE 14 · Checklist de producción

### Bloqueantes (antes de publicar)
- [ ] `APP_ENV=production` y `/_dusk` inaccesible (verificado con `route:list`)
- [ ] Contraseñas de admin/técnico/invitado rotadas; `security:check` = 0 advertencias
- [ ] `root` de MySQL rotado; usuario `tecni_app` con permisos mínimos
- [ ] Sin credenciales en archivos versionados (`phpunit-integration.xml` saneado, `producto` eliminado)
- [ ] `SESSION_SECURE_COOKIE=true` + redirección 301 a HTTPS
- [ ] `LOG_STACK=daily` + `LOG_LEVEL=warning` + rotación verificada
- [ ] `npm run build` ejecutado y manifest más reciente que las vistas
- [ ] `config:cache`, `route:cache`, `view:cache`, `event:cache` aplicados
- [ ] `schedule:run` y `queue:work` registrados en Tareas de Windows
- [ ] Un `.sql` de backup generado y verificado en destino off-site
- [ ] `restore()` blindado (sin Zip Slip) y `git-update` fuera de HTTP
- [ ] Cascadas de FK corregidas (borrar con historial no destruye caja)
- [ ] Numeración de facturas con tabla de secuencias (sin carrera)

### Importantes (primera semana)
- [ ] `whereDate()` → `where()` en las 6+ ubicaciones
- [ ] `ReporteFinancieroService` único (balance correcto en HTML/Excel/PDF)
- [ ] "Utilidad Neta" diferenciada del flujo de caja
- [ ] Cierre de caja con saldo acumulado
- [ ] Cita de stock implementada (al menos la Opción A: alertar lo comprometido)
- [ ] Los 15 tests nuevos en verde
- [ ] `cotizaciones`/`electronicas` con `id_orden` generado por el sistema
- [ ] Fila real de `configuraciones` + caché
- [ ] Índices creados/redundantes eliminados
- [ ] `eventos` purgado + purga automática programada
- [ ] `MAIL_MAILER` real + remitente del dominio
- [ ] Factura/POS con NIT y teléfono correctos
- [ ] Formato monetario con 2 decimales en todos los输出

### Higiene
- [ ] `.env` ↔ `.env.example` sincronizados (incluida la variable `LOG_DAILY_DAYS`)
- [ ] `DEPLOY_CHECKLIST.md` reescrito para Windows/ServBay
- [ ] `php.ini` con `memory_limit=256M`
- [ ] CSP endurecida y HSTS solo en HTTPS
- [ ] `laravel/tinker` movido a `require-dev`
- [ ] Comandos destructivos con guarda de entorno
- [ ] `docs/` completo (modelo de datos, glosario, arquitectura, runbook, manual)
- [ ] `CONTRIBUTING.md` y `SECURITY_DEBT.md` al día
- [ ] `pint --test` limpio + PHPStan en CI

---

## ANEXO A · SQL de índices (ejecutable)

```sql
-- ══ CREAR ══
CREATE INDEX idx_eventos_created_at         ON eventos (created_at);
CREATE INDEX idx_eventos_modelo_created     ON eventos (modelo_tipo, modelo_id, created_at);
CREATE INDEX idx_cotizaciones_fecha_estado   ON cotizaciones (fecha, estado);
CREATE INDEX idx_facturas_estado_saldo       ON facturas (estado, saldo_pendiente);
CREATE UNIQUE INDEX uq_mantenimiento_stock   ON mantenimiento_stock (mantenimiento_id, stock_id);
CREATE UNIQUE INDEX uq_electronica_stock     ON electronica_stock (electronica_id, stock_id);
CREATE UNIQUE INDEX uq_factura_item_stock    ON factura_items (factura_id, stock_id);
CREATE UNIQUE INDEX uq_cotizacion_item       ON cotizacion_items (cotizacion_id, item_id, tipo);
CREATE UNIQUE INDEX uq_configuraciones_nombre ON configuraciones (nombre);
CREATE UNIQUE INDEX uq_categoria_stocks      ON categoria_stocks (nombre, tipo);
CREATE FULLTEXT INDEX ft_clientes_nombres    ON clientes (nombres, apellidos);
CREATE FULLTEXT INDEX ft_stocks_producto     ON stocks (producto);

-- ══ SECUENCIAS (corrige P1-6) ══
CREATE TABLE secuencias_documento (
  prefijo VARCHAR(10) NOT NULL PRIMARY KEY,
  valor   INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;
INSERT INTO secuencias_documento (prefijo, valor) VALUES ('VT-', 0), ('CP-', 0), ('F-', 100);

-- ══ ELIMINAR (prefijo redundante / par inverso) ══
DROP INDEX idx_movcaja_tipo             ON movimiento_cajas;
DROP INDEX idx_abonos_mantenimiento    ON abonos;
DROP INDEX idx_abonos_electronica      ON abonos;
DROP INDEX idx_mant_fecha_entrada      ON mantenimientos;
DROP INDEX cotizaciones_estado_index   ON cotizaciones;
DROP INDEX idx_cierrecaja_fecha        ON cierre_cajas;
DROP INDEX idx_facturas_estado_tipo    ON facturas;   -- par inverso de idx_fact_tipo_estado
DROP INDEX idx_mant_estado_anulado     ON mantenimientos;
DROP INDEX idx_elec_estado_anulado     ON electronicas;

-- ══ INTEGRIDAD ══
ALTER TABLE facturas ALTER COLUMN fecha DROP DEFAULT;
ALTER TABLE proveedores DROP COLUMN activo;
```

## ANEXO B · Migraciones sugeridas

| Archivo | Contenido |
|---|---|
| `2026_XX_XX_000001_fix_cascadas_destructivas.php` | `ON DELETE RESTRICT/SET NULL` en `users`, `clientes`, `tecnicos`, `stocks`, `facturas` y descendientes |
| `2026_XX_XX_000002_fix_factura_fecha_default.php` | `ALTER TABLE facturas ALTER COLUMN fecha DROP DEFAULT;` + sanear nulos |
| `2026_XX_XX_000003_drop_proveedores_activo.php` | `ALTER TABLE proveedores DROP COLUMN activo;` |
| `2026_XX_XX_000004_limpiar_unicidad_pivots_y_cotizaciones.php` | Deduplicar + crear los `UNIQUE` del Anexo A |
| `2026_XX_XX_000005_optimizar_indices.php` | Crear los índices del Anexo A y eliminar los redundantes |
| `2026_XX_XX_000006_crear_secuencias_documento.php` | Tabla `secuencias_documento` + seed de prefijos |
| `2026_XX_XX_000007_softdeletes_historicos.php` | `deleted_at` en `mantenimientos`, `abonos`, `movimiento_cajas`, `cotizaciones`, `equipos`, `clientes`, `tecnicos` |
| `2026_XX_XX_000008_configuracion_singleton.php` | Fila `id=1` real + `UNIQUE(nombre)` + caché |
| `2026_XX_XX_000009_reservas_stock.php` *(solo si se elige la Opción B/C)* | Tabla `reservas_stock` o columna `stock_reservado` + servicio de reserva |

## ANEXO C · `.env` objetivo (producción)

```dotenv
APP_NAME="Mantenimiento Equipos"
APP_ENV=production
APP_KEY=base64:<NUEVA_CLAVE_GENERADA>
APP_DEBUG=false
APP_TIMEZONE=America/Bogota
APP_URL=https://tecnisystemas.local

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CO

# Sin contraseñas por defecto: rotar y retirar
# ADMIN_DEFAULT_PASSWORD=
# TECNICO_DEFAULT_PASSWORD=
# INVITADO_DEFAULT_PASSWORD=

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=14
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tecni_systemas
DB_USERNAME=tecni_app
DB_PASSWORD=<PASSWORD_FUERTE_UNICA>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.<proveedor>
MAIL_PORT=587
MAIL_USERNAME=<usuario>
MAIL_PASSWORD=<password>
MAIL_FROM_ADDRESS="<remitente@dominio-real>"
MAIL_FROM_NAME="${APP_NAME}"

MYSQLDUMP_PATH=C:\ServBay\packages\mysql\8.4\bin\mysqldump.exe
GOOGLE_DRIVE_BACKUP_PATH=<ruta-de-drive>
BACKUP_RETENTION_DAYS=30

VITE_APP_NAME="${APP_NAME}"
```

## ANEXO D · `php.ini` objetivo

```ini
memory_limit = 256M
max_execution_time = 120
max_input_vars = 2000
post_max_size = 12M
upload_max_filesize = 8M
date.timezone = America/Bogota
opcache.enable = 1
opcache.revalidate_freq = 2
```

## ANEXO E · Reglas de servidor web (defensa en profundidad)

**Nginx (ServBay)**
```nginx
location ~ ^/_dusk      { deny all; }
location ~ ^/_ignition  { deny all; }
location ~ /\.(?!well-known) { deny all; }
location ~ ^/(storage|app|bootstrap|vendor|tests|database)/ { deny all; }
location ~ \.php$ { ... fastcgi_pass ... }
```

**Apache (`public/.htaccess`)**
```apache
RewriteEngine On
RewriteRule ^_dusk - [F,L]
RewriteRule ^_ignition - [F,L]
```

## ANEXO F · Verificación final (script de comprobación)

```powershell
php artisan route:list --path=_dusk      # → No routes found
php artisan security:check                # → 0 advertencias
php artisan about | Select-String "CACHED"
php artisan migrate:status                # → 56/56 Ran
php artisan schedule:list                 # → app:backup-db + eventos:purge
vendor\bin\phpunit                        # → 501+ tests OK (incluye los 15 nuevos)
vendor\bin\pint --test                    # → sin violaciones
php artisan config:show app | Select-String "env.*production"
```

---

## FIN DEL INFORME

**Estado de la auditoría:** completa en alcance técnico (5dims + concurrencia + tests). **No se modificó ningún archivo de código, configuración o datos** del proyecto; el único archivo agregado es este documento (`AUDITORIA_EXHAUSTIVA.md`).

**Limitaciones declaradas:**
- No se ejecutaron pruebas ofensivas activas contra la aplicación; P0-1 se verificó por inspección del código de Dusk + `route:list`, no por explotación en vivo.
- No se auditó la configuración del servidor web de ServBay, el `php.ini` en runtime, los ACL del sistema de archivos ni el Firewall de Windows.
- No se auditó el historial completo de GitHub (ramas, PRs) ni el estado de la CI.
- `composer audit` / `npm audit` no se ejecutaron (requieren red).
- No se ejecutaron `tests/Integration` (requiere MySQL `tecni_systemas_test`) ni los tests Dusk (requieren navegador).
- No se midió cobertura de código (no hay Xdebug/pcov en el entorno).
- El análisis del esquema y los EXPLAIN se hizo sobre la BD `tecni_systemas` **en solo lectura**.


