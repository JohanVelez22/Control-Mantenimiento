# Deuda técnica — hoja de ruta

Estado al 2026-11-05. Este documento lista lo que **queda pendiente** después de la auditoría de noviembre (ver `CHANGELOG.md` 1.1.0 y 1.2.0 para lo ya resuelto). Sirve como guía para este proyecto y para los proyectos que se deriven de él.

## Cómo se priorizó

| Prioridad | Criterio |
|---|---|
| **Alta** | Puede causar pérdida de datos o deja zonas del código sin red de seguridad. |
| **Media** | Afecta la seguridad en profundidad o la mantenibilidad de forma notable. |
| **Baja** | Mantenibilidad, rendimiento o accesibilidad, sin impacto funcional hoy. |

**Esfuerzo:** S = horas · M = 1–2 días · L = varios días. **Riesgo:** probabilidad de romper algo al hacer el cambio.

## Backlog

| # | Prioridad | Deuda | Por qué importa | Esfuerzo | Riesgo |
|---|---|---|---|---|---|
| 1 | Alta | Claves foráneas en cascada (`users`, `clientes`, `equipos`, `tecnicos`, `stocks`) → `restrict` | Un borrado manual (tinker, SQL) arrastraría órdenes, abonos y caja. La app no expone borrados físicos, pero la base no lo impide. | M | Medio: migración sobre datos reales |
| 2 | Alta | Cobertura de pruebas en comandos de consola (`app:backup-db` con MySQL real, `app:clean-data`), PDF de facturas y matriz de permisos por endpoint POST | Hoy hay prueba de humo de todas las pantallas GET, pero no de cada acción POST por rol. | M | Bajo |
| 3 | Media | JavaScript inline en vistas (≈75 bloques `<script>`, ≈200 `onclick`) → módulos de Vite | Es lo único que impide quitar `'unsafe-inline'` de la CSP. | L | Medio |
| 4 | Media | Validación inline en controladores → FormRequests; `registrarCompra`/`registrarVenta` casi duplicados | Reglas repartidas y código repetido en `MovimientoInventarioController` (≈1.000 líneas). | M | Bajo (hay pruebas) |
| 5 | Media | Autorización mezclada: Policies/Gates definidos, pero la mayoría de controladores compara `role` a mano | Las reglas son difíciles de auditar; gates como `view-financial-reports` no se aplican. | M | Medio |
| 6 | Media | Reportes financieros visibles para el técnico | **Decisión de negocio pendiente.** Si debe ser solo admin: `Gate::authorize('view-financial-reports')` en `ReporteFinancieroController`. | S | Bajo |
| 7 | Media | Login por nombre / alias (`admin`) | Facilita adivinar la cuenta. Ya no hay ambigüedad entre usuarios, pero conviene exigir correo. | S | Bajo |
| 8 | Baja | CSS: ≈3.900 `!important` y `layouts/app.blade.php` de ≈2.200 líneas | Mantenibilidad. Dividir el layout en parciales y limpiar reglas por módulo. | L | Alto en lo visual |
| 9 | Baja | Casts `decimal:2` en montos | Consistencia de tipos; requiere adaptar el JSON que consumen los formularios. | S | Medio |
| 10 | Baja | Accesibilidad: ≈200 `<label>` sin `for`, modales sin trampa de foco | Uso con teclado y lector de pantalla. | M | Bajo |
| 11 | Baja | Overlays con `backdrop-filter` siempre presentes en el DOM | Rendimiento en equipos lentos. | S | Bajo |
| 12 | Baja | Código repetido en vistas: modal de baja de stock (2 copias), 5 formas de formatear montos | Crear componentes `<x-money-input>` y `<x-glass-modal>`. | M | Bajo |
| 13 | Baja | Rollback de migraciones de junio 2026 en SQLite | Solo afecta revertir historia antigua; desde 2026_07_07 el rollback está verificado. | S | Bajo |

## Orden recomendado

1. **#6** (decisión rápida) y **#2** (más pruebas antes de refactorizar).
2. **#4** y **#5**, apoyados en las pruebas del paso anterior.
3. **#1** en una ventana de mantenimiento con respaldo (`app:backup-db` → `app:verificar-migraciones` → migración).
4. **#3**: al terminar, quitar `'unsafe-inline'` de `script-src` en `SecurityHeaders`.
5. **#8–#12** de forma incremental, módulo por módulo, con revisión visual.

## Herramientas disponibles

| Comando | Para qué |
|---|---|
| `php artisan app:preflight` | Lista lo que falta para producción (configuración, contraseñas, migraciones, compilación, respaldos). |
| `php artisan app:verificar-migraciones` | Duplicados y migraciones pendientes antes de migrar (solo lectura). |
| `php artisan security:check` | Revisión rápida de configuración sensible. |
| `php artisan test` | 596 pruebas, incluida la prueba de humo de todas las pantallas (`SmokeRutasTest`). |
| `npm run vendor:sync` | Copia flatpickr, tom-select y chart.js a `public/vendor` (actualizar SRI si cambian versiones). |
