
DASHBOARD JOBSEEKER
Portal del Candidato
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	22_Empleabilidad_Dashboard_JobSeeker
Dependencias:	Todos los módulos de Empleabilidad
 
1. Resumen Ejecutivo
El Dashboard JobSeeker es la experiencia central del candidato en el programa Impulso Empleo. Proporciona una vista unificada de su progreso, recomendaciones personalizadas, estado de candidaturas, y acceso rápido a todas las funcionalidades del ecosistema.
1.1 Principios de Diseño UX
•	Value-First: Mostrar valor inmediato desde el primer acceso
•	Actionable: Cada widget incluye acciones claras de siguiente paso
•	Progressive: Adaptar contenido según estado del usuario en su journey
•	Motivational: Celebrar logros y mantener momentum
•	Mobile-First: Diseño responsive optimizado para móvil
1.2 Secciones del Dashboard
Sección	Contenido	Posición
Header	Saludo personalizado, notificaciones, AI Copilot toggle	Top fixed
Progress Overview	Perfil completeness, nivel, racha, siguiente milestone	Hero area
Quick Actions	Acciones recomendadas personalizadas	Below hero
Learning Progress	Curso actual, path progress, siguiente lección	Main content
Job Search	Ofertas recomendadas, estado candidaturas	Main content
Achievements	Logros recientes, próximos a desbloquear	Sidebar/Bottom
Activity Feed	Actividad reciente, notificaciones	Bottom
 
2. Layout Adaptativo por Estado
2.1 Estado: Nuevo Usuario (post-diagnóstico)
Focus: Onboarding y primeros pasos
•	Hero: Resultado del diagnóstico con perfil asignado
•	CTA Principal: 'Empezar tu Ruta de Aprendizaje' con learning path recomendada
•	Quick Actions: Completar perfil, añadir foto, subir CV
•	Ocultar: Job search (aún no tiene perfil completo)
2.2 Estado: En Formación
Focus: Progreso en learning path
•	Hero: Progress bar de learning path con porcentaje
•	CTA Principal: 'Continuar: [Nombre de lección actual]'
•	Widget Destacado: Racha de días y XP semanal
•	Sidebar: Próximos logros a desbloquear
2.3 Estado: Buscando Empleo Activamente
Focus: Ofertas y candidaturas
•	Hero: Stats de búsqueda: aplicaciones enviadas, vistas de perfil
•	CTA Principal: 'X nuevas ofertas para ti'
•	Widget Destacado: Estado de candidaturas (pipeline visual)
•	Mostrar: Empresas seguidas con nuevas ofertas
2.4 Estado: Contratado (Success)
Focus: Celebración y siguiente etapa
•	Hero: Mensaje de felicitación con badge 'Contratado'
•	Widget: Encuesta de satisfacción NPS
•	CTA: Compartir historia de éxito, invitar amigos
 
3. Especificación de Widgets
3.1 Widget: Profile Completeness
Propiedad	Valor
Tipo	Progress ring con porcentaje y breakdown
Datos	candidate_profile.completeness_score, missing_sections
Acciones	Click en sección incompleta → navega a editar
Refresh	On profile update
API	GET /api/v1/profile/me/completeness
3.2 Widget: Learning Progress
Propiedad	Valor
Tipo	Card con progress bar y thumbnail de curso actual
Datos	user_learning_path.progress_percent, current_course, next_lesson
Estados	in_progress: mostrar continuar | completed: mostrar certificado
Acciones	'Continuar lección' → deep link a LMS
API	GET /api/v1/my-paths?status=active
3.3 Widget: Application Status
Propiedad	Valor
Tipo	Mini-pipeline horizontal o lista de cards
Datos	job_application grouped by status, last_activity
Counters	Enviadas | En revisión | Entrevistas | Ofertas
Highlight	Badge si hay actualizaciones nuevas
API	GET /api/v1/applications/my/summary
3.4 Widget: Jobs For You
Propiedad	Valor
Tipo	Carousel de job cards (3-5 visibles)
Datos	Top 10 jobs from Recommendation System
Card Info	Título, empresa, ubicación, match score, salario
Acciones	Ver detalle | Guardar | Aplicar rápido
API	GET /api/v1/recommendations/jobs?limit=10
 
3.5 Widget: Gamification Stats
Propiedad	Valor
Tipo	Compact stats bar con iconos
Datos	user_gamification: level, xp, streak, achievements_count
Visual	🔥 Racha: 7 días | ⭐ Nivel 4 | 🏆 12 logros
Acciones	Click → expandir a página de logros
API	GET /api/v1/gamification/my
3.6 Widget: Quick Actions (Profile Actions)
Propiedad	Valor
Tipo	Lista priorizada de action cards
Datos	Recommendation System profile actions
Max Items	3 acciones visibles, expandible
Formato	Icono + título + impacto esperado + CTA
API	GET /api/v1/recommendations/actions?limit=3
4. Estructura de Navegación
4.1 Menú Principal
Icono	Label	Destino
🏠	Inicio	/dashboard
📚	Formación	/learning (LMS)
💼	Empleos	/jobs (Job Board)
📄	Mi CV	/cv-builder
👤	Perfil	/profile
🏆	Logros	/achievements
⚙️	Ajustes	/settings
 
5. APIs del Dashboard
Método	Endpoint	Descripción
GET	/api/v1/dashboard/jobseeker	Datos consolidados del dashboard
GET	/api/v1/dashboard/jobseeker/state	Estado del usuario para layout adaptativo
GET	/api/v1/profile/me/completeness	Widget de completitud
GET	/api/v1/my-paths?status=active&limit=1	Widget de learning progress
GET	/api/v1/applications/my/summary	Widget de application status
GET	/api/v1/recommendations/feed?limit=20	Feed unificado de recomendaciones
GET	/api/v1/notifications?unread=true	Notificaciones no leídas
6. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Layout base. Header. Navegación. User state detection.	All modules
Sprint 2	Semana 3-4	Widget Profile Completeness. Widget Learning Progress.	Sprint 1
Sprint 3	Semana 5-6	Widget Application Status. Widget Jobs For You.	Sprint 2
Sprint 4	Semana 7-8	Gamification Stats. Quick Actions. Activity Feed.	Sprint 3
Sprint 5	Semana 9-10	API consolidación. Mobile optimization. QA. Go-live.	Sprint 4
— Fin del Documento —
22_Empleabilidad_Dashboard_JobSeeker_v1.docx | Jaraba Impact Platform | Enero 2026
