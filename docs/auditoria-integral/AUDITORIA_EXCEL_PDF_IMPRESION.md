# Auditoría de Excel, PDF e impresión

> **Origen:** informe del agente 7 (Excel/PDF/impresión) de la auditoría integral del 2026-11-13 (análisis con evidencia, sin modificar producción).
> **Coordinación:** el coordinador verificó los hallazgos ALTOS/CRÍTICOS antes de corregirlos y añadió este encabezado de estado.

## Estado tras las correcciones de esta ronda

| Hallazgo | Estado |
|---|---|
| X-01 el informe mensual exportaba solo 10 filas | **Corregido** + prueba |
| X-03 las facturas de orden mostraban abonos anulados | **Corregido** (A4 y POS) + prueba |
| X-02 la plantilla A4 de media hoja se rompe con muchos ítems | **Pendiente** |
| X-04 valor de inventario distinto entre documentos | **Pendiente** |
| X-05 "totales filtrados" suman solo la página | **Pendiente** |
| X-06 a X-23 | **Pendientes** |

Los identificadores (p. ej. `M-01`) se usan igual en `CAMBIOS_REALIZADOS.md`, `PENDIENTES.md` y en los mensajes de commit.

---

## Informe del agente 7 (Excel/PDF/impresión)

Fecha: 13/11/2026. Modo solo lectura; no se modificó ningún archivo del proyecto ni la BD MySQL.

## Metodología (generación real)

- Test PHPUnit propio en la carpeta de trabajo de la auditoría (no versionada) (`ag7/GeneraDocsAuditoriaTest.php` y `ag7/ExtraDocsAuditoriaTest.php`), ejecutado con `vendor/bin/phpunit -c phpunit.xml <ruta>` (SQLite en memoria, RefreshDatabase). Pasos: `db:seed` + `app:seed-demo-data`, y luego volumen: 90 mantenimientos y 90 electrónicas del día (10 % anulados, costos con decimales), 150 stocks, 90 movimientos de caja (algunos anulados), una orden con 40 repuestos y 6 abonos (1 anulado), una electrónica con 40 repuestos, una factura de venta con 61 ítems y 5 abonos, una cotización con 62 ítems, una factura con 250 ítems (POS). También datos de riesgo: cliente `=HYPERLINK("http://x.test","clic") Ñandú Peña`, persona `-2+3+cmd|calc`, y un equipo cuyo dueño es un proveedor.
- Se pidió cada ruta real (`?export=excel|pdf`, facturas `?formato=estandar|pos`), como admin. Se generaron 55 archivos (todos HTTP 200) en `ag7/out/`.
- Excel leído con `PhpOffice\PhpSpreadsheet\IOFactory` (`ag7/xlsx.php`): tipo de celda, formato, sumas, autofiltro, panel congelado, combinaciones de celdas, configuración de página.
- PDF: se descomprimieron los streams con gzuncompress (`ag7/pdftext.php`, `ag7/pdflines.php`). Se decodificó WinAnsi (`\341` = á) y UTF‑16BE (DejaVu). Se extrajeron las coordenadas (página, y) de cada texto para detectar superposiciones y cortes de tabla. Se probó el logo con jpg, png, svg y sin logo, apuntando el disco `public` al carpeta de trabajo de la auditoría.

## Resumen ejecutivo

| Severidad | Cantidad |
|---|---|
| CRÍTICA | 0 |
| ALTA | 5 |
| MEDIA | 11 |
| BAJA | 7 |

Hallazgos ALTOS:
- **X-01**: el Informe financiero mensual (`/reportes`) exporta a Excel y PDF solo los primeros 10 movimientos, porque `paginate()` modifica el builder.
- **X-02**: facturas y órdenes A4 con más de unos 10 ítems: las firmas y el pie quedan encima de los ítems, la línea de corte cruza la tabla, los totales quedan partidos entre páginas y la marca ANULADA solo sale en la última página.
- **X-03**: el historial de abonos de las órdenes (A4 y POS) muestra abonos anulados como pagos válidos. En A4 solo se ven 3.
- **X-04**: "Valor inventario" del PDF de stock = suma de precios unitarios (16.942.000). El Excel calcula cantidad × precio (54.139.000). La pantalla suma los precios unitarios de la página.
- **X-05**: Reporte de mantenimientos: el pie "TOTALES FILTRADOS" de pantalla suma solo la página (10 registros, $1.845.003). El PDF/Excel del mismo filtro suma 90 registros, $13.005.023. Lo mismo pasa en Operaciones.

