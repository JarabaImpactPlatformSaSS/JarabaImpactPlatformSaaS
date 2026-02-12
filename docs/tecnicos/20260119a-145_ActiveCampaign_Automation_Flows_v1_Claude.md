


FLUJOS DE AUTOMATIZACIÓN
ActiveCampaign

Especificación Técnica de Email Marketing
y Automatización por Vertical


JARABA IMPACT PLATFORM


Versión: 1.0
Fecha: Enero 2026
Estado: Especificación para Implementación
Código: 145_ActiveCampaign_Automation_Flows_v1
 
Índice de Contenidos

1. Resumen Ejecutivo
2. Arquitectura de Integración ActiveCampaign + Drupal
3. Flujos Core (Transversales)
4. Vertical Empleabilidad
5. Vertical Emprendimiento
6. Vertical AgroConecta
7. Vertical ComercioConecta
8. Vertical ServiciosConecta
9. Configuración Técnica en ActiveCampaign
10. Roadmap de Implementación
 
1. Resumen Ejecutivo

Este documento especifica los flujos de automatización de email marketing para las 5 verticales del Ecosistema Jaraba utilizando ActiveCampaign Pro. Define secuencias de onboarding, nurturing, transaccionales y reactivación sincronizadas con los eventos ECA de Drupal.

1.1 Plan Recomendado: ActiveCampaign Pro

Característica	Justificación para Jaraba
Contenido Condicional	Personalizar emails por vertical/avatar sin duplicar templates
Segmentación Avanzada	Segmentar por tenant, rol, vertical, health_score
3 Usuarios	Equipo Jaraba + EDI + Soporte
A/B Testing en Automatizaciones	Optimizar secuencias de onboarding
Predictive Sending	IA para mejor hora de envío por usuario
Atribución de Conversiones	Medir ROI para FOC y justificación institucional
Precio Estimado	~€79-99/mes (1K-2.5K contactos)

1.2 Métricas Objetivo

Métrica	Target
Open Rate	>25% (benchmark SaaS: 21%)
Click Rate	>3.5% (benchmark: 2.5%)
Onboarding Completion	>60% completan secuencia de 7 días
Churn Reduction	-15% mediante secuencias de retención
Reactivation Rate	>10% usuarios inactivos reactivados
 
2. Arquitectura de Integración

2.1 Flujo de Datos Drupal → ActiveCampaign

Los eventos de Drupal (ECA) disparan webhooks hacia ActiveCampaign para sincronizar contactos, asignar tags y enrollar en automatizaciones.

Componente	Función
ECA Module (Drupal)	Detecta eventos: user_insert, order_complete, diagnostic_completed, etc.
Webhook Dispatcher	Envía payload JSON a ActiveCampaign API
ActiveCampaign API v3	Crea/actualiza contactos, aplica tags, inicia automatizaciones
Custom Fields	Sincroniza: vertical, tenant_id, avatar_type, health_score, plan_type
Tags	Estados dinámicos: onboarding_day_1, churn_risk, high_value, etc.

2.2 Custom Fields Requeridos

Campo	Tipo / Valores
vertical	Dropdown: empleabilidad, emprendimiento, agroconecta, comercioconecta, serviciosconecta
avatar_type	Dropdown: job_seeker, employer, producer, merchant, professional, entrepreneur, mentor
tenant_id	Text: ID único del tenant
tenant_name	Text: Nombre visible del tenant
plan_type	Dropdown: starter, growth, pro, enterprise
health_score	Number: 0-100
impact_credits	Number: Créditos acumulados
signup_date	Date: Fecha de registro
last_activity	Date: Última actividad en plataforma
stripe_customer_id	Text: ID de Stripe para tracking
institutional_program	Text: Andalucía +ei, SEPE, etc.
 
3. Flujos Core (Transversales)

Estos flujos aplican a todas las verticales con contenido condicional según el avatar/vertical del usuario.

3.1 AC-CORE-001: Onboarding Universal (7 días)

