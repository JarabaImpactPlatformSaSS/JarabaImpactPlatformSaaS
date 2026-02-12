DASHBOARD DEL EMPRENDEDOR
Entrepreneur Dashboard
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	41_Emprendimiento_Dashboard_Entrepreneur
Dependencias:	25_Business_Diagnostic, 28_Digitalization_Paths, 30_Progress_Milestones
 
1. Resumen Ejecutivo
El Dashboard del Emprendedor es el centro de control personal donde cada usuario visualiza su progreso en el itinerario de digitalización, próximos pasos, métricas de negocio, sesiones de mentoría, recursos disponibles y logros obtenidos.
1.1 Objetivos del Dashboard
•	Visibilidad 360°: Estado completo del journey emprendedor en una vista
•	Orientación a la acción: Próximo paso siempre claro y accesible
•	Motivación continua: Gamificación con logros, badges y créditos de impacto
•	Métricas de negocio: KPIs básicos para emprendedores que ya venden
•	Acceso rápido: Shortcuts a herramientas, mentores y recursos
1.2 Stack Tecnológico
Componente	Tecnología
Frontend	React components con Tailwind CSS
Gráficos	Recharts para visualizaciones
Estado	SWR/React Query para datos en tiempo real
Backend	APIs REST de Drupal consolidando entidades
Cache	Redis para métricas pre-calculadas
Notificaciones	Activity stream widget embebido
 
2. Estructura del Dashboard
El dashboard se organiza en secciones modulares que se adaptan al estado del emprendedor.
2.1 Layout General
┌─────────────────────────────────────────────────────────────┐ │                    HEADER: Saludo + Quick Stats            │ ├─────────────────────────────────────────────────────────────┤ │  ┌────────────────────┐  ┌────────────────────────────────┐ │ │  │  PRÓXIMO PASO      │  │  PROGRESO DEL ITINERARIO       │ │ │  │  (CTA Principal)   │  │  (Barra + Fase actual)         │ │ │  └────────────────────┘  └────────────────────────────────┘ │ ├─────────────────────────────────────────────────────────────┤ │  ┌──────────────┐ ┌──────────────┐ ┌──────────────────────┐ │ │  │ MENTORÍA     │ │ LOGROS       │ │ MÉTRICAS NEGOCIO     │ │ │  │ Próx. sesión │ │ Badges       │ │ Ventas, Clientes...  │ │ │  └──────────────┘ └──────────────┘ └──────────────────────┘ │ ├─────────────────────────────────────────────────────────────┤ │  ┌────────────────────────────────────────────────────────┐ │ │  │            ACTIVIDAD RECIENTE / FEED                   │ │ │  └────────────────────────────────────────────────────────┘ │ └─────────────────────────────────────────────────────────────┘
2.2 Secciones del Dashboard
Sección	Contenido	Visibilidad
Header	Saludo personalizado, quick stats, notificaciones	Siempre
Próximo Paso	CTA prominente con la siguiente acción recomendada	Siempre
Progreso Itinerario	Barra de progreso, fase actual, hitos completados	Siempre
Mentoría	Próxima sesión, mentor asignado, tareas pendientes	Si tiene engagement activo
Logros	Badges ganados, créditos de impacto, racha actual	Siempre
Métricas Negocio	Ventas, clientes, conversión (si tiene tienda)	Si fase >= crecimiento
Canvas & Validación	Estado del canvas, hipótesis activas	Si usa estos módulos
Actividad	Feed de acciones recientes, notificaciones	Siempre
Recursos	Accesos rápidos a herramientas y materiales	Siempre
 
3. Widgets Detallados
3.1 Widget: Header con Quick Stats
Elemento	Fuente	Formato
Saludo	user.display_name + hora del día	'Buenos días, María'
Días en el programa	engagement.start_date - TODAY	'Día 45 de tu transformación'
Progreso global	progress_milestones agregado	'68% completado'
Créditos de impacto	SUM(impact_credits)	'2,450 créditos'
Notificaciones	COUNT(unread_notifications)	Badge con número
3.2 Widget: Próximo Paso
El widget más importante del dashboard. Siempre muestra UNA acción clara.
Estado del Usuario	Próximo Paso Sugerido
Sin diagnóstico	'Completa tu diagnóstico de negocio' → Formulario diagnóstico
Diagnóstico sin itinerario	'Descubre tu ruta de digitalización' → Selector de path
Itinerario sin tasks	'Comienza tu primera tarea' → Primera task del plan
Tasks en progreso	'Continúa: [nombre tarea]' → Task en curso
Task bloqueada	'Completa antes: [prereq]' → Tarea prerequisito
Sesión mentoría pronto	'Tu sesión con [mentor] en 2h' → Botón unirse
Canvas incompleto	'Completa tu modelo de negocio' → Canvas builder
Hipótesis pendiente	'Valida: [hipótesis]' → Experimento activo
3.3 Widget: Progreso del Itinerario
•	Barra de progreso: Visual con porcentaje y colores por fase
•	Fase actual: Badge con nombre de la fase (Diagnóstico, Acción, Optimización)
•	Hitos completados: Lista de milestones con check/pending
•	Tiempo estimado: '~3 semanas para completar fase actual'
3.4 Widget: Mentoría
Elemento	Contenido
Mentor asignado	Avatar + nombre + especialidad
Próxima sesión	Fecha/hora + botón 'Unirse' (si < 15min)
Sesiones restantes	X de Y sesiones del pack
Tareas pendientes	Lista de session_tasks no completadas
Rating del mentor	Estrellas + 'Dejar review' si sesión reciente
 