## Hallazgos

### ALTA

**X-01 — Informe financiero mensual: Excel y PDF truncados a 10 registros**
- `app/Http/Controllers/ReporteController.php:39` hace `$transacciones = $queryDetallado->paginate(10, ...)`; las líneas `:92` y `:97` exportan `$queryDetallado->get()`. `Eloquent\Builder::paginate()` ejecuta `$this->forPage(...)` sobre el mismo builder (vendor/.../Eloquent/Builder.php:1186), así que el builder queda con `limit 10 offset 0`.
- Generación real: `reportes.xlsx` → "Total registros: 10" y "Balance Neto: $ 501". `reportes.pdf` → "3. TRANSACCIONES DETALLADAS (10 REGISTROS)". La misma hoja dice "INGRESOS TOTALES $ 6.008.001". En el rango había 74 ingresos y 31 egresos activos.
- Escenario: el contador descarga el informe del mes y recibe 10 filas que no suman los totales del encabezado.
- Corrección: `$transacciones = (clone $queryDetallado)->paginate(10, ['*'], 'transacciones_page');`. Agregar un test que exporte más de 10 movimientos y cuente las filas.
- Relacionado (MEDIA): sin `mes`, el detalle trae todo el histórico, pero el resumen es del mes actual, y el PDF dice "Período: 11/2026". Hay que aplicar el mismo filtro de mes/año (por defecto el actual) al detalle.

**X-02 — Plantilla A4 de media hoja (layouts/print) se rompe con muchos ítems**
- `resources/views/layouts/print.blade.php:29` usa `.invoice-wrapper { height: 141mm }`, con línea de corte en `border-bottom`. En `:121-136`, `.signatures-block` y `.footer` usan `position:absolute; bottom:…`. Las vistas repiten `position:absolute` en las firmas (`inventario/facturas/print.blade.php:96`, `mantenimientos/factura.blade.php:115`, `cotizaciones/pdf.blade.php:73`, `electronicas/factura`). No hay `page-break-inside: avoid` en totales ni en firmas.
- Generación real:
  - `elec_factura.pdf` (40 repuestos): "Firma Cliente / Recibe" en y=460.0 cae entre las filas "número 4" (y=467.5) y "número 5" (y=453.6). El pie "Gracias por su confianza…" (y=445.8) y "Este documento es un soporte válido…" (y=431.6) se dibujan encima de las filas 6 y 7 (y=439.7 / 425.8). "SALDO PENDIENTE" queda en y=21.3, en el margen inferior.
  - `mant_factura.pdf` (40 repuestos): "Costo Total Estimado" queda en la página 1 (y=35.2) y "SALDO PENDIENTE" en la página 2. La tabla de totales se parte. La marca de agua "ANULADA" solo aparece en la página 2; la página 1 de una orden anulada sale sin marca.
  - `fac_print.pdf` (61 ítems) y `cot.pdf` (62 ítems): 2 y 3 páginas. La línea punteada de corte (y≈427) atraviesa la tabla de la página 1. Las firmas quedan a media hoja de la última página. El thead sí se repite.
- Umbral práctico: con unos 10‑12 renglones la tabla ya pasa la mitad de la hoja.
- Corrección: si los ítems pasan de N (o siempre), usar un layout de hoja completa sin `height` fijo ni `position:absolute`. Firmas y pie en flujo normal, con `page-break-inside: avoid` en `.totals`, `.signatures-block` y el bloque de abonos. La marca de agua debe ir con `position: fixed` para que se repita en cada página (dompdf repite los elementos fixed).

