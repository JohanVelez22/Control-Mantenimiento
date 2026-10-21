# 📜 Registro de Cambios (Changelog) — Tecni-Systemas

Todas las modificaciones notables realizadas en este proyecto durante la ejecución del **Prompt Maestro de Auditoría, Hardening, Refactorización y Evolución Segura** están documentadas en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y este proyecto se adhiere al versionado semántico.

---

## [1.0.0] - 2026-10-21 (Certificación Final de Auditoría y Producción)

### Fase 0 — Diagnóstico, Baseline y Matriz de Hallazgos
- Ejecución de auditoría en modo solo lectura sobre el 100% de la base de código (75 archivos PHP, 106 vistas Blade, 66 archivos de base de datos).
- Establecimiento de línea base automatizada con 501 pruebas aprobadas (1,507 aserciones).
- Generación de `AUDITORIA_EXHAUSTIVA.md` con clasificación de hallazgos por severidad (P0, P1, P2, P3).

### Fase 1 — P0 Seguridad Crítica (`commit effecb8`)
- **Autenticación Blindada:** Eliminación de bypass de contraseñas por defecto en `.env`. La autenticación ahora valida de manera estricta contra hashes en base de datos.
- **Timing Attack Mitigation:** Inclusión de hash señuelo Bcrypt de 60 caracteres exactos (`self::DUMMY_HASH`) en `AuthController` para evitar disparidades de latencia.
- **Exclusión de Fuga de Credenciales:** Sanitización de empaquetados de respaldo en `BackupDatabase` y `BackupController` para excluir `.env`, logs y claves privadas.
- **Protección de Dusk en Producción:** Bloqueo y verificación de rutas `_dusk` ante cualquier entorno público.

### Fase 2 — Integridad Financiera y Datos (`commit 8e5b6e9`)
- **Cierres de Caja Inmutables:** Protección estricta contra la alteración de movimientos o abonos pertenecientes a fechas con caja cerrada.
- **Conciliación Física de Efectivo:** Integración del cálculo de efectivo real contado vs. esperado, con registro de motivos de diferencia.
- **Saneamiento de Abonos y Pagos:** Reconciliación de totales en facturas y cálculo monetario con 2 decimales para evitar desfasajes por flotantes.

### Fase 3 — Concurrencia y Operaciones Transaccionales (`commit f77ed7d`)
- **Numeración Atómica de Documentos:** Creación de la tabla `secuencias_documento` y migración de la generación de IDs de comprobantes para evitar colisiones concurrentes (`MAX+1`).
- **Bloqueos Pesimistas en Stock:** Aplicación de `lockForUpdate()` en transacciones de venta, deducción y reversión de existencias.
- **Idempotencia en Conversión:** Blindaje en la conversión de cotizaciones a facturas evitando dobles clics o envíos duplicados.

### Fase 4 — Optimización de Base de Datos e Índices (`commit 66ff51a`)
- **Optimización de Índices:** Creación de índices compuestos para consultas de alto volumen en `eventos`, `cotizaciones` y `facturas`.
- **Eliminación de Redundancia:** Supresión de pares inversos y prefijos duplicados en índices de base de datos.
- **Sustitución de `whereDate`:** Reemplazo de funciones sobre columnas indexadas por comparaciones de rango directo para permitir el uso efectivo de índices B-Tree.

### Fase 5 — Frontend, Accesibilidad y Mantenibilidad (`commit 672b7c5`)
- **Corrección de Sintaxis en Glass.css:** Normalización de declaraciones huérfanas en `@media print` preservando la identidad visual y estética de la aplicación.
- **Accesibilidad ARIA en Modales:** Adición de atributos `role="dialog"`, `aria-modal="true"`, `aria-labelledby` y captura/restauración de foco en ventanas emergentes y lightboxes.
- **Sanitización del DOM:** Reemplazo de inserciones inseguras de `innerHTML` por manipulación nativa con `replaceChildren()` y migración de atributos `onclick` a delegación de eventos vía `data-lightbox-*`.

### Fase 6 — Testing Avanzado y Hardening (`commit 2fb8324`)
- **Respuesta RESTful en Roles:** Configuración del middleware `CheckRole` para retornar HTTP 403 Forbidden estructurado en peticiones API/JSON.
- **Rotación de `remember_token`:** Invalidación inmediata de sesiones activas en otros dispositivos cuando un usuario actualiza su contraseña.
- **Blindaje contra Zip Slip:** Validación exhaustiva de rutas de archivos en paquetes de restauración para impedir ataques de escape de directorio.
- **Suite de Hardening:** Creación de `SecurityHardeningAuditTest.php` expandiendo la suite a 554 pruebas aprobadas (1,784 aserciones).

### Fase 7 — Documentación, Preparación para Producción y Entrega
- **Sincronización de Entorno:** Actualización exhaustiva de `.env.example` con parámetros de producción y zonas horarias (`America/Bogota`).
- **Manuales Operativos:** Redacción de `docs/MANUAL_OPERACIONES_Y_MANTENIMIENTO.md` y `docs/ARQUITECTURA_Y_MODELO_DATOS.md`.
- **Checklist de Producción:** Actualización de `DEPLOY_CHECKLIST.md` con guías paso a paso para entornos Linux y Windows.
- **Actualización de Seguridad:** Renovación de `SECURITY_DEBT.md` documentando la resolución completa de hallazgos críticos.
