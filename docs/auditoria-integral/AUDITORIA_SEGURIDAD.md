# Auditoría de seguridad

> **Origen:** informe del agente 6 (ciberseguridad) de la auditoría integral del 2026-11-13 (análisis con evidencia, sin modificar producción).
> **Coordinación:** el coordinador verificó los hallazgos ALTOS/CRÍTICOS antes de corregirlos y añadió este encabezado de estado.

## Estado tras las correcciones de esta ronda

| Hallazgo | Estado |
|---|---|
| S-01 cuentas sembradas con contraseña publicada | **Mitigado en código** (`app:restablecer-password` por consola, sin pasar por la web). **Acción del dueño pendiente:** ejecutarlo para las 3 cuentas |
| S-02 / O-03 las pruebas borraban respaldos reales | **Corregido**: storage temporal por proceso de pruebas (`tests/TestCase.php`), verificado con huella antes/después |
| S-03 `start-ngrok.bat` expone la aplicación | **Pendiente (decisión del dueño)**: no se borró un archivo suyo |
| S-05 la cuenta invitado compartida podía fijar una clave | **Corregido** (403 si no hay cambio obligatorio) + prueba |
| F-01 el invitado veía montos en páginas 404 | **Corregido** + prueba que falla sin el arreglo |
| S-04, S-06, S-07, S-08, S-09 a S-16 | **Pendientes** |

Los identificadores (p. ej. `M-01`) se usan igual en `CAMBIOS_REALIZADOS.md`, `PENDIENTES.md` y en los mensajes de commit.

---

## Informe del agente 6 (ciberseguridad)

Proyecto: `C:\ServBay\www\tecnisystemas` (WolfCore System), Laravel 13.40 y PHP 8.4.27.
Fecha: 2026-11-13. Método: análisis estático, comandos de solo lectura (`route:list`, `app:preflight`, `composer audit`, `npm audit`) y pruebas de concepto en SQLite en memoria.
No se atacó el sitio, no se escribió en MySQL y no se modificó ningún archivo del proyecto.

- Pruebas de concepto: `(archivo de trabajo de la auditoría)/SeguridadAuditoriaPocTest.php`. Resultado: **8/8 OK y 32 aserciones**. Las pruebas que pasan confirman el hallazgo que describen.
- Se ejecutaron con `C:\ServBay\packages\php\current\php.exe vendor/bin/phpunit --no-coverage <ruta>`. No invocan respaldos.
- Dos pruebas se nombraron con un número provisional que no coincide con el ID final: `test_s06_*` corresponde a S-09 y `test_s12_*` a S-11.
- Inventario de rutas con sus middleware: `(archivo de trabajo de la auditoría)/routes.txt`, 155 rutas.

## Resumen ejecutivo

| ID | Sev. | Título |
|---|---|---|
| S-01 | **CRÍTICA** | Las 3 cuentas sembradas de la BD real siguen con la contraseña publicada, y el cambio obligatorio deja que el primero en entrar fije la contraseña nueva |
| S-02 | **ALTA** | Correr la batería de tests en el servidor borra respaldos reales (rotación a 5 copias en `storage/app/backups`) |
| S-03 | **ALTA** | Publicación con `php artisan serve --host=0.0.0.0` más ngrok (`start-ngrok.bat`) |
| S-04 | MEDIA | La consulta del invitado permite enumerar órdenes conociendo solo la cédula (órdenes secuenciales) |
| S-05 | MEDIA | `PUT /cambiar-password` funciona sin obligación de cambio; con la cuenta invitado compartida, un visitante deja fuera a los demás |
| S-06 | MEDIA | Cambiar la contraseña desde Usuarios no cierra las otras sesiones del usuario (sin `AuthenticateSession`) |
| S-07 | MEDIA | La aplicación se conecta a MySQL como `root`, y la restauración ejecuta SQL arbitrario con esos privilegios |
| S-08 | MEDIA | Respaldos sin cifrar (BD completa) copiados a la carpeta de Drive; descargar, restaurar o borrar no pide volver a autenticarse |
| S-09 | BAJA | El bloqueo por cuenta depende del texto escrito: un tercero lo dispara, y se evita usando otro alias |
| S-10 | BAJA | "Mantener sesión iniciada" dura 400 días y anula `expire_on_close` y `lifetime` |
| S-11 | BAJA | Gates de admin definidos pero sin usar: el técnico ve los reportes financieros |
| S-12 | BAJA | La regla "editar requiere contraseña de admin" (comentario en `routes/web.php`) solo se cumple en parte |
| S-13 | BAJA | Cabeceras: no hay `trustHosts`, `X-XSS-Protection` está obsoleto y las descargas binarias salen sin cabeceras de seguridad |
| S-14 | BAJA | `.env.backup` con `APP_DEBUG=true` y secretos; `.env` con contraseñas por defecto; Dusk instalado |
| S-15 | BAJA | Las pruebas escriben en los logs y en `storage/app/public` de producción |
| S-16 | BAJA | Errores 500 por fechas no validadas (`Carbon::parse` sobre la entrada) |

