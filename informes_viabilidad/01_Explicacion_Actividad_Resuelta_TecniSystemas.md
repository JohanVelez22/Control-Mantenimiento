# GUÍA DE RESOLUCIÓN DE LA ACTIVIDAD: INFORME DE VIABILIDAD
**Asignatura:** Gestión de Proyectos de Software | Periodo 2026-2  
**Docente:** Ing. Diego Fernando Londoño (diego.londono@ciaf.edu.co | 3043942508)  
**Institución:** CIAF Educación Superior  
**Equipo de Proyecto / Estudiantes:**  
- Johan Vélez (Líder Técnico / Desarrollador Backend)  
- Valentina Cobariza (Diseñadora UI/UX / Desarrolladora Frontend)  
- Santiago Zapata (Modelador de Datos / DBA & QA)  
**Proyecto Seleccionado:** Sistema de Gestión y Control Integral para Taller de Mantenimiento, Electrónica, Stock y Caja — **TECNI-SYSTEMAS**  
**Fecha de Entrega:** Domingo 13 de Septiembre de 2026  

---

## INTRODUCCIÓN Y CONTEXTUALIZACIÓN
El presente documento da cumplimiento estricto y secuencial a los cinco (5) pasos solicitados en el instructivo *"Explicación de la actividad informe de viabilidad"*. Se parte de la caracterización de los requerimientos funcionales, no funcionales y stakeholders del sistema **Tecni-Systemas** (empresa ubicada en la Cra 4 # 20-81, Pereira, Risaralda, NIT 4.501.927), evaluando la suficiencia del alcance y formulando el marco técnico y económico del proyecto.

---

## PASO 1: IDEAS PRINCIPALES PARA EL INFORME DE VIABILIDAD
Para estructurar el informe de viabilidad del sistema **Tecni-Systemas**, se definieron las siguientes directrices estratégicas:

1. **Transformación Digital y Centralización Operativa:**
   - La empresa opera bajo un modelo mixto con altos riesgos de inconsistencia: recepción de equipos de cómputo y dispositivos electrónicos registrada en formatos de papel o notas físicas, y cotizaciones manuales.
   - La idea central es migrar hacia un sistema web centralizado (desarrollado sobre Laravel 11/PHP 8.3 y MySQL), eliminando la duplicidad de datos y los riesgos de pérdida de información de garantías.

2. **Trazabilidad Integral del Ciclo de Vida del Servicio:**
   - Cada equipo que ingresa debe tener un seguimiento cronológico transparente: *Recepción → Diagnóstico → Cotización → Autorización del Cliente → Asignación a Técnico Especialista (Mantenimiento General o Microelectrónica) → Registro de Repuestos Descontados de Inventario → Control de Abonos Monetarios → Liquidación/Facturación → Entrega al Cliente*.

3. **Control Financiero y Seguridad en Caja:**
   - Separación estricta entre la operación técnica y los movimientos monetarios.
   - Implementación de arqueos diarios obligatorios de caja (apertura, ingresos por servicios, ventas de mostrador, abonos parciales, compras a proveedores, egresos por conceptos parametrizados y saldo final de cierre).
   - Mecanismo de seguridad de doble validación: las anulaciones de movimientos financieros o bajas de inventario exigen confirmación administrativa con clave especial de seguridad.

4. **Retorno de Inversión y Reducción de Pérdidas Ocultas:**
   - Demostrar que el costo de producción y puesta en marcha del software se recupera en menos de seis meses gracias a la eliminación de pérdidas por repuestos extraviados o no cobrados, reducción de tiempos muertos de cotización y la posibilidad de que los clientes puedan consultar el estado de su equipo por internet.

---

## PASO 2: LISTADO DE REQUERIMIENTOS ADICIONALES (ANÁLISIS DE SUFICIENCIA)
Tras confrontar los requerimientos iniciales con la operativa real del negocio, se determinó que **los requerimientos iniciales no eran suficientes**, requiriéndose la incorporación de requerimientos complementarios críticos para garantizar la solidez y seguridad de la plataforma.

### 2.1. Requerimientos Funcionales Faltantes Incorporados
| Código | Módulo | Requerimiento Faltante Identificado | Justificación Técnica y de Negocio |
| :--- | :--- | :--- | :--- |
| **RF-ADD-01** | Seguridad / Auditoría | Doble factor de autorización para anulaciones críticas | Evita fraudes internos o errores accidentales. Si un técnico desea anular un mantenimiento, factura o salida de stock, el sistema exige la clave del Administrador. |
| **RF-ADD-02** | Inventario / Stock | Registro formal de Bajas de Stock con motivo y reversión | Permite registrar mermas, repuestos defectuosos de fábrica o daños en taller con trazabilidad del responsable y reversión auditada. |
| **RF-ADD-03** | Caja / Abonos | Sistema de abonos fraccionados en servicios y caja | En reparaciones de alto costo (ej. microelectrónica), el cliente abona un porcentaje al inicio y el saldo contra entrega. Los abonos ingresan a caja con recibo individual. |
| **RF-ADD-04** | Cotizaciones | Flujo de conversión automática de Cotización a Orden/Factura | Evita que el asesor vuelva a digitar datos; con un solo clic, una cotización aprobada pasa a ser una orden de trabajo activa. |
| **RF-ADD-05** | Clientes / Equipos | Historial clínico del equipo por Serial / Código Único | Permite conocer antecedentes de fallas previas de la misma máquina, qué técnico la intervino y qué componentes se reemplazaron. |
| **RF-ADD-06** | Reportes Financieros | Reportes especializados: Diario, Acumulado y Operaciones | Generación automática en PDF y Excel para auditorías contables internas y cumplimiento tributario. |
| **RF-ADD-07** | Configuración | Parametrización de comprobantes y formatos de impresión | Alternancia configurable entre formato Ticket (POS 80mm térmico) y Formato Media Carta/Carta para facturación formal. |

### 2.2. Requerimientos No Funcionales Faltantes Incorporados
| Código | Tipo de RNF | Requerimiento Faltante Incorporado | Criterio de Cumplimiento |
| :--- | :--- | :--- | :--- |
| **RNF-ADD-01** | Seguridad Transaccional | Prevención de reenvío de formularios (`PreventBackHistory`) | Middleware que impide duplicar cobros o ingresos al presionar el botón "Atrás" en el navegador. |
| **RNF-ADD-02** | Seguridad y Concurrencia | Throttling de peticiones (Rate Limiting) | Máximo 5 intentos por minuto en Login y límites estrictos en endpoints de anulación para evitar ataques de fuerza bruta o saturación. |
| **RNF-ADD-03** | Integridad de Datos | Control de concurrencia y transacciones ACID en MySQL | Uso obligatorio de `DB::transaction()` en el descuento de stock simultáneo al registro de reparaciones. |
| **RNF-ADD-04** | Usabilidad / UX | Diseño Adaptable con Glassmorphism y Accesibilidad | Interfaz web intuitiva, contraste optimizado para entornos de taller con iluminación variable y diseño responsivo para tablets de recepción. |
| **RNF-ADD-05** | Continuidad Operativa | Respaldo y contingencia local en red LAN | Capacidad de operar en servidor local (ServBay/Apache/Nginx) sin depender obligatoriamente de conexión a internet externa. |

---

## PASO 3: DETERMINACIÓN DEL EQUIPO DE DESARROLLADORES REQUERIDO
Para llevar a cabo el proyecto **Tecni-Systemas** en un horizonte de ejecución de **8 semanas (2 meses)**, se determinó una estructura de equipo multidisciplinaria optimizada:

### Estructura y Roles del Equipo
1. **Desarrollador Backend y Líder Técnico (Johan Vélez):**
   - *Funciones:* Arquitectura de software en Laravel 11, desarrollo de controladores, servicios de lógica de negocio (AnulacionService, MovimientoInventarioService), middleware de seguridad, control de sesiones, transacciones ACID y generación de reportes en PDF con DomPDF.
   - *Dedicación:* 21 horas semanales (168 horas totales en 8 semanas).

2. **Desarrolladora Frontend & UI/UX (Valentina Cobariza):**
   - *Funciones:* Maquetación de vistas con arquitectura Blade, diseño del sistema visual Glassmorphism (`glass.css`), componentes reactivos de búsqueda asíncrona (Fetch/AJAX) para clientes, repuestos y municipios, validaciones en cliente y diseño responsive.
   - *Dedicación:* 13 horas semanales (104 horas totales en 8 semanas).

3. **Especialista en Base de Datos & QA (Santiago Zapata):**
   - *Funciones:* Diseño conceptual, lógico y físico en MySQL (InnoDB, codificación utf8mb4), definición de índices compuestos para optimización de consultas en reportes, integridad referencial, scripts de migración, seeders y control de calidad.
   - *Dedicación:* 8 horas semanales (64 horas totales en 8 semanas).

**Total de Horas de Desarrollo del Equipo:** 42 horas semanales = **336 horas de programación totales**.

---

## PASO 4: CÁLCULO DETALLADO DEL COSTO DEL PROYECTO
Se aplican con rigurosidad las fórmulas y parámetros oficiales definidos por la cátedra de Gestión de Proyectos de Software (Docente: Ing. Diego Fernando Londoño):

### 4.1. Parámetros Base de Mano de Obra (Estándar CIAF)
- **Sueldo Base Mensual de Referencia:** $2.200.000,00 COP
- **Horas Mensuales Laborales:** 210 horas
- **Horas Semanales Laborables:** 42 horas
- **Horas Diarias Laborales:** 8 horas
- **Valor Hora de Programación:**  
  $$\text{Valor Hora} = \frac{\$2.200.000}{210} = \$10.476,19\text{ COP}$$
- **Valor Día de Programación:**  
  $$\text{Valor Día} = \$10.476,19 \times 8 = \$83.809,52\text{ COP}$$
- **Valor Semana de Programación:**  
  $$\text{Valor Semana} = \$10.476,19 \times 42 = \$440.000,00\text{ COP}$$

### 4.2. Desglose de Inversión de Mano de Obra (8 semanas de desarrollo)
- Total de horas requeridas: 336 horas (distribuidas en el equipo de Backend, Frontend y Base de Datos).
- Costo Base de Desarrollo:
  $$\text{Costo Desarrollo} = 336\text{ horas} \times \$10.476,19 = \$3.520.000,00\text{ COP}$$

### 4.3. Costos Operativos e Indirectos (2 meses de ejecución)
- **Estudio de Viabilidad y Levantamiento Inicial:** $350.000,00 (pago único)
- **Servicios Públicos (Energía e Internet de alta velocidad):** $320.000/mes × 2 meses = $640.000,00
- **Alimentación y Transporte del Equipo:** $180.000/mes × 2 meses = $360.000,00
- **Equipamiento y Licencias de Software:** $0,00 (Uso de infraestructura existente en taller y stack de tecnologías Open Source: PHP, Laravel, MySQL, Apache/Nginx).
- **Adecuaciones Físicas de Infraestructura (Cableado LAN y Sistema UPS regulado):** $680.000,00
- **Subtotal de Costos Directos e Indirectos:**
  $$\text{Subtotal} = \$3.520.000 + \$350.000 + \$640.000 + \$360.000 + \$680.000 = \$5.550.000,00\text{ COP}$$
- **Reserva de Contingencias (10% sobre el subtotal):**
  $$\text{Contingencias} = \$5.550.000 \times 10\% = \$555.000,00\text{ COP}$$
- **Costo Total de Producción del Software:**
  $$\text{Costo Producción} = \$5.550.000 + \$555.000 = \$6.105.000,00\text{ COP}$$

### 4.4. Margen de Utilidad y Precio Final del Proyecto
De acuerdo con las directrices de clase, en el sector de software colombiano se evalúan márgenes entre el 18% y el 40% para garantizar rentabilidad comercial, cobertura de impuestos (hasta 35% de renta sobre la utilidad) y riesgo operativo:

- **Escenario 1 (Margen Conservador - 20%):**  
  Utilidad: $\$6.105.000 \times 20\% = \$1.221.000$ | Precio Total: **$\$7.326.000$ COP**
- **Escenario 2 (Margen Competitivo - 30%):**  
  Utilidad: $\$6.105.000 \times 30\% = \$1.831.500$ | Precio Total: **$\$7.936.500$ COP**
- **Escenario 3 (Margen Recomendado - 35%):**  
  Utilidad: $\$6.105.000 \times 35\% = \$2.136.750$ | Precio Total: **$\$8.241.750$ COP**

*El Escenario 3 (35%) es el adoptado*, ya que permite absorber los impuestos de ley, brindar 3 meses de soporte posventa sin costo adicional y asegurar una Tasa Interna de Retorno (TIR) atractiva para el equipo desarrollador.

---

## PASO 5: RUTA DE EJECUCIÓN Y PASO AL INFORME FORMAL
Con estos insumos validados:
1. Se estructura el **Informe Formal de Viabilidad** utilizando la plantilla de 6 secciones exigida por la institución.
2. Se consolidan las matrices de viabilidad Técnica, Económica y Operativa.
3. Se concluye categóricamente la viabilidad integral de **Tecni-Systemas**.
