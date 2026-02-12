DASHBOARD DEL MENTOR
Mentor Dashboard
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	33_Emprendimiento_Mentor_Dashboard
Dependencias:	31_Mentoring_Core, 32_Mentoring_Sessions
 
1. Resumen Ejecutivo
El Dashboard del Mentor es el centro de control para consultores y mentores certificados del ecosistema Jaraba. Proporciona visibilidad sobre clientes activos, sesiones programadas, ingresos generados, métricas de impacto y herramientas para gestión de disponibilidad y reporting.
1.1 Objetivos del Dashboard
•	Pipeline de clientes: Visión completa de emprendedores asignados y su progreso
•	Gestión de agenda: Sesiones programadas, disponibilidad, recordatorios
•	Métricas de negocio: Ingresos, sesiones realizadas, valoraciones
•	Impacto generado: KPIs de resultados de sus mentees para reporting
•	Certificación y nivel: Progreso hacia niveles superiores de mentor
1.2 Stack Tecnológico
Componente	Tecnología
Frontend	React + Tailwind CSS
Gráficos	Recharts para visualizaciones
Calendario	FullCalendar.js para agenda
Backend	APIs REST Drupal consolidadas
Tiempo real	SWR/React Query + polling
Exportación	PDF vía pdfmake.js
 
2. Estructura del Dashboard
2.1 Layout General
┌─────────────────────────────────────────────────────────┐ │  HEADER: Nombre + Nivel + Rating + Notificaciones      │ ├─────────────────────────────────────────────────────────┤ │ ┌─────────────┐ ┌─────────────┐ ┌─────────────────────┐ │ │ │ KPI: €€€    │ │ KPI: Sesiones│ │ KPI: Clientes      │ │ │ │ Este mes    │ │ Este mes    │ │ Activos            │ │ │ └─────────────┘ └─────────────┘ └─────────────────────┘ │ ├─────────────────────────────────────────────────────────┤ │ ┌───────────────────────┐ ┌───────────────────────────┐ │ │ │                       │ │                           │ │ │ │   CALENDARIO/AGENDA   │ │   PIPELINE DE CLIENTES    │ │ │ │   (Próximas sesiones) │ │   (Progreso mentees)      │ │ │ │                       │ │                           │ │ │ └───────────────────────┘ └───────────────────────────┘ │ ├─────────────────────────────────────────────────────────┤ │ ┌─────────────────────────────────────────────────────┐ │ │ │         MÉTRICAS DE IMPACTO (para reporting)        │ │ │ └─────────────────────────────────────────────────────┘ │ └─────────────────────────────────────────────────────────┘
2.2 Secciones del Dashboard
Sección	Contenido	Actualización
Header	Perfil, nivel, rating, notificaciones pendientes	Tiempo real
KPIs resumen	Ingresos, sesiones, clientes activos este mes	Diaria
Calendario	Sesiones programadas, disponibilidad	Tiempo real
Pipeline	Lista de mentees con progreso y próximas acciones	Diaria
Impacto	Métricas agregadas de resultados de mentees	Semanal
Valoraciones	Reviews recientes, rating promedio	Al recibir
 
3. Métricas y KPIs del Mentor
3.1 Métricas de Negocio
Métrica	Descripción	Objetivo
Ingresos mes	€ netos recibidos (post-comisión)	Según paquetes vendidos
Sesiones realizadas	Número de sesiones completadas	> 15/mes para Pro
Tasa de ocupación	Slots reservados / Slots disponibles	> 60%
Clientes activos	Engagements con sesiones pendientes	5-15 según nivel
Valoración media	Promedio de ratings de mentees	> 4.5 estrellas
Tasa de renovación	Clientes que compran pack adicional	> 30%
3.2 Métricas de Impacto (para Reporting)
Métrica	Descripción	Fuente
Mentees activos	Emprendedores en mentoría activa	mentoring_engagement
Progreso promedio	% avance en itinerario de mentees	digitalization_path
Madurez digital Δ	Incremento promedio en score de madurez	maturity_assessment
Tareas completadas	Total de tareas finalizadas por mentees	action_task
Negocios lanzados	Emprendedores que han empezado a vender	business_metrics
Empleos generados	Suma de empleos creados por mentees	impact_metrics
 
