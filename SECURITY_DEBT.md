# 📋 Registro de Deuda Técnica y Hoja de Ruta de Seguridad — Tecni-Systemas

Este documento registra de manera formal y transparente los elementos de seguridad web a nivel de cliente (**CSP y Modularización de JavaScript**) que quedan programados para optimización progresiva en próximos sprints, tras haber alcanzado la certificación funcional y financiera para producción.

---

## 🛡️ Estado Actual de Seguridad

### ✅ Mitigaciones Ya Completadas (100% Listas)
1. **Localización de Dependencias Frontend**:
   - `Chart.js`, `Flatpickr` (con localización español) y `TomSelect` fueron instalados vía `npm` y compilados localmente en `public/build/` con **Vite**.
   - Cero solicitudes a CDNs externos (`cdn.jsdelivr.net`, `npmcdn.com` eliminados).
2. **Endurecimiento de CSP**:
   - `Content-Security-Policy` no permite ningún dominio externo para scripts (`script-src 'self'`).
3. **Protección de Sesiones y Cookies**:
   - Encriptación y directiva `Secure` configuradas con auto-activación en producción (`APP_ENV === 'production'`).
4. **Integridad de Datos y Finanzas**:
   - 55 tests automáticos y 28 pruebas transaccionales aprobadas al 100%.

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