**X-03 — Historial de abonos muestra abonos anulados (y solo 3 en A4)**
- `mantenimientos/factura.blade.php:87-88` y `electronicas/factura.blade.php:87-88` usan `$abonosHistorial = $x->abonos->sortBy('fecha'); $abonosMostrar = $abonosHistorial->take(3);`. `factura_pos.blade.php:100` (ambos módulos) usa `@foreach($x->abonos->sortBy('fecha') …)`. La relación `abonos()` (Mantenimiento.php:53) no filtra `anulado`. El total sí lo excluye (`getTotalAbonadoAttribute`).
- Generación real (ORD‑150, 6 abonos, el de $1.000 anulado): en A4 la lista muestra $1.000, $1.001 y $1.002 (incluye el anulado y omite 3 válidos), con "SALDO PENDIENTE $ 144.985". En POS se listan los 6, con el anulado "$ 1.000" sin marca. La lista no cuadra con "Total Abonado".
- Lo mismo pasa en `inventario/facturas/print.blade.php:83` (`$abonos->take(3)`). Allí sí vienen filtrados por `anulado=false` desde el controlador.
- Corrección: `$x->abonos->where('anulado', false)->sortBy('fecha')`. Mostrar todos los abonos, o "y N abonos más" con el total. Si se decide mostrar los anulados, marcarlos tachados como "ANULADO".

**X-04 — Valoración de inventario distinta entre PDF, Excel y pantalla**
- `resources/views/stocks/pdf_reportes.blade.php:212` usa `$totalCompra = $stocks->sum('precio_compra')`, rotulado "Valor Inv. (Compra)" (`:240`), y el pie en `:311-318`. `app/Exports/StocksExport.php:48-50` usa `cantidad × precio`. La pantalla (`stocks/reportes.blade.php:215-220`) suma los precios unitarios de la página actual (20 filas).
- Generación real (155 productos activos): PDF → "VALOR INV. (COMPRA) $ 16.942.000"; Excel → "Valor Compra: $ 54.139.000"; pantalla → suma de 20 precios.
- Corrección: el PDF y la pantalla deben usar el mismo cálculo que el export (cantidad × precio sobre toda la consulta). En pantalla se calcula con `(clone $query)->selectRaw('SUM(cantidad*precio_compra)')`.

**X-05 — Totales de pantalla = solo la página, pero rotulados como "filtrados"**
- `resources/views/mantenimientos/reportes.blade.php:256-259` muestra `TOTAL: {{ $mantenimientos->count() }}` y "TOTALES FILTRADOS" con `$mantenimientos->sum('costo')`, sobre un paginador de 10. Electrónicas ya está bien (`ElectronicaController.php:348-351`, `$totales` sobre la consulta completa). `reportes_financieros/operaciones.blade.php:119-121, 168-170, 217-219, 267-269` usa `$registros->count()` y `->sum(...)` sobre `paginate(10)`. Stock usa la misma lógica (ver X-04).
- Generación real, mismo filtro (hoy): pantalla "TOTAL: 10 … $ 1.845.003"; PDF "TOTAL: 90 REGISTROS COSTO ACUMULADO: $ 13.005.023"; Excel "Total: 90 / Total: $ 13.005.023". En Operaciones (ingresos) la pantalla dice "TOTAL: 10" y el Excel "Total registros: 74 / $ 6.008.001".
- Corrección: calcular los totales con `(clone $query)->count()/sum()` antes de paginar, como ya hace ElectronicaController, y pasarlos a la vista.

### MEDIA

**X-06 — Los totales de los reportes de órdenes incluyen anuladas; el acumulado no**
- `mantenimientos/pdf.blade.php:225,325` y `electronicas/pdf.blade.php` usan `->sum('costo')` sobre todas. `MantenimientosExport.php:47` y `ElectronicasExport.php:50` hacen `->sum('costo')`. `ReporteFinancieroController::reporteAcumulado` excluye las anuladas.
- Real: mantenimientos del día → PDF/Excel $13.005.023, acumulado/diario "Mantenimiento $ 11.745.023" (diferencia: 9 anuladas por $1.260.000). Mismo período, dos cifras.
- Corrección: total = solo activas, y mostrar aparte "Anuladas: n / $".

