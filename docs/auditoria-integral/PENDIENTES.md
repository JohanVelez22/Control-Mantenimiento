# Pendientes

Estado al 2026-11-16, después de la quinta ronda (verificación de punta a punta en el navegador; ver `CAMBIOS_REALIZADOS.md`). Cada fila remite al informe donde están la evidencia, el archivo y la línea. El detalle de lo corregido está en `CAMBIOS_REALIZADOS.md`.

## P0: acciones del dueño antes de operar (no las puede hacer el código)

| # | Acción | Por qué | Cómo |
|---|---|---|---|
| 1 | Fijar las contraseñas de las 3 cuentas sembradas | S-01: mientras sean las publicadas, quien entre primero fija la nueva | `php artisan app:restablecer-password <correo>` para `administrador@`, `tecnico@` e `invitado@` |
| 2 | Respaldo verificado y migraciones | Hay 6 migraciones nuevas (vínculos antiguos, foto del cierre, costo histórico, código de consulta, stock mínimo, precisión, llaves, índices y restricciones). El sistema funciona igual antes de aplicarlas | `php artisan app:backup-db`, `php artisan app:verificar-respaldo`, `php artisan app:verificar-migraciones` y `php artisan migrate --force` |
| 3 | Compilar la interfaz | Esta ronda cambió estilos y clases | `npm run build` en la carpeta del proyecto |
| 4 | Retirar los datos de demostración | D-26: la base real tiene los datos de prueba (5 por tabla) | `php artisan app:clean-data --produccion` (hace y verifica un respaldo antes; conserva usuarios, configuración e historial). Después, registrar los datos reales de la empresa en Configuración |
| 5 | Crear la tarea programada de Windows | O-01: sin ella no hay respaldos automáticos ni verificación semanal. `app:preflight` lo detecta (latido del programador) | `GUIA_RESPALDOS.md`, 5(a) |
| 6 | Copia fuera del disco C: | Si falla el disco se pierde todo | `GOOGLE_DRIVE_BACKUP_PATH` o `D:\Respaldos` (`GUIA_RESPALDOS.md`, 5(b)) |
| 7 | Clave de cifrado de respaldos | S-08: con `BACKUP_PASSWORD` los respaldos se cifran (AES-256). **Sin la clave no se pueden restaurar**: escribirla en papel y guardarla aparte | `.env` → `BACKUP_PASSWORD=...` |
| 8 | Vaciar `*_DEFAULT_PASSWORD` del `.env` tras el paso 1 | S-01 | Editar `.env` (respaldarlo antes) |
| 9 | Mover `.env.backup` fuera del proyecto | S-14: contiene secretos y `APP_DEBUG=true` | Explorador de archivos |
| 10 | Usuario MySQL propio en lugar de `root` | D-07 / S-07 / O-10 | `GUIA_INSTALACION.md`, paso 4 |
| 11 | No usar `start-ngrok.bat` en producción | S-03 / O-05 | Decisión del dueño |
| 12 | Revisión final | Debe terminar sin bloqueantes | `php artisan app:preflight` |
| 13 | Registrar el efectivo inicial de la caja | Desde esta ronda ninguna salida en efectivo puede dejar la caja en negativo: el primer día, antes de pagar algo en efectivo, se registra el dinero que hay en el cajón | Caja → Nuevo movimiento → Ingreso, concepto "Apertura de caja", efectivo |
| 14 | Rutas de node y composer para "Actualizar desde GitHub" | El servidor web no suele tenerlas en el PATH; sin ellas la actualización avisa que falta compilar | `.env` → `NPM_BIN=C:\ServBay\packages\node\current\npm.cmd` y `COMPOSER_BIN=C:\ServBay\bin\composer.cmd` |

## Resueltos en el código

Todos estos hallazgos quedaron corregidos y con prueba:
- **Backend:** B-01 a B-24.
- **Finanzas:** M-01 a M-24.
- **Base de datos:** D-01 a D-06, D-08 a D-13 y D-15 a D-26.
- **Seguridad:** S-02, S-04 a S-06, S-08 a S-13, S-15 y S-16.
- **Documentos:** X-01 a X-20, X-22 y X-23.
- **Operación:** O-03, O-04, O-07, O-11, O-13, O-14, O-17 y O-19.
- **Pruebas:** Q-01 a Q-04.
- **Arquitectura:** ARQ-01, ARQ-03 y ARQ-06 a ARQ-12.
- **Interfaz:** F-01 a F-13, F-15, F-16, F-19, F-21 a F-23, F-25 a F-28, F-35 y F-37, más parte de F-18 ("Cancelar" siempre neutro).

