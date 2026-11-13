# Auditoría financiera

> **Origen:** informe del agente 5 (finanzas) de la auditoría integral del 2026-11-13 (análisis con evidencia, sin modificar producción).
> **Coordinación:** el coordinador verificó los hallazgos ALTOS/CRÍTICOS antes de corregirlos y añadió este encabezado de estado.

## Estado tras las correcciones de esta ronda

| Hallazgo | Estado |
|---|---|
| M-01 un duplicado de pago contaba como pago | **Corregido** (vínculo solo por `factura_id`; duplicar neutraliza referencias) |
| M-02 un ingreso manual con "#VT-n" pagaba la factura | **Corregido** |
| M-03 editar el monto de un pago en caja permitía pagado > total | **Corregido** (el monto de pagos vinculados no se edita) |
| M-04 editar la factura reactivaba pagos anulados | **Corregido** |
| M-05 editar la factura reescribía pagos en fechas pasadas | **Corregido** (pagado de solo lectura; los pagos son abonos en Caja) |
| M-06 movimientos retroactivos alteraban cierres | **Corregido** (período monótono). Snapshot del saldo inicial en el cierre: **pendiente** |
| M-07 ninguna operación era idempotente | **Corregido** (middleware `IdempotenciaFormulario` + token `_idem`) |
| M-08 cierres intermedios o futuros | **Corregido** |
| M-10 `updateFactura` sin bloqueo | **Corregido** |
| D-06 carrera anular / abonar | **Corregido** (revalidación tras el bloqueo) |
| M-09, M-11 a M-24 | **Pendientes** (ver `PENDIENTES.md`) |

Pruebas: `tests/Feature/FinanzasCorreccionesTest.php` (10 pruebas; 9 fallan contra el código anterior, comprobado).

Los identificadores (p. ej. `M-01`) se usan igual en `CAMBIOS_REALIZADOS.md`, `PENDIENTES.md` y en los mensajes de commit.

---

## Informe del agente 5 (finanzas)

Proyecto: tecnisystemas (WolfCore System). Moneda: pesos colombianos (COP). Fecha: 2026-11-13.
Modo: solo lectura. Las pruebas de evidencia se ejecutaron **solo en SQLite en memoria**, con un archivo de pruebas guardado en la carpeta de trabajo de la auditoría (no versionada):

- Pruebas: `(archivo de trabajo de la auditoría)/FinanzasAuditTest.php`
- Ejecución: `C:\ServBay\packages\php\current\php.exe vendor/bin/phpunit <ruta>`. Resultado: **OK (20 tests, 86 assertions)**, estable en 3 ejecuciones. Cada test **pasa si el hallazgo existe**, porque comprueba el comportamiento defectuoso actual. Al corregir un hallazgo, su test debe fallar y entonces se invierte su aserción.
- El `setUp` comprueba que el driver de base de datos es `sqlite` antes de ejecutar nada.

---

## Resumen ejecutivo

Quedan **7 hallazgos ALTOS**, 9 MEDIOS y 8 BAJOS. No encontré nada CRÍTICO de pérdida directa e inmediata de dinero. El patrón de fondo es el mismo en casi todos: **el pagado de una factura se reconstruye buscando la referencia `#VT-n`/`#CP-n` en el texto libre de la descripción** (vínculo *legacy*). Además, **varias pantallas pueden reescribir movimientos de caja ya registrados** en lugar de agregar movimientos nuevos.

| ID | Sev. | Título corto |
|---|---|---|
| M-01 | ALTA | Duplicar un pago de factura lo vuelve a contar como pago (regresión del arreglo "duplicar sin vínculos") |
| M-02 | ALTA | Un ingreso manual con "#VT-n" en la descripción paga la factura, sin tope |
| M-03 | ALTA | Editar en caja el monto del pago de una factura permite pagado > total |
| M-04 | ALTA | Editar una factura reactiva su pago anulado en caja y sobrescribe el monto original |
| M-05 | ALTA | Subir el "total pagado" desde la edición de la factura reescribe el pago del día de la factura (no entra dinero hoy) |
| M-06 | ALTA | Movimientos retroactivos en días abiertos anteriores a un cierre cambian el arqueo ya cerrado (no hay *snapshot*) |
| M-07 | ALTA | Ninguna operación de dinero es idempotente: un doble POST duplica ventas, compras, abonos, caja, repuestos y edición de factura |
| M-08 | MEDIA | Cierre "intermedio" (anterior a uno existente) y cierre de fechas futuras permitidos |
| M-09 | MEDIA | Anulaciones, eliminación de abonos, borrado de cierres y edición de montos sin justificación; `eventos` editable y borrable |
| M-10 | MEDIA | `updateFactura` sin bloqueo ni control de versión de la factura |
| M-11 | MEDIA | La utilidad de ventas pasadas no está congelada; una compra no actualiza el costo del artículo |
| M-12 | MEDIA | "utilidad_neta" / "utilidad_bruta" son en realidad flujo de caja |
| M-13 | MEDIA | No existen IVA, retenciones ni descuentos; los documentos se llaman "Factura" |
| M-14 | MEDIA | El motivo de un descuadre se puede borrar después del cierre |
| M-15 | MEDIA | "Duplicar" en caja crea un movimiento ACTIVO de inmediato, antes de la revisión |
| M-16 | MEDIA | Sin un único punto de redondeo: float en PHP frente a DECIMAL; validación `decimal:0,2` inconsistente |
| M-17 | MEDIA | Los cambios de repuestos en órdenes (tablas pivote) no se auditan |
| M-18 | BAJA | El promedio redondeado de repuestos deja el costo de la orden en −0,01 y un costo mínimo mayor que el real |
| M-19 | BAJA | EPSILON como tolerancia hacia arriba: abono o pago de hasta saldo + 0,01 |
| M-20 | BAJA | Una venta o compra con total 0 en un día cerrado no pasa por el guardián de cierre |
| M-21 | BAJA | Anular una cotización alterna su estado sin verificar la intención ni bloquear la fila |
| M-22 | BAJA | Las cotizaciones vencidas se convierten con los precios viejos |
| M-23 | BAJA | Ambigüedades de `cleanAmount` ("1,500" = 1500; "0.001" = 1; 3 decimales) |
| M-24 | BAJA | "Monto anulados" del reporte suma magnitudes heterogéneas; `mantenimientos.store` sin `max` para `costo` DECIMAL(10,2) |

