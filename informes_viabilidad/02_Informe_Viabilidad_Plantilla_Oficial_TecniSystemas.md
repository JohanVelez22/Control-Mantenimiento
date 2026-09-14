# INFORME DE VIABILIDAD DE PROYECTO DE SOFTWARE
**Asignatura:** Gestión de Proyectos de Software | Periodo 2026-2  
**Institución:** CIAF Educación Superior  
**Docente:** Ing. Diego Fernando Londoño  
**Equipo de Proyecto / Estudiantes:**  
- Johan Vélez (Líder Técnico / Desarrollador Backend)  
- Valentina Cobariza (Diseñadora UI/UX / Desarrolladora Frontend)  
- Santiago Zapata (Modelador de Datos / Especialista en Base de Datos y QA)  
**Proyecto:** Sistema Integral para el Control de Mantenimientos, Taller de Electrónica, Stock y Caja — **TECNI-SYSTEMAS**  
**Organización Cliente:** Tecni Systemas (Cra 4 # 20-81, Pereira, Risaralda - NIT 4.501.927)  
**Fecha:** 13 de Septiembre de 2026  

---

## PORTADA
- **Título del Informe:** Estudio e Informe de Viabilidad Técnica, Económica y Operativa  
- **Nombre del Proyecto:** TECNI-SYSTEMAS — Plataforma de Gestión de Servicios Técnicos, Almacén y Control de Caja  
- **Equipo de Proyecto / Autores:**  
  - Johan Vélez (Líder de Proyecto / Desarrollador Backend)  
  - Valentina Cobariza (Diseñadora UI/UX / Desarrolladora Frontend)  
  - Santiago Zapata (Modelador de Datos / DBA & QA)  
- **Docente Titular:** Ing. Diego Fernando Londoño  
- **Entidad:** CIAF Educación Superior — Escuela de Tecnología e Ingeniería  
- **Ciudad y Fecha:** Pereira, Risaralda — Septiembre 13 de 2026

---

## ÍNDICE DE CONTENIDO
1. [Resumen](#resumen)
2. [1. Presentación del Proyecto](#1-presentación-del-proyecto)
   - 1.1. Contexto Empresarial y Diagnóstico
   - 1.2. Ventajas del Proyecto y Áreas Beneficiadas
   - 1.3. Impacto en Productividad, Agilidad y Reducción de Costos
3. [2. Usuarios que Intervienen en el Proyecto (Stakeholders)](#2-usuarios-que-intervienen-en-el-proyecto)
4. [3. Viabilidad Técnica](#3-viabilidad-técnica)
   - 3.1. Inventario y Estado de Infraestructura Actual
   - 3.2. Infraestructura Requerida y Costo de Incremento
   - 3.3. Procedimiento para Migración a la Nueva Base de Datos
   - 3.4. Conectividad LAN/Wi-Fi, Respaldo Eléctrico (UPS) y Seguridad
5. [4. Viabilidad Económica](#4-viabilidad-económica)
   - 4.1. Estructura de Costos de Mano de Obra (Horas/Días/Semanas)
   - 4.2. Gastos Operativos, Viabilidad y Adecuaciones
   - 4.3. Costo Total de Producción y Reserva de Contingencias
   - 4.4. Margen de Utilidad, Análisis Financiero y Retorno de Inversión (ROI)
6. [5. Viabilidad Operativa](#5-viabilidad-operativa)
   - 5.1. Cronograma de Despliegue y Puesta en Marcha
   - 5.2. Tabla de Entregas Parciales (Betas) y Plan de Capacitación
   - 5.3. Factibilidad con Infraestructura Actual vs Mejorada
7. [6. Recomendaciones y Conclusiones](#6-recomendaciones-y-conclusiones)
   - 6.1. Recomendaciones al Cliente y a la Organización
   - 6.2. Conclusiones del Estudio
   - 6.3. Dictamen Final

---

## RESUMEN
El presente informe de viabilidad evalúa la factibilidad técnica, económica y operativa para el diseño, desarrollo e implementación del software **TECNI-SYSTEMAS** en la empresa homónima con sede en Pereira, Risaralda. Actualmente, la organización experimenta cuellos de botella en la administración de órdenes de trabajo de computadores y tarjetas electrónicas, descontrol en el inventario de repuestos y discrepancias en los arqueos diarios de caja debido al uso de registros físicos y hojas de cálculo no integradas.

**Objetivo General:**  
Demostrar con rigor analítico y metodológico que la implementación de un sistema web integral cliente-servidor basado en PHP 8.3 / Laravel 11 y MySQL es completamente viable y factible, garantizando la centralización de datos, la trazabilidad de diagnósticos, el descuento automatizado de existencias, la seguridad financiera mediante doble factor de clave administrativa para anulaciones y un retorno de inversión (ROI) proyectado superior al 35% durante el primer año.

---

## 1. PRESENTACIÓN DEL PROYECTO

### 1.1. Contexto Empresarial y Diagnóstico
**Tecni Systemas** (NIT 4.501.927, Cra 4 # 20-81 Pereira) es una microempresa especializada en el soporte técnico de equipos de cómputo, diagnóstico y microelectrónica (reparación de placas madre, circuitos de carga y celulares), y comercialización de accesorios y repuestos tecnológicos. 

El diagnóstico situacional reveló las siguientes vulnerabilidades críticas:
- **Pérdida de trazabilidad de garantías:** Se dificulta comprobar si una pieza fue cambiada en el local o si el periodo de garantía técnica ha expirado.
- **Fuga de repuestos en taller:** Los técnicos utilizan partes de stock sin registrar la salida de inmediato en el sistema, lo que genera diferencias entre el inventario físico y contable de hasta un **18% mensual**.
- **Descuadres en caja y falta de conciliación:** Los abonos dados por clientes para reparaciones complejas se anotan en recibos manuales, lo que causa errores recurrentes en los cierres de caja del final del día.
- **Tiempos muertos en cotización:** La elaboración de cotizaciones para clientes corporativos toma entre 24 y 48 horas por falta de una base de datos centralizada de tarifas y piezas.

### 1.2. Ventajas del Proyecto y Áreas Beneficiadas
La implantación del software proporciona ventajas operacionales tangibles:
- **Centralización en tiempo real:** Toda la información técnica, financiera y de inventario reside en un único repositorio seguro.
- **Módulo Dual de Servicio:** Gestión diferenciada para mantenimiento preventivo/correctivo estándar y laboratorio de microelectrónica especializada.
- **Doble Factor de Autorización:** Las anulaciones de cobros o bajas de mercancía requieren validación de contraseña de administrador, eliminando fraudes internos.
- **Reportes Financieros Automatizados:** Emisión instantánea de Reporte Diario, Reporte Acumulado y Reporte de Operaciones en PDF/Excel.

**Áreas y Usuarios Beneficiados:**
1. *Área de Recepción y Atención:* Registro veloz de clientes (con búsqueda automática de municipios), equipos y emisión de comprobantes de ingreso en formatos Ticket (80mm) y Media Carta.
2. *Taller de Mantenimiento de Cómputo:* Asignación ordenada de técnicos, diagnóstico estructurado y vinculación directa de repuestos.
3. *Laboratorio de Microelectrónica:* Registro pormenorizado de fallas de componentes, pruebas de circuito y control de abonos progresivos.
4. *Almacén / Bodega:* Control de stock mínimo con alertas visuales, registro de compras a proveedores con actualización de costo medio y bajas justificadas.
5. *Gerencia y Contabilidad:* Visibilidad financiera en tiempo real, arqueos de caja transparentes e historial de auditoría de eventos.
6. *Clientes Finales:* Posibilidad de consulta web de estado de su equipo y factura digital mediante portal de consulta con throttling de seguridad.

### 1.3. Impacto en Productividad, Agilidad y Reducción de Costos
- **Incremento de Productividad:** Aumento estimado del **40%** en la cantidad de órdenes gestionadas por técnico al día al suprimir el diligenciamiento manual de planillas.
- **Agilidad Operativa:** Reducción del tiempo de emisión de cotizaciones y facturas de 30 minutos a **menos de 2 minutos**.
- **Reducción de Costos:** Eliminación casi total (reducción de más del **92%**) de pérdidas de repuestos sin facturar y cero discrepancias contables en caja menor.

---

## 2. USUARIOS QUE INTERVIENEN EN EL PROYECTO

La arquitectura del sistema y su control de acceso basado en roles (middleware `CheckRole` en Laravel) define de manera estricta **tres (3) roles de usuario**: `admin`, `tecnico` e `invitado`. A partir de ellos y de los actores externos del negocio, se estructura la siguiente matriz de usuarios:

| Rol del Sistema / Actor | Perfil y Funciones en Tecni Systemas | Responsabilidades Clave en el Software |
| :--- | :--- | :--- |
| **1. Rol Administrador (`admin`)** | **Administrador General / Propietario (Sponsor):** Dirección estratégica, supervisión integral de caja y almacén, fijación de tarifas y auditoría contable. | Acceso total al sistema. Es el único perfil facultado para ingresar su contraseña maestra y autorizar anulaciones críticas (servicios, facturas, caja, bajas de inventario), supervisar arqueos diarios y emitir reportes acumulados. |
| **2. Rol Técnico (`tecnico`)** | **Técnico Operativo (Hardware, Electrónica y Mostrador/Caja):** Diagnóstico y reparación de equipos de cómputo y placas electrónicas, y atención técnica en mostrador. | Registra recepción de equipos, diagnósticos, insumos y repuestos descontados de stock, cotizaciones, y la operación de cobros/abonos de servicios y ventas directas en caja. No puede anular transacciones sin autorización de Administrador. |
| **3. Rol Invitado (`invitado`)** | **Clientes Externos (Consulta Pública):** Clientes naturales o corporativos que entregan sus equipos en reparación. | Acceden a un portal web liviano protegido (con limitador de 30 consultas/minuto) donde pueden consultar en tiempo real el avance de su equipo por serial o número de orden y ver su comprobante digital. |
| **Actor Externo (Catálogo)** | **Proveedores de Repuestos e Insumos:** Aliados comerciales que suministran partes, circuitos y accesorios. | Registrados en el catálogo maestro para asociar facturas de compra, plazos de crédito, precios de entrada y actualización de costos medios. |

---

## 3. VIABILIDAD TÉCNICA

### 3.1. Aspectos Técnicos de Hardware y Software
El proyecto se basa en una arquitectura cliente-servidor probada:
- **Backend:** PHP 8.3 con framework Laravel 11/13, aplicando el patrón Modelo-Vista-Controlador (MVC), middleware de seguridad (`prevent-back-history`, `CheckRole`, `throttle`) y transacciones ACID.
- **Frontend:** Laravel Blade, diseño visual personalizado con Glassmorphism (`glass.css`), CSS moderno responsivo y JavaScript Vanilla para interactividad asíncrona sin dependencias pesadas.
- **Base de Datos:** MySQL 8.0 / MariaDB bajo motor InnoDB con soporte íntegro de integridad referencial, índices compuestos de optimización y codificación `utf8mb4_unicode_ci`.
- **Librerías de Soporte:** DomPDF para generación de comprobantes en formatos Ticket (80mm) y Carta, y Maatwebsite Excel para reportes contables.

### 3.2. Inventario de Infraestructura Actual y Estado
| Elemento | Cantidad | Especificaciones Actuales | Estado | ¿Es Funcional para el Proyecto? |
| :--- | :---: | :--- | :---: | :---: |
| **PC Servidor / Principal** | 1 | Procesador Intel Core i5 10ª Gen, 16GB RAM DDR4, SSD NVMe 512GB | Excelente | **Sí**, posee potencia sobrada para alojar el servidor web local y la BD MySQL. |
| **PC Taller / Diagnóstico** | 2 | Intel Core i3 8ª Gen, 8GB RAM, SSD 240GB | Bueno | **Sí**, óptimo para operar el sistema vía navegador web. |
| **PC Mostrador / Recepción** | 1 | AMD Ryzen 3, 8GB RAM, SSD 256GB | Excelente | **Sí**, terminal idónea para caja, ventas y recepción. |
| **Impresora Térmica POS** | 1 | Xprinter 80mm conexión USB/LAN | Bueno | **Sí**, compatible con el módulo de tickets PDF de 80mm. |
| **Impresora Láser Carta** | 1 | HP LaserJet Pro M404n (Monocromática) | Muy Bueno | **Sí**, para facturas formales de media carta y cartas de entrega. |
| **Red de Datos** | 1 | Switch Fast-Ethernet 10/100 Mbps y Router Wi-Fi estándar | Regular | **Parcialmente**, requiere ampliación a Gigabit y cableado estructurado Cat 6. |
| **Protección Eléctrica** | - | Multitomas convencionales sin UPS en el puesto principal | Deficiente | **No funcional**, el servidor se expone a corrupción de BD ante cortes de luz. |

### 3.3. Necesidades de Infraestructura Nueva y Costo de Incremento
Para asegurar alta disponibilidad y protección de los datos de Tecni Systemas, se requiere la siguiente adecuación técnica:

1. **Sistema UPS Regulada de 850VA / 480W:**  
   Protege el PC servidor y switch contra microcortes y picos de voltaje frecuentes en la red eléctrica central de Pereira. Brinda 15-20 minutos de autonomía para cierre seguro de transacciones.  
   *Costo estimado:* **$320.000 COP**.
2. **Switch Gigabit Ethernet de 8 Puertos (10/100/1000 Mbps) + Patch Cords Cat 6:**  
   Garantiza comunicación ultrarrápida (1 Gbps) entre terminales y servidor local, eliminando latencia al generar reportes o facturar.  
   *Costo estimado:* **$180.000 COP**.
3. **Punto de Acceso Wi-Fi Doble Banda (2.4 / 5 GHz) Independiente:**  
   Permite separar la red interna del taller de la red Wi-Fi de cortesía para clientes, blindando la seguridad del servidor.  
   *Costo estimado:* **$180.000 COP**.
- **Costo Total de Infraestructura Nueva Requerida:** **$680.000 COP**.

### 3.4. Procedimiento para Migración a la Nueva Base de Datos
La empresa cuenta con datos dispersos en hojas de Excel y notas de clientes. Se diseñó el siguiente protocolo formal de migración en 5 fases:

1. **Extracción y Diagnóstico:** Levantamiento de todas las fuentes de datos existentes (listas de clientes habituales, catálogo de proveedores, inventario físico de repuestos y códigos de productos).
2. **Limpieza y Normalización (Data Cleansing):** Eliminación de registros duplicados, unificación de formatos telefónicos (10 dígitos colombianos), corrección de números de identificación (cédulas/NIT) y categorización estándar de repuestos (Tecnología, Repuestos, Accesorios, Servicios).
3. **Mapeo Relacional:** Conversión de tablas planas a entidades normalizadas en 3ra Forma Normal (3FN), vinculando llaves primarias y foráneas (`cliente_id`, `equipo_id`, `proveedor_id`, `categoria_id`).
4. **Carga Automatizada con Seeders:** Creación de scripts de carga controlada mediante migraciones y seeders en Laravel con validación de excepciones y transacciones reversibles en caso de fallos.
5. **Verificación y Prueba de Integridad:** Cruce de inventario físico real versus saldo cargado en la base de datos MySQL, validando que las existencias y saldos coincidan al 100% antes de habilitar operaciones.

---

## 4. VIABILIDAD ECONÓMICA

La evaluación de costos se realiza bajo la estructura financiera de la cátedra de Gestión de Proyectos de Software (Docente: Ing. Diego Fernando Londoño).

### 4.1. Base de Cálculo de Mano de Obra (Estándar CIAF)
- **Salario Base Mensual:** $2.200.000 COP
- **Jornada Mensual:** 210 horas laborales (42 horas semanales, 8 horas diarias)
- **Valor Hora de Programación:** $\$2.200.000 / 210 = \$10.476,19$ COP
- **Valor Día de Programación:** $\$10.476,19 	imes 8 = \$83.809,52$ COP
- **Valor Semana de Programación:** $\$10.476,19 	imes 42 = \$440.000,00$ COP

### 4.2. Inversión en Desarrollo de Software (8 Semanas)
El equipo de desarrollo integrado por **Johan Vélez** (Backend - 21 h/sem), **Valentina Cobariza** (Frontend/UX - 13 h/sem) y **Santiago Zapata** (Base de Datos/QA - 8 h/sem) suma una carga conjunta de 42 horas semanales (336 horas en 8 semanas):
$$	ext{Costo Mano de Obra} = 336	ext{ horas} 	imes \$10.476,19 = \$3.520.000,00	ext{ COP}$$

### 4.3. Presupuesto Consolidado de Costos
| Ítem / Concepto | Justificación / Detalle | Periodicidad / Unidad | Costo Total (COP) |
| :--- | :--- | :---: | :---: |
| **Mano de Obra Equipo Desarrollador** | 336 horas de ingeniería de software (Backend, Frontend, DBA) | 8 semanas | $3.520.000 |
| **Estudio de Viabilidad y Requisitos** | Fase previa de análisis, observación de campo y entrevistas | Pago único | $350.000 |
| **Infraestructura Nueva Requerida** | UPS regulada 850VA + Switch Gigabit + Red Wi-Fi doble banda | Pago único | $680.000 |
| **Servicios Públicos (Energía e Internet)** | Consumo operativo de desarrollo y servidores locales | $320.000/mes × 2 meses | $640.000 |
| **Alimentación y Transporte** | Viáticos del equipo durante jornadas de despliegue y pruebas | $180.000/mes × 2 meses | $360.000 |
| **Licencias y Plataforma Cloud** | Stack 100% Open Source (PHP, Laravel, MySQL, Linux/Windows) | Costo cero | $0 |
| **Subtotal Costos Directos e Indirectos** | Sumatoria de rubros de fabricación y adecuación | - | **$5.550.000** |
| **Reserva de Contingencias (10%)** | Fondo para imprevistos técnicos, variaciones o retrasos | 10% del subtotal | $555.000 |
| **COSTO TOTAL DE PRODUCCIÓN** | **Inversión base integral de fabricación del software** | - | **$6.105.000** |
| **Margen de Utilidad (35%)** | Margen comercial bruto que cubre impuestos de renta (35%) y ganancia | 35% de producción | **$2.136.750** |
| **PRECIO DE VENTA TOTAL DEL PROYECTO** | **Valor comercial para el cliente Tecni Systemas** | - | **$8.241.750** |

### 4.4. Justificación de Rentabilidad y Retorno de Inversión (ROI)
- **Ahorro Mensual Estimado:** Se estima que la empresa recuperará mensualmente un mínimo de **$1.450.000 COP** distribuidos en:
  - Recuperación de repuestos no facturados o extraviados: ~$650.000/mes.
  - Aumento de capacidad de atención y ventas en mostrador: ~$500.000/mes.
  - Ahorro en papelería física, talonarios y tiempo de liquidación contable: ~$300.000/mes.
- **Período de Recuperación (Payback):**  
  $$	ext{Payback} = rac{\$8.241.750}{\$1.450.000	ext{/mes}} pprox 5,7	ext{ meses}$$
El retorno de inversión se logra en **menos de seis meses**, lo cual califica la propuesta como altamente atractiva y de bajo riesgo financiero.

---

## 5. VIABILIDAD OPERATIVA

### 5.1. Tiempos de Implementación y Puesta en Marcha
El proyecto está programado para ejecutarse en un lapso total de **8 semanas (2 meses)**, distribuidas en cuatro fases iterativas e incrementales:
- **Semanas 1 y 2:** Modelado de base de datos, arquitectura base en Laravel, configuración de autenticación, roles y módulo de configuración empresarial.
- **Semanas 3 y 4:** Gestión de clientes, equipos, técnicos y catálogo de repuestos con control de bajas y proveedores.
- **Semanas 5 y 6:** Módulo de mantenimientos, órdenes de microelectrónica, cotizaciones y conexión con stock de repuestos.
- **Semanas 7 y 8:** Flujo de caja, abonos fraccionados, cierres diarios, reportes financieros, auditoría de eventos y pruebas finales de estrés.

### 5.2. Tabla de Entregas Parciales (Betas) y Plan de Capacitación
De acuerdo con el formato exigido, se detalla el cronograma de entregas parciales y capacitación:

| Nombre del Módulo / Versión | Fecha de Entrega | Fecha de Capacitación | Quiénes Recibirán la Capacitación |
| :--- | :---: | :---: | :--- |
| **Beta 1: Módulo Base y Catálogos**<br/>(Login, Clientes, Equipos, Técnicos, Proveedores) | 02 de Octubre de 2026 | 03 de Octubre de 2026 | Administrador General (`admin`) y Técnicos de Taller (`tecnico`). |
| **Beta 2: Módulo Técnico y Almacén**<br/>(Mantenimientos, Electrónica, Stock y Bajas) | 16 de Octubre de 2026 | 17 de Octubre de 2026 | Técnicos de Hardware y Electrónica (`tecnico`) y Administrador (`admin`). |
| **Beta 3: Módulo Financiero y Ventas**<br/>(Cotizaciones, Facturación, Abonos, Caja y Cierres) | 30 de Octubre de 2026 | 31 de Octubre de 2026 | Técnicos en mostrador (`tecnico`) y Administrador General (`admin`). |
| **Versión 1.0 Final: Sistema Completo**<br/>(Reportes Financieros, Eventos, Backup y Rollout) | 08 de Noviembre de 2026 | 09 de Noviembre de 2026 | Todo el personal operativo de Tecni Systemas (Administrador y Técnicos). |

### 5.3. Análisis de Factibilidad con Infraestructura Actual vs Nueva
- **¿Puede funcionar con la infraestructura actual sin actualizaciones?**  
  **Sí, pero de forma precaria.** El software está optimizado para ejecutarse en el servidor local existente (Core i5, 16GB RAM) usando el stack local ServBay/PHP-FPM/MySQL, y las terminales de cómputo tienen navegadores web actualizados. Sin embargo, operar sin switch Gigabit creará lentitud en la carga concurrente de imágenes de repuestos, y operar sin UPS dejará la base de datos vulnerable a cierres abruptos por cortes de energía que corrompan las tablas InnoDB de transacciones de caja.
- **Con la infraestructura nueva incorporada ($680.000 COP):**  
  El proyecto alcanza un nivel de operatividad y estabilidad del **100%**, garantizando continuidad ininterrumpida de servicio, velocidad de red de 1000 Mbps y protección total ante fallas eléctricas.

---

## 6. RECOMENDACIONES Y CONCLUSIONES

### 6.1. Recomendaciones al Cliente y Sobre la Empresa
Como analistas de sistemas, formulamos las siguientes recomendaciones indispensables para el éxito sostenido del proyecto:
1. **Disciplina en el Cierre Diario de Caja:** El cierre de caja (`cierre_cajas`) debe realizarse diariamente sin excepción a las 18:30 hrs al término de la atención al público, cuadrando el efectivo físico contra el reporte del sistema.
2. **Custodia de la Clave de Administrador:** La contraseña de anulación nunca debe compartirse con los técnicos ni recepcionistas. Cualquier anulación de servicio, abono o factura debe ser supervisada físicamente por la administración.
3. **Política de Backups Automatizados:** Configurar un script diario nocturno (`mysqldump`) que guarde copia encriptada de la base de datos en una unidad de almacenamiento externa y una copia en almacenamiento en la nube (Google Drive/OneDrive).
4. **Mantenimiento Preventivo de la UPS:** Realizar prueba de descarga y cambio de baterías de la UPS regulada cada 18 meses para garantizar la autonomía de respaldo del servidor.
5. **Registro Inmediato de Repuestos:** Prohibir la salida de componentes del estante de repuestos sin haber digitado previamente la orden de mantenimiento correspondiente en el software.
6. **Capacitación Continua:** Designar al Administrador como "campeón del sistema" para capacitar a cualquier nuevo técnico que ingrese a la empresa en el futuro.

### 6.2. Conclusiones del Estudio
1. Vimos una clara necesidad de modernización en Tecni Systemas para proteger los equipos en custodia, evitar descuadres de dinero en caja y mejorar el servicio a los clientes.
2. La arquitectura técnica seleccionada (PHP 8.3, Laravel 11, MySQL, HTML5/CSS Vanilla Glassmorphism) es madura, eficiente, no genera costos recurrentes de licenciamiento a terceros y es relativamente fácil de mantener a largo plazo.
3. El análisis financiero confirma que la inversión total ($8.241.750 COP) es justa, competitiva y altamente rentable para la organización, amortizándose en 5,7 meses.
4. El equipo humano de la empresa mostró excelente disposición durante las entrevistas, reduciendo al mínimo la resistencia al cambio cultural tecnológico.

### 6.3. Dictamen Final
En concordancia con todos los hallazgos técnicos, financieros, legales y operativos recopilados durante el levantamiento de información:

> **El Proyecto es Viable y Factible.**

**Equipo Responsable de la Formulación y Desarrollo:**
- **Johan Vélez** — Líder de Proyecto y Arquitecto Backend
- **Valentina Cobariza** — Desarrolladora Frontend y Diseñadora UI/UX
- **Santiago Zapata** — Especialista en Base de Datos y Aseguramiento de Calidad (QA)
