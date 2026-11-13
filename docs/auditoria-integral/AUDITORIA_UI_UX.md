# Auditoría UI/UX y notificaciones

> **Origen:** informe del agente 3 (frontend y UI/UX) de la auditoría integral del 2026-11-13 (análisis con evidencia, sin modificar producción).
> **Coordinación:** el coordinador verificó los hallazgos ALTOS/CRÍTICOS antes de corregirlos y añadió este encabezado de estado.

## Estado tras las correcciones de esta ronda

| Hallazgo | Estado |
|---|---|
| F-01 el invitado veía saldos en la 404 | **Corregido** + prueba |
| F-02 el modal de notificaciones cortaba "Cerrar" | **Corregido** (lista `flex-1 min-h-0`, botón `shrink-0`). **No medido en navegador** |
| F-03 sin Escape, clic en el fondo ni foco | **Corregido**. **No probado en navegador** |
| F-04 el buscador filtra solo la página visible | **Pendiente** |
| F-05 a F-37 | **Pendientes** (propuesta de sistema de diseño y de notificaciones más abajo) |

Los identificadores (p. ej. `M-01`) se usan igual en `CAMBIOS_REALIZADOS.md`, `PENDIENTES.md` y en los mensajes de commit.

---

## Informe del agente 3 (frontend y UI/UX)

Proyecto: `C:\ServBay\www\tecnisystemas` · Fecha: 2026-11-13 · Modo: solo lectura (análisis estático; no se arrancó servidor ni navegador).
Incluye revisión del commit `b7565c3` ("refactorización columnas de agregar y notificaciones").

Severidad: **ALTA** = rompe / da error / bloquea / expone datos · **MEDIA** = problema de UX visible · **BAJA** = estética / deuda.

---

## 0. Resumen ejecutivo

| Sev. | ID | Hallazgo |
|---|---|---|
| ALTA | F-01 | Un usuario **invitado** ve el panel y el modal de notificaciones (clientes, órdenes, saldos con montos) en la página 404, porque `errors/404` usa `layouts.app` y el View::composer no filtra por rol. |
| ALTA | F-02 | **Regresión b7565c3**: en el modal de notificaciones, el botón "Cerrar" y el final de la lista quedan recortados e inalcanzables en portátiles de 1366×768 y en celulares (`overflow:hidden !important` + lista fija `max-h-[440px]`). |
| ALTA | F-03 | El modal de notificaciones no se cierra con Escape ni con clic en el fondo, y no recibe el foco. Sumado a F-02, el usuario queda atrapado tras iniciar sesión, porque el modal se abre solo. |
| ALTA | F-04 | El buscador de 11 listados paginados solo filtra la página visible (10–15 filas). Si "no aparece", el usuario crea un **cliente/equipo duplicado**. |
| MEDIA | F-05…F-25 | Las notificaciones no filtran por rol ni por técnico, los contadores se truncan a 50 y no hay "atrasadas". Los mensajes de abono dicen "eliminar" cuando la acción anula. Las confirmaciones son inconsistentes y faltan errores en línea. El carrusel del dashboard se ve mal en móvil, la calibración de 5 ítems no se cumple, hay una regla global de `thead` sticky, los tokens están definidos pero no se usan y hay 4 estilos distintos de "Cancelar"… |
| BAJA | F-26…F-37 | Estética, accesibilidad menor y deuda de CSS. |

Cifras clave del sistema de diseño:
- **0 usos** de los tokens `--color-*`, `--radius-*`, `--shadow-*` y `--text-*` fuera de `_tokens.css`. Solo se usan `--acrylic-noise`, `--glass-*` y `--sidebar-*`.
- `!important` en CSS: **3.926** (`_buttons.css` 1.681, `_utilities.css` 1.428, `_tables.css` 264, `_forms.css` 200…).
- Hex literales en CSS: **≈704**. Hex en vistas: **597** (la mayoría en PDF/impresión, donde es legítimo). En vistas de pantalla hay ≈170 repartidos en 24 archivos, además de 19 valores arbitrarios Tailwind `[#2563EB]` / `[#3B82F6]`.
- `style="…"` en vistas: **618** (los 11 primeros son plantillas PDF/impresión; en pantalla: `layouts/app` 23, `inventario/facturas/edit` 14, `reportes_financieros/acumulado` 11, `stocks/reportes` 10, `dashboard` 9, `topbar` 9).
- Clases `btn-*`: 56 definidas en CSS y ≈60 distintas usadas en vistas.
- Radios: 12 valores literales distintos (4, 6, 8, 9, 10, 12, 13, 14, 16, 18, 20 y 9999 px). Tamaños de fuente: 15 valores literales (9 a 15 px en pasos de 0,5, más rem).
- Componentes Blade: `x-icon` (31 usos), `x-whatsapp-link` (11), `x-print-dropdown` (5). `x-modal` y `x-table` **solo se usan en `design_system`**. Hay 12 vistas que reimplementan modales a mano.
- Accesibilidad: 371 campos de formulario frente a 47 `<label for=…>`. 31 `aria-label` en total. 40 botones con `title` sin `aria-label`, casi todos de solo emoji.

---

## 1. Hallazgos

### F-01 · ALTA · El invitado ve las notificaciones operativas y financieras en la página 404
- **Dónde:** `resources/views/errors/404.blade.php:1` (`@extends('layouts.app')`); `app/Providers/AppServiceProvider.php:116-297` (View::composer de `layouts.app`, que solo comprueba `Auth::check()`); `resources/views/layouts/app.blade.php:1152-1418` (modal) y `layouts/partials/topbar.blade.php:45-127` (campana).
- **Evidencia:** el composer calcula `mantList`, `elecList`, `cajaList` (saldo y nombre del cliente o proveedor), `movimientosPendientes` (saldo y persona) y `cotList` (total) para **cualquier** usuario autenticado. El resto de las pantallas del invitado usan `layouts.guest` / `layouts.consulta`. En cambio, `404.blade.php` usa `layouts.app` y el bloque `@auth` pinta el sidebar, el topbar y el modal.
- **Escenario:** un invitado abre `/mantenimientos/999999` (la ruta admite el rol invitado y el *route-model binding* falla) o cualquier `/{recurso}/{id}` inexistente. El binding se resuelve antes de `role`, así que responde 404 en vez de redirigir. La sesión ya está iniciada, así que Laravel pinta la 404 con layout completo: campana con contadores, modal con nombres de clientes, equipos y montos adeudados, y el menú lateral de técnico.
- **Corrección:**
  1. En el composer: `if (! Auth::check() || ! Auth::user()->isAdmin() && ! Auth::user()->isTecnico()) { $view->with(vacíos); return; }`.
  2. Hacer que `errors/404.blade.php` extienda `errors.minimal`, igual que 403/419/429/500/503, o elegir el layout según el rol, como `mantenimientos/show.blade.php:1-3`.
  3. Test de feature: un invitado pide `/mantenimientos/999999` y el HTML no debe contener `ts-notif-modal`.