**X-07 — Exportaciones de "Operaciones" (compras, ventas, ingresos, egresos) incoherentes**
- `ReportesFinancierosExport.php:57-72` solo reconoce `tipo_movimiento` ingreso o egreso, así que en compras y ventas el pie dice "Balance Neto: $ 0" (real: `ops_solo_compras.xlsx`, `ops_solo_ventas.xlsx`). En egresos sale "Balance Neto: $ -2.277.000".
- El PDF (`ReporteFinancieroController.php:687,690`) usa `'anulado' => $tx->anulado ?? false`. Factura no tiene `anulado`, así que VT‑3 anulada sale "Progreso Anulada / Estado Activo". `descripcion` usa `facturable->nombre`, lo que da "N/A" en todas las compras a proveedor (real: `ops_solo_compras.pdf` "CP-1 COMPRA N/A"). Reusa `pdf_diario`, con pie "BALANCE NETO DEL DÍA: $ 0" para un rango de 30 días y tarjetas en $0. El emoji de `$tipoLabels` sale como "?" ("( ? Ventas de Inventario)").
- Excel y PDF de ventas incluyen la factura anulada sin excluirla de ningún total. La pantalla sí la excluye.
- Corrección: export específico para facturas, con total de documentos no anulados. En el PDF: `anulado = $tx->anulado ?? ($tx->estado === 'anulada')`, descripción `nombre_razon_social ?? nombre`, rótulo del pie según el tipo ("TOTAL VENTAS DEL PERÍODO"), y quitar los emojis del texto que va al PDF.

**X-08 — PDF acumulado: anulados en "$ 0" fijo y "TOTAL: 7" fijo**
- `reportes_financieros/pdf_acumulado.blade.php:229` tiene `<td class="monto">$ 0</td>` y en `:242` "TOTAL: 7" escrito a mano. La pantalla (`acumulado.blade.php:282`) y el Excel muestran `total_costo_anulados`.
- Real: pantalla y `acum.xlsx` C11 = 4.867.500; `acum.pdf` "Movimientos Anulados 25 $ 0". Además "TOTAL REGISTROS 279" suma conteos de categorías heterogéneas, incluidos los anulados.
- Corrección: usar `$acumulado['total_costo_anulados']` y quitar el "TOTAL: 7" fijo.

**X-09 — Excel: los valores 0 quedan como celdas vacías**
- `config/excel.php` no define `exports.strict_null_comparison`, así que se usa el default del paquete, `false` (vendor/maatwebsite/excel/config/excel.php:35). Ese default trata `0 == null`. El propio `AcumuladoExport.php:82-87` lo parchea solo para la fila 11.
- Real: `acum.xlsx` fila 8 "Ventas de Inventario" con B8 y C8 vacías. `stocks.xlsx` tiene la columna Cantidad vacía en las 22 filas con stock 0, y Utilidad vacía cuando es 0. En Excel, "existencias 0" y "dato faltante" se ven igual.
- Corrección: en `config/excel.php`, `'exports' => ['strict_null_comparison' => true]` (o `WithStrictNullComparison` en cada export), y quitar el parche de AcumuladoExport.

**X-10 — Excel: fechas e identificaciones mal tipadas**
- `MantenimientosExport.php:137-138`, `ElectronicasExport.php:140-141` y `ReportesFinancierosExport.php:162` escriben `Carbon::parse(...)->format('d/m/Y')` como texto. Real: columnas N/O y B con tipo `s` en todas las filas, así que no se pueden ordenar ni filtrar por fecha. "Pendiente" queda mezclado con fechas.
- La identificación sale como número (real: C5 tipo `n`, 1020304050). Se pierden los ceros a la izquierda y los documentos de más de 15 dígitos pierden precisión.
- Corrección: `Date::dateTimeToExcel($fecha)` con `NumberFormat::FORMAT_DATE_DDMMYYYY` (`WithColumnFormatting`), celda vacía si no hay salida, e identificación con `setValueExplicit(..., TYPE_STRING)` o formato `@`.

