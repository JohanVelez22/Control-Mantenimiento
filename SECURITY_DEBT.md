# 📋 Registro de Certificación de Seguridad y Deuda Técnica — Tecni-Systemas

Este documento certifica el estado de seguridad, mitigación de vulnerabilidades y hardening arquitectónico alcanzado tras la ejecución del plan integral de auditoría en 7 fases.

---

## 🛡️ Estado de Seguridad del Proyecto (Fases 1 a 6 Completadas)

### Métrica de Verificación Automatizada
- **Tests Automatizados:** 554 pruebas aprobadas (100% verde).
- **Aserciones de Verificación:** 1,784 aserciones independientes.
- **Análisis Estático (Laravel Pint):** Sin violaciones de estándares PSR-12 / Laravel.
- **Compilación de Activos (Vite):** Producción verificada sin errores de manifiesto.

---

### ✅ Matriz de Mitigaciones Implementadas y Verificadas

| ID | Área | Diagnóstico Original | Mitigación Implementada | Evidencia / Test |
|---|---|---|---|---|
| **P0-1** | Autenticación | Riesgo de bypass por contraseñas por defecto en `.env`. | Autenticación estricta contra hashes Bcrypt en BD. Dummy hash de 60 chars para mitigar timing attacks. | `LoginFlexibilityTest`, `LoginLockoutTest` |
| **P0-2** | Fuga de Datos | Inclusión involuntaria de `.env` o credenciales en backups ZIP. | Exclusión estricta de `.env`, credenciales y logs en `BackupDatabase` y `BackupController`. | `BackupManagementTest` |
| **P0-3** | Entorno | Rutas de Laravel Dusk potencialmente expuestas en producción. | Guarda estricta de entorno en `AppServiceProvider` / `CheckRole` y denegación en web server. | `RouteListTest`, `AUDITORIA_EXHAUSTIVA.md` |
| **P0-4** | Backups | Vulnerabilidad potencial a Path Traversal y Zip Slip en restauración. | Validación de nombres de archivo mediante regex estricto y escaneo previo de cabeceras ZIP rechazando rutas relativas (`..`). | `SecurityHardeningAuditTest::test_backup_zip_slip_is_blocked_safely` |
| **P1-1** | Finanzas | Mutabilidad arbitraria en cajas cerradas y recálculo de efectivo. | Cierres de caja inmutables, saldo anterior acumulado y validación de abonos contra fecha de cierre. | `CierreCajaConciliacionTest`, `FinancialIntegrityTest` |
| **P1-2** | Concurrencia | Colisiones en numeración de documentos (`MAX+1`). | Implementación de tabla `secuencias_documento` con incremento atómico bajo transacción. | `DocumentSequenceTest`, `CotizacionConversionConcurrencyTest` |
| **P1-3** | Inventario | Condiciones de carrera en deducción de existencias. | Deducción transaccional con `lockForUpdate()`, validación de stock disponible y registro de bajas auditables. | `StockConcurrencyTest`, `StockControllerTest` |
| **P1-4** | Base de Datos | Consultas con `whereDate()` que invalidaban índices y redundancia. | Reemplazo por comparaciones directas de fecha, creación de índices compuestos y eliminación de pares inversos. | `DatabaseOptimizationTest` |
| **P1-5** | Frontend | Sintaxis CSS huérfana en `glass.css`, accesibilidad deficiente en modales y uso de `innerHTML`. | Corrección de sintaxis en `@media print`, roles y atributos ARIA (`role="dialog"`, `aria-modal="true"`), y sanitización con `replaceChildren()`. | `FrontendAccessibilityAndSecurityTest` |
| **P1-6** | Sesiones | `remember_token` estático al cambiar contraseña y subprocesos host en testing. | Rotación automática de `remember_token` con `Str::random(60)` y bypass seguro sin spawns de shell en testing. | `SecurityHardeningAuditTest::test_user_password_change_rotates_remember_token` |

---

## 📌 Hoja de Ruta de Mantenimiento y Deuda Técnica Residual

### 1. Política de Seguridad de Contenido (CSP)
- **Ubicación:** `app/Http/Middleware/SecurityHeaders.php`
- **Estado Actual:** CSP activa con `script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net`.
- **Justificación de Negocio:** Permite la renderización dinámica de gráficos en el Dashboard, selectores dependientes (TomSelect) y calendarios (Flatpickr) en la interfaz actual de taller sin alterar la experiencia de usuario.
- **Plan de Evolución:**
  1. Empaquetar Flatpickr y TomSelect dentro del bundle local de Vite (`resources/js/app.js`).
  2. Migrar los bloques `<script>` inline de vistas Blade a módulos JS empaquetados con paso de configuración vía `data-*` attributes.
  3. Restringir la directiva a `script-src 'self'` sin `'unsafe-inline'`.

### 2. Rotación Periódica de Claves de Cifrado
- **Plan:** Establecer un procedimiento de rotación de `APP_KEY` anual o semestral utilizando el comando `php artisan key:generate` en ventanas de mantenimiento programadas.