- *(Se cruza con Seguridad; el Agente de Seguridad debería confirmarlo con un test.)*

### F-02 · ALTA · Regresión b7565c3: el modal de notificaciones recorta el botón "Cerrar"
- **Dónde:** `resources/css/glass/_modals.css:92-95` (`#ts-notif-card { overflow: hidden !important; }`, añadido en b7565c3); `layouts/app.blade.php:1154` (`style="… overflow: hidden !important;"`) y `:1187` (`max-h-[440px]`, antes `max-h-[55vh] sm:max-h-[50vh]`).
- **Evidencia:** `.ts-modal-card` tiene `max-height: 90dvh`. Antes desplazaba el contenido con `overflow-y:auto`; ahora oculta lo que sobra. La lista tiene una altura fija de 440 px y su contenedor (`flex flex-col flex-1`, `min-height:auto`) no se encoge.
- **Cálculo:**
  - Portátil de 1366×768, con un *viewport* útil de unos 657 px: el límite es 90dvh ≈ 591 px y el contenido suma ≈ 48 + 64 + 44 + 440 + 68 = 664 px. El botón "Cerrar" queda fuera.
  - iPhone SE / Android de 360×640: las pestañas ocupan 2–3 filas y el contenido suma ≈ 712 px frente a un límite de ≈ 490–560 px. Se pierden más de 150 px, incluidos el botón y los últimos ítems.
- **Escenario:** el modal se abre solo al iniciar sesión (F-06) y el usuario no ve cómo cerrarlo (F-03).
- **Corrección:**
  - Lista: `flex-1 min-h-0 overflow-y-auto` en lugar de `max-h-[440px]`.
  - Contenedor intermedio: `flex flex-col flex-1 min-h-0`.
  - Tarjeta: mantener `max-height: 90dvh; display:flex; flex-direction:column; overflow:hidden`.
  - Botón "Cerrar": `shrink-0`.
  - Quitar el `overflow: hidden !important` inline y el duplicado en `_modals.css`.
  - Si se quiere "5 bloques", usar `max-h-[min(440px,50dvh)]`.

### F-03 · ALTA · Modal de notificaciones sin Escape, sin cierre por fondo y sin gestión de foco
- **Dónde:** `layouts/app.blade.php:559-583` (manejador global de Escape), `:1153` (overlay sin `onclick`), `:1386-1411` (`openNotifModal` / `closeNotifModal`).
- **Evidencia:** el manejador genérico convierte `ts-notif-modal` en `closeTsNotifModal()`, que **no existe** (la función real es `closeNotifModal`). El overlay no tiene `onclick="if(event.target===this) closeNotifModal()"`. Además, `openNotifModal` no llama a `recordFocus()` ni enfoca nada dentro, aunque declara `aria-modal="true"`. `closeNotifModal` fuerza `document.body.style.overflow='auto'`, lo que pisa el estado del sidebar móvil, que usa la clase `overflow-hidden`.
- **Mismo patrón, revisado con un script:** `pwd-cierre-modal` en `cierre/show.blade.php` no tiene Escape propio (`cierre/index` sí). `detalle-modal` en `eventos/index.blade.php:245` usa un manejador propio y funciona.
- **Corrección:**
  - Registrar `window.closeTsNotifModal = closeNotifModal`, o mejor que cada modal declare `data-close="closeNotifModal"` y el manejador global lo lea.
  - Añadir cierre al hacer clic en el overlay.
  - Usar `recordFocus()` al abrir y enfocar `#btn-notif-all`; usar `restoreFocus()` al cerrar.
  - Usar una clase `overflow-hidden` en vez de un estilo inline.

### F-04 · ALTA · El buscador de los listados solo filtra la página actual
- **Dónde:** `layouts/app.blade.php:1124-1148` (`filterTable`, filtra filas del DOM). Se usa en `clientes/index:101`, `equipos/index:299`, `proveedores/index:103`, `tecnicos/index:109`, `usuarios/index:157`, `stocks/index:230`, `mantenimientos/index:216`, `electronicas/index:217`, `caja/index:238`, `cotizaciones/index:160` e `inventario/facturas/index:204`.
- **Evidencia:** los `<input id="search-…">` no tienen `name` ni `<form>` (por ejemplo `clientes/index.blade.php:16`), y los controladores paginan de 10 en 10 o de 15 en 15 (`ClienteController:31` `paginate(15)`, `MantenimientoController:43` `paginate(10)`…). Varios índices **ya aceptan `?search=` en el servidor** pero la vista no lo envía: `ClienteController:16`, `ProveedorController:29`, `ElectronicaController:40` y `MovimientoCajaController:54`.
- **Escenario:** con 300 clientes, buscar "Pérez" en la página 1 no muestra nada. El usuario cree que no existe y crea un duplicado. Lo mismo pasa con equipos, órdenes o facturas.
- **Corrección:**
  1. Envolver cada buscador en `<form method="GET">` con `name="search"`, `value="{{ request('search') }}"`, envío con debounce de 400 ms y `->withQueryString()` en el paginador.
  2. Añadir el filtro `search` en los controladores que no lo tienen (Mantenimiento index, Equipo, Cotización, Facturas, Técnico, Usuario).
  3. Mantener `filterTable` como resaltado local opcional.
  4. Mostrar el estado vacío "Sin resultados para «X» en todo el sistema".

### F-05 · MEDIA · Notificaciones sin segmentación, contadores truncados y omisiones
- **Dónde:** `AppServiceProvider.php:134-296`.
- **Evidencias:**
  - (a) Admin y técnico reciben exactamente la misma lista: no se filtra por `tecnico_id` aunque la columna existe. El técnico ve los saldos de **todas** las facturas.
  - (b) Cada categoría tiene `->limit(50)` y el contador es `count($lista)`: con 120 órdenes pendientes la campana dice 50.
  - (c) `movimientosPendientes` hace `latest()->limit(50)` **antes** de `filter(saldo_pendiente > 0.01)` (`:242-246`). Si los 50 movimientos más recientes con `monto_total` ya están saldados, los pendientes más antiguos no aparecen.
  - (d) La caché se guarda en `topbar_notifs_user_{id}` durante 30 s, pero los datos son idénticos para todos: N copias en caché y hasta 30 s de desfase después de terminar una orden.
  - (e) Solo hay estado `pendiente`: no existe "atrasada" (por ejemplo, más de 3 días, el mismo umbral que el dashboard pinta en rojo en `dashboard.blade.php:241`), "terminada sin entregar con saldo" ni "cotización vencida" (`validez_dias` existe en `Cotizacion` y no se usa).
  - (f) No hay estado de leído ni prioridad. La campana muestra un punto rojo que parpadea (`animate-ping`) **siempre** que haya cualquier pendiente.
- **Corrección:** ver la propuesta de la sección 4. Como arreglo rápido:
  - Usar contadores con `->count()` aparte de la lista.
  - Filtrar por `tecnico_id` cuando el usuario es técnico.
  - Ocultar Caja y Facturas al técnico si negocio lo decide.
  - Usar una clave de caché compartida por rol e invalidarla en los observers de Mantenimiento, Electrónica, Cotización, Factura y MovimientoCaja.
  - Filtrar el saldo en SQL o con `->cursor()` en lugar de limitar antes de filtrar.

