DASHBOARD DE PROGRAMA
Program Dashboard
Para Gestores de Programa y Entidades Financiadoras
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	42_Emprendimiento_Dashboard_Program
Dependencias:	43_Impact_Metrics, 07_Core_MultiTenant, FOC
 
1. Resumen Ejecutivo
El Dashboard de Programa proporciona una vista ejecutiva del impacto agregado del programa de emprendimiento. Diseñado para gestores de programa, entidades financiadoras y stakeholders institucionales, ofrece métricas de impacto, análisis de cohortes, consumo de grants y herramientas de reporting para justificación de subvenciones.
1.1 Audiencias del Dashboard
Audiencia	Necesidad Principal	Acceso
Gestores de Programa	Monitoreo operativo diario, identificar problemas	Full access
Dirección Jaraba	Vista ejecutiva, tendencias, decisiones estratégicas	Full access
Entidades Financiadoras	Justificación de inversión, ROI social, compliance	Read-only seleccionado
Administraciones Públicas	Indicadores de política pública, ODS	Reportes exportados
Sponsors/Partners	Impacto de su contribución específica	Vista filtrada
1.2 KPIs Principales
•	Emprendedores Activos: Total de usuarios en programas activos
•	Negocios Creados: Negocios lanzados gracias al programa
•	GMV Generado: Volumen de facturación de emprendedores €
•	Empleos Creados: Puestos de trabajo generados
•	Tasa de Supervivencia: % negocios activos a 12 meses
•	SROI: Retorno social por euro invertido
 
2. Estructura del Dashboard
2.1 Vistas Disponibles
Vista	Contenido	Audiencia Principal
Executive Summary	KPIs principales, tendencias, alertas críticas	Dirección, Financiadores
Cohort Tracker	Análisis por promoción/cohorte de programa	Gestores de programa
Economic Impact	GMV, empleos, impuestos, multiplicador local	Financiadores, Administraciones
Survival Funnel	Funnel de supervivencia empresarial	Gestores, Dirección
Geographic Map	Distribución territorial del impacto	Administraciones
Mentor Performance	Métricas de mentores y sesiones	Gestores
Grant Tracker	Consumo de grants, burn rate, proyección	Finanzas, Financiadores
SROI Calculator	Cálculo interactivo de retorno social	Financiadores
2.2 Filtros Globales
•	Periodo: Este mes, trimestre, año, personalizado
•	Programa/Cohorte: Filtrar por edición específica de programa
•	Territorio: Provincia, comarca, municipio
•	Sector: Comercio, servicios, hostelería, agro...
•	Fuente de Financiación: Por grant/convocatoria específica
 
3. Widgets Principales
3.1 Executive Summary
Widget	Contenido	Visualización
Impact Counter	Contador animado de negocios creados	Número grande + sparkline
KPI Cards	6 KPIs principales con trend vs periodo anterior	Cards con flechas ↑↓
Alert Banner	Alertas críticas (cohortes en riesgo, grants por agotar)	Banner coloreado
Trend Chart	Evolución de emprendedores activos últimos 12 meses	Line chart
Quick Actions	Accesos rápidos: Generar reporte, Ver cohorte...	Botones
3.2 Cohort Tracker
Análisis comparativo de cohortes de programa:
Métrica por Cohorte	Descripción
Participantes	Inscritos, activos, completados, abandonos
Progreso Medio	% de avance promedio en itinerario
Negocios Lanzados	# y % que lanzaron negocio
Supervivencia	% activos a 3m, 6m, 12m post-programa
GMV Generado	Facturación total de la cohorte
NPS	Net Promoter Score de la cohorte
3.3 Grant Tracker
Control de consumo de subvenciones y grants:
Elemento	Contenido
Budget Total	Importe total del grant
Consumido	Importe gastado hasta la fecha
Comprometido	Importe en compromisos pendientes
Disponible	Saldo libre para gastar
Burn Rate	Velocidad de consumo mensual
Runway	Meses restantes al ritmo actual
Proyección	Fecha estimada de agotamiento
Alerta	Warning si runway < 3 meses
 