La CSP con `'unsafe-inline'` ya es deuda conocida y no se repite como hallazgo.

---

## CRÍTICA

### S-01. Contraseñas publicadas en la BD real y toma de la cuenta en el primer ingreso

**Evidencia**
- `app:preflight` (solo lectura) contra la BD real devuelve:
  `✘ Ningún usuario con contraseña conocida → administrador@tecnisystemas.com, tecnico@tecnisystemas.com, invitado@tecnisystemas.com`.
- `.env`: `ADMIN_DEFAULT_PASSWORD`, `TECNICO_DEFAULT_PASSWORD` e `INVITADO_DEFAULT_PASSWORD` están en `PasswordsConocidas::LISTA` (comprobado sin imprimir los valores). Son las contraseñas que aparecieron en versiones anteriores de la documentación y del repositorio de GitHub (`origin`).
- `AuthController.php:154-160`: si la contraseña es conocida, marca `must_change_password` y **autentica** igual.
- `PasswordController@update` solo pide `current_password`, que el atacante conoce.
- PoC `test_s01_password_publicada_permite_tomar_la_cuenta_de_admin` (OK): entra con `admin` / la clave publicada y fija `Atacante2026X`. El dueño legítimo queda fuera.

**Escenario.** El sistema se expone por ngrok (S-03) o en la red local. Cualquiera que haya leído la documentación o el historial de git entra como administrador. El "cambio obligatorio" le pide definir una contraseña nueva, y el atacante la define. Desde ahí controla los usuarios, la caja, los respaldos (descarga de la BD completa), la restauración y `git-update`.

**Corrección**
1. Ya, antes de volver a exponer el sistema: cambiar las 3 contraseñas desde consola, en el servidor. Por ejemplo, con un comando `app:reset-password {email}` que genere `Str::password(16)` y lo muestre una sola vez. Vaciar `*_DEFAULT_PASSWORD` del `.env`.
2. En el código, si `PasswordsConocidas::es($inputPassword)`, **negar el ingreso** en lugar de autenticar. Mensaje: "Contraseña bloqueada; solicite al administrador un restablecimiento". El restablecimiento debe hacerse fuera de banda (comando de consola o contraseña temporal aleatoria creada por el admin).
3. `app:preflight` debe ser bloqueante en el arranque. Ejemplo: el middleware de login rechaza todo mientras exista una contraseña conocida y `APP_ENV=production`.

---

## ALTA

### S-02. Los tests borran respaldos reales

**Evidencia**
- `BackupController::getBackupDir()` y `BackupDatabase` usan siempre `storage_path('app/backups')`, el directorio de producción.
- `tests/Feature/BackupManagementTest.php:144-158` (`test_admin_can_trigger_manual_backup_and_rotation_runs`) crea `Configuracion` con `backup_max_copias = 5` y llama a `backups.manual`.
- `BackupDatabase::aplicarPoliticaRotacion` (`app/Console/Commands/BackupDatabase.php:567-603`) borra **todo** archivo `.sql/.zip/.gz` que quede fuera de los 5 más recientes, sin filtrar por nombre.
- `storage/logs/*.log` lo confirma con entradas `testing.INFO: Rotación de backups: 1 archivo(s) eliminados para respetar límite de 5 copias` y `testing.INFO: Retención de backups (local): 5 archivo(s) eliminados`.
- `tearDown` borra después los archivos con "test" o "memory" en el nombre, así que los respaldos reales eliminados no vuelven.

**Escenario.** Hay 10 respaldos reales. Se ejecuta `artisan test` en el servidor (como indica el propio contexto). El test crea 1 respaldo y la rotación conserva 5: 1 de prueba y 4 reales. **Se borran 6 respaldos reales.** Luego `tearDown` elimina el de prueba. Si `GOOGLE_DRIVE_BACKUP_PATH` estuviera configurado, los tests también copiarían y rotarían en Drive.