### F-06 · MEDIA · Alerta al iniciar sesión: invasiva, costosa e incoherente con lo que muestra
- **Dónde:** `app/Http/Controllers/AuthController.php:252-307`, `layouts/app.blade.php:1413-1416`.
- **Evidencia:** en cada login se ejecutan 2 `count` y 2 consultas con eager loading de hasta 50 filas, y se guarda el array completo (hasta 100 registros) en la sesión con `flash`. La vista solo lo usa como booleano (`count(session('alertas_pendientes')) > 0`), y `alertas_totales` no se lee en ningún sitio. El modal que se abre muestra la lista del *composer* (`latest()`, las más recientes), no la de la alerta, que se ordena por antigüedad. Se abre en **cada** inicio de sesión y no ofrece "no volver a mostrar hoy".
- **Corrección:** en `AuthController`, guardar solo `session()->flash('mostrar_alertas', true)`, o nada, y decidir en el layout. Abrir el modal solo si hay ítems **críticos** (atrasados o vencidos) y como máximo una vez al día, con una marca en `localStorage` o en el usuario. Ordenar la lista por prioridad y antigüedad.

### F-07 · MEDIA · Abono: la interfaz dice "Eliminar" pero la acción anula
- **Dónde:** `mantenimientos/show.blade.php:298-300` y `electronicas/show.blade.php:298` (`data-confirm-delete="¿Eliminar este abono…?"`, `title="Anular abono"`); `AbonoController.php:26` ("Abono eliminado correctamente…"); `app/Traits/HandlesAbono.php:145-170` (marca `anulado = true`).
- **Escenario:** el usuario cree que el registro desaparece, pero queda anulado en el historial y en caja. El título del botón dice "Anular" y el modal dice "Eliminar".
- **Corrección:** unificar el texto en "Anular abono" ("Se conservará en el historial y su ingreso en caja quedará anulado") y cambiar también el mensaje de éxito. Usar el modal `openAnularModal` para mantener la coherencia con el resto de anulaciones.

### F-08 · MEDIA · Confirmaciones desproporcionadas e inconsistentes al quitar ítems
- **Dónde:** `cotizaciones/_scripts.blade.php:283-308` y `inventario/facturas/edit.blade.php:≈480-500` (modal global y, como alternativa, `confirm()` nativo para quitar una fila **no guardada**) frente a `inventario/venta.blade.php:259-270` y `compra.blade.php` (quitan la fila sin confirmar).
- **Escenario:** cada vez que el usuario quita una fila de borrador en una cotización le aparece un modal. En venta no le aparece nada, ni siquiera un "deshacer". El `confirm()` nativo rompe el estilo visual.
- **Corrección:** sin confirmación para filas no persistidas, con un toast "Ítem quitado · Deshacer" de 5 s. Confirmar solo cuando la fila ya existe en la base de datos (`tr.existing-row` en `facturas/edit`). Eliminar los `confirm()` nativos.

### F-09 · MEDIA · Errores de validación poco visibles en formularios clave
- **Dónde:** `mantenimientos/_form.blade.php` (14 campos, 2 `@error`), `electronicas/_form.blade.php` (14 campos, 7 `@error`), `caja/edit.blade.php` (6 campos, 0 `@error`); toast global en `layouts/app.blade.php:321`.
- **Evidencia:** cuando no hay `@error` en línea, la única señal es un toast que muestra **solo el primer error** ("… (y 3 error(es) más)") y desaparece a los 4,5 s (`:312-315`). El contenedor de toasts no tiene `role="status"` / `aria-live`.
- **Corrección:** añadir `@error` bajo cada campo, mejor con un componente `<x-field>` (ver la sección 3). Los toasts de error deben permanecer hasta que se cierren. Añadir `aria-live="polite"` al contenedor (`role="alert"` para errores).

### F-10 · MEDIA · El placeholder desaparece al enfocar, en todo el sistema
- **Dónde:** `layouts/app.blade.php:2084-2107`.
- **Escenario:** formatos de ejemplo ("Contraseña de Administrador…", "0", "Buscar cliente…") desaparecen en cuanto el campo recibe el foco, antes de escribir. Las ayudas de formato se pierden justo cuando se necesitan.
- **Corrección:** eliminar el script; el navegador ya oculta el placeholder al escribir. Si se busca un efecto estético, usar `input:focus::placeholder{opacity:.4}`.

### F-11 · MEDIA · Carrusel del dashboard en móvil y tablet
- **Dónde:** `dashboard.blade.php:53-141`, `:421`.
- **Evidencias:**
  - Cada slide mide `height: 420px` fijos (inline).
  - La cabecera es `flex justify-between` sin `flex-wrap`: título `text-xl`, descripción y badge compiten en unos 300 px.
  - El lienzo de barras e ingresos tiene `pl-8 pr-14 pb-8 pt-8` (88 px horizontales perdidos), así que en 360 px el gráfico mide unos 180 px de ancho.
  - Las flechas absolutas (`left/right:15px`) quedan encima del área del gráfico.
  - No hay gesto de *swipe*.
  - El autoplay cada 15 s no se pausa con *hover*, foco ni `prefers-reduced-motion` (WCAG 2.2.2).
  - Los 4 indicadores son `<button>` sin `aria-label` (`:137-140`).
- **Corrección:**
  - Usar `h-[340px] sm:h-[420px]`.
  - Cabecera con `flex-wrap gap-2`.
  - Padding del lienzo `px-2 sm:pl-8 sm:pr-14`.
  - En `<640px`, flechas debajo del gráfico o solo indicadores, más *swipe* con `pointerdown`/`pointerup`.
  - Pausar con `mouseenter`/`focusin` y no arrancar si `matchMedia('(prefers-reduced-motion: reduce)')`.
  - `aria-label="Ir al gráfico N"` y `aria-current` en el indicador activo.

### F-12 · MEDIA · La calibración de "5 ítems" de b7565c3 no se cumple y el snap obligatorio da saltos
- **Dónde:** `_tables.css:664-771` (b7565c3), `inventario/venta.blade.php:56`, `compra.blade.php`, `facturas/edit.blade.php:197`, `cotizaciones/create.blade.php:60` y `edit.blade.php`.
- **Evidencias:**
  - (a) En las tablas, `height` del `td` es un **mínimo** y `max-height` no se aplica a `tr`/`td`. En `facturas/edit` cada celda tiene una etiqueta `text-[10px]` más un control de 38 px más 16 px de padding, unos 71 px. El contenedor de 366 px muestra unos 4 ítems y medio, no 5.
  - (b) `venta.blade.php:89` `.alerta-costo-badge` es `absolute top-full` bajo un input de 38 px en una celda de 55 px. Sobresale 6–8 px y se monta sobre la fila siguiente. `cotizaciones/_scripts.blade.php:63` `.stock-warning` tiene `absolute -bottom-3` y pasa lo mismo.
  - (c) `scroll-snap-type: y mandatory` junto con `scroll-behavior: smooth` y el auto-scroll al añadir: al tabular entre campos o abrir TomSelect cerca del borde, el contenedor "salta" a la fila siguiente o anterior. Con `mandatory` el usuario no puede dejar una fila a medio ver.
  - (d) La altura de 366 px está en px fijos. En un celular horizontal (unos 360 px de alto útil) ocupa toda la pantalla.
