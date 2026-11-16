# Resultados de pruebas

Fecha: 2026-11-13. Todas las cifras de esta página salen de ejecuciones reales en este equipo (PHP 8.4.27, PHPUnit 12.5.43, SQLite en memoria). No se declara nada que no se haya ejecutado.

## 0. Quinta ronda (2026-11-16): recorrido en el navegador y suites

| Corrida | Resultado |
|---|---|
| Recorrido manual por el navegador (copia aislada, MySQL `tecni_e2e`) | 37 pantallas, todos los flujos de dinero, roles, PDF A4 y tirilla, vista a 375 px. 15 hallazgos corregidos (ver `CAMBIOS_REALIZADOS.md`, quinta ronda) y repetidos en el navegador después de corregir |
| Suite completa en SQLite | **795 pruebas en verde** (incluye `VerificacionOperacionTest` y `MediaHojaFirmasTest`, nuevas) |
| Suite completa en MySQL 8.4.11 (base temporal) | **786 en verde, 9 saltadas a propósito, 0 fallos** |
| Restauración de punta a punta en MySQL | OK |

## 0. Cuarta ronda (2026-11-16): SQLite y MySQL 8.4

| Corrida | Resultado | Cómo |
|---|---|---|
| Suite completa en SQLite (en memoria) | **780 pruebas en verde** | `vendor/bin/phpunit` |
| Suite completa en **MySQL 8.4.11** | **771 en verde, 9 saltadas a propósito, 0 fallos** (1 min 55 s) | Base temporal `tecni_test_<pid>` creada y eliminada por un script de la carpeta de trabajo; credenciales leídas del `.env` sin imprimirlas. La base de producción no se tocó |
| Restauración de punta a punta en MySQL | **OK (13 aserciones)** | `tests/Integration/RestauracionMysqlTest.php` (grupo `mysql-aislado`). Recorrido: respaldo real con mysqldump y cifrado AES-256; huella en el manifiesto; verificación en otra base temporal; cambios posteriores; restauración por la web con contraseña; comprobación de que el historial posterior se conservó y de que el modo mantenimiento se liberó |
| Compatibilidad sin migrar | OK | `CompatibilidadSinMigrarTest`: flujos principales con la base sin las columnas nuevas (solo SQLite) |
| Vista previa en navegador (SQLite con migraciones nuevas) | 41 pantallas respondieron 200 | Además se revisaron el código de consulta, el botón de recepción, el historial de la orden y la campana |

**Las 9 pruebas saltadas en MySQL** prueban la restauración con un SQL de ejemplo. En MySQL ese archivo se rechaza con razón: la restauración exige antes un volcado completo verificado en una base temporal. Ese flujo real lo cubre `RestauracionMysqlTest`. También se salta `CompatibilidadSinMigrarTest`, porque el DDL de MySQL no se puede revertir dentro de una prueba.

**Errores reales que solo aparecieron en MySQL** (corregidos antes de integrar):
1. La migración de integridad intentaba una restricción CHECK sobre `equipos.proveedor_id`, que tiene una llave con `ON DELETE SET NULL` (error 3823). La migración habría fallado en producción. Se quitó esa restricción (la garantiza la validación del controlador) y cada CHECK quedó tolerante a rechazos.
2. `VerificadorEsquema` leía `information_schema` con nombres en minúscula y MySQL los devuelve en mayúscula: `app:preflight` habría fallado.
3. Datos de prueba con valores que el ENUM de MySQL rechaza (`reparacion`, `estado` de cotización), y un estado `convertida` que no existe en la base (se quitó del controlador).
4. `Configuracion::getGlobal()` buscaba la fila con `id = 1`. Ahora usa la primera fila, igual que el resto del código. En producción la fila es la 1, así que hoy no había efecto.

**No probado:** impresión en una impresora térmica física; tabletas táctiles reales.

## 1. Suite automática (resultado final de la ronda)

| Momento | Pruebas | Aserciones | Resultado |
|---|---|---|---|
| Inicio de la auditoría (agente 8, antes de corregir) | 604 | 2041 | OK, sin avisos ni pruebas omitidas |
| **Final de la ronda** (`vendor/bin/phpunit`) | **628** | **2316** | **OK** (≈59 s) |

`vendor/bin/pint` queda sin cambios pendientes y `npm run build` compila.

### Pruebas nuevas de esta ronda

