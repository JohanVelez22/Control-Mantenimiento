# Evaluación final

Fecha: 2026-11-13.

**Base de la evaluación:** 9 informes especializados con evidencia (pruebas de demostración, `EXPLAIN` sobre la base real en solo lectura, documentos generados y leídos de verdad) y las correcciones de esta ronda, cada una con una prueba que falla con el código anterior.

**Lo que no se hizo:** no se ejecutaron pruebas de carga, no se probó la concurrencia real en MySQL, no se imprimió en impresora térmica y no se usó Dusk.

## 1. ¿Se puede desplegar a pruebas? → **B. APTO CON RESTRICCIONES**

### Por qué no es C (no apto)

Ninguno de los fallos críticos que encontraron los agentes sigue abierto en el código:

| Fallo crítico | Estado |
|---|---|
| Las pruebas borraban respaldos reales | Corregido |
| Pagos duplicados o inventados por texto | Corregido |
| Cierres alterables a posteriori | Corregido |
| Dobles envíos que duplicaban ventas y abonos | Corregido |
| El invitado veía saldos | Corregido |
| La rotación podía borrar todos los respaldos | Corregido |

Además, **la recuperación está demostrada**: se restauró un respaldo real en una base temporal y coincidió tabla por tabla y en las sumas de dinero.

### Por qué no es A (apto sin restricciones)

1. **Las 3 cuentas sembradas siguen con contraseñas publicadas** (S-01). La herramienta para cambiarlas desde la consola ya existe, pero el dueño todavía no la ha ejecutado. Mientras tanto, cualquier exposición en red permite tomar la cuenta de administrador.
2. **La migración nueva está escrita y probada, pero no se aplicó** en la base real. En la base actual no cambia ninguna fila.
3. **No existe la tarea programada de Windows**: no hay respaldos automáticos ni verificación semanal, y no hay copia fuera del disco C:.
4. Hay limitaciones que afectan a las **pruebas financieras integrales**:
   - La plantilla A4 de media hoja se rompe con muchos ítems (X-02).
   - Los bloqueos de concurrencia solo se probaron en MySQL real para el cierre de caja (D-08); el resto, no (Q-04).

### Restricciones para las pruebas

- Probar en un entorno aislado: una copia de la base restaurada con `app:verificar-respaldo`, o SQLite, y **sin ngrok**.
- Antes de exponer el sistema a cualquier red, ejecutar `app:restablecer-password` para las 3 cuentas.
- Los resultados de Inventario y de los totales filtrados se contrastan contra la Caja, que es la fuente correcta.

### Condiciones obligatorias antes de PRODUCCIÓN

Estar listo para pruebas **no** certifica el sistema para producción.

1. Contraseñas propias en las 3 cuentas y las `*_DEFAULT_PASSWORD` vacías en `.env`.
2. `php artisan migrate --force` aplicado, con respaldo previo verificado.
3. Tarea programada creada, y al menos una semana de respaldos automáticos más una ejecución de `app:verificar-respaldo` en verde.
4. Copia fuera del equipo (Drive o disco externo), con una restauración de prueba en otra máquina siguiendo `GUIA_RESPALDOS.md` 5(c).
5. Usuario MySQL propio sin privilegios globales (D-07).
6. ~~Reglas de cierre de órdenes (B-01) y bloqueo de repuestos en órdenes cerradas (B-03).~~ Hecho el 2026-11-13.
7. ~~Totales de documentos iguales a los de la pantalla (X-04, X-05, X-06)~~ (hecho el 2026-11-13). Pendiente: plantilla A4 que no se rompa con muchos ítems (X-02).
8. `start-ngrok.bat` fuera del uso en producción, y HTTPS si el sistema sale de la red local.
9. Una semana de **operación en paralelo** con el método actual, cuadrando la caja diaria contra el sistema.
10. `php artisan app:preflight` sin bloqueantes.

## 2. Conclusión sobre su futuro (sección 22)

**1. ¿Tiene una buena arquitectura?**
Para un sistema de un taller, es correcta y por encima del promedio en el backend financiero: transacciones, bloqueos, secuencias atómicas, FK `RESTRICT`, auditoría y 628 pruebas. Como arquitectura de producto no es buena:
- la autorización está repartida en tres mecanismos incoherentes;
- Mantenimiento y Electrónica están duplicados;
- hay lógica de negocio en las vistas;
- `layouts/app.blade.php` tiene 2.265 líneas y el CSS lleva 3.926 `!important`.

Notas del agente 1: organización 5/10, mantenibilidad 4/10, escalabilidad 5/10, reutilización 3/10, documentación 4/10, backend 6/10.

**2. ¿Qué porcentaje se puede reutilizar razonablemente?**
Medido archivo por archivo sobre 314 archivos y 60.031 líneas de código propio (`ANALISIS_REUTILIZACION.md` §3.2):

