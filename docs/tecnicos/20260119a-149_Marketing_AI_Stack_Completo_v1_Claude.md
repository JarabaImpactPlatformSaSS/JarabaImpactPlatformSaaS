
STACK DE MARKETING IA COMPLETO
Departamento de Marketing Impulsado por Inteligencia Artificial
DECISIÓN EJECUTIVA
Se APRUEBAN los 3 módulos propuestos (jaraba_crm, jaraba_email, jaraba_social) y se especifican 5 extensiones adicionales para lograr cobertura del 95%+ en funciones de marketing digital.

Versión:	1.0
Fecha:	Enero 2026
Código:	149_Marketing_AI_Stack_Completo_v1
Estado:	APROBADO - Listo para Implementación
Cobertura Marketing:	95%+ de funciones de un departamento de marketing moderno
 
1. Resumen Ejecutivo
Este documento consolida el Stack de Marketing IA completo del Ecosistema Jaraba, incluyendo los módulos existentes, los 3 módulos propuestos (ahora aprobados) y 5 extensiones adicionales para cerrar todos los gaps identificados.
1.1 Métricas de Cobertura
Escenario	Cobertura	Estado
Stack Base (sin cambios)	60-65%	Insuficiente
+ 3 Módulos Aprobados	85-90%	Competitivo
+ 5 Extensiones Adicionales	95%+	Enterprise-Ready
1.2 Inversión Adicional Consolidada
Componente	Horas Est.	Coste €
jaraba_crm (Pipeline B2B)	40-50h	2,000-2,500€
jaraba_email (Email Marketing Nativo)	115-155h	5,750-7,750€
jaraba_social (Social Media Automation)	50-70h	2,500-3,500€
5 Extensiones para Gaps	60-85h	3,000-4,250€
TOTAL MARKETING AI STACK	265-360h	13,250-18,000€
 
2. Stack de Marketing Existente
Componentes de marketing ya especificados en la arquitectura oficial del Ecosistema Jaraba:
2.1 Content Marketing & SEO
Módulo	Funciones	Doc	Estado
jaraba_content_hub	Blog multi-tenant, AI Writing Assistant, Newsletter automatizado, SEO scoring	128	COMPLETO
jaraba_geo	Answer Capsules, /llms.txt, Schema.org automation, SSR para crawlers IA	02	COMPLETO
jaraba_ai_skills	Sistema de skills configurables, prompt templates, brand voice por tenant	129	COMPLETO
2.2 Lead Generation & Conversion
Herramienta	Funciones	Doc	Estado
Diagnóstico Express	Lead magnet TTV<45seg, scoring automático, segmentación por perfil	TTV	COMPLETO
Calculadora Madurez	Assessment interactivo, ROI calculator, email capture post-valor	CMD	COMPLETO
jaraba_onboarding	Product-led onboarding, checklists, tours interactivos, gamificación	110	COMPLETO
QR Dinámicos	UTM tracking, landing pages dinámicas, atribución de conversiones	81	COMPLETO
2.3 Inteligencia Artificial & Personalización
Módulo	Funciones	Doc	Estado
jaraba_ai (RAG)	Qdrant vector DB, strict grounding, 5 copilots verticales	20,44,93	COMPLETO
Purchase Intent Detection	AI Logging, clasificación de intención, lead scoring predictivo	KB-AI	COMPLETO
Content Gap Analyzer	Detecta contenido faltante, editorial queue, email digest semanal	KB-AI	COMPLETO
jaraba_tenant_knowledge	Training IA por tenant, FAQs, políticas, productos enriquecidos	130	COMPLETO
2.4 Analytics & Customer Success
Módulo	Funciones	Doc	Estado
jaraba_foc	MRR, ARR, Churn, LTV, CAC, forecasting, segmentación	FOC	COMPLETO
jaraba_analytics	Report Builder, dashboards embebidos, cohort analysis	116	COMPLETO
jaraba_success	Health Score, alertas churn predictivas, playbooks automáticos	113	COMPLETO
 
3. Módulos Aprobados (Nuevos)
✓ DECISIÓN: Los siguientes 3 módulos quedan OFICIALMENTE APROBADOS para desarrollo e inclusión en la arquitectura oficial del Ecosistema Jaraba.

