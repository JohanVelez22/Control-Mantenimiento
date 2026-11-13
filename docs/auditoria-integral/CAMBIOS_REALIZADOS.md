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