**X-11 — Excel: totales como texto y hoja sin ergonomía**
- En `*Export.php`, los pies se escriben como cadenas: `'Total: $ '.number_format(...)`. Real: `M96` tipo `s` = "Total: $ 26.010.000"; no se puede operar ni verificar. No hay autofiltro, panel congelado ni `rowsToRepeatAtTop` (real: "autofiltro=NO freeze=NO printTitles=[0,0]"). La orientación queda por defecto (vertical) con 15 columnas. `MantenimientosExport` es el único sin pie "Página &P de &N". `StocksExport.php:35,39` combina A1:J1 aunque hay 11 columnas (K = Estado).
- Corrección: etiqueta en una celda y valor numérico (o `=SUBTOTAL(9,M5:M94)`) con el mismo formato `"$ "#,##0`. Además `setAutoFilter('A4:…')`, `freezePane('A5')`, `setRowsToRepeatAtTop([4,4])`, orientación horizontal con `fitToWidth=1`, y corregir A1:K1 y el pie de Mantenimientos.

**X-12 — Cliente "N/A" en Excel y en las descripciones cuando el dueño del equipo es un proveedor**
- `MantenimientosExport.php:125-126` y `ElectronicasExport.php:128-129` usan `$m->equipo->cliente->nombre ?? 'N/A'`. `ReporteFinancieroController.php:37-38, 58-59, 449-450, 470-471` usa `equipo->cliente->nombre ?? '—'`. Los PDF usan `propietario_nombre` (`mantenimientos/pdf.blade.php:285`).
- Real: ORD‑101 → PDF "Distribuidora Tecnopartes Mayorista 800999888-2"; Excel "N/A". ELC‑4 (demo) → Excel "N/A" / "-".
- Corrección: usar `equipo->propietario_nombre` y `propietario_identificacion` en exports y mapeos, y cargar con eager loading `equipo.proveedor`.

**X-13 — Facturas de inventario: Identificación y Teléfono siempre "N/A"**
- `inventario/facturas/print.blade.php:15` y `print_pos.blade.php:17` usan `facturable->nit_documento ?? facturable->documento`. Ninguna de esas columnas existe; Cliente y Proveedor usan `identificacion`. El teléfono del cliente está en `movil`.
- Real: `fac_print.pdf` "Identificación: N/A / Teléfono: N/A"; `fac_print_pos.pdf` "Doc/NIT: N/A".
- Corrección: `facturable->identificacion` y `facturable->telefono ?? facturable->movil`.

**X-14 — Ticket POS de más de 3500 pt: dos páginas y metros de papel en blanco**
- `app/Services/PosTicketService.php:39` mide sobre un lienzo de 3500 pt. Si el contenido lo supera, `maxY` se reinicia por página y la altura final queda en 3500.
- Real (250 ítems): `x_fac_pos_250.pdf` "PAGES=2 MEDIABOX 226.77 x 3500". La página 2 termina en y=2284, así que quedan unos 80 cm de papel térmico en blanco.
- Corrección: medir con un lienzo mayor (por ejemplo 14400 pt, el máximo PDF) o acumular la altura por página (`page_number`); limitar el formato POS a N ítems, o avisar y ofrecer A4.

**X-15 — Imprimir desde pantalla saca solo la página visible**
- Botones `onclick="window.print()"` en `mantenimientos/reportes.blade.php:15`, `electronicas/reportes.blade.php:15`, `stocks/reportes.blade.php:14`, `reportes/index.blade.php:36`, `reportes_financieros/{diario,acumulado,operaciones}.blade.php`. Los listados están paginados (10 o 20 filas) y `.pagination` se oculta al imprimir (`_print.css:37`).
- Escenario: el usuario imprime el reporte de 90 órdenes y obtiene 10, con totales de página y sin aviso.
- Corrección: que el botón Imprimir abra el PDF (`?export=pdf`), o imprimir con `per_page=all`. Como mínimo, poner en la hoja impresa "Mostrando X de Y".

