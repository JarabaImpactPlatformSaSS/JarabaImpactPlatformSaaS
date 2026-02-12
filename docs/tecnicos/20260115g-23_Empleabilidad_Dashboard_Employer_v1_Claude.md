
DASHBOARD EMPLOYER
Portal del Empleador
Métricas y Gestión de Reclutamiento
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	23_Empleabilidad_Dashboard_Employer
Dependencias:	13_Employer_Portal, 19_Matching_Engine
 
1. Resumen Ejecutivo
El Dashboard Employer extiende el Employer Portal (13) con métricas avanzadas de reclutamiento, analytics en tiempo real y herramientas de gestión del pipeline de candidatos. Este documento especifica los widgets, visualizaciones y KPIs específicos del dashboard.
1.1 Objetivos del Dashboard
•	Visibilidad: Estado en tiempo real de todas las ofertas y candidaturas
•	Eficiencia: Identificar cuellos de botella en el proceso de selección
•	Calidad: Medir calidad de candidatos y efectividad de matching
•	ROI: Demostrar valor del ecosistema vs otras fuentes
•	Acción: Facilitar decisiones rápidas con datos accionables
1.2 Diferencias con Employer Portal
Employer Portal (13)	Dashboard Employer (23)
Funcionalidades operativas (CRUD jobs, ATS)	Visualizaciones y métricas avanzadas
Gestión día a día	Análisis estratégico y reporting
Todos los planes	Pro y Enterprise principalmente
 
2. Layout y Estructura
2.1 Secciones del Dashboard
Sección	Contenido	Posición
Header	Filtro de periodo, selector de ofertas, notificaciones	Top fixed
KPI Cards	4 métricas principales en tarjetas	Hero row
Pipeline Overview	Funnel visual de candidatos por estado	Main left
Recent Activity	Últimas candidaturas y acciones	Main right
Time Metrics	Time-to-hire, time-to-response charts	Middle row
Source Analysis	Efectividad por fuente de candidatos	Bottom left
Top Candidates	Mejores matches pendientes de revisión	Bottom right
2.2 Filtros Globales
•	Periodo: Últimos 7 días | 30 días | 90 días | Año | Custom range
•	Ofertas: Todas | Activas | Selección específica
•	Comparación: vs periodo anterior (toggle)
 
3. KPI Cards Principales
KPI	Cálculo	Benchmark	Trend
Open Positions	COUNT(jobs WHERE status = 'published')	-	vs anterior
Applications	COUNT(applications) en periodo	-	% cambio
Avg. Time to Hire	AVG(hired_at - job.published_at)	< 30 días	días +/-
Offer Accept Rate	hired / offers_made × 100	> 70%	% puntos
3.1 Diseño de KPI Card
•	Valor Principal: Número grande y prominente
•	Trend Indicator: Flecha verde/roja con porcentaje de cambio
•	Sparkline: Mini gráfico de tendencia de 7 días
•	Benchmark: Indicador visual si está por encima/debajo del objetivo
 
4. Especificación de Widgets
4.1 Widget: Pipeline Funnel
Propiedad	Valor
Tipo	Funnel chart horizontal con etapas
Etapas	New → Screening → Interview → Offer → Hired
Datos	COUNT(applications) GROUP BY status
Interacción	Click en etapa → lista de candidatos en ese estado
Métricas	Conversion rate entre cada etapa
Refresh	Real-time (WebSocket) o cada 5 min
4.2 Widget: Time-to-Hire Trend
Propiedad	Valor
Tipo	Line chart con benchmark line
Datos	AVG(time_to_hire) por semana/mes
Benchmark	Línea horizontal a 30 días (objetivo)
Tooltip	Valor exacto, número de hires en periodo
Drill-down	Click → desglose por oferta
4.3 Widget: Source Effectiveness
Propiedad	Valor
Tipo	Horizontal bar chart + tabla
Fuentes	Jaraba Platform, LinkedIn, Indeed, Referrals, Direct, Other
Métricas	Applications, Interviews, Hires, Conversion %, Avg Quality Score
Highlight	Badge 'Ecosystem' para candidatos de Impulso Empleo
Insight	'Candidatos del ecosistema tienen 2.3x más probabilidad de ser contratados'
 
4.4 Widget: Top Candidates
Propiedad	Valor
Tipo	Lista de candidate cards compactas
Datos	Top 5 candidates por match_score con estado 'new' o 'screening'
Card Info	Foto, nombre, match score, oferta, días en pipeline
Badges	🎓 Ecosystem Graduate, ✓ Verified Skills, 🔥 High Match
Acciones	Ver perfil | Agendar entrevista | Descartar
4.5 Widget: Recent Activity
Propiedad	Valor
Tipo	Activity feed timeline
Eventos	Nueva aplicación, cambio de estado, entrevista agendada, oferta enviada
Formato	[Avatar] [Nombre] aplicó a [Oferta] · hace 2 horas
Limit	Últimos 10 eventos, expandible
Real-time	WebSocket para nuevos eventos
 
5. Analytics Avanzados (Pro/Enterprise)
5.1 Métricas Adicionales
Métrica	Descripción	Plan
Quality of Hire	Score promedio de candidatos contratados	Pro+
Cost per Hire	Coste total de reclutamiento / hires	Enterprise
Candidate Experience	NPS de candidatos (incluso rechazados)	Enterprise
Diversity Metrics	Distribución demográfica del pipeline	Enterprise
Predictive Analytics	Probabilidad de cierre por candidato	Enterprise
Benchmark Comparison	vs industria y región	Enterprise
5.2 Reportes Exportables
•	Recruitment Summary (PDF): Resumen mensual para dirección
•	Pipeline Report (XLSX): Estado detallado de todas las candidaturas
•	Source ROI (PDF): Análisis de efectividad por fuente
•	Time Analysis (XLSX): Desglose de tiempos por etapa
6. APIs del Dashboard
Método	Endpoint	Descripción
GET	/api/v1/employer/dashboard	Datos consolidados del dashboard
GET	/api/v1/employer/dashboard/kpis	KPI cards con trends
GET	/api/v1/employer/dashboard/pipeline	Datos del funnel
GET	/api/v1/employer/dashboard/time-metrics	Time-to-hire series
GET	/api/v1/employer/dashboard/sources	Source effectiveness
GET	/api/v1/employer/dashboard/top-candidates	Best matches pendientes
GET	/api/v1/employer/dashboard/activity	Activity feed
POST	/api/v1/employer/reports/generate	Generar reporte exportable
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Layout base. KPI cards. Filtros globales.	Portal 13
Sprint 2	Semana 3-4	Pipeline funnel. Recent activity. WebSocket integration.	Sprint 1
Sprint 3	Semana 5-6	Time metrics charts. Source effectiveness.	Sprint 2
Sprint 4	Semana 7-8	Top candidates widget. Advanced analytics (Pro+).	Sprint 3
Sprint 5	Semana 9-10	Report generation. Drill-downs. QA. Go-live.	Sprint 4
— Fin del Documento —
23_Empleabilidad_Dashboard_Employer_v1.docx | Jaraba Impact Platform | Enero 2026