3.1 jaraba_crm - Pipeline de Ventas B2B
Sistema CRM nativo para gestión del pipeline de ventas B2B, oportunidades y forecasting comercial.
Reemplaza:	HubSpot CRM, Pipedrive, Salesforce Essentials
Ahorro anual:	€600-3,000/año (según herramienta sustituida)
Horas desarrollo:	40-50 horas
Entidades y Campos
Entidad	Campos Clave	Integración
opportunity	title, contact, company, value, probability, stage, expected_close, owner	FOC (revenue forecast), ECA (automations)
opportunity_activity	type (call/email/meeting/note), description, date, next_action	Timeline visual, reminders
crm_contact	name, email, phone, company, role, source, lead_score	jaraba_email (sequences)
Pipeline Kanban (Taxonomía opportunity_stage)
Etapa	Descripción	Probabilidad	Color
lead_nuevo	Lead recién capturado, sin cualificar	10%	Gris
cualificado	BANT validado: Budget, Authority, Need, Timeline	25%	Azul
propuesta_enviada	Propuesta/presupuesto enviado al cliente	50%	Amarillo
negociacion	En negociación activa de términos	75%	Naranja
cerrado_ganado	Deal cerrado exitosamente	100%	Verde
cerrado_perdido	Oportunidad perdida (con razón)	0%	Rojo
 
3.2 jaraba_email - Email Marketing Nativo
Sistema de email marketing completo con secuencias automatizadas, templates MJML responsivos y tracking de engagement.
Reemplaza:	ActiveCampaign, Mailchimp, ConvertKit, Sendinblue
Ahorro anual:	€948-2,388/año (ActiveCampaign Pro €79-199/mes)
Horas desarrollo:	115-155 horas (core 60-80h + ECA 30-40h + templates 25-35h)
Backend envío:	SendGrid API (incluido en presupuesto infraestructura)
Entidades del Sistema
Entidad	Campos Clave	Función
email_sequence	name, trigger_event, vertical, avatar_type, status	Define secuencias de drip
email_sequence_step	sequence_id, order, delay_days, template_id, conditions	Pasos individuales
email_template	name, subject, mjml_content, html_compiled, variables	Templates MJML responsive
email_enrollment	user_id, sequence_id, current_step, status, enrolled_at	Tracking de usuarios en secuencias
email_tracking	enrollment_id, step_id, sent_at, opened_at, clicked_at	Métricas de engagement
Secuencias Predefinidas por Vertical (50 secuencias, 150+ emails)
Vertical	Secuencia	Emails	Trigger
Core	Onboarding Universal	7 emails / 14 días	user_registered
Core	Churn Prevention	5 emails / 21 días	health_score < 50
Empleabilidad	Job Seeker Activation	10 emails / 30 días	profile_created
Empleabilidad	Employer Nurturing	8 emails / 21 días	employer_registered
Emprendimiento	Diagnostic Follow-up	6 emails / 14 días	diagnostic_completed
AgroConecta	Producer Onboarding	8 emails / 21 días	producer_approved
ComercioConecta	Merchant Activation	7 emails / 14 días	store_created
ServiciosConecta	Professional Setup	6 emails / 14 días	profile_published
 
3.3 jaraba_social - Social Media Automation
Sistema de automatización de redes sociales con calendario editorial, generación de variantes IA y publicación multi-plataforma vía Make.com.
Reemplaza:	Buffer, Hootsuite, Later, Sprout Social
Ahorro anual:	€180-1,188/año (Buffer €15-99/mes)
Horas desarrollo:	50-70 horas (core 30-40h + Make.com 20-30h)
Arquitectura de Publicación
Flujo: Drupal → Webhook → Make.com → Claude API (variantes) → APIs Sociales → Callback
Entidad	Campos Clave	Función
social_post	content_source_id, platforms[], scheduled_at, variants[], status	Post programado con variantes
social_account	platform, account_name, access_token_ref, tenant_id	Cuentas conectadas por tenant
social_analytics	post_id, platform, impressions, engagement, clicks, conversions	Métricas de rendimiento
content_calendar	month, tenant_id, themes[], campaigns[], posts[]	Planificación editorial
Plataformas Soportadas
Plataforma	API	Formatos	Límites
LinkedIn	LinkedIn API v2	Post, Article, Carousel	3,000 chars
Instagram	Meta Graph API	Feed, Stories, Reels	2,200 chars
Facebook	Meta Graph API	Post, Link, Photo, Video	63,206 chars
X (Twitter)	X API v2	Tweet, Thread	280 chars
 