Parámetro	Valor
Trigger	Tag añadido: onboarding_start
Condición Entrada	Contact NOT has tag: onboarding_completed
Duración	7 días
Goal	Contact has tag: first_action_completed

Secuencia de Emails:

Email	Timing	Asunto	Contenido Clave
Email 1	Inmediato	¡Bienvenido/a a [VERTICAL]!	Presentación, primer Quick Win, CTA: completar perfil
Email 2	+1 día	Tu primer paso: [ACCIÓN_AVATAR]	Guía específica por avatar (job_seeker: CV, producer: primer producto)
Email 3	+3 días	¿Sabías que puedes...?	Feature discovery: funcionalidad clave no usada
Email 4	+5 días	Historias de éxito como tú	Case study de mismo avatar/vertical
Email 5	+7 días	¿Necesitas ayuda?	Oferta de soporte, link a KB, badge de bienvenida

3.2 AC-CORE-002: Reactivación Inactivos

Parámetro	Valor
Trigger	Tag añadido: inactive_14_days (desde ECA cron)
Condición	health_score < 40
Duración	21 días
Goal	Contact has tag: reactivated

Email	Timing	Asunto	Contenido Clave
Email 1	Inmediato	Te echamos de menos, [NOMBRE]	Recordatorio de valor, novedades desde su última visita
Email 2	+7 días	Mira lo que te estás perdiendo	Nuevas features, contenido relevante para su vertical
Email 3	+14 días	Último aviso: ¿seguimos juntos?	Oferta especial, llamada a acción urgente
Email 4	+21 días	Tu cuenta será archivada	FOMO final, link directo para reactivar

3.3 AC-CORE-003: Pre-Churn Retention

Parámetro	Valor
Trigger	Tag añadido: churn_risk (desde ECA cuando cancel_at_period_end = true)
Condición	plan_type != free
Duración	14 días antes de cancelación
Goal	Tag removed: churn_risk

Email	Timing	Asunto	Contenido Clave
Email 1	Inmediato	Hemos visto que quieres irte...	Encuesta de motivo, oferta de pausa en lugar de cancelación
Email 2	+3 días	Oferta especial para quedarte	20% descuento 3 meses si reactiva
Email 3	+7 días	¿Podemos hablar?	Oferta de llamada con Customer Success
 
4. Vertical Empleabilidad

4.1 AC-EMP-001: Onboarding Job Seeker

Parámetro	Valor
Trigger	Tag: onboarding_start AND avatar_type = job_seeker
Condición	vertical = empleabilidad
Goal	profile_completion >= 80%

Email	Timing	Asunto	Contenido Clave
Email 1	Inmediato	¡Tu camino al empleo empieza hoy!	Bienvenida, link directo a completar CV Builder
Email 2	+1 día	Completa tu perfil en 5 minutos	Checklist de perfil, tips para destacar
Email 3	+2 días	[X] ofertas coinciden con tu perfil	Preview de matching, CTA: ver ofertas
Email 4	+4 días	Tu Diagnóstico de Empleabilidad	Invitación a completar TTV (Diagnóstico Express)
Email 5	+7 días	Configura tus alertas de empleo	Tutorial de Job Alerts, personalización

4.2 AC-EMP-002: Aplicación a Oferta

Parámetro	Valor
Trigger	Webhook desde ECA-APP-001 (nueva aplicación)
Datos	job_title, company_name, match_score, application_id

Email	Timing	Asunto	Contenido Clave
Email 1	Inmediato	✓ Aplicación enviada: [JOB_TITLE]	Confirmación, próximos pasos, match_score
Email 2	+3 días	Prepárate para la entrevista	Tips de entrevista, recursos del LMS
Email 3	+7 días (si sin respuesta)	¿Sin noticias? No te desanimes	Otras ofertas similares, motivación

4.3 AC-EMP-003: Onboarding Employer

Parámetro	Valor
Trigger	Tag: onboarding_start AND avatar_type = employer
Goal	first_job_posted = true

