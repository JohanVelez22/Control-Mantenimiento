# Auditoría de base de datos

> **Origen:** informe del agente 4 (base de datos) de la auditoría integral del 2026-11-13 (análisis con evidencia, sin modificar producción).
> **Coordinación:** el coordinador verificó los hallazgos ALTOS/CRÍTICOS antes de corregirlos y añadió este encabezado de estado.

## Estado tras las correcciones de esta ronda

| Hallazgo | Estado |
|---|---|
| D-02 la rotación podía borrar todos los respaldos de BD | **Corregido**: cupo por tipo, solo nombres propios, mínimo 3 copias + prueba `BackupRotacionTest` |
| D-03 respaldo no verificable | **Corregido en lo esencial**: `app:verificar-respaldo` exige `Dump completed`, rechaza `USE`/`CREATE DATABASE`, usa `--one-database` y solo elige respaldos de BD. Manifiesto con checksums: **pendiente** |
| D-06 carrera anular / abonar | **Corregido** |
| D-09 `updateFactura` sin bloqueo | **Corregido** (bloquea la factura) |
| D-10 opciones de mysqldump | **Corregido** (`--single-transaction --quick --no-tablespaces --routines --triggers`) |
| D-01, D-04, D-05, D-07, D-08, D-11 a D-26 | **Pendientes** |

Los identificadores (p. ej. `M-01`) se usan igual en `CAMBIOS_REALIZADOS.md`, `PENDIENTES.md` y en los mensajes de commit.

---

## Informe del agente 4 (base de datos)

Fecha: 2026-11-13 · Alcance: MySQL 8.4.11 real (`tecni_systemas`, ServBay, `.env` APP_ENV=production), migraciones, modelos, servicios, controladores con consultas, respaldo y restauración.
Método: lectura de `information_schema`, `SHOW`, `EXPLAIN` y `SELECT` mediante scripts PHP con bootstrap de Laravel y `SET SESSION TRANSACTION READ ONLY` (scripts en `carpeta de trabajo de la auditoría/a4/`: `schema.php`, `integ.php`, `i2.php`, `i3.php`, `i4.php`, `explain.php`; salida del esquema en `schema.txt`). No se escribió nada en la base ni en el proyecto.

> Destinos: secciones 1–5 y 7 → `AUDITORIA_BASE_DATOS.md` (y `GUIA_RESPALDOS.md` para la sección 5); sección 6 → `MIGRACIONES.md`.

---

## 0. Resumen ejecutivo

La base está sana en estructura (InnoDB y utf8mb4_unicode_ci en todo, `STRICT_TRANS_TABLES`, 35 FK, 0 migraciones pendientes, sin huérfanos ni duplicados de negocio). **Pero los datos actuales son los de `app:seed-demo`** (5 filas por tabla, 15 movimientos, 0 abonos, 0 cierres, los 3 usuarios con `must_change_password=1`), así que la integridad "limpia" casi no prueba nada: los riesgos están en el código que va a escribir los datos reales y en el procedimiento de recuperación.

Hallazgos CRÍTICOS/ALTOS:

| ID | Sev. | Tema |
|---|---|---|
| D-01 | ALTA | La restauración no es atómica: no activa el modo mantenimiento, no revisa la versión de esquema y deja intactas las tablas que no vienen en el dump |
| D-02 | ALTA | La rotación por cupo mezcla tipos de respaldo: generar snapshots de código (`code_*.zip`) borra los respaldos de BD. La retención en la carpeta de Drive borra cualquier `.sql/.zip/.gz` |
| D-03 | ALTA | Ningún respaldo es verificable: no hay manifiesto, checksum ni comprobación del pie "Dump completed". El `app:verificar-respaldo` que se está escribiendo (sin commit) compara contra la base viva (no concluye nada) y, si el dump trae `USE`, puede escribir en la base viva |
| D-04 | ALTA | 67 usos de `whereDate/whereMonth/whereYear` o SQL crudo con `DATE(col)`. EXPLAIN muestra `possible_keys=NULL`: los índices de fecha no se usan en caja, eventos, reportes ni en el guard de cierre |
| D-05 | ALTA | Los accessors de `MovimientoCaja` (`total_pagado`, `saldo_pendiente`, `effective_monto_total`) causan N+1 con `LIKE '%…'` y full scan: unas 20 consultas por página de caja y hasta unas 100 en el topbar (cada 30 s por usuario) |
| D-06 | ALTA | Carrera entre anular y abonar: después de `lockForUpdate` no se vuelve a comprobar `anulado`, y `Factura::recalcularPagos()` guarda atributos viejos, así que puede deshacer la anulación de una factura |
| D-07 | ALTA | La aplicación se conecta como `root` (SUPER, FILE, SHUTDOWN, DROP, todas las bases) |

---

## 1. Esquema real

### 1.1 Entorno
- MySQL 8.4.11, charset/collation `utf8mb4` / `utf8mb4_unicode_ci` (base y las 31 tablas). Todas InnoDB. Sin triggers, rutinas ni vistas.
- sql_mode: `ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`.
- Aislamiento `REPEATABLE-READ`, `innodb_lock_wait_timeout=50`, `time_zone=SYSTEM` (Windows "Pacífico Sudamérica", UTC-5). La conexión de Laravel no fija `timezone`.
- **Binlog activado** (`log_bin=1`, formato MIXED, retención 30 días, `sync_binlog=1`, `innodb_flush_log_at_trx_commit=1`). Permite recuperar a un punto en el tiempo (PITR) si el respaldo guarda su posición de binlog. Hoy no la guarda (D-10).
- Usuario de la aplicación: `root@localhost` con todos los privilegios (D-07).
- `cache`, `session` y `queue` usan driver `database`, así que esas tablas viajan en cada respaldo y vuelven al restaurar (D-10).

### 1.2 Tipos monetarios (todo es DECIMAL; no hay FLOAT/DOUBLE)

| Tabla.columna | Tipo | Observación |
|---|---|---|
| movimiento_cajas.monto / monto_total | decimal(12,2) | ok |
| abonos.monto | decimal(12,2) | ok |
| facturas.total_documento / total_pagado / saldo_pendiente (STORED) / saldo_a_favor (STORED) | decimal(12,2) | ok |
| factura_items.precio_unitario / subtotal (STORED) | decimal(12,2) | ok |
| cotizaciones.total, cotizacion_items.precio_unitario/subtotal | decimal(12,2) | ok |
| cierre_cajas.* (7 columnas) | decimal(12,2) | ok |
| bajas_stock.precio_compra_unitario / costo_total_perdida | decimal(12,2) | ok |
| electronica_stock.precio_unitario | decimal(12,2) | ok |
| **mantenimientos.costo** | **decimal(10,2)** | inconsistente (D-11) |
| **electronicas.costo** | **decimal(10,2)** | inconsistente |
| **mantenimiento_stock.precio_unitario** | **decimal(10,2)** | su gemela `electronica_stock` usa 12,2 |
| **stocks.precio_compra / precio_venta / precio_tecnico** | **decimal(10,2)** | el precio de compra entra a `factura_items` (12,2) |
| stocks.utilidad | decimal(5,2) | porcentaje; tope 999.99 % |

Las precisiones coinciden con las migraciones (`create_mantenimientos_table:19`, `create_stocks_table:20-23`, `create_electronicas_table:22`, `create_mantenimiento_stock_table:19`). No hay deriva entre migraciones y esquema real.

### 1.3 Nullables y diseño relevante
- `equipos.cliente_id` y `proveedor_id` son ambos NULL-ables. Ningún CHECK obliga a que uno y solo uno tenga valor. Hoy: 0 sin dueño y 0 con ambos.
- `abonos.mantenimiento_id` y `electronica_id` son ambos NULL-ables. Ningún CHECK exige exactamente uno.
- `movimiento_cajas.estado` (varchar 255, 'activo'/'anulado') duplica `anulado` (tinyint). Hoy son coherentes (15 'activo'/0) y ninguna restricción lo garantiza.
- `mantenimientos.estado` es varchar(50) y `electronicas.estado` es ENUM; `equipos.estado` es varchar(30). Hay mezcla de estrategias.
- `facturas.facturable_*` y `cotizacion_items.item_id` son polimórficos, sin FK. Hoy hay 0 huérfanos.
- `electronicas.id_orden` es NULL-able (UNIQUE) y `mantenimientos.id_orden` es NOT NULL. Hoy hay 0 nulos.
- `facturas` usa `SoftDeletes`, pero ningún código borra facturas. El UNIQUE de `numero_factura` incluye las filas borradas lógicamente (ver D-17).

### 1.4 Claves foráneas (35)
- RESTRICT: 26. `NO ACTION` (equivale a RESTRICT en InnoDB): `cotizaciones.cliente_id` y `cotizaciones.user_id`.
- SET NULL: `bajas_stock.user_id`, `equipos.baja_user_id`, `equipos.proveedor_id`, `eventos.user_id`, `cotizaciones.factura_id`, `cotizaciones.proveedor_id`, **`movimiento_cajas.abono_id`, `movimiento_cajas.factura_id`, `movimiento_cajas.parent_id`** (ver D-12).
- `ON UPDATE` es NO ACTION en todas (correcto con PK autoincrementales).

