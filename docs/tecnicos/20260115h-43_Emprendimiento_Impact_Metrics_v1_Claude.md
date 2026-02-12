SISTEMA DE MÉTRICAS DE IMPACTO
Impact Metrics
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	43_Emprendimiento_Impact_Metrics
Dependencias:	Todas las entidades del vertical, FOC
 
1. Resumen Ejecutivo
El Sistema de Métricas de Impacto mide el impacto real y medible del programa en la vida de los emprendedores y en el ecosistema económico. Combina indicadores de negocio SaaS con indicadores de impacto social requeridos para justificación de financiación pública, reporting ESG y cumplimiento RETECH.
1.1 Dimensiones de Impacto
Dimensión	Descripción	Stakeholder
Supervivencia	Tasa de negocios activos a 1/2/3 años	Financiadores, Administraciones
Facturación	Ingresos generados por emprendedores	Territorio, Economía local
Empleo	Puestos de trabajo creados (incluido autoempleo)	Políticas públicas
Digitalización	Nivel de adopción digital medible	Programa, Emprendedores
Formación	Competencias adquiridas y certificadas	Emprendedores
Mentoría	Impacto del acompañamiento profesional	Mentores, Programa
1.2 Alineación con Frameworks
•	ODS (Objetivos de Desarrollo Sostenible): ODS 8 (Trabajo decente), ODS 9 (Industria e innovación), ODS 10 (Reducción desigualdades)
•	SROI (Social Return on Investment): Metodología de cálculo de retorno social por euro invertido
•	ESG Reporting: Indicadores para informes de sostenibilidad corporativa
•	RETECH: Compliance con requisitos de fondos de digitalización NextGen
 
2. Catálogo de Métricas
2.1 Métricas de Supervivencia Empresarial
Métrica	Cálculo	Objetivo
Survival Rate 1Y	Negocios activos 12m / Total iniciados	> 70%
Survival Rate 2Y	Negocios activos 24m / Total iniciados	> 55%
Survival Rate 3Y	Negocios activos 36m / Total iniciados	> 45%
Abandon Rate	Negocios abandonados / Total	< 25%
Pivot Rate	Negocios que pivotaron modelo / Total	15-30% (saludable)
2.2 Métricas de Facturación
Métrica	Cálculo	Objetivo
GMV Agregado	SUM(facturación de emprendedores)	Crecimiento 20% YoY
Avg Revenue/Business	GMV / Negocios activos	> €15,000/año
Revenue Growth Rate	(GMV mes actual - mes anterior) / anterior	> 5% MoM
First Sale Rate	Emprendedores con 1+ venta / Total	> 60%
Time to First Sale	Días desde registro hasta primera venta	< 90 días
2.3 Métricas de Creación de Empleo
Métrica	Cálculo	Objetivo
Jobs Created	SUM(empleos generados por emprendedores)	Maximizar
Self-Employment Rate	Autoempleos / Total emprendedores activos	> 80%
Avg Employees/Business	Total empleados / Negocios con empleados	> 1.5
FTE Generated	Full-Time Equivalents creados	Crecimiento constante
Salary Mass Generated	SUM(salarios anuales generados)	Maximizar
2.4 Métricas de Digitalización
Métrica	Cálculo	Objetivo
Digital Maturity Score	Promedio de puntuación de madurez digital	> 65/100
Maturity Improvement	(Score final - Score inicial) / inicial	> 40%
Tools Adopted	Promedio de herramientas digitales activas	> 5 por negocio
Online Presence Rate	Emprendedores con web/tienda online	> 70%
Digital Sales %	Ventas online / Ventas totales	> 30%
 