- **Corrección:**
  - Usar `scroll-snap-type: y proximity`, o quitarlo.
  - `max-height: min(366px, 45dvh)`.
  - Mover los avisos ("MENOR AL COSTO", "Sin stock") a un `title` o tooltip, o al estado `border-red-500` del input, en lugar de un texto absoluto.
  - Si se quiere una altura fija, calcularla con `--row-h` y aplicarla a un `<div>` interno, no al `td`.

### F-13 · MEDIA · Regla global `table thead` sticky con `!important` (b7565c3)
- **Dónde:** `_tables.css:44-61` (dos bloques casi idénticos para `table thead`, el segundo repite `z-index:30`) y `:67-90`.
- **Evidencia:** el selector genérico `table thead` afecta a **todas** las tablas de la aplicación: `backup-table` en configuración, tablas del dashboard, `layouts/app` cuando se imprime desde el navegador, etc. Les impone fondo `#E2E8F0 !important`, `position:sticky !important` y `z-index:30 !important`. Como casi todas están dentro de `.overflow-x-auto` con `overflow-y:hidden !important` (`_tables.css:313-325`), el sticky no tiene efecto ahí, pero sí fija el color y la capa. Además, `.ts-table { overflow:hidden }` (`_tables.css:11`) puede anular el sticky dentro de `.items-table-container`. Hay que verificarlo en un navegador.
- **Corrección:** limitar la regla a `.items-table-container thead, .table-sticky thead`, quitar el `!important`, eliminar el bloque duplicado y no aplicar fondo a `thead` en `@media print`.

### F-14 · MEDIA · Alturas de controles distintas dentro de una misma fila
- **Dónde:** `.glass-input` mide unos 42 px (`_forms.css:4` padding 10 px) y TomSelect `min-height:42px` (`_tomselect.css:18`), pero las vistas lo sobrescriben con `py-1.5` (30 usos, unos 36 px), `py-2.5` (17), `py-3` (14, unos 46 px), además de los `height:38px !important` propios de las tablas de ítems. Ejemplos: `cotizaciones/_scripts.blade.php:51` `tipo-select py-1.5` junto al TomSelect; los filtros de `reportes_financieros/*` combinan fecha, select y botón con alturas distintas.
- **Corrección:** tres tamaños de control como tokens (`--control-h-sm: 36px`, `--control-h-md: 42px`, `--control-h-lg: 48px`) con clases `.input-sm/.input-lg`, y prohibir `py-*` sobre `.glass-input` (con lint de Blade o revisión).

### F-15 · MEDIA · Botones sin nombre accesible y etiquetas no asociadas
- **Evidencia:** 40 `<button … title="…">` sin `aria-label`, casi todos de solo emoji: 🗑️ `venta.blade.php:96`, ⬅️ `venta.blade.php:6` (es un `<a>` con solo `title`), 👁️/✏️ en los índices… Hay 371 campos y solo 47 `<label for>`. Ejemplo: `venta.blade.php:17,21,39`, donde `<label class="field-label">` no está asociado, así que hacer clic en la etiqueta no enfoca el campo y los lectores de pantalla no lo anuncian. `<span id="notif-title">` no está enlazado al panel.
- **Corrección:** el componente `<x-field label="…" name="…">` genera `for`/`id`. Los botones de icono deben llevar `aria-label` (el `title` solo no basta en táctil ni con lector de pantalla). El emoji debe ir en `<span aria-hidden="true">`.

### F-16 · MEDIA · Componentes Blade disponibles pero reimplementados
- **Evidencia:**
  - `x-modal` (`components/modal.blade.php`) y `x-table` solo se usan en `design_system/index.blade.php`.
  - Hay modales a mano en `caja/conceptos/index`, `cierre/index`, `cierre/show`, `configuracion/index`, `equipos/index` (2), `eventos/index`, `inventario/facturas/show`, `stocks/categorias/index`, `stocks/index`, `stocks/show` y `usuarios/index`, además de 4 en `layouts/app`.
  - Tres no tienen `role="dialog"`: `equipos/index:157,232` y `eventos/index:245`.
  - Defecto de `x-modal`: en modo formulario, toda acción `primary` se fuerza a `btn-danger` (`modal.blade.php:117-119`), aunque sea "Guardar". Además, la clase se concatena dos veces (`{{ $btnClass }} … {{ $action['class'] }}`).
  - El JS de `x-modal` registra **otro** manejador de Escape y de clic en el overlay, aparte del global.
- **Corrección:** arreglar `x-modal` (variante explícita `variant="danger|primary|success"`, un solo manejador de Escape por `data-xmodal` o `data-close`) y migrar los modales de forma progresiva, empezando por los 3 sin `role="dialog"`.

### F-17 · MEDIA · Tokens de diseño definidos pero ignorados
- **Dónde:** `resources/css/glass/_tokens.css:94-162`.
- **Evidencia:** `grep var(--…)` sobre `resources/css/glass/*.css` da 0 usos de `--color-primary`, `--color-success`, `--color-danger`, `--text-*`, `--radius-*` y `--shadow-*` fuera de su definición. A la vez hay ≈704 hex literales en CSS, 12 radios distintos y 15 tamaños de fuente distintos. `tailwind.config.js` duplica la paleta (`ts.blue` etc.) en lugar de leer las variables.
- **Corrección:** ver la sección 3 (mapear Tailwind a `var(--…)` y sustituir los literales por tokens archivo por archivo, empezando por `_buttons.css`).

### F-18 · MEDIA · Jerarquía de botones inconsistente para una misma acción
- **Evidencia:**
  - "Cancelar": `btn-cancel` ×17, `btn-ghost-amber` ×7, `btn-ghost-red` ×4, `btn-ghost` ×3.
  - En los confirms de tipo "success", `confirmDelete` pinta "Cancelar" en **rojo** (`btn-ghost-red`, `app.blade.php:372`), es decir, color de peligro para una acción neutra.
  - "Anular": `btn-action-anular` ×12, `btn-danger` ×7, `btn-ghost` ×15.
  - "Guardar": `btn-save` ×12, `btn-primary` ×7, `btn-ghost` ×2.
  - "PDF": `btn-pdf` ×6, `btn-ghost` ×2, `btn-action-print` ×2.
- **Corrección:** cinco roles de botón (sección 3): *primary*, *secondary/neutral*, *ghost*, *danger* (anular/rechazar, reversible) y *destructive* (eliminar definitivo). "Cancelar" siempre debe ser *secondary* neutro.