**X-16 — Formato de fecha de facturas POS con hora falsa**
- `inventario/facturas/print_pos.blade.php:15` usa `->format('d/m/Y H:i')` sobre `fecha` (cast `date`). Real: "Fecha: 10/11/2026 00:00".
- Corrección: `d/m/Y`, o usar `created_at` si se quiere la hora.

### BAJA

**X-17 — Cotización: `setPaper('letter')` ignorado.** `CotizacionController.php:255` pide carta, pero `layouts/print.blade.php:13` fija `@page { size: A4 portrait }`. Real: `cot.pdf` MediaBox 595.28 x 841.89 (A4). Hay que decidir un solo tamaño (quitar `size` del layout o el `setPaper`).

**X-18 — PDF de documentos pesados por fuentes completas.** `config/dompdf.php:113` tiene `enable_font_subsetting => false`. DejaVu Sans y Sans‑Bold se incrustan enteras. Real: cada factura u orden pesa entre 880 y 930 KB (ticket de 1 página: 923 KB), y los reportes con Helvetica entre 50 y 110 KB. Activar el subsetting.

**X-19 — "SIN LOGO" impreso en documentos de cliente.** `layouts/print.blade.php:208` dibuja una caja gris con el texto "SIN LOGO" cuando no hay logo (real: `x_factura_none.pdf` "SIN LOGO" en y=770). Los reportes y el POS muestran el nombre de la empresa; aquí debería pasar lo mismo.

**X-20 — Exports de stock sin orden.** `StockController.php` llama a `$query->get()` (PDF y Excel) sin `orderBy`, mientras la pantalla usa `orderBy('id','desc')`. Real: el Excel empieza en STK-1000, STK-1007…

**X-21 — Reglas de impresión duplicadas y en conflicto.** Hay `@page` en `resources/css/glass/_print.css:7` (A4, 15/12 mm), en `_utilities.css:1338` (10/8 mm) y en cada vista (`mantenimientos/reportes.blade.php:366` horizontal 6 mm, etc.). Las reglas de ocultamiento (`nav, header, footer, form, button`) también se repiten. Gana la última que carga, y eso es difícil de mantener. Conviene unificarlas en `_print.css` con una clase en `<body>` para la orientación.

**X-22 — Margen doble declarado en los PDF de reportes.** `mantenimientos/pdf.blade.php:17-28` (y `electronicas/pdf`, `stocks/pdf_reportes`, `reportes_financieros/pdf_*`) declara `@page { margin:20px 25px }` y además `body { margin:20px 25px !important }`. No se vio impacto en la generación (el contenido arranca en x≈18.8 pt), pero la doble declaración confunde. Dejar una sola.

**X-23 — Lógica de negocio en Blade de impresión.** `caja/print.blade.php:16-69` hace consultas (`Factura::where`, `MovimientoCaja::where…`) y deduce la referencia con regex sobre la descripción. Lo mismo pasa con `Configuracion::first()` y la lectura del logo en cada plantilla PDF (`mantenimientos/pdf.blade.php:1-10`, etc., sin la caché que sí usa `layouts/print`). Pasar los datos desde el controlador o un servicio y reutilizar la caché del logo.

## Tabla documento → estado