| Archivo | Pruebas | Qué demuestra | ¿Falla con el código anterior? |
|---|---|---|---|
| `tests/Feature/FinanzasCorreccionesTest.php` | 10 | M-01 a M-08 e idempotencia (M-07) con los casos numéricos del agente financiero | **Sí: 9 de 10** (comprobado revirtiendo el código). La décima comprueba que, si el primer envío falla, el formulario corregido se puede reenviar |
| `tests/Feature/AuditoriaIntegralCorreccionesTest.php` | 4 | B-02, B-12, X-01, X-03 | **Sí: 4 de 4** (comprobado) |
| `tests/Feature/BackupRotacionTest.php` | 3 | Rotación y retención por tipo, mínimo de copias, no borra archivos ajenos | Lógica nueva |
| `tests/Feature/CambioPasswordObligatorioTest.php` (+3) | 3 | S-05, S-01 (comando por consola) y F-01 (el invitado no ve montos en la 404) | F-01 **sí** (comprobado) |
| `tests/Feature/DiaDeTrabajoTest.php` | 1 (78 aserciones) | Día completo por HTTP: orden, repuesto, abono, venta a crédito, compra, gasto, anulación, reporte diario, cierre con conteo, día bloqueado y saldo inicial del día siguiente | Escrita por el agente 8; pasa con el código corregido |
| `tests/Feature/ReporteDiarioExportCoincideTest.php` | 1 | La pantalla del reporte diario, el Excel real (leído con PhpSpreadsheet) y los datos del PDF coinciden | Agente 8 |
| `tests/Feature/InvariantesFacturaCajaStockTest.php` | 1 | Pagado = caja, estado y stock coherentes tras doble pago, doble anulación, reactivación y anulación de un abono hijo | Agente 8 |

### Pruebas existentes que cambiaron porque cambió la regla de negocio

No se debilitó ninguna prueba para hacerla pasar. Estas fijaban un comportamiento que la auditoría demostró incorrecto y ahora exigen el correcto:

| Prueba | Antes exigía | Ahora exige | Motivo |
|---|---|---|---|
| `AuditFindingsTest` (3), `MovimientoInventarioControllerTest::test_admin_can_update_factura`, `RobustezContableAuditTest::test_p0_a_*`, `SecurityBloque0AuditTest::test_cannot_lower_*` | Poder subir o bajar el pagado editando la factura | Rechazo con error en `total_pagado` y pagado sin cambios | M-05 |
| `RobustezContableAuditTest::test_p0_e1_*` | Anular un movimiento anterior a un cierre, omitiendo los hijos cerrados | Rechazo, porque el período está cerrado | M-06 (período monótono) |
| `ProductionIntegrityTest::test_financial_partial_payment_and_cancellation` | Vínculo del pago a la factura por el texto `#VT-…` | Vínculo por `factura_id` | M-01/M-02 |
| `MovimientoCajaControllerTest::test_caja_duplicate_creates_copy` | La copia conserva la descripción | La copia se rotula "Copia de mov. N: …" | M-01 |

### Aislamiento de las pruebas (O-03 / S-02)

Antes, `php artisan test` usaba el `storage/` real. Una prueba de rotación borró el respaldo real `db_tecni_systemas_2026-11-05_14-39-48.sql`, y las restauraciones escribían en `storage/app/public` y en el log de producción.

Ahora `tests/TestCase.php` usa un storage temporal por proceso, y `GOOGLE_DRIVE_BACKUP_PATH` queda vacío en `phpunit.xml`. **Verificado:** se tomó una huella de `storage/app/backups`, `storage/app/public` y el log antes y después de correr las pruebas de respaldos, y no hubo diferencias.

## 2. Verificaciones manuales ejecutadas

| Verificación | Entorno | Resultado |
|---|---|---|
| `app:backup-db --sin-rotacion` con las nuevas opciones de mysqldump | MySQL real (solo añade un archivo) | Respaldo de 198,67 KB terminado en `-- Dump completed` |
| `app:verificar-respaldo` sobre ese respaldo | MySQL real; base temporal creada y eliminada | 31 tablas con conteos iguales y 8 sumas de control de dinero idénticas |
| Modal de notificaciones a 1366×640 | Vista previa aislada (SQLite en la carpeta de trabajo, sin MySQL ni el storage real) | El botón "Cerrar" queda dentro del card (545-583 px de 640). Se cierra con Escape y con clic en el fondo |
| Token `_idem` en formularios | Vista previa aislada | Se inyecta al enviar, se mantiene en un reenvío sin cambios y se renueva al editar un campo |
| Doble envío real de un movimiento de caja con el mismo token | Vista previa aislada | **Una sola fila creada** (consulta directa a la base de la vista previa) |

**No verificado:**
- La toma de foco del modal: con el panel del navegador oculto la página no recibe foco, así que no se puede comprobar.
- Impresión física en impresora térmica.
- Dusk.
- Pruebas de concurrencia reales en MySQL: `lockForUpdate` no hace nada en SQLite. La propuesta está en la sección del agente 8.

---

## Informe del agente 8 (QA y testing), previo a las correcciones