---

## Hallazgos detallados

### M-01 — ALTA — Un duplicado del pago de una factura vuelve a contar como pago
- **Dónde:** `app/Http/Controllers/MovimientoCajaController.php:432-454` (`duplicate`), junto con `app/Models/Factura.php:153-182` (`recalcularPagos`, rama *legacy* `whereNull('factura_id')` + `descripcion LIKE '%#VT-n'`) y `MovimientoCaja::getTotalPagadoAttribute` (línea 132 en adelante, por `ref_search`).
- **Evidencia:** `duplicate` pone `factura_id = null` y `abono_id = null`, pero **copia la descripción** `"Cobro venta #VT-1"`. `recalcularPagos` trata como pago de la factura todo movimiento sin `factura_id` cuya descripción termina en `#VT-1`.
- **Caso numérico (test `test_duplicar_pago_de_factura_se_cuenta_como_pago_por_referencia_legacy`):** venta VT-1 por 100.000 con pago de 40.000. Se duplica el pago y después se registra un abono real de 10.000. El pagado debería quedar en 40.000 + 10.000 = **50.000**; el sistema registra **90.000**. El saldo por cobrar queda en 10.000 cuando en realidad es 50.000. Además, anular la factura arrastra también el duplicado, porque `$pagosDeLaFactura` incluye la rama *legacy*.
- **Corrección:** (a) en `duplicate`, borrar o transformar las referencias del texto, por ejemplo `$nuevo->descripcion = preg_replace('/#([A-Z]{2}-\d+)/', 'ref. $1', ...)` o el prefijo "Copia de mov. #id"; (b) **cerrar la rama *legacy***: ejecutar una vez una migración de datos que asigne `factura_id` a los movimientos antiguos que coinciden exactamente y, desde ahí, que `recalcularPagos`, `showFactura`, `anularFactura`, `updateFactura` y `MovimientoCaja::total_pagado` usen **solo** `factura_id`/`parent_id`.

### M-02 — ALTA — Un ingreso manual que menciona "#VT-n" paga la factura, sin tope y sin clave de administrador
- **Dónde:** `MovimientoCajaController::store` (191-259) llama a `sincronizarFacturaRelacionada` (696-723), que busca `#XXX` con una expresión regular y ejecuta `recalcularPagos`.
- **Caso numérico (test `test_ingreso_manual_con_referencia_paga_factura_sin_tope`):** venta VT-1 por 100.000, pagado 0. Un técnico registra un ingreso manual de **500.000** con la descripción "Pago cliente #VT-1". Resultado: factura `total_pagado = 500.000`, `saldo_a_favor = 400.000`, estado `emitida`. No se valida contra el saldo ni contra el tipo de documento, y no se pide clave de administrador (`store` no llama a `autorizarOperacionSensible`).
- **Corrección:** eliminar el enlace por texto (ver M-01). Si se quiere permitir pagar una factura desde caja, hacerlo con un selector explícito de factura que guarde `factura_id`/`parent_id` y valide `monto <= saldo_pendiente` dentro de una transacción con bloqueo (igual que `storeAbono`).

### M-03 — ALTA — Editar el monto del pago de una factura en caja permite pagado > total
- **Dónde:** `MovimientoCajaController::update` (294-388). Para `factura_id`/`parent_id` solo se bloquea el cambio de **tipo** (329). El control `monto_total >= monto` (333) solo se aplica si el usuario envía `monto_total` y no está vacío.
- **Caso (test `test_editar_monto_pago_factura_en_caja_supera_total`):** venta por 100.000 con pago de 40.000. Se edita el pago a **300.000** con `monto_total` vacío. La factura queda con `total_pagado = 300.000` (saldo a favor de 200.000). Lo mismo ocurre con un abono hijo (`parent_id`).
- **Corrección:** si el movimiento tiene `factura_id` o `parent_id`, validar dentro de la transacción, con la factura bloqueada, que `(pagado_total − monto_anterior + monto_nuevo) <= total_documento + 0`. Mejor aún: **prohibir editar el monto** de pagos vinculados (igual que con los abonos de órdenes) y obligar a "anular y registrar de nuevo" (ver el libro de movimientos).

### M-04 — ALTA — Editar una factura reactiva su pago anulado y sobrescribe el monto original
- **Dónde:** `MovimientoInventarioController::updateFactura` 852-893. `$baseCaja` se busca **sin filtrar `anulado`**, y el `update` fuerza `'estado' => 'activo', 'anulado' => false` (el valor de `$shouldBeAnulada` siempre es false aquí) y `'monto' => $montoBaseAjustado`.
- **Caso (test `test_editar_factura_reactiva_pago_anulado_y_pierde_monto`):** venta por 100.000 pagada con 100.000. Se anula el pago en caja (reembolso) y el pagado de la factura queda en 0. Luego se edita la factura solo para cambiar las observaciones. El movimiento anulado vuelve a quedar **activo**, y su `monto` original (100.000) se **sobrescribe con 0**. El registro del reembolso se pierde del movimiento y solo queda en `eventos`. Si el formulario hubiera enviado 100.000, el dinero reembolsado volvería a contarse en caja.
- **Corrección:** en `updateFactura` buscar `$baseCaja` con `->where('anulado', false)`. Si el pago base está anulado, **no tocarlo** y registrar cualquier diferencia de pagado como movimiento nuevo (ver M-05). No volver a poner nunca `anulado=false` desde la edición.

