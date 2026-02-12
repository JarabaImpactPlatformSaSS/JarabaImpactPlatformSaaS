ONBOARDING PRODUCT-LED
Sistema de Onboarding Self-Service con Gamificación
Plataforma Core - Gap #3 Crítico
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	110_Platform_Onboarding_ProductLed
Dependencias:	Frontend React, jaraba_core module
 
1. Resumen Ejecutivo
Este documento especifica el sistema de onboarding product-led para la Jaraba Impact Platform. El objetivo es guiar a usuarios desde el registro hasta su primer 'Aha! moment' de forma autónoma, reduciendo la dependencia de soporte humano y mejorando conversión y retención.
1.1 Objetivos del Sistema
Objetivo	Descripción	KPI Target
Time to Value	Tiempo hasta primera acción de valor	< 5 minutos
Activation Rate	% usuarios que completan setup crítico	> 60%
Onboarding Completion	% que termina checklist completo	> 40%
Support Tickets	Reducción tickets durante onboarding	-50%
7-Day Retention	Usuarios activos a los 7 días	> 70%
1.2 Componentes del Sistema
•	Onboarding Checklists: Listas de tareas personalizadas por rol y vertical
•	Interactive Tours: Guías paso a paso contextuales dentro del producto
•	Hotspots & Tooltips: Señales visuales para features importantes
•	Progress Tracking: Visualización de progreso y milestones
•	Contextual Help: Ayuda inline basada en página actual y estado usuario
•	Celebration Moments: Gamificación con badges y confetti en logros
2. Arquitectura Técnica
2.1 Stack Tecnológico
Componente	Tecnología
Tour Library	Shepherd.js para tours interactivos
Tooltips	Tippy.js para tooltips y popovers
Checklist UI	React componente custom con drag-drop
Progress Storage	Backend Drupal + LocalStorage para UX
Analytics	Mixpanel/Amplitude para tracking eventos
Celebrations	canvas-confetti para animaciones
A/B Testing	GrowthBook para experimentos
 
3. Modelo de Datos
3.1 Entidad: onboarding_template
Templates de onboarding por rol/vertical.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
name	VARCHAR(128)	Nombre template	NOT NULL
role	VARCHAR(64)	Rol target	job_seeker|employer|producer|entrepreneur
vertical	VARCHAR(64)	Vertical target	empleabilidad|agroconecta|emprendimiento
plan_tier	VARCHAR(32)	Plan requerido	basic|pro|enterprise|all
steps	JSON	Definición de pasos	Array de step objects
estimated_time_min	INT	Tiempo estimado minutos	Mostrado al usuario
is_active	BOOLEAN	Template activo	DEFAULT TRUE
version	INT	Versión del template	For A/B testing
3.2 Entidad: user_onboarding_progress
Progreso de onboarding por usuario.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK users.uid, UNIQUE
template_id	INT	Template asignado	FK onboarding_template.id
status	VARCHAR(32)	Estado general	not_started|in_progress|completed|skipped
steps_completed	JSON	IDs de pasos completados	Array de step_ids
steps_skipped	JSON	IDs de pasos saltados	Array de step_ids
current_step	VARCHAR(64)	Paso actual	step_id o null
completion_percentage	DECIMAL(5,2)	% completado	Calculated
aha_moment_reached	BOOLEAN	Llegó al Aha! moment	DEFAULT FALSE
aha_moment_at	DATETIME	Cuando llegó al Aha!	NULLABLE, UTC
started_at	DATETIME	Inicio onboarding	NOT NULL, UTC
completed_at	DATETIME	Fin onboarding	NULLABLE, UTC
total_time_spent_min	INT	Tiempo total invertido	Tracked via events
 
