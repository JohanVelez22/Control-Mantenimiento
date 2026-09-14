# INFORME DE VIABILIDAD DEL PROYECTO (ESTUDIO INTEGRAL MULTIDIMENSIONAL)
**Sesión 2:** El Informe de Viabilidad del Proyecto — El "Sí" o "No" Antes de Empezar  
**Materia:** Gestión de Proyectos de Software | Periodo 2026-2  
**Docente:** Ing. Diego Fernando Londoño (diego.londono@ciaf.edu.co | 3043942508)  
**Institución:** CIAF Educación Superior  
**Nombre del Proyecto:** Sistema Integral de Gestión de Inventarios, Mantenimiento Técnico, Electrónica y Caja para "TECNI-SYSTEMAS"  
**Fecha:** 13 de Septiembre de 2026  
**Versión:** 1.0 (Definitiva para Aprobación)  
**Equipo de Proyecto / Elaborado por:**  
- Johan Vélez (Líder Técnico / Desarrollador Backend)  
- Valentina Cobariza (Diseñadora UI/UX / Desarrolladora Frontend)  
- Santiago Zapata (Modelador de Datos / DBA & QA)

---

## 1. RESUMEN EJECUTIVO
Este documento evalúa la viabilidad multidimensional del proyecto **"Sistema Integral de Gestión de Mantenimientos, Laboratorio de Electrónica, Inventario y Caja"** para la empresa **Tecni Systemas** (NIT 4.501.927, Cra 4 # 20-81, Pereira). El proyecto busca reemplazar de manera integral el sistema mixto actual (anotaciones manuales en papel, recibos de talonario y archivos aislados de Excel) para eliminar las pérdidas de repuestos sin facturar, corregir los descuadres en los arqueos diarios de caja y reducir los tiempos de cotización y entrega a clientes.

**Conclusión Principal de Gobernanza:**  
El proyecto es **VIABLE** en sus cuatro dimensiones evaluadas (Técnica, Económica, Operativa y Legal/Regulatoria). Se proyecta un **Retorno de Inversión (ROI) superior al 100% (111,1%)** en el primer año y un período de recuperación de la inversión (Payback) estimado en **5,7 meses**. Se recomienda formalmente proceder a la fase de iniciación y firma del Acta de Constitución del Proyecto (Project Charter).

---

## 2. ANÁLISIS DE VIABILIDAD TÉCNICA
**Objetivo:** Determinar si el equipo de desarrollo dispone de la capacidad, las tecnologías idóneas y la infraestructura para construir y mantener el producto de software.

### 2.1. Evaluación por Criterios Técnicos
1. **Tecnología:**  
   - *Evaluación:* El backend se construirá con **PHP 8.3** y el framework **Laravel 11/13**, proporcionando una arquitectura robusta MVC, sistema de autenticación con control de acceso basado en roles (RBAC) y transacciones ACID. El frontend se implementará con **Blade**, diseño limpio responsivo **Glassmorphism (CSS Vanilla sin dependencias pesadas)** y componentes interactivos en JavaScript Vanilla para búsquedas asíncronas de clientes y repuestos. La base de datos se alojará en **MySQL 8.0 (InnoDB)**. El equipo posee amplia experiencia demostrada en este stack tecnológico.  
   - *Calificación:* **Aceptable**

2. **Complejidad:**  
   - *Evaluación:* El proyecto es de complejidad media-alta. No exige algoritmos complejos de inteligencia artificial, pero sí requiere un control muy estricto en las reglas de negocio: doble factor de validación con contraseña administrativa para anulaciones de servicios y bajas de stock, cálculo automatizado de márgenes comerciales de repuestos, arqueo de caja con control de abonos fraccionados y generación de comprobantes PDF en tiempo real (formatos Ticket 80mm y Carta).  
   - *Calificación:* **Aceptable**

3. **Recursos Humanos:**  
   - *Evaluación:* El equipo está conformado por 3 integrantes especializados: **Johan Vélez** como Desarrollador Backend Líder (PHP/Laravel, 21 h/sem), **Valentina Cobariza** como Desarrolladora Frontend/UX (Blade/CSS/JS, 13 h/sem) y **Santiago Zapata** como Administrador de Base de Datos y QA (MySQL, 8 h/sem). La interfaz Glassmorphism fue diseñada para ser clara y fácil de usar bajo la iluminación del taller.  
   - *Calificación:* **Aceptable**

4. **Integración con Hardware y Periféricos:**  
   - *Evaluación:* El sistema debe comunicarse localmente con impresoras térmicas de recibos de 80mm (protocolo USB/LAN POS), impresoras láser estándar para facturas formales de media carta y con la API geográfica de municipios de Colombia para normalizar los datos de clientes. El equipo cuenta con librerías nativas probadas (DomPDF, GuzzleHTTP).  
   - *Calificación:* **Aceptable**

### 2.2. Riesgos Técnicos Identificados y Plan de Mitigación
- **Riesgo 1: Corrupción de tablas transaccionales por cortes imprevistos de energía en Pereira.**  
  *Mitigación:* Instalación obligatoria de una UPS regulada de 850VA / 480W en el servidor local y uso exclusivo de motor transaccional MySQL InnoDB con `autocommit = 0` y `DB::transaction()`.
- **Riesgo 2: Reenvío accidental de cobros o duplicidad de transacciones al retroceder en el navegador.**  
  *Mitigación:* Implementación de middleware global `PreventBackHistory` y encabezados HTTP `Cache-Control: no-cache, no-store, must-revalidate`.
- **Riesgo 3: Saturación de solicitudes en el módulo público de consulta para clientes.**  
  *Mitigación:* Limitador de velocidad (Rate Limiting / Throttling) restringido a 30 peticiones por minuto por dirección IP.

**Conclusión Técnica:** **VIABLE.** El stack es conocido, estable, de código abierto y los riesgos técnicos están completamente cubiertos con contramedidas arquitectónicas.

---

## 3. ANÁLISIS DE VIABILIDAD ECONÓMICA
**Objetivo:** Demostrar que el valor económico y los ahorros operacionales generados por el proyecto superan con creces el costo total de desarrollo e implantación.

### 3.1. Estructura Salarial de Referencia (Metodología Oficial Cátedra CIAF)
- Sueldo Base Mensual: **$2.200.000,00 COP**
- Horas Mensuales Laborales: **210 horas**
- Horas Diarias Laborales: **8 horas**
- Horas Semanales Laborables: **42 horas**
- **Valor Hora de Programación:** $\$2.200.000 / 210 = \$10.476,19	ext{ COP}$
- **Valor Día de Programación:** $\$10.476,19 	imes 8 = \$83.809,52	ext{ COP}$
- **Valor Semana de Programación:** $\$10.476,19 	imes 42 = \$440.000,00	ext{ COP}$

### 3.2. Presupuesto Detallado de Inversión y Costos
| Rubro / Concepto | Detalle y Cuantificación | Valor Unitario (COP) | Costo Total (COP) |
| :--- | :--- | :---: | :---: |
| **Mano de Obra Equipo de Desarrollo** | 336 horas (42 h/semana × 8 semanas) | $10.476,19 / h | $3.520.000 |
| **Estudio Previo de Viabilidad y Requisitos** | 1 pago único (Fase 0 de consultoría) | $350.000 | $350.000 |
| **Adecuación Infraestructura (UPS + Switch)** | UPS 850VA regulada + Switch Gigabit 8P + Red | $680.000 (1 vez) | $680.000 |
| **Servicios Públicos (Energía + Internet)** | Consumo operativo durante 2 meses | $320.000 / mes | $640.000 |
| **Alimentación y Transporte del Personal** | Desplazamientos a sede Tecni Systemas (2 meses) | $180.000 / mes | $360.000 |
| **Capacitación a Personal y Usuarios** | Incluida en horas de desarrollo (0 costo extra) | $0 | $0 |
| **Licencias de Software y Hosting Local** | Tecnologías Open Source / Servidor local ServBay | $0 / mes | $0 |
| **Subtotal de Costos Directos e Indirectos** | Sumatoria de rubros de ejecución | - | **$5.550.000** |
| **Reserva de Contingencias (10%)** | 10% de cobertura ante variaciones | 10% | $555.000 |
| **TOTAL COSTO DE PRODUCCIÓN** | **Inversión base integral de fabricación** | - | **$6.105.000** |

### 3.3. Análisis Comparativo de Márgenes de Utilidad Comercial
Siguiendo la guía de la clase, se analizan los distintos escenarios de rentabilidad para el mercado tecnológico colombiano:
- **Margen Mínimo Sectorial (20%):**  
  $\$6.105.000 	imes 20\% = \$1.221.000$ | Precio Venta: **$7.326.000 COP** (Cubre costos pero margen ajustado ante imprevistos tributarios).
- **Margen Competitivo (30%):**  
  $\$6.105.000 	imes 30\% = \$1.831.500$ | Precio Venta: **$7.936.500 COP** (Equilibrio estándar del sector).
- **Margen Recomendado y Adoptado (35%):**  
  $$	ext{Utilidad Bruta} = \$6.105.000 	imes 35\% = \$2.136.750	ext{ COP}$$
  $$	ext{PRECIO TOTAL DEL PROYECTO} = \$6.105.000 + \$2.136.750 = \$8.241.750	ext{ COP}$$
  *Justificación:* El margen del 35% permite absorber el impuesto sobre la renta en Colombia (tasa del 35% sobre utilidades corporativas), financiar una póliza de garantía y mantenimiento preventivo por 90 días pos-lanzamiento y dejar una Tasa Interna de Retorno (TIR) real superior al 22%.

### 3.4. Análisis de Retorno de Inversión (ROI) y Beneficio Económico
- **Pérdidas operativas actuales estimadas en Tecni Systemas:** $1.450.000 COP/mes (repuestos no registrados, horas perdidas en cobro manual y fugas en arqueo de caja).
- **Ahorro Anual Estimado:** $\$1.450.000 	imes 12 = \$17.400.000	ext{ COP}$.
- **Cálculo de Retorno de Inversión (ROI primer año):**  
  $$	ext{ROI} = rac{	ext{Beneficios Netos} - 	ext{Inversión}}{	ext{Inversión}} = rac{\$17.400.000 - \$8.241.750}{\$8.241.750} 	imes 100\% = \mathbf{111,1\%}$$
- **Período de Recuperación (Payback):** $5,7	ext{ meses}$.

**Conclusión Económica:** **VIABLE.** El proyecto se autofinancia con los ahorros generados en el primer semestre de operación.

---

## 4. ANÁLISIS DE VIABILIDAD OPERATIVA
**Objetivo:** Evaluar si la organización y sus colaboradores están preparados para adoptar, usar y sostener la nueva plataforma.

### 4.1. Criterios de Evaluación Operativa
| Criterio Operativo | Evaluación Detallada en Tecni Systemas | Estado |
| :--- | :--- | :---: |
| **Aceptación del Usuario** | El Administrador y los técnicos han mostrado entusiasmo absoluto al constatar que el software eliminará el cierre manual nocturno de 2 horas y agilizará la recepción de equipos en mostrador. Los técnicos expresaron apertura tras confirmar que la interfaz Glassmorphism requiere mínimos clics. | **Aceptable** |
| **Capacidad de Mantenimiento** | El sistema no requiere personal de infraestructura dedicado a tiempo completo. El entorno local ServBay / Apache y MySQL cuenta con scripts de arranque automático y copias de seguridad programadas (`cron/mysqldump`). | **Aceptable** |
| **Cambio Cultural** | Se transita de planillas de papel a interfaces digitales. Requiere supervisión inicial para asegurar que ningún técnico extraiga piezas sin registrarlas en el sistema. | **Con Reservas (Mitigable)** |

### 4.2. Plan de Mitigación Operativa
1. **Talleres de Co-Creación:** Pruebas preliminares con los técnicos durante la fase Beta para ajustar los formularios de diagnóstico según la terminología habitual del taller (fallas típicas de pantallas, reballing, fuentes, etc.).
2. **Programa de "Campeones del Sistema":** Capacitación intensiva al Administrador General y al Técnico Líder para que orienten la resolución de dudas operativas del personal.
3. **Manual de Operaciones Rápido:** Elaboración de fichas plastificadas de una página ("Guía Rápida de 3 Pasos para Recibir, Reparar y Cobrar") colocadas en cada estación de trabajo.

**Conclusión Operativa:** **VIABLE CON RESERVAS.** La viabilidad operativa es plenamente alcanzable mediante la ejecución estricta del plan de capacitación y la presencia del campeón del sistema.

---

## 5. ANÁLISIS DE VIABILIDAD LEGAL Y REGULATORIA
**Objetivo:** Garantizar que el sistema cumpla con el marco legal colombiano y los principios de seguridad de la información.

1. **Protección de Datos Personales (Ley 1581 de 2012 y Decreto 1377 de 2013 de Colombia):**  
   - *Diagnóstico:* El sistema recopila nombres, cédulas/NIT, números telefónicos, correos y direcciones de clientes para la emisión de órdenes de servicio y facturación.  
   - *Medida Implementada:* Se integra en la base de datos la fecha de autorización de Habeas Data y se imprime automáticamente en el pie de página de las órdenes y facturas la cláusula de autorización de tratamiento de datos personales, garantizando cumplimiento de la Superintendencia de Industria y Comercio (SIC).  
   - *Estado:* **Requisito Crítico Satisfecho (Aceptable)**.

2. **Licenciamiento y Propiedad Intelectual:**  
   - El código fuente ha sido desarrollado de forma original, utilizando librerías bajo licencias libres permisivas (MIT para Laravel, DomPDF, PHP y MariaDB/MySQL). No existen riesgos de infracción de propiedad intelectual de terceros.  
   - La propiedad patrimonial del software desarrollado pertenecerá en su totalidad a la empresa **Tecni Systemas**, suscribiéndose un acuerdo formal de cesión de derechos con el equipo de desarrollo.  
   - *Estado:* **Aceptable**.

**Conclusión Legal:** **VIABLE.** No existen barreras regulatorias ni litigios potenciales.

---

## 6. ANÁLISIS DE INTERESADOS (MATRIZ DE STAKEHOLDERS)

| Interesado / Stakeholder (Rol) | Expectativa Principal | Nivel de Influencia / Interés | Estrategia de Gestión y Comunicación |
| :--- | :--- | :---: | :--- |
| **1. Administrador General (Rol `admin`)** | Control total de activos, supervisión de caja, reducción de mermas y auditoría de anulaciones con clave maestra. | **Alto / Alto** | Informes quincenales de avance, dashboard ejecutivo en tiempo real y reuniones de validación de Betas. |
| **2. Técnicos de Taller (Rol `tecnico`)** | Diagnósticos ágiles, control de repuestos consumidos, cotizaciones rápidas y registro de cobros/abonos en mostrador. | **Alto / Alto** | Sesiones prácticas de co-creación, simulaciones de órdenes/cobro y validación de formatos de ticket 80mm. |
| **3. Clientes Externos (Rol `invitado`)** | Consultar el estado de su equipo sin esperas por internet y recibir comprobante digital. | **Bajo / Medio** | Portal web de consulta simplificado con protección contra sobrecarga (throttling de 30 req/min). |

---

## 7. CONCLUSIÓN Y RECOMENDACIÓN FINAL

1. El proyecto **"Sistema Integral para Tecni-Systemas"** es **TÉCNICA, ECONÓMICA, LEGAL Y OPERATIVAMENTE VIABLE**.
2. La viabilidad operativa cuenta con un plan de mitigación sólido y documentado que neutraliza el riesgo de resistencia al cambio cultural.
3. El retorno de inversión superior al 100% en el primer año (Payback de 5,7 meses) justifica con creces la aprobación de los recursos.

**Recomendación Final de Gobernanza:**  
**PROYECTO APROBADO.** Se autoriza formalmente el inicio inmediato del desarrollo, la firma del Acta de Constitución del Proyecto y la ejecución del presupuesto formulado.

**Equipo Responsable de la Formulación y Desarrollo:**
- **Johan Vélez** — Líder de Proyecto y Arquitecto Backend
- **Valentina Cobariza** — Desarrolladora Frontend y Diseñadora UI/UX
- **Santiago Zapata** — Especialista en Base de Datos y Aseguramiento de Calidad (QA)