### F-19 · MEDIA · Coloreado del modal de confirmación deducido del texto del mensaje
- **Dónde:** `layouts/app.blade.php:360-365`.
- **Evidencia:** el modal se pinta como "success" (verde) si el mensaje contiene `cotización`, `cotizacion`, `seguridad`, `Respaldo`, `copia` o `Nueva Venta`, o si la URL contiene `backup`. Un futuro "¿Eliminar esta cotización?" o "¿Restaurar la copia de seguridad? (sobrescribe la BD)" saldría en verde con botón verde.
- **Corrección:** decidirlo solo con `data-confirm-type`, eliminar la heurística por texto y URL, y marcar `cotizaciones/show.blade.php:155` con `data-confirm-type="success"`.

### F-20 · MEDIA · Tablas en móvil: el modo tarjeta existe pero nunca se usa
- **Dónde:** `_responsive.css:68-119` (`.responsive-cards`), 15 vistas con `data-label` y 0 vistas con la clase `responsive-cards`. `_responsive.css:34-55` fuerza `min-width: 680px/820px` en `<1024px`.
- **Escenario:** en un celular, los listados de mantenimientos, caja o facturas solo se navegan con scroll horizontal de 820 px. La columna de acciones queda fuera de la vista.
- **Corrección:** aplicar `responsive-cards` en los listados que ya tienen `data-label` (por ejemplo `cotizaciones/index`) y completar los `data-label` que faltan. Otra opción es una columna sticky a la derecha para las acciones (`position:sticky; right:0`).

### F-21 · MEDIA · Dropdown de notificaciones sin semántica ni teclado
- **Dónde:** `topbar.blade.php:46-57`, `layouts/app.blade.php:1074-1098`.
- **Evidencia:** el botón no tiene `aria-expanded` ni `aria-controls`, y el panel no se cierra con Escape. Cuando se cierra con el mismo botón, el `clickHandler` del documento no se retira, así que los *listeners* se acumulan. Las filas son `<button>` sin `type` que llaman a `openNotifModal` y ocultan el dropdown con `classList.add('hidden')` sin animación ni reinicio de `opacity`.
- **Corrección:** `aria-expanded` sincronizado, cierre con Escape y `removeEventListener` en ambas ramas. Mejor aún, usar `<details>` o un *popover* nativo (`popover` / `popovertarget`), que ya resuelve el clic fuera y Escape.

### F-22 · MEDIA · Textos de rol y avatar mal formados
- **Dónde:** `topbar.blade.php:31,35` (`substr(auth()->user()->name, 0, 1)`) y `:40`.
- **Evidencia:** con un nombre que empiece por Á, É, Ñ…, `substr` corta el primer byte UTF-8 y `e()` lo sustituye por "�". Además, el rol se pinta en crudo ("tecnico" → "TECNICO", sin tilde) y con un respaldo `?? 'Admin'` engañoso.
- **Corrección:** usar `mb_substr($name, 0, 1)` o `Str::substr`, y un mapa de etiquetas `['admin'=>'Administrador','tecnico'=>'Técnico','invitado'=>'Invitado']`.

### F-23 · MEDIA · Mucho espacio en blanco al final de todas las páginas
- **Dónde:** `layouts/app.blade.php:120`: `pb-[30vh] md:pb-[50vh]` en `<main>`.
- **Escenario:** en escritorio, cada página termina con media pantalla vacía, lo que hace pensar que falta contenido o aparece una barra de scroll innecesaria. Parece pensado para dejar espacio a los desplegables, pero TomSelect y Flatpickr ya se anclan a `body`.
- **Corrección:** `pb-8`, y si algún formulario concreto lo necesita, `pb-[40vh]` solo en esa vista.

### F-24 · MEDIA · Fuentes externas en una aplicación de intranet
- **Dónde:** `_tokens.css:7` (`@import` de Google Fonts *Inter* dentro del CSS, que bloquea el render), `layouts/app.blade.php:68-71`, `layouts/guest.blade.php:17` y `layouts/consulta.blade.php:30` (Michroma y Orbitron).
- **Escenario:** sin internet en el taller, el logo y los títulos cambian a la fuente de respaldo. Además, el `@import` retrasa la primera pintura. CSP ya lo permite.
- **Corrección:** auto-hospedar los `.woff2` en `public/vendor/fonts` con `@font-face` y `font-display:swap`, quitar el `@import` y las preconexiones, y retirar `fonts.googleapis.com` y `fonts.gstatic.com` de la CSP.

### F-25 · MEDIA · Etiquetas de estado de documentos poco claras en el sidebar y la navegación
- **Dónde:** `sidebar.blade.php:65-67` ("Info Operativos" lleva a `reportes.financiero.diario`); nombres de menú como "Operaciones (C/V)" y "Caja (Ing/Egr)".
- **Escenario:** abreviaturas crípticas para usuarios nuevos. La entrada "Info Operativos" abre reportes **financieros**. No hay `aria-current="page"` en el elemento activo.
- **Corrección:** "Compras y ventas", "Caja", "Reportes"; añadir `aria-current="page"` en el `.nav-item.active`.

### F-26 · BAJA · Duplicación de `initPasswordToggles`
- **Dónde:** `resources/js/app.js:1-151` y `layouts/app.blade.php:2110-2259`, con código casi idéntico. Se registran **dos** `MutationObserver` sobre `body` con `subtree:true` que vuelven a escanear todos los `input[type=password]` en cada nodo añadido (toasts, opciones de TomSelect en cada pulsación).
- **Corrección:** dejar solo la versión del módulo `app.js` y borrar la del layout.

### F-27 · BAJA · Toasts
- **Dónde:** `layouts/app.blade.php:280-316`.
- **Evidencia:** el tipo `warning` se pinta azul, igual que `info`. No hay `aria-live`. El botón de cerrar es `&times;`. Los errores se cierran solos.
- **Corrección:** cuatro variantes (success, info, warning en ámbar, error persistente) y `role="status"` / `role="alert"`.

### F-28 · BAJA · Pestañas sin semántica ARIA
- **Dónde:** `dashboard.blade.php:161-170` (`switchDashTab`), modal de notificaciones `:1170-1184` (`filterNotifs`).
- **Evidencia:** no usan `role="tablist"`, `role="tab"` ni `aria-selected`. Las clases se reconstruyen como cadenas largas en JS (`app.blade.php:1341-1373`, unas 30 líneas por pestaña).
- **Corrección:** un componente `<x-tabs>` con atributos ARIA, estado activo por `aria-selected` y estilo por CSS (`[aria-selected=true]`).

### F-29 · BAJA · Iconografía mixta
- **Evidencia:** el sidebar usa SVG (`x-icon`, 31 usos) y el resto de la aplicación usa emoji (🗑️ ✏️ 👁️ 🖨️ 🔔 ☀️). Los emoji se ven distintos en Windows, Android e iOS, no heredan `currentColor` y su alineación vertical varía.
- **Corrección:** ampliar `x-icon` con el set de acciones (editar, ver, eliminar, anular, imprimir, campana, sol, luna) y reemplazar los emoji de los botones de acción.

