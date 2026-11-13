# Auditoría técnica (backend y lógica de negocio)

> **Origen:** informe del agente 2 (backend) de la auditoría integral del 2026-11-13 (análisis con evidencia, sin modificar producción).
> **Coordinación:** el coordinador verificó los hallazgos ALTOS/CRÍTICOS antes de corregirlos y añadió este encabezado de estado.

## Estado tras las correcciones de esta ronda

| Hallazgo | Estado |
|---|---|
| B-01 cierre de órdenes sin reglas | **Corregido** + prueba `OrdenCierreReglasTest` (entrega con saldo: casilla + motivo + evento) |
| B-03 repuestos en órdenes terminadas | **Corregido** + prueba `test_b03_*` |
| B-02 quitar repuesto oculta sobrepago | **Corregido** + prueba `AuditoriaIntegralCorreccionesTest::test_b02_*` |
| B-12 el único admin podía quitarse el rol | **Corregido** + prueba `test_b12_*` |
| B-05 errores 500 con entradas inválidas | **Parcial**: el cierre de caja con fecha inválida ya responde con error de validación; el resto, pendiente |
| B-04, B-06 a B-11, B-13 a B-24 | **Pendientes** |

Los identificadores (p. ej. `M-01`) se usan igual en `CAMBIOS_REALIZADOS.md`, `PENDIENTES.md` y en los mensajes de commit.

---

## Informe del agente 2 (backend)

Proyecto: `C:\ServBay\www\tecnisystemas`. Fecha: 2026-11-13. Modo: solo lectura (las pruebas temporales están en la carpeta de trabajo de la auditoría (no versionada) y usan SQLite en memoria).

Pruebas de demostración, todas en verde: `agente2/*.php`
- `OrdenCicloVidaAuditTest.php`: 11 pruebas sobre el ciclo de vida de la orden, la trazabilidad y los 500.
- `Entradas500AuditTest.php`: URLs con entradas inválidas.
- `CotizacionLegacyAuditTest.php`: cruce COT-1 con COT-10.
- `FacturaUpdate500AuditTest.php`
- `AbonoMontoAuditTest.php`

Se ejecutan con `C:\ServBay\packages\php\current\php.exe vendor/bin/phpunit <ruta>`.

---

## 1. Lógica operativa de Mantenimiento / Electrónica: cómo funciona hoy

| Proceso | Implementación | Observación |
|---|---|---|
| Creación | `MantenimientoController@store` / `ElectronicaController@store`. El número `ORD-n`/`ELC-n` lo asigna `OrdenService::siguiente` con bloqueo. | Se puede crear una orden ya `terminado` sin `fecha_salida`. |
| Asignación de técnico | Campo `tecnico_id` en store/update (solo `exists`). | Acepta técnicos y equipos **inactivos** (B-07). El cambio de técnico queda en `Evento` como cambio de `tecnico_id`. |
| Repuestos | `HandlesStockAttach::attachStock/detachStock`: descuenta o devuelve stock y suma o resta `costo`. | No mira el estado de la orden. Al quitar un repuesto no comprueba lo abonado (B-02, B-03). |
| Abonos | `HandlesAbono::storeAbono`: tope = saldo, bloqueo de fila, revisión de cierre de caja y creación de un `MovimientoCaja` con `abono_id`. Para borrar se usa `destroyAbono` (anulación lógica). | Funciona bien en concurrencia y cierre de caja. No valida la fecha frente a `fecha_entrada` ni fechas futuras (B-07). |
| Estados | `pendiente` / `terminado` (enum), más el flag `anulado`. | **No hay reglas de transición** (B-01). |
| Fechas | `fecha_entrada` es obligatoria; `fecha_salida` es `nullable|after_or_equal:fecha_entrada`. | `fecha_salida` no depende del estado (B-01). |
| Factura | `factura()` solo exige `fecha_salida`. | No exige `terminado` ni mira el saldo (B-01). Si falla, redirige al index, que el invitado no puede ver (B-17). |
| Consulta del cliente | Doble factor (cédula + orden) en `consulta()` ×2 y en `GuestController@search`. | Lógica triplicada (tabla §5). |
| Anulación | `AnulacionService::revertirStockYAbonos` con bloqueo e intención explícita. | Correcto; ya estaba corregido. |

### Transiciones verificadas en código y con pruebas

| Pregunta | Respuesta | Evidencia |
|---|---|---|
| ¿Se puede marcar `terminado` sin `fecha_salida`? | **Sí** | `test_terminado_sin_fecha_salida_y_con_saldo` |
| ¿Se puede marcar `terminado` con saldo pendiente? | **Sí** (el saldo total sigue pendiente) | misma prueba |
| ¿Se puede volver de `terminado` a `pendiente`? | **Sí**, sin motivo ni autorización. Para el técnico basta la clave de admin que ya exige toda edición. | `test_terminado_a_pendiente` |
| ¿Se puede editar, abonar o agregar repuestos a una orden terminada? | **Sí, las tres cosas.** Un repuesto añadido a una orden terminada y pagada vuelve a abrir el saldo sin cambiar el estado. | `test_repuesto_en_orden_terminada_pagada` |
| ¿Qué pasa con la factura sin fecha de salida? | Se bloquea, pero una orden **pendiente con `fecha_salida`** sí se puede facturar. | `test_pendiente_con_fecha_salida_permite_factura` |
| ¿Hay operaciones fuera de orden? | Sí: abono con fecha anterior a la entrada o un año en el futuro; repuestos después de entregar; quitar un repuesto ya pagado. | `test_abono_fecha_futura`, `test_detach_repuesto_deja_costo_menor_que_abonado` |
| ¿Hay trazabilidad por orden? | Parcial (B-04): se registran estado, costo, técnico y abono como `Evento` genérico, pero no qué repuesto se añadió y no existe vista por orden. | `test_trazabilidad_repuesto` |

