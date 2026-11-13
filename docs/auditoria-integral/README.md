# Auditoría integral 2026-11-13: índice

Esta carpeta es la **documentación técnica vigente** del sistema. Cuando un documento antiguo de `docs/` la contradiga, vale esta carpeta (ver DOC-01 en `ARQUITECTURA.md`).

## Cómo se hizo

- **Organización:** 9 agentes especializados y un coordinador. Cada agente trabajó en solo lectura y entregó evidencia: pruebas de demostración, `EXPLAIN` sobre la base real con `READ ONLY` y documentos generados y leídos.
  1. Arquitectura y reutilización
  2. Backend
  3. Frontend, UI/UX y notificaciones
  4. Base de datos
  5. Finanzas
  6. Ciberseguridad
  7. Excel, PDF e impresión
  8. QA
  9. DevOps y respaldos
- **Correcciones:** las hizo el coordinador, una por commit y con una prueba que falla con el código anterior.
- **Protección de la operación:** antes de modificar se respaldó y se comprobó la recuperación. No se escribió en la base MySQL real.

## Entregables

| # | Documento | Contenido |
|---|---|---|
| 1 | `INVENTARIO.md` | Estructura, dependencias, comandos y módulos (conteo real) |
| 2 | `ARQUITECTURA.md` | Arquitectura actual, problemas (ARQ-xx) y arquitectura objetivo |
| 3 | `AUDITORIA_TECNICA.md` | Backend y lógica de negocio (B-xx) |
| 4 | `AUDITORIA_SEGURIDAD.md` | Seguridad (S-xx) |
| 5 | `AUDITORIA_FINANCIERA.md` | Finanzas (M-xx), idempotencia y libro de movimientos |
| 6 | `AUDITORIA_BASE_DATOS.md` | Esquema, integridad, índices, concurrencia y respaldos (D-xx) |
| 7 | `AUDITORIA_UI_UX.md` | Interfaz, sistema de diseño y notificaciones (F-xx) |
| 8 | `AUDITORIA_EXCEL_PDF_IMPRESION.md` | Documentos generados de verdad (X-xx) |
| 9 | `ANALISIS_REUTILIZACION.md` | Reutilización por componente, % medido y calificaciones |
| 10 | `PLAN_MODERNIZACION.md` | Fases 0 a 5, sin reescritura |
| 11 | `CAMBIOS_REALIZADOS.md` | Commits y detalle de lo corregido |
| 12 | `MIGRACIONES.md` | Migración de esta ronda y estrategia |
| 13 | `RESULTADOS_TESTING.md` | 628 pruebas / 2316 aserciones, verificaciones manuales y lo no verificado |
| 14 | `PENDIENTES.md` | P0 (acciones del dueño), P1 y P2, más los riesgos aceptados |
| 15 | `GUIA_INSTALACION.md` | Instalación y entorno de pruebas aislado |
| 16 | `GUIA_DESPLIEGUE.md` | Pasos obligatorios de esta versión, staging y despliegue |
| 17 | `GUIA_RESPALDOS.md` | Tarea programada, 3-2-1, recuperación ante desastre y verificación |
| 18 | `EVALUACION_FINAL.md` | **Categoría B (apto con restricciones)**, condiciones para producción y las 12 respuestas |

## Registro de archivos

### Revisados

Revisión hecha por los agentes. El detalle por archivo y línea está en la sección "Qué revisé / no revisé" de cada informe.

| Área | Cobertura |
|---|---|
| `app/` (92 archivos, 14.805 líneas) | Completa por arquitectura (agente 1). Controladores y servicios financieros línea a línea (agentes 2, 4, 5 y 6) |
| `resources/views` (114 vistas) y `resources/css`/`js` | Métricas de todas. Línea a línea: layouts, notificaciones, formularios principales, PDF/impresión (agentes 3 y 7) |
| `routes/` (155 rutas con sus middleware) | Completa (agente 6, `route:list`) |
| `database/` (62 migraciones + seeders) | Esquema real comparado con las migraciones (agente 4) |
| `config/`, `bootstrap/`, `.env.example`, scripts `.bat`, `docs/` antiguos | Completa (agentes 1, 6 y 9) |
| `tests/` (71 archivos) | Suite ejecutada completa, con tiempos, cobertura por ruta y calidad (agente 8) |
| Base MySQL real | Solo lectura: esquema, 35 FK, integridad, `EXPLAIN` y privilegios (agente 4) |