3.4 Economic Impact
Métrica	Cálculo	Visualización
GMV Total	SUM(facturación emprendedores)	€ con trend
Empleos Generados	SUM(employee_count) de todos los negocios	Número + breakdown
Masa Salarial	Empleos × Salario medio estimado	€ anual
Impuestos Generados	(IVA + IRPF + SS) estimados	€ anual
Multiplicador Local	GMV × factor territorial (1.5-2.0)	€ impacto económico
Coste por Empleo	Inversión programa / Empleos creados	€/empleo
3.5 SROI Calculator
Calculadora interactiva de Retorno Social de la Inversión:
•	Inputs configurables: Salario medio, multiplicadores, proxies de valor social
•	Componentes de valor: Salarios, impuestos, ahorro desempleo, valor económico local
•	Output: Ratio SROI (ej: 3.2:1 = por cada €1 invertido, €3.2 de valor social)
•	Exportable: PDF con metodología y cálculos para justificación
 
4. Sistema de Alertas
4.1 Tipos de Alerta
Tipo	Condición	Acción Sugerida
🔴 Crítica	Grant runway < 2 meses	Revisar presupuesto urgente
🔴 Crítica	Cohorte con > 30% abandonos	Intervención con facilitador
🟠 Warning	Supervivencia 12m < 60%	Revisar programa de seguimiento
🟠 Warning	NPS cohorte < 30	Encuesta de satisfacción detallada
🟡 Info	Nueva cohorte iniciando	Verificar onboarding
🟢 Positiva	SROI > objetivo	Documentar para reporting
4.2 Canales de Notificación
•	In-app: Banner en dashboard + icono de notificaciones
•	Email: Alertas críticas inmediatas, digest semanal de warnings
•	Slack: Integración opcional para equipos que lo usen
 
5. Sistema de Reporting
5.1 Reportes Generables
Reporte	Contenido	Formato
Monthly Summary	Resumen mensual de KPIs para dirección	PDF, PPT
Cohort Report	Análisis detallado de una cohorte específica	PDF, XLSX
Grant Justification	Informe para justificación de subvención	PDF con anexos
SROI Report	Cálculo detallado de retorno social	PDF
ODS Alignment	Contribución a Objetivos de Desarrollo Sostenible	PDF
Geographic Impact	Distribución territorial con mapas	PDF
Raw Data Export	Datos brutos para análisis externo	XLSX, CSV
5.2 Programación de Reportes
•	Automáticos: Monthly Summary el día 1 de cada mes a las 09:00
•	Bajo demanda: Cualquier reporte desde el dashboard con filtros aplicados
•	Programados: Configurar envío recurrente a stakeholders específicos
 
6. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/program-dashboard/summary	KPIs ejecutivos con filtros
GET	/api/v1/program-dashboard/cohorts	Lista de cohortes con métricas
GET	/api/v1/program-dashboard/cohorts/{id}	Detalle de cohorte específica
GET	/api/v1/program-dashboard/grants	Estado de grants activos
GET	/api/v1/program-dashboard/economic-impact	Métricas de impacto económico
GET	/api/v1/program-dashboard/sroi	Cálculo SROI con parámetros
GET	/api/v1/program-dashboard/alerts	Alertas activas
POST	/api/v1/program-dashboard/reports/generate	Generar reporte
GET	/api/v1/program-dashboard/geographic	Datos geográficos
 
7. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Executive Summary. KPI cards. API consolidada.
Sprint 2	Semana 3-4	Cohort Tracker. Grant Tracker.
Sprint 3	Semana 5-6	Economic Impact. SROI Calculator.
Sprint 4	Semana 7-8	Sistema de alertas. Reporting PDF. QA.
7.1 KPIs de Éxito
KPI	Target	Medición
Uso por gestores	> 3 visitas/semana	Analytics de acceso
Reportes generados	> 5/mes	Contador de exports
Tiempo en dashboard	> 5 min/sesión	Session duration
Alertas resueltas	> 80% en 48h	% alertas cerradas
--- Fin del Documento ---
