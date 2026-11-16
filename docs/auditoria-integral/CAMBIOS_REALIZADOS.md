# Cambios realizados en la auditoría integral (2026-11-13)

Base de comparación: `origin/main` en `1a4982b`. Cada bloque es un commit independiente y lleva sus pruebas. La suite completa quedó en **628 pruebas / 2316 aserciones en verde**.

## Precaución previa (sección 17 del encargo)

- **Antes de modificar se respaldó y se comprobó la recuperación.**
  - Respaldo `db_tecni_systemas_2026-11-13_02-41-32.sql` restaurado en una base temporal: 31 tablas, 35 FK, 62 migraciones y sumas idénticas. La base temporal se eliminó después.
  - Después de endurecer el volcado se generó y verificó `db_tecni_systemas_2026-11-13_02-54-41.sql` con `app:verificar-respaldo`.
- **No se escribieron datos en la base MySQL real.**
  - Las pruebas corren en SQLite en memoria.
  - La vista previa del navegador usó una base SQLite desechable.
  - Las lecturas de la base real fueron en sesión `READ ONLY` (conteos) o de solo lectura (`app:verificar-respaldo` solo lee la base viva).
- **No se manejaron contraseñas reales** ni se imprimieron secretos del `.env`.

## Commits

| Commit | Bloque | Hallazgos |
|---|---|---|
| `ebe027f` | La prueba del selector de stock no depende del orden de las clases CSS | — |
| `33a1fc2` | `app:verificar-respaldo`: restaura en una base temporal y compara | D-03 (inicio) |
| `7b79b83` | Pruebas aisladas del storage real, de los logs y de Drive | O-03, S-02, Q-01 |
| `3769102` | Rotación y retención por tipo con mínimo de 3 copias; mysqldump transaccional; verificación estricta | D-02, D-03, D-10, O-07, O-11 |
| `eb6769e` | El invitado ya no ve montos en páginas de error; cambio de clave solo cuando es obligatorio; `app:restablecer-password` | F-01, S-05, S-01 (mitigación) |
| `fadc37a` | Pagos solo por `factura_id`; la edición de factura no reescribe caja; período monótono; cierres cronológicos; idempotencia | M-01 a M-08, M-10 |
| `569c4f1` | Sobrepago oculto al quitar repuestos; último administrador; exportaciones completas; abonos anulados en facturas; modal de notificaciones | B-02, B-12, D-06, X-01, X-03, F-02, F-03 |
| `4597a23` | "Actualizar desde GitHub" respalda antes de migrar y reporta los fallos | O-04 |
| `bb0385a` | Una venta a crédito se puede cobrar después del cierre de su día: solo se valida la fecha del abono | Q-10 |
| (este commit) | Carpeta `docs/auditoria-integral/` con los 18 entregables + 3 pruebas de flujo completo del agente 8 | — |

## Detalle por área

### Finanzas

**Vínculo de pagos a facturas (M-01/M-02)**
- Los pagos se vinculan a la factura **solo por `factura_id`**, y los abonos por `parent_id`.
- Se eliminó el vínculo por texto `#VT-n` en `Factura::recalcularPagos`, en `MovimientoInventarioController` (4 consultas y el pago base de `updateFactura`) y en `MovimientoCajaController::sincronizarFacturaRelacionada`.
- Al duplicar un movimiento, la descripción se rotula "Copia de mov. N: …" y se neutralizan las referencias (`#` → `ref.`).
- La migración `2026_11_13_120000_vincular_pagos_legacy_a_facturas` asigna `factura_id` a los pagos antiguos que solo tenían el texto. Es reversible. En la base real vincularía 0 filas.

**Pagos vinculados**
- **M-03.** En Caja no se edita el monto de un pago vinculado a una factura o a otro movimiento. Para corregirlo se anula y se registra de nuevo.
- **M-04.** Editar la factura busca solo el pago base **activo**: nunca reactiva un pago anulado ni cambia su monto, fecha o estado.
- **M-05.** El pagado no se modifica desde la edición de la factura.
  - El campo pasa a ser de solo lectura y el servidor rechaza cualquier diferencia.
  - Los pagos se registran como abonos en Caja, con su fecha y su medio reales.
  - Ya no se crean movimientos de $0.
