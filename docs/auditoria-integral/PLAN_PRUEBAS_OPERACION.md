# Plan de pruebas antes de operar con dinero real

Estado al 2026-11-13: **listo para pruebas**, con condiciones.
- **Código:** las correcciones de la auditoría están hechas, salvo lo listado en la sección 4.
- **Suite:** 708 pruebas en verde, unitarias, de funcionalidades e integración.
- **Navegador:** cada bloque se revisó en una copia aislada con SQLite.

Este plan reemplaza a la operación real durante la primera semana: se trabaja en paralelo con el método actual y se compara.

## 1. Antes de empezar (obligatorio, solo lo puede hacer el dueño)

| # | Paso | Comando o lugar | Hecho |
|---|---|---|---|
| 1 | Contraseña propia para las 3 cuentas | `php artisan app:restablecer-password <correo>` | ☐ |
| 2 | Respaldo y migración | `php artisan app:backup-db` y luego `php artisan migrate --force` | ☐ |
| 3 | Verificar ese respaldo | `php artisan app:verificar-respaldo` (debe terminar en ✅) | ☐ |
| 4 | Tarea programada de respaldos | `GUIA_RESPALDOS.md`, 5(a) | ☐ |
| 5 | Copia fuera del disco C: | `GOOGLE_DRIVE_BACKUP_PATH` en `.env`, o un disco externo | ☐ |
| 6 | Vaciar `*_DEFAULT_PASSWORD` en `.env` (respaldar el `.env` antes) | Editor | ☐ |
| 7 | No usar `start-ngrok.bat` | — | ☐ |
| 8 | Revisión final | `php artisan app:preflight` sin bloqueantes | ☐ |

Recomendado, no bloqueante: crear un usuario MySQL propio en lugar de `root` (D-07).

## 2. Recorrido de prueba (un día, con datos de prueba)

Cada flujo indica qué revisar. Si algo no cuadra, anotarlo con fecha, pantalla y número de documento.

### Órdenes de servicio (Mantenimiento y Electrónica)
1. Crear una orden **Pendiente**, agregar un repuesto y verificar que el stock baja y el costo sube.
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
2. Entrar como invitado y consultar una orden con cédula y número de orden. No debe ver montos de otras órdenes.
3. Cambiar la contraseña de un usuario desde Usuarios. Sus otras sesiones deben cerrarse.

## 3. Semana en paralelo (criterio para pasar a producción)

- Registrar todo en el sistema **y** en el método actual.
- Comparar cada día el cierre de caja del sistema con el conteo físico y con el registro manual.
- Hacer `app:verificar-respaldo` al menos una vez en la semana y una restauración de prueba en otro equipo (`GUIA_RESPALDOS.md`, 5(c)).
- **Criterio:** 5 días hábiles seguidos sin diferencias que no se puedan explicar. Si aparece una, se corrige y se reinicia el conteo.

## 4. Lo que todavía no está resuelto (conocido)

| Tema | Efecto en la operación | Qué hacer mientras tanto |
|---|---|---|
| M-06 (resto) y M-11: el saldo inicial del cierre y el costo histórico no se guardan como foto. Requieren columnas nuevas | Si se cambia el precio de compra de un producto, la utilidad de ventas pasadas cambia en los reportes. El cierre ya está protegido contra cambios retroactivos | Tomar la caja como fuente de verdad del dinero |
| D-01 (resto): la restauración desde la web no activa el modo mantenimiento | Si alguien trabaja mientras se restaura, su operación se pierde | Restaurar solo fuera del horario de atención |
| S-04: la consulta del invitado usa cédula + número de orden, no un código aleatorio | Quien conozca ambos datos ve el estado de esa orden | Aceptable para un local; no publicar los números de orden |
| S-08: los respaldos no están cifrados | Quien tenga acceso al disco o a la carpeta de Drive puede leer la base | Carpeta de Drive privada; equipo con contraseña |
| D-07: conexión como `root` | Un fallo de seguridad tendría más alcance | Crear un usuario propio (`GUIA_INSTALACION.md`, paso 4) |
| D-04: filtros por fecha que no usan índices | Con muchos años de datos, los reportes se vuelven más lentos | Sin efecto con el volumen actual |
| No probado | Impresión en impresora térmica real; concurrencia de la app completa en MySQL (solo se probaron los bloqueos del cierre); restaurar desde la web de punta a punta | Probarlo en el recorrido del punto 2 |