### Reglas de cierre propuestas (sin complejidad innecesaria)

1. **Cerrar la orden (`pendiente` → `terminado`)**
   - `fecha_salida` obligatoria (`required_if:estado,terminado`), con `after_or_equal:fecha_entrada` y `before_or_equal:today`.
   - `saldo_pendiente == 0`. **Excepción autorizada "entrega con saldo (crédito)"**: una casilla `entrega_con_saldo` más un motivo obligatorio. Para el técnico, además, la clave de admin. Se registra un `Evento` con acción `cierre_con_saldo`.
2. **`fecha_salida` solo con `terminado`**
   - En estado `pendiente`, `fecha_salida` debe ser `null` (`prohibited_unless:estado,terminado`).
   - La factura exige `estado === 'terminado'`. Si hace falta un comprobante de recepción, debe ser un PDF distinto.
3. **Reabrir (`terminado` → `pendiente`)**
   - Solo admin, o técnico con clave de admin.
   - Motivo obligatorio. Se borra `fecha_salida` y queda un `Evento` `reabierto`.
4. **Orden terminada**
   - Se bloquea agregar o quitar repuestos y cambiar `costo`, `equipo_id` o `tecnico_id` mientras no se reabra.
   - Se **permiten** abonos, porque cobrar el saldo es justamente lo que queda pendiente en una entrega a crédito.
5. **Centralizar las reglas** en un `OrdenEstadoService` (o en el trait `CalculaCostoOrden`), con métodos `puedeCerrar()`, `cerrar()` y `reabrir()` compartidos por ambos módulos, en lugar de reglas sueltas en cada `update`.

### Mejoras funcionales de valor (bajo costo)

- **Historial en la ficha de la orden.** Un parcial en `show` con `Evento::where('modelo_tipo', Mantenimiento::class)->where('modelo_id', $id)` más los eventos de sus `Abono`, y una línea por repuesto añadido o quitado. Se renderiza "fecha · usuario · cambio" usando `$lookups` para nombres de técnico y equipo. Hace falta un índice `(modelo_tipo, modelo_id)` en `eventos` (verificarlo con el agente de BD).
- **Fecha prometida y alerta de atraso.** Columna `fecha_prometida` (date, nullable) y un scope `atrasadas()` (`pendiente`, `fecha_prometida < today`). Con eso se añade un badge rojo en el index y en la barra superior. Hoy la barra cuenta pendientes, con tope de 50 (B-22), y el login muestra "días transcurridos" (`AuthController.php:254-306`) pero sin un compromiso con el cliente.
- **Lista "por cobrar".** Órdenes `terminado` con `saldo_pendiente > 0`. Hoy no aparecen en ninguna alerta.
- **Kardex del producto** con su uso en órdenes (B-23).

---

## 2. Hallazgos (B-xx)

### ALTA

#### B-01 · ALTA · El cierre de la orden no tiene reglas: terminado sin salida ni pago, factura de orden pendiente y reapertura libre
- **Archivos:** `app/Http/Controllers/MantenimientoController.php:215-225` (store), `:272-282` (update), `:315` (factura); `app/Http/Controllers/ElectronicaController.php:80-90`, `:151-161`, `:238`.
- **Evidencia:** las reglas son `'estado' => 'required|in:pendiente,terminado'` y `'fecha_salida' => 'nullable|date|after_or_equal:fecha_entrada'`, sin relación entre ellas. La factura solo revisa `if (! $mantenimiento->fecha_salida)`.
- **Escenarios (todos comprobados con prueba):**
  - (a) La orden queda `terminado` sin fecha de salida y con $50.000 de saldo. Luego la factura no se puede generar.
  - (b) Una orden `pendiente` con `fecha_salida` sí genera factura.
  - (c) `terminado` vuelve a `pendiente` sin motivo.
- **Efecto colateral:** `AnulacionService::bloqueoPorOrdenesPendientes` (`AnulacionService.php:165-172`) solo cuenta `pendiente`. Si se marca `terminado` sin cerrar de verdad, se puede desactivar el técnico o el equipo de una orden que sigue abierta.
- **Corrección:** las reglas del §1, en un servicio compartido. En validación:

  ```php
  'fecha_salida' => ['nullable','date','after_or_equal:fecha_entrada','before_or_equal:today','required_if:estado,terminado','prohibited_unless:estado,terminado'],
  ```

  En el cierre, si `saldo_pendiente > EPSILON` y no viene `entrega_con_saldo` con motivo, se rechaza. En `factura()` se exige `estado === 'terminado'`.

#### B-02 · ALTA · Quitar un repuesto después de pagar deja `costo < abonado` y oculta el sobrepago
- **Archivo:** `app/Traits/HandlesStockAttach.php:95-116`.
- **Evidencia:** `$lockedModel->decrement('costo', $precio * $cantidad);` sin comparar con `total_abonado`. El `update` de la orden sí protege este caso con `costoMinimoPermitido()` (`CalculaCostoOrden.php:23`), pero el detach lo esquiva. `getSaldoPendienteAttribute` hace `max(0, …)` y el sobrepago desaparece de la vista.
- **Escenario (`test_detach_repuesto_deja_costo_menor_que_abonado`):**
  1. Mano de obra de $50.000 más un repuesto de $50.000 dan `costo = 100.000`.
  2. El cliente paga $100.000.
  3. Se quita el repuesto: `costo = 50.000`, `total_abonado = 100.000`, `saldo = 0`.
  4. Hay $50.000 cobrados de más, sin alerta ni devolución.
