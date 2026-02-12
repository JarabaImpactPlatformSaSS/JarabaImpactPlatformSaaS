
IMPACT METRICS
Sistema de Métricas de Impacto
Medición de Resultados del Ecosistema
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	24_Empleabilidad_Impact_Metrics
Dependencias:	Todas las entidades del vertical
 
1. Resumen Ejecutivo
El sistema de Impact Metrics mide el impacto real y medible del programa Impulso Empleo en la vida de los usuarios y en el ecosistema económico. A diferencia de métricas SaaS tradicionales, este sistema combina indicadores de negocio con indicadores de impacto social requeridos para justificación de financiación pública y reporting ESG.
1.1 Dimensiones de Impacto
Dimensión	Descripción	Stakeholder
Empleabilidad	Mejora en capacidad de conseguir empleo	Candidatos
Inserción Laboral	Contrataciones efectivas logradas	Candidatos, Empleadores
Competencias	Skills adquiridos y certificados	Candidatos, Formadores
Digitalización	Adopción de herramientas digitales	Todo el ecosistema
Económico	Impacto en ingresos y economía local	Territorio, Financiadores
Social	Reducción de desigualdad, inclusión	Administraciones
1.2 Alineación con Frameworks
•	ODS (Objetivos de Desarrollo Sostenible): ODS 4 (Educación), ODS 8 (Trabajo decente), ODS 10 (Reducción desigualdades)
•	SROI (Social Return on Investment): Metodología de cálculo de retorno social
•	ESG Reporting: Indicadores para informes de sostenibilidad corporativa
•	RETECH: Compliance con requisitos de fondos de digitalización
 
2. Catálogo de Métricas
2.1 Métricas de Inserción Laboral
Métrica	Cálculo	Objetivo	ODS
Placement Rate	Usuarios contratados / Usuarios activos × 100	> 25%	8.5, 8.6
Time to Employment	AVG(días desde registro hasta hired)	< 90 días	8.5
Job Retention Rate	Usuarios que mantienen empleo > 6 meses	> 80%	8.5
Ecosystem Hiring	Contrataciones en PYMEs ecosistema / Total	> 40%	8.3
Quality of Employment	Contratos indefinidos / Total contratos	> 50%	8.5
Salary Improvement	AVG(salario nuevo - salario anterior)	> 15%	8.5, 10.1
2.2 Métricas de Competencias
Métrica	Cálculo	Objetivo	ODS
Course Completion Rate	Cursos completados / Cursos iniciados × 100	> 70%	4.3, 4.4
Path Graduation Rate	Learning paths completadas / Iniciadas	> 60%	4.4
Credentials Earned	Total credenciales emitidas	Crecimiento	4.4
Skills Verified	Skills con certificación / Total skills perfil	> 50%	4.4
Learning Hours	Total horas de formación completadas	AVG > 20h	4.3
Digital Literacy Gain	Score post-diagnóstico - Score inicial	> +3 puntos	4.4
 
2.3 Métricas de Engagement
Métrica	Cálculo	Objetivo
Active User Rate	Usuarios con actividad en últimos 30 días / Total	> 60%
Profile Completion	AVG(completeness_score) de todos los perfiles	> 75%
Application Rate	Usuarios que han aplicado >= 1 vez / Total activos	> 80%
CV Generated	Usuarios con CV generado / Total usuarios	> 70%
AI Copilot Usage	Usuarios que usaron copilot / Total activos	> 50%
NPS Score	Net Promoter Score de usuarios	> 40
2.4 Métricas Económicas
Métrica	Cálculo	Objetivo
Cost per Placement	Coste total programa / Contrataciones logradas	< €500
Salary Mass Generated	SUM(salarios anuales de usuarios contratados)	Maximizar
ROI for Employers	(Valor contratación - Coste plataforma) / Coste	> 300%
SROI (Social ROI)	Valor social generado / Inversión pública	> 3:1
Local Economic Impact	Ingresos generados en economía local rural	Crecimiento
 