### F-30 · BAJA · Títulos de página con tamaños distintos
- **Evidencia:** `<h2>` de página: 44 en `text-2xl` y 15 en `text-3xl`. `<h1>` se usa en pocas vistas con `text-xl`, `text-2xl` o `text-3xl`. El script de título de impresión (`app.blade.php:1967`) hace `h1 || .glass-card h2 || h2`.
- **Corrección:** un `<x-page-header title subtitle back>` que genera `<h1 class="text-2xl">` en todas las páginas.

### F-31 · BAJA · CSS compilado pesado y con selectores por ID de vista
- **Evidencia:** `public/build/assets/glass-*.css` pesa 258 KB y `app-*.css` 82 KB. `_utilities.css` (4.225 líneas) y `_buttons.css` (3.556) contienen unos 168 selectores con `#id` de vistas concretas (`#items-table`, `#tabla-mantenimientos`…), que mezclan estilos de página con el sistema global.
- **Corrección:** mover los estilos específicos a `@push('styles')` de cada vista o a `resources/css/pages/*.css` y podar las reglas muertas (por ejemplo las variantes `btn-backup-*` no usadas).

### F-32 · BAJA · Los avisos inline usan colores hardcodeados en vez de semánticos
- **Dónde:** botones de las tarjetas del modal de notificaciones (`style="background-color: #2563eb"`, `#9333ea`, `#4f46e5`, `#f97316`, `#10b981` en `app.blade.php:1210-1328`); badges del dropdown (`topbar.blade.php:71-107`).
- **Corrección:** usar las clases `.badge-{tono}` y `.btn-sm-{tono}` basadas en tokens.

### F-33 · BAJA · `<style>` embebidos en vistas
- **Dónde:** `inventario/facturas/edit.blade.php:≈180-196`, `dashboard.blade.php:4-15` (impresión) y otros.
- **Corrección:** moverlos al CSS de página.

### F-34 · BAJA · El sidebar en escritorio táctil solo se expande al pasar el ratón
- **Dónde:** `layouts/app.blade.php:850-861`.
- **Evidencia:** en tablets de ≥1024 px (iPad en horizontal) el menú queda colapsado a 64 px y las etiquetas solo aparecen con `mouseenter`, que el tacto dispara de forma errática.
- **Corrección:** con `@media (hover:none)`, tratarlo como móvil (drawer) o añadir un botón para fijar el menú.

### F-35 · BAJA · Los modales dependen de una convención frágil de nombres para Escape
- **Dónde:** `layouts/app.blade.php:573-580`.
- **Evidencia:** el manejador convierte `id="xxx-yyy-modal"` en `closeXxxYyyModal()`. Comprobado con un script: no hay función para `ts-notif-modal` (F-03), `pwd-cierre-modal`, `pwd-delete-modal`, `calc-modal` ni `detalle-modal`; algunos tienen su propio manejador y otros no.
- **Corrección:** declarar el cierre explícitamente con `data-close-fn` o usar `<dialog>` nativo (`showModal()`), que ya resuelve Escape, foco y `inert`.

### F-36 · BAJA · Fondo de modal y overlay con `!important` en `display`
- **Dónde:** `_modals.css:46-75` (`.ts-modal-overlay.hidden { display:flex !important; visibility:hidden }`).
- **Evidencia:** un modal "oculto" sigue en el árbol de layout y de accesibilidad con `visibility:hidden`. Las animaciones dependen de *timeouts* de 10, 200 y 300 ms repartidos en 6 funciones.
- **Corrección:** con `<dialog>` nativo o `x-modal` unificado basta una única función `openModal(id)` / `closeModal(id)`.

### F-37 · BAJA · Indicadores visuales de la campana demasiado insistentes
- **Dónde:** `topbar.blade.php:48-53`.
- **Evidencia:** `animate-ping` permanente mientras exista **cualquier** pendiente (es lo normal en un taller), sin número. Termina ignorándose ("ceguera de alerta").
- **Corrección:** mostrar el número, animar solo cuando haya ítems críticos nuevos sin leer y respetar `prefers-reduced-motion`.

---

## 2. Revisión específica del commit b7565c3

| Archivo | Cambio | Valoración |
|---|---|---|
| `_modals.css` + `layouts/app` (modal de notificaciones) | `overflow:hidden !important`, lista con `max-h-[440px]` | **Regresión (F-02)**: recorta el botón Cerrar en pantallas de menos de unos 700 px de alto. |
| `_tables.css` (+164 líneas) | `thead` sticky global con `!important`, bloque duplicado; altura de fila de 55 px; `scroll-snap mandatory` | F-12, F-13. El objetivo (cabecera fija y 5 filas) es válido, pero el alcance es demasiado amplio y el snap obligatorio molesta. |
| `venta` / `compra` / `facturas/edit` / `cotizaciones` | Quita estilos inline de los `td` y pasa a `align-middle`, `w-full` en los inputs, `col-accion` en `th` | **Mejora**: menos inline y controles alineados a 38 px. Queda pendiente el aviso absoluto que sobresale (F-12b). |
| Auto-scroll al añadir fila | `setTimeout(60)` + `scrollTo({behavior:'smooth'})` | Correcto. Combinado con el snap obligatorio puede terminar en una fila distinta de la nueva (F-12c). |
| `topbar` | `py-1` → `flex flex-col`; `.notif-row:last-child` sin borde | Correcto, sin efectos colaterales. |
| Se quita `relative z-10` del botón 🗑️ | | Correcto: evita que el botón quede por encima del `thead` sticky (z-30). |

---

## 3. Propuesta de sistema de diseño consolidado (basado en lo existente)

**Principio:** conservar la estética "Liquid Glass" y la paleta actual, pero hacer que **todo** derive de tokens en `_tokens.css`, que Tailwind también consumirá.

### 3.1 Tokens