### 1.5 Índices: redundancias y faltantes
Redundantes por prefijo:
- `electronicas.idx_elec_fecha_entrada (fecha_entrada)` es prefijo de `idx_elec_fecha_anulado (fecha_entrada, anulado)`. Su equivalente en mantenimientos sí se eliminó en `2026_10_21_170000`; el de electrónicas se olvidó.
- `eventos.eventos_modelo_tipo_modelo_id_index (modelo_tipo, modelo_id)` es prefijo de `idx_eventos_modelo_created (modelo_tipo, modelo_id, created_at)`.

De poco valor (una sola columna booleana de baja cardinalidad): `idx_movcaja_anulado`, `abonos_anulado_index`, `idx_clientes_active`, `idx_proveedores_active`.

Faltantes, según los WHERE/ORDER BY reales:
- `movimiento_cajas`: el topbar ordena por `created_at` (`latest()`) y no hay índice. Hace filesort y conviene cambiarlo a `orderBy('id','desc')`.
- `eventos (user_id, created_at)` y `(accion, created_at)`: son los filtros reales de `EventoController::index`.
- Para el saldo inicial, `movimiento_cajas (tipo_pago, fecha)` o, mejor, un índice cubriente `(fecha, tipo_pago, anulado, estado, tipo_movimiento, monto)`.

Únicos correctos: `id_orden` (×2), `numero_factura`, `codigo`, `identificacion` (clientes, proveedores, técnicos), `equipos.serie`, `stocks.codigo`, `cierre_cajas.fecha`, los pivotes y `concepto_cajas.nombre`.

---

## 2. Integridad de datos reales (conteos)

Conteos: movimiento_cajas 15 · abonos 0 · facturas 5 · mantenimientos 5 · electronicas 5 · eventos 135 · stocks 5 · cotizaciones 5 · cierre_cajas 0 · equipos 5 · clientes 6.

| Comprobación | Resultado |
|---|---|
| movimiento_cajas.abono_id / factura_id / parent_id huérfanos | 0 / 0 / 0 |
| parent_id anidado (nieto) | 0 |
| abonos sin movimiento, con más de un movimiento, o con monto/fecha/anulado distinto del movimiento | 0 / 0 / 0 |
| cotizacion_items.item_id huérfano (stock/mant/elec) | 0 / 0 / 0 |
| subtotal ≠ cantidad × precio en cotizacion_items; total de la cotización ≠ suma de ítems | 0 / 0 |
| total_documento de la factura ≠ suma de factura_items | 0 |
| total_pagado de la factura ≠ suma de la caja activa | 0 (VT-1..3 y CP-1..2 cuadran; VT-2 tiene saldo 125.000) |
| equipos sin cliente ni proveedor / con ambos | 0 / 0 |
| facturable huérfano | 0 |
| duplicados de id_orden, numero_factura o identificación | 0 (`app:verificar-migraciones` también da 0) |
| montos negativos, stock negativo, precio_venta < precio_compra | 0 |
| secuencias_documento frente al máximo existente | ORD 5/5, ELC 5/5, VT 3/3, CP 2/2, COT 5/5, F 0 |
| estado/anulado incoherentes en movimiento_cajas | 0 |
| movimientos en días cerrados después del cierre | 0 (no hay cierres) |
| movimientos legacy sin factura_id que citan #VT/#CP/#F | **0**. La rama `LIKE` de compatibilidad ya no tiene datos que la necesiten (ver D-05) |
| costo de la orden ≥ repuestos | ok en las 10 órdenes |

Observaciones sobre los datos:
- **Datos de demostración incoherentes**: `SeedDemoData.php:363` crea ingresos de caja "Ingreso por servicio técnico ORD-n" sin abono. Por eso las 10 órdenes muestran saldo pendiente total (abonos = 0) aunque la caja registra 800.000 cobrados por esos servicios. No afecta a producción si la base se vacía antes de usarla (`app:clean-data`). Si estos datos se quedan, los reportes por orden y los de caja no cuadran.
- 1 cotización `aprobada` sin `factura_id` (puede ser aprobada y no convertida; solo informativo).
- Recomendación: convertir las consultas de esta tabla en un comando de solo lectura `app:verificar-integridad` y programarlo cada semana junto a `app:verificar-respaldo`.

---

## 3. Consultas y rendimiento (EXPLAIN real)

| Consulta (origen) | Plan | Problema |
|---|---|---|
| Listado de caja: `whereDate('fecha','>=')…` (`MovimientoCajaController:43-44, 127-128`) | `ALL`, `possible_keys=NULL`, filesort | `DATE(fecha)` impide usar `idx_movcaja_fecha`. El `paginate()` añade un `COUNT(*)` que también hace full scan |
| Mismo filtro con `where('fecha','>=')` | `possible_keys=idx_movcaja_fecha` | el optimizador puede usarlo cuando la tabla crezca |
| `total_pagado` con ref (`MovimientoCaja:113-150`) | `ALL` | `OR descripcion LIKE '%Orden ORD-1'…` descarta índices |
| Legacy de `recalcularPagos` (`Factura:150-166`) | `ref idx_movcaja_tipo_fecha` + filtro | recorre todos los ingresos o egresos de la historia en cada pago |
| Topbar: movimientos pendientes (`AppServiceProvider:229-245`) | `ref factura_id` + filesort | `not like '%#VT-n%'` por cada factura pendiente (hasta 50), `ORDER BY created_at` sin índice |
| Topbar: facturas con saldo | `range idx_facturas_saldo_pendiente` | ok |
| Dashboard: suma histórica de caja (`DashboardController:43-44`) | `ref idx_movcaja_tipo_fecha` | recorre toda la historia en cada carga, sin caché |
| Dashboard "hoy" con `whereDate` | ICP sobre tipo, `DATE(fecha)` | no usa el rango de fecha |
| `saldoInicialEfectivo` sin conteo previo (`CierreCaja:51-68`) | `ALL` | suma toda la historia en efectivo en cada vista de cierre |
| `calcularDia` (MySQL usa `where`, no `whereDate`) | `ref idx_movcaja_fecha` | ok |
| `CierreCajaGuard::fechaEstaCerrada` (`whereDate`) | `ALL` en cierre_cajas | no usa el UNIQUE(fecha). Se llama 2–6 veces por operación |
| Eventos: `whereDate(created_at)` + `latest()` + `paginate(30)` (`EventoController:44-45`) | `ALL`/filesort | la tabla crece con cada `save()` auditado |
| `OrdenService::obtenerMaximoExistente` | `index` (scan del unique) | `pluck` de todos los números del prefijo en cada documento nuevo |
| Mantenimientos/electrónicas index | `PRIMARY` backward scan + eager loading | ok |
| Abonos por orden | `range idx_abonos_mant_fecha` | ok |

N+1 detectados:
- `caja/index.blade.php:182-189`: por fila se evalúan `effective_monto_total` (2 veces) y `saldo_pendiente` (2 veces). Cada `saldo_pendiente` recalcula `total_pagado` con una consulta `SUM` y full scan (la ruta con `ref_search` ignora el `childPayments` precargado; ver la condición `relationLoaded && ! $refSearch`, `MovimientoCaja:118`). Son unas 20 consultas por página de 10 filas. `effective_monto_total` añade consultas a Factura/Mantenimiento/Electrónica cuando `monto_total` es 0/NULL (los abonos hijos guardan `monto_total=0`).
- Topbar (`AppServiceProvider:245-255`): hasta 50 movimientos × 2 `saldo_pendiente` = unas 100 consultas con `LIKE`. La caché dura 30 s **por usuario**.
- `ReporteFinancieroController:233-234`: `Mantenimiento::…->with('abonos')->get()->sum(saldo_pendiente)` carga todas las órdenes del día en memoria (aceptable por día). `reporteAcumulado:382-389` usa `whereIn(pluck ids)` en lugar de un join o subconsulta, y `:352` compara `str_contains($descripcion, 'VT-1')`, que también coincide con "VT-10" (el mismo defecto de prefijo que ya se corrigió en `referenciaExacta`; se avisa al agente financiero).
- `DashboardController:193`: `Cliente::…->get()` carga todos los clientes en cada carga del dashboard.

Paginación en tablas que crecerán: caja (`paginate(10)`), eventos (`paginate(30)`) y cierres paginan bien, pero el `COUNT(*)` con `DATE()` hace full scan. `CierreCajaController::show` carga todos los movimientos del día (acotado). Exportaciones y reportes usan `->get()` sin límite (`MantenimientoController:173/178`, `ElectronicaController:354/360`, `StockController:272/281`, `ReporteFinancieroController:622-645`). Conviene `cursor()`/`lazy()` o `chunk` en Excel/PDF.