| Categoría | Líneas | Archivos | Comentario |
|---|---|---|---|
| Sin cambios | **10,6 %** | 18 % | ≈8 % si se excluye `config/` estándar |
| Con adaptación | **34 %** | 17 % | |
| No reutilizable (negocio del taller) | **55 %** | 65 % | |

Razonablemente reutilizable: **≈40-45 % de las líneas**. Casi tres cuartas partes de esa cifra están mezcladas con negocio y hay que **extraerlas**, no copiarlas.

**3. ¿Qué módulos pueden ser componentes comunes?**
- Autenticación con bloqueo escalonado.
- Cambio obligatorio de contraseña y `PasswordsConocidas`.
- Contraseña de supervisor con límite de intentos (`AnulacionService::autorizarOperacionSensible`).
- Auditoría (`Auditable` + `Evento`).
- Secuencias de documentos (`OrdenService`).
- Respaldos, rotación por tipo y `app:verificar-respaldo`.
- `app:preflight` y `app:verificar-migraciones`.
- Idempotencia de formularios (`IdempotenciaFormulario`).
- PDF seguro (`PaginacionPdf`, dompdf sin PHP ni JavaScript) y Excel seguro (`SafeValueBinder`).
- Middleware de seguridad y CSS glass (tokens, formularios, tablas, modales).
- `ColombiaHelper`, como módulo de localización.

**4. ¿Qué impide reutilizarlo hoy?**
- Identidad fija en el código: "WolfCore", `@tecnisystemas.com` y el logo.
- Roles como cadenas fijas, con 119 comparaciones manuales.
- Menú y notificaciones fijos en el layout y en `AppServiceProvider`.
- Duplicación entre Mantenimiento y Electrónica.
- Archivos gigantes.
- Rutas de ServBay para Windows triplicadas.
- Documentación contradictoria.

**5. Refactorizaciones indispensables**
Indispensables para reutilizarlo (fases 1 y 2 del plan):
- identidad configurable;
- permisos declarativos y en uso real;
- menú y notificaciones registrables por módulo;
- un solo resolvedor de binarios;
- sacar el DDL y el negocio del `AppServiceProvider`;
- partir el layout.

Indispensable para el dominio: unificar las órdenes (fase 4).

**6. ¿Conservar la arquitectura o reconstruir el núcleo?**
Conservar el stack, el modelo de datos y los módulos de negocio, y **reconstruir el núcleo por extracción**. Reescribir desde cero tiraría el activo más valioso del proyecto: las reglas financieras ya endurecidas y sus 628 pruebas.

**7. ¿Puede ser una plantilla para futuros proyectos?**
**Sí, pero no tal como está.** El veredicto es (c): necesita una refactorización importante. Tras las fases 1 y 2 (≈2-3 semanas) ya puede servir de punto de partida para un segundo sistema de gestión con caja, inventario y documentos.

**8. Ventajas de usarlo como base**
- Stack actual y sin vulnerabilidades conocidas (`composer audit` y `npm audit` limpios).
- Protecciones financieras probadas: idempotencia, período monótono, bloqueos, anulación con intención explícita y vínculos por clave.
- Respaldos verificables.
- Seguridad de sesión cuidada.
- Una suite grande que actúa como red de seguridad.
- Interfaz en español ya pensada para operarios.

**9. Riesgos de usarlo como base**
- Arrastrar la deuda de interfaz (CSS con `!important`, JavaScript inline) y la duplicación de órdenes.
- Acoplarse a Windows/ServBay.
- Que el reemplazo masivo de la identidad deje restos.
- Que las 628 pruebas den una falsa sensación de cobertura: hay escenarios de concurrencia y de documentos que no cubren.

**10. ¿Qué falta para considerarlo un prototipo profesional?**
- Los pendientes P0 de `PENDIENTES.md`.
- Las reglas de cierre de órdenes.
- Totales de documentos coherentes.
- Pruebas de concurrencia en MySQL.
- Sistema de diseño unificado y búsqueda en el servidor.
- Usuario de base de datos con mínimo privilegio.
- Respaldos cifrados con copia externa.
- Documentación única y vigente (esta carpeta debe reemplazar a los documentos antiguos).

**11. ¿Se puede desplegar a pruebas ahora?**
Sí, **con las restricciones de la sección 1** (categoría B).

**12. ¿Qué corregir antes de producción?**
Las 10 condiciones de la sección 1, en ese orden.

## 3. Respuesta directa: ¿vale la pena como punto de partida?

**Sí, como punto de partida del núcleo, no como plantilla para copiar.** Lo difícil y caro de un sistema empresarial con dinero ya está razonablemente resuelto y probado:
- integridad contable;
- concurrencia básica;
- auditoría;
- respaldos verificables;
- seguridad de sesión.

Lo que falta (identidad configurable, permisos, módulos registrables, interfaz unificada) es trabajo conocido, acotado y de bajo riesgo si se hace por extracción con la suite como red.

La alternativa de empezar de cero costaría más que esas 2-3 semanas y volvería a pasar por los mismos errores financieros que esta auditoría ya encontró y corrigió.