2.5 Métricas de Programa
Métrica	Cálculo	Objetivo
Completion Rate	Emprendedores que completan itinerario / Iniciados	> 60%
Avg Progress	Promedio de progress_percent de usuarios	> 65%
Tasks Completed/User	Media de tareas completadas por usuario	> 15
Mentoring Utilization	Sesiones realizadas / Sesiones contratadas	> 85%
NPS Score	Net Promoter Score del programa	> 50
Recommendation Rate	% que recomendaría el programa	> 80%
2.6 Métricas Económicas del Programa
Métrica	Cálculo	Objetivo
Cost per Entrepreneur	Coste total programa / Emprendedores atendidos	< €500
Cost per Business Created	Coste / Negocios viables generados	< €1,500
Cost per Job Created	Coste / Empleos generados	< €3,000
SROI Ratio	Valor social generado / Inversión	> 3:1
Grant Efficiency	Impacto logrado / Grant consumido	Maximizar
 
3. Arquitectura de Datos
3.1 Entidad: impact_snapshot
Snapshot periódico de métricas de impacto agregadas:
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
period_type	VARCHAR(16)	ENUM: daily|weekly|monthly|quarterly|yearly
period_start	DATE	Inicio del periodo
period_end	DATE	Fin del periodo
tenant_id	INT	FK tenant.id, NULL=global
total_entrepreneurs	INT	Total emprendedores registrados
active_entrepreneurs	INT	Emprendedores con actividad en periodo
new_enrollments	INT	Nuevos registros en periodo
businesses_created	INT	Negocios marcados como creados
businesses_active	INT	Negocios activos (con actividad/facturación)
total_gmv	DECIMAL(12,2)	Volumen de facturación agregado €
jobs_created	INT	Empleos generados acumulado
avg_digital_maturity	DECIMAL(5,2)	Puntuación media de madurez digital
completion_rate	DECIMAL(5,2)	% que completan itinerario
nps_score	INT	Net Promoter Score del periodo
mentoring_sessions	INT	Sesiones de mentoría realizadas
created	DATETIME	Timestamp de generación
3.2 Entidad: business_outcome
Registro de resultados de negocio de cada emprendedor:
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
user_id	INT	FK users.uid
business_name	VARCHAR(255)	Nombre del negocio
business_status	VARCHAR(24)	ENUM: idea|validating|launched|growing|scaling|paused|closed
launch_date	DATE	Fecha de lanzamiento oficial
first_sale_date	DATE	Fecha de primera venta
employee_count	INT	Número de empleados (incluye propietario)
monthly_revenue	DECIMAL(10,2)	Facturación mensual última reportada €
total_revenue_ytd	DECIMAL(12,2)	Facturación acumulada año €
digital_maturity_current	INT	Puntuación madurez actual (0-100)
digital_maturity_initial	INT	Puntuación madurez al inicio
verified_at	DATETIME	Última verificación de datos
verification_method	VARCHAR(24)	ENUM: self_reported|platform_data|external_audit
 
4. Sistema de Seguimiento Post-Programa
4.1 Protocolo de Seguimiento
Momento	Canal	Acciones
+3 meses	Email + In-app	Encuesta NPS, verificar estado del negocio
+6 meses	Email + Llamada	Verificar supervivencia, facturación, empleos
+12 meses	Email + Llamada	Verificación anual formal para reporting
+24 meses	Email	Seguimiento largo plazo, caso de éxito potencial
+36 meses	Email	Última verificación para métricas 3Y
4.2 Automatización de Seguimiento (ECA)
ECA-IMPACT-001: Follow-up Scheduler
1.	Trigger: Cron diario a las 09:00
2.	Buscar emprendedores con program_end_date + 90 días = TODAY
3.	Enviar email de seguimiento con encuesta embebida
4.	Si no responde en 7 días: crear tarea para gestor
5.	Repetir para +6m, +12m, +24m, +36m
 