**Corrección**
- Llevar la ruta a `config('backup.dir', storage_path('app/backups'))`.
- En `tests/TestCase::setUp()`, apuntarla a un directorio temporal (`sys_get_temp_dir().'/wolfcore-tests-'.uniqid()`) y forzar `backup.drive_path = null`.
- En la rotación y la retención, filtrar por el patrón de nombres que genera el comando: `^(db_|backup_bd_multimedia_|backup_integral_|uploads_)`.
- Como defensa adicional, que `phpunit.xml` fije `LOG_CHANNEL=null` o un canal de log propio (ver S-15).

### S-03. Exposición con el servidor de desarrollo y ngrok

**Evidencia.** `start-ngrok.bat` (versionado en git) ejecuta `php artisan serve --host=0.0.0.0 --port=8000` y `ngrok http 8000`. `bootstrap/app.php:23` confía en `X-Forwarded-*` de `127.0.0.1`/`::1` para que funcione el túnel.

**Escenario**
- El servidor embebido de PHP queda escuchando en todas las interfaces, en HTTP sin TLS para la LAN, con un solo proceso. Un cliente lento o una exportación pesada lo bloquea.
- Por ngrok, el login queda en Internet. Junto con S-01, el resultado es la toma de la cuenta de admin desde cualquier lugar.
- Además, el `php` del PATH es 8.4.0, que según el contexto falla.

**Corrección**
- Servir con el servidor web de ServBay (Caddy/Nginx más PHP-FPM) en `127.0.0.1`, o en la interfaz de la LAN con TLS.
- Si hace falta acceso remoto, usar un túnel con autenticación previa (ngrok `--basic-auth` / OAuth, o Cloudflare Access) o una VPN (Tailscale/WireGuard).
- Quitar `start-ngrok.bat` del repositorio o documentarlo solo para demostraciones.

---

## MEDIA

### S-04. La consulta del invitado permite enumerar órdenes

**Evidencia**
- `MantenimientoController@consulta` (`:394-437`), `ElectronicaController@consulta` y `GuestController@search`: la autorización se basa en la `identificacion` del cliente más el `id_orden`, que es secuencial (`ORD-N`, `ELC-N`).
- Los números puros se convierten en `ORD-N`.
- El límite es `throttle:30,1` **por usuario**, y la cuenta invitado es compartida: 43.200 intentos al día.
- PoC `test_s04_enumeracion_de_ordenes_con_cedula_conocida` (OK): conociendo la cédula, recorre 1..25, encuentra `ORD-17` y luego abre `mantenimientos.show` con 200.

**Escenario.** La cédula no es un dato secreto: aparece en facturas y formularios. Un tercero con la cuenta invitado obtiene todas las órdenes de esa persona: equipo, descripción, costos, abonos y técnico.

**Corrección**
- Generar por orden un `codigo_consulta` aleatorio (por ejemplo `Str::random(8)`, impreso en el recibo) y exigirlo en lugar de `id_orden`, o además de él.
- Limitar por IP **y** por identificación, por ejemplo `RateLimiter` con 5 fallos cada 15 min por cédula.
- Registrar en `Evento` las consultas fallidas.

### S-05. Cambio de contraseña libre y cuenta invitado compartida

**Evidencia**
- `PasswordController@edit` redirige si `must_change_password` es falso, pero `update` (`:27-70`) no lo comprueba.
- Al cambiar la contraseña, `update` borra **todas las demás sesiones** del usuario (`:55-60`).
- La cuenta `invitado@tecnisystemas.com` es única y compartida (`AdminUserSeeder`, `PolicyMantenimiento`, que usa la sesión del invitado genérico).
- PoC `test_s05_invitado_compartido_cambia_password_sin_obligacion` (OK).

**Escenario**
- Cualquier visitante con la clave del invitado ejecuta el PUT y fija una contraseña que solo él conoce. Echa a los demás invitados y la consulta pública queda inutilizada.
- Con la contraseña predeterminada (S-01), el **primer** visitante decide la nueva contraseña del invitado.