| Documento | Origen | Generado | Papel / orient. | Estado | Hallazgos |
|---|---|---|---|---|---|
| Excel Mantenimientos | MantenimientosExport (MantenimientoController:175, Operaciones:651) | 90 filas | por defecto vertical | Con observaciones | X-06, X-09..X-12, X-05 |
| Excel Electrónica | ElectronicasExport (ElectronicaController:356, Operaciones:653) | 90 filas | vertical | Con observaciones | X-06, X-09..X-12 |
| Excel Stock | StocksExport (StockController:283) | 155 filas | vertical | Con observaciones (valores correctos) | X-09, X-11, X-20 |
| Excel Informe financiero | ReportesFinancierosExport (ReporteController:94) | **10 filas** | vertical | **Incorrecto** | X-01 |
| Excel Diario | ReportesFinancierosExport + resumen | 279 filas | vertical | Correcto en totales (balance = caja) | X-10, X-11 |
| Excel Acumulado | AcumuladoExport | 7 categorías | vertical | Con observaciones | X-09 |
| Excel Operaciones (compras/ventas/ingresos/egresos) | ReportesFinancierosExport | 2/3/74/31 | vertical | **Incorrecto** en compras/ventas | X-07 |
| PDF mantenimientos/pdf | Pdf::loadView + setPaper a4 landscape | 6 págs. | A4 horiz. | Bien (thead repetido, Página X de Y 6/6, tildes) | X-06, X-05 |
| PDF electronicas/pdf | idem | 6 págs. | A4 horiz. | Bien | X-06 |
| PDF stocks/pdf_reportes | idem portrait | 7 págs. | A4 vert. | **Valor de inventario incorrecto** | X-04, X-20 |
| PDF reportes/pdf | ReporteController | 1 pág. | A4 vert. | **Incompleto (10 filas)** | X-01 |
| PDF pdf_diario | ReporteFinancieroController | 8 págs. | A4 vert. | Bien (8/8, totales = pantalla) | — |
| PDF pdf_diario (Operaciones) | idem | 1–4 págs. | A4 vert. | Incorrecto en compras/ventas | X-07 |
| PDF pdf_acumulado | idem | 1 pág. | A4 vert. | Anulados en $0 | X-08 |
| mantenimientos/factura (A4) | layouts/print | 1 pág. (corta) / 2 págs. (40 rep.) | A4 media hoja | Bien si es corta; **se rompe si es larga** | X-02, X-03, X-19 |
| electronicas/factura (A4) | layouts/print | 1 pág. con superposición | A4 media hoja | **Superposición** | X-02, X-03 |
| mantenimientos/electronicas factura_pos | PosTicketService | 226.77 x 432–1502 pt | 80 mm | Bien (altura dinámica) | X-03 |
| cotizaciones/pdf | layouts/print | 3 págs. | A4 (pide carta) | Se rompe si es larga | X-02, X-17 |
| caja/print y print_pos | layouts/print, POS | 1 pág. | A4 / 80 mm | Bien | X-23 |
| inventario/facturas/print | layouts/print | 2 págs. (61 ítems) | A4 | Se rompe si es larga; identificación N/A | X-02, X-13 |
| inventario/facturas/print_pos | POS | 1768 pt (61 ítems); 2×3500 pt (250 ítems) | 80 mm | Bien hasta unos 170 ítems | X-13, X-14, X-16 |
| stocks/print (ficha) | layouts/print | 1 pág. | A4 | Bien | — |
| Impresión de pantalla (window.print) | _print.css + @media print en vistas | — | A4 vert./horiz. | Solo imprime la página visible | X-15, X-21 |

## Verificado y correcto