## Lo que queda abierto (con su motivo)

| ID | Qué falta | Motivo | Efecto en la operación |
|---|---|---|---|
| D-07, S-14 (Dusk), O-08, O-15, O-16, O-20 | Usuario MySQL propio, `composer install --no-dev`, OPcache y ajustes de PHP y del servidor, versión LTS de Node | Configuración del servidor (panel de ServBay o el dueño), no código | Ninguno funcional; el preflight los avisa |
| F-24 | Fuentes alojadas en el propio servidor | Hay que descargar los archivos de fuente (Inter, Michroma, Orbitron); queda pendiente la autorización. Mientras tanto, ya no bloquean la primera pintura | Sin internet, los títulos usan la fuente de respaldo |
| F-34 | Menú lateral en tabletas táctiles de 1024 px o más | Requiere probar en un equipo táctil real | En tabletas el menú se expande al tocar de forma irregular |
| F-14, F-17, F-18 (resto), F-20, F-29 a F-33, F-36, X-21, ARQ-04, ARQ-05 | Sistema de diseño (ver detalle abajo) | Mejoras visuales y de mantenimiento, sin errores de funcionamiento. Conviene hacerlas después de la semana en paralelo, con revisión visual | Ninguno funcional |
| ARQ-02 | Unir Mantenimiento y Electrónica en una sola tabla | Requiere migrar datos reales de dos tablas a una: alto riesgo sin beneficio para operar. Ya comparten reglas, validaciones, abonos, repuestos y exportación | Ninguno |
| D-14 (booleanos) | Quitar índices de baja cardinalidad | Se decide tras un mes de uso real (`sys.schema_unused_indexes`) | Ninguno |
| — | Impresión en impresora térmica real | Necesita la impresora | Probarlo en el recorrido del plan de pruebas |

Detalle del sistema de diseño pendiente:
- alturas de controles;
- tokens de color;
- iconos SVG en lugar de emoji;
- tarjetas en móvil;
- títulos;
- CSS por página;
- nombres de marca.

## Riesgos aceptados de forma explícita

- **Efectivo nunca negativo (quinta ronda).** Una salida en efectivo mayor que el efectivo registrado se rechaza. Si el dinero salió de una plata que no estaba registrada, primero se registra ese ingreso o se usa Consignación.
- **Venta por debajo del costo.** Se permite con la advertencia de margen negativo en pantalla. El precio de lista del producto sí debe ser igual o mayor que el costo.

- **Reglas de cierre de órdenes (B-01).** Terminar una orden con saldo exige "entrega con saldo" y motivo.
- **Motivo al anular (M-09).** Anular pide un motivo de al menos 5 caracteres.
- **Duplicar en caja (M-15).** Abre el formulario precargado; hay que guardar.
- **Cotizaciones vencidas (M-22).** No se convierten; hay que editarlas.
- **El pagado no se edita desde la factura (M-05).** Los pagos se registran como abonos en Caja.
- **Período monótono (M-06).** Nada se registra ni se anula con fecha anterior al último cierre.
- **Idempotencia (M-07).** Un reenvío idéntico dentro de 10 minutos se rechaza. Para repetir la operación, recargar la página.
- **Consulta del invitado (S-04).** Tras migrar, el cliente consulta con su documento y el **código de consulta** del comprobante de recepción, no con el número de orden.
- **Respaldos con contraseña (S-08).** Descargar, restaurar o borrar respaldos y actualizar el sistema pide la contraseña de la sesión.
- **Permisos (ARQ-01).** Configuración, auditoría y respaldos son solo del administrador. El técnico ve los reportes financieros porque opera la caja y el cierre.
- **Costo promedio (M-11).** Cada compra actualiza el precio de compra del producto con el costo promedio ponderado. El precio de venta no cambia solo.
- **Abonos (B-07).** La fecha de un abono debe estar entre el ingreso de la orden y hoy.
- **Cambio de dueño de un equipo (B-06).** No se permite si el equipo tiene órdenes cobradas o entregadas: hay que registrar el equipo de nuevo.