### M-05 — ALTA — Subir el pagado desde la edición de la factura reescribe el pasado
- **Dónde:** `updateFactura` 883-893: `monto = totalPagado − abonos`, `fecha = $request->fecha` (la fecha de la factura).
- **Caso (test `test_editar_factura_aumenta_pago_en_fecha_pasada_no_hoy`):** venta del día D−3 por 100.000 con pagado 0. Hoy el cliente paga 100.000 en efectivo y el usuario lo registra editando la factura. Resultado: caja de hoy = **0**; caja de D−3 = **100.000**. El arqueo de hoy tendrá un sobrante de 100.000 y el de D−3 (si después se cierra) un faltante, o queda alterado a posteriori. Además, la edición traslada el pago base a la fecha de la factura aunque en caja tuviera otra fecha, y cuando el pago base no existía lo crea siempre como `efectivo` (línea 909, no se pasa `tipoPago`).
- **Corrección:** quitar `total_pagado` del formulario de edición (que sea de solo lectura). Los pagos posteriores se registran **solo** como abono (`caja.abonos.store`) con la fecha y el medio reales. Si se conserva el campo, que la diferencia positiva genere un abono hijo con fecha de hoy y medio elegido, y que una diferencia negativa se rechace ("anule el abono correspondiente").

### M-06 — ALTA — Movimientos retroactivos en días abiertos alteran cierres ya hechos
- **Dónde:** `app/Services/CierreCajaGuard.php:20-27` solo comprueba si **esa misma fecha** tiene cierre. `CierreCaja::saldoInicialEfectivo` (48-68) calcula el saldo inicial **en el momento de consultarlo**. `CierreCajaController::store` (107-121) **no guarda** `saldo_anterior` ni `efectivo_esperado`.
- **Caso (test `test_movimiento_retroactivo_altera_cierre_posterior`):** hoy entra un ingreso de 200.000, se cierra con conteo de 200.000 y diferencia 0. Después se registra un egreso de 50.000 con fecha de **ayer**, día sin cierre, y el sistema lo acepta. El cierre de hoy muestra ahora `efectivo_esperado = 150.000` con `diferencia = 0` guardada. La diferencia real (contado − esperado) es **+50.000**, un sobrante que nadie ve.
- **Corrección:** (1) Guardián de período **monótono**: rechazar cualquier fecha `<= max(cierre_cajas.fecha)` (`fechaEstaCerrada($f)` → `CierreCaja::where('fecha','>=',$f)->exists()`). (2) Guardar en `cierre_cajas` columnas *snapshot* `saldo_inicial` y `efectivo_esperado`, y que vistas y exports lean esas columnas, no el cálculo en vivo. (3) Verificación diaria (comando) que compare el *snapshot* con el recálculo y avise si difieren.

### M-07 — ALTA — Ninguna operación de dinero es idempotente
- **Caso (tests `test_doble_post_de_venta_duplica_todo`, `test_doble_post_de_abono_caja_y_repuesto`):** el mismo POST de venta enviado dos veces produce 2 facturas, 2 ingresos (100.000 en lugar de 50.000) y 2 unidades descontadas del inventario. El mismo abono de 30.000 enviado dos veces deja 60.000 abonados. El mismo repuesto agregado dos veces da cantidad 2 y costo duplicado.
- Lista exacta y propuesta en la sección **Idempotencia**, más abajo.

### M-08 — MEDIA — Cierre intermedio y cierre futuro
- **Dónde:** `CierreCajaController::store` 59-72 solo valida que no exista un cierre en esa fecha.
- **Caso (test `test_cierre_intermedio_altera_saldo_inicial_de_cierre_posterior`):** ingreso de 100.000 en D0. Se cierra D2 con conteo de 100.000 (diferencia 0). Después se cierra **D0** con conteo de 80.000 (faltante de 20.000). El saldo inicial de D2 pasa de 100.000 a **80.000** y su diferencia guardada (0) ya no corresponde. Esto contradice la regla que `destroy` (135-140) sí protege. El test `test_cierre_de_fecha_futura_permitido` muestra que se puede cerrar una fecha a +10 días, lo que bloquea por adelantado el registro de ese día.
- **Corrección:** en `store`, rechazar `fecha > hoy` y `fecha < max(cierre.fecha)`. Opcionalmente, exigir que los días con movimientos anteriores a la fecha estén cerrados (cierre secuencial).