**Corrección**
- En `update`, `abort_unless($usuario->must_change_password, 403)`. Si se quiere permitir el cambio voluntario, que sea solo para admin y técnico.
- Excluir el rol `invitado` del cambio obligatorio y de cualquier cambio de contraseña hecho por él mismo. Que lo gestione solo el admin.
- A mediano plazo, que la consulta pública no requiera una cuenta compartida: página sin login con `codigo_consulta` (S-04) y limitación por IP.

### S-06. Restablecer la contraseña no cierra las sesiones abiertas

**Evidencia**
- `UserController@update:122-136` cambia `password` y `remember_token`, pero no borra las filas de `sessions` del usuario.
- El middleware `AuthenticateSession` (`auth.session`) no está registrado: `grep` no lo encuentra en `app`, `bootstrap`, `config` ni `routes`.
- La sesión es de inactividad deslizante: 120 min que se renuevan con cada uso.

**Escenario.** Roban la sesión de un técnico. El admin "le cambia la contraseña", pero el atacante sigue activo indefinidamente mientras siga usando la sesión. Lo mismo pasa si el usuario cambia su propia contraseña o su correo desde Mi Perfil.

**Corrección**
- Tras cambiar la contraseña en `UserController@update`, ejecutar `DB::table('sessions')->where('user_id', $usuario->id)->delete()`, conservando la sesión actual si es el propio usuario.
- Añadir `auth.session` al grupo autenticado, que invalida las sesiones cuyo hash de contraseña cambió.

### S-07. Usuario MySQL `root`

**Evidencia**
- `app:preflight`: `⚠ Usuario de BD distinto de root → Se usa el usuario root`.
- `BackupController::restoreSqlFile` ejecuta el `.sql` completo con el cliente `mysql`, o con `DB::unprepared` como alternativa, usando esas credenciales.

**Escenario.** Si llega a `storage/app/backups` un `.sql` manipulado (copia traída de Drive, otro equipo o un USB), al restaurarlo puede ejecutar cualquier cosa sobre **todo** el servidor MySQL: otras bases, `CREATE USER`, `SELECT ... INTO OUTFILE` si `secure_file_priv` lo permite. No hay inyección SQL en la aplicación, así que es defensa en profundidad.

**Corrección**
- Crear `tecni_app` con `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, LOCK TABLES` solo sobre `tecni_systemas.*`, sin `FILE`, `SUPER` ni `GRANT`.
- Usar un usuario de respaldo aparte con `SELECT, LOCK TABLES, SHOW VIEW, TRIGGER`.

### S-08. Respaldos en claro y operaciones destructivas sin reautenticación

**Evidencia**
- `BackupDatabase` genera `.sql` y `.zip` sin cifrar, con la BD completa: hashes, cédulas y datos financieros. Si hay `GOOGLE_DRIVE_BACKUP_PATH`, los copia a la carpeta sincronizada.
- `BackupController@download`, `@restore`, `@destroy` y `@gitUpdate` solo exigen rol admin más CSRF.
- `gitUpdate` ejecuta `git pull --ff-only` y luego `migrate --force` desde la web.

**Escenario**
- Basta una sesión de admin robada o abandonada para descargar la BD o restaurar una copia vieja. Lo segundo pierde datos, aunque se crea un respaldo de seguridad.
- Si alguien compromete la cuenta de GitHub, su código se despliega con el siguiente clic del admin.
- Sincronizar con Drive expone la BD si se compromete esa cuenta de Google.

**Corrección**
- Cifrar los ZIP (`ZipArchive::setEncryptionName(..., ZipArchive::EM_AES_256, $clave)`) con una clave guardada fuera del respaldo, y guardar los `.sql` dentro de un ZIP cifrado.
- Exigir la contraseña del admin (`current_password`) en descargar, restaurar, eliminar y `git-update`, con su propio `RateLimiter`.
- Para `git-update`, verificar commits firmados o limitarlo a una etiqueta o rama de releases.

---

## BAJA

### S-09. Bloqueo por cuenta basado en el texto escrito

**Evidencia.** `AuthController.php:63-64,177-179`: la clave es `sha1($inputNormalized)`. Con 10 fallos en 15 min, bloquea 5 min.

**PoC.** `test_s06_bloqueo_de_cuenta_por_terceros_y_evasion_por_alias` (OK):
- 10 intentos desde 10 IP distintas con `admin` bloquean al admin legítimo, aunque use otra IP y la clave correcta.
- Con `administrador@tecnisystemas.com` el contador está en cero, así que hay 10 intentos más por cada alias (`admin`, `administrador` y el correo).