5. Alineación con Objetivos de Desarrollo Sostenible
ODS	Meta	Métricas Asociadas
ODS 8: Trabajo Decente	8.3 Promover políticas que apoyen actividades productivas y emprendimiento	Jobs Created, Self-Employment Rate, Salary Mass
ODS 8: Trabajo Decente	8.5 Lograr empleo pleno y productivo	Business Survival Rate, Avg Revenue
ODS 9: Industria e Innovación	9.3 Aumentar acceso de PYMEs a servicios financieros y tecnológicos	Digital Maturity Score, Tools Adopted
ODS 9: Industria e Innovación	9.b Apoyar desarrollo tecnológico y diversificación industrial	Online Presence Rate, Digital Sales %
ODS 10: Reducción Desigualdades	10.2 Potenciar inclusión social, económica y política	Entrepreneurs from Rural Areas, Gender Ratio
5.1 Cálculo SROI (Social Return on Investment)
Fórmula simplificada para el contexto del programa:
SROI = (Valor Social Generado) / (Inversión Total)
Componentes del Valor Social:
•	Salarios generados (jobs × avg salary × duration)
•	Ahorro en prestaciones desempleo evitadas
•	Impuestos generados (IVA + IRPF + cotizaciones)
•	Valor económico local (GMV × multiplicador territorial)
 
6. Sistema de Reporting
6.1 Reportes Predefinidos
Reporte	Contenido	Frecuencia	Formato
Monthly Impact Report	KPIs principales, tendencias, alertas	Mensual	PDF, XLSX
Quarterly Business Review	Análisis trimestral con comparativas	Trimestral	PDF, PPT
RETECH Compliance	Indicadores requeridos por fondos NextGen	Trimestral	PDF, XML
ESG Report	Métricas de sostenibilidad social	Anual	PDF
ODS Contribution	Mapeo a objetivos ODS con evidencias	Anual	PDF
Success Stories	Casos de éxito documentados	Bajo demanda	PDF, Web
Grant Justification	Informe para justificación de subvenciones	Según convocatoria	PDF, XLSX
6.2 Dashboard de Impacto (para Gestores)
Vista	Contenido	Audiencia
Executive Summary	KPIs principales con tendencias y alertas	Dirección
Survival Tracker	Funnel de supervivencia empresarial por cohorte	Gestores programa
Economic Impact	GMV, empleos, impuestos generados	Financiadores
Geographic Map	Distribución geográfica del impacto	Administraciones
SROI Calculator	Cálculo interactivo de retorno social	Financiadores
Cohort Analysis	Comparativa de resultados por promoción	Gestores
 
7. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/impact/summary	Resumen ejecutivo de métricas actuales
GET	/api/v1/impact/metrics/{metric_name}	Serie temporal de una métrica específica
GET	/api/v1/impact/snapshots	Histórico de snapshots por periodo
GET	/api/v1/impact/survival-funnel	Datos del funnel de supervivencia
GET	/api/v1/impact/geographic	Distribución geográfica
GET	/api/v1/impact/ods-mapping	Contribución a ODS
GET	/api/v1/impact/sroi	Cálculo de SROI actual
POST	/api/v1/impact/reports/generate	Generar reporte bajo demanda
GET	/api/v1/impact/outcomes/{user_id}	Business outcome de un emprendedor
POST	/api/v1/impact/outcomes	Registrar/actualizar business outcome
 
8. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades impact_snapshot, business_outcome. ETL básico.
Sprint 2	Semana 3-4	Cálculo de métricas. Snapshot scheduler.
Sprint 3	Semana 5-6	Dashboard frontend. Executive summary.
Sprint 4	Semana 7-8	Sistema de seguimiento. Encuestas. ECA.
Sprint 5	Semana 9-10	Reportes PDF. ODS mapping. SROI calculator. QA.
8.1 KPIs del Sistema
KPI	Target	Medición
Data Quality	> 90%	% de emprendedores con datos actualizados < 6 meses
Follow-up Response	> 70%	% de seguimientos con respuesta
Report Generation	< 30 seg	Tiempo de generación de reporte PDF
SROI Accuracy	< 10% error	Desviación respecto a auditoría externa
--- Fin del Documento ---