### M-09 — MEDIA — Sin justificación en anulaciones ni ediciones; auditoría modificable
- **Dónde:** no existe un campo `motivo` en el modal global de anulación (`resources/views/layouts/app.blade.php` ~197: solo `accion` y `password_confirm`), ni en `AnulacionService`, `MovimientoCajaController::anular`/`update`, `destroyAbono`, `anularFactura` o `CierreCajaController::destroy`. `App\Models\Evento` es un modelo Eloquent normal: el test `test_edicion_monto_solo_queda_en_evento` **edita y borra** un evento sin restricción. Cualquier código o consola puede hacerlo, y además `app:clean-data` y `ResetTests` vacían la tabla. `Auditable` traga sus fallos (`try/catch` con `Log::warning`), así que una operación puede quedar sin evento.
- **Qué registra Auditable:** `creado` (todos los atributos), `actualizado` (solo los campos cambiados, antes/después), `anulado` (detectado por `anulado` 0→1, `estado='anulada'` o `active` 1→0) y `eliminado`. Enmascara `email, identificacion, telefono, movil, direccion, password, remember_token`. **No registra:** las tablas pivote (`mantenimiento_stock`, `electronica_stock` → M-17), las actualizaciones masivas por *query builder* (p. ej. `CategoriaStockController:59`), el motivo, la operación de negocio que originó el cambio (venta X → movimiento Y) ni un identificador de correlación.
- **Edición de monto en caja:** el valor anterior **solo** queda en `eventos.valores_antiguos` (comprobado: 80.000 → 8.000). El movimiento no guarda historial.
- **Corrección:** campo `motivo` obligatorio (mínimo 10 caracteres) en el modal global y en las ediciones de monto, que se guarde en el libro de movimientos (propuesta más abajo). Proteger `eventos` igual que el libro (sección "Garantía append-only").

### M-10 — MEDIA — `updateFactura` sin bloqueo de la factura
- **Dónde:** `MovimientoInventarioController.php:696` en adelante. No hay `Factura::lockForUpdate()` al inicio (solo `recalcularPagos` bloquea, al final), ni control de versión (`updated_at` del formulario).
- **Escenario:** dos pestañas editan la misma factura o se reintenta el PUT. `new_items` se inserta dos veces y el inventario se descuenta dos veces. La última escritura pisa el `total_pagado` de la otra.
- **Corrección:** bloquear la factura al inicio de la transacción. Agregar un campo oculto `version` (`updated_at`) y rechazar si no coincide ("la factura cambió, recargue"). Usar el token de idempotencia.

### M-11 — MEDIA — La utilidad histórica no está congelada y el costo no se actualiza con las compras
- **Dónde:** `Factura::getCostoTotalAttribute` (113-118) usa el `stock.precio_compra` **actual**; `factura_items` no guarda el costo. `registrarCompra` (140-146) solo actualiza `proveedor_id` y la cantidad.
- **Casos:** `test_utilidad_historica_no_congelada`: venta de 2 × 50.000 con costo de 30.000 da utilidad 40.000. Al cambiar `precio_compra` a 45.000, la **misma venta** muestra utilidad 10.000. `test_compra_no_actualiza_precio_compra`: se compran 10 unidades a 40.000 y `precio_compra` sigue en 30.000, de modo que las alertas de "venta bajo costo" y la "utilidad esperada" del inventario quedan desactualizadas.
- **Corrección:** agregar `factura_items.costo_unitario DECIMAL(12,2)` y llenarlo al vender con el costo vigente. Al comprar, actualizar `precio_compra` con el costo promedio ponderado: `(cant_actual × costo_actual + cant_compra × precio_compra_nuevo) / (cant_actual + cant_compra)`, redondeado una vez a 2 decimales. Recalcular `precio_venta`/`precio_tecnico` solo si el usuario lo confirma.

### M-12 — MEDIA — "Utilidad" que en realidad es flujo de caja
- **Dónde:** `ReporteController.php:74` (`'utilidad_neta' => $saldo_mes`, ingresos − egresos de caja) y `ResetMonthlyStats.php:37` (`utilidad_bruta = ingresos − egresos`).
- **Caso:** en un mes se compra inventario por 5.000.000 (egreso) y se vende la mitad por 3.000.000. La "utilidad neta" sale −2.000.000, cuando la utilidad bruta real es 3.000.000 − 2.500.000 = +500.000 y el resto es inventario.
- **Corrección:** renombrar a "Flujo neto de caja". Si se quiere utilidad, calcularla como ventas − costo de lo vendido (con M-11) + mano de obra de órdenes − gastos (egresos que no son compras de inventario).
- `utilidad_esperada` (`ReporteController:66`) suma también artículos inactivos (`Stock::sum` sin `activos()`).

### M-13 — MEDIA — No hay IVA, retenciones ni descuentos
- **Evidencia:** no hay ninguna coincidencia de `iva|impuesto|retenci|descuento` en la lógica. Los "descuentos" solo existen como texto libre en las observaciones (`venta.blade.php:139`), y los documentos se numeran y titulan "Factura VT-n".
- **Implicaciones en Colombia:** si el taller es responsable de IVA, (a) debe expedir **factura electrónica de venta validada por la DIAN** (Res. 000165/2023). Un PDF interno llamado "Factura" no es factura legal y puede confundir al cliente. (b) Los servicios de reparación y la venta de repuestos están gravados (19 % general), y sin discriminar base e IVA no hay cómo declarar ni descontar IVA en las compras. (c) A los clientes que son agentes retenedores (empresas) les aplican retención en la fuente, ReteIVA y ReteICA: el pago recibido será **menor** que el total y, con el diseño actual, la diferencia queda como "saldo pendiente" para siempre. (d) Si el taller no es responsable de IVA, debe decirlo en el documento.
- **Corrección mínima:** rotular los documentos como "Documento interno / Remisión — no es factura de venta" mientras no haya integración DIAN. Agregar a `facturas` las columnas `subtotal`, `descuento`, `base_gravable`, `iva`, `retenciones` y `total`, y un parámetro de régimen en `Configuracion`. Tratar las retenciones como un "pago" con un medio específico (`retencion`) que cierre el saldo sin entrar a caja.

### M-14 — MEDIA — El motivo de un descuadre se puede borrar después del cierre
- **Dónde:** `CierreCajaController::update` 173-191 (`motivo_diferencia` *nullable*).
- **Caso (test `test_motivo_de_descuadre_se_puede_borrar`):** cierre con sobrante de 5.000 y motivo. Se envía PUT con motivo vacío y el motivo queda en `null`, aunque `store` lo exige obligatoriamente cuando `|diferencia| >= 0,01`.
- **Corrección:** en `update`, si `abs(diferencia) >= 0.01`, exigir `motivo_diferencia` *required*. Mejor aún: permitir solo **agregar** observaciones, no reemplazarlas.