Email	Timing	Asunto	Contenido Clave
Email 1	Inmediato	Bienvenido al Portal de Talento	Acceso, guía rápida, beneficios
Email 2	+1 día	Publica tu primera oferta GRATIS	Tutorial paso a paso, plantillas
Email 3	+3 días	[X] candidatos esperan tu oferta	Stats del marketplace, urgencia
Email 4	+7 días	Optimiza tus ofertas con IA	Feature discovery: AI Copilot

4.4 AC-EMP-004: Contratación Exitosa

Parámetro	Valor
Trigger	Webhook desde ECA-APP-003 (status = hired)
Destinatarios	Candidato + Empleador (flujos paralelos)

Email	Timing	Asunto	Contenido Clave
Al Candidato	Inmediato	🎉 ¡Enhorabuena! Has sido contratado/a	Celebración, badge, invitación a dejar review
Al Empleador	Inmediato	✓ Contratación completada	Confirmación, encuesta NPS, upsell a plan superior
 
5. Vertical Emprendimiento

5.1 AC-EMPR-001: Onboarding Emprendedor

Parámetro	Valor
Trigger	Tag: onboarding_start AND vertical = emprendimiento
Goal	diagnostic_completed = true

Email	Timing	Asunto	Contenido Clave
Email 1	Inmediato	¡Bienvenido/a al ecosistema emprendedor!	Filosofía Sin Humo, primer Quick Win
Email 2	+1 día	Tu Diagnóstico de Madurez Digital	CTA: completar Calculadora de Madurez
Email 3	+3 días	Tu Itinerario personalizado está listo	Resultado diagnóstico, path recomendado
Email 4	+5 días	Conoce a tu Mentor	Sistema de mentoring, reserva primera sesión
Email 5	+7 días	Únete a la comunidad	Grupos de colaboración, próximos eventos

5.2 AC-EMPR-002: Path Progress

Parámetro	Valor
Trigger	Webhook desde ECA-PATH-002 (step_completed)
Datos	path_name, module_name, step_name, progress_percent, credits_earned

Email	Timing	Asunto	Contenido Clave
Módulo 25%	Automático	¡Vas por buen camino! 25% completado	Celebración, preview siguiente módulo
Módulo 50%	Automático	¡Mitad del camino! 50% completado	Resumen logros, badges ganados
Módulo 75%	Automático	¡Ya casi! 75% completado	Motivación final, preview certificación
Path Completado	Automático	🎓 ¡Has completado tu itinerario!	Certificación, siguiente path recomendado

5.3 AC-EMPR-003: Andalucía +ei Específico

Parámetro	Valor
Trigger	Tag: programa_andalucia_ei
Condición	institutional_program = Andalucía +ei
Compliance	Mensajes alineados con requisitos STO/SAE

Email	Timing	Asunto	Contenido Clave
Alta Programa	Inmediato	Bienvenido/a al Programa Andalucía +ei	Info programa, horas requeridas, incentivo €528
Horas 25%	Automático	Llevas 15h de las 60h del programa	Progress tracking, motivación
Fase Inserción	Automático	¡Has completado la formación!	Siguiente fase, opciones Carril A/B
Incentivo	Post-firma	Tu incentivo de €528 está en proceso	Confirmación recibí, timeline pago
 
6. Vertical AgroConecta

6.1 AC-AGRO-001: Onboarding Productor

Parámetro	Valor
Trigger	Webhook desde ECA-AGRO-003 (producer_profile created)
Goal	first_product_published = true AND stripe_onboarding = complete

Email	Timing	Asunto	Contenido Clave
Email 1	Inmediato	¡Bienvenido/a a AgroConecta!	Intro marketplace, checklist primeros pasos
Email 2	+1 día	Configura tu tienda en 10 minutos	Tutorial Stripe Connect, logo, descripción
Email 3	+2 días	Sube tu primer producto	Guía de fotos, descripciones SEO, pricing
Email 4	+4 días	Activa la trazabilidad QR	Feature discovery: blockchain/QR
Email 5	+7 días	[X] compradores buscan productos como los tuyos	Stats marketplace, urgencia