- **SafeValueBinder** registrado globalmente (`config/excel.php:10-11`) y efectivo en todos los exports. Real: `=Hyperlink("Http://X.test","Clic")…` y `-2+3+cmd|calc` quedan como tipo `s` (texto), no como fórmula. Los números siguen siendo `n`.
- Los montos se escriben como número (`(float)`) con formato `"$ "#,##0` en todas las columnas de costo y precio. Las sumas de la columna coinciden con el pie: Mant 13.005.022,5 → "$ 13.005.023"; Elec 26.010.000; Stock cantidad × precio 54.139.000.
- Totales del Excel diario y acumulado iguales a la pantalla y al PDF del mismo filtro (balance 2.775.501, por cobrar 35.235.023), salvo X-08.
- **thead repetido** en todas las páginas de todos los PDF multipágina (mantenimientos, electrónicas, stock, diario, operaciones, facturas y cotización). dompdf lo hace sin necesitar `display: table-header-group`. `tbody tr { page-break-inside: avoid }` en mantenimientos, electrónicas y stock; no se vieron filas cortadas.
- **Paginación** "Página X de Y" (App\Support\PaginacionPdf, `<meta name="paginacion">`) presente y correcta en todas las páginas de los reportes: 6/6, 6/6, 7/7, 8/8, 4/4, 2/2, 1/1. No se superpone al contenido (y=17.2 pt contra el contenido mínimo en y≥30). `enable_php=false`, `enable_remote=false`, chroot en base_path. Los documentos de cliente (facturas, POS) no llevan numeración, por diseño.
- **Tildes y ñ** correctas en Helvetica/WinAnsi (reportes) y en DejaVu (facturas): "ORDEN DE SERVICIO TÉCNICO", "Ñandú Peña", "Página".
- **Logo**: jpg, png y svg se incrustan como data URI y se dibujan (svg como vector). Sin logo, los reportes y el POS muestran el nombre de la empresa; la caché del logo se invalida al cambiar la configuración (`ConfiguracionController:69`, `Configuracion.php:52`).
- Tickets POS de 80 mm (226.77 pt) con altura dinámica correcta para tamaños normales (432 a 1768 pt), pie a 19 pt del borde.
- **_print.css** oculta sidebar, topbar, nav, botones, formularios, paginación y modales. Encabezado de impresión con logo en `layouts/app.blade.php:122`. thead y tfoot como header/footer-group, y `tr { page-break-inside: avoid }`.
- **No existe importación de Excel** (sin `Excel::import`, `ToModel`, `WithHeadingRow`, `ToCollection` ni lectura de CSV en app/, routes/ o resources/).

## No revisado / límites

- No se renderizaron los PDF a imagen (no hay Ghostscript ni poppler): la superposición se dedujo de coordenadas de texto. Colores, recortes de bordes y la apariencia de la marca de agua no se validaron visualmente.
- No se probó la impresión real desde el navegador (Chrome/Edge) ni en una impresora térmica física. El CSS de impresión se evaluó leyendo el código.
- `cierre/show.blade.php` (impresión del cierre de caja) y `dashboard.blade.php` (@media print) se vieron solo por grep.
- Electrónica POS y caja POS con historiales largos no se llevaron al límite. Tampoco se probaron los reportes con más de unos 300 registros (rendimiento y memoria de dompdf).

## Archivos revisados

app/Exports/{AcumuladoExport,ElectronicasExport,MantenimientosExport,ReportesFinancierosExport,SafeValueBinder,StocksExport}.php; app/Support/PaginacionPdf.php; app/Services/PosTicketService.php; app/Providers/AppServiceProvider.php (registro dompdf); config/excel.php; config/dompdf.php; app/Http/Controllers/{MantenimientoController (reportes, factura), ElectronicaController (reportes, factura), StockController (reportes, print), ReporteController, ReporteFinancieroController, CotizacionController::pdf, MovimientoCajaController::print, MovimientoInventarioController::printFactura}.php; app/Models/{Equipo, Mantenimiento, Abono, Factura, Proveedor, MovimientoCaja, Stock}.php (campos y accessors); resources/views/{layouts/print, layouts/print_pos, mantenimientos/{pdf,factura,factura_pos,reportes}, electronicas/{pdf,factura,reportes}, cotizaciones/pdf, caja/print, inventario/facturas/{print,print_pos}, stocks/{pdf_reportes,reportes}, reportes_financieros/{pdf_diario,pdf_acumulado,operaciones,acumulado}}.blade.php; resources/css/glass/_print.css y _utilities.css (bloque print); routes/web.php (rutas de export e impresión).

Revisados parcialmente o solo por grep: electronicas/factura_pos, caja/print_pos, stocks/print, reportes/pdf, reportes/index, reportes_financieros/diario, cierre/show, dashboard.

Evidencia en la carpeta de trabajo de la auditoría (no versionada): `ag7/out/*.xlsx|*.pdf|*_screen.html`, `ag7/out/_status.txt`, `ag7/out/_ref.json`; scripts `ag7/xlsx.php`, `ag7/pdftext.php`, `ag7/pdflines.php`, `ag7/rawstream.php`.