### M-15 — MEDIA — "Duplicar" en caja crea un movimiento activo antes de revisarlo
- **Dónde:** `MovimientoCajaController::duplicate` 432-454 llama a `save()` y redirige a editar con el mensaje "Revisa y actualiza los datos antes de guardar".
- **Escenario:** el usuario duplica un egreso de 1.000.000 y cierra la pestaña. Queda un egreso **activo** de 1.000.000 con fecha de hoy que descuadra el arqueo. Un doble POST crea dos.
- **Corrección:** `duplicate` debe redirigir a `caja.create` con los campos precargados (`?from=id`) **sin persistir nada**. El guardado ocurre en `store`, con su token de idempotencia.

### M-16 — MEDIA — Redondeo sin un único punto; validación decimal inconsistente
- **Evidencia:** todos los cálculos se hacen en `float` de PHP (`calcularTotal`, `CalculaCostoOrden`, `recalcularPagos`, `CierreCajaController::calcularDia`) y se truncan o redondean implícitamente al escribir en DECIMAL(…,2). En MySQL eso redondea; en SQLite (tests) se guarda el valor tal cual, así que **los tests no detectan las diferencias de redondeo de producción**. `caja.store/update` exige `decimal:0,2`; `abonos`, `venta`, `compra`, `updateFactura` y `cotizaciones` **no** lo exigen.
- **Ejemplo:** `"12.345,678"` → 12345.678 (test `test_clean_amount_ambiguedades`). Con 1.000 unidades, `calcularTotal` da 12.345.678,00, pero `factura_items` guarda `precio_unitario` = 12.345,68 y `subtotal` generado = 12.345.680,00. El total del documento y la suma de las líneas difieren en 2,00.
- **Corrección:** regla única: **todo monto se normaliza a entero de centavos (o a pesos enteros, porque el COP no usa centavos) al entrar**, con `decimal:0,2` en todos los formularios. Las sumas se hacen sobre los valores ya redondeados de cada línea (`round(cant × precio, 2)` por línea y luego sumar). `Factura::EPSILON` solo debería usarse para comparar valores leídos de la base, nunca como margen permitido (ver M-19). Opcional: `bcmath`/`brick/money`.

### M-17 — MEDIA — Los cambios de repuestos en órdenes no se auditan
- **Dónde:** `HandlesStockAttach` usa `attach`, `updateExistingPivot` y `detach` sobre una relación `belongsToMany` sin modelo pivote, y esas operaciones no disparan eventos Eloquent. Solo queda el `increment('costo')` en `eventos`, sin decir qué repuesto ni a qué precio.
- **Corrección:** modelo pivote `MantenimientoStock extends Pivot` con `Auditable` (`->using(...)`), o registrar explícitamente un `Evento::registrar('repuesto_agregado', $orden, null, [stock_id, cantidad, precio])`.

### M-18 — BAJA — Precio promedio redondeado en la tabla pivote
- **Dónde:** `HandlesStockAttach.php:46-53` (promedio redondeado a 2 decimales) frente a la línea 64 (`increment` con el precio real), y `detachStock` (112).
- **Caso (test `test_promedio_redondeado_en_repuestos_deja_costo_negativo`):** se agrega 1 unidad a 10.000 y luego 2 a 10.001. `costo` = 30.002, pero la pivote guarda 3 × 10.000,67 = **30.002,01**, y `costoMinimoPermitido()` = 30.002,01 > costo real, así que la orden no se puede editar dejando su costo actual. Al quitar el repuesto, `costo` = **−0,01**.
- **Corrección:** guardar en la pivote `valor_total` (o líneas separadas por cada precio) y descontar exactamente lo que se sumó. Alternativa: `increment('costo', round(nuevaCant × nuevoPrecio − totalPrevio, 2))`.

### M-19 — BAJA — EPSILON permite sobrepago de 0,01
- **Dónde:** `HandlesAbono.php:40,66`, `registrarVenta/Compra:88,248`, `updateFactura:835`, `MovimientoCajaController:485,516`: `monto > saldo + EPSILON`.
- **Caso (test `test_abono_puede_superar_costo_por_epsilon`):** orden de 100.000 con abono de 100.000,01, que se acepta. `total_abonado` = 100.000,01 y el `saldo_pendiente` aparece como 0 por `max(0, …)`, así que el sobrepago queda oculto.
- **Corrección:** comparar en centavos enteros: `(int) round($monto*100) > (int) round($saldo*100)`.

### M-20 — BAJA — Venta o compra con total 0 en un día cerrado
- **Dónde:** `registrarVenta/Compra`. El guardián solo se ejecuta dentro de `registrarMovimientoCaja` (1048), que no se llama cuando el total y el pagado son 0.
- **Caso (test `test_venta_en_cero_en_dia_cerrado`):** en un día cerrado se registra una venta de 3 unidades a $0. Se crea la factura y salen 3 unidades del inventario en un día "bloqueado".
- **Corrección:** `CierreCajaGuard::asegurarAbierta($request->fecha)` al inicio de las dos operaciones.

### M-21 — BAJA — Anular una cotización alterna su estado sin verificar la intención
- **Dónde:** `CotizacionController::anular` 260-292 no llama a `verificarIntencion` ni usa `lockForUpdate`.
- **Caso (test `test_cotizacion_anular_alterna_sin_intencion`):** dos POST con `accion=anular`: el segundo **reactiva** la cotización.
- **Corrección:** el mismo patrón que órdenes y facturas: bloqueo de la fila, `verificarIntencion($request, ! $cot->anulado)`.

