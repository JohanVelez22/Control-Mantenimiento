# 📖 Manual de Operaciones y Mantenimiento del Sistema — Tecni-Systemas

Este manual describe los procedimientos operativos para administradores, operadores técnicos y personal de soporte del sistema **Tecni-Systemas**.

---

## 1. Gestión de Respaldos y Recuperación de Desastres

El sistema cuenta con un motor dual de copias de seguridad: interfaz gráfica en `/configuracion` (pestaña Respaldos) y comandos de consola Artisan para automatización en servidor.

### 1.1 Tipos de Respaldo Soportados
1. **Solo Base de Datos (`.sql`):** Exportación completa de todas las tablas, esquemas, índices y registros relacionales.
2. **Base de Datos + Archivos Multimedia (`.zip`):** Base de datos MySQL más la totalidad de imágenes de repuestos, logos de empresa y fotos de perfil almacenadas en `storage/app/public`.
3. **Respaldo Integral (`.zip`):** Base de datos, multimedia y snapshot del código fuente versionado para clonación completa del servidor.

### 1.2 Comandos de Consola para Automatización
```bash
# Generar respaldo de base de datos MySQL
php artisan app:backup-db

# Respaldo de BD + Archivos multimedia
php artisan app:backup-db --files

# Respaldo integral (BD + Archivos + Código fuente)
php artisan app:backup-db --all
```

### 1.3 Procedimiento de Restauración Segura
- **Ubicación:** Menú Configuración de Empresa → Pestaña Respaldos → Tabla de Copias Existentes → Botón **Restaurar**.
- **Mecanismos de Protección Activos:**
  - **Blindaje contra Zip Slip:** El descompresor valida individualmente cada entrada del archivo comprimido, abortando la operación si detecta secuencias de escape de directorio (`..`, `/`, `\`).
  - **Trazabilidad Preservada:** Antes de ejecutar la restauración del script SQL, el sistema almacena en memoria los eventos de auditoría previos (`eventos`) y los reintegra automáticamente tras la carga de datos, impidiendo que una restauración borre el historial de operaciones del usuario.
  - **Filtro de Extensiones Multimedia:** Durante la restauración de archivos públicos, se descartan ejecutables o scripts maliciosos, permitiendo exclusivamente extensiones legítimas (`jpg`, `png`, `webp`, `pdf`, `txt`).

---

## 2. Operación de Caja Chica y Cierres Diarios

### 2.1 Flujo Operativo Diario
1. **Registro de Movimientos:** Todos los cobros por órdenes de mantenimiento, reparaciones electrónicas y ventas de mostrador generan un movimiento en la tabla `movimiento_cajas`.
2. **Métodos de Pago:** Se diferencian contablemente los ingresos en **Efectivo** de los realizados mediante **Consignación/Transferencia**.

### 2.2 Arqueo Físico y Conciliación
Al finalizar la jornada laboral:
1. Acceder al módulo **Cierre de Caja** (`/cierre`).
2. El sistema calcula en tiempo real el **Efectivo Esperado** sumando el saldo de apertura acumulado del día anterior más el flujo neto de efectivo del día.
3. El cajero ingresa el **Efectivo Físico Contado** en gaveta.
4. Si existe diferencia (faltante o sobrante), el sistema solicita obligatoriamente el motivo para registro contable.
5. Al pulsar **Cerrar Caja**, el registro queda sellado de forma **inmutable**.

### 2.3 Regla de Inmutabilidad
- No es posible crear, modificar o anular abonos ni movimientos de caja cuya fecha pertenezca a un día con cierre formalizado.
- Cualquier ajuste posterior debe realizarse como un nuevo movimiento en la fecha abierta actual con concepto justificado.

---

## 3. Control de Inventario y Precios Diferenciales

### 3.1 Precios al Público vs. Precios para Técnicos
- Cada repuesto en stock define un costo base, un porcentaje de margen al público y un porcentaje de margen especial para técnicos colaboradores.
- Si una orden de servicio o cliente tiene asignado el tipo "Técnico", el cotizador y la orden aplican automáticamente el precio preferencial parametrizado.

### 3.2 Bajas de Stock y Reversión
- **Dar de Baja:** Cuando un repuesto sufre avería, daño en taller o consumo interno no facturable, se procesa desde el detalle del producto (`stocks.dar-de-baja`), descontando existencias y registrando el motivo en auditoría.
- **Reversión:** Si el producto se recupera o fue dado de baja por error, un administrador puede pulsar "Revertir Baja", reintegrando el inventario de manera atómica con bloqueo pesimista (`lockForUpdate`).

---

## 4. Gestión de Usuarios y Accesos

| Rol | Permisos Principales | Restricciones |
|---|---|---|
| **Administrador** | Control total, configuración de empresa, cierres, respaldos, anulación de usuarios y parámetros contables. | Ninguna dentro del dominio del taller. |
| **Técnico** | Creación y actualización de órdenes, clientes, equipos, cotizaciones y ventas. | No puede acceder a Respaldos, Configuración, Auditoría ni anular registros sin autorización. |
| **Invitado** | Consulta de estado de orden propia vía portal de clientes y visualización de factura. | Bloqueado de cualquier mutación, inventario, caja o datos de terceros. |

### 4.1 Políticas de Seguridad y Contraseñas
- Al modificar la contraseña de cualquier cuenta, el sistema invalida inmediatamente todas las sesiones activas en otros navegadores rotando el `remember_token` criptográfico.
- La protección contra ataques de fuerza bruta penaliza progresivamente las IPs con intentos erróneos reiterados (1 min → 3 min → 5 min → 15 min → 30 min).