4. Definición de Steps
4.1 Schema de Step
{
  "id": "complete_profile",
  "title": "Completa tu perfil",
  "description": "Añade tu foto, headline y experiencia",
  "type": "task",  // task | tour | video | link
  "category": "setup",  // setup | learn | engage
  "is_required": true,
  "estimated_time_min": 3,
  "trigger_url": "/profile/edit",
  "completion_condition": {
    "type": "field_filled",
    "entity": "user_profile",
    "fields": ["photo", "headline", "bio"]
  },
  "celebration": {
    "type": "confetti",
    "message": "¡Genial! Tu perfil ya está listo",
    "badge_id": "profile_complete"
  }
}
4.2 Tipos de Completion Conditions
Tipo	Descripción	Ejemplo
field_filled	Campo(s) de entidad completados	profile.photo != null
entity_created	Entidad específica creada	order.count >= 1
action_performed	Acción específica ejecutada	action: 'job_apply'
page_visited	Página visitada X segundos	url: '/jobs', duration: 30
tour_completed	Tour interactivo completado	tour_id: 'dashboard_tour'
video_watched	Video visto > 80%	video_id: 'intro', progress: 0.8
api_response	API devuelve condición	GET /api/check → success
5. Onboarding Flows por Rol
5.1 Job Seeker (Empleabilidad)
#	Step	Aha! Moment	Tiempo	Condición
1	Completar diagnóstico	-	1 min	diagnostic_result.exists
2	Subir CV o crear desde cero	-	3 min	cv_document.exists
3	Completar perfil básico	-	2 min	profile.completeness > 60%
4	Ver ofertas recomendadas	✓	1 min	page_visit: /jobs/recommended
5	Aplicar a primera oferta	✓✓	2 min	application.count >= 1
6	Configurar alertas de empleo	-	1 min	job_alert.count >= 1
5.2 Producer (AgroConecta)
#	Step	Aha! Moment	Tiempo	Condición
1	Configurar perfil de tienda	-	3 min	store_profile.complete
2	Añadir primer producto	✓	2 min	product.count >= 1
3	Usar Producer Copilot	✓✓	2 min	copilot_action: generate_description
4	Configurar métodos de pago	-	3 min	stripe_account.connected
5	Publicar tienda	-	1 min	store.status = published
6	Compartir en redes	-	1 min	share_action.count >= 1
 
6. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/onboarding/my-progress	Obtener progreso actual del usuario
POST	/api/v1/onboarding/steps/{id}/complete	Marcar step como completado
POST	/api/v1/onboarding/steps/{id}/skip	Saltar step opcional
GET	/api/v1/onboarding/tours/{id}	Obtener definición de tour
POST	/api/v1/onboarding/tours/{id}/complete	Marcar tour como completado
POST	/api/v1/onboarding/dismiss	Dismissar onboarding widget
POST	/api/v1/onboarding/restart	Reiniciar onboarding
GET	/api/v1/onboarding/help?context={page}	Ayuda contextual para página
7. Gamificación y Badges
7.1 Badges de Onboarding
Badge	Criterio	Puntos
first_steps	Completar paso 1	10
profile_pro	Perfil 100% completo	25
quick_starter	Onboarding < 10 min	50
aha_achieved	Llegar al Aha! moment	100
all_done	Completar 100% onboarding	200
explorer	Visitar todas las secciones	30
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades BD. API progress. Checklist widget UI.	Frontend React
Sprint 2	Semana 3-4	Shepherd.js tours. Completion conditions engine.	Sprint 1
Sprint 3	Semana 5-6	Templates por rol. Celebrations. Badges.	Sprint 2
Sprint 4	Semana 7-8	Analytics integration. A/B testing. Contextual help.	Sprint 3
Sprint 5	Semana 9-10	Optimización UX. Testing. Documentación. Go-live.	Sprint 4
8.1 Estimación de Esfuerzo
Componente	Horas	Prioridad
Backend: Entidades + API	30-40	P0
Checklist Widget UI	25-30	P0
Shepherd.js Tours Integration	30-40	P0
Completion Conditions Engine	25-35	P1
Gamification + Badges	20-25	P2
Analytics + A/B Testing	20-25	P2
TOTAL	150-195	-
— Fin del Documento —