3.5 Widget: Logros y Gamificación
Sistema de gamificación para mantener engagement:
Badge	Criterio	Créditos
🎯 Diagnóstico Completo	Completar business_diagnostic	+100
🚀 Primera Tarea	Completar primera action_task	+50
⚡ Quick Win Champion	5 Quick Wins completados	+150
📊 Canvas Master	Canvas con completeness >= 80%	+200
🔬 Validador	Primera hipótesis validada	+150
💬 Mentor Conectado	Primera sesión de mentoría	+100
🏆 Fase Completada	Completar una fase del itinerario	+300
🔥 Racha 7 días	7 días consecutivos con actividad	+75
💼 Primera Venta	Primera transacción registrada	+500
3.6 Widget: Métricas de Negocio
Solo visible para emprendedores con Commerce activado o que reportan métricas:
Métrica	Fuente	Visualización
Ventas este mes	commerce_order o input manual	€ con trend vs mes anterior
Clientes activos	customer_count	Número con variación
Conversión web	Google Analytics API	% con sparkline
Ticket medio	revenue / orders	€ con comparativa
Leads generados	CRM integration o manual	Número mensual
 
4. Adaptación por Fase del Emprendedor
El dashboard se adapta según la fase del Método Jaraba™ en la que se encuentra el usuario.
Fase	Enfoque Dashboard	Widgets Destacados
Diagnóstico (Fase 1)	Completar evaluación inicial	Próximo paso, Diagnóstico pendiente, Recursos básicos
Acción (Fase 2)	Ejecutar tareas del plan	Progreso itinerario, Quick Wins, Mentoría, Canvas
Optimización (Fase 3)	Métricas y escalado	Métricas negocio, Validación MVP, Analytics
4.1 Dashboard Fase Diagnóstico
•	CTA principal: 'Completa tu diagnóstico'
•	Progreso del diagnóstico (secciones completadas)
•	Preview del resultado esperado
•	Recursos: 'Qué esperar del programa'
4.2 Dashboard Fase Acción
•	CTA principal: Siguiente tarea del plan
•	Kanban miniatura de tareas
•	Próxima sesión de mentoría
•	Estado del Canvas si aplica
4.3 Dashboard Fase Optimización
•	CTA principal: Hipótesis a validar o métrica a mejorar
•	Métricas de negocio prominentes
•	Scorecard de validación
•	Recomendaciones de IA para optimizar
 
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/dashboard/summary	Datos consolidados del dashboard
GET	/api/v1/dashboard/next-step	Próximo paso recomendado
GET	/api/v1/dashboard/progress	Progreso del itinerario
GET	/api/v1/dashboard/achievements	Logros y badges del usuario
GET	/api/v1/dashboard/mentoring	Estado de mentoría activa
GET	/api/v1/dashboard/metrics	Métricas de negocio
GET	/api/v1/dashboard/activity	Feed de actividad reciente
POST	/api/v1/dashboard/metrics	Reportar métricas manualmente
5.1 Respuesta API /dashboard/summary
{   "user": { "name": "María", "days_in_program": 45 },   "progress": { "overall": 68, "current_phase": "action", "phase_progress": 45 },   "next_step": { "type": "task", "title": "Crear perfil de Google My Business", "url": "/tasks/123" },   "mentoring": { "next_session": "2026-01-20T10:00:00Z", "mentor_name": "Carlos" },   "achievements": { "badges": 5, "credits": 2450, "streak_days": 12 },   "metrics": { "sales_mtd": 1250, "customers": 8, "conversion": 3.2 } }
 
6. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	API consolidada. Widget header y próximo paso.
Sprint 2	Semana 3-4	Widget progreso itinerario. Widget logros.
Sprint 3	Semana 5-6	Widget mentoría. Widget métricas negocio.
Sprint 4	Semana 7-8	Feed actividad. Adaptación por fase. QA.
6.1 KPIs de Éxito
KPI	Target	Medición
Tiempo en dashboard	> 2 min/sesión	Analytics de tiempo en página
CTR próximo paso	> 40%	% clics en CTA principal
Engagement diario	> 60%	% usuarios activos que visitan dashboard
Satisfacción	> 4/5	Encuesta de usabilidad
--- Fin del Documento ---
