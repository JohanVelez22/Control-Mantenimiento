# 📋 Registro de Deuda Técnica y Hoja de Ruta de Seguridad — Tecni-Systemas

Este documento registra de manera formal y transparente los elementos de seguridad web a nivel de cliente (**CSP y Modularización de JavaScript**) que quedan programados para optimización progresiva en próximos sprints, tras haber alcanzado la certificación funcional y financiera para producción.

---

## 🛡️ Estado Actual de Seguridad

### ✅ Mitigaciones Ya Completadas (100% Listas)
1. **Seguridad y Cierre de Backdoors**:
   - Autenticación estrictamente contra hashes en base de datos; eliminación de bypass de contraseñas por defecto y de creación dinámica de cuentas en login.
   - Exclusión estricta de archivos `.env` y credenciales de los respaldos ZIP descargables.
   - Autorización sensible (`AnulacionService`) para la eliminación de abonos financieros y transacciones.
2. **Protección de Sesiones y Cookies**:
   - Encriptación y directiva `Secure` configuradas con auto-activación en producción (`APP_ENV === 'production'`).
   - Serialización segura en formato JSON para prevenir ataques de deserialización.
3. **Integridad de Datos y Finanzas**:
   - Transacciones atómicas (`DB::beginTransaction`), bloqueos pesimistas (`lockForUpdate`) en inventario.
   - 122 tests automáticos con casi 500 aserciones aprobadas al 100%.

---

## 📌 Deuda Técnica Documentada (A Mediano Plazo)

### 1. Directiva `'unsafe-inline'` y `'unsafe-eval'` en Content-Security-Policy
* **Ubicación**: [`app/Http/Middleware/SecurityHeaders.php`](file:///c:/ServBay/www/tecni-systemas/app/Http/Middleware/SecurityHeaders.php)
* **Motivo actual**: Plantillas Blade como `dashboard.blade.php` y `layouts/app.blade.php` contienen bloques interactivos `<script>` embebidos con HTML para modales, eventos y gráficos.
* **Riesgo**: Bajo en redes y sistemas de gestión interna de taller; medio en auditorías externas de cumplimiento estricto (OWASP ASVS L3).
* **Plan de Acción**:
  - Migrar progresivamente las funciones de `app.blade.php` y `dashboard.blade.php` a archivos independientes dentro de `resources/js/modules/`.
  - Reemplazar el paso de rutas y variables PHP directas por atributos `data-*` en elementos del DOM o metadatos JSON.
  - Una vez completado, retirar `'unsafe-inline'` y `'unsafe-eval'` de `SecurityHeaders.php`.

### 2. Migración de Librerías CDN al Bundle Local de Vite
* **Ubicación**: [`resources/views/layouts/app.blade.php`](file:///c:/ServBay/www/tecni-systemas/resources/views/layouts/app.blade.php)
* **Motivo actual**: Flatpickr y TomSelect se cargan desde `cdn.jsdelivr.net` y se encuentran permitidos en la CSP.
* **Plan de Acción**: Empaquetar completamente estas librerías en el pipeline de Vite para prescindir de orígenes externos y permitir una CSP estricta `script-src 'self'`.