- **M-10.** `updateFactura` bloquea la factura (`lockForUpdate`) y comprueba que no la hayan anulado mientras tanto.

**Cierres de caja**
- **M-06.** `CierreCajaGuard::fechaEstaCerrada` es **monótono**: una fecha está cerrada si ella o un día posterior tiene cierre.
- **M-08.**
  - No se cierran días futuros.
  - No se hace un cierre si ya existe uno posterior.
  - Una fecha inválida produce un error de validación en lugar de un 500.

**Concurrencia e idempotencia**
- **D-06.** `storeAbono` (Caja) y `HandlesAbono` (órdenes) vuelven a comprobar la anulación **después** del bloqueo de fila.
- **M-07.** Nuevo middleware `App\Http\Middleware\IdempotenciaFormulario`, registrado en el grupo `web`.
  - Cada formulario de escritura recibe un token `_idem` (layout `app`, fase de captura). El token se renueva cuando el usuario edita un campo.
  - Un segundo envío con el mismo token se rechaza con un aviso (HTML) o con un 409 (JSON).
  - Si el primer envío falla, el token se libera.

**Órdenes**
- **B-02.** Quitar un repuesto no puede dejar el costo de la orden por debajo de lo abonado.

### Seguridad

- **F-01.** El composer del layout solo entrega notificaciones (órdenes, saldos, montos) a los roles admin y técnico. El invitado veía montos en cualquier 404.
- **S-05.** `PUT /cambiar-password` responde 403 si el usuario no está obligado a cambiar la contraseña. Así se evita que alguien con la cuenta invitado compartida fije una clave que los demás no conocen.
- **S-01.** Nuevo comando `php artisan app:restablecer-password {email}`.
  - La contraseña se escribe oculta y se valida con la misma política: mínimo 8 caracteres, mayúsculas y minúsculas, números, y que no sea pública.
  - Cierra las sesiones del usuario y deja constancia en `eventos`.
- **B-12.** El único administrador activo no puede quitarse el rol.

### Respaldos y despliegue

- **D-02/O-07.** Rotación y retención por tipo (`db_`, `backup_bd_multimedia_`, `backup_integral_`, `code_`):
  - solo actúan sobre nombres generados por el sistema;
  - el cupo se cuenta por tipo;
  - siempre se conservan las 3 copias más recientes de cada tipo.
- **D-10/O-11.** mysqldump se ejecuta con `--single-transaction --quick --no-tablespaces --routines --triggers --default-character-set=utf8mb4`.
- **D-03.** `app:verificar-respaldo`:
  - elige solo respaldos que contienen la base de datos;
  - exige la marca `Dump completed`;
  - rechaza archivos con `USE` o `CREATE DATABASE`;
  - restaura con `--one-database`;
  - está programado los domingos a las 03:30.
- **O-04.** `gitUpdate` ejecuta `app:backup-db --sin-rotacion` antes de `migrate --force`.
  - Si el respaldo falla, no migra y lo informa.
  - Si la migración falla, lo informa como error (antes respondía "Se actualizó correctamente").
- **O-03.** Las pruebas usan un storage temporal propio.

### Documentos e interfaz

- **X-01.** El informe mensual exportaba a Excel y PDF solo 10 movimientos, porque `paginate()` modificaba el builder.
- **X-03.** Las facturas de orden, A4 y POS de mantenimiento y electrónica, ya no muestran abonos anulados. La A4 indica cuántos abonos anteriores no caben.
- **F-02/F-03.** En el modal de notificaciones:
  - la lista se encoge (`flex-1 min-h-0`), así que el botón Cerrar siempre queda visible;
  - se cierra con Escape (alias `closeTsNotifModal`) y con clic en el fondo;
  - el foco pasa al botón al abrirlo.

### Segunda ronda (2026-11-13, preparación para operar)

Cada punto tiene pruebas que fallan con el código anterior. Suite: 672 pruebas en verde, con la suite `Integration` incluida.