- **Corrección:** dentro de la transacción, después del bloqueo:

  ```php
  if ((float)$lockedModel->costo - $precio*$cantidad < $lockedModel->total_abonado - Factura::EPSILON) throw new \DomainException('No se puede quitar el repuesto: el costo quedaría por debajo de lo abonado ($…). Anule primero el abono correspondiente o registre la devolución.');
  ```

  Conviene también exponer un atributo `saldo_a_favor = max(0, abonado − costo)` para que nunca quede oculto.

### MEDIA

#### B-03 · MEDIA · Se pueden agregar o quitar repuestos (stock y costo) en órdenes terminadas o ya facturadas
- **Archivo:** `app/Traits/HandlesStockAttach.php:17-21` y `:85-93`. Solo se revisa `anulado`.
- **Escenario (`test_repuesto_en_orden_terminada_pagada`):** una orden entregada y pagada recibe un repuesto. El estado sigue `terminado`, el saldo reaparece en $50.000 y la factura reimpresa ya no coincide con la entregada.
- **Corrección:** en `attachStock` y `detachStock`, rechazar si `estado === 'terminado'`, con el mensaje "Reabra la orden para modificar repuestos" (ver reapertura en §1).

#### B-04 · MEDIA · Trazabilidad incompleta e imposible de consultar por orden
- **Archivos:** `app/Traits/Auditable.php:57-73`; `app/Http/Controllers/EventoController.php:16-55`; `app/Traits/HandlesStockAttach.php:44-61`.
- **Evidencia:**
  - La descripción siempre es genérica ("Actualizó un registro de Mantenimiento") y no incluye `id_orden`.
  - `EventoController@index` busca solo en `descripcion` y `modelo_tipo`. No se puede filtrar por `modelo_id` ni por número de orden, y la vista es solo para admin.
  - `attach`/`updateExistingPivot`/`detach` de la tabla pivot no disparan eventos. Al agregar un repuesto quedan dos eventos sin relación entre sí: `Stock{cantidad:9}` y `Mantenimiento{costo:100000}`. No queda qué repuesto ni cuántas unidades (`test_trazabilidad_repuesto`).
- **Escenario:** el cliente reclama que le cobraron un repuesto. Nadie puede reconstruir quién lo añadió ni cuándo sin revisar la tabla `eventos` a mano.
- **Corrección:**
  1. En `attachStock`/`detachStock`, llamar a `Evento::registrar('repuesto_agregado'|'repuesto_quitado', $lockedModel, null, ['stock_id'=>…,'producto'=>…,'cantidad'=>…,'precio_unitario'=>…], "Repuesto {$stock->producto} ×{$cant} en {$id_orden}")`.
  2. En `Auditable`, incluir en la descripción `$model->id_orden ?? $model->numero_factura ?? $model->codigo ?? '#'.$model->id`.
  3. Añadir el parcial "Historial" en `show` (§1).

#### B-05 · MEDIA · Errores 500 con entradas de usuario (validación ausente o tardía)
Todos confirmados con prueba:

| Petición | Resultado | Causa |
|---|---|---|
| `GET /reportes-financieros/diario?fecha=xyz`, `acumulado?desde=xyz`, `operaciones?desde=xyz` | 500 | `Carbon::parse($request->fecha)` sin validar (`ReporteFinancieroController.php:27-29, 298-304, 565-566`) |
| `GET /reportes-financieros/operaciones?tipo=otro&export=pdf` | 500 | `$tipoLabels[$tipo]` con clave inexistente (`:704`) |
| `GET /mantenimientos-reportes?search[]=a`, `/electronicas?search[]=a`, `/caja?search[]=a` | 500 | Conversión de arreglo a string en `"%{$search}%"` |
| `DELETE /mantenimientos/{id}/stocks/abc` | 500 | Firma `destroy(Mantenimiento $m, int $stock_id)` → TypeError (`MantenimientoStockController.php:27`, igual en Electronica) |
| `PUT /inventario/facturas/{id}` con `facturable_global=X` | 500 | `[$type,$id] = explode(':', …)` **fuera** del try (`MovimientoInventarioController.php:678`) |
| `POST /mantenimientos/{id}/abonos` con `monto=abc` | 500 | `number_format($request->monto…)` se evalúa en el controlador **antes** de validar (`AbonoController.php:20`, `ElectronicaAbonoController.php:20`). Además ese mensaje no se usa (ver código muerto). |
| `PUT /mantenimientos/{id}` como técnico con `admin_password[]=x` | TypeError | `required` acepta un arreglo y luego se llama `adminPasswordValida(string $password)` (`MantenimientoController.php:285,293`; `AnulacionService.php:42,109`) |
| `GET /api/municipios?departamento[]=x` | TypeError (por lectura) | `ColombiaHelper::municipiosDe(string)` |

- **Corrección:**
  - Validar `fecha|desde|hasta => nullable|date` y `tipo => in:…`.
  - `search => nullable|string|max:100`.
  - Ruta `->whereNumber('stock_id')`.
  - `facturable_global => ['required','regex:/^(Cliente|Proveedor):\d+$/']`, la misma regla que ya usa `EquipoController:42`, también en compra, venta y cotización.
  - Quitar el `number_format` del controlador de abonos.
  - `admin_password => required|string`.
  - En `autorizarOperacionSensible`, `is_string($password)`.