```css
:root{
  /* Marca */
  --c-primary:#2563EB; --c-primary-600:#1D4ED8; --c-accent:#06B6D4; --c-ink:#0F172A;
  /* Módulos (ya usados de facto) */
  --c-mant:#2563EB; --c-elec:#9333EA; --c-cot:#4F46E5; --c-venta:#10B981; --c-compra:#F97316; --c-caja:#F59E0B;
  /* Semánticos */
  --c-success:#10B981; --c-warning:#F59E0B; --c-danger:#EF4444; --c-info:#3B82F6; --c-neutral:#64748B;
  /* Superficies / texto (claro; html.dark redefine) */
  --surface-0:#DDE7F5; --surface-glass:var(--glass-bg); --surface-solid:#FFFFFF; --surface-thead:#E2E8F0;
  --text-1:#0F172A; --text-2:#475569; --text-3:#94A3B8; --border-1:#CBD5E1;
  /* Tipografía (escala 1.125) */
  --fs-xs:11px; --fs-sm:12.5px; --fs-base:14px; --fs-md:16px; --fs-lg:18px; --fs-xl:20px; --fs-2xl:24px; --fs-3xl:30px;
  --fw-regular:400; --fw-medium:500; --fw-bold:700; --fw-black:900;
  /* Espaciado (4px) */
  --sp-1:4px; --sp-2:8px; --sp-3:12px; --sp-4:16px; --sp-5:20px; --sp-6:24px; --sp-8:32px;
  /* Radios: reducir 12 valores a 5 */
  --r-sm:8px; --r-md:12px; --r-lg:16px; --r-xl:20px; --r-pill:9999px;
  /* Sombras */
  --sh-1:0 2px 8px -1px rgb(15 23 42/.08); --sh-2:0 10px 30px -5px rgb(37 99 235/.08),0 4px 12px rgb(0 0 0/.03); --sh-modal:0 28px 60px -12px rgb(15 23 42/.2);
  /* Controles */
  --control-h-sm:36px; --control-h-md:42px; --control-h-lg:48px;
  /* Capas (z-index únicos) */
  --z-base:1; --z-sticky:30; --z-topbar:40; --z-sidebar:50; --z-dropdown:1000; --z-modal:5000; --z-toast:6000;
}
```

`tailwind.config.js` → `theme.extend.colors.primary = 'var(--c-primary)'`, etc., y `borderRadius`, `boxShadow` y `zIndex` mapeados a las mismas variables. Así desaparecen `[#2563EB]` y los hex en Blade.

### 3.2 Tipografía y títulos
- Inter para la interfaz. Michroma y Orbitron **solo** para el logotipo, auto-hospedadas.
- `<x-page-header>` genera `h1` en 24 px/900 con subtítulo de 14 px en `--text-2`; las secciones usan `h2` de 18 px/700; las tarjetas, `h3` de 16 px/700.

### 3.3 Botones (5 roles × 3 tamaños)

| Rol | Uso | Clase |
|---|---|---|
| primary | Acción principal (Guardar, Registrar, Nuevo) | `.btn .btn-primary` (o el color del módulo con `.btn-primary[data-tone=elec]`) |
| secondary | Cancelar, Volver, Cerrar | `.btn .btn-secondary` (neutro, **nunca rojo ni ámbar**) |
| ghost | Acciones terciarias o de tabla (Ver, Editar, PDF) | `.btn .btn-ghost` + `.btn-icon` para solo icono (con `aria-label` obligatorio) |
| danger | Anular, Rechazar (reversible, queda en el historial) | `.btn .btn-danger-soft` (borde y texto rojo, fondo tenue) |
| destructive | Eliminar definitivo, Restaurar BD, Borrar respaldo | `.btn .btn-destructive` (sólido rojo; confirmación escribiendo el nombre si es irreversible) |

Tamaños: `.btn-sm` (32 px), por defecto (42 px), `.btn-lg` (48 px). Las variantes de un solo uso (`btn-backup-*`, `btn-execute-backup*`, `btn-theme-*`, `btn-ghost-violet`, etc.) pasan a ser alias.

### 3.4 Formularios
- `<x-field label name required hint>` con `<label for>`, control, ayuda y `@error` en línea con `aria-describedby`.
- `.glass-input` con 3 alturas por token. Select con TomSelect a la misma altura. Fecha con Flatpickr a la misma altura.
- El indicador de obligatorio es `*` con `aria-hidden` más `required`.

### 3.5 Tablas
- `<x-table>` con contenedor `overflow-x-auto`, un `thead` sticky **opcional** (`sticky` prop), `responsive-cards` en `<768px` (con `data-label` obligatorio) y estado vacío en un *slot*.
- Paginación con `->withQueryString()`.
- Búsqueda en servidor con `<x-search-input name="search">`.

### 3.6 Tarjetas, pills y alertas
- `.glass-card` es la única superficie. Los acentos de módulo se aplican con `--card-accent`, que ya existe.
- Pills: `pill-{success|warning|danger|info|neutral}`, más `pill-anulado`.
- `<x-alert tone>` para avisos persistentes en página (no usar toasts para errores de validación).

### 3.7 Modales
- `<x-modal>` reparado o `<dialog>` nativo, con variantes `confirm` (danger, destructive, success), `form` e `info`.
- Requisitos: `max-height: 90dvh`, cuerpo con scroll interno (`min-h-0 flex-1 overflow-y-auto`) y pie siempre visible; Escape, clic en el fondo, foco inicial en "Cancelar" para los destructivos y restauración del foco al cerrar.

### 3.8 Notificaciones, toasts y menús
- Toast `role=status` (o `role=alert` en errores), 4 tonos, los errores no se cierran solos y como máximo 3 en pila.
- Dropdowns con `popover` nativo o `<details>`, `aria-expanded` y Escape.
- Menú lateral con `aria-current`.

### 3.9 Iconografía
- `x-icon` es la única fuente (SVG con stroke de 1,75–2 en `currentColor`): 16 px en tablas, 20 px en botones y 24 px en cabeceras.
- Emoji solo como decoración (`aria-hidden`) en estados vacíos.

**Esfuerzo estimado:**
- Tokens + Tailwind: 1 día.
- Botones (alias y retirada de variantes): 2 días.
- `x-field` y `x-page-header`: 2 días.
- `x-modal` / `<dialog>` y migración de 15 modales: 3 días.
- `x-table` con tarjetas móviles y búsqueda en servidor: 3 días.
- Iconos: 1 día.
- **Total: unas 2,5 semanas para un desarrollador, de forma incremental por módulo.**

---

## 4. Propuesta del sistema de notificaciones

### 4.1 Estado actual (diagnóstico)
- Se calculan en cada render de `layouts.app` (View::composer) con caché de 30 s **por usuario** pero con datos globales. No hay persistencia, estado de leído, prioridad ni destinatario.
- No respetan permisos: el admin y el técnico ven lo mismo, y el invitado lo ve en la 404 (F-01).
- No hay duplicados entre facturas y movimientos gracias a los filtros `whereNull('factura_id')` y `descripcion NOT LIKE`. Sin embargo, ese último filtro depende del texto de la descripción y genera hasta 50 cláusulas `LIKE`.
- No existen las categorías "atrasada" ni "vencida". La alerta de login es redundante con el modal y se abre en cada inicio de sesión (F-06).

### 4.2 Diseño propuesto

Opción recomendada: usar la tabla estándar de Laravel `notifications` (`php artisan make:notifications-table`) más columnas propias.

```text
notifications
  id               uuid PK
  type             string      (clase Notification: OrdenAtrasada, SaldoPorCobrar…)
  notifiable_type/notifiable_id  → User destinatario (morph)
  data             json        {titulo, mensaje, url, modulo, monto?, ref_type, ref_id}
  prioridad        tinyint     1=crítica 2=alta 3=informativa        (columna añadida, indexada)
  dedupe_key       string(120) p.ej. "orden_atrasada:mantenimiento:345"  (unique con notifiable_id)
  read_at          timestamp null
  resuelta_at      timestamp null   (la condición dejó de cumplirse → se oculta sola)
  created_at/updated_at
  INDEX (notifiable_id, read_at, resuelta_at, prioridad)
```