4. Extensiones para Cerrar Gaps
Las siguientes 5 extensiones cierran los gaps identificados aprovechando al máximo la arquitectura existente, minimizando desarrollo nuevo:
4.1 Paid Ads Integration (Extensión jaraba_analytics)
Gap cerrado:	Gestión y tracking de campañas de publicidad pagada
Solución:	Integración bidireccional con Meta Ads y Google Ads vía Make.com + tracking de conversiones en FOC
Horas adicionales:	15-20 horas
Funcionalidades:
•	Sync automático de gastos publicitarios desde Meta/Google → FOC (cálculo CAC preciso)
•	Pixel de conversión nativo: track conversions desde Drupal → plataformas ads
•	Dashboard de ROAS por campaña integrado en jaraba_analytics
•	Audiencias sincronizadas: exportar segmentos Drupal → Custom Audiences
Nota: La gestión creativa y configuración de campañas se realiza en las interfaces nativas de Meta/Google (más potentes y actualizadas). Esta extensión se centra en el tracking y atribución.
4.2 Retargeting Pixel Manager (Extensión jaraba_analytics)
Gap cerrado:	Gestión centralizada de pixels de retargeting multi-plataforma
Solución:	Módulo de configuración de pixels con inyección server-side y eventos ECA
Horas adicionales:	10-15 horas
Funcionalidades:
•	Configuración de pixels por tenant: Meta Pixel, Google Tag, LinkedIn Insight, TikTok Pixel
•	Eventos estándar automáticos: PageView, ViewContent, AddToCart, Purchase, Lead, CompleteRegistration
•	Server-side tracking (Conversions API) para mayor precisión post-iOS14
•	Consent Mode integrado con banner de cookies existente
4.3 Events & Webinars (Extensión jaraba_content_hub)
Gap cerrado:	Gestión de eventos, webinars y formaciones online
Solución:	Entidad event con integración Calendly/Zoom via webhooks + página de registro nativa
Horas adicionales:	15-20 horas
Funcionalidades:
•	Entidad content_event: title, description, date, type (webinar/workshop/meetup), capacity, speakers
•	Landing page de registro con countdown y social proof (plazas restantes)
•	Integración Calendly: sync bidireccional de reservas
•	Integración Zoom/Meet: generación automática de enlaces de sala
•	Secuencia email pre/post evento (reutiliza jaraba_email)
•	Replay automático: grabación disponible para registrados post-evento
4.4 A/B Testing Framework (Extensión jaraba_analytics)
Gap cerrado:	Experimentación y optimización de conversiones
Solución:	Sistema de experimentos con variantes, asignación aleatoria y análisis estadístico
Horas adicionales:	12-18 horas
Funcionalidades:
•	Entidad experiment: name, type (landing/email/cta/pricing), variants[], traffic_split, goal_metric
•	Variantes de landing pages: título, CTA, imágenes, layout
•	Variantes de email: subject line, contenido, hora de envío (integrado con jaraba_email)
•	Asignación consistente por usuario (cookie/user_id hash)
•	Cálculo de significancia estadística (p-value, confidence interval)
•	Auto-winner: selección automática cuando se alcanza significancia
4.5 Referral Program Universal (Extensión jaraba_onboarding)
Gap cerrado:	Programa de referidos para todas las verticales
Solución:	Generalizar sistema de referidos de ComercioConecta (doc 72) para toda la plataforma
Horas adicionales:	8-12 horas (reutiliza 80% del código existente)
Funcionalidades:
•	Código de referido único por usuario (formato: AMIGO-XXXX)
•	Recompensa bidireccional: referrer + referee reciben beneficio
•	Configuración de recompensas por vertical: descuento, créditos, meses gratis
•	Dashboard de referidos: visualizar invitaciones, conversiones, recompensas acumuladas
•	Leaderboard gamificado: top referrers con badges
•	Integración con jaraba_email: secuencia de activación de referidos
 
5. Mapa Completo del Stack de Marketing IA
5.1 Funciones del Funnel por Etapa
Etapa	Función	Componente	Estado
AWARENESS	Content Marketing / Blog	jaraba_content_hub	COMPLETO
	SEO Tradicional	jaraba_geo	COMPLETO
	GEO (IA Search Optimization)	jaraba_geo	COMPLETO
	Social Media Automation	jaraba_social	APROBADO
	Paid Ads Tracking	jaraba_analytics ext.	NUEVO
CONSIDERATION	Lead Magnets (Diagnósticos)	jaraba_diagnostic	COMPLETO
	Landing Pages Optimizadas	Frontend Architecture	COMPLETO
	Retargeting Pixels	jaraba_analytics ext.	NUEVO
	Email Nurturing Sequences	jaraba_email	APROBADO
	Webinars/Events	jaraba_content_hub ext.	NUEVO
CONVERSION	CRM Pipeline B2B	jaraba_crm	APROBADO
	Lead Scoring Avanzado	jaraba_crm + jaraba_ai	APROBADO
	A/B Testing	jaraba_analytics ext.	NUEVO
	Purchase Intent Detection	jaraba_ai (AI Logging)	COMPLETO