- **B-01 / B-03 (órdenes).** `App\Services\OrdenEstadoService`:
  - terminar exige fecha de salida (no futura); con saldo, exige "entrega con saldo" y motivo;
  - reabrir exige motivo;
  - una orden terminada no cambia costo, equipo ni técnico, ni repuestos;
  - la factura solo se genera para órdenes terminadas.
  Prueba: `OrdenCierreReglasTest`.
- **X-04 a X-09, X-12, X-13 (reportes).**
  - Totales sobre todo el filtro y no solo la página, sin las anuladas, iguales en pantalla, PDF y Excel.
  - Valor de inventario = cantidad × precio.
  - Ceros visibles en Excel.
  - Propietario real del equipo y datos del cliente en las facturas.
  Prueba: `ReportesTotalesCoherentesTest`.
- **M-09 (anulaciones).** Motivo obligatorio al anular, registrado en `eventos`; el modal global lo pide. Prueba: `AnulacionMotivoTest`.
- **M-14, M-15, M-19 a M-22, M-24 (caja y cotizaciones).**
  - El motivo de un descuadre no se puede vaciar.
  - Duplicar un movimiento no guarda nada.
  - Los montos se comparan en centavos.
  - Una venta en $0 en un día cerrado se rechaza.
  - Anular una cotización respeta la intención (`accion`).
  - Una cotización vencida no se convierte.
  - El costo de una orden tiene tope.
  Prueba: `CajaCotizacionCorreccionesTest`.
- **S-06.** Cambiar la contraseña desde Usuarios cierra las demás sesiones. Prueba: `SesionesCambioPasswordTest`.
- **D-08.** El cierre reserva el día antes de leer los movimientos con lectura bloqueante, y el guardián usa lectura compartida. Comprobado en MySQL 8.4 con dos conexiones sobre una base temporal.
- **Q-03.** `tests/Integration` forma parte de `phpunit.xml`.

Ninguno de estos cambios requiere migración.

### Tercera ronda (2026-11-13, código pendiente)

Suite: 708 pruebas en verde.

- **X-02.** Con más de 8 renglones, la plantilla A4 pasa a hoja completa: las firmas y el pie quedan después de los ítems, los totales no se parten y la marca de agua sale en todas las páginas. Prueba `PlantillaA4HojaCompletaTest`: renderiza el PDF real y mide página y altura de cada texto.
- **M-16 / M-23.** `App\Support\Monto::parse` es la única regla para interpretar montos; lo ambiguo se rechaza. Prueba `MontoTest`.
- **M-12 / M-13.** El informe dice "Flujo Neto de Caja", y los documentos llevan la leyenda "Documento interno de control. No es factura electrónica de venta (DIAN)".
- **F-04.** En los 11 listados, Enter busca en el servidor. Prueba `BusquedaServidorTest`.
- **D-05.** Los pagos de caja se enlazan solo por clave: no hay LIKE sobre la descripción ni N+1. Prueba `CajaPagosPorClaveTest`.
- **D-01 (parcial).** Antes de restaurar se verifica en una base temporal, y después se avisa si el esquema es viejo.

### Cuarta ronda (2026-11-16, robustez)

Se hizo en una copia aislada del código (rama `robustez-final`) y se integró al final. Suite: **780 pruebas en SQLite**, más la suite completa en una base MySQL 8.4 temporal (ver `RESULTADOS_TESTING.md`). Cada bloque tiene pruebas que fallan con el código anterior.