4. Pipeline de Clientes
Vista de todos los emprendedores asignados con su estado:
4.1 Estados del Pipeline
Estado	Descripción	Acción del Mentor
🆕 Nuevo	Acaba de contratar, sin sesión aún	Programar sesión inicial
📅 Activo	En proceso de mentoría regular	Seguimiento normal
⏸️ Pausado	Sin actividad >2 semanas	Reactivar contacto
⚠️ En riesgo	Última sesión con problemas o engagement bajo	Intervención especial
✅ Graduado	Completó programa satisfactoriamente	Proponer renovación
❌ Finalizado	Terminó sin completar o canceló	Encuesta de salida
4.2 Información por Cliente
•	Datos básicos: Nombre, negocio, sector, avatar
•	Progreso: % de itinerario, tareas pendientes, última actividad
•	Sesiones: Usadas/total, próxima sesión programada
•	Notas: Última nota de sesión, tareas asignadas
•	Alertas: Engagement expirando, sin actividad, etc.
 
5. Gestión de Disponibilidad
5.1 Configuración de Slots
Configuración	Opciones	Default
Días disponibles	L-D seleccionables	L-V
Horario	Desde-hasta por día	09:00-18:00
Duración sesión	30, 45, 60, 90 min	60 min
Buffer entre sesiones	0, 15, 30 min	15 min
Anticipación mínima	Horas antes para reservar	24 horas
Anticipación máxima	Días vista para reservar	30 días
5.2 Funcionalidades de Calendario
•	Vista semanal/mensual: Sesiones programadas, slots disponibles
•	Bloqueo de fechas: Vacaciones, días no disponibles
•	Sync externo: Exportación iCal, sync Google Calendar
•	Reprogramación: Drag & drop de sesiones, notificación automática
 
6. Sistema de Niveles de Mentor
Nivel	Requisitos	Beneficios
Base	Perfil completo + verificación	Publicación en directorio, 20% fee
Certificado	Formación Método Jaraba™ aprobada	Badge certificado, 15% fee, destacado
Premium	Certificado + 50 sesiones + 4.5★	12% fee, prioridad en matching
Élite	Premium + 200 sesiones + caso de éxito	10% fee, ponente en eventos, formador
6.1 Progreso Visible en Dashboard
Barra de progreso hacia el siguiente nivel mostrando:
•	Sesiones realizadas vs requeridas
•	Rating actual vs mínimo requerido
•	Certificaciones obtenidas vs pendientes
•	Beneficios que se desbloquearán al subir
 
7. Reporting para Entidades Financiadoras
Generación de informes para justificar impacto ante financiadores:
7.1 Tipos de Informe
Informe	Contenido	Frecuencia
Actividad mensual	Sesiones, horas, clientes atendidos	Mensual
Impacto trimestral	Métricas de resultados de mentees	Trimestral
Casos de éxito	Historias destacadas de emprendedores	A demanda
Justificación programa	Informe completo para subvenciones	Por proyecto
7.2 Datos Incluidos
•	Número de emprendedores atendidos
•	Horas de mentoría impartidas
•	Mejora promedio en madurez digital
•	Negocios lanzados/digitalizados
•	Empleos generados
•	Facturación generada por mentees
8. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/mentor/dashboard	Datos consolidados del dashboard
GET	/api/v1/mentor/kpis	KPIs de negocio del mentor
GET	/api/v1/mentor/pipeline	Lista de clientes con estado
GET	/api/v1/mentor/calendar	Sesiones programadas y disponibilidad
PUT	/api/v1/mentor/availability	Actualizar configuración disponibilidad
POST	/api/v1/mentor/block-dates	Bloquear fechas
GET	/api/v1/mentor/impact-metrics	Métricas de impacto agregadas
GET	/api/v1/mentor/reviews	Reviews recibidas
GET	/api/v1/mentor/level-progress	Progreso hacia siguiente nivel
POST	/api/v1/mentor/reports/generate	Generar informe PDF
9. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Layout dashboard. KPIs principales.
Sprint 2	Semana 3-4	Pipeline de clientes. Estados y alertas.
Sprint 3	Semana 5-6	Calendario y disponibilidad. Sync externo.
Sprint 4	Semana 7-8	Métricas de impacto. Generación de informes. QA.
--- Fin del Documento ---