6.2 AC-AGRO-002: Alertas Operativas

Parámetro	Valor
Tipo	Transaccionales (no marketing)
Prioridad	Alta - entrega inmediata

Email	Timing	Asunto	Contenido Clave
Stock Bajo	Inmediato	⚠️ Stock bajo: [PRODUCTO]	Alerta con cantidad actual, link reponer
Producto Caducando	-7 días	📅 [PRODUCTO] caduca en 7 días	Opciones: descuento, retirar, donar
Nuevo Pedido	Inmediato	🛒 ¡Nuevo pedido! #[ORDER_ID]	Detalles pedido, CTA: preparar envío
Pago Recibido	Inmediato	💰 Pago recibido: €[AMOUNT]	Confirmación Stripe, timeline transferencia

6.3 AC-AGRO-003: Nurturing Comprador

Email	Timing	Asunto	Contenido Clave
Post-Compra	+3 días	¿Qué tal tu pedido de [PRODUCTOR]?	Solicitud review, productos relacionados
Carrito Abandonado	+2 horas	¿Olvidaste algo en tu cesta?	Recordatorio productos, incentivo envío
Recompra	+30 días	¿Hora de reponer [PRODUCTO]?	Productos comprados previamente, descuento
 
7. Vertical ComercioConecta

7.1 AC-COM-001: Onboarding Comerciante

Parámetro	Valor
Trigger	Tag: onboarding_start AND avatar_type = merchant
Goal	store_setup_complete = true

Email	Timing	Asunto	Contenido Clave
Email 1	Inmediato	¡Tu comercio ahora es phygital!	Bienvenida, concepto online+offline
Email 2	+1 día	Conecta tu TPV en 5 minutos	Tutorial Square/SumUp integration
Email 3	+3 días	Crea tu primera Oferta Flash	Feature: ofertas tiempo limitado
Email 4	+5 días	QR Dinámicos para tu escaparate	Generar QR, casos de uso
Email 5	+7 días	Aparece en Google Maps	Local SEO, Google Business

7.2 AC-COM-002: Digest Diario Comerciante

Parámetro	Valor
Trigger	Cron diario 08:00 (hora local comercio)
Condición	merchant.daily_digest_enabled = true
Contenido	Dinámico basado en actividad del día anterior

Secciones del Digest:
• Resumen de ventas ayer vs. semana anterior
• Productos más vendidos
• Alertas de stock bajo
• Reviews pendientes de responder
• Tip del día (rotativo)
 
8. Vertical ServiciosConecta

8.1 AC-SERV-001: Onboarding Profesional

Parámetro	Valor
Trigger	Tag: onboarding_start AND avatar_type = professional
Goal	first_service_published = true AND calendar_connected = true

Email	Timing	Asunto	Contenido Clave
Email 1	Inmediato	Bienvenido/a a ServiciosConecta	Plataforma de confianza digital
Email 2	+1 día	Configura tu agenda inteligente	Conectar Google/Outlook Calendar
Email 3	+2 días	Define tus servicios y tarifas	Crear service_offerings
Email 4	+4 días	Activa el Buzón de Confianza	Documentos cifrados, firma digital
Email 5	+7 días	Tu primera consulta online	Setup Jitsi, mejores prácticas

8.2 AC-SERV-002: Ciclo de Cita

Parámetro	Valor
Tipo	Transaccional multi-step
Canales	Email + SMS (si habilitado)

Email	Timing	Asunto	Contenido Clave
Confirmación	Inmediato	✓ Cita confirmada: [SERVICIO]	Fecha, hora, ubicación/link, preparación
Recordatorio -24h	-24 horas	📅 Mañana: cita con [PROFESIONAL]	Recordatorio, documentos necesarios
Recordatorio -1h	-1 hora	⏰ Tu cita empieza en 1 hora	Link directo, últimos preparativos
Post-Cita	+2 horas	¿Qué tal tu consulta?	Solicitud review, próximos pasos
Follow-up	+7 días	¿Necesitas una cita de seguimiento?	Recomendación siguiente consulta