#### B-06 · MEDIA · Las órdenes no guardan una copia del cliente: cambiar el propietario de un equipo, o el equipo de una orden, reescribe el historial
- **Archivos:** `app/Http/Controllers/EquipoController.php:118-160` (update permite cambiar `cliente_id`/`proveedor_id` sin mirar órdenes); `MantenimientoController.php:280` y `ElectronicaController.php:152` (`equipo_id` editable con abonos registrados).
- **Escenario:** el equipo X del cliente A tiene ORD-5 pagada. Se corrige el propietario a B. Entonces ORD-5 aparece en la consulta con la cédula de B, la factura reimpresa sale a nombre de B y el `MovimientoCaja.persona` del abono sigue diciendo A.
- **Corrección:** bloquear el cambio de propietario si el equipo tiene órdenes no anuladas, o exigir clave de admin y registrar un `Evento`. En la orden, impedir cambiar `equipo_id` si `total_abonado > 0` o si `estado === 'terminado'`. La alternativa es guardar `cliente_id` en la orden al crearla.

#### B-07 · MEDIA · Validaciones insuficientes o distintas entre módulos gemelos
| Caso | Mantenimiento | Electrónica | Evidencia / riesgo |
|---|---|---|---|
| `costo` en store | sin `max` | `max:99999999.99` | `MantenimientoController.php:221`. Con la columna `decimal(10,2)`, en MySQL estricto da un error SQL y luego un mensaje genérico. |
| Descripción | `max:500` | `max:1000` | Las dos columnas son `text`. |
| `equipo_id`/`tecnico_id` | solo `exists` | solo `exists` | Store y update aceptan técnico o equipo **inactivo o dado de baja**, aunque la vista solo lista activos. Debe ser `Rule::exists('tecnicos','id')->where('active',true)`, permitiendo en update el valor actual. |
| Abono `fecha` | `required|date` | igual | Acepta una fecha anterior a `fecha_entrada` y un año en el futuro (`test_abono_fecha_futura`). El abono futuro cae en la caja de un día que todavía no existe. Añadir `after_or_equal:fecha_entrada` y `before_or_equal:today`. |
| Abono `monto` | `numeric|min:0.01` | igual | Falta `decimal:0,2`. Normalización: inventario usa `cleanAmount()` en el servidor y los abonos dependen de JavaScript. Si el JS falla, `'1.000'` se guarda como **$1** (`AbonoMontoAuditTest`). |
| `fecha_entrada` | `required|date` | igual | Acepta fechas futuras. |
| Reportes, rango por defecto | mes actual | **ninguno** (`ElectronicaController.php:273-281`) | El export de Electrónica sin filtros vuelca toda la tabla. |
| Concepto de caja | `ConceptoCajaController` `max:80` | `MovimientoCajaController@storeConcepto` `max:255` | Reglas distintas para el mismo campo. |
| Venta, compra y convertir cotización | — | — | `items.*.stock_id => exists` acepta productos **anulados** (`MovimientoInventarioController.php:65, 222`; `CotizacionController@convertir` no revisa `active`). |