| Commit | Bloque | Hallazgos |
|---|---|---|
| `55c9f11` | Validaciones de entrada (sin errores 500), reglas de órdenes y abonos, cotizaciones por clave, conceptos de sistema, numeración, notificaciones | B-04 a B-24, F-05, F-06, ARQ-06/07, D-17, D-18 |
| `63510d9` | Repuestos en el historial, historial por orden, kardex completo, costo exacto | B-04, M-17, M-18, B-23, ARQ-10 |
| `f4bdb78` | Foto del cierre, costo histórico y promedio, código de consulta y comprobante de recepción, stock mínimo, DECIMAL(12,2), llaves RESTRICT, índices y CHECK | M-06, M-11, S-04, ARQ-12, D-11 a D-15, D-21 |
| `3c976e5` | Respaldos: contraseña para operaciones sensibles, mantenimiento al restaurar, cifrado AES-256, manifiesto SHA-256, credenciales temporales | S-08, D-01, D-03, D-10, D-16, ARQ-09 |
| `5b0f9a8` | Seguridad y operación: bloqueo por cuenta, recordarme 7 días, cabeceras, permisos en rutas, comandos peligrosos, preflight | S-09 a S-15, ARQ-01, ARQ-08, ARQ-11, D-19, D-24, D-26, O-14, O-17, O-19 |
| `eeb9d79` | Filtros por fecha con índice, tope de exportación | D-04, D-22, D-23 |
| `a9ab330` | Excel tipado y usable, ticket POS, PDF más livianos, imprimir abre el PDF completo | X-10, X-11, X-14 a X-20, X-22, X-23, ARQ-03 |
| `c372ef8` | Interfaz: accesibilidad, avisos, campana, modales, estilos | F-08 a F-28, F-35, F-37 |

Puntos importantes:
- **Compatibilidad antes de migrar.** El código usa una columna nueva solo si ya existe (`App\Support\Esquema`). `CompatibilidadSinMigrarTest` recorre los flujos principales con la base sin migrar.
- **Migraciones nuevas (6):** todas son reversibles, salvo la conversión de precisión, que se detiene si hay valores que no caben. Las restricciones CHECK se omiten (y se registran) si hay datos que no las cumplen.
- **Cambios visibles para el usuario:**
  - el invitado consulta con el código de consulta;
  - las operaciones de respaldo piden la contraseña;
  - la descarga de respaldos es por formulario;
  - "Imprimir" en los reportes abre el PDF completo;
  - el menú usa "Compras y ventas", "Caja" y "Reportes".

### Quinta ronda (2026-11-16, verificación de punta a punta en el navegador)

Se montó una copia aislada (rama `verificacion-e2e`, base MySQL propia `tecni_e2e`, datos inventados) y se recorrió el sistema como lo haría el personal del local: clientes, técnicos, proveedores, productos, equipos, órdenes de mantenimiento y electrónica, repuestos, abonos, entrega, ventas de contado y a crédito, compras, cotizaciones, caja, cierre, anulaciones, respaldo y restauración, la consulta del invitado, los permisos por rol, los PDF (A4 y tirilla) y la vista en celular. Cada error encontrado se corrigió con una prueba que falla con el código anterior (`VerificacionOperacionTest`, `MediaHojaFirmasTest`).