> Origen: agente 8. Describe el estado **anterior** a las correcciones. Q-01 está corregido (aislamiento del storage). Q-09 está corregido (idempotencia). Q-10 sigue **pendiente**.

### RESULTADOS DE TESTING: Agente 8 (QA y testing)

Fecha: 2026-11-13 · Proyecto: C:\ServBay\www\tecnisystemas (rama master, con el cambio del coordinador en EventosViewTest)
PHP: C:\ServBay\packages\php\current\php.exe (8.4.27) · PHPUnit 12.5.43 · SQLite en memoria

---

## 1. Ejecución de la suite completa

Comando: `php vendor/bin/phpunit --display-deprecations --display-warnings --display-notices --display-skipped --display-incomplete --log-junit <carpeta de trabajo de la auditoría>/junit.xml`

| Métrica | Valor |
|---|---|
| Resultado | **OK: 604/604 tests, 2041 aserciones**, código de salida 0 |
| Duración | 43,5 s de PHPUnit (44,4 s de reloj), memoria 142 MB |
| Deprecations / warnings / notices / risky / skipped / incomplete | **0 / 0 / 0 / 0 / 0 / 0** |
| JUnit | `(carpeta de trabajo de la auditoría)/junit.xml` |
| Tiempos de todos los tests (seg, aserciones, test) | `(carpeta de trabajo de la auditoría)/tiempos.txt` |

Suites fuera de phpunit.xml (no se ejecutan con `artisan test`):
- `tests/Integration/IntegracionCompletaTest.php`: **11 tests**. Los ejecuté a mano y pasan (OK, 31 aserciones, 1,2 s). Están huérfanos (ver Q-03).
- `tests/Browser/*` (Dusk): no se ejecutaron porque requieren un servidor web y las reglas de la auditoría prohíben arrancarlo (ver §3.3).

### 10 tests más lentos
| # | s | aserc. | Test |
|---|---|---|---|
| 1 | 5,36 | 2 | SmokeRutasTest::test_ninguna_pantalla_responde_error_para_admin_y_tecnico |
| 2 | 3,13 | 5 | BackupManagementTest::test_admin_can_trigger_manual_backup_integral_zip (comprime TODO el código fuente en un ZIP real, ver Q-01) |
| 3 | 1,84 | 13 | LoginLockoutTest::test_reincidencia_escala_a_3_minutos_y_luego_a_5_minutos |
| 4 | 1,01 | 4 | LoginLockoutTest::test_bloqueo_a_los_5_intentos_por_1_minuto |
| 5 | 1,01 | 4 | LoginLockoutTest::test_intento_durante_bloqueo_es_rechazado_con_tiempo_restante |
| 6 | 0,88 | 1 | Unit\AnulacionServiceTest::test_admin_password_valida_returns_true_for_correct_admin_password |
| 7 | 0,50 | 5 | AuditoriaIntegridadTest::test_pdf_numera_paginas_sin_php_embebido |
| 8 | 0,33 | 2 | CotizacionLifecycleTest::test_admin_can_generate_pdf_cotizacion |
| 9 | 0,31 | 4 | AuditoriaIntegridadTest::test_login_por_nombre_ambiguo_no_suplanta |
| 10 | 0,31 | 2 | SmokeRutasTest::test_invitado_no_puede_ejecutar_ninguna_accion_de_escritura |

La suite es rápida. Para bajarla unos 3 s, el respaldo integral del test 2 debería apuntar a un directorio temporal y no recorrer el código fuente real (Q-01).

---

## 2. Cobertura

`php -m` no muestra **pcov ni xdebug**, así que no se pudo medir la cobertura de líneas. En su lugar hice un mapa manual: para cada ruta busqué con grep las llamadas `route('nombre'...)` y las URIs literales en tests/Feature y tests/Unit.
Mapa completo (ruta, verbo, acción, número de referencias por archivo de test): `(carpeta de trabajo de la auditoría)/route_coverage.tsv`

Importante: `SmokeRutasTest` recorre **todas** las rutas GET con admin y técnico, pero solo comprueba que no devuelvan error 5xx. Una pantalla cubierta solo por el smoke no tiene verificado ni su contenido ni sus cifras.

### Métodos SIN prueba específica, por riesgo