---

## 4. Transacciones, bloqueos y concurrencia

Correcto: `OrdenService` (fila de `secuencias_documento` con `lockForUpdate` + `insertOrIgnore`), `StockService` (vuelve a leer con bloqueo en cada entrada/salida), `registrarVenta` (bloquea stocks en orden `ksort`), `CierreCajaController::store` (bloqueo de rango sobre UNIQUE(fecha) para evitar dos cierres del mismo día), anulación de órdenes y facturas (`lockForUpdate` + `verificarIntencion`), `HandlesStockAttach` (bloquea la orden, `increment('costo')`).

Problemas: D-06, D-08 y D-09 (abajo).

Orden de bloqueo recomendado (documentarlo y respetarlo en todo el código): **documento (orden/factura) → movimiento raíz → movimientos hijos → stocks por id ascendente → secuencias**.

---

## 5. Respaldos y recuperación

### 5.1 Estado actual (`BackupDatabase.php`)
- Comando: `mysqldump --user --host --port <db> --result-file` (`:306-314`). Usa las opciones por defecto (`--opt` = `--lock-tables --add-drop-table --extended-insert…`) y la contraseña va por `MYSQL_PWD` (obsoleto y visible en el entorno del proceso).
- Dump real `db_tecni_systemas_2026-11-13_02-41-32.sql` (≈198 KB): `SET NAMES utf8mb4` ✔, `TIME_ZONE='+00:00'` ✔, `DROP TABLE IF EXISTS` ✔, pie "Dump completed" ✔, sin `USE`/`CREATE DATABASE` ✔, 31 tablas ✔, triggers incluidos por defecto (no hay ninguno).
- Le falta: `--single-transaction` (hoy `LOCK TABLES … READ` bloquea las escrituras de la app durante el dump), `--source-data=2` (posición del binlog para PITR), `--routines --events` (hoy no hay ninguno, pero es preventivo), `--hex-blob`, `--set-gtid-purged=OFF` (GTID está OFF), `--default-character-set=utf8mb4` explícito, compresión, checksum SHA-256 y manifiesto.
- Respalda `cache`, `cache_locks`, `sessions` y `jobs` con datos: guarda payloads de sesión (datos personales) y, al restaurar, revive caché vieja, incluidos los contadores del limitador de intentos y `schema_config_formato_checked`.
- La rotación (`aplicarPoliticaRotacion:567-605`) cuenta juntos `.sql/.zip/.gz` de **todos los tipos** (D-02). Si un `zip` falla deja `temp_db_*.sql` en la carpeta, y ese archivo también entra en el cupo.

### 5.2 Restauración (`BackupController::restore:519-767`)
Correcto: respaldo de seguridad previo (`--sin-rotacion`), protección contra Zip Slip, lista blanca de extensiones multimedia, `--default-character-set=utf8mb4` y nombres validados. Problemas en D-01 y D-16.

### 5.3 Diseño: procedimiento verificable de recuperación `app:verificar-respaldo`

**Estado observado:** existe `app/Console/Commands/VerificarRespaldo.php`, **sin seguimiento en git** (creado hoy a las 02:42 mientras se hacía esta auditoría), y `routes/console.php` modificado lo programa cada semana. Revisión de ese borrador:

| # | Problema del borrador | Línea |
|---|---|---|
| a | Elige el respaldo `.sql/.zip` más reciente **de cualquier tipo**: un `code_*.zip` no contiene `database.sql` y la verificación falla (falso negativo) | 47-51 |
| b | Compara contra la base **viva**. En producción siempre hay diferencias y termina en `SUCCESS` con advertencia. **No es concluyente** | 101-104 |
| c | No impide que el dump contenga `USE \`tecni_systemas\`` / `CREATE DATABASE` (dumps hechos con `--databases` o archivos subidos a mano). En ese caso el `mysql` "hacia la temporal" escribiría en la base viva. Falta `--one-database` y un escaneo previo | 114-117 |
| d | No verifica el pie `-- Dump completed`: un dump truncado justo en un límite de sentencia se restaura "bien" | — |
| e | Sumas incompletas o mal elegidas: `factura_items.precio_unitario` (debería ser `subtotal`). Faltan `facturas.total_pagado`, `cotizaciones.total`, `cotizacion_items.subtotal`, pivotes `cantidad*precio_unitario`, `bajas_stock.costo_total_perdida`, `cierre_cajas.efectivo_real_contado`, `MAX(secuencias_documento.valor)` | 32-36 |
| f | No compara estructura (columnas, índices, FK) ni la tabla `migrations` (versión de esquema) | — |
| g | No drena `stdout` del proceso mysql (riesgo menor de bloqueo); no limpia bases `verif_restauracion_%` de corridas anteriores que se cayeron | 114-121 |
| h | No registra el resultado en `eventos` ni avisa; solo agrega al log | — |

**Diseño recomendado (dos partes).**