| Hallazgo (lo que se vio en pantalla) | Corrección |
|---|---|
| Un producto se guardaba con precio de venta $1 y costo $10.000; la lista mostraba "+50 %" y "+$ −9.999" | El precio de venta y el de técnico no pueden ser menores que el costo (al crear y al editar). Los precios vacíos los sigue calculando el modelo |
| La utilidad de una venta ya hecha cambiaba después de cada compra del mismo producto (VT-3 pasó de +30.000 a +43.333) | `Factura::costo_total` usa el costo guardado en cada ítem al vender. Si al editar se cambia el producto de un ítem, su costo guardado se actualiza |
| Se aceptó un egreso en efectivo de $99.999.999 con la caja casi vacía (efectivo en −99,7 millones) | Ninguna salida en efectivo (egreso manual, edición, compra, abono a una compra) puede dejar la caja en negativo; el mensaje indica registrar primero la "Apertura de caja" o usar Consignación (`App\Services\EfectivoCaja`) |
| Se aceptó un movimiento de caja con fecha 31/12/2026 | Caja, ventas y compras no admiten fechas futuras; el mensaje dice "anterior o igual a hoy" |
| Se registró a mano un ingreso con el concepto "Abono Mantenimiento" sin orden asociada | Los conceptos del sistema no se ofrecen ni se aceptan en movimientos manuales |
| La orden impresa (A4 y tirilla) mostraba los repuestos y el total, pero no el valor del servicio | Línea "Servicio técnico / mano de obra" y rótulo "Total del servicio" cuando la orden está terminada. La orden de electrónica imprime el código de consulta |
| En media hoja, con el encabezado completo de la empresa, la línea de firma quedaba encima del último abono | El cálculo de espacio cuenta los títulos de las tablas; si no cabe, se usa la hoja completa. La prueba mide la posición real en el PDF |
| El invitado podía consultar una orden con cédula + número consecutivo en `/consulta/...`, sin el código | Esas rutas llevan al portal que exige el código |
| La lista de respaldos mostraba la hora en UTC (07:25 PM en lugar de 02:25 PM) | Se usa la zona horaria del sistema |
| "Actualizar desde GitHub" descargaba el código y migraba, pero no recompilaba la interfaz ni instalaba dependencias | Si cambian estilos, scripts o `composer.lock`, ejecuta `npm ci && npm run build` o `composer install --no-dev`; si no puede, dice exactamente qué correr (`NPM_BIN`, `COMPOSER_BIN` en `.env`) |
| En una instalación nueva las facturas salían con la empresa de ejemplo (NIT 900.000.000-1) | El seeder reemplaza la fila de demostración por los datos reales; no toca datos que el dueño ya cambió |
| En el punto de venta, "Total recibido" arrancaba en 0: si el cajero lo olvidaba, la venta quedaba fiada | Sigue al total mientras el cajero no lo cambie |
| Se aceptó un celular "12" | Celular de al menos 7 caracteres (clientes y técnicos) |
| Aviso de venta a crédito con formato "$100,000.00"; tarjeta "Stock bajo (<5)" aunque cada producto tiene su mínimo; texto del portal que pedía "número de orden" | Formato `$100.000`; "Stock bajo" con la regla real; textos del portal con el código |
| Las pruebas heredaban `BACKUP_PASSWORD` del `.env` y fallaban cuando la clave estaba definida | `phpunit.xml` la fija vacía (las pruebas de cifrado la ponen ellas mismas) |

Revisado y **sin cambios** (se confirmó que es intencional o ya estaba resuelto):
- la venta por debajo del costo se permite: la pantalla muestra "Advertencia de Margen Negativo";
- cada usuario cambia su contraseña desde su perfil (el ícono con su inicial), con la contraseña actual;
- anular sin indicar la intención alterna el estado, pero la interfaz siempre la envía, y un doble clic no revierte la acción;
- el técnico ve caja, reportes y compras; cerrar caja, configuración, auditoría y respaldos son solo del administrador.

Lo que funcionó sin cambios en el recorrido:
- descuento de inventario y costo promedio ponderado;
- validaciones de abonos (monto, fecha, saldo);
- entrega con saldo y motivo;
- cierre de caja con descuadre y bloqueo del día;
- anulación con motivo y devolución de inventario;
- cotización convertida en venta una sola vez;
- respaldo cifrado, restauración con contraseña y estado anterior guardado;
- los permisos por rol;
- todas las pantallas a 375 px sin desbordes.

## Archivos modificados en la ronda

**Aplicación**
- `app/Console/Commands/BackupDatabase.php`, `VerificarRespaldo.php` y `RestablecerPassword.php` (nuevo).
- `app/Http/Controllers/BackupController.php`, `CierreCajaController.php`, `MovimientoCajaController.php`, `MovimientoInventarioController.php`, `PasswordController.php`, `ReporteController.php` y `UserController.php`.
- `app/Http/Middleware/IdempotenciaFormulario.php` (nuevo).
- `app/Models/Factura.php`, `app/Providers/AppServiceProvider.php` y `app/Services/CierreCajaGuard.php`.
- `app/Traits/HandlesAbono.php` y `HandlesStockAttach.php`.
- `bootstrap/app.php` y `routes/console.php`.

**Base de datos**
- `database/migrations/2026_11_13_120000_vincular_pagos_legacy_a_facturas.php` (nueva).

**Vistas**
- `resources/views/layouts/app.blade.php` e `inventario/facturas/edit.blade.php`.
- `mantenimientos/factura{,_pos}.blade.php` y `electronicas/factura{,_pos}.blade.php`.

**Configuración de pruebas**
- `phpunit.xml` y `tests/TestCase.php`.

**Pruebas** (nuevas y ajustadas)
- `tests/Feature/*`: ver `RESULTADOS_TESTING.md`.