3. Arquitectura de Datos
3.1 Entidad: impact_snapshot
Snapshot periódico de métricas de impacto agregadas:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
period_type	VARCHAR(16)	Tipo de periodo	ENUM: daily|weekly|monthly|quarterly|yearly
period_start	DATE	Inicio del periodo	NOT NULL, INDEX
period_end	DATE	Fin del periodo	NOT NULL
tenant_id	INT	Tenant específico	FK tenant.id, NULL=global
vertical_id	INT	Vertical específica	NULLABLE
total_users	INT	Total usuarios registrados	NOT NULL
active_users	INT	Usuarios activos	NOT NULL
new_users	INT	Nuevos registros	NOT NULL
placements	INT	Contrataciones	NOT NULL
placement_rate	DECIMAL(5,2)	Tasa de colocación	RANGE 0-100
avg_time_to_employment	DECIMAL(8,2)	Días promedio	NULLABLE
ecosystem_hires	INT	Contrataciones ecosistema	NOT NULL
courses_completed	INT	Cursos completados	NOT NULL
credentials_issued	INT	Credenciales emitidas	NOT NULL
total_learning_hours	DECIMAL(12,2)	Horas formación	NOT NULL
applications_submitted	INT	Candidaturas enviadas	NOT NULL
interviews_conducted	INT	Entrevistas realizadas	NOT NULL
offers_made	INT	Ofertas realizadas	NOT NULL
offers_accepted	INT	Ofertas aceptadas	NOT NULL
total_salary_mass	DECIMAL(15,2)	Masa salarial generada	EUR
avg_salary_improvement	DECIMAL(5,2)	Mejora salarial %	NULLABLE
nps_score	DECIMAL(4,1)	NPS del periodo	RANGE -100 to 100
computed_at	DATETIME	Fecha de cálculo	NOT NULL, UTC
raw_data	JSON	Datos brutos detallados	For drill-down
Índice único:
UNIQUE INDEX (period_type, period_start, tenant_id, vertical_id)
 
3.2 Entidad: placement_record
Registro detallado de cada contratación para análisis de impacto:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
application_id	INT	Candidatura origen	FK job_application.id, NOT NULL
user_id	INT	Usuario contratado	FK users.uid, NOT NULL, INDEX
employer_id	INT	Empleador	FK employer_profile.id, NOT NULL
job_id	INT	Oferta	FK job_posting.id, NOT NULL
hired_at	DATE	Fecha contratación	NOT NULL, INDEX
contract_type	VARCHAR(32)	Tipo de contrato	ENUM: indefinido|temporal|formacion|practicas
contract_duration_months	INT	Duración si temporal	NULLABLE
is_full_time	BOOLEAN	Jornada completa	DEFAULT TRUE
salary_annual	DECIMAL(10,2)	Salario anual bruto	NULLABLE, EUR
salary_previous	DECIMAL(10,2)	Salario anterior	NULLABLE (si disponible)
is_ecosystem_employer	BOOLEAN	Empleador del ecosistema	DEFAULT FALSE
is_remote	BOOLEAN	Trabajo remoto	DEFAULT FALSE
location_city	VARCHAR(128)	Ciudad del empleo	NOT NULL
location_province	VARCHAR(128)	Provincia	NOT NULL
user_profile_type_at_hire	VARCHAR(32)	Perfil diagnóstico al contratar	NULLABLE
courses_completed_at_hire	INT	Cursos completados al contratar	DEFAULT 0
credentials_at_hire	INT	Credenciales al contratar	DEFAULT 0
days_to_hire	INT	Días desde registro	Computed
retention_6m_verified	BOOLEAN	Verificado a 6 meses	DEFAULT FALSE
retention_6m_status	VARCHAR(32)	Estado a 6 meses	ENUM: employed|left|unknown
retention_12m_verified	BOOLEAN	Verificado a 12 meses	DEFAULT FALSE
retention_12m_status	VARCHAR(32)	Estado a 12 meses	ENUM: employed|left|promoted|unknown
follow_up_notes	TEXT	Notas de seguimiento	NULLABLE
created	DATETIME	Fecha registro	NOT NULL, UTC
 