**Dinero**
| Método | Estado | Riesgo |
|---|---|---|
| ReporteFinancieroController@reporteAcumulado | Solo se comprueba `assertViewHas('acumulado')`. Ninguna cifra verificada. Excel y PDF nunca probados | ALTA |
| ReporteFinancieroController@reporteOperaciones | Solo `assertViewHas('registros')`. Exportaciones sin probar | ALTA |
| ReporteFinancieroController@reporteDiario (export=excel/pdf) | Sin prueba antes de esta auditoría. **La cubre ReporteDiarioExportCoincideTest** (entregable 2) | (cubierta ahora) |
| MantenimientoController@factura / ElectronicaController@factura (factura de la orden para el cliente) | Solo el smoke. No se comprueba que los totales, abonos y saldo impresos coincidan con la orden | MEDIA-ALTA |
| BackupController@restore, camino MySQL real (mysql.exe, respaldo de seguridad previo, reconexión) | Los tests usan solo SQLite en memoria, donde la restauración ejecuta `DB::unprepared("SELECT 1")` y **se salta el respaldo de seguridad** (`$enMemoria`). La restauración real no tiene ninguna prueba | ALTA |
| Flujo completo del día (orden→abonos→venta→compra→cierre→reporte) | No existía. **Lo cubre DiaDeTrabajoTest** (entregable 1) | (cubierta ahora) |

**Permisos**
| Método | Estado | Riesgo |
|---|---|---|
| AuthController@logout | 0 tests (no se verifica que invalide la sesión ni que regenere el token) | MEDIA |
| Closure `electronicas.dismiss-alert` | 0 tests (está en la lista blanca del invitado en SmokeRutasTest) | BAJA |
| Caminos del técnico con contraseña de admin | Hay tests, pero muchos tests "de anulación" envían `password_confirm` **actuando como admin**, que está exento (AnulacionService:103). La contraseña no interviene (Q-06) | MEDIA |

**Datos**
| Método | Referencias |
|---|---|
| conceptos.update / conceptos.destroy, stocks.categorias.update / destroy | 1 test cada uno |
| cotizaciones.rechazar / cotizaciones.reactivar | 1 test cada uno |
| caja.duplicate, cierre.destroy, mantenimientos.duplicate, equipos.dar-de-baja / reactivar, tecnicos.update | 2 tests cada uno |

**Visual (solo smoke 5xx)**
clientes.show, equipos.show, proveedores.show, stocks.show, tecnicos.show, usuarios.show, caja.show, caja.create, cotizaciones.create, electronicas.create, mantenimientos.create, equipos.create, stocks.create, tecnicos.create, usuarios.create, reportes.index (ReporteController@index).

---

## 3. Calidad de los tests

### 3.1 Hallazgos

**Q-01: ALTA: la suite escribe y BORRA archivos en el directorio REAL de respaldos (storage/app/backups)**
- Archivos: tests/Feature/BackupManagementTest.php:37-40, 179-195 y 490-500; app/Console/Commands/BackupDatabase.php:37, 215-218 y 567-597.
- Evidencia: `$this->backupDir = storage_path('app/backups')` es la misma carpeta que usa producción (hoy contiene 4 respaldos .sql reales: `db_tecni_systemas_2026-11-05_14-43-19.sql` …). El test `test_admin_can_trigger_manual_backup_integral_zip` crea `Configuracion` con `backup_max_copias => 5` y ejecuta `app:backup-db --all` sin `--sin-rotacion`. Eso llama a `aplicarPoliticaRotacion($backupDir, 5)` y a `limpiarBackupsAntiguos(..., 15)` **sobre la carpeta real**.
- Escenario que falla: si en la carpeta hay 5 o más respaldos reales (lo normal con respaldo diario automático), al correr `php artisan test` en el equipo de producción se ejecuta `File::delete` sobre los respaldos reales más antiguos, hasta dejar 5 archivos contando el ZIP de prueba. Ese ZIP se borra después en tearDown porque su nombre contiene "memory", así que el resultado son **4 respaldos reales**. Además, ese ZIP de prueba empaqueta todo el código fuente (3 s). Si `GOOGLE_DRIVE_BACKUP_PATH` estuviera definido en .env, el test también copiaría a Drive y aplicaría allí la retención.
- Corrección: en `Tests\TestCase::setUp()` aislar el almacenamiento: `$this->app->useStoragePath($dir = sys_get_temp_dir().'/tecni-tests-'.getmypid()); File::ensureDirectoryExists($dir.'/app/backups');`. Añadir también `config(['backup.drive_path' => null])` y `<env name="GOOGLE_DRIVE_BACKUP_PATH" value="" force="true"/>` en phpunit.xml. Otra opción es hacer configurable el directorio de respaldos (`config('backup.local_path')`) y fijarlo en phpunit.xml. Con eso, los tearDown que borran por nombre ("test"/"memory") dejan de ser necesarios.