### M-22 — BAJA — Cotizaciones vencidas se convierten
- **Dónde:** `CotizacionController::convertir` no comprueba `fecha + validez_dias`.
- **Caso (test `test_cotizacion_vencida_se_convierte`):** una cotización de hace 120 días (validez 15) por 50.000 se convierte después de que el costo subió a 70.000. Resultado: venta con utilidad **−20.000**, sin ninguna advertencia.
- **Corrección:** bloquear o exigir confirmación de administrador si está vencida, y advertir si algún precio queda por debajo de `precio_compra`.

### M-23 — BAJA — Ambigüedades de `cleanAmount`
- **Dónde:** `MovimientoInventarioController.php:953-1013`.
- **Casos (test `test_clean_amount_ambiguedades`):** `"1,500"` → 1500 (en es-CO normalmente significa 1,5), `"0.001"` → 1, `"12.345,678"` → 12345.678 (pasa con 3 decimales). `CierreCajaController::store` (40-57) tiene **otro** normalizador distinto: `"1.5"` → 1.5, pero `"1,500"` → 1.5. Es decir, dos reglas contradictorias en el mismo sistema.
- **Corrección:** un único `App\Support\Monto::parse()` usado por todos los controladores, que en formato es-CO trate el punto como separador de miles y la coma como decimal, y que rechace (en lugar de reinterpretar) lo ambiguo. Que el JS envíe siempre el valor crudo en un campo oculto.

### M-24 — BAJA — Varios
- `ReporteFinancieroController:174-177, 408-411`: "monto anulados" suma en una sola cifra el `costo` de órdenes anuladas, el `total_documento` de facturas anuladas y el `monto` de movimientos manuales. Es una cifra sin significado contable; mostrarlas por separado.
- `MantenimientoController:221` (store) no tiene `max:99999999.99` para un `costo` DECIMAL(10,2), a diferencia de `update`/Electrónica. Con MySQL estricto daría un error genérico. Tampoco hay tope en `attachStock` (`increment('costo')`) ni en `precio_unitario` de venta o compra (DECIMAL(12,2)).
- `registrarVenta` acepta `precio_unitario` 0 y precios por debajo del costo (la vista solo advierte), sin registrar quién lo autorizó.

---

## Idempotencia y concurrencia

### Operaciones NO idempotentes (doble POST sin el JS de bloqueo: dos pestañas, reintento de red, doble clic)
F5 después de un POST exitoso **no** reenvía, porque todas responden con *redirect* (patrón PRG). En cambio, el reenvío de un POST que **falló por validación** y el doble clic o los reintentos de red sí duplican.

| Operación | Ruta | Efecto del duplicado | Mitigación actual |
|---|---|---|---|
| Venta | POST `inventario/venta` | 2 facturas, 2 ingresos, inventario ×2 | Solo el tope de stock |
| Compra | POST `inventario/compra` | 2 facturas, 2 egresos, inventario ×2 | ninguna |
| Edición de factura con `new_items` | PUT `inventario/facturas/{f}` | ítems y salida de inventario ×2 | ninguna (M-10) |
| Abono de orden (mant./elec.) | POST `.../abonos` | abono ×2 mientras quepa en el saldo | tope del saldo (bloquea solo el pago total) |
| Movimiento de caja manual | POST `caja` | ×2 | ninguna |
| Abono sobre movimiento de caja | POST `caja/{m}/abonos` | ×2 mientras quepa en el saldo | tope del saldo |
| Duplicar movimiento | POST `caja/{m}/duplicate` | ×2 movimientos activos | ninguna (M-15) |
| Repuesto a orden | POST `mantenimientos/{m}/stocks`, `electronicas/...` | cantidad y costo ×2 | ninguna |
| Baja de stock | POST de baja (`StockController`) | baja ×2 | tope del stock |
| Crear cotización / orden | POST `cotizaciones`, `mantenimientos` | documento ×2 (consume numeración) | ninguna |
| Anular cotización | POST `cotizaciones/{c}/anular` | alterna (M-21) | ninguna |

**Idempotentes hoy (correcto):** cierre de caja (índice único `fecha` + comprobación con bloqueo); convertir cotización (bloqueo de la fila + `estado !== 'pendiente'`); anular o reactivar órdenes, facturas y movimientos de caja (bloqueo + `accion`); eliminar abono (el segundo intento encuentra `anulado`). Conviene tener en cuenta que, si un formulario no envía `accion`, `verificarIntencion` devuelve `null` y la operación **alterna** (`AnulacionService:136`). Recomiendo que `accion` sea obligatoria.

