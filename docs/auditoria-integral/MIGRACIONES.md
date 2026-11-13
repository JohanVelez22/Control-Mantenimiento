# Migraciones

## Migraciones de esta ronda

| Migración | Qué hace | ¿Destructiva? | ¿Reversible? | Estado en la base real |
|---|---|---|---|---|
| `2026_11_13_120000_vincular_pagos_legacy_a_facturas` | Asigna `factura_id` a pagos antiguos vinculados solo por el texto `#VT-n`/`#CP-n` (necesaria porque el código ya no enlaza por texto) | No (solo rellena columnas NULL; no toca montos, fechas ni estados) | Sí (`down()` revierte exactamente las filas vinculadas, que quedan listadas en `eventos`) | **No aplicada por el coordinador**: el entorno de la auditoría no permitió `migrate --force` sobre la base de producción. En la base real vincularía **0 filas** (conteo de solo lectura). Se aplica en el siguiente despliegue (ver `GUIA_DESPLIEGUE.md`) |

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

