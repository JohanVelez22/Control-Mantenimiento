# Plan de modernización

Principio: **no reescribir**. Se conserva el stack (Laravel 13, PHP 8.4, MySQL 8.4, Blade, Tailwind, Vite) porque está al día, tiene 628 pruebas y su dominio está bien modelado. Lo que hay que reconstruir es el núcleo, y se hace por extracción.

No se actualizó ninguna dependencia en esta ronda: `composer audit` y `npm audit` salen limpios y no había motivo justificado.

## Fase 0: operar seguro (hecha en esta ronda, salvo las acciones del dueño)

Hecho: correcciones financieras M-01 a M-08, idempotencia, respaldos verificables, aislamiento de las pruebas, fugas de datos al invitado y Q-10. Las acciones del dueño están en `PENDIENTES.md`, sección P0.

## Fase 1: núcleo configurable (≈1 semana)

1. `config/empresa.php`: nombre, marca, logo, dominio de correo y módulos habilitados. Así se elimina "WolfCore", "@tecnisystemas.com" y "Wolf Guard" del código (ARQ-05).
2. Login sin alias fijos; el bloqueo escalonado se conserva.
3. Un solo resolvedor de binarios (`App\Support\Binarios`) en lugar de las tres copias (ARQ-09).
4. Sacar de `AppServiceProvider` el `ALTER TABLE` en tiempo de ejecución y las 182 líneas de notificaciones (ARQ-06, D-18).

## Fase 2: permisos declarativos (≈1 semana)

1. Policies y gates que se usan de verdad (`authorize`, `@can`); `Gate::before` para admin.
2. Rutas con el rol real; desaparecen las 29 comparaciones de rol en controladores y las 90 en vistas (ARQ-01, S-11).
3. FormRequests para la validación (deuda #4).

## Fase 3: interfaz y documentos (≈3 semanas)

1. Sistema de diseño con tokens y componentes: botón, campo con error en línea, modal único, tabla con búsqueda en el servidor y modo tarjeta en móvil (`AUDITORIA_UI_UX.md`).
2. Partir `layouts/app.blade.php` (2.265 líneas) y llevar su JavaScript a módulos de Vite.
3. Notificaciones persistentes con la tabla `notifications`, prioridad, `read_at`/`resuelta_at` y destinatario por rol o técnico.
4. Plantillas PDF A4 de flujo continuo (X-02) y los mismos totales en pantalla, Excel y PDF (X-04 a X-06).

## Fase 4: dominio (≈2 semanas, riesgo alto)

1. Unificar Mantenimiento y Electrónica en una sola orden con tipo (ARQ-02); migración de datos con respaldo y prueba de restauración.
2. Libro de movimientos *append-only*: las correcciones son reversos y nunca ediciones, con hash encadenado opcional (M-09, propuesta del agente 5).
3. Costo histórico congelado y utilidad real (M-11, M-12).

## Fase 5: plantilla maestra

Al terminar las fases 1 y 2, extraer `app/Core` (auth, usuarios, permisos, auditoría, respaldos, secuencias, contraseña de supervisor, PDF/Excel seguros, idempotencia y cambio de contraseña obligatorio) y `app/Modules/*`, cada módulo con su ServiceProvider, rutas, menú y permisos. El detalle está en `ARQUITECTURA.md`, sección 4.

## Cómo se mide el avance

- La suite sigue en verde y cada hallazgo corregido trae una prueba que falla con el código anterior, como se hizo en esta ronda.
- `app:verificar-respaldo` en verde antes y después de cada migración.
- Para la reutilización: porcentaje de líneas en la categoría A (sin cambios). Hoy es ≈10,6 %; la meta tras las fases 1 y 2 es ≥ 30 %.