**Parte A — al respaldar (`app:backup-db`)**: generar un manifiesto que haga la verificación determinista.
1. Antes del dump: `SELECT @@hostname, VERSION()` y la lista de `migrations` (nombre y batch).
2. Calcular un **manifiesto de contenido** por tabla de negocio (todas salvo `cache`, `cache_locks`, `sessions`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`):
   - `COUNT(*)`, `MAX(id)` (o `MAX(prefijo)` en `secuencias_documento`) y `CHECKSUM TABLE <t> EXTENDED`. El checksum es determinista para el mismo contenido en la misma versión y formato de fila, y sirve para comparar con la restauración en el mismo servidor.
   - Sumas de control monetarias (siempre `SUM` sobre DECIMAL, comparadas como cadena):
     ```sql
     SELECT 'movimiento_cajas', COUNT(*), SUM(monto), SUM(COALESCE(monto_total,0)), SUM(anulado) FROM movimiento_cajas;
     SELECT 'abonos', COUNT(*), SUM(monto), SUM(anulado) FROM abonos;
     SELECT 'facturas', COUNT(*), SUM(total_documento), SUM(total_pagado), SUM(saldo_pendiente) FROM facturas;
     SELECT 'factura_items', COUNT(*), SUM(subtotal), SUM(cantidad) FROM factura_items;
     SELECT 'mantenimientos', COUNT(*), SUM(costo), SUM(anulado) FROM mantenimientos;
     SELECT 'electronicas', COUNT(*), SUM(costo), SUM(anulado) FROM electronicas;
     SELECT 'mantenimiento_stock', COUNT(*), SUM(cantidad*precio_unitario) FROM mantenimiento_stock;
     SELECT 'electronica_stock', COUNT(*), SUM(cantidad*precio_unitario) FROM electronica_stock;
     SELECT 'cotizaciones', COUNT(*), SUM(total) FROM cotizaciones;
     SELECT 'cotizacion_items', COUNT(*), SUM(subtotal) FROM cotizacion_items;
     SELECT 'cierre_cajas', COUNT(*), SUM(saldo_final), SUM(COALESCE(efectivo_real_contado,0)), SUM(diferencia) FROM cierre_cajas;
     SELECT 'stocks', COUNT(*), SUM(cantidad), SUM(cantidad*precio_compra) FROM stocks;
     SELECT 'bajas_stock', COUNT(*), SUM(costo_total_perdida) FROM bajas_stock;
     SELECT 'secuencias_documento', GROUP_CONCAT(CONCAT(prefijo,'=',valor) ORDER BY prefijo) FROM secuencias_documento;
     ```
3. Ejecutar `mysqldump --single-transaction --quick --source-data=2 --routines --events --triggers --hex-blob --set-gtid-purged=OFF --default-character-set=utf8mb4 --no-tablespaces` sobre las tablas de negocio, más un segundo paso `--no-data` para `cache`, `sessions`, `jobs`, etc. (MySQL no tiene `--ignore-table-data`). Pasar las credenciales con `--defaults-extra-file` (archivo temporal con permisos 0600 que se borra en `finally`), no con `MYSQL_PWD`.
4. Calcular el manifiesto **otra vez** después del dump. Si es igual al de antes, `"exacto": true` (nadie escribió durante el dump). Si cambió, `"exacto": false` y la verificación usará el modo tolerante (paso B.6).
5. Comprobar el pie `-- Dump completed on` en el archivo; si falta, el respaldo es FALLIDO (código 1 + evento).
6. Escribir `<nombre>.manifest.json` (dentro del ZIP o junto al `.sql`) con: archivo, sha256 del SQL, tamaño, fecha, versión de MySQL, `binlog_file/pos` (leído de la línea `CHANGE REPLICATION SOURCE TO` del dump), lista de migraciones, manifiesto por tabla y `exacto`. Comprimir (`.sql.gz` o ZIP) y rotar **solo por tipo** (D-02).

**Parte B — `php artisan app:verificar-respaldo {--archivo=} {--conservar-temporal}`**
1. **Selección**: el respaldo de BD más reciente (`db_*.sql[.gz]`, `backup_bd_multimedia_*.zip`, `backup_integral_*.zip`; **nunca** `code_*`). Validar el nombre con `^[A-Za-z0-9_.-]+$` y que el archivo esté dentro de `storage/app/backups` (`realpath`).
2. **Integridad del archivo**: sha256 = el del manifiesto (si hay manifiesto); pie `-- Dump completed`. **Escaneo de seguridad** línea a línea (en streaming, sin cargar el archivo entero): rechazar si encuentra `^\s*USE\s`, `CREATE DATABASE`, `DROP DATABASE`, `GRANT`, `CREATE USER` o `\! ` (shell del cliente). Así se asegura que el dump solo puede afectar a la base que se indica.
3. **Preparación**: borrar las bases `verif_restauracion_%` que quedaron de corridas anteriores (`SHOW DATABASES LIKE 'verif\_restauracion\_%'`). Comprobar espacio libre (≥ 3 × tamaño del SQL). `CREATE DATABASE verif_restauracion_<yyyymmdd_His>_<rand4> CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`. Usar idealmente un usuario MySQL dedicado `respaldo_verif` con privilegios **solo** sobre `verif\_restauracion\_%` (CREATE, DROP, INSERT, ALTER, INDEX, REFERENCES, LOCK TABLES) y `SELECT` sobre la base viva. Con eso, aunque el dump traiga `USE tecni_systemas`, el servidor rechaza la escritura.
4. **Restauración**: `proc_open([mysql, '--defaults-extra-file=…', '--one-database', '--default-character-set=utf8mb4', '--database='.$temp], [0=>archivo|stdin del .gz, 1=>pipe, 2=>pipe])`, drenando stdout y stderr con `stream_select` y con un tiempo máximo configurable (`BACKUP_VERIFY_TIMEOUT`, por defecto 600 s). Si el código de salida ≠ 0, FALLO con el stderr recortado a 2 KB.
5. **Verificación estructural** (temporal frente a manifiesto; si no hay manifiesto, frente a la viva):
   - Lista de tablas igual (excepto las técnicas).
   - `SELECT migration FROM migrations ORDER BY id` igual a la lista del manifiesto (versión de esquema).
   - Columnas: `information_schema.COLUMNS` (nombre, tipo, nullable, default, generación) por tabla. Índices: `STATISTICS` agrupados. FK: `REFERENTIAL_CONSTRAINTS` + `KEY_COLUMN_USAGE` con `DELETE_RULE`. Comparar como conjuntos y reportar las diferencias.
6. **Verificación de contenido**:
   - **Con manifiesto exacto**: `COUNT`, `MAX(id)`, `CHECKSUM TABLE … EXTENDED` y sumas de control (consultas de A.2 contra `verif_…`) deben ser **iguales** en todas las tablas. Cualquier diferencia es FALLO.
   - **Manifiesto no exacto o sin manifiesto (respaldos antiguos)**: modo tolerante contra la viva. En tablas que solo crecen por inserción, comparar `COUNT(*) WHERE id <= :max_id_respaldo` y `SUM(monto) WHERE id <= :max_id`. La igualdad se espera salvo que hayan cambiado filas antiguas (anulaciones), y eso se informa como ADVERTENCIA con el detalle de las filas cuyo `updated_at` es posterior a la fecha del respaldo:
     ```sql
     SELECT COUNT(*), COALESCE(SUM(monto),0) FROM `verif_x`.movimiento_cajas WHERE id <= ?;
     SELECT COUNT(*), COALESCE(SUM(monto),0) FROM `tecni_systemas`.movimiento_cajas WHERE id <= ?;
     SELECT COUNT(*) FROM `tecni_systemas`.movimiento_cajas WHERE id <= ? AND updated_at > ?;  -- explica las diferencias
     ```
   - **Invariantes de negocio sobre la temporal** (no dependen de la viva): las consultas de la sección 2 (huérfanos, `total_pagado` frente a la caja, ítems frente a total, secuencias ≥ máximo). Si fallan, el respaldo es restaurable pero contiene datos inconsistentes (ADVERTENCIA).
7. **Limpieza** (en `finally`, siempre): `DROP DATABASE IF EXISTS verif_…` (salvo `--conservar-temporal`), borrar los SQL descomprimidos y el `--defaults-extra-file`. Si el DROP falla, código de salida 2 y aviso explícito con el nombre de la base que queda pendiente.
8. **Reporte**: tablas de consola + `Evento::registrar('backup_verificado' | 'backup_verificacion_fallida', …)` con archivo, duración, modo (exacto/tolerante), nº de tablas, diferencias y sha256. Escribir también `storage/app/backups/<archivo>.verificado.json` para que `app:preflight` y la pantalla de respaldos muestren "último respaldo verificado: fecha/OK". Códigos de salida: 0 = OK exacto; 1 = FALLO (restauración, estructura, checksum o archivo); 3 = OK con advertencias (modo tolerante). Programarlo **después** de cada respaldo programado (`->then()` o `onSuccess`) y no solo los domingos.
9. **Manejo de errores**: cualquier excepción antes del `CREATE` → FALLO sin limpieza; después del `CREATE` → limpieza garantizada. Conexión PDO propia (no la por defecto) con `ERRMODE_EXCEPTION` y `MYSQL_ATTR_INIT_COMMAND='SET SESSION TRANSACTION READ ONLY'` para todas las lecturas sobre la viva. Nunca interpolar identificadores sin validarlos con `^[A-Za-z0-9_]+$` (hoy `comparar()` interpola `$viva` y `$tabla` sin validar; `$tabla` sale de `information_schema`, así que el riesgo es bajo, pero conviene validarlos).
10. **Pruebas**: un test con la conexión MySQL de integración (`phpunit-integration.xml`) que genere un respaldo, lo verifique (código 0), lo trunque a mitad (código 1), inyecte `USE otra_base;` (código 1, rechazado antes de restaurar) y compruebe que no queda ninguna base `verif_%`.

**Procedimiento manual equivalente (para `GUIA_RESPALDOS.md`)**
```
1. php artisan app:backup-db                        (genera el .sql y el .manifest.json)
2. php artisan app:verificar-respaldo               (esperado: código 0, "OK exacto")
3. Restauración real: php artisan down → Restaurar desde Configuración → php artisan migrate --force
   → php artisan cache:clear → php artisan app:verificar-integridad → php artisan up
4. PITR (si hace falta llegar a un punto posterior al respaldo):
   mysqlbinlog --start-position=<pos del manifiesto> --stop-datetime="AAAA-MM-DD HH:MM:SS" mysql-bin.0000NN | mysql tecni_systemas
```

---

## 6. Estrategia de migraciones (para `MIGRACIONES.md`)

### 6.1 Estado
62 migraciones, todas ejecutadas (lotes 1–5; lote 1 = 51, es decir, la base de producción se creó de cero con `migrate`). `app:verificar-migraciones` = 0 pendientes y 0 duplicados.

### 6.2 Riesgos en las existentes
- **Excepciones silenciadas** (`try { … } catch (Throwable) {}`) en `2026_10_21_170000` (unique de pivotes, índices nuevos, borrado de redundantes), `2026_07_15_070000`, `2026_07_12_000001` y `2026_07_13_020256`. La migración queda como "Ran" aunque el UNIQUE no se haya creado. `app:verificar-migraciones` solo busca duplicados y **no comprueba que la restricción exista** (D-19). En esta base sí existen.
- **Migraciones con lógica de datos**: `2026_10_21_160000` (backfill de secuencias), `2026_10_01_143000` (backfill de emails de técnicos), `2026_10_21_170000` (inserta una fila de `configuraciones` con NIT/teléfono ficticios en no-testing), `2026_10_01_133607` y `2026_10_28_190000` (vínculos `factura_id`), `2026_11_04_120000`, `2026_06_18_185859`, `2026_06_12_130509`, `2026_06_09_154817`.
- **Destructivas o irreversibles**: `2026_07_15_070000` borra `stocks.proveedor` (el `down()` lo vuelve a crear vacío); `2026_07_12_000002` recrea columnas generadas (pierde semántica en el `down`); `2026_07_07_134353` borra `clientes.nombre`.
- **`down()` que falla con datos reales**: `2026_09_09_200000:39,47` vuelve a poner `cliente_id NOT NULL` en `cotizaciones` y `equipos`, lo que es imposible si hay equipos de proveedor o cotizaciones de proveedor (D-21).
- **DDL fuera de migraciones**: `AppServiceProvider:81-92` ejecuta `Schema::table('configuraciones', …)` en cada arranque mientras no exista la clave de caché (D-18).
- **Pruebas solo en SQLite**: columnas `storedAs`, `->change()`, `ALTER … DROP DEFAULT`, FK RESTRICT y bloqueos no se ejercitan en MySQL (D-25).
- La migración de FK (`2026_11_05_120000`) dejó `SET NULL` en `movimiento_cajas.abono_id/factura_id/parent_id` (D-12).

### 6.3 Convención recomendada
1. **Una migración = un cambio de esquema**, reversible y sin `try/catch` mudos. Si hace falta tolerar un entorno heredado, comprobar antes (`Schema::hasIndex`, `hasColumn`) y **lanzar** una excepción clara si los datos lo impiden (por ejemplo, duplicados): mejor un `migrate` que falla con el mensaje "ejecute app:verificar-migraciones" que una base sin la restricción.
2. **Cambios de datos en comandos idempotentes** (`app:backfill-*`) o en migraciones marcadas `/** @data */` que: usen `DB::table` (nunca modelos, que cambian con el tiempo), trabajen en `chunkById`, registren cuántas filas tocaron y tengan un `down()` explícito o declaren que son irreversibles (`throw new IrreversibleMigrationException`).
3. **Patrón expand → migrate → contract** para renombres, cambios de tipo o borrados: (a) agregar la columna nueva, (b) backfill y doble escritura en el código, (c) desplegar, (d) borrar la columna vieja en otra versión. Nunca `dropColumn` en la misma versión que cambia el código.
4. **Nombres explícitos** de índices, unique y FK (`idx_<tabla>_<cols>`, `uq_…`, `fk_…`) para poder borrarlos y verificarlos sin adivinar.
5. **Antes de `migrate --force` en producción**: `app:preflight` → `app:verificar-migraciones` → `app:backup-db` → `app:verificar-respaldo` (código 0) → `php artisan down` → `migrate --force` → `app:verificar-esquema` (nuevo: compara `information_schema` con un `database/schema/esquema_esperado.json` generado en CI) → `up`. `gitUpdate` en `BackupController:862` ejecuta `migrate --force` sin respaldo previo ni modo mantenimiento: debe seguir esta secuencia.
6. **Squash** (`php artisan schema:dump --prune`) cuando se estabilice la 1.x: reduce 62 archivos a un `mysql-schema.sql` y elimina el riesgo de reproducir historiales con lógica de datos en instalaciones nuevas (mantener los tests SQLite con su propio dump).
7. **CI con MySQL 8.4** (servicio Docker) que ejecute `migrate:fresh`, `migrate:rollback --step=5`, `migrate` y el suite de integración.
8. Nada de DDL en `boot()`, controladores ni comandos que no sean migraciones.

---

## 7. Hallazgos (por severidad)

### D-01 · ALTA · La restauración no es atómica ni comprueba la versión del esquema
- **Archivo**: `app/Http/Controllers/BackupController.php:446-513, 581-664, 862`.
- **Evidencia**: `restoreSqlFile` lanza `mysql < archivo` sobre la base viva. No hay `php artisan down`, no se compara la tabla `migrations` del dump con la del código y no se ejecuta `migrate` después. Las tablas que no vienen en el dump (creadas por migraciones posteriores al respaldo) quedan con sus datos actuales.
- **Escenario**: se restaura un respaldo del 01/10 (anterior a `secuencias_documento`, `anulado_en_cascada` y `must_change_password`). El dump borra y recrea `users` sin `must_change_password`, `abonos` sin `anulado_en_cascada`, etc. La tabla `migrations` restaurada dice que esas migraciones no se ejecutaron, pero `secuencias_documento` sigue con los valores de hoy. El código actual falla con "Unknown column" en login y en abonos hasta que alguien ejecute `migrate`. Si `mysql` falla a mitad de camino (disco, `max_allowed_packet`, corte), la base queda mezclada entre tablas nuevas y viejas mientras los usuarios siguen operando.
- **Corrección**: (1) `Artisan::call('down', ['--secret'=>…])` antes de restaurar y `up` en `finally`; (2) leer del archivo las filas de `migrations` (o del manifiesto, §5.3) y, si son anteriores a las del código, avisar y ejecutar `migrate --force` justo después; si son **posteriores**, rechazar; (3) restaurar primero en una base temporal (los pasos B.3–B.6) y solo si verifica bien, cambiar con `RENAME TABLE viva.t TO viva_old.t, temp.t TO viva.t` (atómico por sentencia en MySQL), conservando `viva_old_<fecha>` hasta confirmar; (4) después de restaurar: `cache:clear` y `app:verificar-integridad`.

### D-02 · ALTA · La rotación y la retención pueden borrar todos los respaldos de BD y archivos ajenos en Drive
- **Archivo**: `app/Console/Commands/BackupDatabase.php:536-561, 567-605`.
- **Evidencia**: `aplicarPoliticaRotacion` ordena por `mtime` **todos** los `.sql/.zip/.gz` de `storage/app/backups` (incluidos `code_*.zip` y `temp_db_*.sql` huérfanos) y conserva solo los `$maxCopies` (10 por defecto) más recientes. `limpiarBackupsAntiguos($drivePath, …)` borra **cualquier** `.sql/.zip/.gz` de la carpeta de Drive con más de 15 días.
- **Escenario**: el administrador genera 10 snapshots de código desde la pantalla de respaldos (o el cron está configurado con `backup_tipo_incluido=code`) y la rotación elimina los últimos respaldos de base de datos. En Drive, si `GOOGLE_DRIVE_BACKUP_PATH` apunta a una carpeta compartida, se borran ZIPs de otras personas.
- **Corrección**: rotar por tipo (patrón de nombre `db_*`, `backup_bd_multimedia_*`, `backup_integral_*`, `code_*`) con un cupo propio para cada uno. Excluir `temp_*` y los respaldos de seguridad previos a una restauración (prefijo propio `pre_restore_*` con retención aparte). Exigir siempre al menos N respaldos de BD verificados. En Drive, borrar solo los archivos que coincidan con `^(db|backup_bd_multimedia|backup_integral|code)_<db>_\d{4}-…` y usar una subcarpeta exclusiva.

### D-03 · ALTA · No hay forma de demostrar que un respaldo se puede restaurar; el verificador en curso no es concluyente y puede escribir en la base viva
- **Archivos**: `BackupDatabase.php:306-333` (sin manifiesto, checksum ni comprobación del pie); `app/Console/Commands/VerificarRespaldo.php` (sin seguimiento en git) líneas 47-51, 101-104, 114-117, 32-36.
- **Evidencia**: ver la tabla del §5.3 (a–h).
- **Escenario 1**: con un `code_*.zip` como archivo más reciente, la verificación semanal falla (falso negativo) o, al revés, se acostumbra a "⚠ hay N diferencias (normal…)" y una corrupción real pasa desapercibida. **Escenario 2**: un `.sql` generado con `mysqldump --databases tecni_systemas` (o subido a mano a la carpeta) contiene `USE \`tecni_systemas\``, y el "restaurar en la temporal" sobrescribe la base viva con el contenido del respaldo.
- **Corrección**: implementar el diseño completo del §5.3 (manifiesto al respaldar; verificación exacta contra el manifiesto; `--one-database`, escaneo de `USE/CREATE DATABASE`; usuario MySQL acotado; pie y sha256; registro en eventos; ejecución tras cada respaldo).

### D-04 · ALTA (rendimiento a mediano plazo) · `whereDate/whereMonth/whereYear` anulan los índices de fecha
- **Archivos**: 67 apariciones en 11 archivos. Las de mayor impacto: `MovimientoCajaController.php:43-44,127-128`, `EventoController.php:44-45`, `ReporteFinancieroController.php` (20), `ReporteController.php` (7), `DashboardController.php:45-46`, `CierreCajaGuard.php:27`, `MantenimientoController.php:97-98`, `ElectronicaController.php`, `StockController.php`, `MovimientoInventarioController.php`.
- **Evidencia (EXPLAIN real)**: `where date(fecha) >= '2026-09-01' and date(fecha) <= '2026-11-30'` da `type=ALL, possible_keys=NULL`. La misma consulta con `fecha >= … and fecha <= …` da `possible_keys=idx_movcaja_fecha`. `select * from cierre_cajas where date(fecha)=…` da `ALL` (no usa el UNIQUE).
- **Escenario**: con 3 años de operación (unos 50 000 movimientos y más de 300 000 eventos), cada carga del listado de caja o de eventos hace 2 full scans (datos + `COUNT(*)` del paginador). El guard de cierre, que se ejecuta 2–6 veces en cada alta o edición de caja, también hace full scan de `cierre_cajas`.
- **Corrección**: en columnas `DATE` usar `where('fecha', '>=', $desde)->where('fecha', '<=', $hasta)` o `whereBetween('fecha', [$desde, $hasta])` con fechas `Y-m-d`. En `TIMESTAMP` (`eventos.created_at`) usar `where('created_at','>=',"$desde 00:00:00")->where('created_at','<',Carbon::parse($hasta)->addDay()->toDateString())`. `CierreCajaGuard`: `CierreCaja::where('fecha', $fecha)->exists()`. Un scope `scopeEntreFechas($q, $col, $desde, $hasta)` reutilizable evita repetirlo. Para SQLite en los tests no hace falta `whereDate` en columnas `date` si se guardan como `Y-m-d`.

### D-05 · ALTA · N+1 con `LIKE '%…'` en los accessors de MovimientoCaja (listado de caja, topbar)
- **Archivos**: `app/Models/MovimientoCaja.php:107-150 (getTotalPagadoAttribute), 152-187 (getEffectiveMontoTotalAttribute), 189-197`; `resources/views/caja/index.blade.php:182-189`; `app/Providers/AppServiceProvider.php:229-258`; `app/Models/Factura.php:150-166`.
- **Evidencia**: EXPLAIN de la consulta de `total_pagado` con ref da `type=ALL`. La precarga de `childPayments` se ignora si la descripción tiene una referencia (línea 118). `saldo_pendiente` no se memoiza y la vista lo evalúa 2 veces por fila. El topbar evalúa `saldo_pendiente` 2 veces por cada uno de hasta 50 movimientos, con caché de solo 30 s por usuario. En los datos actuales hay **0** movimientos que dependan del vínculo legacy por descripción (consulta `i4.php`).
- **Escenario**: con 50 000 movimientos, abrir la caja ejecuta unas 20 consultas de full scan (con 30 ms cada una son unos 0,6 s) y cada usuario conectado dispara unas 100 cada 30 s desde el layout.
- **Corrección**: (1) backfill definitivo de `factura_id` / `parent_id` en un comando idempotente (hoy no hay filas pendientes) y **eliminar la rama `LIKE`** de `total_pagado`, `effective_monto_total`, `recalcularPagos` y el topbar, dejando solo los vínculos por FK; (2) calcular en SQL con `withSum(['childPayments as abonos_sum' => fn($q)=>$q->activos()], 'monto')` y una subconsulta para el total del documento; (3) memoizar los accessors (`$this->attributes['_total_pagado'] ??= …` o `Attribute::make(...)->shouldCache()`); (4) en el topbar, una sola consulta agregada (`HAVING monto_total - (monto + abonos_sum) > 0.01`) y caché global (no por usuario) invalidada por evento de modelo.

### D-06 · ALTA · Carrera anular ↔ abonar: no se revalida tras el bloqueo y `recalcularPagos` guarda un estado obsoleto
- **Archivos**: `app/Http/Controllers/MovimientoCajaController.php:459-465, 513-516, 581`; `app/Traits/HandlesAbono.php:34-37, 63-64`; `app/Models/Factura.php:142-145, 168-200`.
- **Evidencia**: `storeAbono` (caja) comprueba `$movimiento->anulado` y `factura->estado` **antes** de la transacción. Después de `lockForUpdate()` usa `$lockedMov->saldo_pendiente`, pero no vuelve a mirar `$lockedMov->anulado` ni el estado de la factura. `HandlesAbono::storeAbono` hace lo mismo con la orden (`$lockedModel->anulado` no se revisa). `recalcularPagos()` ejecuta `static::where('id',$this->id)->lockForUpdate()->first()` y **descarta el resultado**, así que trabaja con `$this->estado/total_documento/observaciones` cargados antes. `save()` escribe los atributos que cambian respecto a esa copia vieja.
- **Escenario**: el admin anula VT-2 (`anularFactura`: bloquea la factura, marca los movimientos `anulado=1`, `estado='anulada'`) mientras un cajero registra un abono a VT-2 desde caja. El cajero pasó la comprobación previa; su transacción espera el bloqueo del movimiento y, al obtenerlo, crea el movimiento hijo **activo** e invoca `recalcularPagos()` sobre una instancia con `estado='pendiente_pago'`. Resultado: factura anulada con `estado` vuelto a `emitida`/`pendiente_pago` y dinero activo en caja de un documento anulado. Lo mismo con la orden: abono activo sobre una orden anulada.
- **Corrección**: después de cada `lockForUpdate`, revalidar sobre la fila bloqueada (`if ($lockedMov->anulado || $lockedMov->estado!=='activo') rollback`; en órdenes, `if ($lockedModel->anulado)`), y bloquear también la factura vinculada en el **mismo orden** que `anularFactura` (factura → movimiento). En `recalcularPagos()`: `$fresh = static::whereKey($this->id)->lockForUpdate()->first(); $this->setRawAttributes($fresh->getAttributes(), true);` antes de calcular, o calcular con una sola sentencia `UPDATE facturas SET total_pagado=(SELECT …) WHERE id=? AND estado<>'anulada'`. Agregar un test de concurrencia en MySQL (dos conexiones) a la batería de integración.

### D-07 · ALTA · La aplicación usa `root` con privilegios globales
- **Archivo**: `.env` (`DB_USERNAME=root`); `SHOW GRANTS`: `SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, RELOAD, SHUTDOWN, PROCESS, FILE, … SUPER … BACKUP_ADMIN, BINLOG_ADMIN, CLONE_ADMIN …`.
- **Escenario**: una inyección SQL o un RCE en la app equivale a control total del servidor MySQL: `SELECT … INTO OUTFILE` (FILE) para escribir archivos en disco, `SHUTDOWN`, `DROP` de cualquier base, lectura de otras bases de ServBay.
- **Corrección**: crear `tecni_app@localhost` con `SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE` sobre `tecni_systemas.*`; `tecni_migra` (más `CREATE, ALTER, DROP, INDEX, REFERENCES`) solo para `migrate`; `tecni_backup` (`SELECT, LOCK TABLES, SHOW VIEW, TRIGGER, EVENT, PROCESS, RELOAD, REPLICATION CLIENT`) para mysqldump; `respaldo_verif` (§5.3 B.3). Coordinar con el agente de seguridad.

### D-08 · MEDIA · El cierre de caja no se serializa con las altas y ediciones de movimientos del mismo día
- **Archivos**: `CierreCajaController.php:74-121`; `CierreCajaGuard.php:21-28`; usos en `MovimientoCajaController:226,363,522`, `HandlesAbono:72`, `MovimientoInventarioController:699`.
- **Evidencia**: el guard es un `exists()` sin bloqueo. `store()` del cierre bloquea solo el hueco del UNIQUE(fecha) de `cierre_cajas` y lee los movimientos con lectura consistente (snapshot).
- **Escenario**: a las 18:00 el cajero cierra el día mientras otro usuario registra un ingreso con la misma fecha. El guard del segundo no ve el cierre (aún sin commit), el cierre no ve el movimiento (snapshot) y, tras ambos commits, el arqueo bloqueado excluye un ingreso real: el día "cuadrado" queda descuadrado y es inmodificable.
- **Corrección**: en el guard dentro de transacción usar `CierreCaja::where('fecha',$fecha)->sharedLock()->exists()` (S-lock sobre el registro o hueco del UNIQUE, que entra en conflicto con el insert X del cierre), o un bloqueo con nombre `SELECT GET_LOCK(CONCAT('caja:',?), 10)` tanto en el cierre como en toda escritura de caja de esa fecha. En el cierre, leer los movimientos con `lockForUpdate()` / `sharedLock()` para que esperen a las transacciones en curso.

### D-09 · MEDIA · `updateFactura` sin bloqueo de la factura; bloqueos de stock sin orden; orden de bloqueo inverso
- **Archivo**: `MovimientoInventarioController.php:616, 693-731, 738-804, 852-929`; `MovimientoCajaController.php:513 → Factura::recalcularPagos`.
- **Evidencia**: `$factura->estado === 'anulada'` se evalúa con el modelo del route binding, fuera de la transacción, y nunca se bloquea la fila de la factura. Los stocks se bloquean en el orden de los ítems del request (a diferencia de `registrarVenta`, que usa `ksort`). En caja el orden es movimiento → factura; en `anularFactura` es factura → movimientos.
- **Escenario**: (a) se edita VT-2 mientras otro usuario la anula: la edición recalcula y deja `estado='pendiente_pago'` (se pierde la anulación) y descuenta stock otra vez. (b) Dos ediciones o ventas con los mismos productos en distinto orden provocan un deadlock; InnoDB aborta una y el usuario ve "Error al actualizar la factura" (sin reintento).
- **Corrección**: primera sentencia de la transacción `Factura::whereKey($id)->lockForUpdate()->firstOrFail()` y revalidar `estado`; reunir todos los `stock_id` (viejos y nuevos), ordenarlos y bloquearlos al principio; adoptar el orden canónico del §4; envolver en `DB::transaction($fn, 3)` para reintentar ante deadlock (1213).

### D-10 · MEDIA · Opciones de mysqldump y contenido del respaldo
- **Archivo**: `BackupDatabase.php:306-320`.
- **Evidencia**: el dump real contiene `LOCK TABLES` (62 apariciones) y no tiene `--single-transaction`, `--source-data` ni `--routines/--events`. Incluye `sessions` (6 filas), `cache` (13 filas) y `jobs`. La contraseña va en `MYSQL_PWD`. No hay compresión ni hash.
- **Escenario**: durante el dump nocturno las escrituras de la app esperan; con la base grande, un cajero que trabaja a las 02:00 ve timeouts. Tras un desastre a las 17:00 solo se puede volver a las 02:00 aunque el binlog tiene todo (falta la posición de inicio). Restaurar revive las sesiones y los contadores de intentos de login.
- **Corrección**: las opciones del §5.3 A.3, con dos pasos (datos de negocio y `--no-data` de las tablas técnicas), `--defaults-extra-file`, gzip, sha256 y manifiesto.

### D-11 · MEDIA · Precisión monetaria inconsistente (10,2 frente a 12,2)
- **Evidencia**: §1.2. `mantenimientos.costo`, `electronicas.costo`, `mantenimiento_stock.precio_unitario` y `stocks.precio_*` son `decimal(10,2)` (máx. 99.999.999,99), mientras que facturas, caja y abonos (12,2) y `electronica_stock` (12,2) no lo son.
- **Escenario**: con `STRICT_TRANS_TABLES`, `HandlesStockAttach:64` `increment('costo', …)` sobre una orden que supere 99.999.999,99 (por ejemplo, una orden con varios equipos o un servidor) aborta con "Out of range value". Un repuesto caro registrado por `factura_items` (12,2) no cabe en `stocks.precio_compra`.
- **Corrección**: una migración `->decimal(12,2)->change()` en las 7 columnas (ALTER instantáneo no; con tablas pequeñas es inmediato), con su `down` a 10,2 protegido por una comprobación de `MAX(col) < 1e8`. Unificar los casts `decimal:2` (deuda nº 9 ya documentada).

### D-12 · MEDIA · FK `SET NULL` en los vínculos contables de `movimiento_cajas`
- **Evidencia**: `abono_id → abonos SET NULL`, `factura_id → facturas SET NULL`, `parent_id → movimiento_cajas SET NULL` (§1.4). La migración `2026_11_05_120000` las dejó fuera de su lista.
- **Escenario**: un borrado físico (tinker, SQL manual, un futuro `forceDelete` de factura) de un abono o de un movimiento padre deja sus movimientos **activos** sin vínculo. Pasan a contar como ingresos sueltos y `parent_id=NULL` convierte cada abono hijo en un movimiento raíz con su propio saldo, lo que duplica totales y saldos sin dejar rastro.
- **Corrección**: migración que pase las tres a `RESTRICT`, coherente con la política de "solo se anula". `factura_id` puede quedarse en SET NULL solo si se documenta que las facturas nunca se borran físicamente (hoy usan SoftDeletes y nadie las borra).

### D-13 · MEDIA · Sin restricciones CHECK para las invariantes de negocio
- **Evidencia**: `information_schema.CHECK_CONSTRAINTS` está vacío (MySQL 8.4 las aplica).
- **Corrección** (migración con verificación previa de datos):
  ```sql
  ALTER TABLE movimiento_cajas ADD CONSTRAINT chk_mov_monto CHECK (monto > 0),
    ADD CONSTRAINT chk_mov_estado CHECK ((anulado = 1) = (estado = 'anulado'));
  ALTER TABLE abonos ADD CONSTRAINT chk_abono_monto CHECK (monto > 0),
    ADD CONSTRAINT chk_abono_orden CHECK ((mantenimiento_id IS NULL) <> (electronica_id IS NULL));
  ALTER TABLE equipos ADD CONSTRAINT chk_equipo_duenio CHECK ((cliente_id IS NULL) <> (proveedor_id IS NULL));
  ALTER TABLE stocks ADD CONSTRAINT chk_stock_cant CHECK (cantidad >= 0);
  ALTER TABLE facturas ADD CONSTRAINT chk_fact_montos CHECK (total_documento >= 0 AND total_pagado >= 0);
  ALTER TABLE mantenimientos ADD CONSTRAINT chk_mant_costo CHECK (costo >= 0);  -- ídem electronicas
  ```
  Nota: `chk_mov_monto` es incompatible con `updateFactura:887` (`monto => max(0, …)`, que puede guardar 0); hay que revisar ese flujo antes (agente financiero). SQLite ignora `ALTER … ADD CONSTRAINT`, así que la migración debe aplicarse solo en MySQL.

### D-14 · BAJA · Índices redundantes y de baja cardinalidad
- **Evidencia**: §1.5 (`idx_elec_fecha_entrada`, `eventos_modelo_tipo_modelo_id_index`, más 4 booleanos de una sola columna).
- **Corrección**: eliminar los dos redundantes. Antes de quitar los booleanos, revisar `sys.schema_unused_indexes` tras un mes de uso.

### D-15 · MEDIA · Índices faltantes y ORDER BY sin índice en tablas que crecen
- **Evidencia**: el topbar usa `ORDER BY created_at` sobre `movimiento_cajas` (filesort, EXPLAIN). Los filtros de eventos por usuario y acción con orden por fecha usan `ref eventos_user_id_foreign` + filesort. `saldoInicialEfectivo` hace `ALL`.
- **Corrección**: `latest('id')` en lugar de `latest()` en el topbar y en eventos (el id es monótono y está en el PK); índices `eventos(user_id, created_at)` y `eventos(accion, created_at)`; índice cubriente para el saldo inicial `movimiento_cajas(tipo_pago, anulado, estado, fecha, tipo_movimiento, monto)`; o, mejor, apoyar `saldoInicialEfectivo` en el último cierre (que ya guarda `efectivo`) y no recorrer toda la historia cuando no hay conteo.

### D-16 · MEDIA · `eventos` crece sin límite y la restauración la carga entera en memoria
- **Archivos**: `app/Traits/Auditable.php` (un evento por cada created/updated/deleted de los modelos auditados); `BackupController.php:565-575, 667-699`.
- **Evidencia**: la restauración hace `DB::table('eventos')->get()` antes y después y compara cada evento viejo con la colección restaurada con `first()` (O(n·m)), insertando uno a uno sin transacción. Los ids de los eventos re-insertados cambian.
- **Escenario**: con 300 000 eventos, unos 300 000² comparaciones y cientos de MB de memoria: la petición HTTP de restaurar agota `memory_limit` o `max_execution_time` **después** de haber sobrescrito la base, y la auditoría posterior al respaldo se pierde.
- **Corrección**: excluir `eventos` del dump de restauración o volcarla a `eventos_preservados` antes (`CREATE TABLE … AS SELECT` en la misma base, que el dump no toca) y reinsertar con `INSERT … SELECT … WHERE NOT EXISTS (…)` en SQL. Política de retención o archivado (por ejemplo, mover a `eventos_archivo` los mayores de 2 años con un comando programado). Ejecutar la restauración como comando de consola o job, no dentro de la petición HTTP.

### D-17 · BAJA · `OrdenService` recorre todos los números en cada documento e ignora las facturas borradas lógicamente
- **Archivo**: `app/Services/OrdenService.php:36, 98-121`.
- **Evidencia**: `Schema::hasTable()` (consulta a `information_schema`) en cada llamada. `obtenerMaximoExistente` hace `pluck` de todos los `id_orden`/`numero_factura` del prefijo. `$modelo::query()` en `Factura` aplica el scope de SoftDeletes, mientras que el UNIQUE de `numero_factura` incluye las borradas.
- **Escenario**: si alguna vez se borra lógicamente VT-10 y la secuencia queda atrás (por ejemplo, tras restaurar un respaldo antiguo de `secuencias_documento`), se genera VT-10 otra vez y el insert falla por UNIQUE.
- **Corrección**: `withoutGlobalScopes()` (o `withTrashed()` si existe), calcular el máximo en SQL (`MAX(CAST(SUBSTRING(col, LENGTH(?)+1) AS UNSIGNED))`) y cachear `hasTable` en una propiedad estática.

### D-18 · MEDIA · DDL en tiempo de ejecución dentro de `AppServiceProvider::boot`
- **Archivo**: `app/Providers/AppServiceProvider.php:81-92`.
- **Evidencia**: si no existe la clave de caché `schema_config_formato_checked`, cada petición consulta `information_schema` y puede ejecutar `ALTER TABLE configuraciones ADD formato_factura`. La caché usa el driver `database`: tras restaurar un respaldo o ejecutar `cache:clear`, vuelve a evaluarse.
- **Escenario**: un `ALTER` implícito (con commit implícito) en medio de una petición, lanzado por el primer visitante, con `root`; diverge del historial de migraciones.
- **Corrección**: eliminar el bloque (la migración `2026_09_09_165000` ya crea la columna) y apoyarse en `app:preflight`, que debería avisar si hay migraciones pendientes.

### D-19 · MEDIA · Migraciones que tragan errores y un verificador que no comprueba lo que crearon
- **Archivos**: `2026_10_21_170000_optimize_database_schema_and_indexes.php:39-75, 90-123`; `VerificarMigraciones.php:31-37`.
- **Escenario**: en otra instalación (sucursal, reinstalación desde un respaldo con duplicados) `uq_mantenimiento_stock` no se crea, la migración queda como "Ran" y `app:verificar-migraciones` (que solo busca duplicados **antes** de migrar) sigue en verde. El código asume esa unicidad (`updateExistingPivot`).
- **Corrección**: §6.3 punto 1, más una sección en `app:verificar-migraciones` (o un `app:verificar-esquema` nuevo) que confirme en `information_schema.STATISTICS`/`TABLE_CONSTRAINTS` la existencia de los índices, UNIQUE y FK esperados (`uq_mantenimiento_stock`, `uq_electronica_stock`, `uq_categoria_stocks`, `uq_configuraciones_nombre`, `idx_eventos_created_at`, …).

### D-20 · BAJA · Migraciones con lógica de datos y datos ficticios
- **Evidencia**: §6.2 (inserta configuración con NIT `900.000.000-1` y dirección ficticia en no-testing; backfills dentro de migraciones de esquema; `dropColumn` destructivos).
- **Corrección**: §6.3 puntos 2–3. Revisar que `configuraciones` de producción no tenga los valores ficticios (`app:preflight`).

### D-21 · BAJA · `down()` imposible de ejecutar con datos de proveedor
- **Archivo**: `database/migrations/2026_09_09_200000_add_proveedor_id_to_cotizaciones_and_equipos_tables.php:39, 47`.
- **Escenario**: `migrate:rollback` con equipos o cotizaciones de proveedor da "Invalid use of NULL value" y deja el rollback a medias (el `dropColumn('proveedor_id')` ya se ejecutó).
- **Corrección**: en `down()`, abortar con `IrreversibleMigrationException` si existe `cliente_id IS NULL`, o no restaurar el NOT NULL.

### D-22 · MEDIA · Dashboard y reportes calculan totales históricos en cada carga
- **Archivos**: `DashboardController.php:43-46, 193`; `ReporteFinancieroController.php:333-389`.
- **Evidencia**: sumas sobre toda la historia de caja sin caché; carga completa de clientes; `reporteAcumulado` carga en memoria todos los movimientos con `monto_total>0` del período y busca coincidencias por `str_contains` (O(n·m), y además "VT-1" coincide con "VT-10").
- **Corrección**: caché de 60 s (o invalidación por evento) para los KPIs globales; selector de clientes por AJAX con búsqueda y límite; sustituir el bucle por un `LEFT JOIN facturas f ON f.id = m.factura_id` (una vez completado el backfill de D-05) y `whereIn` por subconsultas.

### D-23 · BAJA · Exportaciones y PDF sin streaming
- **Archivos**: `MantenimientoController:173,178`, `ElectronicaController:354,360`, `StockController:272,281`, `ReporteFinancieroController:622-645`.
- **Corrección**: `FromQuery` + `chunkSize` en Maatwebsite Excel; `lazyById()` en los PDF; límites de rango obligatorios.

### D-24 · BAJA · Zona horaria de la sesión MySQL implícita
- **Evidencia**: `config/database.php` sin `'timezone'`, `@@time_zone=SYSTEM` (Windows UTC-5) y `config('app.timezone')='America/Bogota'`.
- **Escenario**: al migrar a un servidor Linux o nube en UTC, los `TIMESTAMP` (`created_at`, `fecha_baja`) se leen desplazados 5 horas; los filtros "hoy" de eventos y de cierres pierden registros nocturnos.
- **Corrección**: `'timezone' => env('DB_TIMEZONE', '-05:00')` en la conexión mysql.

### D-25 · BAJA · Las pruebas (SQLite) no cubren el comportamiento propio de MySQL
- **Evidencia**: 604 tests en SQLite en memoria; `BackupDatabase` escribe un `.sql` ficticio con SQLite (`:280-290`); `CierreCajaController:154-160` tiene ramas por motor.
- **Corrección**: un job de CI con MySQL 8.4 para la suite `phpunit-integration.xml`: migraciones fresh/rollback, `EXPLAIN` de regresión de las consultas clave (que `possible_keys` no sea NULL), concurrencia (D-06/D-08/D-09) y el ciclo respaldo → verificación (§5.3 B.10).

### D-26 · BAJA (informativo) · La base de producción contiene los datos de demostración
- **Evidencia**: 5 filas por tabla, 3 usuarios por defecto con `must_change_password=1`, 10 ingresos "Ingreso por servicio técnico ORD-n" sin abono (`SeedDemoData.php:363`), así que las órdenes muestran el saldo total pendiente aunque haya ingresos en caja.
- **Corrección**: antes de entrar en producción, `app:clean-data` (con confirmación) y respaldo verificado. Corregir el seeder para que cree esos cobros con `HandlesAbono` (abono + movimiento vinculado) y la demo sea coherente.

---

## 8. Revisado y correcto
- Charset/collation uniformes; InnoDB; STRICT; sin FLOAT en dinero; columnas generadas `saldo_pendiente/saldo_a_favor/subtotal` correctas.
- UNIQUE de negocio presentes (id_orden, numero_factura, codigo, identificaciones, serie, codigo de stock, fecha de cierre, pivotes).
- 26 FK en RESTRICT tras `2026_11_05_120000`; `migrations` sin pendientes; sin duplicados.
- `OrdenService` (bloqueo de fila de secuencia, sincroniza con el máximo existente); `StockService` (vuelve a leer con bloqueo); `registrarVenta` (orden canónico de bloqueos); `CierreCajaController::store` (bloqueo sobre el UNIQUE de fecha contra dos cierres); anulación de órdenes y facturas (bloqueo + intención explícita); `HandlesStockAttach` (bloqueo de la orden + increment).
- `calcularDia` usa `where('fecha')` (sargable) en MySQL; paginación en los listados principales; eager loading en los index de mantenimientos y electrónicas.
- Dump real: `SET NAMES utf8mb4`, `TIME_ZONE='+00:00'`, `DROP TABLE IF EXISTS`, pie completo, sin `USE`. Restauración con respaldo de seguridad previo y protección Zip Slip.

## 9. No revisado o revisado parcialmente
- No se generó un esquema "fresco" a partir de las migraciones en MySQL para hacer un diff automático (habría exigido crear una base en el servidor). La comparación migración ↔ esquema se hizo leyendo los archivos (tipos decimales, FK, índices nombrados).
- No se midieron tiempos reales con volumen (los datos son de demo); los planes de EXPLAIN se interpretaron con `possible_keys` y el tipo de acceso.
- `CotizacionController::convertir` (bloqueo de stock sin ordenar, línea 364: mismo patrón que D-09), `SystemAuditCommand`, `CleanDatabase`, `ResetTests`, `ReporteController`, `GuestController` y las vistas distintas de caja y topbar: solo lectura parcial.
- Exportaciones Excel y PDF y el detalle financiero de los reportes quedan para los agentes Excel/PDF y financiero (se avisan aquí D-22 y D-23 y el defecto de prefijo "VT-1" ⊂ "VT-10" en `reporteAcumulado:352`).
- Seguridad de `gitUpdate`, `openFolder` y la descarga de respaldos: corresponde al agente de seguridad (aquí solo se menciona D-07 y la falta de respaldo previo antes de `migrate --force` en `gitUpdate:862`).

## 10. Archivos revisados
`app/Console/Commands/BackupDatabase.php`, `app/Console/Commands/VerificarRespaldo.php` (sin seguimiento en git), `app/Console/Commands/VerificarMigraciones.php`, `app/Console/Commands/SeedDemoData.php` (parcial), `app/Http/Controllers/BackupController.php` (restore, restoreSqlFile, gitUpdate parcial), `MovimientoCajaController.php`, `CierreCajaController.php`, `MovimientoInventarioController.php` (anularFactura, updateFactura, registrarVenta parcial), `MantenimientoController.php` (index, reportes, duplicate), `ElectronicaController.php` (grep), `DashboardController.php`, `ReporteFinancieroController.php` (diario y acumulado), `EventoController.php`, `StockController.php` (grep), `app/Models/MovimientoCaja.php`, `Factura.php`, `CierreCaja.php`, `Mantenimiento.php` (accessors), `Stock.php` (parcial), `Evento.php` (registrar), `app/Services/OrdenService.php`, `CierreCajaGuard.php`, `StockService.php` (grep), `AnulacionService.php` (grep), `app/Traits/HandlesAbono.php`, `HandlesStockAttach.php` (grep), `CalculaCostoOrden.php`, `app/Providers/AppServiceProvider.php` (boot y composer), `config/backup.php`, `config/database.php`, `config/app.php` (zona horaria), `routes/console.php`, migraciones (las 62 con grep; leídas completas: `2026_10_21_170000`, `2026_07_12_000002`, `2026_11_05_120000`, `2026_10_21_160000`, `2026_07_15_070000`, `2026_09_09_200000`), dump `storage/app/backups/db_tecni_systemas_2026-11-13_02-41-32.sql` (cabecera y pie).