**Corrección.** Calcular la clave sobre `$user->id` una vez resuelto el usuario (o `sha1('unknown|'.$inputNormalized)` si no existe). Opcionalmente, un retraso progresivo en lugar del bloqueo total para la cuenta admin.

### S-10. "Recordarme" de 400 días

**Evidencia.** `login.blade.php:75` muestra la casilla `remember`, y `Auth::login($user, $request->filled('remember'))` la aplica. Laravel usa por defecto 576.000 min (400 días), lo que anula `expire_on_close` y `SESSION_LIFETIME=120`.

**Corrección.** Ajustar `'remember' => 60*24*7` en `config/auth.php` (guard web, soportado por Laravel 11+), o retirar la casilla para los admin.

### S-11. Gates de admin sin aplicar

**Evidencia**
- `AppServiceProvider.php:97-104` define `view-financial-reports`, `anular-operacion`, `manage-users` y `manage-backups`, y **ningún** controlador los usa. Los controladores hacen comprobaciones manuales de `role`.
- PoC `test_s12_gate_financiero_no_aplicado` (OK): `$tec->can('view-financial-reports') === false`, pero `reportes.financiero.diario` responde 200 al técnico.

**Corrección.** Decidir la regla de negocio y luego aplicar `Gate::authorize(...)` en `ReporteFinancieroController` o borrar los Gates muertos para que no induzcan a error.

### S-12. "Editar requiere contraseña de admin" solo se cumple en parte

**Evidencia.** `routes/web.php:37` lo afirma, y se aplica en estos casos:
- Mantenimiento y Electrónica: `update`.
- Caja: `update`.
- Facturas: `updateFactura`.
- Stock: solo al cambiar la cantidad.
- Cierre de caja.

No se aplica en `clientes`, `equipos`, `tecnicos`, `proveedores`, `cotizaciones`, `conceptos` ni `stocks/categorias`.

**Escenario.** Un técnico cambia la identificación o el correo de un cliente. Si un usuario invitado tiene ese correo, el cambio afecta a lo que ese usuario puede ver, porque `MantenimientoPolicy@view` usa `Cliente::where('email', $user->email)`.

**Corrección.** Alinear la documentación con el comportamiento, o aplicar `autorizarOperacionSensible` a los `update` de catálogos sensibles, como mínimo al email y la identificación de `Cliente`.

### S-13. Cabeceras y proxies

**Evidencia y corrección**
- No hay `trustHosts`. Con `trustProxies` para `127.0.0.1` (ngrok), `X-Forwarded-Host` se respeta y las URL absolutas y las redirecciones toman el host recibido. **Corrección:** `$middleware->trustHosts(at: ['tecnisystemas.local', '*.ngrok-free.app'])`.
- `SecurityHeaders` y `PreventBackHistory` usan `method_exists($response, 'header')`, que es falso para `BinaryFileResponse` y `StreamedResponse` (comprobado). Las descargas de respaldos y de Excel salen sin `nosniff`, sin CSP y sin `no-store`. **Corrección:** usar `$response->headers->set(...)` sin esa condición.
- `X-XSS-Protection: 1; mode=block` está obsoleto. **Corrección:** poner `0` o eliminarlo.
- Las fuentes de Google (`layouts/app.blade.php:68-71`) filtran IP y Referer a terceros. **Corrección:** alojarlas en local.

### S-14. Secretos y artefactos en el servidor

**Evidencia y corrección**
- `.env.backup` (ignorado por git y excluido de los respaldos de código) contiene `APP_ENV=local`, `APP_DEBUG=true` y todos los secretos. Si alguien lo copia sobre `.env`, la depuración queda activa con exposición pública. **Corrección:** borrarlo o moverlo fuera del proyecto.
- `.env` conserva `*_DEFAULT_PASSWORD` publicadas (S-01). **Corrección:** vaciarlas.
- Laravel Dusk está instalado en producción (aviso de preflight). El proveedor solo se carga en local o testing con `DUSK_ENABLED`, así que es bajo. **Corrección:** `composer install --no-dev`.

### S-15. Las pruebas contaminan los datos de producción

**Evidencia**
- `storage/logs/laravel-2026-11-*.log` mezcla cientos de líneas `testing.*` con las de producción, por ejemplo 452 `Contraseña de administrador incorrecta`. Eso dificulta detectar ataques reales.
- La prueba de restauración copia multimedia a `storage/app/public` real.