4. Dashboard de Impacto
4.1 Vistas del Dashboard
Vista	Contenido	Audiencia
Executive Summary	KPIs principales, tendencias, alertas	Dirección, Sponsors
Placement Tracker	Funnel de conversión, placements por periodo	Gestores programa
Learning Analytics	Completitud cursos, engagement, certificaciones	Equipo formación
Employer Insights	Actividad empleadores, ofertas, contrataciones	Gestores B2B
Geographic Map	Distribución geográfica de impacto	Administraciones
SROI Calculator	Cálculo de retorno social de inversión	Financiadores
ODS Alignment	Contribución a Objetivos de Desarrollo Sostenible	Reporting ESG
4.2 Widgets Principales
•	Impact Counter: Contador animado de contrataciones totales
•	Placement Funnel: Sankey diagram del journey usuario
•	Time Series: Evolución de métricas clave en el tiempo
•	Heat Map: Mapa de calor de actividad por geografía
•	Cohort Analysis: Análisis de cohortes por fecha de registro
•	SROI Gauge: Indicador visual de retorno social
 
5. Sistema de Seguimiento Post-Placement
5.1 Protocolo de Seguimiento
Momento	Canal	Acciones
+30 días	Email + In-app	Encuesta NPS, verificar que sigue empleado, recoger feedback
+90 días	Email	Verificar continuidad, preguntar sobre desarrollo profesional
+6 meses	Email + Llamada (si no responde)	Verificación formal de retención, actualizar placement_record
+12 meses	Email + Llamada	Verificación anual, detectar promociones, recoger caso de éxito
5.2 Automatización de Seguimiento (ECA)
ECA-IMPACT-001: Follow-up Scheduler
1.	Trigger: Cron diario a las 09:00
2.	Buscar placement_records con hired_at + 30 días = TODAY
3.	Enviar email de seguimiento con encuesta NPS embebida
4.	Crear tarea para gestores si no hay respuesta en 7 días
5.	Repetir lógica para 90 días, 6 meses, 12 meses
 
6. Sistema de Reporting
6.1 Reportes Predefinidos
Reporte	Contenido	Frecuencia	Formato
Monthly Impact Report	KPIs, placements, tendencias	Mensual	PDF, XLSX
RETECH Compliance	Indicadores requeridos por fondos	Trimestral	PDF, XML
ESG Report	Métricas de sostenibilidad social	Anual	PDF
ODS Contribution	Mapeo a objetivos ODS	Anual	PDF
Success Stories	Casos de éxito documentados	Bajo demanda	PDF, Web
7. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/impact/summary	Resumen ejecutivo de métricas actuales
GET	/api/v1/impact/metrics/{metric_name}	Serie temporal de una métrica específica
GET	/api/v1/impact/placements	Lista de placements con filtros
GET	/api/v1/impact/snapshots	Histórico de snapshots por periodo
GET	/api/v1/impact/funnel	Datos del funnel de conversión
GET	/api/v1/impact/geographic	Distribución geográfica de impacto
POST	/api/v1/impact/reports/generate	Generar reporte bajo demanda
GET	/api/v1/impact/ods-mapping	Contribución a ODS
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades impact_snapshot, placement_record. ETL básico.	App System
Sprint 2	Semana 3-4	Cálculo de métricas. Snapshot scheduler. APIs básicas.	Sprint 1
Sprint 3	Semana 5-6	Dashboard frontend. Executive summary. Visualizaciones.	Sprint 2
Sprint 4	Semana 7-8	Sistema de seguimiento. Encuestas NPS. ECA follow-up.	Sprint 3
Sprint 5	Semana 9-10	Reportes PDF. ODS mapping. SROI calculator. QA. Go-live.	Sprint 4
— Fin del Documento —
24_Empleabilidad_Impact_Metrics_v1.docx | Jaraba Impact Platform | Enero 2026
