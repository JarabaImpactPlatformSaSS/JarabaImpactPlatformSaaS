
ADVANCED ANALYTICS & BUSINESS INTELLIGENCE
Especificación Técnica para Implementación
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Código:	116_Platform_Advanced_Analytics_v1
Estado:	Especificación para EDI
 
1. Resumen Ejecutivo
Sistema de analytics avanzado con Custom Report Builder, dashboards embebidos, exportación de datos, y analytics de impacto social. Complementa el FOC con capacidades de BI self-service.
1.1 Objetivos del Sistema
Objetivo	Métrica Target	Benchmark
Report adoption	40%+ usuarios activos	Industry: 30-50%
Time to insight	< 60 segundos	Self-service: < 2 min
Custom reports/tenant	5+ promedio	Power users: 10+
Data freshness	< 1 hora	Near real-time
Export usage	20%+ usuarios	Common need
1.2 Componentes del Sistema
•	Custom Report Builder: Crear informes arrastrando métricas y dimensiones
•	Dashboard Designer: Dashboards personalizados con widgets
•	Scheduled Reports: Informes automáticos por email/Slack
•	Data Export: CSV, Excel, PDF con permisos granulares
•	Impact Analytics: Métricas de impacto social por vertical
•	Cohort Analysis: Análisis de cohortes para retention
 
2. Arquitectura del Sistema
2.1 Stack Tecnológico
Componente	Tecnología	Justificación
Data Warehouse	PostgreSQL + TimescaleDB	Time-series optimizado
ETL Pipeline	Apache Airflow o Dagster	Orquestación robusta
Report Builder UI	React + React-Grid-Layout	Drag-drop nativo
Charting	Recharts + Apache ECharts	Flexibilidad + rendimiento
PDF Generation	Puppeteer	Renderizado fiel
Caching	Redis	Queries frecuentes
2.2 Data Pipeline
┌─────────────────────────────────────────────────────────────────┐
│                    DATA PIPELINE                                │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │   Drupal     │  │   Stripe     │  │   External APIs      │  │
│  │   Database   │  │   Events     │  │   (LinkedIn, etc.)   │  │
│  └──────┬───────┘  └──────┬───────┘  └──────────┬───────────┘  │
│         │                 │                     │              │
│         └─────────────────┼─────────────────────┘              │
│                           ▼                                    │
│              ┌─────────────────────────┐                       │
│              │     ETL Pipeline        │                       │
│              │  (Airflow/Dagster)      │                       │
│              └────────────┬────────────┘                       │
│                           ▼                                    │
│              ┌─────────────────────────┐                       │
│              │    Analytics DB         │                       │
│              │  (TimescaleDB)          │                       │
│              └────────────┬────────────┘                       │
│                           ▼                                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │   Report     │  │  Dashboard   │  │  Scheduled           │  │
│  │   Builder    │  │   Widgets    │  │  Exports             │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
 
3. Modelo de Datos
3.1 Entidad: custom_report
Informes personalizados creados por usuarios.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
name	VARCHAR(255)	Sí	Nombre del informe
description	TEXT	No	Descripción
owner_id	UUID FK	Sí	Usuario creador
tenant_id	UUID FK	Sí	Tenant propietario
data_source	ENUM	Sí	jobs|orders|users|courses|mentoring
metrics	JSON	Sí	Array de métricas seleccionadas
dimensions	JSON	Sí	Array de dimensiones/agrupaciones
filters	JSON	No	Filtros aplicados
visualization	ENUM	Sí	table|bar|line|pie|funnel|map
config	JSON	No	Configuración de visualización
is_shared	BOOLEAN	Sí	¿Compartido con tenant?
created_at	TIMESTAMP	Sí	Fecha creación
3.2 Entidad: dashboard
Dashboards personalizados con múltiples widgets.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
name	VARCHAR(255)	Sí	Nombre del dashboard
owner_id	UUID FK	Sí	Usuario creador
tenant_id	UUID FK	Sí	Tenant propietario
layout	JSON	Sí	Configuración React-Grid-Layout
widgets	JSON	Sí	Array de widgets con config
refresh_interval	INT	No	Auto-refresh en segundos
is_default	BOOLEAN	Sí	¿Dashboard por defecto?
is_shared	BOOLEAN	Sí	¿Compartido?
3.3 Entidad: scheduled_report
Configuración de informes programados.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
report_id	UUID FK	Sí	Informe a ejecutar
schedule	VARCHAR(100)	Sí	Cron expression
format	ENUM	Sí	pdf|csv|xlsx
recipients	JSON	Sí	Array de emails
delivery_channel	ENUM	Sí	email|slack|webhook
is_active	BOOLEAN	Sí	¿Activo?
last_run_at	TIMESTAMP	No	Última ejecución
next_run_at	TIMESTAMP	No	Próxima ejecución
 
4. Métricas por Vertical
4.1 Empleabilidad
Métrica	Descripción
Job Insertion Rate	% de usuarios que consiguen empleo
Time to Employment	Días promedio hasta contratación
Application to Interview	% de aplicaciones que pasan a entrevista
Skills Gap Analysis	Habilidades más demandadas vs ofrecidas
Course Completion Rate	% de cursos completados
Salary Improvement	% incremento salarial post-programa
4.2 AgroConecta/ComercioConecta
Métrica	Descripción
GMV (Gross Merchandise Value)	Valor total de transacciones
Average Order Value	Valor promedio por pedido
Producer Revenue Growth	Incremento ingresos productores
Product Sell-Through Rate	% de productos vendidos vs listados
Local Economic Impact	Euros generados en economía local
Food Miles Saved	Km evitados vs distribución tradicional
4.3 Emprendimiento
Métrica	Descripción
Business Survival Rate	% de negocios activos a 12 meses
Digital Maturity Improvement	Puntos ganados en diagnóstico
Revenue Growth	Incremento facturación post-programa
Jobs Created	Empleos generados por emprendimientos
Mentoring Session Completion	% de sesiones completadas
Funding Secured	Financiación conseguida
 
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/analytics/reports	Listar mis informes
POST	/api/v1/analytics/reports	Crear informe
GET	/api/v1/analytics/reports/{id}	Detalle de informe
POST	/api/v1/analytics/reports/{id}/execute	Ejecutar informe
POST	/api/v1/analytics/reports/{id}/export	Exportar a PDF/CSV/Excel
GET	/api/v1/analytics/dashboards	Listar dashboards
POST	/api/v1/analytics/dashboards	Crear dashboard
GET	/api/v1/analytics/metrics	Métricas disponibles por data source
GET	/api/v1/analytics/dimensions	Dimensiones disponibles
POST	/api/v1/analytics/scheduled-reports	Crear informe programado
 
6. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Data Warehouse setup. ETL básico desde Drupal.
Sprint 2	Semana 3-4	Report Builder UI v1. Tablas y gráficos básicos.
Sprint 3	Semana 5-6	Dashboard Designer. Widgets arrastables.
Sprint 4	Semana 7-8	Exportación PDF/CSV/Excel. Scheduled reports.
Sprint 5	Semana 9-10	Métricas de impacto por vertical.
Sprint 6	Semana 11-12	Cohort analysis. Performance optimization. Go-live.
6.1 Estimación de Esfuerzo
Componente	Horas Estimadas
Data Warehouse + ETL	60-80h
Report Builder UI	80-100h
Dashboard Designer	60-80h
Export Engine	40-50h
Scheduled Reports	30-40h
Impact Metrics	40-60h
TOTAL	310-410h
--- Fin del Documento ---