**Corrección.** En `phpunit.xml`, `LOG_CHANNEL=null` y un `storage` temporal (ver S-02).

### S-16. Errores 500 por entrada no validada

**Evidencia.**
- `ReporteFinancieroController.php:28,299,303,565,566` y `CierreCajaController.php:37` hacen `Carbon::parse($request->...)` sin `date`. El log muestra `Could not parse 'xyz'`.
- `MantenimientoStockController::destroy(int $stock_id)` lanza `TypeError` si recibe una cadena.

**Corrección.** Validar con `'fecha' => 'nullable|date'` y `whereNumber('stock_id')` en la ruta.

---

## Controles verificados como correctos

- **Login.**
  - Regenera la sesión antes de `Auth::login` (no hay fijación de sesión).
  - Usa el mismo mensaje para usuario inexistente y para contraseña errónea.
  - `DUMMY_HASH` es un bcrypt válido de coste 12: `password_verify` tarda 0,245 s, frente a 0,239 s con un hash real. No hay oráculo de tiempo.
  - El mensaje "cuenta desactivada" solo aparece con la contraseña correcta.
  - Hay bloqueo progresivo por IP+cuenta y por IP.
  - `logout` invalida la sesión y regenera el token.
- **RequirePasswordChange.** Se aplica a todo el grupo `auth`, y antes que `role`. Responde 403 JSON a AJAX. Solo exceptúa `password.cambiar`, `password.actualizar` y `logout`. El invitado no puede consultar mientras tenga el cambio pendiente. `/up` y `/login` quedan fuera, como corresponde.
- **Sesión** (`config/session.php` y `.env`): driver database, `encrypt=true`, `secure=true`, `http_only`, `same_site=lax`, `expire_on_close`, serialización `json` (no `php`) y 120 min.
- **Usuario desactivado.** `CheckRole` lo expulsa en la siguiente petición. PoC `test_control_usuario_desactivado_es_expulsado` OK.
- **Autorización por rol.** Revisadas las 155 rutas:
  - El invitado solo llega a `dashboard` (redirige), `guest/*`, `consulta/*`, `mantenimientos|electronicas/{id}` y `/factura`, `cambiar-password`, `logout` y `dismiss-alert`.
  - `show` y `factura` aplican `Gate::authorize('view')` con la lista de la sesión. PoC `test_control_invitado_no_ve_orden_no_consultada`: 403.
  - Las acciones de admin (respaldos, configuración, eventos y lista o alta de usuarios) comprueban el rol en el controlador. PoC `test_control_tecnico_sin_funciones_de_admin` OK.
  - Anular, revertir y eliminar exigen a un técnico la contraseña de admin, con límite de 10 intentos cada 15 min.
  - `UserController` impide que un técnico edite a otros o cambie su propio rol, y exige la contraseña actual para cambiar el correo propio.
- **Inyección SQL.** Todos los `*Raw` usan literales o enlaces (`?`). No hay `orderBy` ni nombres de columna que vengan de la entrada. El `LIKE` con `%` del usuario solo amplía la búsqueda (admin o técnico) y no es inyectable. No hay `$request->all()` en asignación masiva.
- **XSS.**
  - Los únicos `{!! !!}` son `nl2br(e(...))` y textos de paginación.
  - Los `{{ }}` dentro de `onclick` son id, rutas o `json_encode` (seguro dentro del atributo escapado).
  - Las asignaciones a `innerHTML` con datos usan `escapeHtml`: eventos, filas de compra, venta y cotización, y el toast.
  - La lista de respaldos interpola nombres de archivo generados por el servidor.
- **CSRF.** No hay exclusiones (`bootstrap/app.php`). Las mutaciones son POST, PUT o DELETE. Los GET no cambian datos, salvo la lista de consultas autorizadas en la sesión del invitado.
- **Comandos del sistema.**
  - `exec`, `proc_open` y `popen` solo reciben valores de configuración y `escapeshellarg` (git).
  - La contraseña de MySQL va por `MYSQL_PWD`, no por la línea de órdenes.
  - `openFolder` está deshabilitado fuera de local y testing.