### Modificados en esta ronda

Excluye los propios documentos de esta carpeta.

- `app/Console/Commands/BackupDatabase.php`
- `app/Console/Commands/RestablecerPassword.php`
- `app/Console/Commands/VerificarRespaldo.php`
- `app/Http/Controllers/BackupController.php`
- `app/Http/Controllers/CierreCajaController.php`
- `app/Http/Controllers/MovimientoCajaController.php`
- `app/Http/Controllers/MovimientoInventarioController.php`
- `app/Http/Controllers/PasswordController.php`
- `app/Http/Controllers/ReporteController.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Middleware/IdempotenciaFormulario.php`
- `app/Models/Factura.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/CierreCajaGuard.php`
- `app/Traits/HandlesAbono.php`
- `app/Traits/HandlesStockAttach.php`
- `bootstrap/app.php`
- `database/migrations/2026_11_13_120000_vincular_pagos_legacy_a_facturas.php`
- `phpunit.xml`
- `resources/views/electronicas/factura.blade.php`
- `resources/views/electronicas/factura_pos.blade.php`
- `resources/views/inventario/facturas/edit.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/mantenimientos/factura.blade.php`
- `resources/views/mantenimientos/factura_pos.blade.php`
- `routes/console.php`
- `tests/Feature/AuditFindingsTest.php`
- `tests/Feature/AuditoriaIntegralCorreccionesTest.php`
- `tests/Feature/BackupRotacionTest.php`
- `tests/Feature/CambioPasswordObligatorioTest.php`
- `tests/Feature/DiaDeTrabajoTest.php`
- `tests/Feature/EventosViewTest.php`
- `tests/Feature/FinanzasCorreccionesTest.php`
- `tests/Feature/InvariantesFacturaCajaStockTest.php`
- `tests/Feature/MovimientoCajaControllerTest.php`
- `tests/Feature/MovimientoInventarioControllerTest.php`
- `tests/Feature/ProductionIntegrityTest.php`
- `tests/Feature/ReporteDiarioExportCoincideTest.php`
- `tests/Feature/RobustezContableAuditTest.php`
- `tests/Feature/SecurityBloque0AuditTest.php`
- `tests/Feature/SmokeRutasTest.php`
- `tests/TestCase.php`

### Pendientes

Archivos con hallazgos abiertos; el detalle está en `PENDIENTES.md`.

- **Órdenes:** `app/Http/Controllers/MantenimientoController.php` y `ElectronicaController.php` (B-01, B-03, B-06, B-07, ARQ-02).
- **Inventario y reportes:**
  - `app/Http/Controllers/MovimientoInventarioController.php` y `ReporteFinancieroController.php` (B-05, X-04 a X-07, M-11, M-12).
  - Vistas `stocks/*`, `reportes_financieros/*`, `mantenimientos/reportes.blade.php` y `electronicas/reportes.blade.php` (X-04, X-05, F-04).
- **Plantillas de impresión:** `resources/views/layouts/print.blade.php` (X-02).
- **Caja:**
  - `app/Models/MovimientoCaja.php`, por los accessors con `LIKE` (D-05).
  - Vistas `caja/*.blade.php`: lógica y consultas en Blade (ARQ-03).
- **Núcleo de la aplicación:**
  - `app/Providers/AppServiceProvider.php`: DDL y notificaciones (ARQ-06, D-18).
  - `app/Http/Controllers/AuthController.php`: alias fijos y alertas duplicadas (ARQ-05, ARQ-07).
- **Layout y estilos:** `resources/views/layouts/app.blade.php` (ARQ-04) y `resources/css/glass/_buttons.css` / `_utilities.css` (ARQ-04, F-xx).
- **Respaldos:** `app/Http/Controllers/BackupController.php`, restauración no atómica (D-01, S-08).
- **Configuración y scripts:** `start-ngrok.bat` (S-03) y `.env` (S-01, D-07: acción del dueño).
- **Documentación antigua:** `docs/*.md` contradictorios (DOC-01).