**Q-02: MEDIA: los tests dependen de las contraseñas reales de producción en .env**
- Archivos: tests/Feature/LoginFlexibilityTest.php:26-154, LoginLockoutTest.php:150-198, ProductionIntegrityTest.php:242-245, y además `env('ADMIN_DEFAULT_PASSWORD', 'Admin123*')` en ElectronicaControllerTest:382, EquipoControllerTest:261/286, MovimientoCajaControllerTest:219/283/495, StockControllerTest:224/246, TecnicoControllerTest:141/160, UserControllerTest:208/227.
- Evidencia: no existe `.env.testing` y phpunit.xml no fija `*_DEFAULT_PASSWORD`. En este equipo `.env` define las tres variables (longitud 9). `AdminUserSeeder::resolverPassword()` genera una contraseña aleatoria cuando la variable está vacía.
- Escenario que falla: en un clon limpio, en CI o en un equipo con esas variables vacías, el seeder crea una clave aleatoria y el test intenta entrar con '' o 'Admin123*'. Fallan unos 8 tests de login. Además, los tests leen la contraseña real del administrador de producción y la usan en peticiones simuladas.
- Corrección: en phpunit.xml añadir `<env name="ADMIN_DEFAULT_PASSWORD" value="Test-Admin-9*" force="true"/>` (lo mismo para TECNICO e INVITADO), o crear `.env.testing` sin secretos reales. En los tests de controladores donde actúa un admin, quitar `password_confirm => env(...)`, porque no se usa.

**Q-03: MEDIA: tests/Integration no está en phpunit.xml (11 tests que nunca se ejecutan)**
- Archivo: phpunit.xml:8-15 (solo están las suites Unit y Feature).
- Evidencia: `phpunit tests/Integration` da OK (11 tests, 31 aserciones), pero `artisan test` informa 604 y no los incluye.
- Corrección: añadir `<testsuite name="Integration"><directory>tests/Integration</directory></testsuite>`. Revisar también `test_reportes_consistencia_totales` (solo `assertGreaterThanOrEqual(0, …)`) y `test_scopes_activos` (`assertLessThanOrEqual`), que no aportan nada.

**Q-04: MEDIA: los tests "de concurrencia" no ejecutan nada en paralelo; `lockForUpdate` no se prueba**
- Archivo: tests/Feature/ConcurrencyTest.php:52-112 (T-01 "dos conversiones simultáneas"), T-04 (cierre concurrente) y similares en RobustezContableAuditTest.
- Evidencia: las dos peticiones se hacen una detrás de otra en el mismo proceso. En SQLite la gramática de Laravel compila `lockForUpdate()` como cadena vacía. Si se quitaran todos los `lockForUpdate()` del código, los tests seguirían pasando, porque la segunda petición ve el stock que dejó la primera.
- Corrección: renombrarlos a "secuencial" y añadir un grupo `#[Group('mysql')]` contra una base de pruebas MySQL dedicada (ver §4.1).

**Q-05: MEDIA: 97 tests con 1 aserción o menos; varios solo comprueban el código de estado**
- Ejemplos: MovimientoInventarioControllerTest (14 tests: `test_facturas_index_filters_by_tipo`, `_by_estado`, `_by_date_range`, `test_factura_show_loads_items_and_abonos`, `test_admin_can_print_factura`…), DashboardControllerTest (7: `test_dashboard_shows_caja_saldos`, `test_dashboard_shows_chart_data_for_7_days`…). Son tests cuyo nombre promete verificar datos pero solo hacen `assertOk()`. `test_venta_rejects_pago_mayor_total` solo comprueba `assertSessionHas('error')` y no verifica que no se haya creado una factura ni que el stock siga igual.
- Corrección: en los filtros, comprobar `viewData('facturas')` (incluye A y excluye B). En los rechazos, comprobar además `Factura::count()` y el stock sin cambios. En el dashboard, comparar los saldos con cifras calculadas a mano.

**Q-06: BAJA: contraseñas sin efecto y nombres engañosos**
- `test_admin_can_anular_movimiento` y similares envían `password_confirm`, pero el admin está exento (AnulacionService.php:102-105). El lector cree que se valida la contraseña.
- `Unit\CierreCajaGuardTest:99` usa `assertTrue(true)`. Es mejor `$this->expectNotToPerformAssertions()` o `assertNull(...)`.
- Corrección: eliminar el parámetro en los tests de admin y mantener los casos de técnico (que sí existen, p. ej. MovimientoCajaControllerTest:228).

**Q-07: BAJA: dependencia de la fecha y hora actuales**
- Ningún test usa `travelTo`/`freezeTime` (0 apariciones). Más de 200 usos de `now()`/`today()`, más 12 con `subDay`/`addDay`.
- Escenario que falla: un test que se ejecuta a las 23:59:59 crea el movimiento con `now()->toDateString()` y el controlador (cierre.index, reporteDiario sin fecha) calcula "hoy" un instante después, ya en el día siguiente. Es un fallo intermitente poco probable. Las fechas fijas (CierreCajaBloqueoTest, CierreCajaConciliacionTest, CierreCajaGuardTest: 2026-01-xx) son seguras mientras no exista una validación `before_or_equal:today`.
- Corrección: `$this->freezeTime()` en el setUp de los tests de caja, cierre y reportes. Los tres tests entregados ya usan `travelTo` con fecha fija.