- **Path traversal.**
  - En descargar, restaurar y eliminar, el nombre se valida con `^[a-zA-Z0-9_\-\.]+$` más `..`.
  - El ZIP se revisa contra Zip Slip (`..`, `/`, `\`, `:`, NUL).
  - La multimedia restaurada se filtra con una lista blanca de extensiones (el log registra `webshell.php` ignorado).
- **Subidas.**
  - Logo y fotos usan `mimes:` (detección por contenido) y `max:5120`.
  - `store()` asigna un nombre aleatorio con la extensión deducida del contenido, en el disco `public`.
  - No se aceptan SVG.
- **PDF.** dompdf con `enable_php=false`, `enable_remote=false`, `enable_javascript=false` y `chroot=base_path()`.
- **Auditable.** Enmascara `password`, `remember_token`, `email`, `identificacion`, `telefono`, `movil` y `direccion`. La vista de eventos además omite `password` y `remember_token`.
- **Logs.** No hay contraseñas, `MYSQL_PWD` ni `APP_KEY` en `storage/logs` (búsqueda con `grep`).
- **Respaldos de código.** Excluyen `.env*` (salvo `.env.example`), `auth.json`, `*.key/pem/sqlite`, `bootstrap/cache`, `storage/logs` y las sesiones.
- **Dependencias.**
  - `composer audit`: *No security vulnerability advisories found*.
  - `npm audit`: *found 0 vulnerabilities*.
  - Versiones: laravel/framework 13.40.0, symfony/http-foundation y http-kernel 8.1.11, dompdf 3.1.6, php-svg-lib 1.0.2, maatwebsite/excel 3.1.70, phpoffice/phpspreadsheet 1.30.7, chart.js 4.6.1, flatpickr 4.6.13 y tom-select 2.3.1.
  - Ninguna figura en avisos conocidos según la base de Packagist/GitHub que consultan `composer audit` y `npm audit`. No contrasté manualmente cada CVE.

## Revisado

- `routes/web.php` y `bootstrap/app.php`.
- Middleware: `CheckRole`, `RequirePasswordChange`, `SecurityHeaders`, `PreventBackHistory`.
- Controladores: `AuthController`, `PasswordController`, `UserController`, `BackupController` (completo), `GuestController`, `ConfiguracionController`, `EventoController` (permisos).
- `MantenimientoController` (`index`, `show`, `factura`, `consulta`), `ElectronicaController` (permisos y consulta), y los `update` de Cliente, Equipo, Tecnico, Stock, Proveedor, Cotizacion, ConceptoCaja, CategoriaStock, MovimientoCaja y CierreCaja.
- `AnulacionService`, los traits `HandlesAbono`, `HandlesStockAttach` y `Auditable`, `Evento::registrar`, las Policies y `AppServiceProvider`.
- `BackupDatabase` (mysqldump, ZIP, rotación y retención) y `PasswordsConocidas`.
- Configuración: `config/session.php`, `filesystems.php`, `dompdf.php` y `backup.php`; `.env.example`, `.env` y `.env.backup` (solo claves y longitudes, sin imprimir valores); `phpunit.xml` y `phpunit-integration.xml`; `start-ngrok.bat`.
- `storage/logs/*` (búsqueda de datos sensibles).
- En `resources/views`: todos los `{!! !!}`, los `{{ }}` en manejadores en línea, los `innerHTML` (66), `eventos/index`, la lista de respaldos y las plantillas de filas de compra, venta y cotización.
- `tests/Feature/BackupManagementTest.php` y `BackupEventTest.php` (efecto sobre los respaldos reales).

## No alcanzado o no revisado a fondo

- La lógica interna de `MovimientoInventarioController`, `CotizacionController`, `MovimientoCajaController` y `CierreCajaController`, más allá de la autorización (le corresponde a la auditoría financiera).
- Exportaciones Excel y PDF, más allá de las opciones de dompdf.
- `resources/js/app.js` completo y cada vista Blade línea por línea. Revisé los patrones de riesgo, no todo el código.
- La integridad SRI de `public/vendor` contra los archivos de origen.
- Comandos `CleanDatabase`, `SeedDemoData`, `ResetTests`, `SetupDatabase` y `SecurityCheck` (solo `grep` de `DB::statement`).
- La configuración real del servidor web de ServBay (raíz del documento, TLS) y la de ngrok. Tampoco verifiqué si el repositorio de GitHub es público.
- `/up` y el comportamiento real de ngrok con `X-Forwarded-*` enviados por el cliente (no se probó contra el túnel).