Reglas, generadas por el comando `app:notificaciones-generar` (scheduler cada 10–15 min) y por observers para resolverlas al instante:

| Tipo | Condición | Prioridad | Destinatario |
|---|---|---|---|
| Orden atrasada | `pendiente` con más de N días (config, 3 por defecto) | Crítica | Técnico asignado (`tecnico_id`) y admins |
| Orden terminada sin entregar | `terminado` sin `fecha_salida` y más de N días | Alta | Admins |
| Saldo por cobrar | Factura de venta u orden con `saldo_pendiente > 0` y más de N días | Alta | Admins (técnico solo si lo decide negocio) |
| Saldo por pagar | Factura de compra con saldo | Alta | Admins |
| Cotización por vencer o vencida | `fecha + validez_dias` ≤ hoy + 2 | Informativa → Alta | Creador y admins |
| Stock bajo | `cantidad < minimo` (hoy "<5" fijo en el dashboard) | Informativa | Admins |
| Respaldo fallido / caja sin cierre de ayer | | Crítica | Admins |

Comportamiento de la interfaz:
- La campana muestra el **número** de no leídas con prioridad ≤ 2. Solo anima (con `prefers-reduced-motion` respetado) si hay críticas nuevas.
- El panel agrupa por prioridad, con "Marcar todo como leído" y enlace a "Ver todas" (`/notificaciones`, paginado y filtrable).
- El modal al iniciar sesión aparece solo si hay **críticas no leídas** y como máximo una vez al día. Las informativas nunca abren el modal.
- El clic en una notificación la marca como leída (`PATCH /notificaciones/{id}/leer`) y navega a la URL.
- Resolución automática: los observers de `Mantenimiento`, `Electronica`, `Factura`, `Abono` y `Cotizacion` ponen `resuelta_at` cuando la condición deja de cumplirse. La deduplicación usa `dedupe_key`, de modo que nunca se crea dos veces la misma alerta abierta.
- El composer se reduce a una consulta indexada: `auth()->user()->unreadNotifications()->whereNull('resuelta_at')->orderBy('prioridad')->limit(20)` más un `count()`.

**Esfuerzo:**
- Migración, clases Notification y comando: 1,5 días.
- Observers y resolución: 1 día.
- Interfaz (campana, panel, página y marcado como leído): 1,5 días.
- Tests (permisos por rol, deduplicación, resolución, invitado sin acceso): 1 día.
- **Total: unos 5 días.**
- Arreglo rápido sin tabla (F-01, F-02, F-03 y F-05 a/b/c): **medio día**.

---

## 5. Qué se revisó y está correcto
- Escape de HTML en toasts (`safeMessage`) y en las opciones de TomSelect (`escape()`); `escapeHtml` en las filas de cotización.
- Bloqueo de doble envío global (`app.blade.php:756-785`), con liberación por `pageshow` (bfcache) y exclusión de formularios GET y con `target`.
- Modal global de anulación con intención explícita (`accion`) y contraseña de admin para el técnico. Foco inicial y restauración de foco en `ts-modal`, `global-anular` y `global-rechazar`.
- Estados vacíos presentes en los 16 índices con tabla (`@forelse/@empty`).
- Todas las tablas de pantalla, salvo la de respaldos (que se genera en JS dentro de un contenedor propio), están envueltas en `overflow-x-auto`.
- Flatpickr y TomSelect se anclan a `body` y se reposicionan al hacer scroll, al redimensionar o cuando el sidebar se expande.
- Título de pestaña por ruta, `lang="es"`, `meta viewport` correcto, tema oscuro sin parpadeo (script temprano) y `prefers-reduced-motion` respetado en el cambio de tema.
- Los cambios de b7565c3 en `venta`/`compra`/`facturas`/`cotizaciones` reducen los estilos inline y alinean los controles (ver la sección 2).
- La interfaz del invitado usa `layouts.consulta` y `layouts.guest` (salvo la 404, F-01).

## 6. Archivos revisados
- Layouts: `layouts/app.blade.php` (completo, 2.266 líneas), `layouts/partials/topbar.blade.php`, `layouts/partials/sidebar.blade.php`, cabeceras de `guest`/`consulta`.
- Componentes: `components/modal.blade.php`, `components/table.blade.php` y el uso de los 5 componentes.
- Vistas: `dashboard.blade.php` (completo hasta los scripts de Chart.js), `inventario/venta.blade.php` (cabecera, tabla y `eliminarFila`), diffs completos de b7565c3 (`compra`, `facturas/edit`, `cotizaciones/create|edit|_scripts`), `cotizaciones/_scripts.blade.php` (filas y eliminación), `mantenimientos/show.blade.php` (repuestos y abonos), `errors/*.blade.php` (cabeceras), `auth/*.blade.php` (cabeceras), `configuracion/index.blade.php` (fragmentos).
- CSS: `_tokens.css` (completo), `_modals.css` (completo), `_responsive.css` (completo), `_tables.css` (líneas 1-90, 270-340 y diff), `_topbar.css` (fragmentos); métricas por `grep` de todos los `resources/css/glass/*.css`.
- JS: `resources/js/app.js`.
- Backend relacionado: `app/Providers/AppServiceProvider.php` (composer y gates), `AuthController.php:240-325`, `routes/web.php` (grupos de rol), `UserPolicy`, `HandlesAbono` (anulación), controladores (paginación y `search`).
- Métricas por `grep` sobre las 114 vistas: hex, `style=`, clases `btn-*`, tamaños de títulos, labels, `aria-*`, modales, `data-label`, `confirm()`, `filterTable` y `@error`.

## 7. Qué NO se revisó (o solo parcialmente)
- No hubo verificación visual en un navegador real: los cálculos de altura de F-02 y F-12 y el comportamiento de `sticky` con `overflow:hidden` en la tabla (F-13) se dedujeron del CSS. Conviene comprobarlos en 1366×768, 390×844 y 360×640.
- Lectura línea a línea solo de las vistas indicadas en la sección 6. Del resto (≈90 vistas: clientes, equipos, proveedores, técnicos, usuarios, stocks, caja, cierre, eventos, reportes y reportes financieros, configuración completa, guest y consulta) solo se tomaron métricas automáticas y fragmentos.
- `_buttons.css` (3.556 líneas), `_utilities.css` (4.225), `_forms.css`, `_flatpickr.css`, `_tomselect.css` y `_sidebar.css`: solo métricas, sin lectura completa.
- Vistas PDF e impresión: excluidas, porque corresponden al Agente de Excel/PDF/Impresión.
- Contraste de color WCAG (no se midieron ratios) y navegación completa por teclado.
- Gráficos de Chart.js más allá de la configuración `responsive`/`maintainAspectRatio` (leyendas y tooltips en móvil).
- **Fuera de alcance, para Seguridad:** el gate `view-financial-reports` está definido pero no se usa, y el técnico accede a `reportes-financieros` desde el sidebar ("Info Operativos").