- **Corrección:** un FormRequest compartido `OrdenRequest` (ya figura como deuda #4) con las reglas unificadas de esta tabla.

#### B-08 · MEDIA · `Cotizacion::factura_creada` enlaza COT-1 con la factura de COT-10, y hace N+1 en el listado
- **Archivo:** `app/Models/Cotizacion.php:51-61`.
- **Evidencia:** `Factura::where('observaciones', 'like', "%Cotización #{$this->codigo}%")`. `convertir()` sigue escribiendo "…desde Cotización #COT-10" (`CotizacionController.php:395`).
- **Escenario (`CotizacionLegacyAuditTest`):** con COT-1 pendiente y COT-10 convertida en VT-9, la ficha de COT-1 muestra el botón "Ver Venta (VT-9)" (`cotizaciones/show.blade.php:32`). Es el mismo defecto de prefijo que ya se corrigió en caja con `referenciaExacta`.
- **Rendimiento:** en `cotizaciones/index.blade.php:108-110` el accessor se llama 3 veces por fila y no se memoriza. En las cotizaciones aprobadas sin `factura_id` (las heredadas) eso son 3 `LIKE '%…%'` por fila.
- **Corrección:**
  - Usar solo `factura_id`. Para los datos heredados, una migración de datos que rellene `factura_id`.
  - Si se mantiene el respaldo por texto, usar un patrón exacto (`REGEXP '#COT-1([^0-9]|$)'`) y memorizar el resultado en `$this->attributes`.
  - No buscar nunca si `estado !== 'aprobada'`.

#### B-09 · MEDIA · Cotizaciones: ítems de stock sin producto, destinatario sin validar y anulación sin intención
- **Archivo:** `app/Http/Controllers/CotizacionController.php`.
- **Ítems:** `items.*.item_id` es `nullable` aunque `tipo=stock` (`:54`). Al convertir, `stockItems` filtra `&& $it->item_id` (`:345`), así que el ítem se factura **sin mover inventario**. Corrección: `'items.*.item_id' => 'required_if:items.*.tipo,stock|nullable|exists:stocks,id'`.
- **Destinatario:** `facturable_global` se divide sin validar que el id exista (`:58-67`, `:173-182`). Un id inexistente rompe la FK y produce el mensaje genérico, o el SQL completo si `APP_DEBUG` está activo. Corrección: la misma regex de B-05 más una comprobación `exists`.
- **Anulación:** `anular()` (`:262-292`) alterna `anulado` sin `verificarIntencion` ni `lockForUpdate`, a diferencia de órdenes, facturas, clientes, equipos y técnicos. Su `catch` devuelve `$e->getMessage()` crudo **sin registrar en el log** (`:290`). Lo mismo pasa con `StockController@anular` (`StockController.php:314-327`), que alterna sin intención.
- **Escenario:** un doble clic en "anular" reactiva la cotización.

#### B-10 · MEDIA · N+1 y volcado completo en los reportes y exportes de Electrónica
- **Evidencia:**
  - `ElectronicaController@reportes` carga relaciones **después** de las ramas de exportación (`:353-367`). Excel y PDF se generan con `Electronica::query()->get()` sin `with`.
  - `ElectronicasExport.php:128-134` y `electronicas/pdf.blade.php:277-281` acceden a `equipo->cliente`, `tecnico` y `propietario_*` (proveedor), unas 4 consultas por fila.
  - Sin rango por defecto (B-07), exportar "todo" con 5.000 órdenes son unas 20.000 consultas y probable timeout.
  - `ReporteFinancieroController@reporteOperaciones` (`:573, 621`) carga Electrónica solo con `['tecnico','user']`, sin `equipo.cliente`.
- **Corrección:** declarar `$query->with(['equipo.cliente','equipo.proveedor','tecnico','user'])` al construir la consulta, aplicar un rango por defecto igual al de Mantenimiento y poner un tope de filas o usar `FromQuery`/chunk en los exportes.

#### B-11 · MEDIA · La búsqueda de los listados de órdenes solo filtra los 10 registros de la página
- **Evidencia:**
  - `mantenimientos/index.blade.php:15,216` y `electronicas/index.blade.php:16,217` usan `filterTable()` en el navegador, sobre la tabla ya paginada.
  - `MantenimientoController@index` no tiene filtro en el servidor.
  - `ElectronicaController@index:40-58` implementa `search` y `estado`, pero **ningún formulario los envía**: es código muerto, y además inconsistente con Mantenimiento.
- **Escenario:** el usuario busca "ORD-15" estando en la página 1 y ve "sin resultados" aunque la orden existe.
- **Corrección:** un formulario GET con `search` y `estado` hacia el servidor, la misma lógica en ambos controladores (scope `buscar()` compartido) y `->withQueryString()` en la paginación.

#### B-12 · MEDIA · Un administrador puede quitarse su propio rol y dejar el sistema sin administradores
- **Archivo:** `app/Http/Controllers/UserController.php:117-120`.
- **Evidencia:** `if (auth()->user()->role === 'admin') { $usuario->role = $request->role; }` no excluye `$usuario->id === auth()->id()` ni comprueba que quede otro admin activo. `anular()` protege solo la propia cuenta (`:168`).
- **Escenario:** el único admin edita su perfil y elige "técnico". Ya nadie puede anular, ver eventos, respaldos o usuarios, y las operaciones del técnico que piden clave de admin fallan siempre (`adminPasswordValida` filtra `role=admin, active=true`).
- **Corrección:** rechazar el cambio de rol propio y bloquear la desactivación o degradación si `User::where('role','admin')->where('active',true)->count() === 1`.

#### B-23 · MEDIA · El historial (kardex) del producto omite su uso como repuesto
- **Archivo:** `app/Http/Controllers/StockController.php:296-307` (`show`). Solo lee `FacturaItem` y las bajas.
- **Escenario:** un producto baja de 10 a 6 por repuestos en órdenes, pero su ficha solo muestra compras y ventas. Las 4 unidades "desaparecen" para quien revisa el inventario. Tampoco aparecen las devoluciones por anulación ni los ajustes manuales (`StockController@update`, que solo dejan un `Evento` genérico de `cantidad`).
- **Corrección:** unir en `show` las relaciones `mantenimientos()` (ya existe en `Stock.php:59`) y `electronicas()` con su pivot (`cantidad`, `precio_unitario`, `created_at`, `id_orden`, `anulado`).

### BAJA

#### B-13 · BAJA · El dashboard ejecuta 4 consultas cuyo resultado no usa la vista
- **Archivo:** `app/Http/Controllers/DashboardController.php:162-194`.
- **Evidencia:** `$electronicaRecientes`, `$recentCot` (con `items`), `$clientes` (**toda la tabla**), `$tecnicos`, `$totalCostoFormateado` y `$totalCostoDiaFormateado` no aparecen en `dashboard.blade.php` (0 ocurrencias cada uno, verificado con grep).
- **Otros defectos:** `$electronicaRecientes` además incluye anuladas (`:162-166`, sin `where('anulado', false)`). `stock_bajo` (`:36`) cuenta productos inactivos.
- **Corrección:** eliminar esas variables y consultas, y filtrar `active` en `stock_bajo`.

#### B-14 · BAJA · Carga de tablas completas para selects y diccionarios
- **Evidencia:**
  - `EventoController::getEntityLookups()` (`:82-97`) hace 8 `pluck`/`get` de tablas enteras (usuarios, clientes, proveedores, técnicos, categorías, conceptos, stocks, equipos) en **cada** carga de `index` y `show`.
  - Los reportes de Mantenimiento y Electrónica cargan todos los clientes, equipos y usuarios (`MantenimientoController.php:187-190`, `ElectronicaController.php:368-371`).
  - `create`/`edit` de órdenes carga todos los equipos activos con su cliente.
  - `StockController@create/edit` usa `Proveedor::all()`, que incluye inactivos.
- **Corrección:** en eventos, resolver solo los ids presentes en la página (`collect($eventos)->pluck('valores_nuevos.*_id')`). En selects grandes, Tom Select con búsqueda remota.

#### B-15 · BAJA · `OrdenService` recorre todos los números existentes en cada creación, y la vista previa escribe en BD desde un GET
- **Archivo:** `app/Services/OrdenService.php:91, 119-144` (`pluck($columna)` de toda la tabla dentro del bloqueo) y `:68-73` (`insertOrIgnore` incluso con `$lock=false`, llamado desde `create()`).
- **Corrección:**
  - Calcular el máximo en SQL: `MAX(CAST(SUBSTRING(id_orden, LENGTH(?)+1) AS UNSIGNED))`, o solo en el respaldo cuando la secuencia es 0 o viene de una restauración.
  - En modo vista previa, no insertar.

#### B-16 · BAJA · Lista y exportación de `reporteOperaciones` no coinciden
- **Archivo:** `ReporteFinancieroController.php:579-591` contra `:627-638`.
- **Evidencia:** la lista de ingresos y egresos no filtra `estado='activo'` y el export sí. Además los 6 bloques `match` están duplicados (unas 40 líneas).
- **Corrección:** una sola función `consultaOperaciones($tipo,$desde,$hasta)` que devuelva el builder, y luego `paginate()` o `get()`.

#### B-17 · BAJA · La factura sin fecha de salida redirige al index, que el invitado no puede ver
- **Archivos:** `MantenimientoController.php:315-319`, `ElectronicaController.php:238-242`.
- **Escenario:** el invitado consulta su orden pendiente y pulsa "Factura". Lo envían a `mantenimientos.index`, `CheckRole` lo saca al dashboard con "No tienes permisos…" y el mensaje real se pierde.
- **Corrección:** `return back()->with('error', …)`, o redirigir a `show`.

#### B-18 · BAJA · Datos del movimiento de caja del abono
- **Archivo:** `app/Traits/HandlesAbono.php:186-193`. `getPersona` usa `equipo->cliente->nombre`. En equipos de **proveedor** guarda "Cliente Mantenimiento" en lugar de `propietario_nombre`.
- **Concepto "Pago Total/Final":** se decide al momento del abono (`:85-97`). Si después se añade un repuesto, el concepto queda falso.
- **Código muerto en el mismo trait:** `$successMsg` se recibe pero no se usa (`:24, 119-122`), y `($model->estado ?? null) === 'anulado'` (`:34`) nunca es verdadero, porque el enum no tiene ese valor.
- **Corrección:** `$model->equipo?->propietario_nombre`, y eliminar el parámetro y la rama muertos.

#### B-19 · BAJA · Las rutas de borrado de abonos aceptan abonos del otro módulo
- **Archivos:** `routes/web.php:154,156`; `AbonoController::destroy(Abono $abono)` y `ElectronicaAbonoController::destroy` son idénticos.
- **Evidencia:** `DELETE /electronicas/abonos/{id}` anula un abono de **mantenimiento** (`test_destroy_abono_ruta_cruzada`). No hay escalada de privilegios, porque ambas exigen la misma autorización, pero hay dos rutas para la misma operación.
- **Corrección:** una sola ruta, o validar `abort_unless($abono->electronica_id, 404)`. Ver la tabla de duplicación.

#### B-20 · BAJA · Renombrar conceptos de sistema rompe el agrupamiento contable
- **Archivo:** `ConceptoCajaController.php:31-37` permite renombrar "Abono Mantenimiento", "Pago Mantenimiento", "Venta de Inventario", etc.
- **Escenario:** el código vuelve a hacer `ConceptoCaja::firstOrCreate(['nombre' => 'Pago Mantenimiento'])` (`HandlesAbono.php:103`, `MovimientoInventarioController.php:1050`, `CotizacionController.php:438`) y crea un **concepto duplicado**. Los reportes por concepto quedan partidos en dos.
- **Corrección:** marcar los conceptos de sistema (columna `sistema` o una lista constante) y prohibir renombrarlos o eliminarlos.

#### B-21 · BAJA · Condición de carrera en la validación del costo mínimo
- **Archivos:** `MantenimientoController.php:278` y `ElectronicaController.php:156`. `costoMinimoPermitido()` se calcula **fuera** de la transacción y sin bloqueo.
- **Escenario:** un abono simultáneo entre la validación y el `update` deja `costo < abonado`.
- **Corrección:** dentro de la transacción, `lockForUpdate()` y volver a comprobar `costo >= costoMinimoPermitido()`.

#### B-22 · BAJA · Contadores de la barra superior con tope de 50
- **Archivo:** `app/Providers/AppServiceProvider.php:140-150, 286-295`. `count($mantList)` se calcula sobre `limit(50)`, así que con 120 pendientes el badge dice 50.
- **Corrección:** `->count()` aparte (cacheado) para el número, y la lista limitada solo para el desplegable.

#### B-24 · BAJA · Código de error de `updateFactura` y lógica muerta
- **Archivo:** `MovimientoInventarioController.php:687-688`. `$wasAnulada` es siempre `false`, porque `:616` ya retornó si la factura estaba anulada, así que las ramas `if (! $shouldBeAnulada)` son siempre verdaderas.
- **Otro caso:** `printFactura` usa `$factura->detalles` (`:430`), una relación inexistente; nunca se evalúa porque `items` siempre está cargada.
- **Corrección:** eliminar ambas cosas.

---

## 3. Lo revisado que está correcto

- **Abonos:** doble verificación del saldo, antes y dentro de la transacción con `lockForUpdate`; bloqueo de cierre de caja; anulación lógica con un `MovimientoCaja` vinculado por `abono_id`.
- **Anulación y reactivación de órdenes:** bloqueo de fila, `verificarIntencion`, cascada controlada (`anulado_en_cascada`), cierre de caja respetado y stock revertido con `StockService`. Ya estaba corregido y no hay regresión.
- **`StockService`:** entrada, salida y baja atómicas con relectura bajo bloqueo; no hay saldo negativo.
- **`OrdenService`:** numeración atómica sincronizada con el máximo (solo tiene el problema de rendimiento de B-15).
- **Consulta del invitado:** doble factor; un número solo se interpreta como `ORD-n`, nunca como id; la sesión guarda los ids autorizados.
- **Compra y venta:** bloqueo de stock en orden canónico (sin deadlocks), tope de stock, `cleanAmount` y cierre de caja.
- **`Auditable`:** enmascara datos sensibles y no tumba la operación si falla la auditoría.
- **Listados principales:** `mantenimientos.index` y `electronicas.index` cargan `abonos` por adelantado (sin N+1 en `saldo_pendiente`); `recentMant` usa `withSum`.
- **Catálogos:** desactivar cliente, equipo o técnico se bloquea si tienen órdenes pendientes; validación `store` y `update` consistente en Cliente, Proveedor, Técnico y Equipo.
- **Middleware:** `CheckRole` (cuenta inactiva → logout) y `RequirePasswordChange` son correctos.

---

## 4. Código muerto (verificado con grep en app, resources, routes y tests)

| Elemento | Ubicación | Evidencia | Acción |
|---|---|---|---|
| Ruta `electronicas.dismiss-alert` | `routes/web.php:66-70` | La sesión `alertas_electronica` no se escribe en ningún sitio y ninguna vista llama a la ruta | Eliminar |
| `AnulacionService::passwordValida()` | `AnulacionService.php:76-85` | Solo la usa `tests/Unit/AnulacionServiceTest.php` | Eliminar junto con su prueba |
| Parámetros `$conceptoAbono`, `$prefijosDescripcion` | `AnulacionService::revertirStockYAbonos` | No se usan; los llamadores los siguen pasando | Quitar de la firma y de los 2 llamadores |
| Parámetro `$successMsg` en `storeAbono` | `HandlesAbono.php:24` | No se usa; además provoca B-05 (`number_format` antes de validar) | Quitar |
| Rama `estado === 'anulado'` | `HandlesAbono.php:34` | El enum es `pendiente,terminado` | Quitar |
| `OrdenService::generarLegacy()` | `OrdenService.php:149-174` | La tabla `secuencias_documento` existe desde la migración 2026_10_21 | Eliminar, o dejar con un aviso en el log |
| Gates `manage-caja`, `close-caja`, `anular-operacion`, `manage-users`, `view-financial-reports`, `manage-backups`, `promote-tecnico` | `AppServiceProvider.php:97-104` | 0 usos (`promote-admin` solo en `SystemAuditCommand`) | Aplicarlos o eliminarlos (deuda #5) |
| Policies `FacturaPolicy`, `CotizacionPolicy`, `CierreCajaPolicy`, `MovimientoCajaPolicy`, `StockPolicy`, `ClientePolicy`, `UserPolicy` y `MantenimientoPolicy/ElectronicaPolicy::update/delete` | `app/Policies/*` | `Gate::authorize`/`can` solo aparece en Mantenimiento y Electrónica (`viewAny`, `view`, `create`) | Igual: deuda #5 |
| Filtros `search`/`estado` de `ElectronicaController@index` | `:40-58` | Ningún formulario los envía (B-11) | Conectarlos o quitarlos |
| Variables del dashboard `recentCot`, `clientes`, `tecnicos`, `electronicaRecientes`, `totalCostoFormateado`, `totalCostoDiaFormateado` | `DashboardController.php:52-53, 157-194` | 0 usos en la vista (B-13) | Eliminar |
| Accessors `Cliente::genero_label`, `tipo_cliente_label`, `tipo_identificacion_abreviado` | `Cliente.php:87-106` | 0 usos (el de Proveedor sí se usa) | Eliminar |
| `Tecnico::mantenimientos()` | `Tecnico.php:52` | 0 usos (`->mantenimientos` solo aparece en el export, con otra variable) | Eliminar, o usarlo en B-01 |
| Hook `Abono::booted deleting` | `Abono.php:48-55` | Los abonos nunca se borran: se anulan lógicamente y la FK es RESTRICT | Eliminar, o lanzar excepción para impedir el borrado físico |
| `$factura->detalles`, `$wasAnulada/$shouldBeAnulada` | `MovimientoInventarioController.php:430, 687-688` | Relación inexistente y condición siempre falsa (B-24) | Eliminar |
| Cálculo `$fallbackHeight` POS | 3 controladores | Solo se usa si falla la medición del ticket; se puede mover dentro de `PosTicketService` | Simplificar |

---

## 5. Código duplicado (cuantificado)

| # | Bloque | Ubicaciones | Tamaño | Propuesta |
|---|---|---|---|---|
| D1 | Controlador de orden Mantenimiento ↔ Electrónica (index/locate, create, store, edit, update, factura, anular, reportes, consulta) | `MantenimientoController.php` (437 l.) y `ElectronicaController.php` (423 l.) | **≈282 líneas no vacías idénticas** tras normalizar nombres (≈74 %) | Un `OrdenController` abstracto o un trait `GestionaOrden` con `modelo()`, `prefijo()`, `campoDescripcion()` y `rutaBase()` |
| D2 | Filtros de reportes (cliente, equipo, técnico, usuario, tipo_rep, estado, anulado, costo, search) | `MantenimientoController.php:97-169` y `ElectronicaController.php:271-346` | ≈75 líneas ×2 | Un scope `filtrarReporte(Request)` en el trait `CalculaCostoOrden` (o un trait nuevo `EsOrden`) |
| D3 | Consulta con doble factor | `MantenimientoController@consulta` (43 l.), `ElectronicaController@consulta` (43 l.), `GuestController@search` (79 l.) | ≈35 líneas ×3 | `ConsultaOrdenService::buscar($modelo, $prefijo, $identificacion, $orden)` |
| D4 | Modelos `Mantenimiento` ↔ `Electronica` (`total_abonado`, `saldo_pendiente`, `scopeActivos`, relaciones) | `Mantenimiento.php` y `Electronica.php` | ≈45 líneas | Mover al trait `CalculaCostoOrden` (que ya existe) |
| D5 | `AbonoController` ↔ `ElectronicaAbonoController` | 28 l. cada uno; solo cambia 1 literal | 28 ×2 | Un controlador con el modelo resuelto por la ruta |
| D6 | `MantenimientoStockController` ↔ `ElectronicaStockController` | 35 l. cada uno; diferencia de 1 línea | 35 ×2 | Igual que D5 |
| D7 | `MantenimientoPolicy` ↔ `ElectronicaPolicy` | 63 l. cada una; solo cambia la clave de sesión | 63 ×2 | Una `OrdenPolicy` base |
| D8 | `MantenimientosExport` ↔ `ElectronicasExport` | 141 / 144 l.; 37 líneas distintas | ≈120 ×2 | Un export base con columnas configurables |
| D9 | `registrarCompra` ↔ `registrarVenta` (ya en deuda #4) | `MovimientoInventarioController.php:42-186` y `:202-346` | 145 l. cada uno; ≈119 idénticas | `registrarDocumento('compra'\|'venta')` |
| D10 | Normalización de `items` y `explode(facturable_global)` | compra, venta, updateFactura (×2 bloques), cotización store/update | ≈12 l. ×6 | `FacturableResolver::desde(string)` con validación de la regex |
| D11 | Cotización `store` ↔ `update` (validación, destinatario, ítems) | `CotizacionController.php:42-123` y `:156-239` | ≈55 l. ×2 | FormRequest más un método privado `guardarItems()` |
| D12 | Búsqueda de `movimientoPadre` de la factura | `showFactura` y `printFactura` (`:388-403`, `:410-425`) | 14 l. ×2 | `Factura::movimientoRaiz()` |
| D13 | `match` de `reporteOperaciones` (lista y export) | `ReporteFinancieroController.php:568-606` y `:620-646` | ≈38 l. ×2 | Ver B-16 |
| D14 | Alertas de pendientes al iniciar sesión, Mantenimiento ↔ Electrónica | `AuthController.php:257-290` | 17 l. ×2 | Un método `alertasPendientes(Model)`; mejor fuera del controlador de login, en un servicio |
| D15 | Cálculo del alto del ticket POS | `MantenimientoController.php:327-331`, `ElectronicaController.php:249-253`, `MovimientoInventarioController.php:430-434` | 5 l. ×3 | Mover a `PosTicketService` |

**Total aproximado de líneas duplicadas que se pueden eliminar: ~1.100** en backend (sin contar vistas).

---

## 6. Alcance

**Revisado a fondo:**
- Controladores: `MantenimientoController`, `ElectronicaController`, `AbonoController`, `ElectronicaAbonoController`, `MantenimientoStockController`, `ElectronicaStockController`, `CotizacionController`, `DashboardController`, `GuestController`, `EventoController`, `EquipoController`, `ClienteController` (store, update, anular), `TecnicoController` (update, anular), `StockController` (index, store, update, reportes, show, anular, bajas), `ConceptoCajaController`, `CategoriaStockController`, `UserController`, `ReporteController`.
- `MovimientoInventarioController`: compra, venta, facturas, show, print y el inicio de `updateFactura`.
- `ReporteFinancieroController`: `reporteDiario` (inicio), `reporteAcumulado` (inicio) y `reporteOperaciones`.
- Traits: `Auditable`, `CalculaCostoOrden`, `HandlesAbono`, `HandlesStockAttach`.
- Servicios: `AnulacionService`, `OrdenService`, `StockService`, `CierreCajaGuard`, `PosTicketService`.
- Modelos: Mantenimiento, Electronica, Abono, Tecnico, Equipo, Cliente, Evento, Cotizacion (accessors), CierreCaja (accessors).
- Policies `MantenimientoPolicy`/`ElectronicaPolicy`, middleware `CheckRole`/`RequirePasswordChange`, `AppServiceProvider` (gates y composer de la barra superior), `routes/web.php` completo.

**Revisado solo de forma superficial** (búsqueda de catch, getMessage, get/all y firmas):
- `BackupController`: los mensajes técnicos solo aparecen con `APP_DEBUG`, y los catch silenciosos son de eventos.
- `MovimientoCajaController`: `storeAbono`, `anular` y `update` no se analizaron en detalle; le corresponden al agente financiero.
- `CierreCajaController`: `store`/`update`.
- `AuthController`: solo la parte de alertas.
- `PasswordController`, `ConfiguracionController`, `ProveedorController`.
- Modelos `Factura`, `MovimientoCaja`, `Stock`, `Proveedor`, `Configuracion`, `BajaStock`, `CategoriaStock`, `ConceptoCaja`, `CotizacionItem`, `FacturaItem`, `User`.
- `ColombiaHelper` y `Support/*` (solo firmas).

**No revisado:**
- Middleware `SecurityHeaders` y `PreventBackHistory` (le corresponden al agente de seguridad).
- `app/Exports/*` en detalle (solo los accesos a relaciones).
- `app/Console/*` (fuera del alcance indicado).
- Las vistas Blade, salvo los puntos citados.