RETENTION	Customer Success Proactivo	jaraba_success	COMPLETO
	Churn Prevention Sequences	jaraba_email	APROBADO
	Health Score & Alertas	jaraba_success	COMPLETO
ADVOCACY	Referral Program	jaraba_onboarding ext.	NUEVO
	Reviews & Testimonials	Módulos verticales	COMPLETO
	User-Generated Content	jaraba_content_hub	COMPLETO
 
6. Diferenciadores IA del Stack
Lo que hace ÚNICO este stack de marketing frente a soluciones tradicionales:
Diferenciador	Descripción	Competencia
IA Nativa en Todo el Funnel	Desde generación de contenido hasta cierre de venta, IA integrada en cada paso	Herramientas aisladas que no se comunican
Content Gap Analyzer	Detecta automáticamente qué contenido crear basándose en preguntas sin respuesta del chatbot	Keyword research manual
Purchase Intent Detection	IA clasifica conversaciones y detecta leads calientes en tiempo real	Lead scoring basado en reglas estáticas
5 Copilots Verticales	Asistentes especializados por sector, no un chatbot genérico	Chatbots genéricos sin contexto de negocio
GEO Optimizado	Preparado para aparecer en respuestas de ChatGPT, Perplexity, Claude, Google AI	SEO tradicional sin optimización para IA
Multi-tenant Nativo	Cada franquicia/tenant tiene su propio marketing personalizado con brand voice	Una cuenta = una configuración
Strict Grounding	IA que no alucina, verificación NLI de respuestas, crítico para reputación	Respuestas sin verificación de fuentes
Variantes IA Multi-plataforma	Un contenido → variantes optimizadas para LinkedIn, Instagram, X automáticamente	Copiar/pegar manual
 
7. Roadmap de Implementación
7.1 Priorización Recomendada
Prio	Componente	Dependencias	Horas	Sprint
1	jaraba_email	Core Platform, SendGrid	115-155h	Sprint 8-11
2	jaraba_crm	Core Platform	40-50h	Sprint 12-13
3	Paid Ads Integration	jaraba_analytics, FOC	15-20h	Sprint 14
4	Retargeting Pixel Manager	jaraba_analytics	10-15h	Sprint 14
5	jaraba_social	jaraba_content_hub, Make.com	50-70h	Sprint 15-16
6	Events & Webinars	jaraba_content_hub, Calendly	15-20h	Sprint 17
7	A/B Testing Framework	jaraba_analytics	12-18h	Sprint 18
8	Referral Program Universal	jaraba_onboarding	8-12h	Sprint 18
7.2 Dependencias Críticas
1.	jaraba_email requiere SendGrid API configurado y templates MJML compilados
2.	jaraba_social requiere escenarios Make.com para publicación multi-plataforma
3.	Paid Ads Integration requiere cuentas de desarrollador en Meta y Google
4.	Events & Webinars requiere cuenta Calendly Pro o Zoom con API access
 
8. Resumen Final
✓ DEPARTAMENTO DE MARKETING IA COMPLETO
Con los 3 módulos aprobados + 5 extensiones, el Ecosistema Jaraba cuenta con un stack de marketing impulsado por IA con cobertura del 95%+ de las funciones de un departamento de marketing moderno.

8.1 Componentes Aprobados
Componente	Tipo	Horas	Estado
jaraba_crm	Módulo Nuevo	40-50h	APROBADO
jaraba_email	Módulo Nuevo	115-155h	APROBADO
jaraba_social	Módulo Nuevo	50-70h	APROBADO
Paid Ads Integration	Extensión jaraba_analytics	15-20h	APROBADO
Retargeting Pixel Manager	Extensión jaraba_analytics	10-15h	APROBADO
Events & Webinars	Extensión jaraba_content_hub	15-20h	APROBADO
A/B Testing Framework	Extensión jaraba_analytics	12-18h	APROBADO
Referral Program Universal	Extensión jaraba_onboarding	8-12h	APROBADO
TOTAL MARKETING AI STACK	3 módulos + 5 extensiones	265-360h	APROBADO
8.2 Gap Residual (5%)
Las siguientes funciones permanecen fuera del scope por ser más eficientes con herramientas externas:
•	Gestión creativa de Paid Ads: Interfaces nativas de Meta/Google son superiores y siempre actualizadas
•	Live Chat Humano: Los 5 copilots IA cubren 90%+ de consultas; escalado a ticket para el resto
•	Streaming de Webinars: Zoom/StreamYard (integrados vía API) son herramientas especializadas

Documento aprobado para inclusión en 02_Core_Modulos_Personalizados y 118_Roadmap_Implementacion
Ecosistema Jaraba | Enero 2026