**Q-08: BAJA: los tests de restauración escriben en storage/app/public real**
- BackupManagementTest:311-332 y 337-362 restauran `test_media_file.txt` e `integral_logo.txt` en `storage_path('app/public')` y los borran después. Si un test falla antes de limpiar, el archivo se queda en la carpeta pública real. Se corrige con el mismo aislamiento que Q-01.

### 3.2 Acoplamiento al markup
`assertSee`/`assertDontSee` se usan mucho en GuestDashboardTest (17), FrontendAccessibilityAndSecurityTest (16), EventosViewTest (13), CierreCajaConciliacionTest (12), CotizacionProveedorTest (12), BackupEventTest (9) y DesignSystemCatalogTest (8). Los de FrontendAccessibility, DesignSystemCatalog y EventosView comprueban clases CSS y atributos concretos (por eso EventosViewTest se rompió con el rediseño de columnas). Para las cifras, conviene usar `viewData()` (como hace ReporteFinancieroControllerTest:76) y dejar `assertSee` para textos funcionales.

### 3.3 Tests Dusk (tests/Browser): estado
- **No funcionan en este equipo tal como está**:
  1. No hay chromedriver: `vendor/laravel/dusk/bin/` solo contiene `jquery.js`. `DuskTestCase::prepare()` (#[BeforeClass]) llama a `startChromeDriver` **antes** del guard de setUp, así que falla con error en lugar de omitirse. Se necesita `php artisan dusk:chrome-driver`.
  2. No existe `.env.dusk.local`. La base que se usaría es la de producción y el guard de `DuskTestCase::setUp` (regex `_test|_dusk|testing|:memory:`) **omitiría** todos los tests. El guard es correcto y protege la BD real.
  3. Necesitan un servidor web en APP_URL. No lo arranqué, por las reglas de la auditoría.
  4. Ningún test usa `DatabaseMigrations`/`DatabaseTruncation`: crean datos persistentes (`firstOrCreate` de usuarios `*_dusk@test.com` con contraseña "password") que se acumulan en la BD de Dusk. `StockProveedorInactivoVerificationTest` depende de que exista un admin sembrado (`User::where('role','admin')->first()`).
- **Qué cubren (13 tests)**: ciclo de vida de caja y abonos con saldo pendiente; cierre de caja y reportes financieros; directorio de clientes y proveedores; login y seguridad de equipos; venta de inventario (scroll) y facturas; ciclo de vida de mantenimientos; estilos de stock.show, aviso de inactivo y reactivar proveedor, tooltip de validación de TomSelect, foco de "utilidad" y **vista móvil de stock.show** (única prueba responsive); ExampleTest (login).
- Corrección: documentar en docs/ el procedimiento (`.env.dusk.local` con `DB_DATABASE=tecni_systemas_dusk`, `dusk:chrome-driver`, servidor), mover el guard antes de `startChromeDriver` y añadir `use DatabaseTruncation`.

---

## 4. Escenarios que faltan

### 4.1 Concurrencia real
- **Límite de PHPUnit con SQLite en memoria**: hay una sola conexión y un solo proceso, y `FOR UPDATE` se ignora. Nada de lo que se ejecute ahí demuestra que los bloqueos funcionan.
- **Cómo simularla sin servidor** (base MySQL dedicada `tecni_systemas_test`, nunca la real):
  1. *Dos conexiones en el mismo proceso*: clonar la conexión (`config(['database.connections.mysql_b' => config('database.connections.mysql')])`). La conexión A abre `DB::beginTransaction()` y hace `Stock::lockForUpdate()->find(id)`. La conexión B ejecuta `SET innodb_lock_wait_timeout=1` e intenta el mismo `lockForUpdate`, o la lógica de StockService. Se espera `QueryException` 1205 (lock wait timeout). Así se demuestra que el código adquiere el bloqueo. Luego A hace commit y se verifica que B, al reintentar, ve la cantidad actualizada.
  2. *Procesos paralelos*: `Symfony\Component\Process\Process` lanza N veces `php artisan tinker --execute=...` o un comando de prueba contra la BD de test, y se verifica stock ≥ 0 y numeración sin huecos ni duplicados. En Windows no hay pcntl, pero Process funciona.
  3. *HTTP real*: `php artisan serve` con la BD de test más `Http::pool()` (fuera del alcance de esta auditoría, que prohíbe servidores).
- Con SQLite en archivo y WAL hay bloqueo a nivel de base (SQLITE_BUSY), no de fila. Eso prueba serialización, pero no la lógica de `lockForUpdate`.

### 4.2 Doble envío (idempotencia)

**Q-09: MEDIA: no hay idempotencia en el servidor para venta, compra, caja.store ni abonos de orden**
- Evidencia (sonda ejecutada, `probes/SondaHallazgosTest.php`): dos POST idénticos a `inventario.venta.store` crean **2 facturas** y descuentan el stock dos veces (10 → 8). La única protección está en el cliente (layouts/app.blade.php:752-768: deshabilita el botón y lo **libera a los 8 s**).
- Escenario: un servidor lento (más de 8 s, p. ej. mientras corre el respaldo), "Atrás" y reenviar, o dos pestañas, crean ventas duplicadas.
- Lo que sí está protegido y verificado: abono por encima del saldo (orden y caja), segunda anulación o reactivación con `accion` explícita y segundo cierre del mismo día. Está cubierto en los entregables 1 y 3.
- Corrección: añadir un campo oculto `idempotency_key` (UUID generado al renderizar el formulario) y una tabla `solicitudes_procesadas(clave unique, ruta, respuesta_id)` dentro de la misma transacción. Si la clave se repite, redirigir al documento ya creado.

### 4.3 Flujo "día de trabajo": **implementado (entregable 1)**. Todas las cifras cuadraron. No se encontró descuadre entre caja, cierre y reporte diario.

**Q-10: MEDIA (funcional, para el agente financiero): una venta a crédito no se puede cobrar después de cerrar su día; una orden sí**
- Archivo: app/Http/Controllers/MovimientoCajaController.php:496-507 ("Mitigación P0-E2"), comparado con app/Traits/HandlesAbono.php:47 (solo valida la fecha del abono).
- Evidencia (sonda): venta del 2026-06-01 con saldo, cierre del 06-01, abono el 06-02 con fecha 06-02 → `"No se pueden registrar abonos a una factura del día 2026-06-01 porque ese día ya tiene cierre de caja registrado."`. En cambio, el abono el 06-02 a una **orden** del 06-01 → OK.
- Escenario: el cliente paga mañana lo que quedó debiendo hoy, una situación diaria. Para registrarlo hay que borrar el cierre (solo se puede borrar el último), y tras dos días cerrados es imposible. El saldo por cobrar se queda abierto para siempre o el cobro se registra como ingreso suelto, rompiendo el vínculo con la factura.
- Corrección: permitir el abono con fecha en un día abierto aunque la factura sea de un día cerrado (el abono es un movimiento nuevo de un día abierto, igual que en órdenes). El reporte del día cerrado debe tomar el saldo pendiente "a la fecha" (suma de abonos con `fecha <= día`) en lugar de `total_pagado` actual. Hay que ajustar el test `RobustezContableAuditTest:543` (P0-E2), que hoy fija el comportamiento contrario.

**Q-11: BAJA (confirmar con negocio): el técnico puede vender a cualquier precio**
- Evidencia (sonda): un técnico registra una venta de un artículo con precio de venta 2.000 a `precio_unitario = 1` y se acepta (MovimientoInventarioController:218-228 no compara con `precio_venta`/`precio_tecnico`).
- Corrección (si negocio lo decide): exigir autorización de admin cuando el precio sea menor que `precio_tecnico`.

### 4.4 Restauración de respaldo
No hay prueba del camino real (Q-01/§2). Propuesta: test `#[Group('mysql')]` sobre `tecni_systemas_test` que haga un ciclo completo: sembrar datos conocidos → `app:backup-db` → modificar o borrar filas → `backups.restore` → comparar `CHECKSUM TABLE` o los conteos y sumas de `movimiento_cajas.monto`, `facturas.total_documento` y `stocks.cantidad` con los valores originales, y verificar que se creó el respaldo de seguridad previo.

### 4.5 PDF/Excel con totales iguales a pantalla
Implementado para el reporte diario (entregable 2), y coinciden. Faltan reporteAcumulado, reporteOperaciones, mantenimientos/electronicas/stocks.reportes (sus tests solo comprueban el estado 200 y el tipo de contenido) y las facturas de orden (`mantenimientos.factura`, `electronicas.factura`). El patrón del entregable 2 (Excel real leído con PhpSpreadsheet más `View::composer` para capturar los datos del PDF) se puede reutilizar.

### 4.6 Responsive
Solo existe `StockProveedorInactivoVerificationTest::test_stock_show_mobile_view_fixed` (Dusk, no ejecutable hoy). Propuesta: un test Dusk parametrizado que recorra las pantallas principales con `resize(375, 812)` y compruebe `document.documentElement.scrollWidth <= window.innerWidth` (sin scroll horizontal) y la visibilidad del botón principal.

---

## 5. Tests implementados (en el carpeta de trabajo de la auditoría, todos pasan)

Ejecución: `C:\ServBay\packages\php\current\php.exe vendor/bin/phpunit <ruta>` desde la raíz del proyecto. Namespace `Tests\Feature`; para incorporarlos basta con copiarlos a `tests/Feature/`.

| # | Archivo | Resultado | Qué verifica |
|---|---|---|---|
| 1 | `(carpeta de trabajo de la auditoría)/tests\DiaDeTrabajoTest.php` | OK (1 test, 78 aserciones, 1,0 s) | Flujo completo por HTTP: cliente → equipo → orden (50.000) → repuesto (+150.000) → abono efectivo 80.000 → pago consignación 120.000 → triple clic rechazado → venta 160.000 (paga 100.000) → compra 200.000 → gasto 30.000 → ingreso 999.000 anulado. Cifras calculadas a mano en la cabecera del archivo: ingresos 300.000, egresos 230.000, efectivo −50.000, consignación 120.000, efectivo esperado 50.000 (saldo inicial 100.000 contado el día anterior), facturado 360.000, por cobrar 60.000, anulados 1 / 999.000, stock 9 y 7. Comprueba que coinciden el reporte diario (24 claves de `resumen`), la vista previa del cierre, el cierre guardado y el saldo inicial del día siguiente, y que el día queda bloqueado tras el cierre. |
| 2 | `(carpeta de trabajo de la auditoría)/tests\ReporteDiarioExportCoincideTest.php` | OK (1 test, 32 aserciones, 1,2 s) | Pantalla frente a Excel **real** (descargado y leído con PhpSpreadsheet: una fila por movimiento, suma de la columna Costo, anulados y pie "Balance Neto: $ 130.000") y frente al PDF (`View::composer` captura los datos que recibe `pdf_diario`: el `resumen` es idéntico al de la pantalla, el HTML contiene los totales formateados y la respuesta empieza por `%PDF`). |
| 3 | `(carpeta de trabajo de la auditoría)/tests\InvariantesFacturaCajaStockTest.php` | OK (1 test, 75 aserciones, 1,3 s) | Invariantes comprobados tras cada paso: (I1) `total_pagado` = Σ movimientos activos ligados (directos y abonos hijos) y estado coherente; (I2) factura anulada → `total_pagado` 0 y sin movimientos activos; (I3) stock = inicial + compras − ventas activas; (I4) nunca se cobra más que el total. Secuencia: venta a crédito, abono, pago final enviado dos veces, venta sin stock, compra a medias, anulación doble, reactivación doble, anulación de un abono hijo y cuadre de caja con facturas. |

Sonda de caracterización (NO es entregable, solo evidencia de Q-09, Q-10 y Q-11): `(carpeta de trabajo de la auditoría)/probes\SondaHallazgosTest.php`

---

## 6. Qué se revisó y qué no

**Revisado**: phpunit.xml; tests/TestCase.php; tests/DuskTestCase.php; listado completo de tests/Feature (55), tests/Unit (7), tests/Integration (1) y tests/Browser (8); lectura detallada de SmokeRutasTest, BackupManagementTest (setUp, tearDown, manual, restauración), BackupEventTest (tearDown), ConcurrencyTest (T-01), ReporteFinancieroControllerTest, MovimientoCajaControllerTest (anulación), LoginFlexibilityTest (setUp), ProductionIntegrityTest:230-245, CierreCajaGuardTest y los tests Dusk CajaAbonos y Example. Código: AbonoController, HandlesAbono, HandlesStockAttach, MantenimientoStockController, MantenimientoController@store, MovimientoInventarioController (compra, venta, anularFactura, registrarMovimientoCaja), MovimientoCajaController (store, storeAbono, anular, sincronización), CierreCajaController completo, CierreCaja::saldoInicialEfectivo, Factura::recalcularPagos, ReporteFinancieroController@reporteDiario, ReportesFinancierosExport, AnulacionService (autorización e intención), BackupDatabase (dump, rotación, retención, Drive), BackupController (createManual, restore), AdminUserSeeder y el bloqueo de doble envío de layouts/app.blade.php.

**Correcto (verificado)**: la suite en verde y sin deprecations; el guard de Dusk contra la BD real; el bloqueo del día tras el cierre (caja, abonos, segundo cierre); el rechazo de abonos que exceden el saldo; la anulación y reactivación idempotentes con `accion`; la cascada y restauración de pagos de facturas; la coherencia entre reporte diario, cierre y saldo inicial; Excel y PDF del reporte diario idénticos a la pantalla.

**NO revisado**: ejecución de Dusk (requiere servidor); cobertura de líneas (sin pcov/xdebug); contenido de los tests de Cotizacion*, Electronica*, Policy*, Security*, FrontendAccessibility y DesignSystem más allá de los conteos; las cifras de reporteAcumulado y reporteOperaciones; las facturas de orden (factura de mantenimientos y electrónicas); git-update; los tests Dusk distintos de los dos leídos.