### Mecanismo propuesto (token de idempotencia por formulario)
1. **Tabla** `idempotency_keys`: `key CHAR(36) PRIMARY KEY` (o UNIQUE), `user_id`, `ruta`, `estado ENUM('procesando','completado')`, `respuesta_url VARCHAR(500) NULL`, `created_at`. Limpieza programada de las claves con más de 48 h.
2. **Blade:** un componente `<x-idempotency />` que imprime `<input type="hidden" name="_idem" value="{{ Str::uuid() }}">`. Se incluye en todos los formularios de la tabla anterior. El UUID se genera al **renderizar** el formulario, de modo que dos pestañas tienen claves distintas y no se bloquean entre sí. Si la vista vuelve con `withInput()`, se reutiliza `old('_idem')` solo cuando el intento anterior **no** se completó.
3. **Middleware** `idempotente` (alias en `bootstrap/app.php`), aplicado a esas rutas:
   ```php
   $key = $request->input('_idem');
   if (! $key || ! Str::isUuid($key)) return back()->with('error', 'Formulario inválido, recargue.');
   try {
       DB::table('idempotency_keys')->insert(['key'=>$key,'user_id'=>auth()->id(),'ruta'=>$request->route()->getName(),'estado'=>'procesando','created_at'=>now()]);
   } catch (UniqueConstraintViolationException) {
       $fila = DB::table('idempotency_keys')->where('key',$key)->first();
       return $fila->estado === 'completado' && $fila->respuesta_url
           ? redirect($fila->respuesta_url)->with('info','Esta operación ya se había registrado.')
           : back()->with('error','La operación se está procesando; espere y recargue.');
   }
   $resp = $next($request);
   if ($resp->isRedirection() && ! session()->has('errors') && ! session()->has('error')) {
       DB::table('idempotency_keys')->where('key',$key)->update(['estado'=>'completado','respuesta_url'=>$resp->headers->get('Location')]);
   } else {
       DB::table('idempotency_keys')->where('key',$key)->delete(); // falló: permitir reintento con la misma clave
   }
   return $resp;
   ```
   El INSERT con clave única es atómico tanto en MySQL como en SQLite, así que no hace falta `Cache::lock` (la caché `file` no es atómica entre procesos). Así se cubren el doble clic, las dos peticiones simultáneas, el reintento de red y el reenvío del navegador.
4. **Endurecimiento opcional dentro de la transacción:** guardar `idempotency_key` también en `facturas` y `movimiento_cajas` (columna UNIQUE *nullable*). Si un *worker* muere entre el commit del negocio y el `update` a `completado`, la propia fila de negocio impide el duplicado.
5. Mantener el bloqueo JS como mejora de experiencia de usuario, no como control.

---

## Libro de movimientos (append-only)

### ¿Se puede reconstruir hoy cada movimiento de caja?
| Dato | ¿Disponible? | Dónde / falla |
|---|---|---|
| Valor original | Parcial | `eventos` (creado) guarda todos los atributos. Si el evento no se grabó (`try/catch`) o se borró, se pierde; el movimiento conserva solo el último monto (M-04 lo sobrescribe). |
| Usuario y fecha | Sí (alta) | `movimiento_cajas.user_id/created_at`. Una edición no cambia `user_id`; el editor solo queda en `eventos`. |
| Concepto, operación relacionada | Parcial | `factura_id/abono_id/parent_id` en los nuevos; en los *legacy*, por texto (M-01/M-02). |
| Modificaciones antes/después | Parcial | `eventos.valores_antiguos/nuevos`, editable y borrable (test). |
| Anulaciones / reversos | Parcial | `anulado`, `anulado_en_cascada` (flags que se sobrescriben al reactivar; no hay historia de cuántas veces se alternó salvo en `eventos`). |
| Justificación | **No** | No existe campo de motivo (M-09). |

### Diseño mínimo viable (sin romper lo actual)
**Tabla `libro_caja`** (solo INSERT):
```
id BIGINT PK AUTO_INCREMENT
movimiento_caja_id BIGINT NOT NULL (FK RESTRICT)
secuencia INT NOT NULL              -- 1,2,3… por movimiento; UNIQUE(movimiento_caja_id, secuencia)
operacion ENUM('alta','edicion','anulacion','reactivacion','anulacion_cascada','reactivacion_cascada')
origen VARCHAR(40)                  -- 'caja.store','venta','compra','abono_orden','abono_caja','factura.update','cotizacion.convertir','anulacion_orden', ...
documento_tipo VARCHAR(40) NULL, documento_id BIGINT NULL   -- Factura/Mantenimiento/Electronica/Abono
fecha_contable DATE NOT NULL        -- fecha del movimiento tras la operación
tipo_movimiento, tipo_pago
monto_antes DECIMAL(12,2) NULL, monto_despues DECIMAL(12,2) NOT NULL
efecto_caja DECIMAL(12,2) NOT NULL  -- delta firmado sobre el saldo: alta ingreso +m, egreso −m; anulación −(efecto vigente); edición = nuevo − anterior
datos_antes JSON NULL, datos_despues JSON NOT NULL
motivo VARCHAR(500) NULL            -- obligatorio en edicion/anulacion/reactivacion (validado en la aplicación)
user_id, ip, idempotency_key CHAR(36) NULL
hash_prev CHAR(64) NULL, hash CHAR(64) NOT NULL   -- sha256(hash_prev || fila canónica) — cadena de integridad
created_at TIMESTAMP NOT NULL
```

**Quién escribe:** un único servicio `LibroCaja::registrar(MovimientoCaja $m, string $operacion, ?array $antes, ?string $motivo, string $origen)`, llamado **dentro de la misma transacción**. Se engancha en un *observer* `MovimientoCajaObserver` (`created`/`updated`) para cubrir todas las rutas existentes: `HandlesAbono`, `registrarMovimientoCaja`, `MovimientoCajaController::store/update/storeAbono/duplicate/anular`, `AnulacionService::marcarMovimientosCaja`, `anularFactura`, `updateFactura`, `CotizacionController::convertir`. Los controladores pasan `origen` y `motivo` mediante un contexto (`LibroCaja::contexto(['origen'=>..., 'motivo'=>...])`, un *singleton* por petición). A diferencia de `Auditable`, **no** se usa `try/catch`: si el libro falla, la operación falla.

**Reglas operativas que acompañan al libro:**
- No se edita el monto de un movimiento con efecto en caja. Se anula (con motivo) y se registra uno nuevo; la edición queda solo para campos descriptivos (persona, descripción, concepto). Esto resuelve M-03, M-04 y M-05.
- `motivo` obligatorio en `anular`, `destroyAbono`, `anularFactura`, `AnulacionService` y `CierreCajaController::destroy`: un `textarea name="motivo"` en el modal global, `required|min:10`.