8.3 AC-SERV-003: Nurturing Cliente

Email	Timing	Asunto	Contenido Clave
No-Show	+1 día	Te esperábamos ayer...	Invitación a reagendar, políticas
Reactivación	+60 días sin cita	Hace tiempo que no nos vemos	Recordatorio servicios, novedades
 
9. Configuración Técnica ActiveCampaign

9.1 Listas Recomendadas

Lista	Propósito
Master List	Todos los contactos (requerida por AC)
Empleabilidad - Job Seekers	Candidatos de empleo
Empleabilidad - Employers	Empresas empleadoras
Emprendimiento - Entrepreneurs	Emprendedores y aspirantes
AgroConecta - Producers	Productores agrícolas
AgroConecta - Buyers	Compradores marketplace
ComercioConecta - Merchants	Comerciantes locales
ServiciosConecta - Professionals	Profesionales liberales
ServiciosConecta - Clients	Clientes de servicios
Institutional Programs	Andalucía +ei, SEPE, etc.

9.2 Tags Críticos

Categoría	Tags
Lifecycle	onboarding_start, onboarding_day_1/3/5/7, onboarding_completed, active, inactive_7d, inactive_14d, inactive_30d, churned
Engagement	high_engagement, low_engagement, reactivated, churn_risk
Value	high_value, expansion_candidate, referral_source
Features	used_ai_copilot, used_matching, used_calendar, stripe_connected
Programs	programa_andalucia_ei, programa_sepe, kit_digital
Actions	applied_job, posted_job, first_sale, first_booking

9.3 Webhooks desde Drupal ECA

Endpoint base: https://[ACCOUNT].api-us1.com/api/3/

Evento ECA	Acción ActiveCampaign
user_insert	POST /contacts (crear) + POST /contactTags (onboarding_start)
user_update (profile)	PUT /contacts/{id} (actualizar custom fields)
diagnostic_completed	POST /contactTags + POST /contactAutomations (enroll en path)
order_complete	POST /contactTags (first_sale) + event tracking
job_application_created	POST /contactTags (applied_job)
subscription_cancelled	POST /contactTags (churn_risk)
inactivity_14d (cron)	POST /contactTags (inactive_14d)
stripe_connected	POST /contactTags (stripe_connected)
 
10. Roadmap de Implementación

Fase	Timeline / Entregables
Fase 1: Setup (Semana 1)	Cuenta AC Pro, custom fields, listas, tags básicos. Integración API con Drupal.
Fase 2: Core Flows (Semana 2-3)	AC-CORE-001 (Onboarding Universal), AC-CORE-002 (Reactivación), AC-CORE-003 (Pre-Churn). Templates base con contenido condicional.
Fase 3: Empleabilidad (Semana 4)	AC-EMP-001 a 004. Integración con ECA de Job Board y Application System.
Fase 4: Emprendimiento (Semana 5)	AC-EMPR-001 a 003. Integración con Paths y Andalucía +ei.
Fase 5: Verticales Comerciales (Semana 6-7)	AgroConecta, ComercioConecta, ServiciosConecta. Flujos transaccionales.
Fase 6: Optimización (Semana 8+)	A/B testing, análisis de métricas, iteración en subject lines y contenido.

10.1 Inversión Estimada

Concepto	Estimación
ActiveCampaign Pro (anual)	~€950-1,200/año
Configuración inicial (consultoría)	€1,500-2,500 (one-time)
Desarrollo webhooks ECA	€2,000-3,000 (incluido en desarrollo Drupal)
Templates email (diseño)	€500-1,000
Copywriting secuencias	€1,000-2,000
Total Año 1	€6,000-9,500
Total Años 2+	€950-1,200/año


--- Fin del Documento ---
