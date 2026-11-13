# Pendientes priorizados

Orden: primero lo que bloquea operar con dinero real, luego el riesgo financiero y de seguridad, y al final la calidad y la reutilización. Cada fila remite al informe donde están la evidencia, el archivo y la línea.

## P0: acciones del dueño antes de operar (no las puede hacer el código)

| # | Acción | Por qué | Cómo |
|---|---|---|---|
| 1 | Fijar las contraseñas de las 3 cuentas sembradas | S-01: mientras sean las publicadas, **quien entre primero por la web fija la nueva y se queda con la cuenta** | `php artisan app:restablecer-password administrador@tecnisystemas.com` (y lo mismo para `tecnico@` e `invitado@`) en la consola del servidor |
| 2 | Aplicar la migración nueva | El código ya no vincula pagos por texto | `php artisan migrate --force` (no cambia filas en la base actual; es reversible) |
| 3 | Crear la tarea programada de Windows | O-01: **hoy no se ejecuta ningún respaldo automático ni la verificación semanal** | Comando exacto en `GUIA_RESPALDOS.md`, 5(a) |
| 4 | Copia fuera del disco C: | O-01: si falla el disco se pierde todo | Estrategia 3-2-1 en `GUIA_RESPALDOS.md`, 5(b) (`GOOGLE_DRIVE_BACKUP_PATH` o `D:\Respaldos`) |
| 5 | No usar `start-ngrok.bat` en producción | S-03/O-05: publica en Internet el servidor de desarrollo | Decisión del dueño; el archivo no se borró |
| 6 | Vaciar `*_DEFAULT_PASSWORD` del `.env` tras el paso 1 | S-01 | Editar `.env` (respaldarlo antes) |

## P1: riesgo financiero o de seguridad (siguiente iteración)

| ID | Pendiente | Esfuerzo |
|---|---|---|
| ~~B-01~~ | **Corregido** (`OrdenEstadoService`, prueba `OrdenCierreReglasTest`). Regla elegida para "entrega con saldo": se permite con casilla explícita y motivo, queda en `eventos`; un técnico que la registra al crear necesita la clave de admin | — |
| ~~B-03~~ | **Corregido**: una orden terminada no recibe ni pierde repuestos hasta reabrirla | — |
| M-06 (resto) | Guardar `saldo_inicial` y `efectivo_esperado` como snapshot en `cierre_cajas` | ½ día |
| M-09 / M-14 | Motivo obligatorio al anular; el motivo de un descuadre no se puede borrar | ½ día |
| M-11 | Congelar el costo histórico (`factura_items.costo_unitario`) para que la utilidad no cambie con el tiempo | 1 día |
| M-12 / M-13 | Renombrar "utilidad neta" (es flujo de caja) y rotular la factura como "documento interno, no factura electrónica DIAN" | 2 h |
| M-15, M-16, M-19 a M-24 | Duplicado sin persistir, redondeo único (`App\Support\Monto`), épsilon, venta en $0 en día cerrado, anulación de cotización, cotizaciones vencidas | 2 días |
| D-01 | Restauración atómica: mantenimiento, comprobar la versión del esquema y restaurar primero en base temporal | 1 día |
| D-07 / S-07 | Usuario MySQL propio con privilegios mínimos, en lugar de `root` | 1 h (+ prueba) |
| D-08 | Serializar el cierre con las altas del mismo día | ½ día |
| S-04 | Código de consulta aleatorio por orden para el invitado | ½ día |
| S-06 | Cerrar las sesiones al cambiar la contraseña desde Usuarios (`auth.session`) | 2 h |
| S-08 | Respaldos cifrados y reautenticación para descargar, restaurar o actualizar | 1 día |
| X-02 | Plantilla A4 de media hoja que no se rompa con muchos ítems | 1 día |
| X-04 / X-05 / X-06 | Valor de inventario y totales iguales en pantalla, Excel y PDF; las anuladas fuera de los totales | 1 día |
| F-04 | Búsqueda en el servidor en los 11 listados (hoy solo filtra la página visible) | 1 día |
| Q-02 | `.env.testing` propio para que las pruebas no dependan de contraseñas del `.env` | 1 h |
| Q-03 | Incluir `tests/Integration` en `phpunit.xml` | 15 min |
| Q-04 | Pruebas de concurrencia reales con dos conexiones MySQL en una base de pruebas | 1 día |

## P2: calidad, rendimiento y reutilización

- **Base de datos y rendimiento:**
  - D-04: los filtros `whereDate` impiden usar índices; deben pasar a rangos.
  - D-05: N+1 con `LIKE` en los accessors de caja. La rama por texto se puede eliminar porque 0 filas dependen de ella.
  - D-11 a D-26.
- **Arquitectura:**
  - ARQ-01: policies y gates en uso real, `@can` en las vistas y roles de las rutas iguales al comportamiento.
  - ARQ-02: unificar Mantenimiento y Electrónica.
  - ARQ-03 a ARQ-12: archivos gigantes, identidad fija, DDL en `AppServiceProvider`, código muerto y binarios triplicados.
- **Interfaz:**
  - F-05 a F-37.
  - Sistema de diseño (≈2,5 semanas).
  - Notificaciones con estado de lectura y prioridad (≈5 días).
- **Documentación antigua:** marcar como históricos o corregir los 8 documentos contradictorios (DOC-01 en `ARQUITECTURA.md`). La referencia vigente es esta carpeta.
- **Backend:** B-04 a B-11, B-13 a B-24.
- **Seguridad:** S-09 a S-16.
- **Despliegue:** O-08 a O-20.

## Riesgos aceptados de forma explícita en esta versión

- **El pagado ya no se edita desde la factura (M-05).** Si el usuario solía corregir pagos así, ahora debe anular el abono o registrar uno nuevo en Caja. Es intencional: la otra forma reescribía arqueos pasados.
- **Período monótono (M-06).** Nada se registra ni se anula con fecha anterior al último cierre. Para corregir un día pasado hay que eliminar los cierres posteriores (admin con clave).
- **Idempotencia (M-07).** Reenviar un formulario idéntico sin modificar ningún campo, dentro de 10 minutos, se rechaza como duplicado. Si hace falta repetir una operación igual, basta con recargar la página.