**Garantía append-only:**
- MySQL: *triggers* `BEFORE UPDATE` y `BEFORE DELETE` sobre `libro_caja` con `SIGNAL SQLSTATE '45000'`. Además, el usuario de la aplicación sin permiso `UPDATE/DELETE` sobre esa tabla (GRANT específico) si el hosting lo permite. Aplicar lo mismo a `eventos`.
- Modelo `LibroCaja` con `updating`/`deleting` que lanzan una excepción (defensa en la aplicación).
- Cadena `hash`: el comando `app:verificar-libro` recalcula la cadena y comprueba que, por cada movimiento, `Σ efecto_caja = (activo ? ±monto : 0)` y que por cada día `Σ efecto_caja (efectivo) = flujo del día`. Se programa a diario y su resultado se muestra en el preflight.
- Excluir `libro_caja` de `app:clean-data`/`ResetTests` en producción, y mantenerla al restaurar respaldos, como ya se hace con `eventos`.

**Consulta:**
- Vista "Historial" en `caja/show`: filas del libro por `movimiento_caja_id` ordenadas por `secuencia` (antes/después, usuario, motivo).
- Reporte "Libro de caja" por rango: `SELECT fecha_contable, tipo_pago, SUM(efecto_caja) ... GROUP BY`. Debe cuadrar con `calcularDia`, y cualquier diferencia indica una manipulación directa en la base.
- **Migración inicial:** una fila `alta` por cada movimiento existente (`efecto_caja` = estado actual), marcada `origen='migracion_inicial'`, para comenzar la cadena.

---

## Verificado y correcto
- `HandlesAbono::storeAbono`: bloqueo pesimista de la orden, relectura del saldo, guardián de cierre doble y vínculo `abono_id` exacto. `destroyAbono`: anulación lógica, cierre y autorización.
- `AnulacionService::revertirStockYAbonos`: solo restaura lo anulado en cascada y rechaza abonos en días cerrados. `verificarIntencion` funciona cuando se envía `accion`.
- `anularFactura`: bloqueo de la factura, intención, guardián en las fechas de los pagos y restauración de solo `anulado_en_cascada`.
- `registrarVenta/Compra`: bloqueo de stock en orden ascendente (sin *deadlocks*), tope de stock y pagado ≤ total. Numeración con `OrdenService` y `secuencias_documento`.
- `MovimientoCajaController::storeAbono`: bloqueo del padre, saldo releído y prohibición de abonar a nietos o a pagos de órdenes. `anular`: bloqueo e intención, sin reactivar pagos de documentos anulados ni sobrepasar el saldo de la orden.
- `CierreCaja::saldoInicialEfectivo`: usa el último conteo **real** y suma solo el efectivo intermedio (comprobado). Fecha del cierre única. `destroy` solo del último cierre.
- `StockService` entrada y salida atómicas. Precios automáticos de `Stock` (`precio_venta = compra × (1+u)`, `precio_tecnico = compra × (1+u/2)`, redondeo a 2 decimales) correctos según su definición, que es un **margen sobre costo**, no sobre venta, y está limitado a ≤ 100 %.
- Reportes diario y acumulado: excluyen anulados de forma consistente; los saldos de órdenes acumulados usan `max(0, Σcosto − Σabonos)`, correcto porque costo ≥ abonado por regla. `whereBetween` con `startOfDay/endOfDay` sobre DATE funciona en MySQL.
- Exports: formato `"$ "#,##0` y `SafeValueBinder`. Los totales del export financiero excluyen anulados.

## No revisado (o solo de forma superficial)
- `ElectronicaController`/`MantenimientoController` completos (solo las reglas de `costo`, `anular` vía servicio y duplicar); `ElectronicaStockController` asumido igual que el trait.
- `reporteOperaciones` (562-713) solo por muestreo; `DashboardController` solo los agregados de caja; `StocksExport`, `MantenimientosExport` y `ElectronicasExport` solo por *grep*.
- Vistas Blade de cálculo en JS (totales en venta, compra o edición): no verifiqué que el JS calcule igual que `calcularTotal`.
- `BackupController` (restaurar reemplaza toda la caja salvo `eventos`), que es de otro agente; `PosTicketService`; `SystemAuditCommand`.
- No ejecuté pruebas de concurrencia real en MySQL: los `lockForUpdate` no tienen efecto en SQLite. Las conclusiones de concurrencia salen de leer el código.

## Archivos revisados
`app/Traits/HandlesAbono.php`, `HandlesStockAttach.php`, `CalculaCostoOrden.php`, `Auditable.php`; `app/Services/AnulacionService.php`, `CierreCajaGuard.php`, `StockService.php`; `app/Http/Controllers/MovimientoInventarioController.php` (completo), `MovimientoCajaController.php` (completo), `CotizacionController.php` (store/update/anular/rechazar/reactivar/convertir), `CierreCajaController.php` (completo), `ReporteController.php`, `ReporteFinancieroController.php` (diario y acumulado), `DashboardController.php` (parcial), `AbonoController.php`, `MantenimientoStockController.php`, `StockController.php` (store/update); `app/Models/Factura.php`, `MovimientoCaja.php`, `CierreCaja.php`, `Mantenimiento.php`, `Stock.php`, `Evento.php`; `app/Exports/AcumuladoExport.php`, `ReportesFinancierosExport.php` (parcial); `app/Console/Commands/ResetMonthlyStats.php`; migraciones de montos y unicidad; `routes/web.php` (rutas de dinero); `resources/views/layouts/app.blade.php` (modal de anulación).
