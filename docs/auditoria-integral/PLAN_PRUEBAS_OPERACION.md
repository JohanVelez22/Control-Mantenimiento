# Plan de pruebas antes de operar con dinero real

Estado al 2026-11-16: **listo para pruebas**, con las condiciones de la sección 1.
- **Código:** las correcciones de la auditoría están hechas, salvo lo listado en la sección 4.
- **Suite:** 780 pruebas en verde en SQLite, y la suite completa también en MySQL 8.4 (base temporal).
- **Navegador:** cada bloque se revisó en una copia aislada con SQLite.

Este plan reemplaza a la operación real durante la primera semana: se trabaja en paralelo con el método actual y se compara.

## 1. Antes de empezar (obligatorio, solo lo puede hacer el dueño)

La lista completa, con el porqué de cada paso, está en `PENDIENTES.md` (P0). En orden:

| # | Paso | Comando o lugar | Hecho |
|---|---|---|---|
| 1 | Contraseña propia para las 3 cuentas | `php artisan app:restablecer-password <correo>` | ☐ |
| 2 | Respaldo verificado | `php artisan app:backup-db` y `php artisan app:verificar-respaldo` (debe terminar en ✅) | ☐ |
| 3 | Migraciones | `php artisan app:verificar-migraciones` y `php artisan migrate --force` | ☐ |
| 4 | Compilar la interfaz | `npm run build` | ☐ |
| 5 | Retirar los datos de demostración | `php artisan app:clean-data --produccion`; luego datos reales de la empresa en Configuración | ☐ |
| 6 | Clave de cifrado de respaldos, escrita en papel y guardada aparte | `BACKUP_PASSWORD` en `.env` | ☐ |
| 7 | Tarea programada de respaldos | `GUIA_RESPALDOS.md`, 5(a) | ☐ |
| 8 | Copia fuera del disco C: | `GOOGLE_DRIVE_BACKUP_PATH` o disco externo | ☐ |
| 9 | Vaciar `*_DEFAULT_PASSWORD` y sacar `.env.backup` del proyecto (respaldar el `.env` antes) | Editor / Explorador | ☐ |
| 10 | No usar `start-ngrok.bat` | — | ☐ |
| 11 | Revisión final | `php artisan app:preflight` sin bloqueantes | ☐ |

Recomendado, no bloqueante: usuario MySQL propio en lugar de `root` (D-07).

## 2. Recorrido de prueba (un día, con datos de prueba)

Cada flujo indica qué revisar. Si algo no cuadra, anotarlo con fecha, pantalla y número de documento.

### Órdenes de servicio (Mantenimiento y Electrónica)
1. Crear una orden **Pendiente**, imprimir el **comprobante de recepción** (botón "Recepción") y verificar que trae el código de consulta. Agregar un repuesto y verificar que el stock baja, el costo sube y el repuesto aparece en el historial de la orden.
2. Registrar un abono parcial y verificar el saldo en la orden y el ingreso en Caja.
3. Intentar terminarla con saldo. Debe pedir **"Entregar con saldo pendiente"** y un motivo.
4. Pagar el resto y terminarla con fecha de salida. Debe aceptarla sin pedir nada más.
5. Con la orden terminada, intentar agregar un repuesto. Debe pedir que se reabra.
6. Reabrirla (estado Pendiente). Debe pedir el motivo y borrar la fecha de salida.
7. Imprimir la factura A4 y la POS. Revisar que salgan los abonos, que el saldo sea correcto y que aparezca la leyenda "No es factura electrónica".
8. Anular una orden. Debe pedir el motivo, devolver el stock y retirar los abonos de la caja.

### Ventas, compras y cotizaciones
1. Vender 2 productos con pago parcial y verificar factura, stock y caja.
2. Escribir montos como `1.500`, `1.500,50` y `$ 25.000`. Deben interpretarse como 1500, 1500,5 y 25000. Un monto como `0.001` debe rechazarse.
3. Crear una cotización y convertirla. Una cotización vencida no se debe poder convertir.
4. Imprimir una factura con más de 10 ítems. Debe salir en hoja completa, con las firmas al final.

### Caja y cierre
1. Registrar un ingreso y un egreso manuales.
2. "Duplicar" un movimiento. Debe abrir el formulario sin guardar nada.
3. Hacer el cierre del día con el efectivo contado. Si hay diferencia, debe pedir el motivo.
4. Intentar registrar o anular algo con la fecha ya cerrada. Debe impedirlo.
5. Comparar el cierre con el conteo físico.

### Reportes
1. Reporte de Mantenimientos con más de 10 órdenes. El total de pantalla, PDF y Excel debe ser el mismo, y las anuladas deben aparecer aparte.
2. Reporte de inventario. El valor debe ser cantidad × precio, igual en pantalla, PDF y Excel.
3. Buscar en un listado y presionar Enter. Debe encontrar registros de otras páginas.

### Seguridad
1. Entrar como técnico y editar una orden. Debe pedir la clave de administrador.
2. Entrar como invitado y consultar una orden con la cédula y el **código de consulta** del comprobante. Con el número de orden ya no debe encontrarla.
3. Cambiar la contraseña de un usuario desde Usuarios. Sus otras sesiones deben cerrarse.
4. Como administrador, descargar o restaurar un respaldo: debe pedir la contraseña. Durante la restauración, otro equipo debe ver "Mantenimiento en curso".

## 3. Semana en paralelo (criterio para pasar a producción)

- Registrar todo en el sistema **y** en el método actual.
- Comparar cada día el cierre de caja del sistema con el conteo físico y con el registro manual.
- Hacer `app:verificar-respaldo` al menos una vez en la semana y una restauración de prueba en otro equipo (`GUIA_RESPALDOS.md`, 5(c)).
- **Criterio:** 5 días hábiles seguidos sin diferencias que no se puedan explicar. Si aparece una, se corrige y se reinicia el conteo.

## 4. Lo que todavía no está resuelto (conocido)

Ver `PENDIENTES.md`, sección "Lo que queda abierto". En resumen:
- configuración del servidor (usuario MySQL propio, OPcache, `composer install --no-dev`);
- fuentes alojadas en el servidor (falta autorizar la descarga);
- menú lateral en tabletas táctiles;
- mejoras visuales del sistema de diseño;
- impresión en impresora térmica real (probar en el recorrido).

Ninguno afecta el dinero ni la integridad de los datos.
